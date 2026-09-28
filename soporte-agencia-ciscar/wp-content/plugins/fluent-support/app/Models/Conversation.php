<?php

namespace FluentSupport\App\Models;

use FluentSupport\App\Services\Helper;
use FluentSupport\Framework\Support\Arr;

class Conversation extends Model
{
    protected $table = 'fs_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'person_id',
        'message_id',
        'conversation_type',
        'content',
        'source',
        'is_important'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if(empty($model->content_hash)) {
                $model->content_hash = md5($model->content);
            }
            $model->created_at = current_time('mysql');
            $model->updated_at = current_time('mysql');
        });

        static::deleting(function ($model) {
            //Delete cc info
            Meta::where('object_type', 'response')->where('object_id', $model->id)->delete();
            // Delete conversation-level attachments — files first, then DB records
            $class = __NAMESPACE__ . '\Attachment';
            $attachments = $class::where('conversation_id', $model->id)->get();
            $class::purgeAttachments($attachments);
            $class::where('conversation_id', $model->id)->delete();
        });
    }

    /**
     * $searchable Columns in table to search
     * @var array
     */
    protected $searchable = [
        'content'
    ];

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param string $search
     * @return ModelQueryBuilder
     */
    public function scopeSearchBy($query, $search)
    {
        if ($search) {
            $fields = $this->searchable;
            $query->where(function ($query) use ($fields, $search) {
                $query->where(array_shift($fields), 'LIKE', "%$search%");

                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', "$search%");
                }
            });
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param string $type
     * @return ModelQueryBuilder
     */
    public function scopeFilterByType($query, $type)
    {
        $query->whereIn('conversation_type', $type);

        return $query;
    }

    /**
     * One2Many: Customer has to many Click Tickets
     * @return Model Collection
     */
    public function ticket()
    {
        $class = __NAMESPACE__ . '\Ticket';

        return $this->belongsTo(
            $class, 'ticket_id', 'id'
        );
    }

    /**
     * One2Many: Customer has to many Click Tickets
     * @return Model Collection
     */
    public function person()
    {
        $class = __NAMESPACE__ . '\Person';

        return $this->belongsTo(
            $class, 'person_id', 'id'
        );
    }

    /**
     * A Conversation belongs to a Customer.
     *
     * @return \FluentSupport\App\Models\Model
     */
    public function customer()
    {
        return $this->person();
    }

    public function attachments()
    {
        $class = __NAMESPACE__ . '\Attachment';
        return $this->hasMany($class, 'conversation_id', 'id');
    }

    /**
     * One2One: Conversation has cc info
     * @return Model Collection
     */
    public function ccinfo()
    {
        $class = __NAMESPACE__ . '\Meta';

        return $this->hasOne(
            $class, 'object_id', 'id'
        )->where('object_type', 'response')->where('key', 'settings');
    }


    public function getSettingsValue($valueKey = false, $default = false)
    {
        $exist = Meta::where('object_type', 'response')
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
        $exist = Meta::where('object_type', 'response')
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
            'object_type' => 'response',
            'key'         => 'settings',
            'object_id'   => $this->id,
            'value'       => maybe_serialize([
                $valueKey => $value
            ])
        ];

        Meta::create($settings);

        return $this;

    }

    public static function deleteAll($ticketId){
        $conversations = Conversation::where('ticket_id', $ticketId)->get();
        foreach ($conversations as $conversation) {
            $conversation->delete();
        }
    }

}
