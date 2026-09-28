<?php

namespace FluentSupport\App\Models;

use Exception;
use FluentSupport\App\Modules\PermissionManager;
use FluentSupport\App\Services\Helper;
use FluentSupport\App\Services\TicketHelper;
use FluentSupport\App\Services\TicketQueryService;
use FluentSupport\App\Services\Tickets\TicketService;
use FluentSupport\Framework\Support\Arr;

class Ticket extends Model
{
    protected $table = 'fs_tickets';

    protected $dates = ['waiting_since'];

    protected $appends = ['display_ticket_number'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_id',
        'agent_id',
        'product_id',
        'mailbox_id',
        'product_source',
        'privacy',
        'priority',
        'client_priority',
        'status',
        'title',
        'slug',
        'hash',
        'source',
        'message_id',
        'content',
        'last_agent_response',
        'last_customer_response',
        'waiting_since',
        'response_count',
        'first_response_time',
        'total_close_time',
        'resolved_at',
        'closed_by',
        'created_by',
        'serial_number',
        'ticket_number'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::slugify($model->title);
            }

            $model->hash = bin2hex(random_bytes(16));
            $model->content_hash = md5($model->content);

            $model->last_customer_response = current_time('mysql');
            $model->created_at = current_time('mysql');
            $model->updated_at = current_time('mysql');
            $model->waiting_since = current_time('mysql');

        });

        static::created(function ($model) {
            if (empty($model->serial_number) || empty($model->ticket_number)) {
                $model->assignTicketNumber();
            }
        });

        static::deleting(function ($model) {
            //Delete the ticket meta
            Meta::where('object_type', 'ticket_meta')->where('object_id', $model->id)->delete();
            //Delete all cc info for the ticket
            Meta::where('object_type', 'ticket')->where('object_id', $model->id)->delete();
            //Delete draft info
            Meta::where('object_type', '_fs_auto_draft')->where('object_id', $model->id)->delete();
            //Delete internal notifications and notification recipient rows for the ticket
            Notification::deleteByTicketId($model->id);
            //delete the responses first (their attachments are cleaned up by Conversation::deleting)
            Conversation::deleteAll($model->id);
            // Delete ticket-level attachments (conversation_id IS NULL) and remove the ticket upload directory
            $class = __NAMESPACE__ . '\Attachment';
            $ticketAttachments = $class::where('ticket_id', $model->id)->whereNull('conversation_id')->get();
            $class::purgeAttachments($ticketAttachments, $model->id);
            $class::where('ticket_id', $model->id)->whereNull('conversation_id')->delete();
        });
    }

    /**
     * $searchable Columns in table to search
     * @var array
     */
    protected $searchable = [
        'content',
        'title',
        'slug',
        'id',
        'serial_number',
        'ticket_number'
    ];

    /**
     * Local scope to filter tickets by search/query string
     * @param ModelQueryBuilder $query
     * @param string $search
     * @return ModelQueryBuilder
     */
    public function scopeSearchBy($query, $search)
    {

        if(!$search) {
            return $query;
        }

        if (strpos($search, ':')) {
            $array = explode(':', (string) $search);
            $column = $array[0];
            $value = $array[1];
            $columns = $this->fillable;
            $columns[] = 'id';

            if (in_array($column, $columns) && $value) {
                if (is_numeric($value)) {
                    $query->where($column, $value);
                } else {
                    $query->where($column, 'LIKE', "%$value%");
                }
                return $query;
            }
        }

        $fields = $this->searchable;
        $query->where(function ($query) use ($fields, $search) {
            $query->where(array_shift($fields), 'LIKE', "%$search%");
            foreach ($fields as $field) {
                $query->orWhere($field, 'LIKE', "%$search%");
            }
        });

        return $query;
    }

    /**
     * Local scope to filter tickets by different filtering condition
     * @param ModelQueryBuilder $query
     * @param mixed $search
     * @return ModelQueryBuilder
     */

    public function doSearchForAdvancedFilter($query, $search)
    {
        foreach ($search as $s) {
            $operator = $s['operator'];
            //If selected item for ticket either title or content
            if (in_array($s['property'], ['title', 'content'])) {
                //If the selected condition is contains, query operator id LIKE
                if ($operator == 'contains') {
                    $query = $query->where(function ($query) use ($s) {
                        $query->where($s['property'], 'LIKE', "%" . $s['value'] . "%");
                    });
                } elseif ($operator == 'not_contains') {
                    //If the selected condition is not_contains, query operator id NOT LIKE
                    $query = $query->where(function ($query) use ($s) {
                        $query->where($s['property'], 'NOT LIKE', '%' . $s['value'] . '%');
                    });
                }
            }

            //If selected item is Ticket Conversation Content
            if ($s['property'] == 'conversation_content') {
                $operator = $s['operator'];
                if ($operator == 'contains') {
                    $query = $query->whereHas('responses', function ($q) use ($s) {
                        $q->where('content', 'LIKE', "%" . $s['value'] . "%");
                    });

                } else if ($operator == 'not_contains') {
                    $query = $query->whereHas('responses', function ($q) use ($s) {
                        $q->where('content', 'NOT LIKE', "%" . $s['value'] . "%");
                    });
                }
            }

            //If selected item is Ticket created or Last Response or Customer Waiting For, or Last Agent Response or Last Customer Response
            if (in_array($s['property'], ['created_at', 'updated_at', 'waiting_since', 'last_agent_response', 'last_customer_response'])) {
                $query = (new \FluentSupport\App\Models\Ticket())->buildDateBaseFilterQuery($query, $s);
            }

            //If selected item is Ticket Status or Client Priority or Agent Priority or Tags or Product or Waiting For Reply
            if (in_array($s['property'], ['status', 'client_priority', 'priority', 'tags', 'product', 'waiting_for_reply', 'agent_id', 'mailbox_id'])) {
                $query = (new \FluentSupport\App\Models\Ticket())->buildPropertiesFilterQuery($query, $s);
            }
        }
        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param array $statuses
     * @return ModelQueryBuilder
     */
    public function scopeFilterByStatues($query, $statuses)
    {
        if ($statuses) {
            $query->whereIn('status', $statuses);
        }

        return $query;
    }

    /**
     * Who replied last on this ticket, derived from the already-loaded
     * last_agent_response / last_customer_response timestamp columns.
     *
     * Returns 'agent', 'customer', or null. This is the per-ticket value behind
     * the `waiting_for_reply` filter and mirrors the timestamp comparison in
     * scopeWaitingOnly() (which lives in SQL, so it can't share this PHP code).
     *
     * Note: boot() seeds last_customer_response on creation, so a brand-new
     * ticket with no agent reply correctly resolves to 'customer' (awaiting an
     * agent). null is reserved for the rare case where neither timestamp is set.
     *
     * @return string|null
     */
    public function getLastReplyByAttribute()
    {
        $agentAt    = $this->last_agent_response;
        $customerAt = $this->last_customer_response;

        if (!$agentAt && !$customerAt) {
            return null;
        }
        if (!$agentAt) {
            return 'customer';
        }
        if (!$customerAt) {
            return 'agent';
        }

        // Tie (same second) resolves to 'customer' — the waiting bias used by scopeWaitingOnly.
        return strtotime($customerAt) >= strtotime($agentAt) ? 'customer' : 'agent';
    }

    /**
     * Local scope to filter tickets by not response by agent
     * @param $query
     * @return mixed
     */
    public function scopeWaitingOnly($query)
    {
        $query->where(function ($q) {
            $q->whereColumn('last_agent_response', '<', 'last_customer_response')
                ->orWhereNull('last_agent_response')
                ->orWhere('status', 'new');
        });
        return $query;
    }

    /**
     * scopeApplyFilters method will filet ticket based on the selected filters
     * This method will get filter option as parameter, loop through and apply conditions in query
     * @param $query
     * @param $filters
     * @return ModelQueryBuilder
     */
    public function scopeApplyFilters($query, $filters)
    {
        $supportedColumns = ['product_id', 'client_priority', 'priority', 'mailbox_id'];
        foreach ($filters as $filterKey => $filterValue) {
            if (!$filterValue && ($filterValue !== '0' && $filterValue !== 0)) {
                continue;
            }
            //If filer using status
            if ($filterKey == 'status_type') {
                //Get list of ticket status
                $statusArray = Helper::getTkStatusesByGroupName($filterValue);
                if ($statusArray) {
                    //Apply filet where status in
                    $query->whereIn('status', $statusArray);
                }
            } else if (in_array($filterKey, $supportedColumns)) {
                // Use whereIn for all supported columns (they all now support multi-select)
                if (is_array($filterValue)) {
                    $query->whereIn($filterKey, $filterValue);
                } else {
                    $query->where($filterKey, $filterValue);
                }
            } else if ($filterKey == 'waiting_for_reply') {
                if ($filterValue != 'yes') {
                    continue;
                }
                //Apply filter where no response by agent
                $query = $this->scopeWaitingOnly($query);
            } else if ($filterKey == 'agent_id') {
                // Handle array of agent IDs for multi-select
                if (is_array($filterValue)) {
                    // Check if 'unassigned' is in the array
                    $hasUnassigned = in_array('unassigned', $filterValue);
                    $agentIds = array_filter($filterValue, function($v) {
                        return $v !== 'unassigned';
                    });

                    if ($hasUnassigned && !empty($agentIds)) {
                        // Include both unassigned and specific agents
                        $query->where(function($q) use ($agentIds) {
                            $q->whereNull('agent_id')
                              ->orWhereIn('agent_id', $agentIds);
                        });
                    } elseif ($hasUnassigned) {
                        // Only unassigned
                        $query->whereNull('agent_id');
                    } elseif (!empty($agentIds)) {
                        // Only specific agents
                        if (defined('FLUENTSUPPORTPRO')) {
                            if (isset($filters['watcher']) && $filters['watcher'] == 'watcher') {
                                $watcherTickets = [];
                                foreach ($agentIds as $agentId) {
                                    $watcherTickets = array_merge($watcherTickets, TicketHelper::getWatcherTicketIds($agentId));
                                }
                                $query->whereIn('id', array_unique($watcherTickets));
                            } else {
                                $query->whereIn('agent_id', $agentIds);
                            }
                        } else {
                            $query->whereIn('agent_id', $agentIds);
                        }
                    }
                } else {
                    // Single value (backward compatibility)
                    if ($filterValue == 'unassigned') {
                        $query->whereNull($filterKey);
                    } else {
                        if (defined('FLUENTSUPPORTPRO')) {
                            if (isset($filters['watcher']) && $filters['watcher'] == 'watcher') {
                                $watcherTickets = TicketHelper::getWatcherTicketIds($filterValue);
                                $query->whereIn('id', $watcherTickets);
                            } else {
                                //Apply filter, get only assigned ticket
                                $query->where($filterKey, $filterValue);
                            }
                        } else {
                            $query->where($filterKey, $filterValue);
                        }
                    }
                }
            } else if ($filterKey == 'agent_group') {
                $groupIds = is_array($filterValue) ? $filterValue : [$filterValue];
                $groupIds = array_filter(array_map('intval', $groupIds));
                if (!empty($groupIds)) {
                    $agentIds = TagPivot::where('source_type', 'agent_group')
                        ->whereIn('tag_id', $groupIds)
                        ->pluck('source_id')
                        ->toArray();
                    if ($agentIds) {
                        $query->whereIn('agent_id', $agentIds);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
            } else if ($filterKey == 'ticket_tags') {
                if (!$filterValue) {
                    continue;
                }
                //Apply filter where ticket only has this tag id
                $query->whereHas('tags', function ($q) use ($filterValue) {
                    $q->whereIn('tag_id', $filterValue);
                });
            }
        }

        return $query;
    }

    /**
     * Local scope to filter tickets by agent id
     * @param ModelQueryBuilder $query
     * @param int $agentId
     * @return ModelQueryBuilder
     */
    public function scopeFilterByAgentId($query, $agentId)
    {
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param int $customerId
     * @return ModelQueryBuilder
     */
    public function scopeFilterByCustomerId($query, $customerId)
    {
        $query->where('customer_id', $customerId);

        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param int $productId
     * @return ModelQueryBuilder
     */
    public function scopeFilterByProductId($query, $productId)
    {
        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param array $priorities
     * @return ModelQueryBuilder
     */
    public function scopeFilterByPriorities($query, $priorities)
    {
        if ($priorities) {
            $query->whereIn('priority', $priorities);
        }

        return $query;
    }

    /**
     * @param $filter
     * @return string[]
     */

    public static function parseRelationalFilterQueryMethods($filter)
    {
        // default operator = in
        $method = 'whereHas';
        $subMethod = 'whereIn';

        switch ($filter['operator']) {
            case 'not_in':
                $method = 'whereDoesntHave';
                $subMethod = 'whereIn';

                break;
            case 'in_all':
                $method = 'whereHas';
                $subMethod = 'where';

                break;
            case 'not_in_all':
                $method = 'whereDoesntHave';
                $subMethod = 'where';

                break;
        }

        return [$method, $subMethod];
    }

    /**
     * Parse filter to set proper operator and value for the filter query.
     *
     * @param array $filter
     * @return array
     */
    public static function filterParser($filter)
    {
        switch ($filter['operator']) {
            case 'before':
                $filter['operator'] = '<';
                $filter['value'] = $filter['value'] . ' 23:59:59';
                break;

            case 'after':
                $filter['operator'] = '>';
                $filter['value'] = $filter['value'] . ' 23:59:59';
                break;

            case 'date_equal':
                $filter['operator'] = 'LIKE';
                $filter['value'] = '%' . $filter['value'] . '%';
                break;

            case 'days_before':
                $filter['operator'] = '<';
                $filter['value'] = gmdate('Y-m-d', time() - $filter['value'] * 24 * 60 * 60);
                break;

            case 'days_within':
                $filter['operator'] = 'BETWEEN';
                $filter['value'] = [
                    gmdate('Y-m-d', time() - $filter['value'] * 24 * 60 * 60),
                    gmdate('Y-m-d') . ' 23:59:59'
                ];
                break;
            case 'date_range':
                $filter['operator'] = 'BETWEEN';
                if (isset($filter['value'][0]))
                    $filter['value'][0] .= ' 00:00:00';
                if (isset($filter['value'][1]))
                    $filter['value'][1] .= ' 23:59:59';
                break;
        }

        return $filter;
    }

    /**
     * @param \FluentSupport\Framework\Database\Orm\Builder|\FluentSupport\Framework\Database\Query\Builder $query
     * @param array $filters
     * @return ModelQueryBuilder
     */
    public function buildDateBaseFilterQuery($query, $filters)
    {
        $filter = static::filterParser($filters);
        $query->where(function ($dateQuery) use ($filter) {

            if ($filter['operator'] == 'BETWEEN') {
                $dateQuery->whereBetween($filter['property'], $filter['value']);
            } else {
                $dateQuery->where($filter['property'], $filter['operator'], $filter['value']);
            }
        });

        return $query;
    }

    /**
     * Relation builder
     * @param $relation
     * @param $query
     * @param $method
     * @param $subMethod
     * @param $subField
     * @param $filter
     * @param false $provider
     * @return ModelQueryBuilder
     */

    public static function buildRelationFilterQuery($relation, $query, $method, $subMethod, $subField, $filter, $provider = false)
    {
        if (in_array($filter['operator'], ['in_all', 'not_in_all']) && $filter['value']) {
            foreach ($filter['value'] as $item) {
                $query = static::buildRelationFilterQuery($relation, $query, $method, $subMethod, $subField, ['value' => $item, 'operator' => ''], $provider);
            }
        } else {
            $query = $query->{$method}($relation, function ($relationQuery) use ($subMethod, $subField, $filter, $provider) {
                $relationQuery = $relationQuery->{$subMethod}($subField, $filter['value']);

                if ($provider) {
                    $relationQuery = $relationQuery->where('provider', $provider);
                }

                return $relationQuery;
            });
        }

        return $query;
    }

    /**
     * get tickets by advanced filter segment data
     * @param $query
     * @param $filter
     * @return ModelQueryBuilder
     */

    public function buildPropertiesFilterQuery($query, $filter)
    {
        if (in_array($filter['property'], ['tags', 'product'])) {
            $subField = $filter['property'] == 'tags' ? 'tag_id' : 'product_id';
            list($method, $subMethod) = static::parseRelationalFilterQueryMethods($filter);
            $query = static::buildRelationFilterQuery($filter['property'], $query, $method, $subMethod, $subField, $filter);
        } elseif ($filter['property'] == 'waiting_for_reply') {
            if (($filter['value'] == 'yes' && $filter['operator'] == 'in') || ($filter['value'] == 'no' && $filter['operator'] == 'not_in')) {
                $query = $query->where(function ($q) {
                    $q->whereColumn('last_agent_response', '<', 'last_customer_response')
                        ->orWhereNull('last_agent_response')
                        ->orWhere('status', 'new');
                });
            } else {
                $query = $query->where(function ($q) {
                    $q->whereColumn('last_customer_response', '<', 'last_agent_response');
                });
            }
        } else {
            $method = $filter['operator'] == 'in' ? 'whereIn' : 'whereNotIn';
            $query = $query->{$method}($filter['property'], (array)$filter['value']);
        }
        return $query;
    }

    /**
     * method to search by properties
     * @param $provider
     * @param $query
     * @param $search
     * @param string $operator
     * @return ModelQueryBuilder
     */
    public function buildSearchableQuery($provider, $query, $search, $operator = 'LIKE')
    {
        switch ($provider) {
            case 'customer':
                $fields = (new Customer())->getSearchableFields();
                break;
            case 'agent':
                $fields = (new Agent())->getSearchableFields();
                break;
            default:
                $fields = $this->searchable;
                break;
        }

        $query->whereHas($provider, function ($query) use ($fields, $search, $operator) {
            $query->where(array_shift($fields), $operator, $search);

            $nameArray = explode(' ', (string) $search);

            if (count($nameArray) >= 2) {
                $query->orWhere(function ($q) use ($nameArray, $operator) {
                    $firstName = array_shift($nameArray);
                    $lastName = implode(' ', $nameArray);

                    $q->where('first_name', $operator, $firstName);
                    $q->where('last_name', $operator, $lastName);
                });
            }

            foreach ($fields as $field) {
                $query->orWhere($field, $operator, $search);
            }
        });

        return $query;
    }

    /**
     * Filter by ticket general properties like customer name, agent name etc
     * @param $provider
     * @param $query
     * @param $filters
     * @return ModelQueryBuilder
     */
    public function filterTicketByUser($provider, $query, $filters)
    {
        foreach ($filters as $filter) {
            if ($filter['operator'] == 'in' || $filter['operator'] == 'not_in') {
                $method = $filter['operator'] == 'in' ? 'whereIn' : 'whereNotIn';
                $query = $query->whereHas($provider, function ($q) use ($method, $filter) {
                    $q->{$method}($filter['property'], $filter['value']);
                });
            }

            if ($filter['operator'] == 'contains' || $filter['operator'] == 'not_contains') {
                $operator = $filter['operator'] == 'contains' ? 'LIKE' : 'NOT LIKE';
                $query->whereHas($provider, function ($q) use ($operator, $filter) {
                    $q->where($filter['property'], $operator, '%' . $filter['value'] . '%');
                });
            }

            if ($filter['operator'] == '=' || $filter['operator'] == '!=') {
                $operator = $filter['operator'];
                $query->whereHas($provider, function ($q) use ($operator, $filter) {
                    $q->where($filter['property'], $operator, $filter['value']);
                });
            }
        }
        return $query;
    }

    /**
     * One2Many: Customer has to many Click Tickets
     * @return Model Collection
     */
    public function responses()
    {
        $class = __NAMESPACE__ . '\Conversation';

        return $this->hasMany(
            $class, 'ticket_id', 'id'
        )->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
    }

    public function preview_response()
    {
        $class = __NAMESPACE__ . '\Conversation';

        return $this->hasOne(
            $class, 'ticket_id', 'id'
        );
    }

    public function tags()
    {
        $class = __NAMESPACE__ . '\TicketTag';

        return $this->belongsToMany(
            $class, 'fs_tag_pivot', 'source_id', 'tag_id'
        )->wherePivot('source_type', 'ticket_tag');
    }

    public function watchers()
    {
        $class = __NAMESPACE__ . '\TagPivot';

        return $this->hasMany($class, 'source_id', 'id')
            ->where('source_type', 'ticket_watcher')
            ->select(['tag_id']);
    }

    /**
     * One2one: Customer has to many Click Tickets
     * @return Model Collection
     */
    public function customer()
    {
        $class = __NAMESPACE__ . '\Customer';

        return $this->belongsTo(
            $class, 'customer_id', 'id'
        );
    }

    /**
     * One2one: Customer has to many Click Tickets
     * @return Model Collection
     */
    public function agent()
    {
        $class = __NAMESPACE__ . '\Agent';

        return $this->belongsTo(
            $class, 'agent_id', 'id'
        );
    }

    public function closed_by_person()
    {
        $class = __NAMESPACE__ . '\Person';

        return $this->belongsTo(
            $class, 'closed_by', 'id'
        );
    }

    public function created_by_person()
    {
        $class = __NAMESPACE__ . '\Agent';

        return $this->belongsTo(
            $class, 'created_by', 'id'
        );
    }

    public function product()
    {
        $class = __NAMESPACE__ . '\Product';

        return $this->belongsTo(
            $class, 'product_id', 'id'
        );
    }

    public function mailbox()
    {
        $class = __NAMESPACE__ . '\MailBox';

        return $this->belongsTo(
            $class, 'mailbox_id', 'id'
        );
    }


    public function deleteTicket()
    {
        /*
         * Action on ticket deleting
         *
         * @since v1.0.0
         * @param object $ticket
         */
        do_action('fluent_support/deleting_ticket', $this);
        // Delete the ticket
        $this->delete();
    }

    public static function getNextSerialNumber()
    {
        $businessSettings = Helper::getOption('global_business_settings', []);
        $minNumber = (int) ($businessSettings['min_serial_number'] ?? 1);
        $minNumber = (int) apply_filters('fluent_support/min_serial_number', $minNumber);

        try {
            $lastTicketNumber = self::query()->max('serial_number');
        } catch (\Exception $e) {
            $lastTicketNumber = null;
        }

        $nextNumber = ((int) $lastTicketNumber) + 1;

        return max($nextNumber, $minNumber);
    }

    public static function isMinimumSerialNumberEnabled()
    {
        $businessSettings = Helper::getOption('global_business_settings', []);
        return ($businessSettings['enable_min_serial_number'] ?? 'no') === 'yes';
    }

    public static function getTicketPrefix($ticket = null)
    {
        $businessSettings = Helper::getOption('global_business_settings', []);
        $prefix = self::isMinimumSerialNumberEnabled() ? trim((string) ($businessSettings['ticket_prefix'] ?? '')) : '';

        $productId = $ticket ? $ticket->product_id : null;

        return apply_filters('fluent_support/ticket_prefix', $prefix, $ticket, $productId);
    }

    public function getDisplayTicketNumberAttribute()
    {
        return $this->ticket_number ?: ($this->serial_number ?: $this->id);
    }

    public function scopeWherePublicIdentifier($query, $identifier)
    {
        return $query->where('serial_number', $identifier);
    }

    protected function assignTicketNumber()
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $nextNumber = $this->serial_number ?: (self::isMinimumSerialNumberEnabled() ? self::getNextSerialNumber() : $this->id);
            $ticketNumber = $this->ticket_number ?: (self::getTicketPrefix($this) . $nextNumber);

            try {
                self::where('id', $this->id)->update([
                    'serial_number' => $nextNumber,
                    'ticket_number' => $ticketNumber
                ]);
                $this->serial_number = $nextNumber;
                $this->ticket_number = $ticketNumber;

                return $nextNumber;
            } catch (\Exception $e) {
                if (stripos($e->getMessage(), 'duplicate') === false) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Could not allocate a unique ticket number.');
    }

    public static function slugify($title)
    {
        $slug = sanitize_title($title, 'support-ticket-' . time(), 'display');
        if (Ticket::where('slug', $slug)->first()) {
            $slug .= '-' . time();
        }
        return $slug;
    }

    public function hasTag($tagId)
    {
        $tags = $this->tags;
        foreach ($tags as $tag) {
            if ($tag->id == $tagId) {
                return true;
            }
        }

        return false;
    }

    public function attachments()
    {
        $class = __NAMESPACE__ . '\Attachment';
        return $this->hasMany($class, 'ticket_id', 'id')->where('conversation_id', NULL);
    }

    public function customData($scope = 'admin', $rendered = false)
    {
        if (!defined('FLUENTSUPPORTPRO')) {
            return [];
        }

        $fields = \FluentSupportPro\App\Services\CustomFieldsService::getFieldLabels($scope);

        if (!$fields) {
            return [];
        }

        $keys = array_keys($fields);

        $customRows = Meta::where('object_type', 'ticket_meta')->where('object_id', $this->id)
            ->whereIn('key', $keys)
            ->get();

        if (!$customRows) {
            return [];
        }

        $formattedData = [];

        $customRenderers = \FluentSupportPro\App\Services\CustomFieldsService::getCustomerRenderers();

        foreach ($customRows as $row) {
            $dataKey = $row->key;

            $value = $row->value;

            $fieldType = $fields[$dataKey]['type'];

            if ($value) {
                if (in_array($fieldType, $customRenderers) && $rendered) {
                    $value = apply_filters('fluent_support/custom_field_render_' . $fieldType, $value, $scope);
                } else if ($fieldType == 'checkbox') {
                    $value = array_values(array_filter(explode('|', $value)));
                }

                if (!is_array($value) && !is_object($value)) {
                    $formattedData[$dataKey] = links_add_target(make_clickable($value));
                } else {
                    $formattedData[$dataKey] = $value;
                }
            }
        }

        return $formattedData;
    }

    /**
     * @param $data This is the data that will be saved to the ticket_meta for custom fields
     * @return bool
     */
    public function syncCustomFields($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $fields = apply_filters('fluent_support/ticket_custom_fields', []);

        if (!$fields) {
            return false;
        }

        $keys = array_keys($fields);

        $validData = Arr::only($data, $keys);

        foreach ($validData as $dataKey => $validDatum) {
            if (empty($validDatum)) {
                Meta::where('object_type', 'ticket_meta')
                    ->where('object_id', $this->id)
                    ->where('key', $dataKey)
                    ->delete();
                continue;
            }

            if ($fields[$dataKey]['type'] == 'checkbox' || is_array($validDatum)) {
                $validDatum = implode('|', $validDatum);
                $validDatum = '|' . $validDatum . '|';
            }

            $exist = Meta::where('object_type', 'ticket_meta')
                ->where('object_id', $this->id)
                ->where('key', $dataKey)
                ->first();

            if ($exist) {
                $exist->value = $validDatum;
                $exist->save();
            } else {
                Meta::insert([
                    'object_type' => 'ticket_meta',
                    'object_id'   => $this->id,
                    'key'         => $dataKey,
                    'value'       => $validDatum
                ]);
            }
        }

        return true;
    }

    public function getLastAgentResponse()
    {
        $query = \FluentSupport\App\App::db()->table('fs_conversations')
            ->select(['fs_conversations.*'])
            ->where('fs_conversations.conversation_type', 'response')
            ->where('fs_conversations.ticket_id', $this->id)
            ->where('fs_persons.person_type', '=', 'agent')
            ->join('fs_persons', 'fs_persons.id', '=', 'fs_conversations.person_id')
            ->orderBy('fs_conversations.id', 'DESC');

        return $query->first();
    }

    public function getLastResponse()
    {
        return \FluentSupport\App\App::db()->table('fs_conversations')
            ->where('ticket_id', $this->id)
            ->where('conversation_type', 'response')
            ->latest('id')
            ->first();
    }

    /**
     * This method will assign tags to the ticket
     * @param $tagIds This is the array of tag ids that will be assigned to the ticket
     * @return \FluentSupport\App\Models\Ticket
     */
    public function applyTags($tagIds)
    {
        $result = false;

        if (!is_array($tagIds)) {
            $tagIds = array($tagIds);
        }

        foreach ($tagIds as $tagId) {
            if (!$this->hasTag($tagId)) {
                $this->tags()->attach($tagId, ['source_type' => 'ticket_tag']);
                $result = true;

                /*
                 * Action while tag added to ticket
                 *
                 * @since v1.0.0
                 * @param integer $tagId
                 * @param object  $ticket
                 */
                do_action('fluent_support/ticket_tag_added', $tagId, $this);
            }
        }
        return $result;
    }

    /**
     * This method will remove tags from ticket
     * @param $tagIds This is the array of tag ids that will be removed from the ticket
     * @return \FluentSupport\App\Models\Ticket
     */
    public function detachTags($tagIds)
    {
        $result = false;

        if (!is_array($tagIds)) {
            $tagIds = array($tagIds);
        }

        foreach ($tagIds as $tagId) {
            if ($this->hasTag($tagId)) {
                $this->tags()->detach($tagId);

                /*
                 * Action while tag removed from ticket
                 *
                 * @since v1.0.0
                 * @param integer $tagId
                 * @param object  $ticket
                 */
                do_action('fluent_support/ticket_tag_removed', $tagId, $this);
                $result = true;
            }
        }
        return $result;
    }

    /**
     * @deprecated Use TicketService::storeTicket() instead.
     */
    public function createTicket($ticketData, $maybeNewCustomer = false)
    {
        _deprecated_function(__METHOD__, '2.0.5', 'TicketService::storeTicket()');

        if (empty($ticketData['customer_id']) && $maybeNewCustomer) {
            $email = Arr::get($maybeNewCustomer, 'email');
            if (!$email || !is_email($email)) {
                return new \WP_Error('error', 'A valid email is required to create a ticket');
            }

            $existingCustomer = Customer::where('email', $email)->first();
            if ($existingCustomer) {
                $ticketData['customer_id'] = $existingCustomer->id;
            } else {
                $customerData = Arr::only($maybeNewCustomer, (new Customer())->getFillable());
                $customerData = array_filter($customerData);
                $createCustomer = Customer::create($customerData);
                if (!$createCustomer) {
                    return new \WP_Error('error', 'Customer could not be created');
                }
                $ticketData['customer_id'] = $createCustomer->id;
            }
        }

        if (empty($ticketData['customer_id'])) {
            return new \WP_Error('error', 'Ticket could not be created');
        }

        $customer = Customer::findOrFail($ticketData['customer_id']);

        return (new TicketService())->storeTicket($ticketData, $customer);
    }

    /**
     * This `createResponse` will create a response for a ticket
     * @param array $data
     * @param int $ticketId
     * @return array
     * @throws Exception
     */

    public static function countTicketByMailBoxId($mailbox_id)
    {
        return self::where('mailbox_id', $mailbox_id)->count();
    }

    public static function syncMailBoxId($mailbox_id, $fallback_id)
    {
        return self::where('mailbox_id', $mailbox_id)
            ->update([
                'mailbox_id' => $fallback_id
            ]);
    }

    public static function getTicketsQuery()
    {
        return self::with([
            'customer'         => function ($query) {
                $query->select(['first_name', 'last_name', 'email', 'id', 'avatar']);
            }, 'agent'         => function ($query) {
                $query->select(['first_name', 'last_name', 'id']);
            },
            'product',
            'tags',
            'preview_response' => function ($query) {
                $query->latest('id');
            }
        ]);
    }

    public function getSettingsValue($valueKey = false, $default = false)
    {
        $exist = Meta::where('object_type', 'ticket')
            ->where('key', 'settings')
            ->where('object_id', $this->id)
            ->first();

        if ($exist) {
            $value = Helper::safeUnserialize($exist->value);
            if ($valueKey) {
                if (!is_array($value)) {
                    return $default;
                }
                return Arr::get($value, $valueKey, $default);
            }
            return $value;
        }

        return $default;
    }

    public function updateSettingsValue($valueKey, $value)
    {
        $exist = Meta::where('object_type', 'ticket')
            ->where('key', 'settings')
            ->where('object_id', $this->id)
            ->first();

        if ($exist) {
            $existingValue = Helper::safeUnserialize($exist->value);

            if (!is_array($existingValue)) {
                $existingValue = [];
            }

            $existingValue[$valueKey] = $value;

            $exist->value = maybe_serialize($existingValue);
            $exist->save();
            return $this;
        }

        $settings = [
            'object_type' => 'ticket',
            'key'         => 'settings',
            'object_id'   => $this->id,
            'value'       => maybe_serialize([
                $valueKey => $value
            ])
        ];

        Meta::create($settings);

        return $this;

    }

}
