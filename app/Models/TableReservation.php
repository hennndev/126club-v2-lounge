<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableReservation extends Model
{
    protected $fillable = [
        'booking_code',
        'booking_name',
        'table_id',
        'customer_id',
        'created_by',
        'reservation_date',
        'reservation_time',
        'status',
        'note',
        'down_payment_amount',
        'check_in_qr_code',
        'check_in_qr_expires_at',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'down_payment_amount' => 'decimal:2',
        'check_in_qr_expires_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(Tabel::class, 'table_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tableSession()
    {
        return $this->hasOne(TableSession::class, 'table_reservation_id');
    }

    public function getBookingCodeFormattedAttribute()
    {
        return 'BKG-'.$this->booking_code;
    }
}
