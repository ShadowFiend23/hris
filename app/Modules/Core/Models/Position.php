<?php

namespace App\Modules\Core\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'position_name',
        'reports_to_position_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PositionFactory
    {
        return PositionFactory::new();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the company through the department relationship
     */
    public function company()
    {
        return $this->hasOneThrough(
            Company::class,
            Department::class,
            'id', // Foreign key on departments table
            'id', // Foreign key on companies table
            'department_id', // Local key on positions table
            'company_id' // Local key on departments table
        );
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_position_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to_position_id');
    }

    /** Detect if setting reports_to_position_id to $candidateId would create a cycle. */
    public function wouldCreateCycle(int $candidateId): bool
    {
        $visited = [];
        $currentId = $candidateId;

        while ($currentId !== null) {
            if ($currentId === $this->id) {
                return true;
            }

            if (in_array($currentId, $visited, true)) {
                break;
            }

            $visited[] = $currentId;
            $currentId = self::withTrashed()->where('id', $currentId)->value('reports_to_position_id');
        }

        return false;
    }
}
