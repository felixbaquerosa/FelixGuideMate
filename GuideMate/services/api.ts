import { API_BASE_URL } from '../config';

// Fail fast instead of hanging forever when the backend is down/unreachable.
const REQUEST_TIMEOUT_MS = 12000;

// ── Types matching the PHP API payloads (app/Controllers/ApiController.php) ──

export type ApiUser = {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar: string;
};

export type ApiCategory = {
  id: number;
  name: string;
  slug: string;
  icon: string;
  listing_count: number;
};

export type ApiListing = {
  id: number;
  title: string;
  slug: string;
  summary: string;
  area: string;
  price: number;
  price_unit: string;
  currency: string;
  rating: number;
  review_count: number;
  category: string;
  category_slug: string;
  image: string;
  featured: boolean;
  duration: string;
  owner_name: string;
  description?: string;
  included?: string;
  not_included?: string;
};

export type ApiArea = { slug: string; name: string; tagline: string };

export type ApiBooking = {
  id: number;
  listing_id: number;
  listing_title: string;
  listing_slug: string;
  image: string;
  booking_date: string;
  guests: number;
  total_amount: number;
  status: string;
  area: string;
};

export type HomePayload = {
  categories: ApiCategory[];
  featured: ApiListing[];
  recommended: ApiListing[];
  areas: ApiArea[];
};

// Origin (scheme + host) of the configured API, e.g. "http://10.0.4.99".
const API_ORIGIN = API_BASE_URL.replace(/^(https?:\/\/[^/]+).*$/, '$1');

// The backend may return image URLs pointing at "localhost" (its own machine).
// A phone can't reach that, so rewrite those to the API address instead.
// External URLs (e.g. picsum placeholders) are left untouched.
export function resolveImage(url?: string | null): string {
  if (!url) {
    return '';
  }
  return url.replace(/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?/i, API_ORIGIN);
}

// ── Token handling ──

let authToken: string | null = null;

export function setAuthToken(token: string | null) {
  authToken = token;
}

// ── Core request helper ──

type Options = {
  method?: string;
  body?: unknown;
  headers?: Record<string, string>;
};

export async function api<T = any>(path: string, options: Options = {}): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers ?? {}),
  };
  if (authToken) {
    headers.Authorization = `Bearer ${authToken}`;
  }

  let response: Response;
  // Abort the request if the server doesn't answer in time so the UI never
  // hangs on an endless spinner when XAMPP is down or unreachable.
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method: options.method ?? 'GET',
      headers,
      body: options.body ? JSON.stringify(options.body) : undefined,
      signal: controller.signal,
    });
  } catch (e) {
    if (e instanceof Error && e.name === 'AbortError') {
      throw new Error('The server took too long to respond. Make sure XAMPP/Apache is running and your phone is on the same Wi-Fi.');
    }
    throw new Error('Cannot reach the server. Check that XAMPP is running and the API address is correct.');
  } finally {
    clearTimeout(timeout);
  }

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const message = (data && (data.error || data.message)) || 'Request failed. Please try again.';
    throw new Error(message);
  }
  return data as T;
}

// ── Endpoints ──

export const getHome = () => api<HomePayload>('/api/home');

export const getListings = (params: Record<string, string> = {}) => {
  const query = new URLSearchParams(params).toString();
  return api<{ listings: ApiListing[] }>(`/api/listings${query ? `?${query}` : ''}`);
};

export const getListing = (slug: string) =>
  api<{
    listing: ApiListing;
    gallery: { id: number; url: string }[];
    reviews: { id: number; rating: number; comment: string; user_name: string; created_at: string }[];
    summary: { average: number; total: number } | any;
    favorited: boolean;
  }>(`/api/listings/${slug}`);

export const apiLogin = (email: string, password: string) =>
  api<{ token: string; user: ApiUser }>('/api/auth/login', { method: 'POST', body: { email, password } });

export const apiRegister = (name: string, email: string, password: string) =>
  api<{ token: string; user: ApiUser }>('/api/auth/register', {
    method: 'POST',
    body: { name, email, password },
  });

export const apiMe = () => api<{ user: ApiUser }>('/api/auth/me');

export const apiLogout = () => api<{ success: boolean }>('/api/auth/logout', { method: 'POST' });

export const apiChangePassword = (currentPassword: string, newPassword: string) =>
  api<{ success: boolean }>('/api/auth/change-password', {
    method: 'POST',
    body: { current_password: currentPassword, new_password: newPassword },
  });

export const apiDeleteAccount = (password: string) =>
  api<{ success: boolean }>('/api/auth/delete-account', { method: 'POST', body: { password } });

export const getBookings = () => api<{ bookings: ApiBooking[] }>('/api/bookings');

export type PaymentMethod = 'gcash' | 'instapay' | 'card';

export const createBooking = (payload: {
  listing_id: number;
  booking_date: string;
  guests: number;
  notes?: string;
  payment_method?: PaymentMethod;
  payment_reference?: string;
  card_last4?: string;
}) => api<{ booking: ApiBooking | null }>('/api/bookings', { method: 'POST', body: payload });

export const getFavorites = () => api<{ listings: ApiListing[] }>('/api/favorites');

export const toggleFavorite = (listingId: number) =>
  api<{ favorited: boolean }>('/api/favorites/toggle', {
    method: 'POST',
    body: { listing_id: listingId },
  });
