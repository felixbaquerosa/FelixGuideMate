import Constants from 'expo-constants';

// Base URL of your GuideMate PHP web backend.
//
// In development we AUTO-DETECT your PC's LAN IP from the Expo dev server that
// Expo Go is already connected to. That means when your Wi-Fi IP changes
// (DHCP), the API address follows automatically — no manual edits needed.
//
// For a production build, set PROD_API_BASE_URL to your real hosted URL.

const PROD_API_BASE_URL = 'https://yourdomain.com/GuideMate/public';

// Fallback used only if the dev host can't be detected (e.g. tunnel mode).
const FALLBACK_DEV_IP = '10.0.4.99';

function detectDevHost(): string {
  // Expo exposes the dev server as "<ip>:<port>" across SDK versions.
  const hostUri =
    Constants.expoConfig?.hostUri ||
    (Constants as any).expoGoConfig?.debuggerHost ||
    (Constants as any).manifest2?.extra?.expoGo?.debuggerHost ||
    (Constants as any).manifest?.debuggerHost ||
    '';
  const host = String(hostUri).split(':')[0];
  return host || FALLBACK_DEV_IP;
}

export const API_BASE_URL = __DEV__
  ? `http://${detectDevHost()}/GuideMate/public`
  : PROD_API_BASE_URL;
