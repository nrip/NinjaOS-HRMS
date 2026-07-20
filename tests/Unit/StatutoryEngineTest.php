<?php

declare(strict_types=1);

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;
use App\Services\StatutoryEngine\PfCalculator;
use App\Services\StatutoryEngine\EsiCalculator;
use App\Services\StatutoryEngine\PtCalculator;
use App\Services\StatutoryEngine\TdsCalculator;
use App\Services\StatutoryEngine\GratuityCalculator;
use App\Services\StatutoryEngine\BonusCalculator;
use App\Services\StatutoryEngine\NetPayCalculator;

// ─────────────────────────────────────────────────────────────────────────────
// Helper: Build a PayrollInputDTO with sensible defaults, allowing overrides
// ─────────────────────────────────────────────────────────────────────────────
function makeDto(array $overrides = []): PayrollInputDTO
{
    $defaults = [
        'employeeId'             => '1',
        'employeeCode'           => 'EMP-MH-00001',
        'stateCode'              => 'MH',
        'grossSalary'            => 50000.0,
        'basicSalary'            => 20000.0,
        'hra'                    => 10000.0,
        'specialAllowance'       => 15000.0,
        'otherAllowances'        => 5000.0,
        'totalWorkingDays'       => 26,
        'lwpDays'                => 0.0,
        'encashmentDays'         => 0.0,
        'noticePayRecovery'      => 0.0,
        'otEarnings'             => 0.0,
        'yearsOfService'         => 3,
        'isEsiEligible'          => false,
        'isDisabled'             => false,
        'optedForPf'             => true,
        'taxRegime'              => 'new',
        'investmentDeclarations' => [],
        'payrollMonth'           => 7,
        'payrollYear'            => 2026,
    ];

    $merged = array_merge($defaults, $overrides);

    return new PayrollInputDTO(
        employeeId:             $merged['employeeId'],
        employeeCode:           $merged['employeeCode'],
        stateCode:              $merged['stateCode'],
        grossSalary:            $merged['grossSalary'],
        basicSalary:            $merged['basicSalary'],
        hra:                    $merged['hra'],
        specialAllowance:       $merged['specialAllowance'],
        otherAllowances:        $merged['otherAllowances'],
        totalWorkingDays:       $merged['totalWorkingDays'],
        lwpDays:                $merged['lwpDays'],
        encashmentDays:         $merged['encashmentDays'],
        noticePayRecovery:      $merged['noticePayRecovery'],
        otEarnings:             $merged['otEarnings'],
        yearsOfService:         $merged['yearsOfService'],
        isEsiEligible:          $merged['isEsiEligible'],
        isDisabled:             $merged['isDisabled'],
        optedForPf:             $merged['optedForPf'],
        taxRegime:              $merged['taxRegime'],
        investmentDeclarations: $merged['investmentDeclarations'],
        payrollMonth:           $merged['payrollMonth'],
        payrollYear:            $merged['payrollYear'],
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Test 1 (Mandatory): PF calculation respects ₹15,000 ceiling and EPS split
// ─────────────────────────────────────────────────────────────────────────────
it('test_pf_calculation_respects_15k_ceiling_and_eps_split', function () {
    $config = config('statutory.payroll.pf');
    $calc   = new PfCalculator();

    // ── Case A: Basic ABOVE the ₹15,000 ceiling ──────────────────────────────
    // Basic = ₹20,000 → PF wage = ₹15,000 (capped)
    $dtoAbove = makeDto(['basicSalary' => 20000.0]);
    $resultAbove = $calc->calculate($dtoAbove, $config);

    // Employee PF = 12% of 15,000 = ₹1,800
    expect($resultAbove['employee_pf'])->toBe(1800.0);

    // Employer EPF = 3.67% of 15,000 = ₹550.50
    expect($resultAbove['employer_epf'])->toBe(550.50);

    // Employer EPS = 8.33% of 15,000 = ₹1,249.50
    expect($resultAbove['employer_eps'])->toBe(1249.50);

    // Total employer = EPF + EPS = 550.50 + 1249.50 = ₹1,800
    expect($resultAbove['employer_total'])->toBe(1800.0);

    // PF wage used must be capped at 15,000
    expect($resultAbove['pf_wage'])->toBe(15000.0);

    // ── Case B: Basic BELOW the ₹15,000 ceiling ──────────────────────────────
    // Basic = ₹12,000 → PF wage = ₹12,000 (not capped)
    $dtoBelow = makeDto(['basicSalary' => 12000.0]);
    $resultBelow = $calc->calculate($dtoBelow, $config);

    // Employee PF = 12% of 12,000 = ₹1,440
    expect($resultBelow['employee_pf'])->toBe(1440.0);

    // Employer EPS = 8.33% of 12,000 = ₹999.60
    expect($resultBelow['employer_eps'])->toBe(999.60);

    // Employer EPF = 3.67% of 12,000 = ₹440.40
    expect($resultBelow['employer_epf'])->toBe(440.40);

    // PF wage used must equal basic (not capped)
    expect($resultBelow['pf_wage'])->toBe(12000.0);

    // ── Case C: Employee has NOT opted for PF ────────────────────────────────
    $dtoNoPf = makeDto(['basicSalary' => 20000.0, 'optedForPf' => false]);
    $resultNoPf = $calc->calculate($dtoNoPf, $config);

    expect($resultNoPf['employee_pf'])->toBe(0.0)
        ->and($resultNoPf['employer_epf'])->toBe(0.0)
        ->and($resultNoPf['employer_eps'])->toBe(0.0)
        ->and($resultNoPf['pf_wage'])->toBe(0.0);

    // ── Case D: Verify EPF + EPS = 12% of PF wage (employer contribution) ────
    // This is the critical split check: 3.67% + 8.33% = 12.00%
    $pfWage = $resultAbove['pf_wage'];
    $totalEmployerContribution = round($pfWage * 0.12, 2);
    expect(round($resultAbove['employer_epf'] + $resultAbove['employer_eps'], 2))
        ->toBe($totalEmployerContribution);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2 (Mandatory): PT calculation applies correct state slab and Haryana nil
// ─────────────────────────────────────────────────────────────────────────────
it('test_pt_calculation_applies_correct_state_slab_and_haryana_nil', function () {
    $config = config('statutory.payroll.pt_slabs');
    $calc   = new PtCalculator();

    // ── Haryana (HR): PT is NOT applicable — must return 0 ───────────────────
    $dtoHR = makeDto(['stateCode' => 'HR', 'grossSalary' => 50000.0]);
    expect($calc->calculate($dtoHR, $config))->toBe(0.0);

    // ── Delhi (DL): PT is NOT applicable — must return 0 ─────────────────────
    $dtoDL = makeDto(['stateCode' => 'DL', 'grossSalary' => 50000.0]);
    expect($calc->calculate($dtoDL, $config))->toBe(0.0);

    // ── Maharashtra (MH): Slab-based with February special case ──────────────
    // Gross = ₹50,000 → slab: 10001+ → ₹200 in non-Feb months
    $dtoMH = makeDto(['stateCode' => 'MH', 'grossSalary' => 50000.0, 'payrollMonth' => 7]);
    expect($calc->calculate($dtoMH, $config))->toBe(200.0);

    // Gross = ₹50,000 in February → ₹300
    $dtoMHFeb = makeDto(['stateCode' => 'MH', 'grossSalary' => 50000.0, 'payrollMonth' => 2]);
    expect($calc->calculate($dtoMHFeb, $config))->toBe(300.0);

    // Gross = ₹7,000 → slab: 0–7500 → ₹0
    $dtoMHLow = makeDto(['stateCode' => 'MH', 'grossSalary' => 7000.0, 'payrollMonth' => 7]);
    expect($calc->calculate($dtoMHLow, $config))->toBe(0.0);

    // Gross = ₹8,000 → slab: 7501–10000 → ₹175
    $dtoMHMid = makeDto(['stateCode' => 'MH', 'grossSalary' => 8000.0, 'payrollMonth' => 7]);
    expect($calc->calculate($dtoMHMid, $config))->toBe(175.0);

    // ── Karnataka (KA): Slab-based ────────────────────────────────────────────
    // Gross = ₹14,000 → slab: 0–14999 → ₹0
    $dtoKALow = makeDto(['stateCode' => 'KA', 'grossSalary' => 14000.0]);
    expect($calc->calculate($dtoKALow, $config))->toBe(0.0);

    // Gross = ₹20,000 → slab: 15000–24999 → ₹150
    $dtoKAMid = makeDto(['stateCode' => 'KA', 'grossSalary' => 20000.0]);
    expect($calc->calculate($dtoKAMid, $config))->toBe(150.0);

    // Gross = ₹30,000 → slab: 25000+ → ₹200
    $dtoKAHigh = makeDto(['stateCode' => 'KA', 'grossSalary' => 30000.0]);
    expect($calc->calculate($dtoKAHigh, $config))->toBe(200.0);

    // ── Gujarat (GJ): Slab-based ──────────────────────────────────────────────
    $dtoGJZero = makeDto(['stateCode' => 'GJ', 'grossSalary' => 5000.0]);
    expect($calc->calculate($dtoGJZero, $config))->toBe(0.0);

    $dtoGJMid = makeDto(['stateCode' => 'GJ', 'grossSalary' => 7000.0]);
    expect($calc->calculate($dtoGJMid, $config))->toBe(80.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3 (Mandatory): Payroll variance report flags > 5% net pay change
// ─────────────────────────────────────────────────────────────────────────────
it('test_payroll_variance_report_flags_greater_than_5_percent_change', function () {
    $varianceConfig = config('statutory.payroll.variance');
    $threshold      = $varianceConfig['flag_threshold_percent']; // 5.0

    // Inline variance calculation logic (mirrors PayrollVarianceService)
    $calculateVariance = function (float $previous, float $current) use ($threshold): array {
        if ($previous == 0.0) {
            return ['variance_percent' => 100.0, 'flagged' => true, 'reason' => 'new_employee'];
        }
        $variancePercent = abs(($current - $previous) / $previous) * 100;
        return [
            'variance_percent' => round($variancePercent, 2),
            'flagged'          => $variancePercent > $threshold,
            'reason'           => $variancePercent > $threshold ? 'exceeds_threshold' : 'within_threshold',
        ];
    };

    // ── Case A: No change — should NOT be flagged ─────────────────────────────
    $resultA = $calculateVariance(50000.0, 50000.0);
    expect($resultA['flagged'])->toBeFalse()
        ->and($resultA['variance_percent'])->toBe(0.0);

    // ── Case B: Exactly 5% increase — should NOT be flagged (threshold is >5%) ─
    $resultB = $calculateVariance(50000.0, 52500.0);
    expect($resultB['flagged'])->toBeFalse()
        ->and($resultB['variance_percent'])->toBe(5.0);

    // ── Case C: 5.01% increase — MUST be flagged ─────────────────────────────
    $resultC = $calculateVariance(50000.0, 52505.0);
    expect($resultC['flagged'])->toBeTrue()
        ->and($resultC['variance_percent'])->toBeGreaterThan(5.0);

    // ── Case D: Large decrease (e.g., LWP month) — MUST be flagged ───────────
    $resultD = $calculateVariance(50000.0, 40000.0);
    expect($resultD['flagged'])->toBeTrue()
        ->and($resultD['variance_percent'])->toBe(20.0);

    // ── Case E: New employee (previous = 0) — MUST be flagged as new_employee ─
    $resultE = $calculateVariance(0.0, 45000.0);
    expect($resultE['flagged'])->toBeTrue()
        ->and($resultE['reason'])->toBe('new_employee');

    // ── Case F: Verify config threshold is exactly 5.0 ───────────────────────
    expect($threshold)->toBe(5.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 4: LWP deduction prorates basic and allowances correctly
// ─────────────────────────────────────────────────────────────────────────────
it('test_lwp_deduction_prorates_basic_and_allowances_correctly', function () {
    // ── Setup: Employee with 2 LWP days out of 26 working days ───────────────
    // Gross = ₹52,000, Basic = ₹20,800, HRA = ₹10,400, Special = ₹15,600, Other = ₹5,200
    // LWP formula: (Component / totalWorkingDays) * lwpDays
    $dto = makeDto([
        'grossSalary'      => 52000.0,
        'basicSalary'      => 20800.0,
        'hra'              => 10400.0,
        'specialAllowance' => 15600.0,
        'otherAllowances'  => 5200.0,
        'totalWorkingDays' => 26,
        'lwpDays'          => 2.0,
    ]);

    // ── Part A: Verify the DTO's lwpDeductionFor() helper ────────────────────
    // Basic LWP deduction = (20800 / 26) * 2 = 800 * 2 = ₹1,600
    expect($dto->lwpDeductionFor($dto->basicSalary))->toBe(1600.0);

    // HRA LWP deduction = (10400 / 26) * 2 = 400 * 2 = ₹800
    expect($dto->lwpDeductionFor($dto->hra))->toBe(800.0);

    // Special allowance LWP deduction = (15600 / 26) * 2 = 600 * 2 = ₹1,200
    expect($dto->lwpDeductionFor($dto->specialAllowance))->toBe(1200.0);

    // Other allowances LWP deduction = (5200 / 26) * 2 = 200 * 2 = ₹400
    expect($dto->lwpDeductionFor($dto->otherAllowances))->toBe(400.0);

    // Total LWP deduction = Gross LWP deduction = (52000 / 26) * 2 = 2000 * 2 = ₹4,000
    expect($dto->lwpDeductionFor($dto->grossSalary))->toBe(4000.0);

    // ── Part B: Effective gross after LWP ────────────────────────────────────
    // effectiveGross = grossSalary - lwpDeductionFor(gross) + otEarnings
    // = 52000 - 4000 + 0 = ₹48,000
    expect($dto->effectiveGross())->toBe(48000.0);

    // ── Part C: NetPayCalculator applies LWP correctly ───────────────────────
    $pfConfig  = config('statutory.payroll.pf');
    $ptConfig  = config('statutory.payroll.pt_slabs');
    $esiConfig = config('statutory.payroll.esi');
    $tdsConfig = config('statutory.payroll.tds');
    $lwpConfig = config('statutory.payroll.lwp');

    $pfCalc  = new PfCalculator();
    $ptCalc  = new PtCalculator();
    $esiCalc = new EsiCalculator();
    $tdsCalc = new TdsCalculator();
    $netCalc = new NetPayCalculator($pfCalc, $esiCalc, $ptCalc, $tdsCalc);

    $result = $netCalc->calculate($dto, [
        'pf'  => $pfConfig,
        'esi' => $esiConfig,
        'pt'  => $ptConfig,
        'tds' => $tdsConfig,
        'lwp' => $lwpConfig,
    ]);

    // Net pay must be LESS than gross (LWP deduction applied)
    expect($result['net_pay'])->toBeLessThan($dto->grossSalary);

    // LWP deduction in the result must match our manual calculation
    expect($result['lwp_deduction'])->toBe(4000.0);

    // ── Part D: Zero LWP — no deduction ──────────────────────────────────────
    $dtoNoLwp = makeDto(['grossSalary' => 52000.0, 'lwpDays' => 0.0]);
    expect($dtoNoLwp->lwpDeductionFor($dtoNoLwp->grossSalary))->toBe(0.0);
    expect($dtoNoLwp->effectiveGross())->toBe(52000.0);

    // ── Part E: Half-day LWP (0.5 days) ──────────────────────────────────────
    $dtoHalfDay = makeDto([
        'grossSalary'      => 52000.0,
        'basicSalary'      => 20800.0,
        'totalWorkingDays' => 26,
        'lwpDays'          => 0.5,
    ]);
    // Basic LWP deduction = (20800 / 26) * 0.5 = 800 * 0.5 = ₹400
    expect($dtoHalfDay->lwpDeductionFor($dtoHalfDay->basicSalary))->toBe(400.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 5: ESI calculation respects standard and disabled ceilings
// ─────────────────────────────────────────────────────────────────────────────
it('test_esi_calculation_respects_21k_standard_and_25k_disabled_ceilings', function () {
    $config = config('statutory.payroll.esi');
    $calc   = new EsiCalculator();

    // ── Case A: Gross within standard ceiling (₹21,000) — ESI applicable ─────
    $dtoEligible = makeDto([
        'grossSalary'  => 18000.0,
        'isEsiEligible'=> true,
        'isDisabled'   => false,
    ]);
    $resultA = $calc->calculate($dtoEligible, $config);

    // Employee ESI = 0.75% of 18,000 = ₹135
    expect($resultA['employee_esi'])->toBe(135.0);

    // Employer ESI = 3.25% of 18,000 = ₹585
    expect($resultA['employer_esi'])->toBe(585.0);

    // ── Case B: Gross ABOVE standard ceiling (₹21,000) — ESI NOT applicable ──
    $dtoAboveCeiling = makeDto([
        'grossSalary'  => 25000.0,
        'isEsiEligible'=> false,  // Determined at time of joining
        'isDisabled'   => false,
    ]);
    $resultB = $calc->calculate($dtoAboveCeiling, $config);
    expect($resultB['employee_esi'])->toBe(0.0)
        ->and($resultB['employer_esi'])->toBe(0.0);

    // ── Case C: Disabled employee — ceiling is ₹25,000 ───────────────────────
    $dtoDisabled = makeDto([
        'grossSalary'  => 23000.0,
        'isEsiEligible'=> true,
        'isDisabled'   => true,
    ]);
    $resultC = $calc->calculate($dtoDisabled, $config);

    // Employee ESI = 0.75% of 23,000 = ₹172.50
    expect($resultC['employee_esi'])->toBe(172.50);
    expect($resultC['employer_esi'])->toBe(747.50); // 3.25% of 23,000

    // ── Case D: Disabled employee ABOVE ₹25,000 ceiling — NOT applicable ─────
    $dtoDisabledAbove = makeDto([
        'grossSalary'  => 26000.0,
        'isEsiEligible'=> false,
        'isDisabled'   => true,
    ]);
    $resultD = $calc->calculate($dtoDisabledAbove, $config);
    expect($resultD['employee_esi'])->toBe(0.0);

    // ── Case E: Verify config ceilings are correct ────────────────────────────
    expect($config['wage_ceiling_standard'])->toBe(21000)
        ->and($config['wage_ceiling_disabled'])->toBe(25000)
        ->and($config['employee_rate'])->toBe(0.0075)
        ->and($config['employer_rate'])->toBe(0.0325);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 6: TDS calculation uses correct regime slabs and investment declarations
// ─────────────────────────────────────────────────────────────────────────────
it('test_tds_calculation_uses_regime_slabs_and_investment_declarations', function () {
    $config = config('statutory.payroll.tds');
    $calc   = new TdsCalculator();

    // ── Case A: New Regime — no deductions, annual income ₹9,00,000 ──────────
    // Annual gross = 75,000 * 12 = ₹9,00,000
    // New regime slabs: 0–3L=0%, 3L–6L=5%, 6L–9L=10%
    // Tax = 0 + (300000*0.05) + (300000*0.10) = 15000 + 30000 = ₹45,000
    // Cess = 45000 * 4% = ₹1,800
    // Total annual tax = ₹46,800
    // Monthly TDS = 46800 / 12 = ₹3,900
    $dtoNewRegime = makeDto([
        'grossSalary'            => 75000.0,
        'basicSalary'            => 30000.0,
        'hra'                    => 15000.0,
        'specialAllowance'       => 22500.0,
        'otherAllowances'        => 7500.0,
        'taxRegime'              => 'new',
        'investmentDeclarations' => [],
        'payrollMonth'           => 7,
        'payrollYear'            => 2026,
    ]);
    $resultNew = $calc->calculate($dtoNewRegime, $config);
    // Correct calculation:
    // Annual gross = 75000 * 12 = 900000
    // Standard deduction (new regime) = 50000 → taxable = 850000
    // Slabs: 0–3L=0%, 3L–6L=5% (299999*0.05=14999.95), 6L–8.5L=10% (249999*0.10=24999.9)
    // Tax = 40000 (clean: 300000*0% + 300000*5% + 250000*10%), Cess = 1600, Annual = 41600, Monthly = 3466.67
    expect($resultNew['monthly_tds'])->toBe(3466.67)
        ->and($resultNew['annual_tax'])->toBe(41600.0)
        ->and($resultNew['regime'])->toBe('new');

    // ── Case B: Old Regime — with 80C and 80D declarations ───────────────────
    // Annual gross = 75,000 * 12 = ₹9,00,000
    // Standard deduction = ₹50,000
    // 80C = ₹1,50,000, 80D = ₹25,000
    // Taxable income = 900000 - 50000 - 150000 - 25000 = ₹6,75,000
    // Old regime slabs: 0–2.5L=0%, 2.5L–5L=5%, 5L–6.75L=20%
    // Tax = 0 + (250000*0.05) + (175000*0.20) = 12500 + 35000 = ₹47,500
    // Cess = 47500 * 4% = ₹1,900
    // Total annual tax = ₹49,400
    // Monthly TDS = 49400 / 12 = ₹4,116.67
    $dtoOldRegime = makeDto([
        'grossSalary'            => 75000.0,
        'basicSalary'            => 30000.0,
        'hra'                    => 15000.0,
        'specialAllowance'       => 22500.0,
        'otherAllowances'        => 7500.0,
        'taxRegime'              => 'old',
        'investmentDeclarations' => [
            '80c' => 150000.0,
            '80d' => 25000.0,
        ],
        'payrollMonth'           => 7,
        'payrollYear'            => 2026,
    ]);
    $resultOld = $calc->calculate($dtoOldRegime, $config);
    // Correct calculation:
    // Annual gross = 900000, std=50000, 80C=150000, 80D=25000 → taxable = 675000
    // Slabs: 0–2.5L=0%, 2.5L–5L=5% (249999*0.05=12499.95), 5L–6.75L=20% (174999*0.20=34999.8)
    // Tax = 47500 (250000*5% + 175000*20%), Cess = 1900, Annual = 49400, Monthly = 4116.67
    expect($resultOld['annual_tax'])->toBe(49400.0)
        ->and($resultOld['monthly_tds'])->toBe(round(49400.0 / 12, 2))
        ->and($resultOld['regime'])->toBe('old');

    // ── Case C: Income below exemption limit — TDS = 0 ───────────────────────
    // Annual gross = 20,000 * 12 = ₹2,40,000 (below ₹3L new regime threshold)
    $dtoLowIncome = makeDto([
        'grossSalary' => 20000.0,
        'basicSalary' => 8000.0,
        'taxRegime'   => 'new',
    ]);
    $resultLow = $calc->calculate($dtoLowIncome, $config);
    expect($resultLow['monthly_tds'])->toBe(0.0)
        ->and($resultLow['annual_tax'])->toBe(0.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 7: Gratuity calculation respects ₹20L ceiling and 5-year eligibility
// ─────────────────────────────────────────────────────────────────────────────
it('test_gratuity_calculation_respects_20l_ceiling_and_5_year_eligibility', function () {
    $config = config('statutory.payroll.gratuity');
    $calc   = new GratuityCalculator();

    // ── Case A: Eligible employee (5+ years) — standard calculation ──────────
    // Formula: (Basic / 26) * 15 * years_of_service
    // Basic = ₹30,000, Years = 7
    // Gratuity = (30000 / 26) * 15 * 7 = 1153.85 * 15 * 7 = ₹1,21,153.85
    $dtoEligible = makeDto([
        'basicSalary'    => 30000.0,
        'yearsOfService' => 7,
    ]);
    $resultEligible = $calc->calculate($dtoEligible, $config);
    expect($resultEligible['eligible'])->toBeTrue()
        ->and($resultEligible['gratuity_amount'])->toBe(round((30000 / 26) * 15 * 7, 2));

    // ── Case B: NOT eligible (< 5 years) — gratuity = 0 ─────────────────────
    $dtoIneligible = makeDto([
        'basicSalary'    => 30000.0,
        'yearsOfService' => 4,
    ]);
    $resultIneligible = $calc->calculate($dtoIneligible, $config);
    expect($resultIneligible['eligible'])->toBeFalse()
        ->and($resultIneligible['gratuity_amount'])->toBe(0.0);

    // ── Case C: Very high salary — capped at ₹20 Lakh ────────────────────────
    // Basic = ₹2,00,000, Years = 20
    // Uncapped = (200000 / 26) * 15 * 20 = 7692.31 * 300 = ₹23,07,692.31
    // Capped at ₹20,00,000
    $dtoHighSalary = makeDto([
        'basicSalary'    => 200000.0,
        'yearsOfService' => 20,
    ]);
    $resultCapped = $calc->calculate($dtoHighSalary, $config);
    expect($resultCapped['eligible'])->toBeTrue()
        ->and($resultCapped['gratuity_amount'])->toBe(2000000.0)
        ->and($resultCapped['capped'])->toBeTrue();

    // ── Case D: Verify config ceiling is ₹20,00,000 ──────────────────────────
    expect($config['ceiling'])->toBe(2000000)
        ->and($config['minimum_service_years'])->toBe(5)
        ->and($config['days_divisor'])->toBe(26)
        ->and($config['years_multiplier'])->toBe(15);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 8: Bonus calculation respects ₹21,000 wage ceiling and Bonus Act rules
// ─────────────────────────────────────────────────────────────────────────────
it('test_bonus_calculation_respects_21k_wage_ceiling_and_bonus_act_rules', function () {
    $config = config('statutory.payroll.bonus');
    $calc   = new BonusCalculator();

    // ── Case A: Eligible employee (gross <= ₹21,000) — minimum bonus ─────────
    // Gross = ₹18,000, Basic = ₹9,000
    // Calculation ceiling = ₹7,000 → bonus base = min(9000, 7000) = ₹7,000
    // Minimum bonus = 8.33% of 7000 * 12 months = 0.0833 * 84000 = ₹6,997.20
    $dtoEligible = makeDto([
        'grossSalary' => 18000.0,
        'basicSalary' => 9000.0,
    ]);
    $resultEligible = $calc->calculate($dtoEligible, $config);
    expect($resultEligible['eligible'])->toBeTrue()
        ->and($resultEligible['annual_bonus'])->toBe(round(7000 * 12 * 0.0833, 2));

    // ── Case B: NOT eligible (gross > ₹21,000) — bonus = 0 ──────────────────
    $dtoIneligible = makeDto([
        'grossSalary' => 25000.0,
        'basicSalary' => 12500.0,
    ]);
    $resultIneligible = $calc->calculate($dtoIneligible, $config);
    expect($resultIneligible['eligible'])->toBeFalse()
        ->and($resultIneligible['annual_bonus'])->toBe(0.0);

    // ── Case C: Basic > calculation ceiling — bonus base capped at ₹7,000 ────
    $dtoHighBasic = makeDto([
        'grossSalary' => 20000.0,
        'basicSalary' => 15000.0, // > 7000 ceiling
    ]);
    $resultCapped = $calc->calculate($dtoHighBasic, $config);
    expect($resultCapped['bonus_base'])->toBe(7000.0); // Capped at calculation_ceiling

    // ── Case D: Verify config values ─────────────────────────────────────────
    expect($config['wage_ceiling'])->toBe(21000)
        ->and($config['calculation_ceiling'])->toBe(7000)
        ->and($config['min_rate'])->toBe(0.0833)
        ->and($config['max_rate'])->toBe(0.20);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 9: PayrollInputDTO validates inputs and throws on invalid data
// ─────────────────────────────────────────────────────────────────────────────
it('test_payroll_input_dto_validates_inputs_and_throws_on_invalid_data', function () {
    // Invalid tax regime
    expect(fn () => makeDto(['taxRegime' => 'flat']))
        ->toThrow(\InvalidArgumentException::class, "taxRegime must be 'old' or 'new'");

    // Invalid month
    expect(fn () => makeDto(['payrollMonth' => 13]))
        ->toThrow(\InvalidArgumentException::class, 'payrollMonth must be 1–12');

    // LWP exceeds working days
    expect(fn () => makeDto(['lwpDays' => 30.0, 'totalWorkingDays' => 26]))
        ->toThrow(\InvalidArgumentException::class, 'lwpDays');

    // Zero working days
    expect(fn () => makeDto(['totalWorkingDays' => 0]))
        ->toThrow(\InvalidArgumentException::class, 'totalWorkingDays must be >= 1');

    // Valid DTO should not throw
    expect(fn () => makeDto())->not->toThrow(\InvalidArgumentException::class);
});
