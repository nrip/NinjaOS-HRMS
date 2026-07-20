/**
 * NinjaOS HRMS — Attendance Screen (Mobile)
 *
 * Features:
 *  - Displays today's punch IN/OUT times and working hours.
 *  - Requests GPS permission and passes coordinates to the punch API.
 *  - Shows a mock geo-fencing status indicator (green = within radius, red = outside).
 *  - Handles the server's 422 response when coordinates are missing for a
 *    geo-fenced location, displaying a user-friendly error.
 */

import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  Alert,
  ScrollView,
  RefreshControl,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../store/authStore';
import {
  submitPunch,
  fetchTodayAttendance,
  getCurrentCoordinates,
  AttendanceRecord,
} from '../services/attendanceService';

export default function AttendanceScreen() {
  const { user } = useAuthStore();
  const [attendance, setAttendance] = useState<AttendanceRecord | null>(null);
  const [loading, setLoading] = useState(true);
  const [punching, setPunching] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [geoStatus, setGeoStatus] = useState<'checking' | 'granted' | 'denied'>('checking');

  const employeeId = (user as any)?.employee_id ?? 0;

  const loadAttendance = useCallback(async () => {
    try {
      const record = await fetchTodayAttendance(employeeId);
      setAttendance(record);
    } catch {
      // Silently fail — user will see empty state.
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [employeeId]);

  useEffect(() => {
    loadAttendance();
    // Check geo permission on mount for the UI indicator.
    getCurrentCoordinates().then((coords) => {
      setGeoStatus(coords ? 'granted' : 'denied');
    });
  }, [loadAttendance]);

  const handlePunch = async (punchType: 'IN' | 'OUT') => {
    setPunching(true);
    try {
      const coords = await getCurrentCoordinates();
      if (coords) {
        setGeoStatus('granted');
      } else {
        setGeoStatus('denied');
      }

      const result = await submitPunch({
        employee_id: employeeId,
        punch_type: punchType,
        latitude: coords?.latitude,
        longitude: coords?.longitude,
      });

      if (result.success) {
        Alert.alert('Success', result.message);
        await loadAttendance();
      } else {
        Alert.alert('Error', result.message);
      }
    } catch (err: any) {
      const msg =
        err?.response?.data?.message ??
        err?.response?.data?.error ??
        'Punch failed. Please try again.';
      Alert.alert('Punch Failed', msg);
    } finally {
      setPunching(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color="#e94560" />
      </View>
    );
  }

  const geoColor = geoStatus === 'granted' ? '#4caf50' : geoStatus === 'denied' ? '#e94560' : '#888';
  const geoLabel = geoStatus === 'granted' ? 'GPS Active' : geoStatus === 'denied' ? 'GPS Unavailable' : 'Checking GPS...';

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); loadAttendance(); }} />}
    >
      {/* Geo-fencing status indicator */}
      <View style={[styles.geoBar, { backgroundColor: geoColor + '22' }]}>
        <Ionicons name="location" size={16} color={geoColor} />
        <Text style={[styles.geoText, { color: geoColor }]}>{geoLabel}</Text>
      </View>

      {/* Today's attendance card */}
      <View style={styles.card}>
        <Text style={styles.cardTitle}>Today — {new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' })}</Text>

        <View style={styles.row}>
          <View style={styles.timeBlock}>
            <Text style={styles.timeLabel}>Punch In</Text>
            <Text style={styles.timeValue}>{attendance?.punch_in ? new Date(attendance.punch_in).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }) : '--:--'}</Text>
          </View>
          <View style={styles.divider} />
          <View style={styles.timeBlock}>
            <Text style={styles.timeLabel}>Punch Out</Text>
            <Text style={styles.timeValue}>{attendance?.punch_out ? new Date(attendance.punch_out).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' }) : '--:--'}</Text>
          </View>
          <View style={styles.divider} />
          <View style={styles.timeBlock}>
            <Text style={styles.timeLabel}>Hours</Text>
            <Text style={styles.timeValue}>{attendance?.working_hours ? `${attendance.working_hours.toFixed(1)}h` : '--'}</Text>
          </View>
        </View>

        <View style={[styles.statusBadge, { backgroundColor: attendance?.status === 'present' ? '#4caf5022' : '#e9456022' }]}>
          <Text style={[styles.statusText, { color: attendance?.status === 'present' ? '#4caf50' : '#e94560' }]}>
            {attendance?.status?.toUpperCase() ?? 'NOT MARKED'}
          </Text>
        </View>
      </View>

      {/* Punch buttons */}
      <View style={styles.punchRow}>
        <TouchableOpacity
          style={[styles.punchButton, styles.punchIn, (punching || !!attendance?.punch_in) && styles.disabled]}
          onPress={() => handlePunch('IN')}
          disabled={punching || !!attendance?.punch_in}
        >
          {punching ? <ActivityIndicator color="#fff" /> : <Text style={styles.punchButtonText}>Punch IN</Text>}
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.punchButton, styles.punchOut, (punching || !attendance?.punch_in || !!attendance?.punch_out) && styles.disabled]}
          onPress={() => handlePunch('OUT')}
          disabled={punching || !attendance?.punch_in || !!attendance?.punch_out}
        >
          {punching ? <ActivityIndicator color="#fff" /> : <Text style={styles.punchButtonText}>Punch OUT</Text>}
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0f3460', padding: 16 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0f3460' },
  geoBar: { flexDirection: 'row', alignItems: 'center', padding: 10, borderRadius: 8, marginBottom: 16, gap: 6 },
  geoText: { fontSize: 13, fontWeight: '500' },
  card: { backgroundColor: '#16213e', borderRadius: 16, padding: 20, marginBottom: 24 },
  cardTitle: { color: '#aaa', fontSize: 14, marginBottom: 16 },
  row: { flexDirection: 'row', justifyContent: 'space-around', marginBottom: 16 },
  timeBlock: { alignItems: 'center', flex: 1 },
  timeLabel: { color: '#888', fontSize: 12, marginBottom: 4 },
  timeValue: { color: '#fff', fontSize: 20, fontWeight: '700' },
  divider: { width: 1, backgroundColor: '#333', marginHorizontal: 8 },
  statusBadge: { alignSelf: 'center', paddingHorizontal: 16, paddingVertical: 6, borderRadius: 20 },
  statusText: { fontSize: 12, fontWeight: '700', letterSpacing: 1 },
  punchRow: { flexDirection: 'row', gap: 12 },
  punchButton: { flex: 1, paddingVertical: 16, borderRadius: 12, alignItems: 'center' },
  punchIn: { backgroundColor: '#4caf50' },
  punchOut: { backgroundColor: '#e94560' },
  disabled: { opacity: 0.4 },
  punchButtonText: { color: '#fff', fontSize: 16, fontWeight: '700' },
});
