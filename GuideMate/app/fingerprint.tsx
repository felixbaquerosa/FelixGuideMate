import { Ionicons } from '@expo/vector-icons';
import * as LocalAuthentication from 'expo-local-authentication';
import { useRouter } from 'expo-router';
import React, { useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    SafeAreaView,
    StatusBar,
    StyleSheet,
    Text,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { getSavedCredentials, isBiometricEnabled, setBiometricEnabled } from '../lib/biometric';

export default function FingerprintScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const insets = useSafeAreaInsets();

  const [enabled, setEnabled] = useState(false);
  const [loading, setLoading] = useState(true);
  const [working, setWorking] = useState(false);

  const theme = {
    bg: isDark ? '#111114' : '#FFFFFF',
    textMain: isDark ? '#FFFFFF' : '#1A1A1A',
    textSub: isDark ? '#9CA3AF' : '#8A8A8A',
    accent: '#22C55E',
  };

  useEffect(() => {
    isBiometricEnabled().then((v) => {
      setEnabled(v);
      setLoading(false);
    });
  }, []);

  const handleToggle = async () => {
    if (enabled) {
      // Turn off
      await setBiometricEnabled(false);
      setEnabled(false);
      Alert.alert('Fingerprint disabled', 'Fingerprint login has been turned off.');
      return;
    }

    setWorking(true);
    try {
      const hasHardware = await LocalAuthentication.hasHardwareAsync();
      if (!hasHardware) {
        Alert.alert('Not supported', 'This device does not have a fingerprint sensor.');
        return;
      }

      const enrolled = await LocalAuthentication.isEnrolledAsync();
      if (!enrolled) {
        Alert.alert(
          'No fingerprint found',
          'Please add a fingerprint in your phone\u2019s Settings first, then try again.'
        );
        return;
      }

      const result = await LocalAuthentication.authenticateAsync({
        promptMessage: 'Touch the fingerprint sensor to set up',
        cancelLabel: 'Cancel',
        disableDeviceFallback: false,
      });

      if (result.success) {
        await setBiometricEnabled(true);
        setEnabled(true);
        const creds = await getSavedCredentials();
        if (creds) {
          Alert.alert('Success', 'Fingerprint login is now enabled. Next time you can sign in with your fingerprint only.');
        } else {
          Alert.alert(
            'Almost done',
            'Fingerprint is enabled. Please sign in with your password once more, and after that you can log in with your fingerprint only.'
          );
        }
      } else if (result.error && result.error !== 'user_cancel' && result.error !== 'system_cancel') {
        Alert.alert('Setup failed', 'Could not verify your fingerprint. Please try again.');
      }
    } catch {
      Alert.alert('Something went wrong', 'Please try again.');
    } finally {
      setWorking(false);
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />

      <View style={[styles.header, { paddingTop: insets.top + 8 }]}>
        <TouchableOpacity onPress={() => router.back()} style={styles.back} activeOpacity={0.7}>
          <Ionicons name="chevron-back" size={26} color={theme.textMain} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={theme.accent} />
        </View>
      ) : (
        <View style={styles.content}>
          <View style={styles.iconWrap}>
            <Ionicons name="finger-print" size={64} color={theme.accent} />
          </View>

          <Text style={[styles.title, { color: theme.textMain }]}>
            {enabled ? 'Fingerprint enabled' : 'Fingerprint not enabled'}
          </Text>

          <View style={styles.bullets}>
            {[
              'Log into GuideMate with fingerprint',
              'It\u2019s super quick!',
              'To keep your account safe, make sure no one else\u2019s fingerprint is saved on your device.',
            ].map((line) => (
              <View key={line} style={styles.bulletRow}>
                <View style={[styles.bulletDot, { backgroundColor: theme.accent }]} />
                <Text style={[styles.bulletText, { color: theme.textSub }]}>{line}</Text>
              </View>
            ))}
          </View>

          <TouchableOpacity
            style={[styles.button, { backgroundColor: theme.accent }, working && { opacity: 0.6 }]}
            onPress={handleToggle}
            disabled={working}
            activeOpacity={0.85}
          >
            {working ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.buttonText}>{enabled ? 'Disable' : 'Enable'}</Text>
            )}
          </TouchableOpacity>
        </View>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: { paddingHorizontal: 8 },
  back: { width: 44, height: 40, alignItems: 'center', justifyContent: 'center' },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  content: { flex: 1, paddingHorizontal: 28, paddingTop: 60 },
  iconWrap: { alignItems: 'center', marginBottom: 36 },
  title: { fontSize: 30, fontWeight: '800', marginBottom: 18, lineHeight: 38 },
  bullets: { marginBottom: 44 },
  bulletRow: { flexDirection: 'row', alignItems: 'flex-start', marginBottom: 14 },
  bulletDot: { width: 7, height: 7, borderRadius: 4, marginTop: 8, marginRight: 12 },
  bulletText: { flex: 1, fontSize: 15, lineHeight: 23 },
  button: { borderRadius: 12, paddingVertical: 17, alignItems: 'center', justifyContent: 'center' },
  buttonText: { color: '#FFFFFF', fontSize: 16, fontWeight: '700' },
});
