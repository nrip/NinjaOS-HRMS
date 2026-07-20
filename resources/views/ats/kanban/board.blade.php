@extends('layouts.app')

@section('title', 'Kanban Pipeline — ' . $requisition->designation->name ?? 'Requisition')

@push('styles')
<style>
.kanban-board { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; }
.kanban-column { min-width: 220px; max-width: 260px; flex-shrink: 0; }
.kanban-column-header { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.5rem 0.75rem; border-radius: 6px 6px 0 0; }
.kanban-cards { min-height: 200px; background: #f8f9fa; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 6px 6px; padding: 0.5rem; }
.kanban-card { background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 0.75rem; margin-bottom: 0.5rem; cursor: grab; box-shadow: 0 1px 3px rgba(0,0,0,.06); transition: box-shadow .15s; }
.kanban-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.12); }
.kanban-card.dragging { opacity: 0.5; cursor: grabbing; }
.drop-zone-active { background: #e8f4fd; border-color: #0d6efd; }
.stage-applied     .kanban-column-header { background: #6c757d; color: #fff; }
.stage-screened    .kanban-column-header { background: #0dcaf0; color: #000; }
.stage-interview_1 .kanban-column-header { background: #0d6efd; color: #fff; }
.stage-interview_2 .kanban-column-header { background: #6610f2; color: #fff; }
.stage-offer       .kanban-column-header { background: #fd7e14; color: #fff; }
.stage-hired       .kanban-column-header { background: #198754; color: #fff; }
.stage-rejected    .kanban-column-header { background: #dc3545; color: #fff; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4" x-data="kanbanBoard()">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Kanban Pipeline</h1>
            <small class="text-muted">
                {{ $requisition->designation->name ?? '' }} &mdash;
                {{ $requisition->location->name ?? '' }} &mdash;
                {{ $requisition->number_of_positions }} position(s)
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('ats.requisitions.show', $requisition) }}" class="btn btn-sm btn-outline-secondary">
                Back to Requisition
            </a>
            <button class="btn btn-sm btn-primary" @click="addCandidateModal = true">
                <i class="bi bi-person-plus me-1"></i> Add Candidate
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Kanban Board --}}
    <div class="kanban-board">
        @foreach($stages as $stage)
        <div class="kanban-column stage-{{ $stage }}"
             @dragover.prevent="dragOverStage = '{{ $stage }}'"
             @dragleave="dragOverStage = null"
             @drop.prevent="dropCandidate('{{ $stage }}')"
             :class="{ 'drop-zone-active': dragOverStage === '{{ $stage }}' }">

            <div class="kanban-column-header d-flex justify-content-between align-items-center">
                <span>{{ str_replace('_', ' ', ucfirst($stage)) }}</span>
                <span class="badge bg-white text-dark">{{ ($candidates[$stage] ?? collect())->count() }}</span>
            </div>

            <div class="kanban-cards">
                @foreach(($candidates[$stage] ?? collect()) as $candidate)
                <div class="kanban-card"
                     draggable="true"
                     @dragstart="dragStart('{{ $candidate->id }}', '{{ $stage }}')"
                     @dragend="dragEnd()">
                    <div class="fw-semibold small">{{ $candidate->full_name }}</div>
                    <div class="text-muted" style="font-size:0.75rem;">{{ $candidate->source ?? 'Direct' }}</div>
                    @if($candidate->parsed_experience)
                        <div class="text-muted" style="font-size:0.75rem;">{{ $candidate->parsed_experience }} yrs exp</div>
                    @endif
                    <div class="mt-2 d-flex gap-1 flex-wrap">
                        <a href="{{ route('ats.candidates.show', $candidate) }}" class="btn btn-xs btn-outline-primary" style="font-size:0.7rem;padding:2px 6px;">View</a>
                        @if($stage === 'hired' && ! $candidate->isConverted())
                        <form method="POST" action="{{ route('ats.candidates.convert', $candidate) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-success" style="font-size:0.7rem;padding:2px 6px;"
                                onclick="return confirm('Convert {{ $candidate->first_name }} to Employee?')">
                                Convert to Employee
                            </button>
                        </form>
                        @endif
                        @if($candidate->isConverted())
                            <span class="badge bg-success" style="font-size:0.65rem;">Converted</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- Stage Move Modal (triggered by drag-drop) --}}
    <div class="modal fade" id="stageMoveModal" tabindex="-1" x-ref="stageMoveModal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Move Candidate</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" :action="'/ats/candidates/' + draggedCandidateId + '/move-stage'">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <input type="hidden" name="stage" :value="targetStage">
                        <div class="mb-3" x-show="targetStage === 'rejected'">
                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" rows="3" class="form-control"
                                :required="targetStage === 'rejected'"
                                placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Interview feedback, etc."></textarea>
                        </div>
                        <p class="text-muted small mb-0">
                            Moving to: <strong x-text="targetStage?.replace('_', ' ')"></strong>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Confirm Move</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function kanbanBoard() {
    return {
        draggedCandidateId: null,
        draggedFromStage: null,
        dragOverStage: null,
        targetStage: null,
        addCandidateModal: false,

        dragStart(candidateId, fromStage) {
            this.draggedCandidateId = candidateId;
            this.draggedFromStage   = fromStage;
        },
        dragEnd() {
            this.dragOverStage = null;
        },
        dropCandidate(toStage) {
            this.dragOverStage = null;
            if (! this.draggedCandidateId || toStage === this.draggedFromStage) return;
            this.targetStage = toStage;
            // Show confirmation modal
            const modal = new bootstrap.Modal(document.getElementById('stageMoveModal'));
            modal.show();
        },
    };
}
</script>
@endpush
