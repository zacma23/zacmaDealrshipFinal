<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmTicket extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $table = 'smm_tickets';

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'organization_id',
        'smm_order_id',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_user_id',
        'last_reply_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function smmOrder(): BelongsTo
    {
        return $this->belongsTo(SmmOrder::class, 'smm_order_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmmTicketMessage::class, 'smm_ticket_id')->oldest();
    }
}
