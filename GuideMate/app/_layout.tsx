import { Stack } from 'expo-router';

export default function RootLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      {/* 1. Point to the individual landing screen entry point */}
      <Stack.Screen name="(auth)/login" />
      
      {/* 2. Map the other specific auth routes individually so the Stack registry detects them */}
      <Stack.Screen name="(auth)/register" />
      <Stack.Screen name="(auth)/forgot-password" />
      
      {/* 3. Map the main dashboard tab group entry point layout */}
      <Stack.Screen name="(tabs)" />
    </Stack>
  );
}