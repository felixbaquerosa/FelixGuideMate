import AsyncStorage from '@react-native-async-storage/async-storage';
import {
  apiChangePassword,
  apiDeleteAccount,
  apiLogin,
  apiLogout,
  apiRegister,
  ApiUser,
  setAuthToken,
} from '../services/api';
import {
  canUseBiometrics,
  clearSavedCredentials,
  getSavedCredentials,
  isBiometricEnabled,
  promptBiometric,
  saveCredentials,
  setBiometricEnabled,
} from './biometric';

// Authentication backed by the GuideMate PHP API.
// The bearer token + the signed-in user are cached on the device so the
// session survives app restarts.

const TOKEN_KEY = 'guidemate_token';
const USER_KEY = 'guidemate_user';

export type SessionUser = {
  id: number;
  fullName: string;
  email: string;
  role: string;
  avatar: string;
};

function toSession(user: ApiUser): SessionUser {
  return {
    id: user.id,
    fullName: user.name,
    email: user.email,
    role: user.role,
    avatar: user.avatar,
  };
}

async function persist(token: string, user: ApiUser): Promise<SessionUser> {
  const session = toSession(user);
  setAuthToken(token);
  await AsyncStorage.setItem(TOKEN_KEY, token);
  await AsyncStorage.setItem(USER_KEY, JSON.stringify(session));
  return session;
}

// Restore a cached token at app startup so authenticated calls keep working.
export async function restoreSession(): Promise<SessionUser | null> {
  try {
    const token = await AsyncStorage.getItem(TOKEN_KEY);
    const rawUser = await AsyncStorage.getItem(USER_KEY);
    if (token && rawUser) {
      setAuthToken(token);
      return JSON.parse(rawUser) as SessionUser;
    }
  } catch {
    // ignore
  }
  return null;
}

export async function login(email: string, password: string): Promise<SessionUser> {
  const data = await apiLogin(email, password);
  const session = await persist(data.token, data.user);
  // Remember credentials so the user can sign back in with fingerprint only.
  await saveCredentials(email, password, session.fullName);
  return session;
}

export async function register(
  fullName: string,
  email: string,
  password: string
): Promise<SessionUser> {
  const data = await apiRegister(fullName, email, password);
  const session = await persist(data.token, data.user);
  await saveCredentials(email, password, session.fullName);
  return session;
}

// True when a fingerprint login is possible: the user opted in, the device has
// an enrolled sensor, and we have saved credentials to replay.
export async function hasBiometricLogin(): Promise<boolean> {
  try {
    if (!(await isBiometricEnabled())) return false;
    if (!(await canUseBiometrics())) return false;
    return (await getSavedCredentials()) !== null;
  } catch {
    return false;
  }
}

// Prompt for fingerprint, then sign in using the saved account. No password
// needed.
export async function loginWithBiometrics(): Promise<SessionUser> {
  const creds = await getSavedCredentials();
  if (!creds) {
    throw new Error('No saved account on this device. Please log in with your password.');
  }
  const ok = await promptBiometric('Log in to GuideMate');
  if (!ok) {
    throw new Error('Fingerprint not recognized.');
  }
  return login(creds.email, creds.password);
}

export async function getSession(): Promise<SessionUser | null> {
  try {
    const raw = await AsyncStorage.getItem(USER_KEY);
    return raw ? (JSON.parse(raw) as SessionUser) : null;
  } catch {
    return null;
  }
}

export async function logout(): Promise<void> {
  try {
    await apiLogout();
  } catch {
    // ignore network errors on logout
  }
  setAuthToken(null);
  await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
}

export async function changePassword(currentPassword: string, newPassword: string): Promise<void> {
  await apiChangePassword(currentPassword, newPassword);
}

export async function deleteAccount(password: string): Promise<void> {
  await apiDeleteAccount(password);
  setAuthToken(null);
  await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
  // Wipe fingerprint login for the deleted account.
  await setBiometricEnabled(false);
  await clearSavedCredentials();
}
