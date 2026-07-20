@extends('layouts.app')

@section('title', $candidate->full_name . ' — Candidate Profile')

@section('content')
<div class="container py-4">
    <div class="row">
        {{-- Candidate Info --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="display-6 mb-2">👤</div>
                    <h5 class="mb-1">{{ $candidate->full_name }}</h5>
                    <p class="text-muted small mb-2">{{ $candidate->requisition->designation->name ?? '' }}</p>
                    @php
                        $stageColors = [
                            'applied' => 'secondary', 'screened' => 'info', 'interview_1' => 'primary',
                            'interview_2' => 'purple', 'offer' => 'warning', 'hired' => 'success', 'rejected' => 'danger',
                        ];
                    @endphp
                    <span class="badge bg-{{ $stageColors[$candidate->current_stage] ?? 'secondary' }} mb-3">
                        {{ str_replace('_', ' ', ucfirst($candidate->current_stage)) }}
                    </span>
                    <hr>
                    <div class="text-start small">
                        <div class="mb-1"><strong>Source:</strong> {{ ucfirst($candidate->source ?? 'Direct') }}</div>
                        <div class="mb-1"><strong>Experience:</strong> {{ $candidate->parsed_experience ?? '—' }} yrs</div>
                        @if($candidate->parsed_skills)
                        <div class="mb-1">
                            <strong>Skills:</strong><br>
                            @foreach($candidate->parsed_skills as $skill)
                                <span class="badge bg-light text-dark border me-1 mb-1">{{ $skill }}</span>
                            @endforeach
                        </div>
                        @endif
                        @if($candidate->date_of_joining)
                        <div class="mb-1"><strong>Expected Joining:</strong> {{ $candidate->date_of_joining->format('d M Y') }}</div>
                        @endif
                        @if($candidate->offered_ctc)
                        <div class="mb-1"><strong>Offered CTC:</strong> ₹{{ number_format($candidate->offered_ctc) }}</div>
                        @endif
                    </div>
                    @if($candidate->isHired() && ! $candidate->isConverted())
                    <hr>
                    <form method="POST" action="{{ route('ats.candidates.convert', $candidate) }}">
                        @csrf
                        <button type="submit" class="btn btn-success w-100"
                            onclick="return confirm('Convert {{ $candidate->first_name }} to a Core HR Employee?')">
                            <i class="bi bi-person-check me-1"></i> Convert to Employee
                        </button>
                    </form>
                    @elseif($candidate->isConverted())
                    <hr>
                    <div class="alert alert-success small mb-0">
                        ✅ Converted to Employee
                        <a href="{{ route('employees.show', $candidate->converted_to_employee_id) }}" class="alert-link">View Profile</a>
                    </div>
                    @endif
                    @if($candidate->isRejected() && $candidate->rejection_reason)
                    <hr>
                    <div class="alert alert-danger small mb-0 text-start">
                        <strong>Rejection Reason:</strong><br>{{ $candidate->rejection_reason }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Resume --}}
            @if($candidate->getFirstMedia('resumes'))
            <div class="card shadow-sm mt-3">
                <div class="card-header small fw-semibold">Resume</div>
                <div class="card-body">
                    <a href="{{ $candidate->getFirstMediaUrl('resumes') }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Download Resume
                    </a>
                </div>
            </div>
            @endif
        </div>

        {{-- Stage History & Move Stage --}}
        <div class="col-lg-8">
            {{-- Move Stage --}}
            @if(! in_array($candidate->current_stage, ['hired', 'rejected']))
            <div class="card shadow-sm mb-4">
                <div class="card-header small fw-semibold">Move to Stage</div>
                <div class="card-body" x-data="{ stage: '' }">
                    <form method="POST" action="{{ route('ats.candidates.move-stage', $candidate) }}">
                        @csrf
                        @method('PATCH')
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small">Target Stage</label>
                                <select name="stage" class="form-select form-select-sm" x-model="stage" required>
                                    <option value="">Select stage...</option>
                                    @foreach(\App\Models\Candidate::STAGES as $s)
                                        @if($s !== $candidate->current_stage)
                                        <option value="{{ $s }}">{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4" x-show="stage === 'rejected'">
                                <label class="form-label small">Rejection Reason <span class="text-danger">*</span></label>
                                <input type="text" name="rejection_reason" class="form-control form-control-sm"
                                    :required="stage === 'rejected'" placeholder="Reason...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Notes</label>
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes...">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">Move</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Stage History Timeline --}}
            <div class="card shadow-sm">
                <div class="card-header small fw-semibold">Stage History</div>
                <div class="card-body">
                    @forelse($candidate->stageHistories->sortByDesc('moved_at') as $history)
                    <div class="d-flex gap-3 mb-3">
                        <div class="text-muted small" style="min-width:120px;">
                            {{ $history->moved_at->format('d M Y H:i') }}
                        </div>
                        <div>
                            <span class="badge bg-secondary">{{ str_replace('_', ' ', $history->from_stage ?? 'start') }}</span>
                            <span class="mx-1">→</span>
                            <span class="badge bg-primary">{{ str_replace('_', ' ', $history->to_stage) }}</span>
                            <div class="small text-muted">by {{ $history->mover->name ?? 'System' }}</div>
                            @if($history->rejection_reason)
                                <div class="small text-danger mt-1">Reason: {{ $history->rejection_reason }}</div>
                            @endif
                            @if($history->notes)
                                <div class="small text-muted mt-1">{{ $history->notes }}</div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-muted small mb-0">No stage history yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
