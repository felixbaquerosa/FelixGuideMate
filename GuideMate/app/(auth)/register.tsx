import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import {
    Dimensions,
    ImageBackground,
    KeyboardAvoidingView,
    Platform,
    SafeAreaView,
    ScrollView,
    StatusBar,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';

const { height } = Dimensions.get('window');

export default function RegisterScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  // Dynamic colors mapping based on system device theme
  const theme = {
    bg: isDark ? '#111114' : '#FFFFFF',
    inputBg: isDark ? '#1E2029' : '#F1F5F9',
    textMain: isDark ? '#FFFFFF' : '#1A202C',
    textSub: isDark ? '#6B7280' : '#888888',
    placeholderColor: isDark ? '#5A6070' : '#A0AEC0',
    dividerLine: isDark ? '#2A2D38' : '#E2E8F0',
    accent: '#22C55E', // Premium Green brand color
  };

  const handleClose = () => {
    router.back();
  };

  const handleGoogle = () => {
    console.log('Continue with Google');
  };

  const handleFacebook = () => {
    console.log('Continue with Facebook');
  };

  const handleRegister = () => {
    if (fullName.trim() && email.trim() && password.trim()) {
      router.replace('./(tabs)');
    }
  };

  const handleLogin = () => {
    router.push('/(auth)/login');
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <StatusBar barStyle={isDark ? 'light-content' : 'dark-content'} />

      {/* ── TOP HALF: Hero Image ── */}
      <ImageBackground
        source={require('../../assets/images/pexels-thefullonmonet-20233772.jpg')}
        style={styles.heroImage}
        resizeMode="cover"
      >
        <View style={styles.overlay} />

        <View style={styles.brandContainer}>
          <Text style={styles.brandText}>GUIDE{'\n'}MATE</Text>
        </View>

        <View style={styles.taglineContainer}>
          <Text style={styles.taglineText}>Your journey starts here.</Text>
          <Text style={styles.taglineSubText}>Create an account to explore.</Text>
        </View>

        <TouchableOpacity style={styles.closeButton} onPress={handleClose} activeOpacity={0.8}>
          <Ionicons name="close" size={18} color="#FFFFFF" />
        </TouchableOpacity>
      </ImageBackground>

      {/* ── BOTTOM HALF: Register Panel ── */}
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      >
        <ScrollView
          style={[styles.authPanel, { backgroundColor: theme.bg }]}
          contentContainerStyle={styles.authContent}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <Text style={[styles.sectionLabel, { color: theme.textMain }]}>Create Account</Text>

          {/* Google */}
          <TouchableOpacity style={[styles.socialButton, { backgroundColor: theme.inputBg }]} onPress={handleGoogle} activeOpacity={0.85}>
            <Text style={styles.googleG}>G</Text>
            <Text style={[styles.socialButtonText, { color: theme.textMain }]}>Continue with Google</Text>
          </TouchableOpacity>

          {/* Facebook */}
          <TouchableOpacity style={[styles.socialButton, { backgroundColor: theme.inputBg }]} onPress={handleFacebook} activeOpacity={0.85}>
            <Text style={styles.facebookF}>f</Text>
            <Text style={[styles.socialButtonText, { color: theme.textMain }]}>Continue with Facebook</Text>
          </TouchableOpacity>

          {/* Divider */}
          <View style={styles.dividerRow}>
            <View style={[styles.dividerLine, { backgroundColor: theme.dividerLine }]} />
            <Text style={[styles.dividerText, { color: theme.textSub }]}>or sign up with email</Text>
            <View style={[styles.dividerLine, { backgroundColor: theme.dividerLine }]} />
          </View>

          {/* Full Name */}
          <View style={[styles.inputWrapper, { backgroundColor: theme.inputBg }]}>
            <Ionicons name="person-outline" size={18} color={theme.placeholderColor} style={styles.inputIcon} />
            <TextInput
              style={[styles.textInput, { color: theme.textMain }]}
              placeholder="Full name"
              placeholderTextColor={theme.placeholderColor}
              value={fullName}
              onChangeText={setFullName}
              autoCapitalize="words"
              autoCorrect={false}
            />
          </View>

          {/* Email */}
          <View style={[styles.inputWrapper, { backgroundColor: theme.inputBg }]}>
            <Ionicons name="mail-outline" size={18} color={theme.placeholderColor} style={styles.inputIcon} />
            <TextInput
              style={[styles.textInput, { color: theme.textMain }]}
              placeholder="Email address"
              placeholderTextColor={theme.placeholderColor}
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>

          {/* Password */}
          <View style={[styles.inputWrapper, { backgroundColor: theme.inputBg }]}>
            <Ionicons name="lock-closed-outline" size={18} color={theme.placeholderColor} style={styles.inputIcon} />
            <TextInput
              style={[styles.textInput, { color: theme.textMain }]}
              placeholder="Password"
              placeholderTextColor={theme.placeholderColor}
              value={password}
              onChangeText={setPassword}
              secureTextEntry={!showPassword}
              autoCapitalize="none"
            />
            <TouchableOpacity onPress={() => setShowPassword(!showPassword)} activeOpacity={0.7}>
              <Ionicons
                name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                size={18}
                color={theme.placeholderColor}
              />
            </TouchableOpacity>
          </View>

          {/* Confirm Password */}
          <View style={[styles.inputWrapper, { backgroundColor: theme.inputBg }]}>
            <Ionicons name="lock-closed-outline" size={18} color={theme.placeholderColor} style={styles.inputIcon} />
            <TextInput
              style={[styles.textInput, { color: theme.textMain }]}
              placeholder="Confirm password"
              placeholderTextColor={theme.placeholderColor}
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              secureTextEntry={!showConfirm}
              autoCapitalize="none"
            />
            <TouchableOpacity onPress={() => setShowConfirm(!showConfirm)} activeOpacity={0.7}>
              <Ionicons
                name={showConfirm ? 'eye-off-outline' : 'eye-outline'}
                size={18}
                color={theme.placeholderColor}
              />
            </TouchableOpacity>
          </View>

          {/* Terms */}
          <Text style={[styles.termsText, { color: theme.textSub }]}>
            By registering, you agree to our{' '}
            <Text style={[styles.termsLink, { color: theme.accent }]}>Terms of Service</Text>
            {' '}and{' '}
            <Text style={[styles.termsLink, { color: theme.accent }]}>Privacy Policy</Text>.
          </Text>

          {/* Create Account Button */}
          <TouchableOpacity style={[styles.createButton, { backgroundColor: theme.accent }]} onPress={handleRegister} activeOpacity={0.85}>
            <Text style={styles.createButtonText}>Create Account</Text>
          </TouchableOpacity>

          {/* Login redirect */}
          <View style={styles.loginRow}>
            <Text style={[styles.loginPrompt, { color: theme.textSub }]}>Already have an account? </Text>
            <TouchableOpacity onPress={handleLogin} activeOpacity={0.7}>
              <Text style={[styles.loginLink, { color: theme.accent }]}>Log in</Text>
            </TouchableOpacity>
          </View>

        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },

  // ── Hero ──
  heroImage: {
    height: height * 0.36,
    width: '100%',
    justifyContent: 'space-between',
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.45)',
  },
  brandContainer: {
    marginTop: 52,
    marginLeft: 22,
  },
  brandText: {
    color: '#FFFFFF',
    fontSize: 28,
    fontWeight: '900',
    lineHeight: 32,
    letterSpacing: 1.5,
  },
  taglineContainer: {
    marginLeft: 22,
    marginBottom: 22,
  },
  taglineText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  taglineSubText: {
    color: 'rgba(255,255,255,0.65)',
    fontSize: 13,
    marginTop: 2,
  },
  closeButton: {
    position: 'absolute',
    top: 48,
    right: 20,
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 2,
    borderColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
  },

  // ── Auth Panel ──
  authPanel: {
    flex: 1,
  },
  authContent: {
    paddingHorizontal: 20,
    paddingTop: 22,
    paddingBottom: 32,
  },
  sectionLabel: {
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: 0.4,
    marginBottom: 18,
  },

  // Social buttons
  socialButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 50,
    paddingVertical: 14,
    marginBottom: 12,
  },
  socialButtonText: {
    fontSize: 15,
    fontWeight: '600',
    letterSpacing: 0.2,
  },
  googleG: {
    color: '#4CAF50',
    fontSize: 18,
    fontWeight: '800',
    marginRight: 10,
  },
  facebookF: {
    color: '#4A90D9',
    fontSize: 20,
    fontWeight: '800',
    marginRight: 10,
  },

  // Divider
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 16,
  },
  dividerLine: {
    flex: 1,
    height: 1,
  },
  dividerText: {
    fontSize: 12,
    marginHorizontal: 10,
  },

  // Input fields
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 50,
    paddingVertical: 13,
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  inputIcon: {
    marginRight: 10,
  },
  textInput: {
    flex: 1,
    fontSize: 15,
    padding: 0,
  },

  // Terms
  termsText: {
    fontSize: 12,
    textAlign: 'center',
    marginVertical: 14,
    lineHeight: 18,
  },
  termsLink: {
    fontWeight: '600',
  },

  // Create button
  createButton: {
    borderRadius: 50,
    paddingVertical: 15,
    alignItems: 'center',
    marginBottom: 16,
  },
  createButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.3,
  },

  // Login redirect
  loginRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loginPrompt: {
    fontSize: 13,
  },
  loginLink: {
    fontSize: 13,
    fontWeight: '700',
  },
});