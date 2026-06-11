import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import React from 'react';
import { SafeAreaView, StatusBar, StyleSheet, Text, TouchableOpacity, useColorScheme, View } from 'react-native';

export default function AccountScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const handleSignInPress = () => {
    router.replace('/(auth)/login');
  };

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    cardBg: isDark ? '#1E2029' : '#FFFFFF',
    border: isDark ? '#2A2D38' : '#EDF2F7',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#718096',
    iconColor: isDark ? '#CBD5E1' : '#2D3748',
    accent: '#22C55E',
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />

      <View style={styles.headerContainer}>
        <Text style={[styles.headerTitle, { color: theme.textMain }]}>Account</Text>
      </View>

      <View style={[styles.welcomeCard, { backgroundColor: theme.cardBg }]}>
        <Text style={[styles.welcomeTitle, { color: theme.textMain }]}>Welcome to GuideMate</Text>
        <Text style={[styles.welcomeSubtitle, { color: theme.textSub }]}>
          Sign in to book, save, and manage trips
        </Text>
        
        <TouchableOpacity 
          style={[styles.signInButton, { backgroundColor: theme.accent }]} 
          onPress={handleSignInPress}
          activeOpacity={0.85}
        >
          <Text style={styles.signInButtonText}>Sign in / Register</Text>
        </TouchableOpacity>
      </View>

      <View style={[styles.menuContainer, { backgroundColor: theme.cardBg }]}>
        <TouchableOpacity style={styles.menuItem} activeOpacity={0.7}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="compass-outline" size={22} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>Explore Cebu</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.menuItem, { borderTopWidth: 1, borderColor: theme.border }]} activeOpacity={0.7}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="calendar-outline" size={21} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>My bookings</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.menuItem, { borderTopWidth: 1, borderColor: theme.border }]} activeOpacity={0.7}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="heart-outline" size={22} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>Saved</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
        </TouchableOpacity>
      </View>

      <View style={styles.footerContainer}>
        <Text style={[styles.footerText, { color: theme.textSub }]}>GuideMate Mobile · Cebu, Philippines</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  headerContainer: { paddingHorizontal: 24, paddingTop: 24, paddingBottom: 16 },
  headerTitle: { fontSize: 28, fontWeight: '800', letterSpacing: -0.5 },
  welcomeCard: { borderRadius: 20, marginHorizontal: 20, paddingVertical: 24, paddingHorizontal: 20, alignItems: 'center', marginBottom: 20, shadowColor: '#A0AEC0', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 12, elevation: 2 },
  welcomeTitle: { fontSize: 19, fontWeight: '700', marginBottom: 6 },
  welcomeSubtitle: { fontSize: 14, textAlign: 'center', marginBottom: 20, fontWeight: '500' },
  signInButton: { borderRadius: 14, paddingVertical: 14, paddingHorizontal: 40, width: '80%', alignItems: 'center' },
  signInButtonText: { color: '#FFFFFF', fontSize: 15, fontWeight: '700' },
  menuContainer: { borderRadius: 20, marginHorizontal: 20, overflow: 'hidden', shadowColor: '#A0AEC0', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 12, elevation: 2 },
  menuItem: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 16, paddingHorizontal: 20 },
  menuItemLeft: { flexDirection: 'row', alignItems: 'center' },
  menuIcon: { marginRight: 14, width: 24, textAlign: 'center' },
  menuItemText: { fontSize: 15, fontWeight: '700' },
  footerContainer: { marginTop: 28, alignItems: 'center' },
  footerText: { fontSize: 12, fontWeight: '500' },
});