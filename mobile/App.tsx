/**
 * NinjaOS HRMS — Mobile App Entry Point
 *
 * Renders the AppNavigator which manages the auth guard and
 * switches between the Login screen and the Main tab navigator
 * based on the Sanctum token stored in expo-secure-store.
 */

import React from 'react';
import { StatusBar } from 'expo-status-bar';
import AppNavigator from './src/navigation/AppNavigator';

export default function App() {
  return (
    <>
      <StatusBar style="light" />
      <AppNavigator />
    </>
  );
}
