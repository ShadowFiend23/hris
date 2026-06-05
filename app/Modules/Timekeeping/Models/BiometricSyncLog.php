<?php

namespace App\Modules\Timekeeping\Models;

use Illuminate\Database\Eloquent\Model;

class BiometricSyncLog extends Model
{
    protected $fillable = [
        'month',
        'started_at',
        'ended_at',
        'records_read',
        'records_imported',
        'records_skipped',
        'status',
        'error_message',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'records_read' => 'integer',
            'records_imported' => 'integer',
            'records_skipped' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function markCompleted(int $read, int $imported, int $skipped): void
    {
        $this->update([
            'ended_at' => now(),
            'status' => 'completed',
            'records_read' => $read,
            'records_imported' => $imported,
            'records_skipped' => $skipped,
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'ended_at' => now(),
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}
