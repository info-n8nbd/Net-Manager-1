<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'action',
        'before_state', 'after_state',
        'performed_by', 'performed_by_role',
        'ip_address', 'user_agent'
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
    ];

    public static function log(
        string $auditableType,
        int $auditableId,
        string $action,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?int $userId = null,
        ?string $role = null
    ): self {
        return self::create([
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'performed_by' => $userId ?? auth()->id(),
            'performed_by_role' => $role ?? (auth()->user()?->role ?? 'system'),
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
        ]);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function auditable()
    {
        $modelClass = 'App\\Models\\' . $this->auditable_type;
        if (class_exists($modelClass)) {
            return $modelClass::find($this->auditable_id);
        }
        return null;
    }
}
