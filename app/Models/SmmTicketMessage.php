<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmmTicketMessage extends Model
{
    use HasFactory;

    protected $table = 'smm_ticket_messages';

    protected $fillable = [
        'smm_ticket_id',
        'user_id',
        'message',
        'is_staff',
        'is_internal_note',
        'attachments',
    ];

    protected $casts = [
        'is_staff' => 'boolean',
        'is_internal_note' => 'boolean',
        'attachments' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SmmTicket::class, 'smm_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
