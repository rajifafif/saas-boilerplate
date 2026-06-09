/**
 * Capacitor-ready secure storage abstraction.
 * 
 * Provides a unified API for storing tokens that works on both:
 * - Web: Uses cookies for SSR compatibility
 * - Mobile (Capacitor): Uses @capacitor/preferences for secure storage
 * 
 * @example
 * const { setAccessToken, getAccessToken, setRefreshToken } = useSecureStorage()
 * 
 * await setAccessToken(response.access_token)
 * const token = await getAccessToken()
 */

interface TokenData {
  accessToken: string | null
  refreshToken: string | null
  expiresAt: number | null
}

// Check if running in Capacitor native environment
const isNativePlatform = (): boolean => {
  if (typeof window === 'undefined') return false
  return (window as any).Capacitor?.isNativePlatform?.() ?? false
}

export function useSecureStorage() {
  // Cookie options for web
  const cookieOptions = {
    maxAge: 60 * 60 * 24 * 30, // 30 days
    sameSite: 'lax' as const,
    secure: process.env.NODE_ENV === 'production',
  }

  /**
   * Store access token
   */
  const setAccessToken = async (token: string | null, expiresIn?: number): Promise<void> => {
    if (isNativePlatform()) {
      // Mobile: Use Capacitor Preferences
      const { Preferences } = await import('@capacitor/preferences')
      
      if (token) {
        await Preferences.set({ key: 'access_token', value: token })
        
        if (expiresIn) {
          const expiresAt = Date.now() + (expiresIn * 1000)
          await Preferences.set({ key: 'token_expires_at', value: expiresAt.toString() })
        }
      } else {
        await Preferences.remove({ key: 'access_token' })
        await Preferences.remove({ key: 'token_expires_at' })
      }
    } else {
      // Web: Use cookies
      const cookie = useCookie('auth.token', cookieOptions)
      cookie.value = token
    }
  }

  /**
   * Get access token
   */
  const getAccessToken = async (): Promise<string | null> => {
    if (isNativePlatform()) {
      const { Preferences } = await import('@capacitor/preferences')
      const { value } = await Preferences.get({ key: 'access_token' })
      return value
    } else {
      const cookie = useCookie('auth.token')
      return cookie.value || null
    }
  }

  /**
   * Store refresh token
   */
  const setRefreshToken = async (token: string | null): Promise<void> => {
    if (isNativePlatform()) {
      const { Preferences } = await import('@capacitor/preferences')
      
      if (token) {
        await Preferences.set({ key: 'refresh_token', value: token })
      } else {
        await Preferences.remove({ key: 'refresh_token' })
      }
    } else {
      const cookie = useCookie('auth.refresh_token', {
        ...cookieOptions,
        maxAge: 60 * 60 * 24 * 30, // 30 days for refresh token
      })
      cookie.value = token
    }
  }

  /**
   * Get refresh token
   */
  const getRefreshToken = async (): Promise<string | null> => {
    if (isNativePlatform()) {
      const { Preferences } = await import('@capacitor/preferences')
      const { value } = await Preferences.get({ key: 'refresh_token' })
      return value
    } else {
      const cookie = useCookie('auth.refresh_token')
      return cookie.value || null
    }
  }

  /**
   * Check if access token is expired
   */
  const isAccessTokenExpired = async (): Promise<boolean> => {
    if (isNativePlatform()) {
      const { Preferences } = await import('@capacitor/preferences')
      const { value } = await Preferences.get({ key: 'token_expires_at' })
      
      if (!value) return true
      return Date.now() > parseInt(value, 10)
    } else {
      // On web, we rely on the server to tell us via 401
      // Could implement expiry tracking in cookie if needed
      return false
    }
  }

  /**
   * Clear all stored tokens (logout)
   */
  const clearTokens = async (): Promise<void> => {
    if (isNativePlatform()) {
      const { Preferences } = await import('@capacitor/preferences')
      await Preferences.remove({ key: 'access_token' })
      await Preferences.remove({ key: 'refresh_token' })
      await Preferences.remove({ key: 'token_expires_at' })
    } else {
      const accessCookie = useCookie('auth.token')
      const refreshCookie = useCookie('auth.refresh_token')
      accessCookie.value = null
      refreshCookie.value = null
    }
  }

  /**
   * Get all token data
   */
  const getTokenData = async (): Promise<TokenData> => {
    const [accessToken, refreshToken] = await Promise.all([
      getAccessToken(),
      getRefreshToken(),
    ])

    let expiresAt: number | null = null
    
    if (isNativePlatform()) {
      const { Preferences } = await import('@capacitor/preferences')
      const { value } = await Preferences.get({ key: 'token_expires_at' })
      expiresAt = value ? parseInt(value, 10) : null
    }

    return {
      accessToken,
      refreshToken,
      expiresAt,
    }
  }

  return {
    setAccessToken,
    getAccessToken,
    setRefreshToken,
    getRefreshToken,
    isAccessTokenExpired,
    clearTokens,
    getTokenData,
    isNativePlatform,
  }
}
