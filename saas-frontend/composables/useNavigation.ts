import type { VerticalNavItems } from '@layouts/types'
import { useApi } from './useApi'

/**
 * Navigation item from backend
 */
export interface BackendNavItem {
  subheader?: string
  title?: string
  to?: string
  icon?: string
  permissions?: string[]
  items?: BackendNavItem[]
}

/**
 * Navigation API response
 */
export interface NavigationResponse {
  navigation: BackendNavItem[]
  home_route: string
  layout_type: 'admin' | 'home'
  role: string
}

/**
 * Composable for fetching navigation from backend.
 * 
 * Returns navigation items, home route, and layout type based on user's role.
 * The backend filters items based on permissions.
 * 
 * @example
 * const { navigation, layoutType, fetchNavigation } = useNavigation()
 * await fetchNavigation()
 * 
 * if (layoutType.value === 'admin') {
 *   // Show admin sidebar layout
 * } else {
 *   // Show home card layout
 * }
 */
export function useNavigation() {
  // State
  const navigation = ref<BackendNavItem[]>([])
  const homeRoute = ref<string>('/')
  const layoutType = ref<'admin' | 'home'>('home')
  const role = ref<string>('member')
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const hasFetched = ref(false)

  /**
   * Transform backend nav items to frontend format
   */
  const transformNavItems = (items: BackendNavItem[]): VerticalNavItems => {
    return items.flatMap((section) => {
      const result: any[] = []
      
      // Add subheader if present
      if (section.subheader) {
        result.push({ heading: section.subheader })
      }
      
      // Add items
      if (section.items) {
        section.items.forEach((item) => {
          result.push({
            title: item.title,
            to: item.to,
            icon: item.icon ? { icon: item.icon } : undefined,
          })
        })
      }
      
      return result
    })
  }

  /**
   * Navigation items transformed for vertical nav component
   */
  const navItems = computed<VerticalNavItems>(() => {
    return transformNavItems(navigation.value)
  })

  /**
   * Fetch navigation from backend API
   */
  const fetchNavigation = async () => {
    if (isLoading.value) return
    
    isLoading.value = true
    error.value = null

    try {
      const { data, error: fetchError } = await useApi('/navigation').get().json<NavigationResponse>()
      
      if (fetchError.value) {
        error.value = fetchError.value.message || 'Failed to fetch navigation'
        return
      }

      if (data.value) {
        navigation.value = data.value.navigation
        homeRoute.value = data.value.home_route
        layoutType.value = data.value.layout_type
        role.value = data.value.role
        hasFetched.value = true
      }
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch navigation'
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Check if layout is admin style
   */
  const isAdminLayout = computed(() => layoutType.value === 'admin')

  /**
   * Check if layout is home/member style
   */
  const isHomeLayout = computed(() => layoutType.value === 'home')

  return {
    // Raw backend data
    navigation,
    homeRoute,
    layoutType,
    role,
    
    // Transformed data
    navItems,
    
    // Layout helpers
    isAdminLayout,
    isHomeLayout,
    
    // State
    isLoading,
    error,
    hasFetched,
    
    // Methods
    fetchNavigation,
  }
}
