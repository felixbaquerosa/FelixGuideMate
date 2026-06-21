import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback, useState } from 'react';
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
import { ApiListing, getFavorites, resolveImage } from '../../services/api';
import { getSession } from '../../lib/authStore';
import { usePreferences } from '../../lib/preferences';

export default function WishlistScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { t, formatPrice } = usePreferences();

  const [listings, setListings] = useState<ApiListing[]>([]);
  const [loading, setLoading] = useState(true);
  const [loggedIn, setLoggedIn] = useState(true);
  const [error, setError] = useState('');

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    cardBg: isDark ? '#1E2029' : '#FFFFFF',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#6B7280',
    accent: '#FF5A1F',
  };

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    const session = await getSession();
    if (!session) {
      setLoggedIn(false);
      setLoading(false);
      return;
    }
    setLoggedIn(true);
    try {
      const data = await getFavorites();
      setListings(data.listings);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load your wishlist.');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load])
  );

  const renderItem = ({ item }: { item: ApiListing }) => (
    <TouchableOpacity
      style={[styles.card, { backgroundColor: theme.cardBg }]}
      activeOpacity={0.85}
      onPress={() => router.push({ pathname: '/listing/[slug]', params: { slug: item.slug } })}
    >
      <Image source={{ uri: resolveImage(item.image) }} style={styles.cardImage} />
      <View style={styles.cardBody}>
        {item.area ? <Text style={[styles.area, { color: theme.accent }]}>{item.area}</Text> : null}
        <Text style={[styles.cardTitle, { color: theme.textMain }]} numberOfLines={1}>{item.title}</Text>
        <Text style={[styles.price, { color: theme.textMain }]}>
          {item.price > 0 ? formatPrice(item.price) : t('free')}
        </Text>
      </View>
      <Ionicons name="heart" size={22} color="#EF4444" style={styles.heart} />
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />
      <View style={styles.headerBar}>
        <Text style={[styles.headerTitle, { color: theme.textMain }]}>Wishlist</Text>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={theme.accent} />
        </View>
      ) : !loggedIn ? (
        <View style={styles.center}>
          <Ionicons name="heart-outline" size={44} color={theme.textSub} />
          <Text style={[styles.emptyText, { color: theme.textSub }]}>Log in to see your saved listings.</Text>
          <TouchableOpacity style={[styles.btn, { backgroundColor: theme.accent }]} onPress={() => router.push('/(auth)/login')}>
            <Text style={styles.btnText}>Log in</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={listings}
          renderItem={renderItem}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          ListEmptyComponent={
            <View style={styles.center}>
              <Ionicons name={error ? 'cloud-offline-outline' : 'heart-outline'} size={44} color={theme.textSub} />
              <Text style={[styles.emptyText, { color: theme.textSub }]}>
                {error || 'No saved listings yet. Tap the heart on a listing to save it here.'}
              </Text>
              {error ? (
                <TouchableOpacity style={[styles.btn, { backgroundColor: theme.accent }]} onPress={load}>
                  <Text style={styles.btnText}>Retry</Text>
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
  headerBar: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 12 },
  headerTitle: { fontSize: 26, fontWeight: '800' },
  center: { flexGrow: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 30, paddingTop: 60 },
  listContent: { paddingHorizontal: 16, paddingBottom: 24, flexGrow: 1 },
  card: { flexDirection: 'row', alignItems: 'center', borderRadius: 16, overflow: 'hidden', marginBottom: 14 },
  cardImage: { width: 90, height: 90, resizeMode: 'cover', backgroundColor: '#00000011' },
  cardBody: { flex: 1, paddingHorizontal: 12 },
  area: { fontSize: 12, fontWeight: '700', marginBottom: 2 },
  cardTitle: { fontSize: 15, fontWeight: '800' },
  price: { fontSize: 14, fontWeight: '700', marginTop: 4 },
  heart: { marginRight: 14 },
  emptyText: { fontSize: 14, textAlign: 'center', marginTop: 12, lineHeight: 20 },
  btn: { marginTop: 16, paddingVertical: 11, paddingHorizontal: 30, borderRadius: 12 },
  btnText: { color: '#FFFFFF', fontWeight: '700', fontSize: 14 },
});
