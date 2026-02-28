<?php

namespace App\Models;

use App\Models\Concerns\Loggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use Loggable;

    protected $fillable = [
        'name',
        'email',
        'company',
        'password_encrypted',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'is_active',
        'last_synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
        'imap_port'      => 'integer',
        'smtp_port'      => 'integer',
    ];

    protected $hidden = ['password_encrypted'];

    // -------------------------------------------------------------------------
    // Loggable overrides
    // -------------------------------------------------------------------------

    public function getActivityLogIdentifier(): string
    {
        return $this->email;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Decrypt and return the plain-text password.
     */
    public function getPassword(): string
    {
        return Crypt::decryptString($this->password_encrypted);
    }

    /**
     * Encrypt and store the password.
     */
    public function setPassword(string $plaintext): void
    {
        $this->password_encrypted = Crypt::encryptString($plaintext);
        $this->save();
    }

    public function getUnreadCount(): int
    {
        return $this->messages()->where('is_read', false)->where('folder', 'INBOX')->count();
    }

    // -------------------------------------------------------------------------
    // Company registry
    // -------------------------------------------------------------------------

    public static function companyOptions(): array
    {
        return [
            'bunnycommunications' => 'BunnyCommunications',
            'nexustel'            => 'Nexustel',
            'infratel'            => 'Infratel',
        ];
    }

    public static function companyColor(string $company): string
    {
        return match($company) {
            'bunnycommunications' => 'primary',
            'nexustel'            => 'success',
            'infratel'            => 'warning',
            default               => 'gray',
        };
    }

    public static function companyIcon(string $company): string
    {
        return match($company) {
            'bunnycommunications' => 'heroicon-o-building-office-2',
            'nexustel'            => 'heroicon-o-signal',
            'infratel'            => 'heroicon-o-server',
            default               => 'heroicon-o-envelope',
        };
    }

    public function getCompanyLabelAttribute(): string
    {
        return static::companyOptions()[$this->company] ?? $this->company;
    }
}
