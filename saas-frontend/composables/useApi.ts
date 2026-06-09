import { useOrganizationStore } from '@/stores/organizationStore'
import { createFetch } from '@vueuse/core'

/**
 * API error response type
 */
export type ApiError = {
  status: number | undefined
  message: string
  data?: any
  originalError?: Error
}

/**
 * Standard API response wrapper
 */
export interface ApiResponse<T = any> {
  data: Ref<T | null>
  error: Ref<ApiError | null>
  response: Response | null
  isFetching: Ref<boolean>
}

// Lazy getter for API URL to avoid calling useRuntimeConfig at module level
const getApiUrl = () => {
  try {
    return useRuntimeConfig().public.apiBaseUrl as string
  } catch {
    // Fallback for SSR or when Nuxt context not available
    return process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000/api'
  }
}

/**
 * Creates an API utility with proper error handling and organization context.
 * 
 * Features:
 * - Auto-injects Authorization header from auth token
 * - Auto-injects X-Organization-ID from organization store
 * - Auto-injects X-Branch-ID if branch is selected
 * - Handles 401 (unauthorized) with token refresh then logout
 * - Handles 403 (forbidden) with toast notification
 * 
 * @example
 * const { data, error } = await useApi('/users').get().json()
 * const { data, error } = await useApi('/users').post(userData).json()
 * const { data, error } = await useApi(`/users/${userId}`).put(updatedData).json()
 * const { data, error } = await useApi(`/users/${userId}`).delete().json()
 */
export const useApi = createFetch({
  baseUrl: '', // Will be set in beforeFetch
  fetchOptions: {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  },
  options: {
    refetch: true,

    async beforeFetch({ url, options }) {
      // Get API URL lazily
      const apiUrl = getApiUrl()
      
      // Prepend base URL if not already absolute
      const fullUrl = url.startsWith('http') ? url : `${apiUrl}${url}`
      
      const accessToken = useCookie('auth.token').value

      // Build headers object
      const headers: Record<string, string> = {
        ...(options.headers as Record<string, string>),
      }

      // Add Authorization header
      if (accessToken) {
        headers.Authorization = `Bearer ${accessToken}`
      }

      // Add Organization context from store
      // Use try-catch because store might not be available during SSR
      try {
        const orgStore = useOrganizationStore()

        if (orgStore.currentOrganizationId) {
          headers['X-Organization-ID'] = orgStore.currentOrganizationId
        }

        if (orgStore.currentBranchId) {
          headers['X-Branch-ID'] = orgStore.currentBranchId
        }
      } catch {
        // Store not available, try cookies
        const orgCookie = useCookie('current-organization-id')
        const branchCookie = useCookie('current-branch-id')

        if (orgCookie.value) {
          headers['X-Organization-ID'] = orgCookie.value
        }
        if (branchCookie.value) {
          headers['X-Branch-ID'] = branchCookie.value
        }
      }

      options.headers = headers
      return { url: fullUrl, options }
    },

    onFetchError: async ({ data, response, error }) => {
      // Handle authentication errors
      if (response?.status === 401) {
        // Check if this is a token expired error
        const errorData = data || (await response?.clone().json().catch(() => null))
        const isTokenExpired = errorData?.error === 'token_expired' ||
                               errorData?.message?.includes('expired')

        if (isTokenExpired) {
          // Try to refresh the token
          console.log('Token expired, attempting refresh...')
          try {
            const { useTokenRefresh } = await import('./useTokenRefresh')
            const { refreshAccessToken } = useTokenRefresh()
            
            const success = await refreshAccessToken()
            
            if (success) {
              // Token refreshed - the caller should retry the request
              // We can't retry here automatically due to @vueuse/core limitations
              console.log('Token refreshed successfully. Please retry the request.')
              return {
                data: null,
                response,
                error: {
                  status: 401,
                  message: 'Token refreshed. Please retry.',
                  data: { tokenRefreshed: true },
                },
              }
            }
          } catch (refreshError) {
            console.error('Token refresh failed:', refreshError)
          }
        }

        // Token refresh failed or not applicable - logout
        console.warn('Session expired, logging out...')
        try {
          const { signOut } = useAuth()
          await signOut({ callbackUrl: '/login' })
        } catch {
          // Fallback: clear tokens and redirect
          const { useSecureStorage } = await import('./useSecureStorage')
          const storage = useSecureStorage()
          await storage.clearTokens()
          
          const tokenCookie = useCookie('auth.token')
          tokenCookie.value = null
          navigateTo('/login')
        }
      }

      // Handle authorization errors
      if (response?.status === 403) {
        console.warn('Permission denied:', data?.message || 'Access forbidden')
        // Could show a toast here if you have a global toast system
      }

      // Parse error response
      try {
        const errorData = data ?? (await response?.clone().json().catch(() => null))

        const apiError: ApiError = {
          status: response?.status,
          message: errorData?.message || error?.message || 'An error occurred',
          data: errorData,
        }

        return {
          data: null,
          response,
          error: apiError,
        }
      } catch {
        const apiError: ApiError = {
          status: response?.status,
          message: error?.message || 'Unknown error',
          originalError: error,
        }

        return {
          data: null,
          response,
          error: apiError,
        }
      }
    },

    afterFetch: async ({ data, response }) => {
      try {
        if (!response.ok) {
          const errorData = await response.clone().json().catch(() => null)
          const apiError: ApiError = {
            status: response.status,
            message: errorData?.message || `Request failed with status ${response.status}`,
            data: errorData,
          }

          return {
            data: null,
            response,
            error: apiError,
          }
        }

        // Force parsing response body if data is null
        const parsedData = data ?? (await response.clone().json().catch(() => null))
        return {
          data: parsedData,
          response,
          error: null as ApiError | null,
        }
      } catch (err) {
        console.warn('Error processing response:', {
          status: response?.status,
          error: err instanceof Error ? err.message : String(err),
        })

        const apiError: ApiError = {
          status: response?.status,
          message: err instanceof Error ? err.message : 'Unknown error during response processing',
          originalError: err instanceof Error ? err : new Error(String(err)),
        }

        return {
          data: null,
          response,
          error: apiError,
        }
      }
    },
  },
})

