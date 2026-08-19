<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records a critical operation in the audit trail.
 *
 * The acting user and the IP come from the current request rather than the
 * action's arguments: an IP has no place in a domain DTO, and every action
 * using this trait is reachable only through an authenticated HTTP route. That
 * is a real precondition — a missing actor fails loudly instead of writing a
 * log entry that cannot answer "who did this".
 */
trait LogsActivity
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    protected static function log(AuditAction $action, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            // The short name keeps `?model_type=Loan` usable in a URL, which a
            // fully qualified class name with backslashes is not.
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Records a mutation, narrowing both sides to the attributes that actually
     * changed. `$original` is the model's attributes captured before the write;
     * the updated_at column is dropped because it moves on every save and says
     * nothing about what a person did.
     *
     * @param  array<string, mixed>  $original
     */
    protected static function logChanges(AuditAction $action, Model $model, array $original): void
    {
        $oldValues = [];
        $newValues = [];

        $changes = $model->getChanges();

        // unset rather than a guard inside the loop: whether updated_at is even
        // reported as changed depends on the write landing in a later second
        // than the read, which would make this branch run or not run by clock.
        unset($changes[$model->getUpdatedAtColumn()]);

        foreach ($changes as $attribute => $value) {
            // An attribute the record did not carry before reads as null rather
            // than being omitted, so both sides of the entry line up.
            $oldValues[(string) $attribute] = $original[$attribute] ?? null;
            $newValues[(string) $attribute] = $value;
        }

        self::log($action, $model, $oldValues, $newValues);
    }
}
