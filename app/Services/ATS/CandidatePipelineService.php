<?php

declare(strict_types=1);

namespace App\Services\ATS;

use App\Jobs\SendCandidateNotification;
use App\Models\Candidate;
use App\Models\CandidateStageHistory;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * CandidatePipelineService
 *
 * Manages candidate movement through the Kanban pipeline.
 *
 * Pipeline Stages (ordered):
 *   applied → screened → interview_1 → interview_2 → offer → hired → rejected
 *
 * Rules:
 *   - Any stage can transition to 'rejected' (with mandatory rejection_reason).
 *   - 'hired' and 'rejected' are terminal stages.
 *   - A queued SendCandidateNotification job is dispatched on every stage change.
 */
class CandidatePipelineService
{
    /**
     * Move a candidate to a new pipeline stage.
     *
     * @param  Candidate  $candidate  The candidate to move.
     * @param  string     $toStage    The target stage (must be in Candidate::STAGES).
     * @param  User       $mover      The user performing the action.
     * @param  array      $options    Optional: ['notes', 'rejection_reason']
     *
     * @throws \InvalidArgumentException  If the stage is invalid.
     * @throws \LogicException            If rejection_reason is missing for 'rejected' stage.
     * @throws \LogicException            If the candidate is already in a terminal stage.
     */
    public function moveToStage(
        Candidate $candidate,
        string $toStage,
        User $mover,
        array $options = []
    ): Candidate {
        // ── Validate target stage ─────────────────────────────────────────────
        if (! in_array($toStage, Candidate::STAGES, true)) {
            throw new \InvalidArgumentException(
                "Invalid pipeline stage: '{$toStage}'. Valid stages: " . implode(', ', Candidate::STAGES)
            );
        }

        // ── Guard: terminal stages cannot be moved further ────────────────────
        if (in_array($candidate->current_stage, ['hired', 'rejected'], true)) {
            throw new \LogicException(
                "Candidate is already in terminal stage '{$candidate->current_stage}' and cannot be moved."
            );
        }

        // ── Guard: rejection_reason is required for 'rejected' stage ─────────
        if ($toStage === 'rejected' && empty($options['rejection_reason'])) {
            throw new \LogicException(
                "A rejection_reason is required when moving a candidate to the 'rejected' stage."
            );
        }

        $fromStage       = $candidate->current_stage;
        $rejectionReason = $options['rejection_reason'] ?? null;
        $notes           = $options['notes'] ?? null;

        // ── 1. Update candidate stage ─────────────────────────────────────────
        $updateData = ['current_stage' => $toStage];
        if ($toStage === 'rejected' && $rejectionReason) {
            $updateData['rejection_reason'] = $rejectionReason;
        }
        $candidate->update($updateData);

        // ── 2. Record stage history ───────────────────────────────────────────
        CandidateStageHistory::create([
            'candidate_id'    => $candidate->id,
            'from_stage'      => $fromStage,
            'to_stage'        => $toStage,
            'moved_by'        => $mover->id,
            'rejection_reason' => $rejectionReason,
            'notes'           => $notes,
            'moved_at'        => now(),
        ]);

        // ── 3. Log stage change (PII-safe: no email/phone) ────────────────────
        Log::info('ATS: Candidate stage changed', [
            'candidate_uuid' => $candidate->candidate_id,
            'from_stage'     => $fromStage,
            'to_stage'       => $toStage,
            'moved_by'       => $mover->id,
        ]);

        // ── 4. Dispatch queued notification ───────────────────────────────────
        // SendCandidateNotification handles email/SMS per stage template.
        dispatch(new SendCandidateNotification(
            candidateId: $candidate->id,
            stage: $toStage,
            moverId: $mover->id,
            rejectionReason: $rejectionReason,
        ));

        return $candidate->fresh();
    }
}
