/**
 * NinjaOS HRMS — Attendance Service (Mobile)
 *
 * Handles:
 *  - Fetching the current day's attendance record.
 *  - Submitting a punch IN/OUT with optional GPS coordinates.
 *
 * Geo-fencing: The API enforces coordinate requirements server-side.
 * The mobile app requests location permission and passes coordinates
 * when available, providing a better UX than relying solely on server errors.
 */

import * as Location from 'expo-location';
import api from './api';

export interface PunchPayload {
  employee_id: number;
  punch_type: 'IN' | 'OUT';
  latitude?: number;
  longitude?: number;
  timestamp?: string;
}

export interface AttendanceRecord {
  id: number;
  attendance_date: string;
  punch_in: string | null;
  punch_out: string | null;
  status: string;
  working_hours: number | null;
}

// ── Request GPS permission and get current coordinates ────────────────────────

export async function getCurrentCoordinates(): Promise<{ latitude: number; longitude: number } | null> {
  const { status } = await Location.requestForegroundPermissionsAsync();
  if (status !== 'granted') {
    return null;
  }
  const location = await Location.getCurrentPositionAsync({
    accuracy: Location.Accuracy.High,
  });
  return {
    latitude: location.coords.latitude,
    longitude: location.coords.longitude,
  };
}

// ── Submit a punch ────────────────────────────────────────────────────────────

export async function submitPunch(payload: PunchPayload): Promise<{ success: boolean; message: string }> {
  const { data } = await api.post('/attendance/punch', payload);
  return data;
}

// ── Fetch today's attendance ──────────────────────────────────────────────────

export async function fetchTodayAttendance(employeeId: number): Promise<AttendanceRecord | null> {
  const today = new Date().toISOString().split('T')[0];
  const { data } = await api.get(`/attendance?employee_id=${employeeId}&date=${today}`);
  return data.data?.[0] ?? null;
}
