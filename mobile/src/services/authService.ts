/**
 * NinjaOS HRMS — Auth Service
 *
 * Wraps the Sanctum /login and /logout endpoints.
 * Tokens are stored in expo-secure-store (AES-256 on device keychain).
 *
 * PII Note: email is never logged; only success/failure status is recorded.
 */

import * as SecureStore from 'expo-secure-store';
import api, { TOKEN_KEY } from './api';

export interface LoginPayload {
  email: string;
  password: string;
}

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  location_id: number | null;
  roles: string[];
}

export interface LoginResponse {
  token: string;
  user: AuthUser;
}

// ── Login ─────────────────────────────────────────────────────────────────────

export async function login(payload: LoginPayload): Promise<LoginResponse> {
  const { data } = await api.post<LoginResponse>('/auth/login', payload);
  // Store the Sanctum token securely on the device keychain.
  await SecureStore.setItemAsync(TOKEN_KEY, data.token);
  return data;
}

// ── Logout ────────────────────────────────────────────────────────────────────

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout');
  } finally {
    // Always clear the local token even if the server request fails.
    await SecureStore.deleteItemAsync(TOKEN_KEY);
  }
}

// ── Get stored token ──────────────────────────────────────────────────────────

export async function getStoredToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

// ── Fetch authenticated user profile ─────────────────────────────────────────

export async function fetchProfile(): Promise<AuthUser> {
  const { data } = await api.get<{ data: AuthUser }>('/auth/me');
  return data.data;
}
