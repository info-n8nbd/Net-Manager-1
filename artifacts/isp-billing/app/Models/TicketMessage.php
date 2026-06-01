<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class TicketMessage extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = ['ticket_id', 'user_id', 'message', 'attachment', 'is_staff_reply'];

    protected $casts = ['is_staff_reply' => 'boolean'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
