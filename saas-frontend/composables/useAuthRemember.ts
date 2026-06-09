import { defineAbilitiesFor } from '@/plugins/casl/ability'
import { useOrganizationStore } from '@/stores/organizationStore'
import type { Organization } from '@/types/organization'
import { useSecureStorage } from './useSecureStorage'
import { useTokenRefresh } from './useTokenRefresh'

/**
 * Auth composable with remember me support and refresh token handling.
 * 
 * Features:
 * - Remember me: adjusts token expiration
 * - Refresh tokens: stores and manages refresh token
 * - Organization setup: sets up org store after login
 * - Auto refresh: sets up automatic token refresh
 * 
 * @example
 * const { signInWithRemember } = useAuthRemember()
 * await signInWithRemember(credentials, rememberMe)
 */
export const useAuthRemember = () => {
  const storage = useSecureStorage()
  const { setupAutoRefresh } = useTokenRefresh()

  // Cookie to store remember preference
  const rememberMeCookie = useCookie('auth.remember', {
    maxAge: 60 * 60 * 24 * 365, // 1 year
    sameSite: 'lax',
    default: () => 'false'
  })

  /**
   * Enhanced signIn function with remember me support and refresh token handling.
   */
  const signInWithRemember = async (credentials: any, rememberMe: boolean = false, options: any = {}) => {
    const { signIn } = useAuth()
    
    try {
      // Store remember preference BEFORE login
      rememberMeCookie.value = rememberMe.toString()
      
      // Perform the login first
      const result = await signIn(credentials, options)
      
      // After successful login, handle tokens and organization setup
      if (result && !result.error) {
        // Wait a bit for the auth module to set the cookies
        await nextTick()
        
        // Handle access token
        const accessToken = result.access_token || result.token
        if (accessToken) {
          const expiresIn = result.expires_in || (rememberMe ? 60 * 60 * 24 * 30 : 60 * 15)
          await storage.setAccessToken(accessToken, expiresIn)
          
          // Also set the auth.token for @sidebase/nuxt-auth compatibility
          const tokenCookie = useCookie('auth.token', {
            maxAge: rememberMe ? 60 * 60 * 24 * 30 : 60 * 60 * 24,
            sameSite: 'lax',
            secure: process.env.NODE_ENV === 'production',
          })
          tokenCookie.value = accessToken
          
          // Setup auto refresh (refresh 1 minute before expiry)
          if (result.expires_in) {
            setupAutoRefresh(result.expires_in)
          }
        }
        
        // Handle refresh token
        if (result.refresh_token) {
          await storage.setRefreshToken(result.refresh_token)
        }

        // Handle organization setup from login response
        if (result.organizations && Array.isArray(result.organizations)) {
          const orgStore = useOrganizationStore()
          
          // Set organizations in store
          orgStore.setOrganizations(result.organizations as Organization[])
          
          // Set current organization from response or default
          if (result.current_organization) {
            orgStore.setCurrentOrganization(result.current_organization as Organization)
          }
          
          // Update CASL abilities based on current role
          if (orgStore.currentRole) {
            const userAbilityRules = useCookie<any[]>('userAbilityRules')
            const newAbility = defineAbilitiesFor(orgStore.currentRole)
            userAbilityRules.value = newAbility.rules as any[]
          }
        }
      }
      
      return result
    } catch (error) {
      throw error
    }
  }

  /**
   * Store tokens manually (for custom login flows).
   */
  const storeTokens = async (accessToken: string, refreshToken: string, expiresIn: number, rememberMe: boolean = false) => {
    await storage.setAccessToken(accessToken, expiresIn)
    await storage.setRefreshToken(refreshToken)
    
    // Also set for compatibility
    const tokenCookie = useCookie('auth.token', {
      maxAge: rememberMe ? 60 * 60 * 24 * 30 : 60 * 60 * 24,
      sameSite: 'lax',
      secure: process.env.NODE_ENV === 'production',
    })
    tokenCookie.value = accessToken
    
    // Setup auto refresh
    setupAutoRefresh(expiresIn)
  }

  /**
   * Clear all auth data (logout).
   */
  const clearAuth = async () => {
    await storage.clearTokens()
    
    // Clear cookies
    const tokenCookie = useCookie('auth.token')
    const refreshCookie = useCookie('auth.refresh_token')
    tokenCookie.value = null
    refreshCookie.value = null
    
    // Clear organization store
    const orgStore = useOrganizationStore()
    orgStore.clear()
    
    // Clear abilities
    const userAbilityRules = useCookie<any[]>('userAbilityRules')
    userAbilityRules.value = []
  }

  /**
   * Get remember preference.
   */
  const getRememberPreference = (): boolean => {
    return rememberMeCookie.value === 'true'
  }

  /**
   * Clear remember preference.
   */
  const clearRememberPreference = () => {
    rememberMeCookie.value = 'false'
  }

  return {
    signInWithRemember,
    storeTokens,
    clearAuth,
    getRememberPreference,
    clearRememberPreference,
    storage, // Expose storage for direct access if needed
  }
}

