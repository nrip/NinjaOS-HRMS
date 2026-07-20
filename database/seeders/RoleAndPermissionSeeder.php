<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()['cache']
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        // Define all permissions
        $permissions = [
            // Employee Management
            'view_employees',
            'create_employee',
            'edit_employee',
            'delete_employee',
            'export_employees',

            // Attendance
            'view_attendance',
            'create_attendance',
            'edit_attendance',
            'approve_attendance_regularization',

            // Leave Management
            'view_leave',
            'create_leave',
            'approve_leave',
            'reject_leave',
            'view_leave_balance',

            // Payroll
            'view_payroll',
            'create_payroll',
            'edit_payroll',
            'approve_payroll',
            'finalize_payroll',
            'generate_payslip',
            'generate_bank_file',

            // ATS
            'view_requisitions',
            'create_requisition',
            'approve_requisition',
            'view_candidates',
            'manage_pipeline',

            // System Configuration
            'manage_locations',
            'manage_departments',
            'manage_designations',
            'manage_shifts',
            'manage_holidays',
            'manage_statutory_config',
            'manage_users',
            'manage_roles',

            // Audit & Reporting
            'view_audit_logs',
            'view_reports',
            'export_reports',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles with their permissions
        $roles = [
            'super_admin' => $permissions, // All permissions

            'central_hr' => [
                'view_employees',
                'create_employee',
                'edit_employee',
                'export_employees',
                'view_attendance',
                'view_leave',
                'view_payroll',
                'create_payroll',
                'edit_payroll',
                'approve_payroll',
                'finalize_payroll',
                'generate_payslip',
                'generate_bank_file',
                'view_requisitions',
                'create_requisition',
                'approve_requisition',
                'view_candidates',
                'manage_locations',
                'manage_departments',
                'manage_designations',
                'manage_shifts',
                'manage_holidays',
                'manage_statutory_config',
                'view_audit_logs',
                'view_reports',
                'export_reports',
            ],

            'location_hr' => [
                'view_employees',
                'create_employee',
                'edit_employee',
                'export_employees',
                'view_attendance',
                'create_attendance',
                'edit_attendance',
                'approve_attendance_regularization',
                'view_leave',
                'create_leave',
                'approve_leave',
                'reject_leave',
                'view_leave_balance',
                'view_payroll',
                'create_payroll',
                'edit_payroll',
                'view_requisitions',
                'create_requisition',
                'view_candidates',
                'manage_pipeline',
                'manage_holidays',
                'view_reports',
                'export_reports',
            ],

            'manager' => [
                'view_employees',
                'view_attendance',
                'approve_attendance_regularization',
                'view_leave',
                'approve_leave',
                'reject_leave',
                'view_leave_balance',
                'view_payroll',
                'view_requisitions',
                'view_candidates',
                'manage_pipeline',
                'view_reports',
            ],

            'employee' => [
                'view_leave_balance',
                'create_leave',
                'view_attendance',
                'view_payroll',
                'view_reports',
            ],

            'payroll_admin' => [
                'view_employees',
                'view_attendance',
                'view_leave',
                'view_payroll',
                'create_payroll',
                'edit_payroll',
                'approve_payroll',
                'finalize_payroll',
                'generate_payslip',
                'generate_bank_file',
                'manage_statutory_config',
                'view_audit_logs',
                'view_reports',
                'export_reports',
            ],

            'auditor' => [
                'view_employees',
                'view_attendance',
                'view_leave',
                'view_payroll',
                'view_audit_logs',
                'view_reports',
                'export_reports',
            ],

            'recruiter' => [
                'view_requisitions',
                'create_requisition',
                'view_candidates',
                'manage_pipeline',
                'view_reports',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
