/**
 * NinjaOS HRMS — Leave Service (Mobile)
 */

import api from './api';

export interface LeaveBalance {
  leave_type: string;
  opening: number;
  accrued: number;
  availed: number;
  pending: number;
  closing: number;
}

export interface LeaveApplicationPayload {
  employee_id: number;
  leave_type: string;
  from_date: string;
  to_date: string;
  reason: string;
  is_half_day?: boolean;
  half_day_session?: 'first_half' | 'second_half';
}

// ── Fetch leave balances ──────────────────────────────────────────────────────

export async function fetchLeaveBalances(employeeId: number): Promise<LeaveBalance[]> {
  const { data } = await api.get(`/leave/balances?employee_id=${employeeId}`);
  return data.data ?? [];
}

// ── Submit a leave application ────────────────────────────────────────────────

export async function applyLeave(payload: LeaveApplicationPayload): Promise<{ success: boolean; message: string }> {
  const { data } = await api.post('/leave/apply', payload);
  return data;
}

// ── Fetch leave history ───────────────────────────────────────────────────────

export async function fetchLeaveHistory(employeeId: number): Promise<any[]> {
  const { data } = await api.get(`/leave?employee_id=${employeeId}`);
  return data.data ?? [];
}
