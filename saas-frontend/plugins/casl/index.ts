import { useOrganizationStore } from '@/stores/organizationStore'
import { createMongoAbility } from '@casl/ability'
import { abilitiesPlugin } from '@casl/vue'
import type { Rule } from './ability'
import { defineAbilitiesFor } from './ability'

export default defineNuxtPlugin((nuxtApp) => {
  // Try to get saved abilities from cookie
  const userAbilityRules = useCookie<Rule[]>('userAbilityRules')

  // Create initial ability (empty or from cookie)
  const initialAbility = createMongoAbility(userAbilityRules.value ?? [])

  // Register CASL Vue plugin
  nuxtApp.vueApp.use(abilitiesPlugin, initialAbility, {
    useGlobalProperties: true,
  })

  // Hook to sync abilities when organization changes
  nuxtApp.hook('app:mounted', () => {
    try {
      const orgStore = useOrganizationStore()

      // Watch for organization/role changes
      watch(
        () => orgStore.currentRole,
        (newRole) => {
          if (newRole) {
            const newAbility = defineAbilitiesFor(newRole)
            initialAbility.update(newAbility.rules)

            // Save to cookie for persistence
            userAbilityRules.value = newAbility.rules as Rule[]
          } else {
            // Clear abilities when no role
            initialAbility.update([])
            userAbilityRules.value = []
          }
        },
        { immediate: true }
      )
    } catch (error) {
      console.warn('Could not setup ability watcher:', error)
    }
  })
})

