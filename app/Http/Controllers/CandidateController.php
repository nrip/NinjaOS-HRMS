<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\Candidate;
use App\Models\JobRequisition;
use App\Services\ATS\CandidatePipelineService;
use App\Services\ATS\HandoffService;
use App\Services\ATS\ResumeParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class CandidateController extends Controller
{
    public function __construct(
        private readonly CandidatePipelineService $pipelineService,
        private readonly HandoffService $handoffService,
        private readonly ResumeParserService $parserService,
    ) {}
    public function show(Candidate $candidate): View
    {
        $candidate->load(['requisition', 'stageHistories.mover', 'employee']);
        return view('ats.candidates.detail', compact('candidate'));
    }
    public function store(Request $request, JobRequisition $requisition): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|max:191',
            'phone'      => 'nullable|string|max:20',
            'source'     => 'nullable|string|max:50',
            'resume'     => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);
        $data['requisition_id'] = $requisition->id;
        $candidate = Candidate::create($data);
        if ($request->hasFile('resume')) {
            $this->parserService->parse($request->file('resume'), $candidate);
        }
        return redirect()->route('ats.kanban.board', $requisition)
            ->with('success', 'Candidate added successfully.');
    }
    public function moveStage(Request $request, Candidate $candidate): RedirectResponse
    {
        $data = $request->validate([
            'stage'            => 'required|string|in:' . implode(',', Candidate::STAGES),
            'notes'            => 'nullable|string|max:1000',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);
        $this->pipelineService->moveToStage(
            $candidate,
            $data['stage'],
            auth()->user(),
            $data
        );
        return back()->with('success', "Candidate moved to stage: {$data['stage']}");
    }
    public function convertToEmployee(Candidate $candidate): RedirectResponse
    {
        $employee = $this->handoffService->convertToEmployee($candidate, auth()->user());
        return redirect()->route('employees.show', $employee)
            ->with('success', "Candidate successfully converted to Employee ({$employee->employee_code}).");
    }
}
