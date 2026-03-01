<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableReservation extends Model
{
    protected $fillable = [
        'booking_code',
        'table_id',
        'customer_id',
        'reservation_date',
        'reservation_time',
        'status',
        'note',
        'check_in_qr_code',
        'check_in_qr_expires_at',
    ];

    protected $casts = [
        'reservation_date' => 'date',
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

    public function tableSession()
    {
        return $this->hasOne(TableSession::class, 'table_reservation_id');
    }

    public function getBookingCodeFormattedAttribute()
    {
        return 'BKG-'.$this->booking_code;
    }
}
