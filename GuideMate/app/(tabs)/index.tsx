import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback, useState } from 'react';
import {
  ActivityIndicator,
  Dimensions,
  FlatList,
  Image,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  useColorScheme,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { usePreferences } from '../../lib/preferences';
import { ApiListing, getHome, HomePayload, resolveImage } from '../../services/api';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - 44) / 2;

// Map backend category slugs to Ionicons + colors.
const CATEGORY_STYLE: Record<string, { icon: string; bg: string; color: string }> = {
  'things-to-do': { icon: 'compass', bg: '#FFF2E6', color: '#FF8C00' },
  'tour-guides': { icon: 'people', bg: '#E6F0FA', color: '#3B82F6' },
  hotels: { icon: 'bed', bg: '#FFF9E6', color: '#FBBF24' },
  restaurants: { icon: 'restaurant', bg: '#E6F7ED', color: '#10B981' },
};

function categoryStyle(slug: string) {
  return CATEGORY_STYLE[slug] ?? { icon: 'apps', bg: '#FFEBEA', color: '#EF4444' };
}

// Extra travel service shortcuts (shown together with the API categories in
// one equal grid).
const SERVICES: { key: string; label: string; icon: string; color: string; bg: string }[] = [
  { key: 'attractions', label: 'Attractions', icon: 'ticket', color: '#EC4899', bg: '#FCE7F3' },
  { key: 'car', label: 'Car Rentals', icon: 'car-sport', color: '#3B82F6', bg: '#E0EDFF' },
  { key: 'flights', label: 'Flights', icon: 'airplane', color: '#8B5CF6', bg: '#EDE9FE' },
  { key: 'esim', label: 'eSIM', icon: 'cellular', color: '#10B981', bg: '#D1FAE5' },
];

export default function HomeScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const insets = useSafeAreaInsets();
  const { t, formatPrice } = usePreferences();

  const [data, setData] = useState<HomePayload | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const theme = {
    bg: isDark ? '#111114' : '#FFFFFF',
    cardBg: isDark ? '#1E2029' : '#F5F5F5',
    border: isDark ? '#2A2D38' : '#E5E5E5',
    textMain: isDark ? '#FFFFFF' : '#111111',
    textSub: isDark ? '#9CA3AF' : '#666666',
    categoryText: isDark ? '#E5E7EB' : '#444444',
    accent: '#FF5A1F',
  };

  const load = useCallback(async () => {
    setError('');
    try {
      const home = await getHome();
      setData(home);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load.');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load])
  );

  const openListing = (slug: string) =>
    router.push({ pathname: '/listing/[slug]', params: { slug } });

  const openCategory = (slug: string) =>
    router.push({ pathname: '/things-to-do', params: { category: slug } });

  const openAttractions = () =>
    router.push({ pathname: '/things-to-do', params: { featured: '1' } });

  type GridItem = { key: string; label: string; icon: string; bg: string; color: string; onPress: () => void };

  const gridItems: GridItem[] = [
    ...(data?.categories ?? []).map((category) => {
      const s = categoryStyle(category.slug);
      return {
        key: `c-${category.id}`,
        label: category.name,
        icon: s.icon,
        bg: s.bg,
        color: s.color,
        onPress: () => openCategory(category.slug),
      };
    }),
    ...SERVICES.map((s) => ({
      key: `s-${s.key}`,
      label: s.label,
      icon: s.icon,
      bg: s.bg,
      color: s.color,
      onPress: s.key === 'attractions' ? openAttractions : () => {},
    })),
  ];

  const renderGridItem = (item: GridItem) => (
    <TouchableOpacity key={item.key} style={styles.gridItem} activeOpacity={0.7} onPress={item.onPress}>
      <View style={[styles.gridIconBg, { backgroundColor: item.bg }]}>
        <Ionicons name={item.icon as any} size={24} color={item.color} />
      </View>
      <Text style={[styles.gridLabel, { color: theme.categoryText }]} numberOfLines={1}>
        {item.label}
      </Text>
    </TouchableOpacity>
  );

  const renderPlaceCard = ({ item }: { item: ApiListing }) => (
    <TouchableOpacity
      style={[styles.placeCard, { backgroundColor: theme.cardBg }]}
      onPress={() => openListing(item.slug)}
      activeOpacity={0.85}
    >
      <Image source={{ uri: resolveImage(item.image) }} style={styles.placeImage} />
      <View style={styles.cardOverlay} />
      <View style={styles.cardTextContainer}>
        {item.area ? (
          <View style={styles.areaBadge}>
            <Ionicons name="location-sharp" size={10} color="#FFFFFF" style={{ marginRight: 2 }} />
            <Text style={styles.areaText} numberOfLines={1}>{item.area}</Text>
          </View>
        ) : null}
        <Text style={styles.placeName} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.placePrice}>
          {item.price > 0 ? formatPrice(item.price) : t('free')}
        </Text>
      </View>
    </TouchableOpacity>
  );

  const ListHeader = () => (
    <View style={[styles.headerContainer, { paddingTop: insets.top + 8 }]}>
      <View style={styles.searchBarRow}>
        <View style={[styles.searchBarWrapper, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
          <Ionicons name="search-outline" size={18} color={theme.textSub} style={styles.searchIcon} />
          <TextInput
            style={[styles.searchInput, { color: theme.textMain }]}
            placeholder={t('search_ph')}
            placeholderTextColor={theme.textSub}
            value={searchQuery}
            onChangeText={setSearchQuery}
            returnKeyType="search"
            onSubmitEditing={() =>
              router.push({ pathname: '/things-to-do', params: { q: searchQuery } })
            }
          />
        </View>
      </View>

      <View style={styles.categoriesGrid}>{gridItems.map(renderGridItem)}</View>

      <View style={styles.rowHeader}>
        <Text style={[styles.sectionTitle, { color: theme.textMain }]}>{t('recommended')}</Text>
        <TouchableOpacity onPress={() => router.push('/things-to-do')}>
          <Text style={[styles.seeMore, { color: theme.accent }]}>{t('see_all')}</Text>
        </TouchableOpacity>
      </View>
    </View>
  );

  if (loading) {
    return (
      <View style={[styles.center, { backgroundColor: theme.bg }]}>
        <ActivityIndicator size="large" color={theme.accent} />
      </View>
    );
  }

  const listings = data?.recommended?.length ? data.recommended : data?.featured ?? [];

  return (
    <FlatList
      style={[styles.container, { backgroundColor: theme.bg }]}
      data={listings}
      renderItem={renderPlaceCard}
      keyExtractor={(item) => String(item.id)}
      ListHeaderComponent={ListHeader}
      numColumns={2}
      columnWrapperStyle={styles.cardRowWrapper}
      showsVerticalScrollIndicator={false}
      contentContainerStyle={styles.scrollContent}
      refreshControl={<RefreshControl refreshing={false} onRefresh={load} tintColor={theme.accent} />}
      ListEmptyComponent={
        <View style={styles.emptyWrap}>
          <Ionicons name={error ? 'cloud-offline-outline' : 'compass-outline'} size={40} color={theme.textSub} />
          <Text style={[styles.emptyText, { color: theme.textSub }]}>
            {error || 'No listings yet. Add listings from the web admin to see them here.'}
          </Text>
          {error ? (
            <TouchableOpacity onPress={load} style={[styles.retryBtn, { backgroundColor: theme.accent }]}>
              <Text style={styles.retryText}>Retry</Text>
            </TouchableOpacity>
          ) : null}
        </View>
      }
    />
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  scrollContent: { paddingBottom: 32 },
  headerContainer: { paddingHorizontal: 16 },
  searchBarRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 20 },
  searchBarWrapper: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 24,
    borderWidth: 1,
    paddingHorizontal: 14,
    height: 42,
  },
  searchIcon: { marginRight: 6 },
  searchInput: { flex: 1, fontSize: 15, padding: 0 },
  categoriesGrid: { flexDirection: 'row', flexWrap: 'wrap', marginBottom: 16 },
  gridItem: { width: '25%', alignItems: 'center', marginBottom: 18 },
  gridIconBg: { width: 56, height: 56, borderRadius: 18, justifyContent: 'center', alignItems: 'center', marginBottom: 7 },
  gridLabel: { fontSize: 11, fontWeight: '500', textAlign: 'center' },
  rowHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 8, marginBottom: 12 },
  sectionTitle: { fontSize: 18, fontWeight: '700', letterSpacing: -0.3 },
  seeMore: { fontSize: 13, fontWeight: '600' },
  cardRowWrapper: { justifyContent: 'space-between', paddingHorizontal: 16, marginBottom: 12 },
  placeCard: { width: CARD_WIDTH, height: CARD_WIDTH * 1.25, borderRadius: 12, overflow: 'hidden' },
  placeImage: { width: '100%', height: '100%', resizeMode: 'cover', backgroundColor: '#00000011' },
  cardOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0,0,0,0.28)' },
  cardTextContainer: { position: 'absolute', bottom: 12, left: 12, right: 12 },
  areaBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.4)',
    alignSelf: 'flex-start',
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: 4,
    marginBottom: 4,
  },
  areaText: { color: '#FFFFFF', fontSize: 10, fontWeight: '500' },
  placeName: { color: '#FFFFFF', fontSize: 14, fontWeight: '700' },
  placePrice: { color: '#FFFFFF', fontSize: 13, fontWeight: '800', marginTop: 2 },
  emptyWrap: { alignItems: 'center', paddingHorizontal: 30, paddingTop: 40 },
  emptyText: { fontSize: 14, textAlign: 'center', marginTop: 12, lineHeight: 20 },
  retryBtn: { marginTop: 16, paddingVertical: 10, paddingHorizontal: 28, borderRadius: 10 },
  retryText: { color: '#FFFFFF', fontWeight: '700' },
});
