import { useAppPermission } from '@/composables/usePermission'
import allNavItems from '@/navigation/vertical'
import type { Actions, Subjects } from '@/types/permission'
import type { VerticalNavItems } from '@layouts/types'

interface NavItemWithPermission {
  heading?: string
  title?: string
  to?: string | object
  icon?: { icon: string }
  subject?: Subjects
  action?: Actions
  children?: NavItemWithPermission[]
}

/**
 * Composable for filtering navigation items based on user permissions.
 * 
 * Uses CASL abilities to determine which nav items the current user can see.
 * 
 * @example
 * const { filteredNavItems } = useFilteredNavigation()
 * // Use filteredNavItems in your layout template
 */
export function useFilteredNavigation() {
  const { can, currentRole } = useAppPermission()

  /**
   * Filter navigation items based on permissions
   */
  const filteredNavItems = computed<VerticalNavItems>(() => {
    // If no role, show nothing
    if (!currentRole.value) {
      return []
    }

    return (allNavItems as NavItemWithPermission[]).filter((item) => {
      // Items without subject are always visible
      if (!item.subject) {
        return true
      }

      // Check if user can perform the action on the subject
      const action = item.action || 'read'
      return can(action, item.subject)
    }) as VerticalNavItems
  })

  return {
    filteredNavItems,
  }
}
