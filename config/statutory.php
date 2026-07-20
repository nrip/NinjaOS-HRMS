<?php

/**
 * NexusOS Statutory Configuration
 *
 * All statutory values (PF ceiling, ESI ceiling, PT slabs, leave accrual rules, etc.)
 * are defined here. These values are NEVER hardcoded in business logic.
 *
 * Effective dates allow for rule versioning — historical payroll runs remain reproducible.
 */

return [

    // ─────────────────────────────────────────────────────────────────────────
    // Provident Fund (PF) — Payment of Provident Funds Act, 1952
    // ─────────────────────────────────────────────────────────────────────────
    'pf' => [
        'wage_ceiling'              => 15000,   // Monthly wage ceiling for PF calculation
        'employee_contribution_rate'=> 0.12,    // 12% of wage (capped at ceiling)
        'employer_contribution_rate'=> 0.12,    // 12% of wage (capped at ceiling)
        'eps_threshold'             => 15000,   // EPS applicable only on wages up to this amount
        'eps_contribution_rate'     => 0.0833,  // 8.33% employer contribution to EPS
        'effective_from'            => '2024-01-01',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Employee State Insurance (ESI) — ESI Act, 1948
    // ─────────────────────────────────────────────────────────────────────────
    'esi' => [
        'wage_ceiling_normal'       => 21000,   // Normal wage ceiling
        'wage_ceiling_disabled'     => 25000,   // Wage ceiling for persons with disabilities
        'employee_contribution_rate'=> 0.0075,  // 0.75% of wage
        'employer_contribution_rate'=> 0.0325,  // 3.25% of wage
        'effective_from'            => '2024-01-01',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Professional Tax (PT) — State-specific
    // Delhi and Haryana do not levy PT.
    // ─────────────────────────────────────────────────────────────────────────
    'pt' => [
        'delhi' => [
            'status'         => 'not_levied',
            'rate'           => 0,
            'effective_from' => '2024-01-01',
        ],
        'haryana' => [
            'status'         => 'not_levied',
            'rate'           => 0,
            'effective_from' => '2024-01-01',
        ],
        'maharashtra' => [
            'status'         => 'levied',
            'type'           => 'fixed',
            'amount'         => 200,  // Fixed ₹200/month max
            'effective_from' => '2024-01-01',
        ],
        'karnataka' => [
            'status'         => 'levied',
            'type'           => 'fixed',
            'amount'         => 200,  // Fixed ₹200/month max
            'effective_from' => '2024-01-01',
        ],
        'uttar_pradesh' => [
            'status'         => 'levied',
            'type'           => 'slab',
            'slabs'          => [
                ['min' => 0,     'max' => 10000,       'rate' => 0],
                ['min' => 10001, 'max' => 20000,       'rate' => 100],
                ['min' => 20001, 'max' => 30000,       'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'gujarat' => [
            'status'         => 'levied',
            'type'           => 'slab',
            'slabs'          => [
                ['min' => 0,     'max' => 10000,       'rate' => 0],
                ['min' => 10001, 'max' => 20000,       'rate' => 100],
                ['min' => 20001, 'max' => 30000,       'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'west_bengal' => [
            'status'         => 'levied',
            'type'           => 'slab',
            'slabs'          => [
                ['min' => 0,     'max' => 10000,       'rate' => 0],
                ['min' => 10001, 'max' => 20000,       'rate' => 100],
                ['min' => 20001, 'max' => 30000,       'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'jharkhand' => [
            'status'         => 'levied',
            'type'           => 'slab',
            'slabs'          => [
                ['min' => 0,     'max' => 10000,       'rate' => 0],
                ['min' => 10001, 'max' => 20000,       'rate' => 100],
                ['min' => 20001, 'max' => 30000,       'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'goa' => [
            'status'         => 'levied',
            'type'           => 'slab',
            'slabs'          => [
                ['min' => 0,     'max' => 10000,       'rate' => 0],
                ['min' => 10001, 'max' => 20000,       'rate' => 100],
                ['min' => 20001, 'max' => 30000,       'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Tax Deducted at Source (TDS) — Income Tax Act, 1961
    // ─────────────────────────────────────────────────────────────────────────
    'tds' => [
        'old_regime' => [
            'slabs' => [
                ['min' => 0,       'max' => 250000,      'rate' => 0],
                ['min' => 250001,  'max' => 500000,      'rate' => 0.05],
                ['min' => 500001,  'max' => 1000000,     'rate' => 0.20],
                ['min' => 1000001, 'max' => PHP_INT_MAX, 'rate' => 0.30],
            ],
            'surcharge_slabs' => [
                ['min' => 0,        'max' => 5000000,     'rate' => 0],
                ['min' => 5000001,  'max' => 10000000,    'rate' => 0.15],
                ['min' => 10000001, 'max' => PHP_INT_MAX, 'rate' => 0.25],
            ],
            'cess_rate'       => 0.04,
            'effective_from'  => '2024-01-01',
        ],
        'new_regime' => [
            'slabs' => [
                ['min' => 0,       'max' => 300000,      'rate' => 0],
                ['min' => 300001,  'max' => 600000,      'rate' => 0.05],
                ['min' => 600001,  'max' => 900000,      'rate' => 0.10],
                ['min' => 900001,  'max' => 1200000,     'rate' => 0.15],
                ['min' => 1200001, 'max' => 1500000,     'rate' => 0.20],
                ['min' => 1500001, 'max' => PHP_INT_MAX, 'rate' => 0.30],
            ],
            'surcharge_slabs' => [
                ['min' => 0,        'max' => 5000000,     'rate' => 0],
                ['min' => 5000001,  'max' => 10000000,    'rate' => 0.15],
                ['min' => 10000001, 'max' => PHP_INT_MAX, 'rate' => 0.25],
            ],
            'cess_rate'       => 0.04,
            'effective_from'  => '2024-01-01',
        ],
        'standard_deduction'   => 50000,
        'hra_deduction_rate'   => 0.50,
        'lta_exemption_limit'  => 10000,
        'section_80c_limit'    => 150000,
        'section_80d_limit'    => 25000,
        'effective_from'       => '2024-01-01',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Gratuity — Payment of Gratuity Act, 1972
    // ─────────────────────────────────────────────────────────────────────────
    'gratuity' => [
        'wage_ceiling'          => 2000000,  // ₹20 Lakh ceiling
        'calculation_formula'   => 'half_month_salary_per_year',
        'minimum_service_years' => 5,
        'effective_from'        => '2024-01-01',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Bonus — Payment of Bonus Act, 1965
    // ─────────────────────────────────────────────────────────────────────────
    'bonus' => [
        'wage_ceiling'          => 21000,
        'minimum_bonus_rate'    => 0.08,
        'maximum_bonus_rate'    => 0.20,
        'minimum_service_days'  => 30,
        'effective_from'        => '2024-01-01',
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Payroll — Consolidated payroll ceilings, rates, and PT slabs keyed by
    // ISO 3166-2 state code (matching Location->state_code / PayrollInputDTO->stateCode)
    // ─────────────────────────────────────────────────────────────────────────
    'payroll' => [

        // ── Provident Fund (PF) ───────────────────────────────────────────────
        'pf' => [
            'wage_ceiling'              => 15000,   // Monthly wage ceiling for PF calculation
            'employee_rate'             => 0.12,    // 12% of PF wage
            'employer_pf_rate'          => 0.0367,  // 3.67% employer PF (EPF)
            'employer_eps_rate'         => 0.0833,  // 8.33% employer EPS
            'admin_charges_rate'        => 0.005,   // 0.5% admin charges on PF wage
            'edli_rate'                 => 0.005,   // 0.5% EDLI contribution
            'effective_from'            => '2024-01-01',
        ],

        // ── Employee State Insurance (ESI) ────────────────────────────────────
        'esi' => [
            'wage_ceiling_standard'     => 21000,   // Normal wage ceiling
            'wage_ceiling_disabled'     => 25000,   // Wage ceiling for persons with disabilities
            'employee_rate'             => 0.0075,  // 0.75% of gross
            'employer_rate'             => 0.0325,  // 3.25% of gross
            'effective_from'            => '2024-01-01',
        ],

        // ── Gratuity ──────────────────────────────────────────────────────────
        'gratuity' => [
            'ceiling'                   => 2000000, // ₹20 Lakh ceiling (Payment of Gratuity Act, 1972)
            'days_divisor'              => 26,      // Monthly salary / 26 = daily rate
            'years_multiplier'          => 15,      // 15 days per completed year
            'minimum_service_years'     => 5,       // Minimum 5 years for eligibility
            'effective_from'            => '2024-01-01',
        ],

        // ── Bonus ─────────────────────────────────────────────────────────────
        'bonus' => [
            'wage_ceiling'              => 21000,   // Eligible if gross <= 21,000 (Bonus Act, 1965)
            'calculation_ceiling'       => 7000,    // Bonus calculated on min(basic, 7000)
            'min_rate'                  => 0.0833,  // 8.33% minimum bonus
            'max_rate'                  => 0.20,    // 20% maximum bonus
            'minimum_service_days'      => 30,      // Minimum 30 days worked in the year
            'effective_from'            => '2024-01-01',
        ],

        // ── TDS — Income Tax Act, 1961 ────────────────────────────────────────
        'tds' => [
            'standard_deduction'        => 50000,   // Standard deduction for salaried employees
            'section_80c_limit'         => 150000,  // 80C: PF, LIC, ELSS, etc.
            'section_80d_limit'         => 25000,   // 80D: Medical insurance
            'hra_exemption_rate_metro'  => 0.50,    // 50% of basic for metro cities
            'hra_exemption_rate_non_metro' => 0.40, // 40% of basic for non-metro
            'lta_exemption_limit'       => 10000,   // LTA exemption per year
            'cess_rate'                 => 0.04,    // 4% health and education cess
            'old_regime' => [
                'slabs' => [
                    ['min' => 0,       'max' => 250000,      'rate' => 0.00],
                    ['min' => 250001,  'max' => 500000,      'rate' => 0.05],
                    ['min' => 500001,  'max' => 1000000,     'rate' => 0.20],
                    ['min' => 1000001, 'max' => PHP_INT_MAX, 'rate' => 0.30],
                ],
                'surcharge_slabs' => [
                    ['min' => 0,        'max' => 5000000,     'rate' => 0.00],
                    ['min' => 5000001,  'max' => 10000000,    'rate' => 0.10],
                    ['min' => 10000001, 'max' => PHP_INT_MAX, 'rate' => 0.15],
                ],
                'rebate_87a_limit'  => 500000,  // Rebate u/s 87A if taxable income <= 5L
                'rebate_87a_amount' => 12500,   // Max rebate ₹12,500
            ],
            'new_regime' => [
                'slabs' => [
                    ['min' => 0,       'max' => 300000,      'rate' => 0.00],
                    ['min' => 300001,  'max' => 600000,      'rate' => 0.05],
                    ['min' => 600001,  'max' => 900000,      'rate' => 0.10],
                    ['min' => 900001,  'max' => 1200000,     'rate' => 0.15],
                    ['min' => 1200001, 'max' => 1500000,     'rate' => 0.20],
                    ['min' => 1500001, 'max' => PHP_INT_MAX, 'rate' => 0.30],
                ],
                'surcharge_slabs' => [
                    ['min' => 0,        'max' => 5000000,     'rate' => 0.00],
                    ['min' => 5000001,  'max' => 10000000,    'rate' => 0.10],
                    ['min' => 10000001, 'max' => PHP_INT_MAX, 'rate' => 0.15],
                ],
                'rebate_87a_limit'  => 700000,  // Rebate u/s 87A if taxable income <= 7L (new regime)
                'rebate_87a_amount' => 25000,   // Max rebate ₹25,000
            ],
            'effective_from' => '2024-04-01',
        ],

        // ── Professional Tax (PT) — keyed by ISO 3166-2 state code ───────────
        //
        // is_applicable: false = state does not levy PT (DL, HR)
        // slabs: array of ['min', 'max', 'amount'] — monthly gross salary ranges
        // feb_amount: Maharashtra levies ₹300 in February; other months ₹200
        // ─────────────────────────────────────────────────────────────────────
        'pt_slabs' => [

            'MH' => [
                'is_applicable' => true,
                'state_name'    => 'Maharashtra',
                'slabs' => [
                    ['min' => 0,     'max' => 7500,        'amount' => 0,   'feb_amount' => 0],
                    ['min' => 7501,  'max' => 10000,       'amount' => 175, 'feb_amount' => 175],
                    ['min' => 10001, 'max' => PHP_INT_MAX, 'amount' => 200, 'feb_amount' => 300],
                ],
            ],

            'KA' => [
                'is_applicable' => true,
                'state_name'    => 'Karnataka',
                'slabs' => [
                    ['min' => 0,     'max' => 14999,       'amount' => 0],
                    ['min' => 15000, 'max' => 24999,       'amount' => 150],
                    ['min' => 25000, 'max' => PHP_INT_MAX, 'amount' => 200],
                ],
            ],

            'DL' => [
                'is_applicable' => false, // Delhi does NOT levy Professional Tax
                'state_name'    => 'Delhi',
                'slabs'         => [],
            ],

            'HR' => [
                'is_applicable' => false, // Haryana does NOT levy Professional Tax
                'state_name'    => 'Haryana',
                'slabs'         => [],
            ],

            'UP' => [
                'is_applicable' => true,
                'state_name'    => 'Uttar Pradesh',
                'slabs' => [
                    ['min' => 0,     'max' => 12000,       'amount' => 0],
                    ['min' => 12001, 'max' => PHP_INT_MAX, 'amount' => 200],
                ],
            ],

            'GJ' => [
                'is_applicable' => true,
                'state_name'    => 'Gujarat',
                'slabs' => [
                    ['min' => 0,     'max' => 5999,        'amount' => 0],
                    ['min' => 6000,  'max' => 8999,        'amount' => 80],
                    ['min' => 9000,  'max' => 11999,       'amount' => 150],
                    ['min' => 12000, 'max' => PHP_INT_MAX, 'amount' => 200],
                ],
            ],

            'WB' => [
                'is_applicable' => true,
                'state_name'    => 'West Bengal',
                'slabs' => [
                    ['min' => 0,     'max' => 10000,       'amount' => 0],
                    ['min' => 10001, 'max' => 15000,       'amount' => 110],
                    ['min' => 15001, 'max' => 25000,       'amount' => 130],
                    ['min' => 25001, 'max' => 40000,       'amount' => 150],
                    ['min' => 40001, 'max' => PHP_INT_MAX, 'amount' => 200],
                ],
            ],

            'JH' => [
                'is_applicable' => true,
                'state_name'    => 'Jharkhand',
                'slabs' => [
                    ['min' => 0,     'max' => 25000,       'amount' => 0],
                    ['min' => 25001, 'max' => PHP_INT_MAX, 'amount' => 100],
                ],
            ],

            'GA' => [
                'is_applicable' => true,
                'state_name'    => 'Goa',
                'slabs' => [
                    ['min' => 0,     'max' => 15000,       'amount' => 0],
                    ['min' => 15001, 'max' => 25000,       'amount' => 150],
                    ['min' => 25001, 'max' => PHP_INT_MAX, 'amount' => 200],
                ],
            ],

        ], // end pt_slabs

        // ── Payroll Variance Report ───────────────────────────────────────────
        'variance' => [
            'flag_threshold_percent'    => 5.0,    // Flag if net pay changes by > 5%
            'parallel_run_tolerance_inr'=> 1.0,    // Parallel run: zero tolerance above ₹1
        ],

        // ── LWP Proration ─────────────────────────────────────────────────────
        //
        // Formula: (Component / total_working_days) * lwp_days
        // Standard working days per month used as denominator for proration.
        // ─────────────────────────────────────────────────────────────────────
        'lwp' => [
            'standard_working_days' => 26, // Standard denominator for LWP proration
        ],

    ], // end payroll

    // ─────────────────────────────────────────────────────────────────────────
    // Overtime (OT) — Shops & Establishments Acts (state-specific)
    // Keys are 2-letter ISO state codes matching Location->state_code.
    // ─────────────────────────────────────────────────────────────────────────
    'overtime' => [
        'DL' => [
            'state_name'                => 'Delhi',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'HR' => [
            'state_name'                => 'Haryana',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'MH' => [
            'state_name'                => 'Maharashtra',
            'daily_working_hours'       => 9,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 9,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'KA' => [
            'state_name'                => 'Karnataka',
            'daily_working_hours'       => 9,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 9,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'UP' => [
            'state_name'                => 'Uttar Pradesh',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'GJ' => [
            'state_name'                => 'Gujarat',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'WB' => [
            'state_name'                => 'West Bengal',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'JH' => [
            'state_name'                => 'Jharkhand',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
        'GA' => [
            'state_name'                => 'Goa',
            'daily_working_hours'       => 8,
            'weekly_working_hours'      => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'        => 2.0,
            'effective_from'            => '2024-01-01',
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Leave Configuration — State-specific accrual, carry-forward, and encashment
    //
    // Structure per state:
    //   leave_types: per-type rules (accrual_rate_per_month, annual_quota, expiry_days,
    //                carry_forward_limit, encashment_allowed, excess_handling)
    //   effective_from: date from which these rules apply
    //
    // excess_handling: 'lapse' | 'encash' — what happens to days exceeding carry_forward_limit
    // expiry_days: null = no expiry; integer = days after grant before the balance expires
    //              (used for Compensatory Off, which typically expires in 30–60 days)
    // accrual_frequency: 'monthly' | 'quarterly' | 'annual'
    // accrual_rate_per_month: fractional days accrued each month (e.g. 1.75 = 21/year)
    // annual_quota: total days granted at the start of the year (for non-accruing types)
    // ─────────────────────────────────────────────────────────────────────────
    'leave' => [

        // ── Default rules (used when no state-specific override exists) ────────
        'default' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => [   // Earned Leave / Privilege Leave
                    'label'                  => 'Earned Leave',
                    'accrual_frequency'      => 'monthly',
                    'accrual_rate_per_month' => 1.75,   // 21 days per year
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 30,
                    'encashment_allowed'     => true,
                    'excess_handling'        => 'encash',
                    'expiry_days'            => null,
                ],
                'CL' => [   // Casual Leave
                    'label'                  => 'Casual Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 12,
                    'carry_forward_limit'    => 0,      // CL does not carry forward
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'SL' => [   // Sick Leave
                    'label'                  => 'Sick Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 12,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'ML' => [   // Maternity Leave — Maternity Benefit Act, 1961
                    'label'                  => 'Maternity Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 182,    // 26 weeks for first 2 children
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'PL' => [   // Paternity Leave
                    'label'                  => 'Paternity Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 15,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'BL' => [   // Bereavement Leave
                    'label'                  => 'Bereavement Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 5,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'CO' => [   // Compensatory Off — expires in 45 days
                    'label'                  => 'Compensatory Off',
                    'accrual_frequency'      => 'on_grant',  // Granted per OT day worked
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => 45,          // Expires 45 days after grant
                ],
                'UL' => [   // Unpaid Leave
                    'label'                  => 'Unpaid Leave',
                    'accrual_frequency'      => 'none',      // No accrual; applied on demand
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
            ],
        ],

        // ── Karnataka — KA ────────────────────────────────────────────────────
        // Karnataka Shops & Commercial Establishments Act, 1961
        // EL: 1 day per 20 working days = ~1.5 days/month (18/year)
        'KA' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => [
                    'label'                  => 'Earned Leave',
                    'accrual_frequency'      => 'monthly',
                    'accrual_rate_per_month' => 1.5,    // 18 days per year (1 per 20 working days)
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 30,
                    'encashment_allowed'     => true,
                    'excess_handling'        => 'encash',
                    'expiry_days'            => null,
                ],
                'CL' => [
                    'label'                  => 'Casual Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 12,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'SL' => [
                    'label'                  => 'Sick Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 12,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'ML' => [
                    'label'                  => 'Maternity Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 182,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'PL' => [
                    'label'                  => 'Paternity Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 15,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'BL' => [
                    'label'                  => 'Bereavement Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 5,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'CO' => [
                    'label'                  => 'Compensatory Off',
                    'accrual_frequency'      => 'on_grant',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => 45,
                ],
                'UL' => [
                    'label'                  => 'Unpaid Leave',
                    'accrual_frequency'      => 'none',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
            ],
        ],

        // ── Maharashtra — MH ──────────────────────────────────────────────────
        // Maharashtra Shops & Establishments Act, 2017
        // EL: 1 day per 20 working days = ~1.5 days/month (18/year)
        'MH' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => [
                    'label'                  => 'Earned Leave',
                    'accrual_frequency'      => 'monthly',
                    'accrual_rate_per_month' => 1.5,
                    'annual_quota'           => null,
                    'carry_forward_limit'    => 45,
                    'encashment_allowed'     => true,
                    'excess_handling'        => 'encash',
                    'expiry_days'            => null,
                ],
                'CL' => [
                    'label'                  => 'Casual Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 8,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'SL' => [
                    'label'                  => 'Sick Leave',
                    'accrual_frequency'      => 'annual',
                    'accrual_rate_per_month' => null,
                    'annual_quota'           => 7,
                    'carry_forward_limit'    => 0,
                    'encashment_allowed'     => false,
                    'excess_handling'        => 'lapse',
                    'expiry_days'            => null,
                ],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── Delhi — DL ────────────────────────────────────────────────────────
        'DL' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── Haryana — HR ──────────────────────────────────────────────────────
        'HR' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── Uttar Pradesh — UP ────────────────────────────────────────────────
        'UP' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 14, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── Gujarat — GJ ──────────────────────────────────────────────────────
        'GJ' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── West Bengal — WB ──────────────────────────────────────────────────
        'WB' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── Jharkhand — JH ────────────────────────────────────────────────────
        'JH' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],

        // ── Goa — GA ──────────────────────────────────────────────────────────
        'GA' => [
            'effective_from' => '2024-01-01',
            'leave_types' => [
                'EL' => ['label' => 'Earned Leave', 'accrual_frequency' => 'monthly', 'accrual_rate_per_month' => 1.75, 'annual_quota' => null, 'carry_forward_limit' => 30, 'encashment_allowed' => true, 'excess_handling' => 'encash', 'expiry_days' => null],
                'CL' => ['label' => 'Casual Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'SL' => ['label' => 'Sick Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 12, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'ML' => ['label' => 'Maternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 182, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'PL' => ['label' => 'Paternity Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 15, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'BL' => ['label' => 'Bereavement Leave', 'accrual_frequency' => 'annual', 'accrual_rate_per_month' => null, 'annual_quota' => 5, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
                'CO' => ['label' => 'Compensatory Off', 'accrual_frequency' => 'on_grant', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => 45],
                'UL' => ['label' => 'Unpaid Leave', 'accrual_frequency' => 'none', 'accrual_rate_per_month' => null, 'annual_quota' => null, 'carry_forward_limit' => 0, 'encashment_allowed' => false, 'excess_handling' => 'lapse', 'expiry_days' => null],
            ],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────────
    // Statutory Compliance Thresholds
    // ─────────────────────────────────────────────────────────────────────────
    'compliance' => [
        'pf_registration_threshold'    => 20,
        'esi_registration_threshold'   => 10,
        'shops_act_threshold'          => 10,
    ],
];
