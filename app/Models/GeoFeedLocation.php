<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeoFeedLocation extends Model
{
    use HasFactory;

    protected $table = 'geofeed_locations';

    protected $fillable = [
        'country_code',
        'region',
        'city',
        'postal_code',
    ];

    public function getLabelAttribute(): string
    {
        $parts = array_filter([
            strtoupper($this->country_code),
            $this->region,
            $this->city,
            $this->postal_code,
        ], fn ($value) => $value !== null && $value !== '');

        return implode(', ', $parts);
    }
}
