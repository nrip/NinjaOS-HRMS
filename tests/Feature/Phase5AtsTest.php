<?php

declare(strict_types=1);

use App\Events\CandidateStageChanged;
use App\Jobs\SendCandidateNotification;
use App\Models\Candidate;
use App\Models\CandidateStageHistory;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\JobRequisition;
use App\Models\Location;
use App\Models\User;
use App\Services\ATS\CandidatePipelineService;
use App\Services\ATS\HandoffService;
use App\Services\ATS\ResumeParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Test 1: Mock resume parser extracts structured data from PDF
//
// Scenario: An HR user uploads a PDF resume.
// - The ResumeParserService stores the file via Spatie MediaLibrary (collection: 'resumes').
// - The service returns a structured JSON payload (Name, Email, Phone, Skills, Experience).
// - No PII (email, phone) appears in the application logs.
// ─────────────────────────────────────────────────────────────────────────────
it('test_mock_resume_parser_extracts_structured_data_from_pdf', function () {
    Storage::fake('public');

    // Arrange: create a fake PDF file with valid PDF magic bytes so Spatie MediaLibrary
    // accepts it (it validates actual MIME type via magic bytes, not the declared type).
    $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\nxref\n0 1\n0000000000 65535 f\ntrailer\n<< /Size 1 >>\nstartxref\n9\n%%EOF";
    $fakeResume = UploadedFile::fake()->createWithContent('john_doe_resume.pdf', $pdfContent);

    // Arrange: create a candidate record to attach the resume to
    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Mumbai HQ',
        'code'       => 'MUM-01',
        'address'    => 'BKC, Mumbai',
        'city'       => 'Mumbai',
        'state'      => 'Maharashtra',
        'state_code' => 'MH',
        'pin_code'   => '400051',
        'gis_lat'    => 19.0596,
        'gis_lng'    => 72.8656,
        'is_active'  => true,
    ]);
    $department  = Department::factory()->create();
    $designation = Designation::factory()->create();
    $requisition = JobRequisition::create([
        'requisition_id'      => (string) Str::uuid(),
        'location_id'         => $location->id,
        'department_id'       => $department->id,
        'designation_id'      => $designation->id,
        'number_of_positions' => 2,
        'job_description'     => 'Senior PHP Developer',
        'required_skills'     => 'PHP, Laravel, MySQL',
        'status'              => 'open',
        'created_by'          => User::factory()->create(['location_id' => $location->id])->id,
    ]);

    $candidate = Candidate::create([
        'candidate_id'     => (string) Str::uuid(),
        'requisition_id'   => $requisition->id,
        'first_name'       => 'John',
        'last_name'        => 'Doe',
        'email'            => 'john.doe@example.com',
        'phone'            => '9876543210',
        'current_stage'    => 'applied',
        'source'           => 'direct',
    ]);

    // Act: parse the resume via the mock service
    $parser = app(ResumeParserService::class);

    // Spy on Log to ensure PII is NOT logged
    Log::spy();

    $result = $parser->parse($fakeResume, $candidate);

    // Assert: structured data is returned
    expect($result)->toBeArray()
        ->and($result['status'])->toBe('success')
        ->and($result['data'])->toBeArray()
        ->and($result['data'])->toHaveKeys(['first_name', 'last_name', 'email', 'phone', 'skills', 'experience_years'])
        ->and($result['data']['skills'])->toBeArray()
        ->and($result['data']['experience_years'])->toBeFloat();

    // Assert: resume file is stored via Spatie MediaLibrary (collection: 'resumes')
    $candidate->refresh();
    expect($candidate->getMedia('resumes'))->toHaveCount(1)
        ->and($candidate->getFirstMedia('resumes')->file_name)->toBe('john_doe_resume.pdf');

    // Assert: PII (email, phone) was NOT written to application logs
    Log::shouldNotHaveReceived('info', [fn ($msg) => str_contains($msg, 'john.doe@example.com')]);
    Log::shouldNotHaveReceived('info', [fn ($msg) => str_contains($msg, '9876543210')]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2: Kanban stage change triggers a queued notification
//
// Scenario: A recruiter moves a candidate from "applied" to "interview_1".
// - The CandidatePipelineService updates the candidate's current_stage.
// - A CandidateStageHistory record is created.
// - A SendCandidateNotification job is pushed to the queue.
//
// Also tests rejection path:
// - Moving to "rejected" requires a rejection_reason.
// - The rejection_reason is stored on the CandidateStageHistory record.
// ─────────────────────────────────────────────────────────────────────────────
it('test_kanban_stage_change_triggers_queued_notification', function () {
    Queue::fake();

    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Bengaluru Office',
        'code'       => 'BLR-01',
        'address'    => 'Koramangala, Bengaluru',
        'city'       => 'Bengaluru',
        'state'      => 'Karnataka',
        'state_code' => 'KA',
        'pin_code'   => '560034',
        'gis_lat'    => 12.9352,
        'gis_lng'    => 77.6245,
        'is_active'  => true,
    ]);
    $recruiter = User::factory()->create(['location_id' => $location->id]);
    $recruiter->assignRole('recruiter');

    $department  = Department::factory()->create();
    $designation = Designation::factory()->create();
    $requisition = JobRequisition::create([
        'requisition_id'      => (string) Str::uuid(),
        'location_id'         => $location->id,
        'department_id'       => $department->id,
        'designation_id'      => $designation->id,
        'number_of_positions' => 1,
        'job_description'     => 'Backend Engineer',
        'required_skills'     => 'PHP, Laravel',
        'status'              => 'open',
        'created_by'          => $recruiter->id,
    ]);

    $candidate = Candidate::create([
        'candidate_id'   => (string) Str::uuid(),
        'requisition_id' => $requisition->id,
        'first_name'     => 'Priya',
        'last_name'      => 'Sharma',
        'email'          => 'priya.sharma@example.com',
        'phone'          => '9123456789',
        'current_stage'  => 'applied',
        'source'         => 'linkedin',
    ]);

    // Act: move candidate from "applied" to "interview_1"
    $pipelineService = app(CandidatePipelineService::class);
    $pipelineService->moveToStage($candidate, 'interview_1', $recruiter, [
        'notes' => 'Strong profile, shortlisted for technical round.',
    ]);

    // Assert: candidate stage is updated
    $candidate->refresh();
    expect($candidate->current_stage)->toBe('interview_1');

    // Assert: stage history record created
    $history = CandidateStageHistory::where('candidate_id', $candidate->id)
        ->where('to_stage', 'interview_1')
        ->first();
    expect($history)->not->toBeNull()
        ->and($history->from_stage)->toBe('applied')
        ->and($history->to_stage)->toBe('interview_1')
        ->and($history->moved_by)->toBe($recruiter->id);

    // Assert: SendCandidateNotification job was pushed to the queue
    Queue::assertPushed(SendCandidateNotification::class, function ($job) use ($candidate) {
        return $job->candidateId === $candidate->id && $job->stage === 'interview_1';
    });

    // ── Rejection path: rejection_reason is required ──────────────────────
    $pipelineService->moveToStage($candidate, 'rejected', $recruiter, [
        'rejection_reason' => 'Did not meet technical requirements.',
        'notes'            => 'Feedback shared with candidate.',
    ]);

    $candidate->refresh();
    expect($candidate->current_stage)->toBe('rejected')
        ->and($candidate->rejection_reason)->toBe('Did not meet technical requirements.');

    $rejectionHistory = CandidateStageHistory::where('candidate_id', $candidate->id)
        ->where('to_stage', 'rejected')
        ->first();
    expect($rejectionHistory)->not->toBeNull()
        ->and($rejectionHistory->rejection_reason)->toBe('Did not meet technical requirements.');

    // Assert: rejection notification also queued
    Queue::assertPushed(SendCandidateNotification::class, function ($job) use ($candidate) {
        return $job->candidateId === $candidate->id && $job->stage === 'rejected';
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3: Convert to Employee creates Core HR record and onboarding tasks
//
// Scenario: A recruiter marks a candidate as "Hired" and triggers "Convert to Employee".
// - HandoffService creates a new Employee record in Phase 1 Core HR.
// - Employee status is set to 'onboarding'.
// - Employee data is pre-filled from the candidate's parsed resume data.
// - The candidate's requisition_id and location_id are used for the employee.
// - The candidate's status is updated to 'converted'.
// - An EmployeeLifecycleHistory record is created (from Phase 1 observer).
// ─────────────────────────────────────────────────────────────────────────────
it('test_convert_to_employee_creates_core_hr_record_and_onboarding_tasks', function () {
    Queue::fake();

    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Hyderabad Office',
        'code'       => 'HYD-01',
        'address'    => 'HITEC City, Hyderabad',
        'city'       => 'Hyderabad',
        'state'      => 'Telangana',
        'state_code' => 'TS',
        'pin_code'   => '500081',
        'gis_lat'    => 17.4474,
        'gis_lng'    => 78.3762,
        'is_active'  => true,
    ]);
    $hrUser = User::factory()->create(['location_id' => $location->id]);
    $hrUser->assignRole('location_hr');

    $department  = Department::factory()->create();
    $designation = Designation::factory()->create();
    $requisition = JobRequisition::create([
        'requisition_id'      => (string) Str::uuid(),
        'location_id'         => $location->id,
        'department_id'       => $department->id,
        'designation_id'      => $designation->id,
        'number_of_positions' => 1,
        'job_description'     => 'Full Stack Developer',
        'required_skills'     => 'PHP, Vue.js',
        'status'              => 'open',
        'created_by'          => $hrUser->id,
    ]);

    // Candidate is in "hired" stage with parsed resume data
    $candidate = Candidate::create([
        'candidate_id'      => (string) Str::uuid(),
        'requisition_id'    => $requisition->id,
        'first_name'        => 'Arjun',
        'last_name'         => 'Mehta',
        'email'             => 'arjun.mehta@example.com',
        'phone'             => '9988776655',
        'current_stage'     => 'hired',
        'source'            => 'naukri',
        'parsed_skills'     => ['PHP', 'Laravel', 'Vue.js', 'MySQL'],
        'parsed_experience' => 4.5,
        'date_of_joining'   => '2026-08-01',
    ]);

    // Act: trigger the Convert to Employee handoff
    $handoffService = app(HandoffService::class);
    $employee = $handoffService->convertToEmployee($candidate, $hrUser);

    // Assert: Employee record was created in Phase 1 Core HR
    expect($employee)->toBeInstanceOf(Employee::class)
        ->and($employee->first_name)->toBe('Arjun')
        ->and($employee->last_name)->toBe('Mehta')
        ->and($employee->email)->toBe('arjun.mehta@example.com')
        ->and($employee->phone)->toBe('9988776655')
        ->and($employee->location_id)->toBe($location->id)
        ->and($employee->department_id)->toBe($department->id)
        ->and($employee->designation_id)->toBe($designation->id)
        ->and($employee->status)->toBe('onboarding')
        ->and($employee->date_of_joining->format('Y-m-d'))->toBe('2026-08-01');

    // Assert: employee_code was auto-generated by Phase 1 EmployeeObserver
    expect($employee->employee_code)->toMatch('/^EMP-[A-Z]{2}-\d{5}$/');

    // Assert: candidate status updated to 'converted'
    $candidate->refresh();
    expect($candidate->converted_to_employee_id)->toBe($employee->id)
        ->and($candidate->converted_at)->not->toBeNull();

    // Assert: EmployeeLifecycleHistory record created (Phase 1 integration)
    $historyRecord = \App\Models\EmployeeLifecycleHistory::where('employee_id', $employee->id)->first();
    expect($historyRecord)->not->toBeNull()
        ->and($historyRecord->new_status)->toBe('onboarding');
});
