# Phase 5: Applicant Tracking System (ATS) Implementation Proposal

## 1. Core Objectives
This phase introduces the Applicant Tracking System (ATS) module to NinjaOS-HRMS. The primary goal is to manage the end-to-end recruitment lifecycle—from job requisition to candidate onboarding—while ensuring seamless integration with the Phase 1 Core HR module.

## 2. Mandatory Features & Guardrails

### 2.1 Requisition & Approval Workflow
- **Requisition Request:** Hiring Managers can raise a Job Requisition.
- **Approval Chain:** Manager → Location HR → Central HR.
- **State Machine:** `draft` → `pending_location_hr` → `pending_central_hr` → `published` / `rejected`.
- **Location Scope:** Location HR can only approve requisitions for their respective locations.

### 2.2 Mock Resume Parser Integration
- **No Custom Parser:** The system will NOT build a custom parsing engine.
- **Mock Service:** A simulated third-party API service (e.g., Sovren/Textkernel) will be implemented via an internal interface.
- **Contract:** Accepts a PDF/DOCX file and returns a structured JSON payload containing: Name, Email, Phone, Skills, and Experience.

### 2.3 Kanban Pipeline
- **Visual Board:** A drag-and-drop Kanban board built with Alpine.js.
- **Pipeline Stages:** Applied, Screened, Interview 1, Interview 2, Offer, Hired, Rejected.
- **Stage Management:** Candidates move through stages, triggering automated actions.

### 2.4 Automated Communication
- **Event-Driven Notifications:** Queued email/SMS notifications dispatched upon pipeline stage changes.
- **Examples:** Interview scheduling emails, offer letters, and rejection notices.

### 2.5 "Convert to Employee" Handoff
- **Seamless Transition:** A one-click action on a "Hired" candidate to create a new `Employee` record in the Phase 1 Core HR module.
- **Data Pre-filling:** Extracted resume data (Name, Email, Phone) pre-fills the employee profile.
- **Onboarding Trigger:** Initiates the Phase 1 onboarding checklist.

## 3. Implementation Details

### 3.1 Mock Resume Parser API Contract
The mock parser will be implemented as a dedicated service class (`ResumeParserService`) that simulates an external HTTP call.

**Input:**
- File path or uploaded file instance (PDF/DOCX).

**Output (Structured JSON):**
```json
{
  "status": "success",
  "data": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "+919876543210",
    "skills": ["PHP", "Laravel", "MySQL", "JavaScript"],
    "experience_years": 5.5,
    "education": [
      {
        "degree": "B.Tech Computer Science",
        "institution": "Example University",
        "year_of_passing": 2018
      }
    ]
  }
}
```

### 3.2 "Convert to Employee" Bridge
The handoff between ATS and Core HR requires careful data mapping to ensure consistency.

1. **Trigger:** User clicks "Convert to Employee" on a candidate in the "Hired" stage.
2. **Data Mapping:**
   - Candidate `first_name` + `last_name` → Employee `name`
   - Candidate `email` → Employee `email`
   - Candidate `phone` → Employee `phone`
   - Job Requisition `location_id`, `department_id`, `designation` → Employee attributes.
3. **Creation:** A new `Employee` record is created with the status `onboarding`.
4. **Lifecycle Event:** The `EmployeeObserver` (from Phase 1) automatically generates the `employee_code` and creates the initial `employee_lifecycle_history` record.

## 4. Proposed File List

### Models & Migrations
- `JobRequisition` (Migration + Model)
- `Candidate` (Migration + Model)
- `CandidateStageHistory` (Migration + Model for tracking pipeline movement)

### Services
- `ATS/RequisitionService.php` (Approval workflow logic)
- `ATS/ResumeParserService.php` (Mock API integration)
- `ATS/CandidatePipelineService.php` (Kanban stage management & notifications)
- `ATS/HandoffService.php` (Bridging ATS Candidate to Core HR Employee)

### Controllers
- `JobRequisitionController.php`
- `CandidateController.php`
- `KanbanBoardController.php`

### Views (Blade + Alpine.js)
- `ats/requisitions/index.blade.php`
- `ats/requisitions/form.blade.php`
- `ats/kanban/board.blade.php` (Drag-and-drop interface)
- `ats/candidates/detail.blade.php`

### Tests (Pest Feature Tests)
- `test_mock_resume_parser_extracts_structured_data_from_pdf`
- `test_kanban_stage_change_triggers_queued_notification`
- `test_convert_to_employee_creates_core_hr_record_and_onboarding_tasks`

## 5. Next Steps
Upon your approval of this proposal, I will proceed with writing the 3 mandated Pest Feature tests, followed by the implementation of the models, services, controllers, and views.
