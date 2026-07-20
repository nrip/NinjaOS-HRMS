@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
<div class="mb-4">
    <h1>{{ $employee->full_name }}</h1>
    <p class="text-muted">{{ $employee->employee_code ?? 'N/A' }}</p>
</div>

<div class="row">
    <!-- Employee Details -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $employee->email }}</p>
                        <p><strong>Phone:</strong> {{ $employee->phone }}</p>
                        <p><strong>Date of Birth:</strong> {{ $employee->date_of_birth->format('d M Y') }}</p>
                        <p><strong>Gender:</strong> {{ ucfirst($employee->gender) }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Aadhaar:</strong> {{ $employee->aadhaar ?? 'N/A' }}</p>
                        <p><strong>PAN:</strong> {{ $employee->pan ?? 'N/A' }}</p>
                        <p><strong>Bank Account:</strong> {{ $employee->bank_account ?? 'N/A' }}</p>
                        <p><strong>Bank Name:</strong> {{ $employee->bank_name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Employment Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Location:</strong> {{ $employee->location->name }}</p>
                        <p><strong>Department:</strong> {{ $employee->department->name }}</p>
                        <p><strong>Designation:</strong> {{ $employee->designation->name }}</p>
                        <p><strong>Status:</strong> <span class="badge badge-{{ $employee->status }}">{{ ucfirst($employee->status) }}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date of Joining:</strong> {{ $employee->date_of_joining->format('d M Y') }}</p>
                        <p><strong>Probation End Date:</strong> {{ $employee->probation_end_date?->format('d M Y') ?? 'N/A' }}</p>
                        <p><strong>Confirmation Date:</strong> {{ $employee->confirmation_date?->format('d M Y') ?? 'N/A' }}</p>
                        <p><strong>Reporting Manager:</strong> {{ $employee->reportingManager->full_name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lifecycle History -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Lifecycle History</h5>
            </div>
            <div class="card-body">
                @if($employee->lifecycleHistory->count())
                <div class="timeline">
                    @foreach($employee->lifecycleHistory as $history)
                    <div class="timeline-item mb-3">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="mb-0">
                                    <strong>{{ ucfirst($history->previous_status) }} → {{ ucfirst($history->new_status) }}</strong>
                                </p>
                                <small class="text-muted">{{ $history->created_at->format('d M Y H:i') }}</small>
                                @if($history->reason)
                                <p class="mb-0 mt-1"><em>{{ $history->reason }}</em></p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted">No lifecycle history available.</p>
                @endif
            </div>
        </div>

        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documents</h5>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    Upload Document
                </button>
            </div>
            <div class="card-body">
                @if($employee->media->count())
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employee->media as $media)
                            <tr>
                                <td>{{ $media->name }}</td>
                                <td><span class="badge bg-info">{{ $media->collection_name }}</span></td>
                                <td>{{ number_format($media->size / 1024, 2) }} KB</td>
                                <td>{{ $media->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('employees.documents.download', [$employee, $media->id]) }}" class="btn btn-sm btn-info">Download</a>
                                    <form method="POST" action="{{ route('employees.documents.delete', [$employee, $media->id]) }}" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted">No documents uploaded.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-warning w-100 mb-2">Edit Employee</a>
                <button class="btn btn-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#transitionModal">
                    Change Status
                </button>
                <form method="POST" action="{{ route('employees.destroy', $employee) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure?')">Delete Employee</button>
                </form>
            </div>
        </div>

        <!-- Audit Log -->
        <div class="card">
            <div class="card-header">
                <h5>Recent Activity</h5>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                @if($auditLog->count())
                @foreach($auditLog as $log)
                <div class="mb-2 pb-2 border-bottom">
                    <small class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</small>
                    <p class="mb-0"><strong>{{ $log->event }}</strong></p>
                    <small>{{ $log->description ?? 'No description' }}</small>
                </div>
                @endforeach
                @else
                <p class="text-muted">No activity recorded.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Transition Modal -->
<div class="modal fade" id="transitionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('employees.transition', $employee) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Employee Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status *</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="">Select Status</option>
                            <option value="onboarding">Onboarding</option>
                            <option value="probation">Probation</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="transferred">Transferred</option>
                            <option value="exit">Exit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="collection" class="form-label">Document Type *</label>
                        <select class="form-select" id="collection" name="collection" required>
                            <option value="">Select Type</option>
                            <option value="documents">Documents</option>
                            <option value="certificates">Certificates</option>
                            <option value="identification">Identification</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document" class="form-label">File *</label>
                        <input type="file" class="form-control" id="document" name="document" required accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Max 10MB. Allowed: PDF, JPG, PNG</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('{{ route("employees.documents.upload", $employee) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            location.reload();
        } else {
            alert('Upload failed');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
@endsection
