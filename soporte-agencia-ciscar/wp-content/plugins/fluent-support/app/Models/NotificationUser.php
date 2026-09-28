<?php

namespace FluentSupport\App\Models;

class NotificationUser extends Model
{
    protected $table = 'fs_notification_users';

    protected $fillable = [
        'notification_id',
        'user_id',
        'channel',
        'is_read',
        'read_at'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->channel) {
                $model->channel = 'web';
            }

            if ($model->is_read === null) {
                $model->is_read = 0;
            }

            $model->created_at = current_time('mysql');
            $model->updated_at = current_time('mysql');
        });

        static::updating(function ($model) {
            $model->updated_at = current_time('mysql');
        });
    }

    public function notification()
    {
        $class = __NAMESPACE__ . '\Notification';

        return $this->belongsTo($class, 'notification_id', 'id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', 0);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', 1);
    }
}
