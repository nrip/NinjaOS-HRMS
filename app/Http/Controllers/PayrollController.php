<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcknowledgeVarianceRequest;
use App\Http\Requests\ProcessPayrollRequest;
use App\Models\PayrollRecord;
use App\Services\Payroll\PayrollService;
use App\Services\Payroll\PayrollVarianceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService         $payrollService,
        private readonly PayrollVarianceService $varianceService,
    ) {}

    /**
     * List payroll records for a location/period.
     */
    public function index(Request $request): View
    {
        $month      = (int) $request->get('month', now()->month);
        $year       = (int) $request->get('year', now()->year);
        $locationId = (int) $request->get('location_id', auth()->user()->location_id ?? 1);

        $records       = PayrollRecord::where('location_id', $locationId)
            ->where('payroll_month', $month)
            ->where('payroll_year', $year)
            ->with('employee')
            ->paginate(25);

        $varianceReport = $this->varianceService->generateReport($locationId, $month, $year);

        return view('payroll.index', compact('records', 'varianceReport', 'month', 'year', 'locationId'));
    }

    /**
     * Show a single payslip.
     */
    public function show(PayrollRecord $payrollRecord): View
    {
        $this->authorize('view', $payrollRecord);
        return view('payroll.payslip', ['record' => $payrollRecord]);
    }

    /**
     * Process payroll for a location/period (creates draft records).
     */
    public function process(ProcessPayrollRequest $request): RedirectResponse
    {
        $this->authorize('process', PayrollRecord::class);

        $records = $this->payrollService->processPayrollForLocation(
            (int) $request->location_id,
            (int) $request->month,
            (int) $request->year,
        );

        return redirect()
            ->route('payroll.index', [
                'location_id' => $request->location_id,
                'month'       => $request->month,
                'year'        => $request->year,
            ])
            ->with('success', "Payroll processed for {$records->count()} employees.");
    }

    /**
     * Approve a payroll record (Manager/HR).
     */
    public function approve(PayrollRecord $payrollRecord): RedirectResponse
    {
        $this->authorize('approve', $payrollRecord);
        $payrollRecord->approve(auth()->id());
        return back()->with('success', 'Payroll record approved.');
    }

    /**
     * Finalize payroll for a location/period (locks all records).
     * Requires all variance flags to be acknowledged first.
     */
    public function finalize(Request $request): RedirectResponse
    {
        $this->authorize('finalize', PayrollRecord::class);

        $locationId = (int) $request->location_id;
        $month      = (int) $request->month;
        $year       = (int) $request->year;

        if (! $this->varianceService->allVariancesAcknowledged($locationId, $month, $year)) {
            return back()->withErrors(['variance' => 'All variance flags must be acknowledged before finalization.']);
        }

        PayrollRecord::where('location_id', $locationId)
            ->where('payroll_month', $month)
            ->where('payroll_year', $year)
            ->where('status', 'approved')
            ->each(fn ($r) => $r->finalize(auth()->id()));

        return back()->with('success', 'Payroll finalized successfully.');
    }

    /**
     * Acknowledge a variance flag for a specific payroll record.
     */
    public function acknowledgeVariance(AcknowledgeVarianceRequest $request, PayrollRecord $payrollRecord): RedirectResponse
    {
        $this->authorize('acknowledgeVariance', $payrollRecord);
        $this->varianceService->acknowledgeVariance($payrollRecord->id, auth()->id());
        return back()->with('success', 'Variance acknowledged.');
    }

    /**
     * Show the variance report for a location/period.
     */
    public function varianceReport(Request $request): View
    {
        $this->authorize('viewVarianceReport', PayrollRecord::class);

        $month      = (int) $request->get('month', now()->month);
        $year       = (int) $request->get('year', now()->year);
        $locationId = (int) $request->get('location_id', auth()->user()->location_id ?? 1);

        $report = $this->varianceService->generateReport($locationId, $month, $year);

        return view('payroll.variance', compact('report', 'month', 'year', 'locationId'));
    }

    /**
     * Show the parallel run reconciliation view.
     */
    public function reconciliation(Request $request): View
    {
        $this->authorize('viewReconciliation', PayrollRecord::class);

        $month      = (int) $request->get('month', now()->month);
        $year       = (int) $request->get('year', now()->year);
        $locationId = (int) $request->get('location_id', auth()->user()->location_id ?? 1);

        $records = PayrollRecord::where('location_id', $locationId)
            ->where('payroll_month', $month)
            ->where('payroll_year', $year)
            ->whereNotNull('legacy_net_pay')
            ->with('employee')
            ->get();

        return view('payroll.reconciliation', compact('records', 'month', 'year', 'locationId'));
    }
}
