<?php

namespace FluentSupport\App\Models;

use FluentSupport\App\Services\Helper;

class Attachment extends Model
{
    protected $table = 'fs_attachments';

    protected $hidden = ['full_url', 'file_path'];

    protected $appends = ['secureUrl'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'person_id',
        'conversation_id',
        'file_type',
        'file_path',
        'full_url',
        'title',
        'driver',
        'file_size',
        'status',
        'settings'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $uid = wp_generate_uuid4();
            $model->file_hash = md5($uid . wp_rand(0, 1000));
        });
    }

    /**
     * Delete a collection of attachments — physical files first, then DB records.
     * Handles local files and fires the remote-driver hook for cloud-stored files.
     * Optionally removes the ticket upload directory after files are purged.
     *
     * @param \FluentSupport\Framework\Database\Orm\Collection|array $attachments
     * @param int|null $ticketId  When provided, removes the ticket_{id} upload directory.
     */
    public static function purgeAttachments($attachments, $ticketId = null)
    {
        foreach ($attachments as $attachment) {
            if ($attachment->driver && $attachment->driver !== 'local') {
                do_action('fluent_support/delete_remote_attachment_' . $attachment->driver, $attachment);
            } elseif ($attachment->file_path && file_exists($attachment->file_path)) {
                wp_delete_file($attachment->file_path);
            }
        }

        if ($ticketId) {
            $uploadDir = wp_upload_dir();
            $ticketDir = $uploadDir['basedir'] . '/' . FLUENT_SUPPORT_UPLOAD_DIR . '/ticket_' . $ticketId;
            if (is_dir($ticketDir)) {
                if (!class_exists('\WP_Filesystem_Direct')) {
                    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
                    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
                }
                (new \WP_Filesystem_Direct(false))->rmdir($ticketDir, true);
            }
        }
    }

    public function setSettingsAttribute($settings)
    {
        $this->attributes['settings'] = \maybe_serialize($settings);
    }

    public function getSettingsAttribute($settings)
    {
        return Helper::safeUnserialize($settings);
    }

    /**
     * Accessor to get dynamic full_name attribute
     * @return string
     */
    public function getSecureUrlAttribute()
    {
        return add_query_arg([
            'fst_file'    => $this->file_hash,
            'secure_sign' => hash_hmac('sha256', $this->id . '|' . gmdate('YmdH'), wp_salt('secure_auth'))
        ], site_url('/index.php'));
    }
}
