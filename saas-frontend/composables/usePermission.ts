import type { Actions, AppAbility, Subjects } from '@/plugins/casl/ability'
import { defineAbilitiesFor } from '@/plugins/casl/ability'
import { useOrganizationStore } from '@/stores/organizationStore'
import { useAbility } from '@casl/vue'

/**
 * Composable for checking permissions in the application.
 * 
 * Provides reactive permission checks based on the current user's role
 * in the active organization.
 * 
 * @example
 * const { can, isAdmin, isMember } = useAppPermission()
 * 
 * // Check specific permission
 * if (can('read', 'Dashboard')) { ... }
 * 
 * // Check role
 * if (isAdmin.value) { ... }
 */
export function useAppPermission() {
  const orgStore = useOrganizationStore()
  const ability = useAbility<AppAbility>()

  /**
   * Check if user can perform action on subject
   */
  const can = (action: Actions, subject: Subjects): boolean => {
    return ability.can(action, subject)
  }

  /**
   * Check if user cannot perform action on subject
   */
  const cannot = (action: Actions, subject: Subjects): boolean => {
    return ability.cannot(action, subject)
  }

  /**
   * Reactive check: is current user an admin?
   */
  const isAdmin = computed(() => orgStore.isAdmin)


  /**
   * Reactive check: is current user a member?
   */
  const isMember = computed(() => orgStore.isMember)

  /**
   * Current role
   */
  const currentRole = computed(() => orgStore.currentRole)

  /**
   * Update abilities when role changes
   */
  const updateAbilities = () => {
    const role = orgStore.currentRole
    const newAbility = defineAbilitiesFor(role)
    ability.update(newAbility.rules)
  }

  // Watch for role changes and update abilities
  watch(
    () => orgStore.currentRole,
    () => {
      updateAbilities()
    },
    { immediate: true }
  )

  return {
    can,
    cannot,
    isAdmin,

    isMember,
    currentRole,
    updateAbilities,
  }
}
