import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback, useState } from 'react';
import { SafeAreaView, StatusBar, StyleSheet, Text, TouchableOpacity, useColorScheme, View } from 'react-native';
import { getSession, logout, SessionUser } from '../../lib/authStore';
import { usePreferences } from '../../lib/preferences';

export default function AccountScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { t } = usePreferences();
  const [user, setUser] = useState<SessionUser | null>(null);

  // Refresh the session each time this tab gains focus.
  useFocusEffect(
    useCallback(() => {
      let active = true;
      getSession().then((session) => {
        if (active) {
          setUser(session);
        }
      });
      return () => {
        active = false;
      };
    }, [])
  );

  const handleSignInPress = () => {
    router.replace('/(auth)/login');
  };

  const handleLogout = async () => {
    await logout();
    setUser(null);
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
        <Text style={[styles.headerTitle, { color: theme.textMain }]}>{t('account_title')}</Text>
        <TouchableOpacity
          style={styles.settingsButton}
          activeOpacity={0.7}
          onPress={() => router.push('/settings')}
        >
          <Ionicons name="settings-outline" size={24} color={theme.iconColor} />
        </TouchableOpacity>
      </View>

      <View style={[styles.welcomeCard, { backgroundColor: theme.cardBg }]}>
        {user ? (
          <>
            <View style={[styles.avatar, { backgroundColor: theme.accent }]}>
              <Text style={styles.avatarText}>
                {user.fullName.trim().charAt(0).toUpperCase() || 'G'}
              </Text>
            </View>
            <Text style={[styles.welcomeTitle, { color: theme.textMain }]}>{user.fullName}</Text>
            <Text style={[styles.welcomeSubtitle, { color: theme.textSub }]}>{user.email}</Text>

            <TouchableOpacity
              style={[styles.signInButton, { backgroundColor: 'transparent', borderWidth: 1.5, borderColor: theme.accent }]}
              onPress={handleLogout}
              activeOpacity={0.85}
            >
              <Text style={[styles.signInButtonText, { color: theme.accent }]}>{t('logout')}</Text>
            </TouchableOpacity>
          </>
        ) : (
          <>
            <Text style={[styles.welcomeTitle, { color: theme.textMain }]}>{t('welcome_guest')}</Text>
            <Text style={[styles.welcomeSubtitle, { color: theme.textSub }]}>
              {t('guest_sub')}
            </Text>

            <TouchableOpacity
              style={[styles.signInButton, { backgroundColor: theme.accent }]}
              onPress={handleSignInPress}
              activeOpacity={0.85}
            >
              <Text style={styles.signInButtonText}>{t('signin_btn')}</Text>
            </TouchableOpacity>
          </>
        )}
      </View>

      <View style={[styles.menuContainer, { backgroundColor: theme.cardBg }]}>
        <TouchableOpacity style={styles.menuItem} activeOpacity={0.7} onPress={() => router.push('/things-to-do')}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="compass-outline" size={22} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>{t('explore')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.menuItem, { borderTopWidth: 1, borderColor: theme.border }]} activeOpacity={0.7} onPress={() => router.push('/(tabs)/trips')}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="calendar-outline" size={21} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>{t('bookings')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.menuItem, { borderTopWidth: 1, borderColor: theme.border }]} activeOpacity={0.7} onPress={() => router.push('/(tabs)/wishlist')}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="heart-outline" size={22} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>{t('saved')}</Text>
          </View>
          <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
        </TouchableOpacity>

        <TouchableOpacity style={[styles.menuItem, { borderTopWidth: 1, borderColor: theme.border }]} activeOpacity={0.7} onPress={() => router.push('/settings')}>
          <View style={styles.menuItemLeft}>
            <Ionicons name="settings-outline" size={21} color={theme.iconColor} style={styles.menuIcon} />
            <Text style={[styles.menuItemText, { color: theme.textMain }]}>{t('settings')}</Text>
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
  headerContainer: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 24, paddingTop: 24, paddingBottom: 16 },
  headerTitle: { fontSize: 28, fontWeight: '800', letterSpacing: -0.5 },
  settingsButton: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  welcomeCard: { borderRadius: 20, marginHorizontal: 20, paddingVertical: 24, paddingHorizontal: 20, alignItems: 'center', marginBottom: 20, shadowColor: '#A0AEC0', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 12, elevation: 2 },
  avatar: { width: 64, height: 64, borderRadius: 32, alignItems: 'center', justifyContent: 'center', marginBottom: 12 },
  avatarText: { color: '#FFFFFF', fontSize: 26, fontWeight: '800' },
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