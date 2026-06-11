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

export default function LoginScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

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
    router.replace('/(tabs)');
  };

  const handleLogin = () => {
    if (email.trim() && password.trim()) {
      router.replace('/(tabs)');
    }
  };

  const handleForgotPassword = () => {
    router.push('/(auth)/forgot-password');
  };

  const handleSignUp = () => {
    router.push('/(auth)/register');
  };

  const handleGoogle = () => {
    console.log('Continue with Google');
  };

  const handleFacebook = () => {
    console.log('Continue with Facebook');
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
          <Text style={styles.taglineText}>Welcome back, explorer.</Text>
          <Text style={styles.taglineSubText}>Sign in to continue your journey.</Text>
        </View>

        <TouchableOpacity style={styles.closeButton} onPress={handleClose} activeOpacity={0.8}>
          <Ionicons name="close" size={18} color="#FFFFFF" />
        </TouchableOpacity>
      </ImageBackground>

      {/* ── BOTTOM HALF: Auth Panel ── */}
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
          <Text style={[styles.sectionLabel, { color: theme.textMain }]}>Log In</Text>

          {/* Email Wrapper */}
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

          {/* Password Wrapper */}
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

          {/* Action Button */}
          <TouchableOpacity style={[styles.loginButton, { backgroundColor: theme.accent }]} onPress={handleLogin} activeOpacity={0.85}>
            <Text style={styles.loginButtonText}>Login</Text>
          </TouchableOpacity>

          {/* Forgot Button */}
          <TouchableOpacity style={styles.forgotButton} onPress={handleForgotPassword} activeOpacity={0.7}>
            <Text style={[styles.forgotText, { color: theme.accent }]}>Forgot Password?</Text>
          </TouchableOpacity>

          {/* Divider Line */}
          <View style={styles.dividerRow}>
            <View style={[styles.dividerLine, { backgroundColor: theme.dividerLine }]} />
            <Text style={[styles.dividerText, { color: theme.textSub }]}>or continue with</Text>
            <View style={[styles.dividerLine, { backgroundColor: theme.dividerLine }]} />
          </View>

          {/* Social Sign-In Wrappers */}
          <TouchableOpacity style={[styles.socialButton, { backgroundColor: theme.inputBg }]} onPress={handleGoogle} activeOpacity={0.85}>
            <Text style={styles.googleG}>G</Text>
            <Text style={[styles.socialButtonText, { color: theme.textMain }]}>Continue with Google</Text>
          </TouchableOpacity>

          <TouchableOpacity style={[styles.socialButton, { backgroundColor: theme.inputBg }]} onPress={handleFacebook} activeOpacity={0.85}>
            <Text style={styles.facebookF}>f</Text>
            <Text style={[styles.socialButtonText, { color: theme.textMain }]}>Continue with Facebook</Text>
          </TouchableOpacity>

          {/* Sign Up Redirect Row */}
          <View style={styles.signUpRow}>
            <Text style={[styles.signUpPrompt, { color: theme.textSub }]}>Don't have an account? </Text>
            <TouchableOpacity onPress={handleSignUp} activeOpacity={0.7}>
              <Text style={[styles.signUpLink, { color: theme.accent }]}>Sign up</Text>
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
  heroImage: {
    height: height * 0.38,
    width: '100%',
    justifyContent: 'space-between',
  },
  overlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.42)',
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
  loginButton: {
    borderRadius: 50,
    paddingVertical: 15,
    alignItems: 'center',
    marginTop: 4,
    marginBottom: 14,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  forgotButton: {
    alignItems: 'center',
    marginBottom: 20,
  },
  forgotText: {
    fontSize: 13,
    fontWeight: '600',
  },
  dividerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  dividerLine: {
    flex: 1,
    height: 1,
  },
  dividerText: {
    fontSize: 12,
    marginHorizontal: 10,
  },
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
  signUpRow: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 6,
  },
  signUpPrompt: {
    fontSize: 13,
  },
  signUpLink: {
    fontSize: 13,
    fontWeight: '700',
  },
});