import { Stack } from 'expo-router';
import { useEffect } from 'react';
import { restoreSession } from '../lib/authStore';
import { PreferencesProvider } from '../lib/preferences';

export default function RootLayout() {
  // Restore any saved login token at startup so authenticated API calls work.
  useEffect(() => {
    restoreSession();
  }, []);

  return (
    <PreferencesProvider>
      <Stack screenOptions={{ headerShown: false }}>
        {/* 1. Point to the individual landing screen entry point */}
        <Stack.Screen name="(auth)/login" />

        {/* 2. Map the other specific auth routes individually so the Stack registry detects them */}
        <Stack.Screen name="(auth)/register" />
        <Stack.Screen name="(auth)/forgot-password" />

        {/* 3. Map the main dashboard tab group entry point layout */}
        <Stack.Screen name="(tabs)" />

        {/* 4. Things to do in Cebu list screen */}
        <Stack.Screen name="things-to-do" />

        {/* 5. Single listing detail screen */}
        <Stack.Screen name="listing/[slug]" />

        {/* 6. App settings (language, currency, etc.) */}
        <Stack.Screen name="settings" />

        {/* 7. Security & biometric screens */}
        <Stack.Screen name="account-security" />
        <Stack.Screen name="change-password" />
        <Stack.Screen name="fingerprint" />
      </Stack>
    </PreferencesProvider>
  );
}
