import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

// ── Currencies ───────────────────────────────────────────────────────────────
// Prices from the backend are in PHP. `rate` = how many PHP equal 1 unit of the
// currency, so converted = phpAmount / rate. Rates are approximate.

export type CurrencyCode = string;

type CurrencyInfo = { code: string; symbol: string; name: string; rate: number; decimals: number };

const CURRENCY_DATA: CurrencyInfo[] = [
  { code: 'PHP', symbol: '\u20B1', name: 'Philippine Peso', rate: 1, decimals: 0 },
  { code: 'USD', symbol: '$', name: 'US Dollar', rate: 56, decimals: 2 },
  { code: 'EUR', symbol: '\u20AC', name: 'Euro', rate: 60.5, decimals: 2 },
  { code: 'GBP', symbol: '\u00A3', name: 'British Pound', rate: 71, decimals: 2 },
  { code: 'JPY', symbol: '\u00A5', name: 'Japanese Yen', rate: 0.375, decimals: 0 },
  { code: 'CNY', symbol: '\u00A5', name: 'Chinese Yuan', rate: 7.7, decimals: 2 },
  { code: 'KRW', symbol: '\u20A9', name: 'South Korean Won', rate: 0.042, decimals: 0 },
  { code: 'HKD', symbol: 'HK$', name: 'Hong Kong Dollar', rate: 7.17, decimals: 2 },
  { code: 'TWD', symbol: 'NT$', name: 'New Taiwan Dollar', rate: 1.74, decimals: 0 },
  { code: 'SGD', symbol: 'S$', name: 'Singapore Dollar', rate: 41.4, decimals: 2 },
  { code: 'MYR', symbol: 'RM', name: 'Malaysian Ringgit', rate: 11.8, decimals: 2 },
  { code: 'THB', symbol: '\u0E3F', name: 'Thai Baht', rate: 1.57, decimals: 0 },
  { code: 'IDR', symbol: 'Rp', name: 'Indonesian Rupiah', rate: 0.0035, decimals: 0 },
  { code: 'VND', symbol: '\u20AB', name: 'Vietnamese Dong', rate: 0.0022, decimals: 0 },
  { code: 'INR', symbol: '\u20B9', name: 'Indian Rupee', rate: 0.672, decimals: 0 },
  { code: 'AUD', symbol: 'A$', name: 'Australian Dollar', rate: 37, decimals: 2 },
  { code: 'NZD', symbol: 'NZ$', name: 'New Zealand Dollar', rate: 34, decimals: 2 },
  { code: 'CAD', symbol: 'C$', name: 'Canadian Dollar', rate: 41, decimals: 2 },
  { code: 'CHF', symbol: 'CHF ', name: 'Swiss Franc', rate: 62.7, decimals: 2 },
  { code: 'SEK', symbol: 'kr', name: 'Swedish Krona', rate: 5.3, decimals: 2 },
  { code: 'NOK', symbol: 'kr', name: 'Norwegian Krone', rate: 5.2, decimals: 2 },
  { code: 'DKK', symbol: 'kr', name: 'Danish Krone', rate: 8.1, decimals: 2 },
  { code: 'RUB', symbol: '\u20BD', name: 'Russian Ruble', rate: 0.62, decimals: 2 },
  { code: 'AED', symbol: 'AED ', name: 'UAE Dirham', rate: 15.2, decimals: 2 },
  { code: 'SAR', symbol: 'SAR ', name: 'Saudi Riyal', rate: 14.9, decimals: 2 },
  { code: 'QAR', symbol: 'QAR ', name: 'Qatari Riyal', rate: 15.4, decimals: 2 },
  { code: 'BRL', symbol: 'R$', name: 'Brazilian Real', rate: 11.2, decimals: 2 },
  { code: 'MXN', symbol: 'MX$', name: 'Mexican Peso', rate: 3.25, decimals: 2 },
  { code: 'ZAR', symbol: 'R', name: 'South African Rand', rate: 3.0, decimals: 2 },
  { code: 'TRY', symbol: '\u20BA', name: 'Turkish Lira', rate: 1.74, decimals: 2 },
  { code: 'PLN', symbol: 'z\u0142', name: 'Polish Zloty', rate: 14, decimals: 2 },
  { code: 'CZK', symbol: 'K\u010D', name: 'Czech Koruna', rate: 2.4, decimals: 2 },
  { code: 'HUF', symbol: 'Ft', name: 'Hungarian Forint', rate: 0.157, decimals: 0 },
  { code: 'ILS', symbol: '\u20AA', name: 'Israeli Shekel', rate: 15.1, decimals: 2 },
  { code: 'EGP', symbol: 'E\u00A3', name: 'Egyptian Pound', rate: 1.18, decimals: 2 },
];

export const CURRENCIES: Record<string, CurrencyInfo> = Object.fromEntries(
  CURRENCY_DATA.map((c) => [c.code, c])
);

export const CURRENCY_LIST = CURRENCY_DATA;

// ── Languages ────────────────────────────────────────────────────────────────

export type LanguageCode = string;

export const LANGUAGES: { code: string; label: string; locale: string }[] = [
  { code: 'en', label: 'English (US)', locale: 'en-US' },
  { code: 'es', label: 'Espa\u00F1ol', locale: 'es-ES' },
  { code: 'fr', label: 'Fran\u00E7ais', locale: 'fr-FR' },
  { code: 'de', label: 'Deutsch', locale: 'de-DE' },
  { code: 'it', label: 'Italiano', locale: 'it-IT' },
  { code: 'pt', label: 'Portugu\u00EAs', locale: 'pt-BR' },
  { code: 'nl', label: 'Nederlands', locale: 'nl-NL' },
  { code: 'ru', label: '\u0420\u0443\u0441\u0441\u043A\u0438\u0439', locale: 'ru-RU' },
  { code: 'tr', label: 'T\u00FCrk\u00E7e', locale: 'tr-TR' },
  { code: 'ar', label: '\u0627\u0644\u0639\u0631\u0628\u064A\u0629', locale: 'ar-SA' },
  { code: 'hi', label: '\u0939\u093F\u0928\u094D\u0926\u0940', locale: 'hi-IN' },
  { code: 'id', label: 'Bahasa Indonesia', locale: 'id-ID' },
  { code: 'th', label: '\u0E44\u0E17\u0E22', locale: 'th-TH' },
  { code: 'vi', label: 'Ti\u1EBFng Vi\u1EC7t', locale: 'vi-VN' },
  { code: 'fil', label: 'Filipino', locale: 'fil-PH' },
  { code: 'zh', label: '\u4E2D\u6587 (\u7B80\u4F53)', locale: 'zh-CN' },
  { code: 'zh-Hant', label: '\u4E2D\u6587 (\u7E41\u9AD4)', locale: 'zh-TW' },
  { code: 'ja', label: '\u65E5\u672C\u8A9E', locale: 'ja-JP' },
  { code: 'ko', label: '\uD55C\uAD6D\uC5B4', locale: 'ko-KR' },
];

type Dict = Record<string, string>;

const TRANSLATIONS: Record<string, Dict> = {
  en: {
    tab_home: 'Home', tab_wishlist: 'Wishlist', tab_sale: 'Sale', tab_trips: 'Trips', tab_account: 'Account',
    account_title: 'Account', welcome_guest: 'Welcome to GuideMate',
    guest_sub: 'Sign in to book, save, and manage trips', signin_btn: 'Sign in / Register',
    logout: 'Log out', explore: 'Explore Cebu', bookings: 'My bookings', saved: 'Saved', settings: 'Settings',
    settings_title: 'Settings', sec_account: 'Account settings', login_methods: 'Login methods',
    account_security: 'Account security', fingerprint: 'Fingerprint', not_enabled: 'Not enabled', enabled: 'Enabled',
    sec_prefs: 'Preferences', language: 'Language', currency: 'Currency', notifications: 'Notification settings',
    sec_others: 'Others', feedback: 'Leave feedback', about: 'About',
    choose_language: 'Choose language', choose_currency: 'Choose currency', app_version: 'Version',
    search_ph: 'Search places in Cebu', recommended: 'Recommended in Cebu', see_all: 'See all', free: 'Free',
  },
  es: {
    tab_home: 'Inicio', tab_wishlist: 'Favoritos', tab_sale: 'Ofertas', tab_trips: 'Viajes', tab_account: 'Cuenta',
    account_title: 'Cuenta', welcome_guest: 'Bienvenido a GuideMate',
    guest_sub: 'Inicia sesión para reservar, guardar y gestionar viajes', signin_btn: 'Iniciar sesión / Registrarse',
    logout: 'Cerrar sesión', explore: 'Explorar Cebú', bookings: 'Mis reservas', saved: 'Guardado', settings: 'Configuración',
    settings_title: 'Configuración', sec_account: 'Configuración de la cuenta', login_methods: 'Métodos de inicio de sesión',
    account_security: 'Seguridad de la cuenta', fingerprint: 'Huella digital', not_enabled: 'No habilitado',
    sec_prefs: 'Preferencias', language: 'Idioma', currency: 'Moneda', notifications: 'Configuración de notificaciones',
    sec_others: 'Otros', feedback: 'Dejar comentarios', about: 'Acerca de',
    choose_language: 'Elegir idioma', choose_currency: 'Elegir moneda', app_version: 'Versión',
    search_ph: 'Buscar lugares en Cebú', recommended: 'Recomendado en Cebú', see_all: 'Ver todo', free: 'Gratis',
  },
  fr: {
    tab_home: 'Accueil', tab_wishlist: 'Favoris', tab_sale: 'Offres', tab_trips: 'Voyages', tab_account: 'Compte',
    account_title: 'Compte', welcome_guest: 'Bienvenue sur GuideMate',
    guest_sub: 'Connectez-vous pour réserver, enregistrer et gérer vos voyages', signin_btn: 'Se connecter / S\u2019inscrire',
    logout: 'Se déconnecter', explore: 'Explorer Cebu', bookings: 'Mes réservations', saved: 'Enregistré', settings: 'Paramètres',
    settings_title: 'Paramètres', sec_account: 'Paramètres du compte', login_methods: 'Méthodes de connexion',
    account_security: 'Sécurité du compte', fingerprint: 'Empreinte digitale', not_enabled: 'Non activé',
    sec_prefs: 'Préférences', language: 'Langue', currency: 'Devise', notifications: 'Paramètres de notification',
    sec_others: 'Autres', feedback: 'Laisser un commentaire', about: 'À propos',
    choose_language: 'Choisir la langue', choose_currency: 'Choisir la devise', app_version: 'Version',
    search_ph: 'Rechercher des lieux à Cebu', recommended: 'Recommandé à Cebu', see_all: 'Tout voir', free: 'Gratuit',
  },
  de: {
    tab_home: 'Startseite', tab_wishlist: 'Merkliste', tab_sale: 'Angebote', tab_trips: 'Reisen', tab_account: 'Konto',
    account_title: 'Konto', welcome_guest: 'Willkommen bei GuideMate',
    guest_sub: 'Melde dich an, um zu buchen, zu speichern und Reisen zu verwalten', signin_btn: 'Anmelden / Registrieren',
    logout: 'Abmelden', explore: 'Cebu entdecken', bookings: 'Meine Buchungen', saved: 'Gespeichert', settings: 'Einstellungen',
    settings_title: 'Einstellungen', sec_account: 'Kontoeinstellungen', login_methods: 'Anmeldemethoden',
    account_security: 'Kontosicherheit', fingerprint: 'Fingerabdruck', not_enabled: 'Nicht aktiviert',
    sec_prefs: 'Präferenzen', language: 'Sprache', currency: 'Währung', notifications: 'Benachrichtigungseinstellungen',
    sec_others: 'Sonstiges', feedback: 'Feedback geben', about: 'Über',
    choose_language: 'Sprache wählen', choose_currency: 'Währung wählen', app_version: 'Version',
    search_ph: 'Orte in Cebu suchen', recommended: 'Empfohlen in Cebu', see_all: 'Alle ansehen', free: 'Kostenlos',
  },
  it: {
    tab_home: 'Home', tab_wishlist: 'Preferiti', tab_sale: 'Offerte', tab_trips: 'Viaggi', tab_account: 'Account',
    account_title: 'Account', welcome_guest: 'Benvenuto su GuideMate',
    guest_sub: 'Accedi per prenotare, salvare e gestire i viaggi', signin_btn: 'Accedi / Registrati',
    logout: 'Esci', explore: 'Esplora Cebu', bookings: 'Le mie prenotazioni', saved: 'Salvati', settings: 'Impostazioni',
    settings_title: 'Impostazioni', sec_account: 'Impostazioni account', login_methods: 'Metodi di accesso',
    account_security: 'Sicurezza account', fingerprint: 'Impronta digitale', not_enabled: 'Non abilitato',
    sec_prefs: 'Preferenze', language: 'Lingua', currency: 'Valuta', notifications: 'Impostazioni notifiche',
    sec_others: 'Altro', feedback: 'Lascia un feedback', about: 'Informazioni',
    choose_language: 'Scegli la lingua', choose_currency: 'Scegli la valuta', app_version: 'Versione',
    search_ph: 'Cerca luoghi a Cebu', recommended: 'Consigliato a Cebu', see_all: 'Vedi tutto', free: 'Gratis',
  },
  pt: {
    tab_home: 'Início', tab_wishlist: 'Favoritos', tab_sale: 'Ofertas', tab_trips: 'Viagens', tab_account: 'Conta',
    account_title: 'Conta', welcome_guest: 'Bem-vindo ao GuideMate',
    guest_sub: 'Entre para reservar, salvar e gerenciar viagens', signin_btn: 'Entrar / Registrar',
    logout: 'Sair', explore: 'Explorar Cebu', bookings: 'Minhas reservas', saved: 'Salvos', settings: 'Configurações',
    settings_title: 'Configurações', sec_account: 'Configurações da conta', login_methods: 'Métodos de login',
    account_security: 'Segurança da conta', fingerprint: 'Impressão digital', not_enabled: 'Não ativado',
    sec_prefs: 'Preferências', language: 'Idioma', currency: 'Moeda', notifications: 'Configurações de notificação',
    sec_others: 'Outros', feedback: 'Deixar feedback', about: 'Sobre',
    choose_language: 'Escolher idioma', choose_currency: 'Escolher moeda', app_version: 'Versão',
    search_ph: 'Buscar lugares em Cebu', recommended: 'Recomendado em Cebu', see_all: 'Ver tudo', free: 'Grátis',
  },
  nl: {
    tab_home: 'Home', tab_wishlist: 'Verlanglijst', tab_sale: 'Aanbiedingen', tab_trips: 'Reizen', tab_account: 'Account',
    account_title: 'Account', welcome_guest: 'Welkom bij GuideMate',
    guest_sub: 'Log in om te boeken, op te slaan en reizen te beheren', signin_btn: 'Inloggen / Registreren',
    logout: 'Uitloggen', explore: 'Ontdek Cebu', bookings: 'Mijn boekingen', saved: 'Opgeslagen', settings: 'Instellingen',
    settings_title: 'Instellingen', sec_account: 'Accountinstellingen', login_methods: 'Inlogmethoden',
    account_security: 'Accountbeveiliging', fingerprint: 'Vingerafdruk', not_enabled: 'Niet ingeschakeld',
    sec_prefs: 'Voorkeuren', language: 'Taal', currency: 'Valuta', notifications: 'Meldingsinstellingen',
    sec_others: 'Overig', feedback: 'Feedback geven', about: 'Over',
    choose_language: 'Kies taal', choose_currency: 'Kies valuta', app_version: 'Versie',
    search_ph: 'Zoek plaatsen in Cebu', recommended: 'Aanbevolen in Cebu', see_all: 'Alles bekijken', free: 'Gratis',
  },
  ru: {
    tab_home: 'Главная', tab_wishlist: 'Избранное', tab_sale: 'Скидки', tab_trips: 'Поездки', tab_account: 'Аккаунт',
    account_title: 'Аккаунт', welcome_guest: 'Добро пожаловать в GuideMate',
    guest_sub: 'Войдите, чтобы бронировать, сохранять и управлять поездками', signin_btn: 'Войти / Регистрация',
    logout: 'Выйти', explore: 'Исследовать Себу', bookings: 'Мои брони', saved: 'Сохранённое', settings: 'Настройки',
    settings_title: 'Настройки', sec_account: 'Настройки аккаунта', login_methods: 'Способы входа',
    account_security: 'Безопасность аккаунта', fingerprint: 'Отпечаток пальца', not_enabled: 'Не включено',
    sec_prefs: 'Предпочтения', language: 'Язык', currency: 'Валюта', notifications: 'Настройки уведомлений',
    sec_others: 'Другое', feedback: 'Оставить отзыв', about: 'О приложении',
    choose_language: 'Выбрать язык', choose_currency: 'Выбрать валюту', app_version: 'Версия',
    search_ph: 'Поиск мест в Себу', recommended: 'Рекомендуемое в Себу', see_all: 'Показать все', free: 'Бесплатно',
  },
  tr: {
    tab_home: 'Ana sayfa', tab_wishlist: 'İstek listesi', tab_sale: 'İndirimler', tab_trips: 'Geziler', tab_account: 'Hesap',
    account_title: 'Hesap', welcome_guest: 'GuideMate\u2019e hoş geldiniz',
    guest_sub: 'Rezervasyon yapmak, kaydetmek ve gezileri yönetmek için giriş yapın', signin_btn: 'Giriş yap / Kayıt ol',
    logout: 'Çıkış yap', explore: 'Cebu\u2019yu keşfet', bookings: 'Rezervasyonlarım', saved: 'Kaydedildi', settings: 'Ayarlar',
    settings_title: 'Ayarlar', sec_account: 'Hesap ayarları', login_methods: 'Giriş yöntemleri',
    account_security: 'Hesap güvenliği', fingerprint: 'Parmak izi', not_enabled: 'Etkin değil',
    sec_prefs: 'Tercihler', language: 'Dil', currency: 'Para birimi', notifications: 'Bildirim ayarları',
    sec_others: 'Diğer', feedback: 'Geri bildirim gönder', about: 'Hakkında',
    choose_language: 'Dil seçin', choose_currency: 'Para birimi seçin', app_version: 'Sürüm',
    search_ph: 'Cebu\u2019da yer ara', recommended: 'Cebu\u2019da önerilen', see_all: 'Tümünü gör', free: 'Ücretsiz',
  },
  ar: {
    tab_home: 'الرئيسية', tab_wishlist: 'المفضلة', tab_sale: 'عروض', tab_trips: 'الرحلات', tab_account: 'الحساب',
    account_title: 'الحساب', welcome_guest: 'مرحبًا بك في GuideMate',
    guest_sub: 'سجّل الدخول للحجز والحفظ وإدارة الرحلات', signin_btn: 'تسجيل الدخول / إنشاء حساب',
    logout: 'تسجيل الخروج', explore: 'استكشف سيبو', bookings: 'حجوزاتي', saved: 'المحفوظات', settings: 'الإعدادات',
    settings_title: 'الإعدادات', sec_account: 'إعدادات الحساب', login_methods: 'طرق تسجيل الدخول',
    account_security: 'أمان الحساب', fingerprint: 'بصمة الإصبع', not_enabled: 'غير مُفعّل',
    sec_prefs: 'التفضيلات', language: 'اللغة', currency: 'العملة', notifications: 'إعدادات الإشعارات',
    sec_others: 'أخرى', feedback: 'إرسال ملاحظات', about: 'حول',
    choose_language: 'اختر اللغة', choose_currency: 'اختر العملة', app_version: 'الإصدار',
    search_ph: 'ابحث عن أماكن في سيبو', recommended: 'موصى به في سيبو', see_all: 'عرض الكل', free: 'مجاني',
  },
  hi: {
    tab_home: 'होम', tab_wishlist: 'इच्छा-सूची', tab_sale: 'सेल', tab_trips: 'यात्राएँ', tab_account: 'खाता',
    account_title: 'खाता', welcome_guest: 'GuideMate में आपका स्वागत है',
    guest_sub: 'बुक करने, सहेजने और यात्राएँ प्रबंधित करने के लिए साइन इन करें', signin_btn: 'साइन इन / रजिस्टर',
    logout: 'लॉग आउट', explore: 'सेबू एक्सप्लोर करें', bookings: 'मेरी बुकिंग', saved: 'सहेजा गया', settings: 'सेटिंग्स',
    settings_title: 'सेटिंग्स', sec_account: 'खाता सेटिंग्स', login_methods: 'लॉगिन विधियाँ',
    account_security: 'खाता सुरक्षा', fingerprint: 'फ़िंगरप्रिंट', not_enabled: 'सक्षम नहीं',
    sec_prefs: 'प्राथमिकताएँ', language: 'भाषा', currency: 'मुद्रा', notifications: 'सूचना सेटिंग्स',
    sec_others: 'अन्य', feedback: 'प्रतिक्रिया दें', about: 'परिचय',
    choose_language: 'भाषा चुनें', choose_currency: 'मुद्रा चुनें', app_version: 'संस्करण',
    search_ph: 'सेबू में स्थान खोजें', recommended: 'सेबू में अनुशंसित', see_all: 'सभी देखें', free: 'निःशुल्क',
  },
  id: {
    tab_home: 'Beranda', tab_wishlist: 'Favorit', tab_sale: 'Promo', tab_trips: 'Perjalanan', tab_account: 'Akun',
    account_title: 'Akun', welcome_guest: 'Selamat datang di GuideMate',
    guest_sub: 'Masuk untuk memesan, menyimpan, dan mengelola perjalanan', signin_btn: 'Masuk / Daftar',
    logout: 'Keluar', explore: 'Jelajahi Cebu', bookings: 'Pesanan saya', saved: 'Tersimpan', settings: 'Pengaturan',
    settings_title: 'Pengaturan', sec_account: 'Pengaturan akun', login_methods: 'Metode masuk',
    account_security: 'Keamanan akun', fingerprint: 'Sidik jari', not_enabled: 'Tidak aktif',
    sec_prefs: 'Preferensi', language: 'Bahasa', currency: 'Mata uang', notifications: 'Pengaturan notifikasi',
    sec_others: 'Lainnya', feedback: 'Beri masukan', about: 'Tentang',
    choose_language: 'Pilih bahasa', choose_currency: 'Pilih mata uang', app_version: 'Versi',
    search_ph: 'Cari tempat di Cebu', recommended: 'Direkomendasikan di Cebu', see_all: 'Lihat semua', free: 'Gratis',
  },
  th: {
    tab_home: 'หน้าแรก', tab_wishlist: 'รายการโปรด', tab_sale: 'โปรโมชั่น', tab_trips: 'ทริป', tab_account: 'บัญชี',
    account_title: 'บัญชี', welcome_guest: 'ยินดีต้อนรับสู่ GuideMate',
    guest_sub: 'ลงชื่อเข้าใช้เพื่อจอง บันทึก และจัดการทริป', signin_btn: 'เข้าสู่ระบบ / ลงทะเบียน',
    logout: 'ออกจากระบบ', explore: 'สำรวจเซบู', bookings: 'การจองของฉัน', saved: 'บันทึกแล้ว', settings: 'การตั้งค่า',
    settings_title: 'การตั้งค่า', sec_account: 'การตั้งค่าบัญชี', login_methods: 'วิธีเข้าสู่ระบบ',
    account_security: 'ความปลอดภัยบัญชี', fingerprint: 'ลายนิ้วมือ', not_enabled: 'ยังไม่เปิดใช้งาน',
    sec_prefs: 'การกำหนดลักษณะ', language: 'ภาษา', currency: 'สกุลเงิน', notifications: 'การตั้งค่าการแจ้งเตือน',
    sec_others: 'อื่นๆ', feedback: 'ส่งความคิดเห็น', about: 'เกี่ยวกับ',
    choose_language: 'เลือกภาษา', choose_currency: 'เลือกสกุลเงิน', app_version: 'เวอร์ชัน',
    search_ph: 'ค้นหาสถานที่ในเซบู', recommended: 'แนะนำในเซบู', see_all: 'ดูทั้งหมด', free: 'ฟรี',
  },
  vi: {
    tab_home: 'Trang chủ', tab_wishlist: 'Yêu thích', tab_sale: 'Khuyến mãi', tab_trips: 'Chuyến đi', tab_account: 'Tài khoản',
    account_title: 'Tài khoản', welcome_guest: 'Chào mừng đến với GuideMate',
    guest_sub: 'Đăng nhập để đặt, lưu và quản lý chuyến đi', signin_btn: 'Đăng nhập / Đăng ký',
    logout: 'Đăng xuất', explore: 'Khám phá Cebu', bookings: 'Đặt chỗ của tôi', saved: 'Đã lưu', settings: 'Cài đặt',
    settings_title: 'Cài đặt', sec_account: 'Cài đặt tài khoản', login_methods: 'Phương thức đăng nhập',
    account_security: 'Bảo mật tài khoản', fingerprint: 'Vân tay', not_enabled: 'Chưa bật',
    sec_prefs: 'Tùy chọn', language: 'Ngôn ngữ', currency: 'Tiền tệ', notifications: 'Cài đặt thông báo',
    sec_others: 'Khác', feedback: 'Gửi phản hồi', about: 'Giới thiệu',
    choose_language: 'Chọn ngôn ngữ', choose_currency: 'Chọn tiền tệ', app_version: 'Phiên bản',
    search_ph: 'Tìm địa điểm ở Cebu', recommended: 'Đề xuất ở Cebu', see_all: 'Xem tất cả', free: 'Miễn phí',
  },
  fil: {
    tab_home: 'Home', tab_wishlist: 'Wishlist', tab_sale: 'Sale', tab_trips: 'Mga Biyahe', tab_account: 'Account',
    account_title: 'Account', welcome_guest: 'Maligayang pagdating sa GuideMate',
    guest_sub: 'Mag-sign in para mag-book, mag-save, at pamahalaan ang mga biyahe', signin_btn: 'Mag-sign in / Magrehistro',
    logout: 'Mag-log out', explore: 'Tuklasin ang Cebu', bookings: 'Aking mga booking', saved: 'Naka-save', settings: 'Mga Setting',
    settings_title: 'Mga Setting', sec_account: 'Mga setting ng account', login_methods: 'Mga paraan ng pag-login',
    account_security: 'Seguridad ng account', fingerprint: 'Fingerprint', not_enabled: 'Hindi naka-enable',
    sec_prefs: 'Mga Kagustuhan', language: 'Wika', currency: 'Pera', notifications: 'Mga setting ng notification',
    sec_others: 'Iba pa', feedback: 'Mag-iwan ng feedback', about: 'Tungkol',
    choose_language: 'Pumili ng wika', choose_currency: 'Pumili ng pera', app_version: 'Bersyon',
    search_ph: 'Maghanap ng lugar sa Cebu', recommended: 'Inirerekomenda sa Cebu', see_all: 'Tingnan lahat', free: 'Libre',
  },
  zh: {
    tab_home: '首页', tab_wishlist: '收藏', tab_sale: '特惠', tab_trips: '行程', tab_account: '账户',
    account_title: '账户', welcome_guest: '欢迎使用 GuideMate',
    guest_sub: '登录以预订、收藏和管理行程', signin_btn: '登录 / 注册',
    logout: '退出登录', explore: '探索宿务', bookings: '我的预订', saved: '已收藏', settings: '设置',
    settings_title: '设置', sec_account: '账户设置', login_methods: '登录方式',
    account_security: '账户安全', fingerprint: '指纹', not_enabled: '未启用',
    sec_prefs: '偏好设置', language: '语言', currency: '货币', notifications: '通知设置',
    sec_others: '其他', feedback: '提交反馈', about: '关于',
    choose_language: '选择语言', choose_currency: '选择货币', app_version: '版本',
    search_ph: '搜索宿务的景点', recommended: '宿务推荐', see_all: '查看全部', free: '免费',
  },
  'zh-Hant': {
    tab_home: '首頁', tab_wishlist: '收藏', tab_sale: '特惠', tab_trips: '行程', tab_account: '帳戶',
    account_title: '帳戶', welcome_guest: '歡迎使用 GuideMate',
    guest_sub: '登入以預訂、收藏和管理行程', signin_btn: '登入 / 註冊',
    logout: '登出', explore: '探索宿霧', bookings: '我的預訂', saved: '已收藏', settings: '設定',
    settings_title: '設定', sec_account: '帳戶設定', login_methods: '登入方式',
    account_security: '帳戶安全', fingerprint: '指紋', not_enabled: '未啟用',
    sec_prefs: '偏好設定', language: '語言', currency: '貨幣', notifications: '通知設定',
    sec_others: '其他', feedback: '提交意見', about: '關於',
    choose_language: '選擇語言', choose_currency: '選擇貨幣', app_version: '版本',
    search_ph: '搜尋宿霧的景點', recommended: '宿霧推薦', see_all: '查看全部', free: '免費',
  },
  ja: {
    tab_home: 'ホーム', tab_wishlist: 'お気に入り', tab_sale: 'セール', tab_trips: '旅行', tab_account: 'アカウント',
    account_title: 'アカウント', welcome_guest: 'GuideMateへようこそ',
    guest_sub: '予約・保存・旅行管理にはログインしてください', signin_btn: 'ログイン / 登録',
    logout: 'ログアウト', explore: 'セブを探索', bookings: '予約一覧', saved: '保存済み', settings: '設定',
    settings_title: '設定', sec_account: 'アカウント設定', login_methods: 'ログイン方法',
    account_security: 'アカウントセキュリティ', fingerprint: '指紋', not_enabled: '無効',
    sec_prefs: '環境設定', language: '言語', currency: '通貨', notifications: '通知設定',
    sec_others: 'その他', feedback: 'フィードバックを送る', about: 'アプリについて',
    choose_language: '言語を選択', choose_currency: '通貨を選択', app_version: 'バージョン',
    search_ph: 'セブの場所を検索', recommended: 'セブのおすすめ', see_all: 'すべて見る', free: '無料',
  },
  ko: {
    tab_home: '홈', tab_wishlist: '위시리스트', tab_sale: '세일', tab_trips: '여행', tab_account: '계정',
    account_title: '계정', welcome_guest: 'GuideMate에 오신 것을 환영합니다',
    guest_sub: '예약, 저장 및 여행 관리를 위해 로그인하세요', signin_btn: '로그인 / 회원가입',
    logout: '로그아웃', explore: '세부 둘러보기', bookings: '내 예약', saved: '저장됨', settings: '설정',
    settings_title: '설정', sec_account: '계정 설정', login_methods: '로그인 방법',
    account_security: '계정 보안', fingerprint: '지문', not_enabled: '사용 안 함',
    sec_prefs: '환경설정', language: '언어', currency: '통화', notifications: '알림 설정',
    sec_others: '기타', feedback: '피드백 남기기', about: '정보',
    choose_language: '언어 선택', choose_currency: '통화 선택', app_version: '버전',
    search_ph: '세부에서 장소 검색', recommended: '세부 추천', see_all: '모두 보기', free: '무료',
  },
};

// ── Context ──────────────────────────────────────────────────────────────────

type PreferencesContextValue = {
  language: LanguageCode;
  currency: CurrencyCode;
  ready: boolean;
  setLanguage: (code: LanguageCode) => void;
  setCurrency: (code: CurrencyCode) => void;
  t: (key: string) => string;
  formatPrice: (phpAmount: number) => string;
};

const LANG_KEY = 'guidemate_language';
const CUR_KEY = 'guidemate_currency';

const PreferencesContext = createContext<PreferencesContextValue | undefined>(undefined);

export function PreferencesProvider({ children }: { children: React.ReactNode }) {
  const [language, setLanguageState] = useState<LanguageCode>('en');
  const [currency, setCurrencyState] = useState<CurrencyCode>('PHP');
  const [ready, setReady] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const [lang, cur] = await Promise.all([
          AsyncStorage.getItem(LANG_KEY),
          AsyncStorage.getItem(CUR_KEY),
        ]);
        if (lang && lang in TRANSLATIONS) {
          setLanguageState(lang);
        }
        if (cur && cur in CURRENCIES) {
          setCurrencyState(cur);
        }
      } catch {
        // ignore
      } finally {
        setReady(true);
      }
    })();
  }, []);

  const setLanguage = useCallback((code: LanguageCode) => {
    setLanguageState(code);
    AsyncStorage.setItem(LANG_KEY, code).catch(() => {});
  }, []);

  const setCurrency = useCallback((code: CurrencyCode) => {
    setCurrencyState(code);
    AsyncStorage.setItem(CUR_KEY, code).catch(() => {});
  }, []);

  const t = useCallback(
    (key: string) => TRANSLATIONS[language]?.[key] ?? TRANSLATIONS.en[key] ?? key,
    [language]
  );

  const formatPrice = useCallback(
    (phpAmount: number) => {
      const info = CURRENCIES[currency] ?? CURRENCIES.PHP;
      const locale = LANGUAGES.find((l) => l.code === language)?.locale ?? 'en-US';
      const value = phpAmount / info.rate;
      const formatted = value.toLocaleString(locale, {
        maximumFractionDigits: info.decimals,
        minimumFractionDigits: 0,
      });
      return `${info.symbol}${formatted}`;
    },
    [currency, language]
  );

  const value = useMemo(
    () => ({ language, currency, ready, setLanguage, setCurrency, t, formatPrice }),
    [language, currency, ready, setLanguage, setCurrency, t, formatPrice]
  );

  return <PreferencesContext.Provider value={value}>{children}</PreferencesContext.Provider>;
}

export function usePreferences(): PreferencesContextValue {
  const ctx = useContext(PreferencesContext);
  if (!ctx) {
    throw new Error('usePreferences must be used within a PreferencesProvider');
  }
  return ctx;
}
