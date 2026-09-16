import AsyncStorage from '@react-native-async-storage/async-storage';
import * as AuthSession from 'expo-auth-session';
import * as Crypto from 'expo-crypto';
import * as WebBrowser from 'expo-web-browser';
import { API_BASE_URL, SOCIAL_AUTH } from '../config';

// Finish any pending web-auth session (required for the browser redirect flow).
WebBrowser.maybeCompleteAuthSession();

export type SocialProvider = 'google' | 'facebook';

export type SocialCredential =
  | { provider: SocialProvider; subject: string }
  | { provider: SocialProvider; token: string };

/**
 * A stable, anonymous per-device identity used in DEMO mode, so signing in
 * again on the same device returns the same demo account. This is NOT the
 * user's real Google/Facebook id — it's a random UUID we generate locally.
 */
async function demoSubject(provider: SocialProvider): Promise<string> {
  const key = `guidemate_demo_${provider}_id`;
  let id = await AsyncStorage.getItem(key);
  if (!id) {
    id = `${provider}-${Crypto.randomUUID()}`;
    await AsyncStorage.setItem(key, id);
  }
  return id;
}

const PROVIDER_LABEL: Record<SocialProvider, string> = {
  google: 'Google',
  facebook: 'Facebook',
};

/**
 * Real Google/Facebook sign-in that works in EXPO GO. We open our PHP backend,
 * which bounces to Google/Facebook and (after login) back to the app with a
 * ready-made GuideMate token. Returns that app token. The provider never sees
 * the phone — only the backend's public https callback — so no dev build /
 * SHA-1 / native config is needed.
 */
export async function backendOAuthSignIn(provider: SocialProvider): Promise<string> {
  const label = PROVIDER_LABEL[provider];
  const returnUrl = AuthSession.makeRedirectUri({ path: `auth/${provider}` });
  const base = (SOCIAL_AUTH.backendPublicUrl || API_BASE_URL).replace(/\/+$/, '');
  const startUrl = `${base}/api/auth/${provider}/start?return=${encodeURIComponent(returnUrl)}`;

  const result = await WebBrowser.openAuthSessionAsync(startUrl, returnUrl);
  if (result.type !== 'success' || !result.url) {
    throw new Error(`${label} sign-in was cancelled.`);
  }
  const err = /[?&]error=([^&#]+)/.exec(result.url);
  if (err) throw new Error(decodeURIComponent(err[1]));
  const match = /[?&]token=([^&#]+)/.exec(result.url);
  if (!match) throw new Error(`${label} sign-in did not complete. Please try again.`);
  return decodeURIComponent(match[1]);
}

/** Back-compat alias kept for existing imports. */
export function backendGoogleSignIn(): Promise<string> {
  return backendOAuthSignIn('google');
}

/**
 * True once you've pasted real credentials for this provider in config.ts.
 * When configured, tapping the button opens the REAL Google/Facebook login;
 * otherwise it falls back to the anonymous demo account.
 */
export function isProviderConfigured(provider: SocialProvider): boolean {
  if (provider === 'facebook') {
    return SOCIAL_AUTH.facebookAppId.trim() !== '';
  }
  const g = SOCIAL_AUTH.googleClientIds;
  return [g.expo, g.ios, g.android, g.web].some((id) => (id ?? '').trim() !== '');
}

/**
 * Obtain a credential to send to POST /api/auth/social.
 *
 * When a provider is configured, real login runs through backendOAuthSignIn()
 * (the Expo Go-safe browser flow), so this helper is only reached for the
 * demo fallback and always returns the anonymous per-device identity.
 */
export async function resolveSocialCredential(provider: SocialProvider): Promise<SocialCredential> {
  return { provider, subject: await demoSubject(provider) };
}
