import React from 'react';
import { SafeAreaView, ScrollView, StatusBar, StyleSheet, Text, TouchableOpacity, useColorScheme, View } from 'react-native';

const promoCodes = [
  { code: '6% off', minSpend: 'Min. spend: PHP 8,000', description: 'Sitewide' },
  { code: 'Others', description: 'More promo codes coming soon' },
];

export default function SaleScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    cardBg: isDark ? '#1E2029' : '#FFFFFF',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#6B7280',
    accent: '#22C55E',
    border: isDark ? '#2A2D38' : '#E5E5E5',
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />
      <ScrollView style={styles.scrollView} contentContainerStyle={styles.content}>
        <Text style={[styles.header, { color: theme.textMain }]}>Deals</Text>

        <View style={styles.locationRow}>
          {['Philippines', 'Vietnam', 'South Korea'].map((loc) => (
            <TouchableOpacity key={loc} style={[styles.locationChip, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
              <Text style={[styles.locationText, { color: theme.textMain }]}>{loc}</Text>
            </TouchableOpacity>
          ))}
        </View>

        <Text style={[styles.promoSectionTitle, { color: theme.textMain }]}>Promo codes for Philippines</Text>

        {promoCodes.map((promo, idx) => (
          <View key={idx} style={[styles.promoCard, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
            <View style={{ flex: 1 }}>
              <Text style={[styles.promoCode, { color: theme.accent }]}>{promo.code}</Text>
              {promo.minSpend && <Text style={[styles.promoMinSpend, { color: theme.textSub }]}>{promo.minSpend}</Text>}
              <Text style={[styles.promoDesc, { color: theme.textMain }]}>{promo.description}</Text>
            </View>
            {promo.minSpend && (
              <TouchableOpacity style={[styles.redeemButton, { backgroundColor: theme.accent }]}>
                <Text style={styles.redeemText}>Redeem</Text>
              </TouchableOpacity>
            )}
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  scrollView: { flex: 1 },
  content: { padding: 20 },
  header: { fontSize: 28, fontWeight: '800', marginBottom: 20 },
  locationRow: { flexDirection: 'row', marginBottom: 24 },
  locationChip: { paddingVertical: 8, paddingHorizontal: 16, borderRadius: 20, marginRight: 10, borderWidth: 1 },
  locationText: { fontWeight: '600', fontSize: 13 },
  promoSectionTitle: { fontSize: 18, fontWeight: '700', marginBottom: 12 },
  promoCard: { borderRadius: 12, padding: 16, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12, borderWidth: 1 },
  promoCode: { fontSize: 18, fontWeight: '800' },
  promoMinSpend: { fontSize: 12, marginTop: 4 },
  promoDesc: { fontSize: 14, marginTop: 4 },
  redeemButton: { paddingVertical: 8, paddingHorizontal: 20, borderRadius: 30 },
  redeemText: { color: '#FFFFFF', fontWeight: '700' },
});