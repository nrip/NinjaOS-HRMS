/**
 * NinjaOS HRMS — Leave Screen (Mobile)
 *
 * Features:
 *  - Real-time leave balance display (Opening + Accrued - Availed = Closing).
 *  - Leave application form with date pickers and half-day support.
 *  - Leave history list.
 */

import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Alert,
  ActivityIndicator,
  FlatList,
} from 'react-native';
import { useAuthStore } from '../store/authStore';
import { fetchLeaveBalances, applyLeave, fetchLeaveHistory, LeaveBalance } from '../services/leaveService';

const LEAVE_TYPES = ['CL', 'SL', 'EL', 'ML', 'PL', 'BL', 'CO', 'UL'];

export default function LeaveScreen() {
  const { user } = useAuthStore();
  const employeeId = (user as any)?.employee_id ?? 0;

  const [balances, setBalances] = useState<LeaveBalance[]>([]);
  const [history, setHistory] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  // Form state
  const [leaveType, setLeaveType] = useState('CL');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [reason, setReason] = useState('');
  const [isHalfDay, setIsHalfDay] = useState(false);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    setLoading(true);
    try {
      const [bal, hist] = await Promise.all([
        fetchLeaveBalances(employeeId),
        fetchLeaveHistory(employeeId),
      ]);
      setBalances(bal);
      setHistory(hist);
    } catch {
      Alert.alert('Error', 'Failed to load leave data.');
    } finally {
      setLoading(false);
    }
  };

  const handleApply = async () => {
    if (!fromDate || !toDate || !reason.trim()) {
      Alert.alert('Validation', 'Please fill all required fields.');
      return;
    }
    setSubmitting(true);
    try {
      const result = await applyLeave({
        employee_id: employeeId,
        leave_type: leaveType,
        from_date: fromDate,
        to_date: toDate,
        reason,
        is_half_day: isHalfDay,
      });
      Alert.alert('Success', result.message);
      setShowForm(false);
      setFromDate(''); setToDate(''); setReason(''); setIsHalfDay(false);
      await loadData();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.message ?? 'Application failed.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <View style={styles.centered}><ActivityIndicator size="large" color="#e94560" /></View>;
  }

  return (
    <ScrollView style={styles.container}>
      {/* Balance cards */}
      <Text style={styles.sectionTitle}>Leave Balances</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.balanceScroll}>
        {balances.map((b) => (
          <View key={b.leave_type} style={styles.balanceCard}>
            <Text style={styles.balanceType}>{b.leave_type}</Text>
            <Text style={styles.balanceClosing}>{b.closing}</Text>
            <Text style={styles.balanceLabel}>Available</Text>
            <View style={styles.balanceDetail}>
              <Text style={styles.balanceSmall}>Accrued: {b.accrued}</Text>
              <Text style={styles.balanceSmall}>Availed: {b.availed}</Text>
            </View>
          </View>
        ))}
      </ScrollView>

      {/* Apply button */}
      <TouchableOpacity style={styles.applyButton} onPress={() => setShowForm(!showForm)}>
        <Text style={styles.applyButtonText}>{showForm ? 'Cancel' : '+ Apply for Leave'}</Text>
      </TouchableOpacity>

      {/* Application form */}
      {showForm && (
        <View style={styles.form}>
          <Text style={styles.formLabel}>Leave Type</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            {LEAVE_TYPES.map((t) => (
              <TouchableOpacity
                key={t}
                style={[styles.typeChip, leaveType === t && styles.typeChipActive]}
                onPress={() => setLeaveType(t)}
              >
                <Text style={[styles.typeChipText, leaveType === t && styles.typeChipTextActive]}>{t}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>

          <Text style={styles.formLabel}>From Date (YYYY-MM-DD)</Text>
          <TextInput style={styles.input} value={fromDate} onChangeText={setFromDate} placeholder="2026-08-01" placeholderTextColor="#555" />

          <Text style={styles.formLabel}>To Date (YYYY-MM-DD)</Text>
          <TextInput style={styles.input} value={toDate} onChangeText={setToDate} placeholder="2026-08-02" placeholderTextColor="#555" />

          <Text style={styles.formLabel}>Reason</Text>
          <TextInput style={[styles.input, { height: 80 }]} value={reason} onChangeText={setReason} multiline placeholder="Reason for leave..." placeholderTextColor="#555" />

          <TouchableOpacity style={styles.halfDayToggle} onPress={() => setIsHalfDay(!isHalfDay)}>
            <View style={[styles.checkbox, isHalfDay && styles.checkboxActive]} />
            <Text style={styles.halfDayText}>Half Day</Text>
          </TouchableOpacity>

          <TouchableOpacity style={[styles.submitButton, submitting && styles.disabled]} onPress={handleApply} disabled={submitting}>
            {submitting ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitButtonText}>Submit Application</Text>}
          </TouchableOpacity>
        </View>
      )}

      {/* History */}
      <Text style={styles.sectionTitle}>Recent Applications</Text>
      {history.length === 0 ? (
        <Text style={styles.emptyText}>No leave applications found.</Text>
      ) : (
        history.slice(0, 10).map((item, idx) => (
          <View key={idx} style={styles.historyItem}>
            <Text style={styles.historyType}>{item.leave_type}</Text>
            <Text style={styles.historyDates}>{item.from_date} → {item.to_date}</Text>
            <View style={[styles.historyStatus, { backgroundColor: item.status === 'approved' ? '#4caf5022' : item.status === 'rejected' ? '#e9456022' : '#ffa72622' }]}>
              <Text style={{ color: item.status === 'approved' ? '#4caf50' : item.status === 'rejected' ? '#e94560' : '#ffa726', fontSize: 11, fontWeight: '700' }}>
                {item.status?.toUpperCase()}
              </Text>
            </View>
          </View>
        ))
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0f3460', padding: 16 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0f3460' },
  sectionTitle: { color: '#fff', fontSize: 16, fontWeight: '700', marginBottom: 12, marginTop: 8 },
  balanceScroll: { marginBottom: 16 },
  balanceCard: { backgroundColor: '#16213e', borderRadius: 12, padding: 16, marginRight: 12, minWidth: 110, alignItems: 'center' },
  balanceType: { color: '#e94560', fontSize: 13, fontWeight: '700', marginBottom: 4 },
  balanceClosing: { color: '#fff', fontSize: 28, fontWeight: '800' },
  balanceLabel: { color: '#888', fontSize: 11, marginBottom: 8 },
  balanceDetail: { gap: 2 },
  balanceSmall: { color: '#666', fontSize: 10 },
  applyButton: { backgroundColor: '#e94560', borderRadius: 10, paddingVertical: 14, alignItems: 'center', marginBottom: 16 },
  applyButtonText: { color: '#fff', fontWeight: '700', fontSize: 15 },
  form: { backgroundColor: '#16213e', borderRadius: 12, padding: 16, marginBottom: 16 },
  formLabel: { color: '#aaa', fontSize: 12, marginBottom: 6, marginTop: 12 },
  input: { backgroundColor: '#1a1a2e', borderRadius: 8, padding: 12, color: '#fff', fontSize: 14, borderWidth: 1, borderColor: '#333' },
  typeChip: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: '#1a1a2e', marginRight: 8, borderWidth: 1, borderColor: '#333' },
  typeChipActive: { backgroundColor: '#e94560', borderColor: '#e94560' },
  typeChipText: { color: '#888', fontWeight: '600', fontSize: 13 },
  typeChipTextActive: { color: '#fff' },
  halfDayToggle: { flexDirection: 'row', alignItems: 'center', marginTop: 12, gap: 10 },
  checkbox: { width: 20, height: 20, borderRadius: 4, borderWidth: 2, borderColor: '#555' },
  checkboxActive: { backgroundColor: '#e94560', borderColor: '#e94560' },
  halfDayText: { color: '#aaa', fontSize: 14 },
  submitButton: { backgroundColor: '#e94560', borderRadius: 10, paddingVertical: 14, alignItems: 'center', marginTop: 16 },
  submitButtonText: { color: '#fff', fontWeight: '700', fontSize: 15 },
  disabled: { opacity: 0.5 },
  historyItem: { backgroundColor: '#16213e', borderRadius: 10, padding: 14, marginBottom: 8, flexDirection: 'row', alignItems: 'center', gap: 10 },
  historyType: { color: '#e94560', fontWeight: '700', fontSize: 13, width: 36 },
  historyDates: { color: '#aaa', fontSize: 12, flex: 1 },
  historyStatus: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  emptyText: { color: '#555', textAlign: 'center', marginTop: 16 },
});
