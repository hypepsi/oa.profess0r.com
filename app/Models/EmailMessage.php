<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    protected $fillable = [
        'email_account_id',
        'message_id',
        'uid',
        'folder',
        'subject',
        'from_name',
        'from_email',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
        'body_html',
        'body_text',
        'is_read',
        'is_starred',
        'has_attachments',
        'direction',
        'ai_summary',
        'ai_summarized_at',
        'sent_at',
    ];

    protected $casts = [
        'to_addresses'     => 'array',
        'cc_addresses'     => 'array',
        'bcc_addresses'    => 'array',
        'is_read'          => 'boolean',
        'is_starred'       => 'boolean',
        'has_attachments'  => 'boolean',
        'ai_summarized_at' => 'datetime',
        'sent_at'          => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getFromDisplayAttribute(): string
    {
        if ($this->from_name) {
            return "{$this->from_name} <{$this->from_email}>";
        }
        return $this->from_email ?? '';
    }

    public function getPreviewAttribute(): string
    {
        $text = strip_tags($this->body_html ?? $this->body_text ?? '');
        return mb_substr(trim($text), 0, 120);
    }

    public function getBodyForDisplayAttribute(): string
    {
        return $this->body_html ?? nl2br(e($this->body_text ?? ''));
    }
}
