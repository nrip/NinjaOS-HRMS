<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\Department;
use App\Models\Designation;
use App\Models\JobRequisition;
use App\Models\Location;
use App\Services\ATS\RequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class JobRequisitionController extends Controller
{
    public function __construct(private readonly RequisitionService $service) {}
    public function index(): View
    {
        $requisitions = JobRequisition::with(['location', 'department', 'designation', 'creator'])
            ->latest()->paginate(20);
        return view('ats.requisitions.index', compact('requisitions'));
    }
    public function create(): View
    {
        $locations    = Location::withoutGlobalScopes()->where('is_active', true)->get();
        $departments  = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();
        return view('ats.requisitions.form', compact('locations', 'departments', 'designations'));
    }
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id'         => 'required|exists:locations,id',
            'department_id'       => 'required|exists:departments,id',
            'designation_id'      => 'required|exists:designations,id',
            'number_of_positions' => 'required|integer|min:1',
            'job_description'     => 'required|string',
            'required_skills'     => 'required|string',
            'closing_date'        => 'nullable|date|after:today',
        ]);
        $data['created_by'] = auth()->id();
        $requisition = JobRequisition::create($data);
        return redirect()->route('ats.requisitions.show', $requisition)
            ->with('success', 'Job requisition created successfully.');
    }
    public function show(JobRequisition $requisition): View
    {
        $requisition->load(['location', 'department', 'designation', 'creator', 'approver', 'candidates']);
        return view('ats.requisitions.show', compact('requisition'));
    }
    public function submit(JobRequisition $requisition): RedirectResponse
    {
        $this->service->submit($requisition, auth()->user());
        return back()->with('success', 'Requisition submitted for approval.');
    }
    public function approve(Request $request, JobRequisition $requisition): RedirectResponse
    {
        $user = auth()->user();
        if ($requisition->status === 'pending_location_hr') {
            $this->service->approveByLocationHr($requisition, $user);
        } elseif ($requisition->status === 'pending_central_hr') {
            $this->service->approveByCentralHr($requisition, $user);
        }
        return back()->with('success', 'Requisition approved.');
    }
    public function reject(Request $request, JobRequisition $requisition): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->reject($requisition, auth()->user(), $request->reason);
        return back()->with('success', 'Requisition rejected.');
    }
}
