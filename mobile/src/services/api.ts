/**
 * NinjaOS HRMS — Mobile API Service
 *
 * Centralised Axios instance that:
 *  1. Reads the Sanctum token from expo-secure-store on every request.
 *  2. Attaches the Authorization header automatically.
 *  3. Handles 401 responses by clearing the token and redirecting to Login.
 *
 * The base URL is read from app.json's `extra.apiBaseUrl` so it can be
 * overridden per environment without a code change.
 */

import axios, { AxiosInstance, AxiosResponse, InternalAxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';

// ── Constants ─────────────────────────────────────────────────────────────────

export const TOKEN_KEY = 'ninjaos_sanctum_token';

const API_BASE_URL: string =
  (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ??
  'http://localhost:8000/api/v1';

// ── Axios instance ────────────────────────────────────────────────────────────

const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 15_000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// ── Request interceptor — attach Sanctum token ────────────────────────────────

api.interceptors.request.use(
  async (config: InternalAxiosRequestConfig): Promise<InternalAxiosRequestConfig> => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error),
);

// ── Response interceptor — handle 401 ────────────────────────────────────────

api.interceptors.response.use(
  (response: AxiosResponse) => response,
  async (error) => {
    if (error.response?.status === 401) {
      // Clear the stored token; the navigation guard will redirect to Login.
      await SecureStore.deleteItemAsync(TOKEN_KEY);
    }
    return Promise.reject(error);
  },
);

export default api;
