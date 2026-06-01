<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ClientDocument extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = ['client_id', 'document_type', 'document_name', 'file_path'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
