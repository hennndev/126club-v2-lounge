<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Printer extends Model
{
    protected $fillable = [
        'name',
        'location',
        'printer_type',
        'connection_type',
        'ip',
        'port',
        'path',
        'timeout',
        'header',
        'footer',
        'logo_path',
        'show_qr_code',
        'width',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'show_qr_code' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'port' => 'integer',
        'timeout' => 'integer',
        'width' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByLocation($query, string $location)
    {
        return $query->whereRaw('TRIM(LOWER(location)) = ?', [strtolower(trim($location))]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('printer_type', $type);
    }

    public static function getDefault(): ?self
    {
        return static::active()->default()->first()
            ?? static::active()->first();
    }

    public static function getByLocation(string $location): ?self
    {
        return static::active()->byLocation($location)->first();
    }

    public static function getByType(string $type): ?self
    {
        return static::active()->byType($type)->first();
    }

    /**
     * Get printer for a service location, preferring printer_type match over location string.
     */
    public static function getForService(string $serviceLocation): ?self
    {
        return static::getByType($serviceLocation) ?? static::getByLocation($serviceLocation);
    }

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class)->withTimestamps();
    }

    public function isNetwork(): bool
    {
        return $this->connection_type === 'network';
    }

    public function isFile(): bool
    {
        return $this->connection_type === 'file';
    }

    public function isWindows(): bool
    {
        return $this->connection_type === 'windows';
    }
}
