/**
 * Subdomain detection composable for multi-tenant SaaS.
 * 
 * Detects organization context from:
 * 1. Subdomain: acme.example.com → slug: "acme"
 * 2. Query param: ?org_slug=acme (for local dev)
 * 3. Route param: /register/:org_slug (if configured)
 * 
 * @example
 * const { isSubdomain, organizationSlug, organizationName } = useSubdomain()
 * 
 * if (isSubdomain.value) {
 *   console.log(`Registering for: ${organizationSlug.value}`)
 * }
 */
export function useSubdomain() {
  const config = useRuntimeConfig()
  const route = useRoute()
  
  // Get base domain from config or derive from current host
  const baseDomain = computed(() => {
    // 1. Try config first for production consistency
    try {
      const appUrl = (config.public.appUrl || config.public.baseUrl) as string
      if (appUrl) {
         const url = new URL(appUrl.startsWith('http') ? appUrl : `https://${appUrl}`)
         return url.hostname
      }
    } catch (e) {}

    // 2. Dynamic Fallback: Extract from current hostname (Server & Client)
    // Useful for dynamic environments or misconfigured .env
    const host = hostname.value // Uses useRequestURL on server, window on client
    if (host) {
      const parts = host.split('.')
      // If localhost or simple domain
      if (parts.includes('localhost') || parts.length === 1) {
        return 'localhost'
      }
      // If we have "sub.domain.com", return "domain.com"
      // If we have "domain.com", return "domain.com"
      if (parts.length >= 2) {
        return parts.slice(-2).join('.')
      }
    }
    
    return 'movana.id' // Final default
  })

  // Non-tenant subdomains to ignore
  const excludedSubdomains = ['www', 'api', 'admin', 'app', 'staging', 'localhost']

  // Current hostname
  const hostname = computed(() => {
    if (import.meta.server) {
      // Server-side: Use request URL
      try {
        const url = useRequestURL()
        return url.hostname
      } catch (e) {
        return 'movana.id' // Fallback for pure node execution
      }
    }
    // Client-side
    if (typeof window !== 'undefined') {
      return window.location.hostname
    }
    return ''
  })

  // Extract subdomain from hostname
  const subdomainFromHost = computed<string | null>(() => {
    const host = hostname.value
    const base = baseDomain.value

    if (!host || !base) return null

    // Check if host ends with base domain
    if (host === base || !host.endsWith(base)) {
      return null
    }

    // Extract subdomain: "acme.example.com" -> "acme"
    const subdomain = host.slice(0, -(base.length + 1)) // Remove ".movana.id"

    // Skip excluded subdomains
    if (excludedSubdomains.includes(subdomain.toLowerCase())) {
      return null
    }

    return subdomain
  })

  // Get org_slug from query params (for local dev testing)
  const slugFromQuery = computed<string | null>(() => {
    const query = route.query.org_slug
    if (typeof query === 'string' && query.trim()) {
      return query.trim()
    }
    return null
  })

  // Get org_slug from route params (if using dynamic routes)
  const slugFromParams = computed<string | null>(() => {
    const param = (route.params as Record<string, string | string[]>)['org_slug']
    if (typeof param === 'string' && param.trim()) {
      return param.trim()
    }
    return null
  })

  // Final organization slug: subdomain > query param > route param
  const organizationSlug = computed<string | null>(() => {
    return subdomainFromHost.value || slugFromQuery.value || slugFromParams.value
  })

  // Is this a subdomain/tenant context?
  const isSubdomain = computed(() => !!organizationSlug.value)

  // Is this the main domain?
  const isMainDomain = computed(() => !isSubdomain.value)

  // Organization data (fetched when on subdomain)
  const organization = ref<{
    id: string
    name: string
    slug: string
    type?: string
    allow_public_registration?: boolean
  } | null>(null)

  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Fetch organization details by slug.
   * Called automatically on subdomain pages.
   */
  const fetchOrganization = async () => {
    const slug = organizationSlug.value
    if (!slug) return null

    isLoading.value = true
    error.value = null

    try {
      // Public endpoint to get org by slug
      const apiUrl = config.public.apiBaseUrl || 'http://localhost:8000/api'
      const response = await $fetch<{
        data: {
          id: string
          name: string
          slug: string
          type?: string
          allow_public_registration?: boolean
        }
      }>(`${apiUrl}/organizations/by-slug/${slug}`)

      organization.value = response.data
      return response.data
    } catch (e: any) {
      error.value = e?.data?.message || 'Organization not found'
      organization.value = null
      return null
    } finally {
      isLoading.value = false
    }
  }

  // Organization name (from fetched data or slug)
  const organizationName = computed(() => {
    return organization.value?.name || organizationSlug.value || null
  })

  return {
    // Core detection
    isSubdomain,
    isMainDomain,
    organizationSlug,
    baseDomain,
    hostname,

    // Source detection (for debugging)
    subdomainFromHost,
    slugFromQuery,
    slugFromParams,

    // Organization data
    organization,
    organizationName,
    
    // State
    isLoading,
    error,

    // Methods
    fetchOrganization,
  }
}

