import { Ionicons } from '@expo/vector-icons';
import { useLocalSearchParams, useRouter } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    Image,
    Modal,
    ScrollView,
    StatusBar,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';
import { ApiListing, createBooking, getListing, PaymentMethod, resolveImage } from '../../services/api';
import { getSession } from '../../lib/authStore';
import { usePreferences } from '../../lib/preferences';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import CalendarPicker from '../../components/CalendarPicker';

const QR_EWALLET = require('../../assets/images/payment/qr-ewallet.png');
const QR_INSTAPAY = require('../../assets/images/payment/qr-instapay.png');

type BookingStep = 'details' | 'method' | 'qr' | 'card';

export default function ListingDetailScreen() {
  const router = useRouter();
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';
  const { t, formatPrice } = usePreferences();
  const insets = useSafeAreaInsets();

  const [listing, setListing] = useState<ApiListing | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Booking modal state
  const [modalOpen, setModalOpen] = useState(false);
  const [step, setStep] = useState<BookingStep>('details');
  const [bookingDate, setBookingDate] = useState('');
  const [guests, setGuests] = useState(1);
  const [booking, setBooking] = useState(false);

  // Payment state
  const [qrMethod, setQrMethod] = useState<'gcash' | 'instapay'>('gcash');
  const [paymentReference, setPaymentReference] = useState('');
  const [cardName, setCardName] = useState('');
  const [cardNumber, setCardNumber] = useState('');
  const [cardExpiry, setCardExpiry] = useState('');
  const [cardCvv, setCardCvv] = useState('');

  const theme = {
    bg: isDark ? '#111114' : '#FFFFFF',
    card: isDark ? '#1E2029' : '#F8F9FA',
    border: isDark ? '#2A2D38' : '#EDF2F7',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#9CA3AF' : '#718096',
    accent: '#FF5A1F',
    inputBg: isDark ? '#1E2029' : '#F1F5F9',
  };

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const data = await getListing(String(slug));
      setListing(data.listing);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load this listing.');
    } finally {
      setLoading(false);
    }
  }, [slug]);

  useEffect(() => {
    load();
  }, [load]);

  const priceLabel = listing ? (listing.price > 0 ? formatPrice(listing.price) : t('free')) : '';

  const openBooking = async () => {
    const user = await getSession();
    if (!user) {
      Alert.alert('Sign in required', 'Please sign in to book this experience.', [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Sign in', onPress: () => router.push('/(auth)/login') },
      ]);
      return;
    }
    setBookingDate('');
    setGuests(1);
    setStep('details');
    setQrMethod('gcash');
    setPaymentReference('');
    setCardName('');
    setCardNumber('');
    setCardExpiry('');
    setCardCvv('');
    setModalOpen(true);
  };

  const totalAmount = listing ? listing.price * guests : 0;

  const goToPayment = () => {
    if (!bookingDate) {
      Alert.alert('Pick a date', 'Please choose a date from the calendar.');
      return;
    }
    // Free listings skip the payment step entirely.
    if (totalAmount <= 0) {
      submitBooking('card');
      return;
    }
    setStep('method');
  };

  const formatCardNumber = (text: string) => {
    const digits = text.replace(/\D/g, '').slice(0, 16);
    const groups = digits.match(/.{1,4}/g);
    setCardNumber(groups ? groups.join(' ') : '');
  };

  const formatExpiry = (text: string) => {
    const digits = text.replace(/\D/g, '').slice(0, 4);
    if (digits.length <= 2) {
      setCardExpiry(digits);
    } else {
      setCardExpiry(`${digits.slice(0, 2)}/${digits.slice(2)}`);
    }
  };

  const submitBooking = async (
    method: PaymentMethod,
    reference?: string,
    cardLast4?: string
  ) => {
    if (!listing) {
      return;
    }
    setBooking(true);
    try {
      await createBooking({
        listing_id: listing.id,
        booking_date: bookingDate,
        guests,
        payment_method: method,
        payment_reference: reference,
        card_last4: cardLast4,
      });
      setModalOpen(false);
      Alert.alert(
        'Payment received',
        `Your booking for "${listing.title}" on ${bookingDate} is confirmed and sent to the admin for processing. You can see it under Trips.`,
        [
          { text: 'View Trips', onPress: () => router.replace('/(tabs)/trips') },
          { text: 'OK' },
        ]
      );
    } catch (e) {
      Alert.alert('Booking failed', e instanceof Error ? e.message : 'Please try again.');
    } finally {
      setBooking(false);
    }
  };

  const confirmQrPaid = () => {
    if (paymentReference.trim().length < 4) {
      Alert.alert('Reference required', 'Enter the reference / confirmation number from your payment app so the admin can verify it.');
      return;
    }
    submitBooking(qrMethod, paymentReference.trim());
  };

  const confirmCardPaid = () => {
    const digits = cardNumber.replace(/\D/g, '');
    if (cardName.trim().length < 2) {
      Alert.alert('Name required', 'Enter the cardholder name.');
      return;
    }
    if (digits.length < 15) {
      Alert.alert('Invalid card number', 'Please enter a valid card number.');
      return;
    }
    if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
      Alert.alert('Invalid expiry', 'Enter the expiry date as MM/YY.');
      return;
    }
    if (cardCvv.length < 3) {
      Alert.alert('Invalid CVV', 'Enter the 3-digit security code.');
      return;
    }
    submitBooking('card', undefined, digits.slice(-4));
  };

  if (loading) {
    return (
      <View style={[styles.center, { backgroundColor: theme.bg }]}>
        <ActivityIndicator size="large" color={theme.accent} />
      </View>
    );
  }

  if (error || !listing) {
    return (
      <View style={[styles.center, { backgroundColor: theme.bg }]}>
        <Text style={{ color: theme.textMain, marginBottom: 12, textAlign: 'center', paddingHorizontal: 30 }}>
          {error || 'Listing not found.'}
        </Text>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={{ color: theme.accent, fontWeight: '700' }}>Go back</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <View style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle="light-content" />

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 120 + insets.bottom }}>
        <View>
          <Image source={{ uri: resolveImage(listing.image) }} style={styles.hero} />
          <View style={styles.heroOverlay} />
          <TouchableOpacity style={styles.backButton} onPress={() => router.back()} activeOpacity={0.8}>
            <Ionicons name="arrow-back" size={22} color="#FFFFFF" />
          </TouchableOpacity>
          <View style={styles.heroTextWrap}>
            {listing.area ? (
              <View style={styles.areaChip}>
                <Ionicons name="location-sharp" size={12} color="#FFFFFF" />
                <Text style={styles.areaChipText}>{listing.area}</Text>
              </View>
            ) : null}
            <Text style={styles.heroTitle}>{listing.title}</Text>
          </View>
        </View>

        <View style={styles.body}>
          <View style={styles.factsRow}>
            <View style={[styles.factCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <Ionicons name="time-outline" size={18} color={theme.accent} />
              <Text style={[styles.factLabel, { color: theme.textSub }]}>Duration</Text>
              <Text style={[styles.factValue, { color: theme.textMain }]}>{listing.duration || 'N/A'}</Text>
            </View>
            <View style={[styles.factCard, { backgroundColor: theme.card, borderColor: theme.border }]}>
              <Ionicons name="star" size={18} color="#F6B100" />
              <Text style={[styles.factLabel, { color: theme.textSub }]}>Rating</Text>
              <Text style={[styles.factValue, { color: theme.textMain }]}>
                {listing.rating > 0 ? `${listing.rating} (${listing.review_count})` : 'No reviews'}
              </Text>
            </View>
          </View>

          {listing.category ? (
            <Text style={[styles.metaLine, { color: theme.textSub }]}>
              {listing.category}{listing.owner_name ? ` · by ${listing.owner_name}` : ''}
            </Text>
          ) : null}

          <Text style={[styles.sectionTitle, { color: theme.textMain }]}>About this experience</Text>
          <Text style={[styles.about, { color: theme.textSub }]}>
            {listing.description || listing.summary || 'No description provided yet.'}
          </Text>

          {listing.included ? (
            <>
              <Text style={[styles.sectionTitle, { color: theme.textMain }]}>{"What's included"}</Text>
              <Text style={[styles.about, { color: theme.textSub }]}>{listing.included}</Text>
            </>
          ) : null}
        </View>
      </ScrollView>

      {/* Sticky Book now bar */}
      <View
        style={[
          styles.bookBar,
          { backgroundColor: theme.bg, borderTopColor: theme.border, paddingBottom: Math.max(insets.bottom, 12) + 10 },
        ]}
      >
        <View>
          <Text style={[styles.bookPriceLabel, { color: theme.textSub }]}>Starting from</Text>
          <Text style={[styles.bookPrice, { color: theme.textMain }]}>
            {priceLabel}
            {listing.price > 0 ? (
              <Text style={[styles.bookPer, { color: theme.textSub }]}> / {listing.price_unit || 'person'}</Text>
            ) : null}
          </Text>
        </View>
        <TouchableOpacity
          style={[styles.bookButton, { backgroundColor: theme.accent }]}
          onPress={openBooking}
          activeOpacity={0.85}
        >
          <Text style={styles.bookButtonText}>Book now</Text>
        </TouchableOpacity>
      </View>

      {/* Booking modal (multi-step) */}
      <Modal visible={modalOpen} transparent animationType="slide" onRequestClose={() => setModalOpen(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, { backgroundColor: theme.bg }]}>
            <View style={styles.modalHeader}>
              {step !== 'details' ? (
                <TouchableOpacity
                  onPress={() => setStep(step === 'method' ? 'details' : 'method')}
                  style={{ marginRight: 8 }}
                >
                  <Ionicons name="chevron-back" size={24} color={theme.textSub} />
                </TouchableOpacity>
              ) : null}
              <Text style={[styles.modalTitle, { color: theme.textMain }]}>
                {step === 'details' ? `Book ${listing.title}` : step === 'method' ? 'Payment method' : step === 'qr' ? 'Scan to pay' : 'Card payment'}
              </Text>
              <TouchableOpacity onPress={() => setModalOpen(false)}>
                <Ionicons name="close" size={24} color={theme.textSub} />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
              {/* ── STEP 1: Date + Guests ── */}
              {step === 'details' ? (
                <>
                  <Text style={[styles.modalLabel, { color: theme.textSub }]}>Select a date</Text>
                  <CalendarPicker value={bookingDate} onChange={setBookingDate} theme={theme} />

                  <Text style={[styles.modalLabel, { color: theme.textSub }]}>Guests</Text>
                  <View style={styles.guestRow}>
                    <TouchableOpacity
                      style={[styles.stepBtn, { borderColor: theme.border }]}
                      onPress={() => setGuests((g) => Math.max(1, g - 1))}
                    >
                      <Ionicons name="remove" size={20} color={theme.textMain} />
                    </TouchableOpacity>
                    <Text style={[styles.guestCount, { color: theme.textMain }]}>{guests}</Text>
                    <TouchableOpacity
                      style={[styles.stepBtn, { borderColor: theme.border }]}
                      onPress={() => setGuests((g) => g + 1)}
                    >
                      <Ionicons name="add" size={20} color={theme.textMain} />
                    </TouchableOpacity>
                  </View>

                  {listing.price > 0 ? (
                    <Text style={[styles.totalLine, { color: theme.textMain }]}>
                      {`Total: ${formatPrice(totalAmount)}`}
                    </Text>
                  ) : null}

                  <TouchableOpacity
                    style={[styles.confirmBtn, { backgroundColor: theme.accent }, booking && { opacity: 0.6 }]}
                    onPress={goToPayment}
                    disabled={booking}
                    activeOpacity={0.85}
                  >
                    <Text style={styles.confirmBtnText}>
                      {totalAmount > 0 ? 'Proceed to payment' : booking ? 'Booking...' : 'Confirm booking'}
                    </Text>
                  </TouchableOpacity>
                </>
              ) : null}

              {/* ── STEP 2: Choose payment method ── */}
              {step === 'method' ? (
                <>
                  <Text style={[styles.payTotal, { color: theme.textMain }]}>
                    Amount to pay: <Text style={{ color: theme.accent }}>{formatPrice(totalAmount)}</Text>
                  </Text>

                  <TouchableOpacity
                    style={[styles.methodRow, { borderColor: theme.border, backgroundColor: theme.card }]}
                    activeOpacity={0.8}
                    onPress={() => { setQrMethod('gcash'); setPaymentReference(''); setStep('qr'); }}
                  >
                    <View style={[styles.methodIcon, { backgroundColor: '#E0EDFF' }]}>
                      <Ionicons name="qr-code" size={22} color="#1d4ed8" />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.methodTitle, { color: theme.textMain }]}>GCash / e-Wallet</Text>
                      <Text style={[styles.methodSub, { color: theme.textSub }]}>Scan the QR with your e-wallet app</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={20} color={theme.textSub} />
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.methodRow, { borderColor: theme.border, backgroundColor: theme.card }]}
                    activeOpacity={0.8}
                    onPress={() => { setQrMethod('instapay'); setPaymentReference(''); setStep('qr'); }}
                  >
                    <View style={[styles.methodIcon, { backgroundColor: '#FEE2E2' }]}>
                      <Ionicons name="qr-code" size={22} color="#dc2626" />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.methodTitle, { color: theme.textMain }]}>InstaPay</Text>
                      <Text style={[styles.methodSub, { color: theme.textSub }]}>Scan with any bank app via InstaPay</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={20} color={theme.textSub} />
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.methodRow, { borderColor: theme.border, backgroundColor: theme.card }]}
                    activeOpacity={0.8}
                    onPress={() => setStep('card')}
                  >
                    <View style={[styles.methodIcon, { backgroundColor: '#D1FAE5' }]}>
                      <Ionicons name="card" size={22} color="#059669" />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.methodTitle, { color: theme.textMain }]}>Debit / Credit Card</Text>
                      <Text style={[styles.methodSub, { color: theme.textSub }]}>Visa, Mastercard, etc.</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={20} color={theme.textSub} />
                  </TouchableOpacity>
                </>
              ) : null}

              {/* ── STEP 3a: QR payment ── */}
              {step === 'qr' ? (
                <>
                  <Text style={[styles.payTotal, { color: theme.textMain }]}>
                    Pay <Text style={{ color: theme.accent }}>{formatPrice(totalAmount)}</Text> via{' '}
                    {qrMethod === 'gcash' ? 'GCash / e-Wallet' : 'InstaPay'}
                  </Text>

                  <View style={styles.qrWrap}>
                    <Image
                      source={qrMethod === 'gcash' ? QR_EWALLET : QR_INSTAPAY}
                      style={styles.qrImage}
                      resizeMode="contain"
                    />
                  </View>
                  <Text style={[styles.qrHint, { color: theme.textSub }]}>
                    Open your app, scan this QR, pay the exact amount, then enter your reference number below.
                  </Text>

                  <Text style={[styles.modalLabel, { color: theme.textSub }]}>Reference / confirmation no.</Text>
                  <TextInput
                    style={[styles.input, { backgroundColor: theme.inputBg, color: theme.textMain }]}
                    placeholder="e.g. 1234567890"
                    placeholderTextColor={theme.textSub}
                    value={paymentReference}
                    onChangeText={setPaymentReference}
                    autoCapitalize="characters"
                  />

                  <TouchableOpacity
                    style={[styles.confirmBtn, { backgroundColor: theme.accent }, booking && { opacity: 0.6 }]}
                    onPress={confirmQrPaid}
                    disabled={booking}
                    activeOpacity={0.85}
                  >
                    <Text style={styles.confirmBtnText}>{booking ? 'Processing...' : "I've paid"}</Text>
                  </TouchableOpacity>
                </>
              ) : null}

              {/* ── STEP 3b: Card payment ── */}
              {step === 'card' ? (
                <>
                  <Text style={[styles.payTotal, { color: theme.textMain }]}>
                    Pay <Text style={{ color: theme.accent }}>{formatPrice(totalAmount)}</Text>
                  </Text>

                  <Text style={[styles.modalLabel, { color: theme.textSub }]}>Cardholder name</Text>
                  <TextInput
                    style={[styles.input, { backgroundColor: theme.inputBg, color: theme.textMain }]}
                    placeholder="Name on card"
                    placeholderTextColor={theme.textSub}
                    value={cardName}
                    onChangeText={setCardName}
                    autoCapitalize="words"
                  />

                  <Text style={[styles.modalLabel, { color: theme.textSub }]}>Card number</Text>
                  <TextInput
                    style={[styles.input, { backgroundColor: theme.inputBg, color: theme.textMain }]}
                    placeholder="1234 5678 9012 3456"
                    placeholderTextColor={theme.textSub}
                    value={cardNumber}
                    onChangeText={formatCardNumber}
                    keyboardType="number-pad"
                  />

                  <View style={styles.cardRow}>
                    <View style={{ flex: 1, marginRight: 10 }}>
                      <Text style={[styles.modalLabel, { color: theme.textSub }]}>Expiry (MM/YY)</Text>
                      <TextInput
                        style={[styles.input, { backgroundColor: theme.inputBg, color: theme.textMain }]}
                        placeholder="MM/YY"
                        placeholderTextColor={theme.textSub}
                        value={cardExpiry}
                        onChangeText={formatExpiry}
                        keyboardType="number-pad"
                      />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.modalLabel, { color: theme.textSub }]}>CVV</Text>
                      <TextInput
                        style={[styles.input, { backgroundColor: theme.inputBg, color: theme.textMain }]}
                        placeholder="123"
                        placeholderTextColor={theme.textSub}
                        value={cardCvv}
                        onChangeText={(t) => setCardCvv(t.replace(/\D/g, '').slice(0, 4))}
                        keyboardType="number-pad"
                        secureTextEntry
                      />
                    </View>
                  </View>

                  <TouchableOpacity
                    style={[styles.confirmBtn, { backgroundColor: theme.accent }, booking && { opacity: 0.6 }]}
                    onPress={confirmCardPaid}
                    disabled={booking}
                    activeOpacity={0.85}
                  >
                    <Text style={styles.confirmBtnText}>{booking ? 'Processing...' : `Pay ${formatPrice(totalAmount)}`}</Text>
                  </TouchableOpacity>
                </>
              ) : null}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  center: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  hero: { width: '100%', height: 300, resizeMode: 'cover', backgroundColor: '#00000022' },
  heroOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0,0,0,0.28)' },
  backButton: {
    position: 'absolute',
    top: 44,
    left: 16,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(0,0,0,0.4)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroTextWrap: { position: 'absolute', bottom: 18, left: 18, right: 18 },
  areaChip: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(0,0,0,0.45)',
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 20,
    marginBottom: 8,
  },
  areaChipText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700', marginLeft: 4 },
  heroTitle: { color: '#FFFFFF', fontSize: 26, fontWeight: '900', letterSpacing: -0.5 },
  body: { padding: 20 },
  factsRow: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  factCard: { flex: 1, borderRadius: 14, borderWidth: 1, paddingVertical: 14, paddingHorizontal: 14 },
  factLabel: { fontSize: 12, marginTop: 6 },
  factValue: { fontSize: 15, fontWeight: '700', marginTop: 2 },
  metaLine: { fontSize: 13, fontWeight: '600' },
  sectionTitle: { fontSize: 17, fontWeight: '800', marginTop: 20, marginBottom: 10 },
  about: { fontSize: 14, lineHeight: 22 },
  bookBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 26,
    borderTopWidth: 1,
  },
  bookPriceLabel: { fontSize: 11 },
  bookPrice: { fontSize: 18, fontWeight: '800' },
  bookPer: { fontSize: 12, fontWeight: '500' },
  bookButton: { borderRadius: 14, paddingVertical: 14, paddingHorizontal: 36 },
  bookButtonText: { color: '#FFFFFF', fontSize: 15, fontWeight: '700' },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalCard: { borderTopLeftRadius: 22, borderTopRightRadius: 22, padding: 22, paddingBottom: 34, maxHeight: '90%' },
  modalHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 },
  modalTitle: { fontSize: 18, fontWeight: '800', flex: 1, marginRight: 12 },
  modalLabel: { fontSize: 13, fontWeight: '600', marginBottom: 6, marginTop: 12 },
  input: { borderRadius: 12, paddingVertical: 13, paddingHorizontal: 16, fontSize: 15 },
  guestRow: { flexDirection: 'row', alignItems: 'center' },
  stepBtn: { width: 42, height: 42, borderRadius: 12, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
  guestCount: { fontSize: 18, fontWeight: '800', marginHorizontal: 20 },
  totalLine: { fontSize: 16, fontWeight: '800', marginTop: 18 },
  confirmBtn: { borderRadius: 14, paddingVertical: 15, alignItems: 'center', marginTop: 20 },
  confirmBtnText: { color: '#FFFFFF', fontSize: 15, fontWeight: '700' },
  payTotal: { fontSize: 16, fontWeight: '700', marginBottom: 16 },
  methodRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
  },
  methodIcon: { width: 44, height: 44, borderRadius: 12, alignItems: 'center', justifyContent: 'center', marginRight: 14 },
  methodTitle: { fontSize: 15, fontWeight: '700' },
  methodSub: { fontSize: 12, marginTop: 2 },
  qrWrap: { alignItems: 'center', backgroundColor: '#FFFFFF', borderRadius: 16, padding: 14, marginBottom: 12 },
  qrImage: { width: 230, height: 230 },
  qrHint: { fontSize: 13, lineHeight: 19, marginBottom: 4, textAlign: 'center' },
  cardRow: { flexDirection: 'row' },
});
