<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Candidate extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;

    /**
     * UUID primary key — DPDP compliance mandate.
     */
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'candidate_id',
        'requisition_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'current_stage',
        'source',
        'parsed_skills',
        'parsed_experience',
        'rejection_reason',
        'offered_ctc',
        'date_of_joining',
        'converted_to_employee_id',
        'converted_at',
    ];

    protected $casts = [
        'parsed_skills'  => 'array',
        'date_of_joining' => 'date',
        'converted_at'   => 'datetime',
    ];

    /**
     * Valid Kanban pipeline stages (ordered).
     */
    public const STAGES = [
        'applied',
        'screened',
        'interview_1',
        'interview_2',
        'offer',
        'hired',
        'rejected',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            // Ensure UUID primary key is always set
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            if (empty($model->candidate_id)) {
                $model->candidate_id = $model->id;
            }
        });
    }

    // ── Spatie MediaLibrary ───────────────────────────────────────────────────

    /**
     * Register the 'resumes' media collection.
     * Consistent with Phase 1 document handling (Spatie MediaLibrary).
     * Only PDF and DOCX files are accepted.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('resumes')
             ->singleFile()
             ->acceptsMimeTypes([
                 'application/pdf',
                 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'application/msword',
             ]);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(JobRequisition::class, 'requisition_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'converted_to_employee_id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(CandidateStageHistory::class, 'candidate_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isHired(): bool
    {
        return $this->current_stage === 'hired';
    }

    public function isRejected(): bool
    {
        return $this->current_stage === 'rejected';
    }

    public function isConverted(): bool
    {
        return $this->converted_to_employee_id !== null;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
