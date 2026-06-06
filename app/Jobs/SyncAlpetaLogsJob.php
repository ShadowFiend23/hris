<?php

namespace App\Jobs;

use App\Modules\Core\Models\Employee;
use App\Modules\Timekeeping\Models\AlpetaLog;
use App\Modules\Timekeeping\Models\AttendanceRecord;
use App\Modules\Timekeeping\Models\BiometricSyncLog;
use App\Modules\Timekeeping\Models\BiometricTerminal;
use App\Modules\Timekeeping\Models\ShiftTemplate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncAlpetaLogsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly string $yearMonth,
        public readonly string $triggeredBy = 'scheduled'
    ) {}

    public function handle(): void
    {
        $table = 'auth_logs_'.$this->yearMonth;

        if (! Schema::connection('alpeta')->hasTable($table)) {
            Log::channel('alpeta_sync')->warning("Skipped sync: table [{$table}] does not exist in the Alpeta database.", [
                'month' => $this->yearMonth,
                'triggered_by' => $this->triggeredBy,
            ]);

            return;
        }

        $syncLog = BiometricSyncLog::create([
            'month' => $this->yearMonth,
            'started_at' => now(),
            'triggered_by' => $this->triggeredBy,
            'status' => 'running',
        ]);

        try {
            /** @var array<int, string> $terminalMap  alpeta_terminal_id => 'in'|'out' */
            $terminalMap = BiometricTerminal::where('is_active', true)
                ->pluck('type', 'alpeta_terminal_id')
                ->all();

            if (empty($terminalMap)) {
                $syncLog->markFailed('No active biometric terminals configured. Configure terminals before syncing.');

                return;
            }

            // Build employee map: alpeta_employee_id => Employee (with shift template)
            $employees = Employee::whereNotNull('alpeta_employee_id')
                ->with('shiftTemplate')
                ->select('id', 'company_id', 'alpeta_employee_id', 'shift_template_id')
                ->get()
                ->keyBy('alpeta_employee_id');

            $logs = AlpetaLog::forMonth($this->yearMonth)
                ->whereIn('terminal_id', array_keys($terminalMap))
                ->whereNotNull('user_id')
                ->where('user_id', '!=', -1)
                ->orderBy('event_time')
                ->get();

            $read = $logs->count();
            $imported = 0;
            $skipped = 0;

            // Group logs by employee+date so we can classify all taps together
            /** @var Collection<string, Collection> $grouped  key: "{userId}_{date}" */
            $grouped = $logs->groupBy(function ($log) {
                $date = Carbon::parse($log->event_time)->toDateString();

                return $log->user_id.'_'.$date;
            });

            foreach ($grouped as $groupKey => $groupLogs) {
                [$alpetaUserId, $date] = explode('_', $groupKey, 2);

                $employee = $employees->get((string) $alpetaUserId);

                if (! $employee) {
                    $skipped += $groupLogs->count();

                    continue;
                }

                // Separate IN and OUT events, sorted by time
                $inEvents = $groupLogs
                    ->filter(fn ($log) => ($terminalMap[$log->terminal_id] ?? null) === 'in')
                    ->sortBy('event_time')
                    ->values();

                $outEvents = $groupLogs
                    ->filter(fn ($log) => ($terminalMap[$log->terminal_id] ?? null) === 'out')
                    ->sortBy('event_time')
                    ->values();

                if ($inEvents->isEmpty() && $outEvents->isEmpty()) {
                    $skipped += $groupLogs->count();

                    continue;
                }

                $record = AttendanceRecord::firstOrNew([
                    'employee_id' => $employee->id,
                    'date' => $date,
                ]);

                $record->company_id = $employee->company_id;
                $record->source = 'biometric';

                $shiftTemplate = $employee->shiftTemplate;

                if ($shiftTemplate && $shiftTemplate->hasSplitShift()) {
                    $this->classifySplitShiftTaps($record, $inEvents, $outEvents, $shiftTemplate);
                } else {
                    $this->classifySimpleTaps($record, $inEvents, $outEvents);
                }

                // Set first IN log's index_key for traceability
                if ($inEvents->isNotEmpty() && ! $record->alpeta_log_id) {
                    $record->alpeta_log_id = (string) $inEvents->first()->index_key;
                }

                $this->calculateTotalsAndStatus($record);

                $record->save();
                $imported++;
            }

            $syncLog->markCompleted($read, $imported, $skipped);

        } catch (Throwable $e) {
            Log::error('SyncAlpetaLogsJob failed', [
                'month' => $this->yearMonth,
                'error' => $e->getMessage(),
            ]);

            $syncLog->markFailed($e->getMessage());

            throw $e;
        }
    }

    /**
     * Classic 2-timestamp classification (no split shift).
     * Keeps earliest IN as clock_in, latest OUT as clock_out.
     */
    private function classifySimpleTaps(
        AttendanceRecord $record,
        Collection $inEvents,
        Collection $outEvents
    ): void {
        if ($inEvents->isNotEmpty()) {
            $record->clock_in = Carbon::parse($inEvents->first()->event_time);
        }

        if ($outEvents->isNotEmpty()) {
            $record->clock_out = Carbon::parse($outEvents->last()->event_time);
        }
    }

    /**
     * 4-timestamp classification for split (morning/afternoon) shifts.
     *
     * - clock_in     : first IN tap of the day
     * - morning_out  : last OUT tap on or before break_end_time
     * - afternoon_in : first IN tap on or after break_start_time
     * - clock_out    : last OUT tap of the day
     */
    private function classifySplitShiftTaps(
        AttendanceRecord $record,
        Collection $inEvents,
        Collection $outEvents,
        ShiftTemplate $shiftTemplate
    ): void {
        $breakStart = Carbon::parse($record->date.' '.$shiftTemplate->break_start_time);
        $breakEnd = Carbon::parse($record->date.' '.$shiftTemplate->break_end_time);

        // clock_in: earliest IN event of the day
        if ($inEvents->isNotEmpty()) {
            $record->clock_in = Carbon::parse($inEvents->first()->event_time);
        }

        // morning_out: last OUT event at or before break_end_time
        $morningOuts = $outEvents->filter(
            fn ($log) => Carbon::parse($log->event_time)->lte($breakEnd)
        );

        if ($morningOuts->isNotEmpty()) {
            $record->morning_out = Carbon::parse($morningOuts->last()->event_time);
        }

        // afternoon_in: first IN event at or after break_start_time
        $afternoonIns = $inEvents->filter(
            fn ($log) => Carbon::parse($log->event_time)->gte($breakStart)
        );

        if ($afternoonIns->isNotEmpty()) {
            $record->afternoon_in = Carbon::parse($afternoonIns->first()->event_time);
        }

        // clock_out: latest OUT event of the day
        if ($outEvents->isNotEmpty()) {
            $record->clock_out = Carbon::parse($outEvents->last()->event_time);
        }
    }

    /**
     * Recalculate total_hours and status based on recorded timestamps.
     */
    private function calculateTotalsAndStatus(AttendanceRecord $record): void
    {
        if ($record->clock_in && $record->clock_out) {
            if ($record->morning_out && $record->afternoon_in) {
                // Split shift: sum morning and afternoon segments
                $morningMinutes = $record->morning_out->diffInMinutes($record->clock_in);
                $afternoonMinutes = $record->clock_out->diffInMinutes($record->afternoon_in);
                $totalMinutes = $morningMinutes + $afternoonMinutes;
            } else {
                $totalMinutes = $record->clock_out->diffInMinutes($record->clock_in);
                $breakMinutes = $record->break_duration ?? 0;
                $totalMinutes = max(0, $totalMinutes - $breakMinutes);
            }

            $record->total_hours = round($totalMinutes / 60, 2);
            $record->status = $record->total_hours >= 4 ? 'present' : 'half_day';
        } elseif ($record->clock_in) {
            $record->status = 'present';
        }
    }
}
