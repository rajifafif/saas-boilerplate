import { useOrganizationStore } from '@/stores/organizationStore'
import { useSecureStorage } from './useSecureStorage'

// Lazy getter for API URL
const getApiBaseUrl = (): string => {
  try {
    return useRuntimeConfig().public.apiBaseUrl as string
  } catch {
    return process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api'
  }
}

/**
 * Token refresh composable.
 * 
 * Handles automatic token refresh when access token expires.
 * Works seamlessly on both web and mobile (Capacitor).
 * 
 * @example
 * const { refreshTokenIfNeeded, setupAutoRefresh } = useTokenRefresh()
 * 
 * // Check and refresh before API call
 * await refreshTokenIfNeeded()
 * 
 * // Or setup automatic refresh
 * setupAutoRefresh()
 */
export function useTokenRefresh() {
  const storage = useSecureStorage()
  const isRefreshing = ref(false)
  let refreshPromise: Promise<boolean> | null = null

  /**
   * Refresh the access token using the refresh token.
   * Returns true if successful, false otherwise.
   */
  const refreshAccessToken = async (): Promise<boolean> => {
    // Prevent concurrent refresh requests
    if (isRefreshing.value && refreshPromise) {
      return refreshPromise
    }

    isRefreshing.value = true

    refreshPromise = (async () => {
      try {
        const refreshToken = await storage.getRefreshToken()

        if (!refreshToken) {
          console.warn('No refresh token available')
          return false
        }

        // Call refresh endpoint
        const apiBaseUrl = getApiBaseUrl()
        const response = await $fetch<{
          access_token: string
          refresh_token: string
          expires_in: number
          organization?: {
            id: string
            name: string
            role: string
          }
        }>(`${apiBaseUrl}/auth/refresh`, {
          method: 'POST',
          body: { refresh_token: refreshToken },
        })

        // Store new tokens
        await storage.setAccessToken(response.access_token, response.expires_in)
        await storage.setRefreshToken(response.refresh_token)

        // Update organization store if org changed
        if (response.organization) {
          const orgStore = useOrganizationStore()
          // The org context is now in the new token
          console.log('Token refreshed with org:', response.organization.name)
        }

        return true
      } catch (error) {
        console.error('Token refresh failed:', error)
        
        // Clear tokens on refresh failure
        await storage.clearTokens()
        
        // Redirect to login
        navigateTo('/login')
        
        return false
      } finally {
        isRefreshing.value = false
        refreshPromise = null
      }
    })()

    return refreshPromise
  }

  /**
   * Check if token needs refresh and refresh if necessary.
   */
  const refreshTokenIfNeeded = async (): Promise<boolean> => {
    const isExpired = await storage.isAccessTokenExpired()
    
    if (isExpired) {
      return refreshAccessToken()
    }
    
    return true
  }

  /**
   * Setup automatic token refresh.
   * Refreshes token 1 minute before expiry.
   */
  const setupAutoRefresh = (expiresIn: number) => {
    if (typeof window === 'undefined') return

    // Refresh 1 minute before expiry
    const refreshTime = Math.max((expiresIn - 60) * 1000, 30000) // At least 30 seconds

    setTimeout(async () => {
      const success = await refreshAccessToken()
      
      if (success) {
        // Get new expiry and setup next refresh
        const tokenData = await storage.getTokenData()
        if (tokenData.expiresAt) {
          const newExpiresIn = Math.floor((tokenData.expiresAt - Date.now()) / 1000)
          setupAutoRefresh(newExpiresIn)
        }
      }
    }, refreshTime)
  }

  /**
   * Handle 401 error - attempt refresh and retry.
   */
  const handleUnauthorized = async (retryCallback: () => Promise<any>): Promise<any> => {
    const success = await refreshAccessToken()
    
    if (success) {
      // Retry the original request
      return retryCallback()
    }
    
    throw new Error('Session expired')
  }

  return {
    refreshAccessToken,
    refreshTokenIfNeeded,
    setupAutoRefresh,
    handleUnauthorized,
    isRefreshing: readonly(isRefreshing),
  }
}
