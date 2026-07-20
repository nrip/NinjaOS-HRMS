@extends('layouts.app')

@section('title', 'Import Employees')

@section('content')
<div class="mb-4">
    <h1>Import Employees</h1>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>CSV Import</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('employees.import.process') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="location_id" class="form-label">Location *</label>
                        <select class="form-select @error('location_id') is-invalid @enderror" id="location_id" name="location_id" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('location_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV File *</label>
                        <input type="file" class="form-control @error('csv_file') is-invalid @enderror" id="csv_file" name="csv_file" accept=".csv" required>
                        <small class="text-muted">Max 10MB. CSV format only.</small>
                        @error('csv_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="dry_run" name="dry_run" value="1">
                        <label class="form-check-label" for="dry_run">
                            Dry Run (Preview without saving)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">Import Employees</button>
                    <a href="{{ route('employees.import.template') }}" class="btn btn-secondary">Download Template</a>
                </form>
            </div>
        </div>

        @if(session('import_errors'))
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Import Errors</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('import_errors') as $error)
                            <tr>
                                <td><strong>{{ $error['row'] }}</strong></td>
                                <td>
                                    <ul class="mb-0">
                                        @foreach($error['errors'] as $field => $messages)
                                        @foreach($messages as $msg)
                                        <li>{{ $field }}: {{ $msg }}</li>
                                        @endforeach
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>CSV Format</h5>
            </div>
            <div class="card-body">
                <p><strong>Required Columns:</strong></p>
                <ul class="small">
                    <li>first_name</li>
                    <li>last_name</li>
                    <li>email</li>
                    <li>phone (10 digits)</li>
                    <li>date_of_birth (YYYY-MM-DD)</li>
                    <li>gender (male/female/other)</li>
                    <li>department_code</li>
                    <li>designation_code</li>
                    <li>date_of_joining (YYYY-MM-DD)</li>
                </ul>

                <p class="mt-3"><strong>Optional Columns:</strong></p>
                <ul class="small">
                    <li>aadhaar (12 digits)</li>
                    <li>pan</li>
                    <li>bank_account</li>
                    <li>bank_name</li>
                    <li>ifsc_code</li>
                </ul>

                <div class="alert alert-info mt-3">
                    <small>
                        <strong>Tip:</strong> Download the template to see the exact format required.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
