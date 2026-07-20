<?php

/**
 * NexusOS Application Configuration
 * 
 * App-specific settings for the multi-location HRMS platform.
 */

return [
    /**
     * Supported States
     * All 9 states where NexusOS operates
     */
    'states' => [
        'delhi' => 'Delhi',
        'haryana' => 'Haryana',
        'maharashtra' => 'Maharashtra',
        'karnataka' => 'Karnataka',
        'uttar_pradesh' => 'Uttar Pradesh',
        'gujarat' => 'Gujarat',
        'west_bengal' => 'West Bengal',
        'jharkhand' => 'Jharkhand',
        'goa' => 'Goa',
    ],

    /**
     * User Roles
     * RBAC roles available in the system
     */
    'roles' => [
        'super_admin' => 'Super Admin',
        'central_hr' => 'Central HR',
        'location_hr' => 'Location HR',
        'manager' => 'Manager',
        'employee' => 'Employee',
        'payroll_admin' => 'Payroll Admin',
        'auditor' => 'Auditor',
        'recruiter' => 'Recruiter',
    ],

    /**
     * Employee Lifecycle Statuses
     */
    'employee_statuses' => [
        'onboarding' => 'Onboarding',
        'probation' => 'Probation',
        'confirmed' => 'Confirmed',
        'transferred' => 'Transferred',
        'on_leave' => 'On Leave',
        'suspended' => 'Suspended',
        'exit' => 'Exit',
    ],

    /**
     * Leave Types
     */
    'leave_types' => [
        'casual_leave' => 'Casual Leave',
        'sick_leave' => 'Sick Leave',
        'earned_leave' => 'Earned Leave',
        'privilege_leave' => 'Privilege Leave',
        'maternity_leave' => 'Maternity Leave',
        'paternity_leave' => 'Paternity Leave',
        'bereavement_leave' => 'Bereavement Leave',
        'unpaid_leave' => 'Unpaid Leave',
    ],

    /**
     * Shift Types
     */
    'shift_types' => [
        'fixed' => 'Fixed Shift',
        'rotational' => 'Rotational Shift',
        'night' => 'Night Shift',
        'flexible' => 'Flexible Shift',
    ],

    /**
     * Attendance Modes
     */
    'attendance_modes' => [
        'biometric' => 'Biometric',
        'mobile_gps' => 'Mobile GPS',
        'manual' => 'Manual Entry',
        'ip_whitelist' => 'IP Whitelist',
    ],

    /**
     * Leave Application Statuses
     */
    'leave_statuses' => [
        'draft' => 'Draft',
        'pending_approval' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    /**
     * Payroll Processing Statuses
     */
    'payroll_statuses' => [
        'draft' => 'Draft',
        'locked' => 'Locked',
        'processed' => 'Processed',
        'approved' => 'Approved',
        'finalized' => 'Finalized',
        'paid' => 'Paid',
    ],

    /**
     * ATS Pipeline Stages
     */
    'ats_stages' => [
        'applied' => 'Applied',
        'screened' => 'Screened',
        'interview_1' => 'Interview Round 1',
        'interview_2' => 'Interview Round 2',
        'offer' => 'Offer',
        'hired' => 'Hired',
        'rejected' => 'Rejected',
        'on_hold' => 'On Hold',
    ],

    /**
     * Geo-fencing Configuration
     * For mobile attendance
     */
    'geofencing' => [
        'default_radius_meters' => 100,  // Default 100m radius
        'max_radius_meters' => 500,  // Maximum 500m radius
    ],

    /**
     * Biometric Integration
     */
    'biometric' => [
        'sync_interval_minutes' => 5,  // Sync every 5 minutes
        'retry_attempts' => 3,
        'retry_delay_seconds' => 30,
    ],

    /**
     * Notification Configuration
     */
    'notifications' => [
        'email_enabled' => true,
        'sms_enabled' => true,
        'whatsapp_enabled' => true,
        'push_enabled' => true,
    ],

    /**
     * Audit & Compliance
     */
    'audit' => [
        'log_all_mutations' => true,
        'log_cross_location_access' => true,
        'retention_days' => 2555,  // 7 years for compliance
    ],

    /**
     * Performance Budgets
     */
    'performance' => [
        'p95_page_load_ms' => 800,
        'p99_api_response_ms' => 500,
        'payroll_run_timeout_seconds' => 240,  // 4 minutes for 5000 employees
        'mobile_cold_start_seconds' => 2,
    ],

    /**
     * Security Settings
     */
    'security' => [
        'mfa_required' => false,  // Can be enabled per role
        'password_expiry_days' => 90,
        'session_timeout_minutes' => 30,
        'max_login_attempts' => 5,
        'lockout_duration_minutes' => 15,
    ],

    /**
     * Data Retention Policies
     */
    'retention' => [
        'audit_logs_years' => 7,
        'payroll_records_years' => 10,
        'employee_records_years' => 7,  // After exit
    ],
];
