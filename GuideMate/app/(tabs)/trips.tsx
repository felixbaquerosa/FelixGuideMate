import React from 'react';
import { SafeAreaView, StatusBar, StyleSheet, Text, useColorScheme, View } from 'react-native';

export default function TripsScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#6B7280',
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />
      <View style={styles.content}>
        <Text style={[styles.title, { color: theme.textMain }]}>Trips</Text>
        <Text style={[styles.message, { color: theme.textSub }]}>
          Need inspiration for your next trip?{'\n'}
          Login to plan your trips to anywhere.
        </Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 20 },
  title: { fontSize: 24, fontWeight: '800', marginBottom: 12 },
  message: { fontSize: 14, textAlign: 'center', lineHeight: 20 },
});