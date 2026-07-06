import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { SafeAreaView, ScrollView, StatusBar, StyleSheet, Text, TouchableOpacity, useColorScheme, View } from 'react-native';

const PLANS = [
  { name: '3GB / 7 days', price: '₱199', tag: 'Best for short trips' },
  { name: '10GB / 15 days', price: '₱499', tag: 'Great for maps and socials' },
  { name: 'Unlimited / 30 days', price: '₱1,199', tag: 'For heavy data users' },
];

export default function EsimScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    cardBg: isDark ? '#1E2029' : '#FFFFFF',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#6B7280',
    accent: '#10B981',
    border: isDark ? '#2A2D38' : '#E5E7EB',
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />
      <View style={styles.header}>
        <Text style={[styles.title, { color: theme.textMain }]}>eSIM</Text>
        <Text style={[styles.subtitle, { color: theme.textSub }]}>Stay connected without swapping physical SIM cards.</Text>
      </View>
      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        <View style={[styles.heroCard, { backgroundColor: theme.accent }]}>
          <Ionicons name="cellular" size={26} color="#FFFFFF" />
          <Text style={styles.heroTitle}>Easy setup for travelers</Text>
          <Text style={styles.heroText}>Activate your data plan instantly when you land.</Text>
        </View>

        {PLANS.map((plan) => (
          <View key={plan.name} style={[styles.card, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
            <View style={{ flex: 1 }}>
              <Text style={[styles.cardName, { color: theme.textMain }]}>{plan.name}</Text>
              <Text style={[styles.cardTag, { color: theme.textSub }]}>{plan.tag}</Text>
            </View>
            <View style={{ alignItems: 'flex-end' }}>
              <Text style={[styles.cardPrice, { color: theme.accent }]}>{plan.price}</Text>
              <TouchableOpacity style={[styles.buyBtn, { backgroundColor: theme.accent }]} activeOpacity={0.85}>
                <Text style={styles.buyText}>Buy now</Text>
              </TouchableOpacity>
            </View>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: { paddingHorizontal: 20, paddingTop: 16, paddingBottom: 8 },
  title: { fontSize: 28, fontWeight: '800' },
  subtitle: { marginTop: 6, fontSize: 14 },
  content: { padding: 20, paddingBottom: 28 },
  heroCard: { borderRadius: 24, padding: 20, marginBottom: 18 },
  heroTitle: { color: '#FFFFFF', fontSize: 24, fontWeight: '900', marginTop: 10 },
  heroText: { color: '#FFFFFF', marginTop: 6, opacity: 0.9 },
  card: { borderRadius: 18, borderWidth: 1, padding: 16, marginBottom: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  cardName: { fontSize: 16, fontWeight: '800' },
  cardTag: { marginTop: 4, fontSize: 12 },
  cardPrice: { fontSize: 20, fontWeight: '900' },
  buyBtn: { marginTop: 8, borderRadius: 18, paddingVertical: 8, paddingHorizontal: 14 },
  buyText: { color: '#FFFFFF', fontWeight: '800' },
});