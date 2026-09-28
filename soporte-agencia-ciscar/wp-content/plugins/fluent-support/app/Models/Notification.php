<?php

namespace FluentSupport\App\Models;

use FluentSupport\App\Services\Notifications\NotificationSettings;
use FluentSupport\App\Services\Helper;

class Notification extends Model
{
    protected $table = 'fs_notifications';

    protected $fillable = [
        'actor_id',
        'ticket_id',
        'conversation_id',
        'event_type',
        'category',
        'payload'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = current_time('mysql');
            $model->updated_at = current_time('mysql');
        });

        static::updating(function ($model) {
            $model->updated_at = current_time('mysql');
        });

        static::deleting(function ($model) {
            NotificationUser::where('notification_id', $model->id)->delete();
        });
    }

    public function setPayloadAttribute($payload)
    {
        $this->attributes['payload'] = maybe_serialize($payload);
    }

    public function getPayloadAttribute($payload)
    {
        return Helper::safeUnserialize($payload);
    }

    public function recipients()
    {
        $class = __NAMESPACE__ . '\NotificationUser';

        return $this->hasMany($class, 'notification_id', 'id');
    }

    public function ticket()
    {
        $class = __NAMESPACE__ . '\Ticket';

        return $this->belongsTo($class, 'ticket_id', 'id');
    }

    public function conversation()
    {
        $class = __NAMESPACE__ . '\Conversation';

        return $this->belongsTo($class, 'conversation_id', 'id');
    }

    public function actor()
    {
        $class = __NAMESPACE__ . '\Person';

        return $this->belongsTo($class, 'actor_id', 'id');
    }

    public static function deleteByTicketId($ticketId)
    {
        if (!(new NotificationSettings())->notificationTablesExist()) {
            return;
        }

        $ticketId = (int) $ticketId;

        if (!$ticketId) {
            return;
        }

        (new static())->getConnection()->transaction(function () use ($ticketId) {
            do {
                $notificationIds = static::where('ticket_id', $ticketId)
                    ->orderBy('id', 'ASC')
                    ->limit(100)
                    ->pluck('id')
                    ->toArray();

                if ($notificationIds) {
                    NotificationUser::whereIn('notification_id', $notificationIds)->delete();
                    static::whereIn('id', $notificationIds)->delete();
                }
            } while ($notificationIds);
        });
    }

    public function scopeByCategory($query, $category)
    {
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        return $query;
    }
}
