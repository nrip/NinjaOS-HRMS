/**
 * NinjaOS HRMS — Payslip Service (Mobile)
 *
 * The API returns a signed URL (not base64 data) to prevent payload bloat.
 * The mobile app uses expo-web-browser to open the signed URL, which
 * renders the PDF in the device's native browser/PDF viewer.
 */

import * as WebBrowser from 'expo-web-browser';
import api from './api';

export interface PayslipSummary {
  payroll_id: string;
  payroll_month: number;
  payroll_year: number;
  gross_pay: number;
  net_pay: number;
  status: string;
  payslip_url: string; // Sanctum-signed temporary URL
}

// ── Fetch list of payslips ────────────────────────────────────────────────────

export async function fetchPayslips(employeeId: number): Promise<PayslipSummary[]> {
  const { data } = await api.get(`/payroll/payslips?employee_id=${employeeId}`);
  return data.data ?? [];
}

// ── Open payslip PDF in device browser ───────────────────────────────────────

export async function openPayslipPdf(signedUrl: string): Promise<void> {
  await WebBrowser.openBrowserAsync(signedUrl);
}
