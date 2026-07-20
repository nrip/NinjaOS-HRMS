/**
 * NinjaOS HRMS — App Navigator
 *
 * Navigation structure:
 *
 *   Root Stack
 *   ├── Auth Stack (unauthenticated)
 *   │   └── LoginScreen
 *   └── Main Tab Navigator (authenticated)
 *       ├── AttendanceScreen
 *       ├── LeaveScreen
 *       └── PayslipScreen
 *
 * The auth guard is implemented by checking `isAuthenticated` from the
 * Zustand store. When the token is restored on app launch, the navigator
 * automatically switches to the Main stack.
 */

import React, { useEffect } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import { useAuthStore } from '../store/authStore';
import LoginScreen from '../screens/LoginScreen';
import AttendanceScreen from '../screens/AttendanceScreen';
import LeaveScreen from '../screens/LeaveScreen';
import PayslipScreen from '../screens/PayslipScreen';

// ── Stack and Tab navigators ──────────────────────────────────────────────────

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

// ── Main Tab Navigator (authenticated users) ──────────────────────────────────

function MainTabNavigator() {
  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: true,
        headerStyle: { backgroundColor: '#1a1a2e' },
        headerTintColor: '#ffffff',
        tabBarStyle: { backgroundColor: '#1a1a2e' },
        tabBarActiveTintColor: '#e94560',
        tabBarInactiveTintColor: '#888',
        tabBarIcon: ({ color, size }) => {
          const icons: Record<string, keyof typeof Ionicons.glyphMap> = {
            Attendance: 'finger-print',
            Leave: 'calendar',
            Payslip: 'document-text',
          };
          return <Ionicons name={icons[route.name] ?? 'ellipse'} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="Attendance" component={AttendanceScreen} options={{ title: 'Attendance' }} />
      <Tab.Screen name="Leave" component={LeaveScreen} options={{ title: 'Leave' }} />
      <Tab.Screen name="Payslip" component={PayslipScreen} options={{ title: 'Payslips' }} />
    </Tab.Navigator>
  );
}

// ── Root Navigator ────────────────────────────────────────────────────────────

export default function AppNavigator() {
  const { isAuthenticated, isLoading, restoreSession } = useAuthStore();

  useEffect(() => {
    // Attempt to restore a previously stored Sanctum token on app launch.
    restoreSession();
  }, []);

  if (isLoading) {
    // Splash screen is handled by expo-splash-screen; return null here.
    return null;
  }

  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        {isAuthenticated ? (
          <Stack.Screen name="Main" component={MainTabNavigator} />
        ) : (
          <Stack.Screen name="Auth" component={LoginScreen} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
