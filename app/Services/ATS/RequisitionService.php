<?php

declare(strict_types=1);

namespace App\Services\ATS;

use App\Models\JobRequisition;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * RequisitionService
 *
 * Manages the Job Requisition approval workflow.
 *
 * State Machine:
 *   draft → pending_location_hr → pending_central_hr → open / rejected
 *
 * Approval Chain:
 *   1. Manager submits → status: pending_location_hr
 *   2. Location HR approves → status: pending_central_hr
 *   3. Central HR approves → status: open (job published)
 *
 * Any approver can reject at any stage → status: rejected
 */
class RequisitionService
{
    /**
     * Submit a draft requisition for approval.
     * Transitions: draft → pending_location_hr
     */
    public function submit(JobRequisition $requisition, User $submitter): JobRequisition
    {
        if ($requisition->status !== 'draft') {
            throw new \LogicException("Only draft requisitions can be submitted. Current status: {$requisition->status}");
        }

        $requisition->update(['status' => 'pending_location_hr']);

        Log::info('ATS: Requisition submitted for approval', [
            'requisition_id' => $requisition->requisition_id,
            'submitted_by'   => $submitter->id,
        ]);

        return $requisition->fresh();
    }

    /**
     * Location HR approves → transitions to pending_central_hr.
     */
    public function approveByLocationHr(JobRequisition $requisition, User $approver): JobRequisition
    {
        if ($requisition->status !== 'pending_location_hr') {
            throw new \LogicException("Requisition is not pending Location HR approval. Current status: {$requisition->status}");
        }

        $requisition->update([
            'status'      => 'pending_central_hr',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        Log::info('ATS: Requisition approved by Location HR', [
            'requisition_id' => $requisition->requisition_id,
            'approved_by'    => $approver->id,
        ]);

        return $requisition->fresh();
    }

    /**
     * Central HR approves → transitions to open (job published).
     */
    public function approveByCentralHr(JobRequisition $requisition, User $approver): JobRequisition
    {
        if ($requisition->status !== 'pending_central_hr') {
            throw new \LogicException("Requisition is not pending Central HR approval. Current status: {$requisition->status}");
        }

        $requisition->update([
            'status'       => 'open',
            'approved_by'  => $approver->id,
            'approved_at'  => now(),
            'posting_date' => now()->toDateString(),
        ]);

        Log::info('ATS: Requisition approved by Central HR — job published', [
            'requisition_id' => $requisition->requisition_id,
            'approved_by'    => $approver->id,
        ]);

        return $requisition->fresh();
    }

    /**
     * Reject a requisition at any approval stage.
     */
    public function reject(JobRequisition $requisition, User $rejector, string $reason): JobRequisition
    {
        $allowedStatuses = ['pending_location_hr', 'pending_central_hr'];
        if (! in_array($requisition->status, $allowedStatuses, true)) {
            throw new \LogicException("Requisition cannot be rejected from status: {$requisition->status}");
        }

        $requisition->update(['status' => 'rejected']);

        Log::info('ATS: Requisition rejected', [
            'requisition_id' => $requisition->requisition_id,
            'rejected_by'    => $rejector->id,
        ]);

        return $requisition->fresh();
    }

    /**
     * Close a requisition (all positions filled or cancelled).
     */
    public function close(JobRequisition $requisition, User $actor): JobRequisition
    {
        $requisition->update(['status' => 'closed', 'closing_date' => now()->toDateString()]);

        Log::info('ATS: Requisition closed', [
            'requisition_id' => $requisition->requisition_id,
            'closed_by'      => $actor->id,
        ]);

        return $requisition->fresh();
    }
}
