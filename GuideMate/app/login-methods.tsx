import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback, useState } from 'react';
import {
    Alert,
    ScrollView,
    StatusBar,
    StyleSheet,
    Text,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { getSession } from '../lib/authStore';
import { canUseBiometrics, isBiometricEnabled } from '../lib/biometric';

export default function LoginMethodsScreen() {
  const router = useRouter();
  const isDark = useColorScheme() === 'dark';
  const insets = useSafeAreaInsets();

  const [loggedIn, setLoggedIn] = useState(false);
  const [email, setEmail] = useState('');
  const [bioEnabled, setBioEnabled] = useState(false);
  const [bioSupported, setBioSupported] = useState(true);

  useFocusEffect(
    useCallback(() => {
      getSession().then((s) => {
        setLoggedIn(!!s);
        setEmail(s?.email ?? '');
      });
      isBiometricEnabled().then(setBioEnabled);
      canUseBiometrics().then(setBioSupported);
    }, [])
  );

  const theme = {
    bg: isDark ? '#0F1012' : '#F2F3F5',
    card: isDark ? '#1E2029' : '#FFFFFF',
    border: isDark ? '#2A2D38' : '#ECECEC',
    textMain: isDark ? '#FFFFFF' : '#1A1A1A',
    textSub: isDark ? '#9CA3AF' : '#8A8A8A',
    accent: '#22C55E',
    iconBg: isDark ? '#15161A' : '#F1F5F9',
    soon: isDark ? '#3A3D48' : '#E5E7EB',
  };

  const requireLogin = (action: () => void) => {
    if (!loggedIn) {
      Alert.alert('Login required', 'Please register or log in to your account first.', [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Register / Log in', onPress: () => router.replace('/(auth)/login') },
      ]);
      return;
    }
    action();
  };

  const Method = ({
    icon,
    title,
    subtitle,
    status,
    statusColor,
    onPress,
    disabled,
  }: {
    icon: keyof typeof Ionicons.glyphMap;
    title: string;
    subtitle: string;
    status?: string;
    statusColor?: string;
    onPress?: () => void;
    disabled?: boolean;
  }) => (
    <TouchableOpacity
      style={[styles.method, { backgroundColor: theme.card, borderColor: theme.border }, disabled && { opacity: 0.6 }]}
      activeOpacity={onPress && !disabled ? 0.7 : 1}
      onPress={disabled ? undefined : onPress}
    >
      <View style={[styles.methodIcon, { backgroundColor: theme.iconBg }]}>
        <Ionicons name={icon} size={22} color={theme.accent} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={[styles.methodTitle, { color: theme.textMain }]}>{title}</Text>
        <Text style={[styles.methodSub, { color: theme.textSub }]}>{subtitle}</Text>
      </View>
      {status ? (
        <Text style={[styles.status, { color: statusColor ?? theme.textSub }]}>{status}</Text>
      ) : onPress && !disabled ? (
        <Ionicons name="chevron-forward" size={18} color={theme.textSub} />
      ) : null}
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['left', 'right']}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.card} />

      <View style={[styles.header, { backgroundColor: theme.card, borderBottomColor: theme.border, paddingTop: insets.top + 12 }]}>
        <TouchableOpacity onPress={() => router.back()} style={styles.headerBack} activeOpacity={0.7}>
          <Ionicons name="chevron-back" size={26} color={theme.textMain} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: theme.textMain }]}>Login methods</Text>
        <View style={styles.headerBack} />
      </View>

      <ScrollView contentContainerStyle={{ padding: 16, paddingBottom: 40 }} showsVerticalScrollIndicator={false}>
        <Text style={[styles.intro, { color: theme.textSub }]}>
          {loggedIn
            ? 'Manage how you sign in to GuideMate.'
            : 'Log in or register to manage your sign-in methods.'}
        </Text>

        <Method
          icon="mail-outline"
          title="Email & password"
          subtitle={loggedIn && email ? email : 'Sign in with your email address'}
          status="Active"
          statusColor={theme.accent}
        />

        <Method
          icon="key-outline"
          title="Change password"
          subtitle="Update the password for your account"
          onPress={() => requireLogin(() => router.push('/change-password'))}
        />

        <Method
          icon="finger-print"
          title="Fingerprint / biometrics"
          subtitle={bioSupported ? 'Sign in quickly with your fingerprint' : 'Not available on this device'}
          status={!bioSupported ? undefined : bioEnabled ? 'Enabled' : 'Set up'}
          statusColor={bioEnabled ? theme.accent : undefined}
          disabled={!bioSupported}
          onPress={() => requireLogin(() => router.push('/fingerprint'))}
        />

        <Text style={[styles.sectionLabel, { color: theme.textSub }]}>Coming soon</Text>

        <Method icon="logo-google" title="Continue with Google" subtitle="Link your Google account" status="Soon" disabled />
        <Method icon="logo-facebook" title="Continue with Facebook" subtitle="Link your Facebook account" status="Soon" disabled />

        {!loggedIn ? (
          <TouchableOpacity
            style={[styles.cta, { backgroundColor: theme.accent }]}
            activeOpacity={0.85}
            onPress={() => router.replace('/(auth)/login')}
          >
            <Text style={styles.ctaText}>Log in or register</Text>
          </TouchableOpacity>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  headerBack: { width: 40, height: 32, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { fontSize: 18, fontWeight: '700' },
  intro: { fontSize: 14, lineHeight: 20, marginBottom: 16 },
  sectionLabel: { fontSize: 13, fontWeight: '700', marginTop: 20, marginBottom: 8, marginLeft: 4 },
  method: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
  },
  methodIcon: {
    width: 42,
    height: 42,
    borderRadius: 21,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  methodTitle: { fontSize: 15.5, fontWeight: '600' },
  methodSub: { fontSize: 13, marginTop: 2 },
  status: { fontSize: 13.5, fontWeight: '700', marginLeft: 8 },
  cta: {
    borderRadius: 14,
    paddingVertical: 16,
    alignItems: 'center',
    marginTop: 22,
  },
  ctaText: { color: '#FFFFFF', fontSize: 16, fontWeight: '700' },
});
