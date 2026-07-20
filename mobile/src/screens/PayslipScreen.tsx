/**
 * NinjaOS HRMS — Payslip Screen (Mobile)
 *
 * Fetches a list of payslip summaries from the API.
 * Each payslip card shows month/year, gross pay, net pay, and status.
 * Tapping "View PDF" opens the Sanctum-signed URL in the device's
 * native browser (expo-web-browser), which renders the PDF natively.
 *
 * Security: The signed URL is short-lived (15 minutes). No PDF data
 * is stored locally or passed through the app's state.
 */

import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../store/authStore';
import { fetchPayslips, openPayslipPdf, PayslipSummary } from '../services/payslipService';

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

export default function PayslipScreen() {
  const { user } = useAuthStore();
  const employeeId = (user as any)?.employee_id ?? 0;

  const [payslips, setPayslips] = useState<PayslipSummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [openingId, setOpeningId] = useState<string | null>(null);

  useEffect(() => {
    fetchPayslips(employeeId)
      .then(setPayslips)
      .catch(() => Alert.alert('Error', 'Failed to load payslips.'))
      .finally(() => setLoading(false));
  }, [employeeId]);

  const handleViewPdf = async (payslip: PayslipSummary) => {
    setOpeningId(payslip.payroll_id);
    try {
      await openPayslipPdf(payslip.payslip_url);
    } catch {
      Alert.alert('Error', 'Could not open payslip. Please try again.');
    } finally {
      setOpeningId(null);
    }
  };

  if (loading) {
    return <View style={styles.centered}><ActivityIndicator size="large" color="#e94560" /></View>;
  }

  const renderItem = ({ item }: { item: PayslipSummary }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <View>
          <Text style={styles.monthYear}>{MONTH_NAMES[item.payroll_month - 1]} {item.payroll_year}</Text>
          <View style={[styles.statusBadge, { backgroundColor: item.status === 'finalized' ? '#4caf5022' : '#ffa72622' }]}>
            <Text style={[styles.statusText, { color: item.status === 'finalized' ? '#4caf50' : '#ffa726' }]}>
              {item.status?.toUpperCase()}
            </Text>
          </View>
        </View>
        <TouchableOpacity
          style={[styles.pdfButton, openingId === item.payroll_id && styles.disabled]}
          onPress={() => handleViewPdf(item)}
          disabled={openingId === item.payroll_id}
        >
          {openingId === item.payroll_id
            ? <ActivityIndicator size="small" color="#fff" />
            : <><Ionicons name="document-text" size={16} color="#fff" /><Text style={styles.pdfButtonText}>View PDF</Text></>
          }
        </TouchableOpacity>
      </View>

      <View style={styles.payRow}>
        <View style={styles.payBlock}>
          <Text style={styles.payLabel}>Gross Pay</Text>
          <Text style={styles.payValue}>₹{item.gross_pay.toLocaleString('en-IN')}</Text>
        </View>
        <View style={styles.payDivider} />
        <View style={styles.payBlock}>
          <Text style={styles.payLabel}>Net Pay</Text>
          <Text style={[styles.payValue, { color: '#4caf50' }]}>₹{item.net_pay.toLocaleString('en-IN')}</Text>
        </View>
      </View>
    </View>
  );

  return (
    <FlatList
      style={styles.container}
      data={payslips}
      keyExtractor={(item) => item.payroll_id}
      renderItem={renderItem}
      ListEmptyComponent={<Text style={styles.emptyText}>No payslips found.</Text>}
      contentContainerStyle={{ paddingBottom: 32 }}
    />
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0f3460', padding: 16 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0f3460' },
  card: { backgroundColor: '#16213e', borderRadius: 16, padding: 20, marginBottom: 12 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 16 },
  monthYear: { color: '#fff', fontSize: 18, fontWeight: '700', marginBottom: 6 },
  statusBadge: { alignSelf: 'flex-start', paddingHorizontal: 10, paddingVertical: 3, borderRadius: 12 },
  statusText: { fontSize: 10, fontWeight: '700', letterSpacing: 0.5 },
  pdfButton: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#e94560', borderRadius: 8, paddingHorizontal: 14, paddingVertical: 10, gap: 6 },
  pdfButtonText: { color: '#fff', fontWeight: '600', fontSize: 13 },
  disabled: { opacity: 0.5 },
  payRow: { flexDirection: 'row', justifyContent: 'space-around' },
  payBlock: { alignItems: 'center', flex: 1 },
  payLabel: { color: '#888', fontSize: 12, marginBottom: 4 },
  payValue: { color: '#fff', fontSize: 20, fontWeight: '700' },
  payDivider: { width: 1, backgroundColor: '#333' },
  emptyText: { color: '#555', textAlign: 'center', marginTop: 40, fontSize: 15 },
});
