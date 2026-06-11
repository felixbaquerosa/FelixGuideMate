import { useRouter } from 'expo-router';
import React from 'react';
import { SafeAreaView, StatusBar, StyleSheet, Text, TouchableOpacity, useColorScheme, View } from 'react-native';

export default function WishlistScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const handleLogin = () => {
    router.push('/(auth)/login');
  };

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#6B7280',
    accent: '#22C55E',
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />
      <View style={styles.content}>
        <Text style={[styles.title, { color: theme.textMain }]}>Wishlist</Text>
        <Text style={[styles.message, { color: theme.textSub }]}>Log in to see your wishlist</Text>
        <TouchableOpacity style={[styles.loginButton, { backgroundColor: theme.accent }]} onPress={handleLogin}>
          <Text style={styles.loginButtonText}>Log in</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 20 },
  title: { fontSize: 24, fontWeight: '800', marginBottom: 12 },
  message: { fontSize: 14, textAlign: 'center', marginBottom: 20 },
  loginButton: { paddingVertical: 12, paddingHorizontal: 40, borderRadius: 50 },
  loginButtonText: { color: '#FFFFFF', fontSize: 15, fontWeight: '700' },
});