import { useOrganizationStore } from '@/stores/organizationStore'
import type { Actions, Subjects } from '@/types/permission'
import { createMongoAbility } from '@casl/ability'
import { getAuthConfig } from '@/config/auth'

// Guard to prevent infinite probing loop when backend is down
let hasProbed = false

/**
 * Global ACL (Access Control List) middleware
 * 
 * Handles:
 * 1. Authentication checks - redirects unauthenticated users to login
 * 2. Organization context - ensures org is loaded after login
 * 3. Permission checks - validates route-level permissions
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const { status } = useAuth()

  // Skip public routes
  if (to.meta.public) {
    return
  }

  // Handle unauthenticatedOnly check - support both formats:
  // 1. to.meta.unauthenticatedOnly (direct)
  // 2. to.meta.auth?.unauthenticatedOnly (@sidebase/nuxt-auth format)
  const authMeta = (to.meta.auth as { unauthenticatedOnly?: boolean; navigateAuthenticatedTo?: string } | undefined)
  const isUnauthenticatedOnly = to.meta.unauthenticatedOnly || authMeta?.unauthenticatedOnly
  const navigateAuthenticatedTo = (to.meta.navigateAuthenticatedTo as string) || authMeta?.navigateAuthenticatedTo || '/'

  const isLoggedIn = status.value === 'authenticated'

  // Handle unauthenticated-only pages (login, register)
  if (isUnauthenticatedOnly) {
    if (isLoggedIn) {
      // Already logged in, redirect to home
      return navigateTo(navigateAuthenticatedTo)
    }
    // Not logged in, allow access to auth pages
    return
  }

  // Redirect unauthenticated users to login
  if (!isLoggedIn) {
    // Check if we have a token but session fetch failed
    // This could happen if the backend is down
    // If we are here, the user is not authenticated.
    // However, before we redirect to login, we want to check if the backend is actually reachable.
    // If the backend is down (503 or Network Error), we should show a fatal error instead of a login redirect loop.
    
    // Only probe once to prevent infinite loop
    if (!hasProbed) {
      hasProbed = true
      try {
        const config = getAuthConfig()
        let apiBase = config.baseURL

        // If apiBase is relative (e.g. '/api'), and we are in a context where that might not resolve 
        // (like server-side or if proxy is missing), we try to assume localhost:8000 for the probe.
        // This is a heuristic to make the probe robust in dev environments.
        if (apiBase.startsWith('/') && !apiBase.startsWith('//')) {
           // Check if we are in dev
           if (process.dev) {
              apiBase = `http://localhost:8000${apiBase}`
           } else {
              // In prod, relative might be fine if served from same origin, 
              // but for safety in the probe we might need origin.
              // For now, let's leave it relative in prod or use location.origin if client.
              if (process.client) {
                 apiBase = `${window.location.origin}${apiBase}`
              }
           }
        }
        
        console.log('ACL Middleware: Probing backend connectivity at', `${apiBase}/profile`)
        
        // Attempt to fetch profile. 
        // If we are truly just logged out, this returns 401.
        // If backend is down, this throws a network error.
        await $fetch(`${apiBase}/profile`, {
          method: 'GET',
          timeout: 3000,
          headers: {
              'Cache-Control': 'no-cache', 
              'Pragma': 'no-cache'
          },
          onResponseError({ response }) {
             if (response.status === 401) {
               throw createError({ statusCode: 401, message: 'Unauthorized' })
             }
          }
        })
        
      } catch (error: any) {
        if (error.statusCode !== 401) {
           console.error('ACL Middleware: Backend appears to be down:', error)
           
           // If backend is down, the auth module might have cleared the token.
           // Try to restore it from backup so the user stays logged in when the server comes back.
           if (process.client) {
               const backup = localStorage.getItem('auth.token.backup')
               if (backup) {
                   console.log('ACL Middleware: Restoring token from backup')
                   const tokenCookie = useCookie('auth.token')
                   tokenCookie.value = backup
               }
           }

           throw createError({
             statusCode: 503,
             statusMessage: 'Service Unavailable',
             message: 'Unable to connect to the server. Please check your connection or try again later.',
             fatal: true
           })
        }
        // If it IS 401, we suppress the error and proceed to the default redirect below
      }
    }

    return navigateTo({
      path: '/login',
      query: to.fullPath !== '/' ? { redirect: to.fullPath } : undefined,
    })
  }

  // User is authenticated - ensure organization context is loaded
  try {
    const orgStore = useOrganizationStore()

    // Fetch organizations if not loaded yet
    if (!orgStore.isInitialized && orgStore.organizations.length === 0) {
      await orgStore.fetchOrganizations()
    }

    // If user has no organizations, just continue (don't block)
    if (orgStore.organizations.length === 0) {
      console.warn('User has no organizations')
      return
    }

    // Check route-level permissions if defined
    const routeSubject = to.meta.subject as Subjects | undefined
    const routeAction = (to.meta.action as Actions) || 'read'

    if (routeSubject) {
      // Get ability rules from cookie (set during login)
      // This avoids using useAbility() which calls onMounted internally
      const userAbilityRules = useCookie<any[]>('userAbilityRules')
      const rules = userAbilityRules.value || []
      
      // Create ability from rules without using component lifecycle
      const ability = createMongoAbility(rules)

      if (!ability.can(routeAction, routeSubject)) {
        console.warn(`Access denied: cannot ${routeAction} ${routeSubject}`)
        return navigateTo('/not-authorized')
      }
    }
  } catch (error) {
    console.error('ACL middleware error:', error)
    // Don't block navigation on error, just log
  }
})


