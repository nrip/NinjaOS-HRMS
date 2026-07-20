<?php

/**
 * NexusOS Statutory Configuration
 * 
 * All statutory values (PF ceiling, ESI ceiling, PT slabs, etc.) are defined here.
 * These values are never hardcoded in business logic and are always fetched from this config.
 * 
 * Effective dates allow for rule versioning - historical payroll runs remain reproducible.
 */

return [
    /**
     * Provident Fund (PF) Configuration
     * Applicable to employees with monthly salary >= ₹15,000
     */
    'pf' => [
        'wage_ceiling' => 15000,  // Monthly wage ceiling for PF calculation
        'employee_contribution_rate' => 0.12,  // 12% of wage (capped at ceiling)
        'employer_contribution_rate' => 0.12,  // 12% of wage (capped at ceiling)
        'eps_threshold' => 15000,  // EPS applicable only on wages up to this amount
        'eps_contribution_rate' => 0.0833,  // 8.33% employer contribution to EPS
        'effective_from' => '2024-01-01',
    ],

    /**
     * Employee State Insurance (ESI) Configuration
     * Applicable to employees with gross salary <= ₹21,000 (₹25,000 for persons with disabilities)
     */
    'esi' => [
        'wage_ceiling_normal' => 21000,  // Normal wage ceiling
        'wage_ceiling_disabled' => 25000,  // Wage ceiling for persons with disabilities
        'employee_contribution_rate' => 0.0075,  // 0.75% of wage
        'employer_contribution_rate' => 0.0325,  // 3.25% of wage
        'effective_from' => '2024-01-01',
    ],

    /**
     * Professional Tax (PT) Configuration
     * State-specific slab-based deduction
     * 
     * Each state has its own PT rules. Some states have no PT (Delhi, Haryana).
     * Others use monthly slabs based on gross salary.
     */
    'pt' => [
        'delhi' => [
            'status' => 'not_levied',
            'rate' => 0,
            'effective_from' => '2024-01-01',
        ],
        'haryana' => [
            'status' => 'not_levied',
            'rate' => 0,
            'effective_from' => '2024-01-01',
        ],
        'maharashtra' => [
            'status' => 'levied',
            'type' => 'fixed',
            'amount' => 200,  // Fixed ₹200/month max
            'effective_from' => '2024-01-01',
        ],
        'karnataka' => [
            'status' => 'levied',
            'type' => 'fixed',
            'amount' => 200,  // Fixed ₹200/month max
            'effective_from' => '2024-01-01',
        ],
        'uttar_pradesh' => [
            'status' => 'levied',
            'type' => 'slab',
            'slabs' => [
                ['min' => 0, 'max' => 10000, 'rate' => 0],
                ['min' => 10001, 'max' => 20000, 'rate' => 100],
                ['min' => 20001, 'max' => 30000, 'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'gujarat' => [
            'status' => 'levied',
            'type' => 'slab',
            'slabs' => [
                ['min' => 0, 'max' => 10000, 'rate' => 0],
                ['min' => 10001, 'max' => 20000, 'rate' => 100],
                ['min' => 20001, 'max' => 30000, 'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'west_bengal' => [
            'status' => 'levied',
            'type' => 'slab',
            'slabs' => [
                ['min' => 0, 'max' => 10000, 'rate' => 0],
                ['min' => 10001, 'max' => 20000, 'rate' => 100],
                ['min' => 20001, 'max' => 30000, 'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'jharkhand' => [
            'status' => 'levied',
            'type' => 'slab',
            'slabs' => [
                ['min' => 0, 'max' => 10000, 'rate' => 0],
                ['min' => 10001, 'max' => 20000, 'rate' => 100],
                ['min' => 20001, 'max' => 30000, 'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
        'goa' => [
            'status' => 'levied',
            'type' => 'slab',
            'slabs' => [
                ['min' => 0, 'max' => 10000, 'rate' => 0],
                ['min' => 10001, 'max' => 20000, 'rate' => 100],
                ['min' => 20001, 'max' => 30000, 'rate' => 150],
                ['min' => 30001, 'max' => PHP_INT_MAX, 'rate' => 200],
            ],
            'effective_from' => '2024-01-01',
        ],
    ],

    /**
     * Tax Deducted at Source (TDS) Configuration
     * Income tax calculation for old and new regimes
     */
    'tds' => [
        'old_regime' => [
            'slabs' => [
                ['min' => 0, 'max' => 250000, 'rate' => 0],
                ['min' => 250001, 'max' => 500000, 'rate' => 0.05],
                ['min' => 500001, 'max' => 1000000, 'rate' => 0.20],
                ['min' => 1000001, 'max' => PHP_INT_MAX, 'rate' => 0.30],
            ],
            'surcharge_slabs' => [
                ['min' => 0, 'max' => 5000000, 'rate' => 0],
                ['min' => 5000001, 'max' => 10000000, 'rate' => 0.15],
                ['min' => 10000001, 'max' => PHP_INT_MAX, 'rate' => 0.25],
            ],
            'cess_rate' => 0.04,  // 4% cess on tax + surcharge
            'effective_from' => '2024-01-01',
        ],
        'new_regime' => [
            'slabs' => [
                ['min' => 0, 'max' => 300000, 'rate' => 0],
                ['min' => 300001, 'max' => 600000, 'rate' => 0.05],
                ['min' => 600001, 'max' => 900000, 'rate' => 0.10],
                ['min' => 900001, 'max' => 1200000, 'rate' => 0.15],
                ['min' => 1200001, 'max' => 1500000, 'rate' => 0.20],
                ['min' => 1500001, 'max' => PHP_INT_MAX, 'rate' => 0.30],
            ],
            'surcharge_slabs' => [
                ['min' => 0, 'max' => 5000000, 'rate' => 0],
                ['min' => 5000001, 'max' => 10000000, 'rate' => 0.15],
                ['min' => 10000001, 'max' => PHP_INT_MAX, 'rate' => 0.25],
            ],
            'cess_rate' => 0.04,  // 4% cess on tax + surcharge
            'effective_from' => '2024-01-01',
        ],
        'standard_deduction' => 50000,  // Standard deduction available in new regime
        'hra_deduction_rate' => 0.50,  // 50% of salary or actual rent paid (whichever is lower)
        'lta_exemption_limit' => 10000,  // Per journey, twice in 4 calendar years
        'section_80c_limit' => 150000,  // Life insurance, PPF, ELSS, etc.
        'section_80d_limit' => 25000,  // Health insurance (self + family)
        'effective_from' => '2024-01-01',
    ],

    /**
     * Gratuity Configuration
     * Calculated as per Payment of Gratuity Act, 1972
     */
    'gratuity' => [
        'wage_ceiling' => 2000000,  // ₹20 Lakh (for calculation purposes)
        'calculation_formula' => 'half_month_salary_per_year',  // 0.5 * salary * years of service
        'minimum_service_years' => 5,  // Gratuity payable after 5 years of continuous service
        'effective_from' => '2024-01-01',
    ],

    /**
     * Bonus Configuration
     * Calculated as per Payment of Bonus Act, 1965
     */
    'bonus' => [
        'wage_ceiling' => 21000,  // Bonus calculated on wages up to ₹21,000/month
        'minimum_bonus_rate' => 0.08,  // 8% of wages (statutory minimum)
        'maximum_bonus_rate' => 0.20,  // 20% of wages (maximum payable)
        'minimum_service_days' => 30,  // Minimum 30 days of service to be eligible
        'effective_from' => '2024-01-01',
    ],

    /*
     * Overtime (OT) Configuration
     * Keys are 2-letter ISO state codes matching Location->state_code and employee_code prefix.
     * All OT rules are read from here — NEVER hardcode multipliers in business logic.
     *
     * Statutory references:
     *   MH: Maharashtra Shops & Establishments Act — OT after 9 hrs, 2x rate
     *   DL: Delhi Shops & Establishments Act — OT after 8 hrs, 2x rate
     *   HR: Haryana Shops & Establishments Act — OT after 8 hrs, 2x rate
     *   KA: Karnataka Shops & Establishments Act — OT after 9 hrs, 2x rate
     *   UP: UP Shops & Establishments Act — OT after 8 hrs, 2x rate
     *   GJ: Gujarat Shops & Establishments Act — OT after 8 hrs, 2x rate
     *   WB: West Bengal Shops & Establishments Act — OT after 8 hrs, 2x rate
     *   JH: Jharkhand Shops & Establishments Act — OT after 8 hrs, 2x rate
     *   GA: Goa Shops & Establishments Act — OT after 8 hrs, 2x rate
     */
    'overtime' => [
        'DL' => [
            'state_name'              => 'Delhi',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,   // 2x hourly rate — read from here, never hardcode
            'effective_from'          => '2024-01-01',
        ],
        'HR' => [
            'state_name'              => 'Haryana',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'MH' => [
            'state_name'              => 'Maharashtra',
            'daily_working_hours'     => 9,     // Standard day is 9 hrs in MH
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 9,   // OT kicks in after 9 hrs in MH
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'KA' => [
            'state_name'              => 'Karnataka',
            'daily_working_hours'     => 9,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 9,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'UP' => [
            'state_name'              => 'Uttar Pradesh',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'GJ' => [
            'state_name'              => 'Gujarat',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'WB' => [
            'state_name'              => 'West Bengal',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'JH' => [
            'state_name'              => 'Jharkhand',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
        'GA' => [
            'state_name'              => 'Goa',
            'daily_working_hours'     => 8,
            'weekly_working_hours'    => 48,
            'ot_applicable_after_hours' => 8,
            'ot_rate_multiplier'      => 2.0,
            'effective_from'          => '2024-01-01',
        ],
    ],

    /**
     * Leave Configuration
     * State-specific accrual and encashment rules
     */
    'leave' => [
        'earned_leave_accrual_rate' => 1.75,  // Days per month (21 days per year)
        'earned_leave_max_carry_forward' => 30,  // Maximum days that can be carried forward
        'earned_leave_encashment_allowed' => true,
        'casual_leave_per_year' => 12,
        'sick_leave_per_year' => 12,
        'effective_from' => '2024-01-01',
    ],

    /**
     * Statutory Compliance Thresholds
     */
    'compliance' => [
        'pf_registration_threshold' => 20,  // Register for PF if >= 20 employees
        'esi_registration_threshold' => 10,  // Register for ESI if >= 10 employees
        'shops_act_threshold' => 10,  // Shops & Establishments Act applies if >= 10 employees
    ],
];
