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
import { ApiBooking, getBookings, resolveImage } from '../../services/api';
import { getSession } from '../../lib/authStore';
import { usePreferences } from '../../lib/preferences';

const STATUS_COLOR: Record<string, string> = {
  confirmed: '#22C55E',
  pending: '#F6B100',
  completed: '#3B82F6',
  cancelled: '#EF4444',
  refunded: '#9CA3AF',
};

export default function TripsScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { formatPrice } = usePreferences();

  const [bookings, setBookings] = useState<ApiBooking[]>([]);
  const [loading, setLoading] = useState(true);
  const [loggedIn, setLoggedIn] = useState(true);
  const [error, setError] = useState('');

  const theme = {
    bg: isDark ? '#111114' : '#F8F9FA',
    cardBg: isDark ? '#1E2029' : '#FFFFFF',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#6B7280',
    border: isDark ? '#2A2D38' : '#EDF2F7',
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
      const data = await getBookings();
      setBookings(data.bookings);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load your trips.');
    } finally {
      setLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load])
  );

  const renderItem = ({ item }: { item: ApiBooking }) => (
    <TouchableOpacity
      style={[styles.card, { backgroundColor: theme.cardBg }]}
      activeOpacity={0.85}
      onPress={() => router.push({ pathname: '/listing/[slug]', params: { slug: item.listing_slug } })}
    >
      <Image source={{ uri: resolveImage(item.image) }} style={styles.cardImage} />
      <View style={styles.cardBody}>
        <Text style={[styles.cardTitle, { color: theme.textMain }]} numberOfLines={1}>{item.listing_title}</Text>
        <View style={styles.metaRow}>
          <Ionicons name="calendar-outline" size={14} color={theme.textSub} />
          <Text style={[styles.metaText, { color: theme.textSub }]}>{item.booking_date}</Text>
          <Ionicons name="people-outline" size={14} color={theme.textSub} style={{ marginLeft: 10 }} />
          <Text style={[styles.metaText, { color: theme.textSub }]}>{item.guests}</Text>
        </View>
        <View style={styles.bottomRow}>
          <Text style={[styles.price, { color: theme.textMain }]}>
            {formatPrice(item.total_amount)}
          </Text>
          <View style={[styles.statusChip, { backgroundColor: (STATUS_COLOR[item.status] ?? '#9CA3AF') + '22' }]}>
            <Text style={[styles.statusText, { color: STATUS_COLOR[item.status] ?? '#9CA3AF' }]}>
              {item.status}
            </Text>
          </View>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} backgroundColor={theme.bg} />
      <View style={styles.headerBar}>
        <Text style={[styles.headerTitle, { color: theme.textMain }]}>My Trips</Text>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={theme.accent} />
        </View>
      ) : !loggedIn ? (
        <View style={styles.center}>
          <Ionicons name="briefcase-outline" size={44} color={theme.textSub} />
          <Text style={[styles.emptyText, { color: theme.textSub }]}>Log in to view and manage your bookings.</Text>
          <TouchableOpacity style={[styles.btn, { backgroundColor: theme.accent }]} onPress={() => router.push('/(auth)/login')}>
            <Text style={styles.btnText}>Log in</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={bookings}
          renderItem={renderItem}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.listContent}
          showsVerticalScrollIndicator={false}
          ListEmptyComponent={
            <View style={styles.center}>
              <Ionicons name={error ? 'cloud-offline-outline' : 'calendar-outline'} size={44} color={theme.textSub} />
              <Text style={[styles.emptyText, { color: theme.textSub }]}>
                {error || 'No bookings yet. Book an experience and it will show up here.'}
              </Text>
              <TouchableOpacity style={[styles.btn, { backgroundColor: theme.accent }]} onPress={error ? load : () => router.push('/things-to-do')}>
                <Text style={styles.btnText}>{error ? 'Retry' : 'Explore Cebu'}</Text>
              </TouchableOpacity>
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
  card: { flexDirection: 'row', borderRadius: 16, overflow: 'hidden', marginBottom: 14 },
  cardImage: { width: 110, height: 110, resizeMode: 'cover', backgroundColor: '#00000011' },
  cardBody: { flex: 1, padding: 12, justifyContent: 'space-between' },
  cardTitle: { fontSize: 15, fontWeight: '800' },
  metaRow: { flexDirection: 'row', alignItems: 'center', marginTop: 4 },
  metaText: { fontSize: 12, marginLeft: 4, fontWeight: '500' },
  bottomRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 6 },
  price: { fontSize: 15, fontWeight: '800' },
  statusChip: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20 },
  statusText: { fontSize: 11, fontWeight: '800', textTransform: 'capitalize' },
  emptyText: { fontSize: 14, textAlign: 'center', marginTop: 12, lineHeight: 20 },
  btn: { marginTop: 16, paddingVertical: 11, paddingHorizontal: 30, borderRadius: 12 },
  btnText: { color: '#FFFFFF', fontWeight: '700', fontSize: 14 },
});
