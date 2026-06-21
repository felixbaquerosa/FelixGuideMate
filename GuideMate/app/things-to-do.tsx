import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    FlatList,
    Image,
    SafeAreaView,
    StatusBar,
    StyleSheet,
    Text,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';
import { ApiListing, getListings, resolveImage } from '../services/api';
import { usePreferences } from '../lib/preferences';

export default function ListingsScreen() {
  const router = useRouter();
  const params = useLocalSearchParams<{ category?: string; q?: string; featured?: string }>();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { t, formatPrice } = usePreferences();

  const [listings, setListings] = useState<ApiListing[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    cardBg: isDark ? '#1E2029' : '#FFFFFF',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#718096',
    accent: '#FF5A1F',
    chipBg: isDark ? '#2A2D38' : '#FFF2E6',
  };

  const title = params.featured
    ? 'Attractions'
    : params.category
      ? params.category.split('-').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
      : 'Explore Cebu';

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const query: Record<string, string> = {};
      if (params.category) query.category = String(params.category);
      if (params.q) query.q = String(params.q);
      if (params.featured) query.featured = String(params.featured);
      const data = await getListings(query);
      setListings(data.listings);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load listings.');
    } finally {
      setLoading(false);
    }
  }, [params.category, params.q, params.featured]);

  useEffect(() => {
    load();
  }, [load]);

  const renderItem = ({ item }: { item: ApiListing }) => (
    <TouchableOpacity
      style={[styles.card, { backgroundColor: theme.cardBg }]}
      onPress={() => router.push({ pathname: '/listing/[slug]', params: { slug: item.slug } })}
      activeOpacity={0.85}
    >
      <Image source={{ uri: resolveImage(item.image) }} style={styles.cardImage} />
      <View style={styles.cardBody}>
        {item.area ? (
          <View style={[styles.areaChip, { backgroundColor: theme.chipBg }]}>
            <Ionicons name="location-sharp" size={12} color={theme.accent} />
            <Text style={[styles.areaChipText, { color: theme.accent }]}>{item.area}</Text>
          </View>
        ) : null}
        <Text style={[styles.cardTitle, { color: theme.textMain }]}>{item.title}</Text>
        {item.summary ? (
          <Text style={[styles.cardDesc, { color: theme.textSub }]} numberOfLines={2}>{item.summary}</Text>
        ) : null}
        <Text style={[styles.cardPrice, { color: theme.textMain }]}>
          {item.price > 0 ? formatPrice(item.price) : t('free')}
          <Text style={[styles.cardPriceUnit, { color: theme.textSub }]}>
            {item.price > 0 ? ` / ${item.price_unit || 'person'}` : ''}
          </Text>
        </Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />

      <View style={styles.header}>
        <TouchableOpacity style={styles.backButton} onPress={() => router.back()} activeOpacity={0.7}>
          <Ionicons name="arrow-back" size={24} color={theme.textMain} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: theme.textMain }]} numberOfLines={1}>{title}</Text>
        <View style={styles.backButton} />
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={theme.accent} />
        </View>
      ) : (
        <FlatList
          data={listings}
          renderItem={renderItem}
          keyExtractor={(item) => String(item.id)}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.listContent}
          ListEmptyComponent={
            <View style={styles.center}>
              <Ionicons name={error ? 'cloud-offline-outline' : 'search-outline'} size={40} color={theme.textSub} />
              <Text style={[styles.emptyText, { color: theme.textSub }]}>
                {error || 'No listings found here yet. They will appear once added & approved in the web admin.'}
              </Text>
              {error ? (
                <TouchableOpacity onPress={load} style={[styles.retryBtn, { backgroundColor: theme.accent }]}>
                  <Text style={styles.retryText}>Retry</Text>
                </TouchableOpacity>
              ) : null}
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flexGrow: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 30, paddingTop: 60 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 12,
    paddingBottom: 8,
  },
  backButton: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { flex: 1, fontSize: 18, fontWeight: '800', textAlign: 'center' },
  listContent: { paddingHorizontal: 16, paddingBottom: 28, flexGrow: 1 },
  card: {
    borderRadius: 18,
    overflow: 'hidden',
    marginBottom: 18,
    shadowColor: '#A0AEC0',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 3,
  },
  cardImage: { width: '100%', height: 180, resizeMode: 'cover', backgroundColor: '#00000011' },
  cardBody: { padding: 16 },
  areaChip: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 20,
    marginBottom: 8,
  },
  areaChipText: { fontSize: 12, fontWeight: '700', marginLeft: 4 },
  cardTitle: { fontSize: 18, fontWeight: '800', marginBottom: 6 },
  cardDesc: { fontSize: 13, lineHeight: 20, marginBottom: 8 },
  cardPrice: { fontSize: 15, fontWeight: '800' },
  cardPriceUnit: { fontSize: 12, fontWeight: '500' },
  emptyText: { fontSize: 14, textAlign: 'center', marginTop: 12, lineHeight: 20 },
  retryBtn: { marginTop: 16, paddingVertical: 10, paddingHorizontal: 28, borderRadius: 10 },
  retryText: { color: '#FFFFFF', fontWeight: '700' },
});
