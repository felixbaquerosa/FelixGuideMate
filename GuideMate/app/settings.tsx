import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback, useState } from 'react';
import {
    FlatList,
    Modal,
    ScrollView,
    StatusBar,
    StyleSheet,
    Text,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { Alert } from 'react-native';
import { getSession, logout } from '../lib/authStore';
import { isBiometricEnabled } from '../lib/biometric';
import {
    CURRENCY_LIST,
    CurrencyCode,
    LANGUAGES,
    LanguageCode,
    usePreferences,
} from '../lib/preferences';

type Picker = 'language' | 'currency' | null;

export default function SettingsScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { language, currency, setLanguage, setCurrency, t } = usePreferences();
  const insets = useSafeAreaInsets();

  const [picker, setPicker] = useState<Picker>(null);
  const [fingerprintOn, setFingerprintOn] = useState(false);
  const [loggedIn, setLoggedIn] = useState(false);

  useFocusEffect(
    useCallback(() => {
      isBiometricEnabled().then(setFingerprintOn);
      getSession().then((s) => setLoggedIn(!!s));
    }, [])
  );

  const requireLogin = (action: () => void) => {
    if (!loggedIn) {
      Alert.alert(
        'Login required',
        'Please register or log in to your account first to use this feature.',
        [
          { text: 'Cancel', style: 'cancel' },
          { text: 'Register / Log in', onPress: () => router.replace('/(auth)/login') },
        ]
      );
      return;
    }
    action();
  };

  const theme = {
    bg: isDark ? '#0F1012' : '#F2F3F5',
    card: isDark ? '#1E2029' : '#FFFFFF',
    border: isDark ? '#2A2D38' : '#ECECEC',
    textMain: isDark ? '#FFFFFF' : '#1A1A1A',
    textSub: isDark ? '#9CA3AF' : '#8A8A8A',
    sectionText: isDark ? '#9CA3AF' : '#7A7A7A',
    sectionBg: isDark ? '#15161A' : '#F2F3F5',
    accent: '#22C55E',
    warn: '#F97316',
    chevron: isDark ? '#6B7280' : '#C4C4C4',
  };

  const currentLanguageLabel = LANGUAGES.find((l) => l.code === language)?.label ?? 'English (US)';

  const handleLogout = async () => {
    await logout();
    router.replace('/(auth)/login');
  };

  const Row = ({
    label,
    value,
    onPress,
    valueColor,
    showDot,
    first,
  }: {
    label: string;
    value?: string;
    onPress?: () => void;
    valueColor?: string;
    showDot?: boolean;
    first?: boolean;
  }) => (
    <TouchableOpacity
      style={[styles.row, !first && { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.border }]}
      activeOpacity={onPress ? 0.6 : 1}
      onPress={onPress}
    >
      <Text style={[styles.rowLabel, { color: theme.textMain }]}>{label}</Text>
      <View style={styles.rowRight}>
        {showDot ? <View style={[styles.dot, { backgroundColor: theme.warn }]} /> : null}
        {value ? <Text style={[styles.rowValue, { color: valueColor ?? theme.textSub }]}>{value}</Text> : null}
        <Ionicons name="chevron-forward" size={18} color={theme.chevron} />
      </View>
    </TouchableOpacity>
  );

  const SectionLabel = ({ text }: { text: string }) => (
    <Text style={[styles.sectionLabel, { color: theme.sectionText, backgroundColor: theme.sectionBg }]}>{text}</Text>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['left', 'right']}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.card} />

      {/* Header */}
      <View style={[styles.header, { backgroundColor: theme.card, borderBottomColor: theme.border, paddingTop: insets.top + 12 }]}>
        <TouchableOpacity onPress={() => router.back()} style={styles.headerBack} activeOpacity={0.7}>
          <Ionicons name="chevron-back" size={26} color={theme.textMain} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: theme.textMain }]}>{t('settings_title')}</Text>
        <View style={styles.headerBack} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 40 }}>
        <SectionLabel text={t('sec_account')} />
        <View style={[styles.group, { backgroundColor: theme.card }]}>
          <Row label={t('login_methods')} first onPress={() => router.push('/login-methods')} />
          <Row label={t('account_security')} onPress={() => requireLogin(() => router.push('/account-security'))} />
          <Row
            label={t('fingerprint')}
            value={fingerprintOn ? t('enabled') : t('not_enabled')}
            valueColor={fingerprintOn ? theme.accent : undefined}
            showDot={!fingerprintOn}
            onPress={() => requireLogin(() => router.push('/fingerprint'))}
          />
        </View>

        <SectionLabel text={t('sec_prefs')} />
        <View style={[styles.group, { backgroundColor: theme.card }]}>
          <Row label={t('language')} value={currentLanguageLabel} first onPress={() => setPicker('language')} />
          <Row label={t('currency')} value={currency} onPress={() => setPicker('currency')} />
          <Row label={t('notifications')} onPress={() => router.push('/notification-settings')} />
        </View>

        <SectionLabel text={t('sec_others')} />
        <View style={[styles.group, { backgroundColor: theme.card }]}>
          <Row label={t('feedback')} first onPress={() => router.push('/feedback')} />
          <Row label={t('about')} onPress={() => router.push('/about')} />
        </View>

        <View style={[styles.group, styles.logoutGroup, { backgroundColor: theme.card }]}>
          <TouchableOpacity style={styles.row} activeOpacity={0.6} onPress={handleLogout}>
            <Text style={[styles.rowLabel, { color: theme.textMain }]}>{t('logout')}</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>

      {/* Language picker */}
      <PickerModal
        visible={picker === 'language'}
        title={t('choose_language')}
        onClose={() => setPicker(null)}
        theme={theme}
        data={LANGUAGES.map((l) => ({ key: l.code, label: l.label, selected: l.code === language }))}
        onSelect={(key) => {
          setLanguage(key as LanguageCode);
          setPicker(null);
        }}
      />

      {/* Currency picker */}
      <PickerModal
        visible={picker === 'currency'}
        title={t('choose_currency')}
        onClose={() => setPicker(null)}
        theme={theme}
        data={CURRENCY_LIST.map((c) => ({
          key: c.code,
          label: `${c.code} \u00B7 ${c.name}`,
          right: c.symbol,
          selected: c.code === currency,
        }))}
        onSelect={(key) => {
          setCurrency(key as CurrencyCode);
          setPicker(null);
        }}
      />
    </SafeAreaView>
  );
}

type PickerItem = { key: string; label: string; right?: string; selected: boolean };

function PickerModal({
  visible,
  title,
  onClose,
  onSelect,
  data,
  theme,
}: {
  visible: boolean;
  title: string;
  onClose: () => void;
  onSelect: (key: string) => void;
  data: PickerItem[];
  theme: any;
}) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.modalBackdrop}>
        <TouchableOpacity style={styles.modalDismiss} activeOpacity={1} onPress={onClose} />
        <View style={[styles.modalSheet, { backgroundColor: theme.card }]}>
          <View style={styles.modalHeader}>
            <Text style={[styles.modalTitle, { color: theme.textMain }]}>{title}</Text>
            <TouchableOpacity onPress={onClose}>
              <Ionicons name="close" size={24} color={theme.textSub} />
            </TouchableOpacity>
          </View>
          <FlatList
            data={data}
            keyExtractor={(item) => item.key}
            style={{ maxHeight: 380 }}
            renderItem={({ item }) => (
              <TouchableOpacity
                style={[styles.option, { borderTopColor: theme.border }]}
                activeOpacity={0.6}
                onPress={() => onSelect(item.key)}
              >
                <Text style={[styles.optionLabel, { color: theme.textMain }]}>{item.label}</Text>
                <View style={styles.rowRight}>
                  {item.right ? <Text style={[styles.optionRight, { color: theme.textSub }]}>{item.right}</Text> : null}
                  {item.selected ? <Ionicons name="checkmark" size={20} color={theme.accent} /> : null}
                </View>
              </TouchableOpacity>
            )}
          />
        </View>
      </View>
    </Modal>
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
  sectionLabel: { fontSize: 13, fontWeight: '600', paddingHorizontal: 20, paddingTop: 22, paddingBottom: 8 },
  group: { },
  logoutGroup: { marginTop: 22 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 17,
  },
  rowLabel: { fontSize: 16, fontWeight: '500' },
  rowRight: { flexDirection: 'row', alignItems: 'center' },
  rowValue: { fontSize: 15, marginRight: 6 },
  dot: { width: 8, height: 8, borderRadius: 4, marginRight: 8 },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'flex-end' },
  modalDismiss: { flex: 1 },
  modalSheet: { borderTopLeftRadius: 20, borderTopRightRadius: 20, paddingBottom: 30 },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 18,
  },
  modalTitle: { fontSize: 17, fontWeight: '700' },
  option: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderTopWidth: StyleSheet.hairlineWidth,
  },
  optionLabel: { fontSize: 16, fontWeight: '500' },
  optionRight: { fontSize: 15, marginRight: 10 },
});
