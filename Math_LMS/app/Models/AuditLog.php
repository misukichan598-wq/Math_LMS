<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function getActionColorAttribute(): string
    {
        return match(true) {
            str_contains($this->action, 'create') => 'success',
            str_contains($this->action, 'update') => 'info',
            str_contains($this->action, 'delete') => 'danger',
            str_contains($this->action, 'login') => 'primary',
            str_contains($this->action, 'logout') => 'secondary',
            default => 'light',
        };
    }

    public function getActionIconAttribute(): string
    {
        return match(true) {
            str_contains($this->action, 'create') => 'fa-plus-circle',
            str_contains($this->action, 'update') => 'fa-edit',
            str_contains($this->action, 'delete') => 'fa-trash',
            str_contains($this->action, 'login') => 'fa-sign-in-alt',
            str_contains($this->action, 'logout') => 'fa-sign-out-alt',
            default => 'fa-circle',
        };
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', 'like', '%' . $action . '%');
    }

    public function scopeByModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public static function logAction(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
