/**
 * NinjaOS HRMS — Auth Store (Zustand)
 *
 * Manages the global authentication state including:
 *  - Current user profile
 *  - Authentication status
 *  - Login/logout actions
 *
 * The Sanctum token itself is stored in expo-secure-store (not in Zustand)
 * to prevent it from appearing in React DevTools or crash reports.
 */

import { create } from 'zustand';
import { AuthUser, login as apiLogin, logout as apiLogout, fetchProfile } from '../services/authService';
import { getStoredToken } from '../services/authService';

interface AuthState {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;

  // Actions
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  restoreSession: () => Promise<void>;
  clearError: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: false,
  isLoading: false,
  error: null,

  login: async (email: string, password: string) => {
    set({ isLoading: true, error: null });
    try {
      const { user } = await apiLogin({ email, password });
      set({ user, isAuthenticated: true, isLoading: false });
    } catch (err: any) {
      const message = err?.response?.data?.message ?? 'Login failed. Please check your credentials.';
      set({ error: message, isLoading: false });
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await apiLogout();
    } finally {
      set({ user: null, isAuthenticated: false, isLoading: false, error: null });
    }
  },

  restoreSession: async () => {
    set({ isLoading: true });
    try {
      const token = await getStoredToken();
      if (!token) {
        set({ isLoading: false });
        return;
      }
      const user = await fetchProfile();
      set({ user, isAuthenticated: true, isLoading: false });
    } catch {
      // Token is invalid or expired — clear state silently.
      set({ user: null, isAuthenticated: false, isLoading: false });
    }
  },

  clearError: () => set({ error: null }),
}));
