// Custom auth configuration that handles remember me functionality
export const getAuthConfig = () => {
  // Check if remember me is enabled (this runs on both client and server)
  const getRememberPreference = () => {
    if (process.client) {
      try {
        // Check localStorage or cookie for remember preference
        const cookies = document.cookie.split(';')
        const rememberCookie = cookies.find(cookie => cookie.trim().startsWith('auth.remember='))
        return rememberCookie ? rememberCookie.split('=')[1] === 'true' : false
      } catch {
        return false
      }
    }
    return false
  }

  const isRemembered = getRememberPreference()
  const tokenMaxAge = isRemembered ? 60 * 60 * 24 * 30 : 60 * 60 * 24 // 30 days vs 1 day

  return {
    baseURL: process.env.NUXT_PUBLIC_API_BASE_URL || '/api',
    provider: {
      type: 'local',
      endpoints: {
        signIn: { path: '/login', method: 'post' },
        getSession: { path: '/profile' }
      },
      pages: {
        login: '/login'
      },
      token: {
        signInResponseTokenPointer: '/token',
        maxAgeInSeconds: tokenMaxAge,
        cookieName: 'auth.token',
        sameSiteAttribute: 'lax',
        secureCookieAttribute: process.env.NODE_ENV === 'production',
      },
      session: {
        dataType: { 
          id: 'string', 
          email: 'string', 
          name: 'string', 
          role: '\'admin\' | \'guest\' | \'account\'', 
          subscriptions: '{ id: number, status: \'ACTIVE\' | \'INACTIVE\' }[]' 
        },
        dataResponsePointer: '/'
      },
      refresh: {
        isEnabled: false,
        endpoint: { path: '/refresh', method: 'post' },
        token: {
          signInResponseRefreshTokenPointer: '/token/refreshToken',
          refreshResponseTokenPointer: '',
          refreshRequestTokenPointer: '/refreshToken'
        },
      }
    },
    sessionRefresh: {
      enableOnWindowFocus: true,
      enablePeriodically: 90000,
    },
    globalAppMiddleware: {
      isEnabled: false,
    },
  }
}
