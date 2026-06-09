import type { Branch, Organization, OrganizationRole } from '@/types/organization'
import { defineStore } from 'pinia'

interface OrganizationState {
  organizations: Organization[]
  currentOrganization: Organization | null
  currentBranch: Branch | null
  isLoading: boolean
  isInitialized: boolean
}

export const useOrganizationStore = defineStore('organization', {
  state: (): OrganizationState => ({
    organizations: [],
    currentOrganization: null,
    currentBranch: null,
    isLoading: false,
    isInitialized: false,
  }),

  getters: {
    /**
     * Current user's role in the active organization
     */
    currentRole: (state): OrganizationRole | null => state.currentOrganization?.role || null,

    /**
     * Check if current user is an admin (owner or admin role)
     */
    isAdmin: (state): boolean => ['owner', 'admin'].includes(state.currentOrganization?.role || ''),

    /**
     * Check if current user is staff
     */
    isStaff: (state): boolean => state.currentOrganization?.role === 'staff',

    /**
     * Check if current user is a regular member
     */
    isMember: (state): boolean => state.currentOrganization?.role === 'member',

    /**
     * Check if user belongs to multiple organizations
     */
    hasMultipleOrganizations: (state): boolean => state.organizations.length > 1,

    /**
     * Get current organization ID for API headers
     */
    currentOrganizationId: (state): string | null => state.currentOrganization?.id || null,

    /**
     * Get current branch ID for API headers
     */
    currentBranchId: (state): string | null => state.currentBranch?.id || null,
  },

  actions: {
    /**
     * Set organizations from login response
     */
    setOrganizations(orgs: Organization[]) {
      this.organizations = orgs
      this.isInitialized = true

      // Auto-select default or first organization
      if (!this.currentOrganization && orgs.length > 0) {
        const defaultOrg = orgs.find(o => o.is_default) || orgs[0]
        this.setCurrentOrganization(defaultOrg)
      }
    },

    /**
     * Set current organization
     */
    setCurrentOrganization(org: Organization) {
      this.currentOrganization = org

      // Auto-select first active branch if available
      if (org.branches && org.branches.length > 0) {
        const activeBranch = org.branches.find(b => b.is_active) || org.branches[0]
        this.currentBranch = activeBranch
      } else {
        this.currentBranch = null
      }

      // Persist to cookie for page refresh
      const orgCookie = useCookie('current-organization-id', {
        maxAge: 60 * 60 * 24 * 30, // 30 days
        sameSite: 'lax',
      })
      orgCookie.value = org.id
    },

    /**
     * Set current branch
     */
    setCurrentBranch(branch: Branch | null) {
      this.currentBranch = branch

      // Persist to cookie
      const branchCookie = useCookie('current-branch-id', {
        maxAge: 60 * 60 * 24 * 30,
        sameSite: 'lax',
      })
      branchCookie.value = branch?.id || null
    },

    /**
     * Switch to a different organization via API.
     * 
     * The API returns new access and refresh tokens with the new org context,
     * making the switch completely stateless.
     */
    async switchOrganization(orgId: string) {
      if (this.currentOrganization?.id === orgId) return

      this.isLoading = true

      try {
        const { data, error } = await useApi(`/organizations/${orgId}/switch`).post().json()

        if (error.value) {
          console.error('Failed to switch organization:', error.value)
          throw error.value
        }

        const response = data.value

        // Store new tokens from response (stateless JWT approach)
        if (response?.access_token) {
          // Import dynamically to avoid circular dependency
          const { useSecureStorage } = await import('@/composables/useSecureStorage')
          const storage = useSecureStorage()
          
          await storage.setAccessToken(response.access_token, response.expires_in || 900)
          
          if (response.refresh_token) {
            await storage.setRefreshToken(response.refresh_token)
          }
          
          // Also update the auth.token cookie for compatibility
          const tokenCookie = useCookie('auth.token', {
            maxAge: 60 * 60 * 24 * 30,
            sameSite: 'lax',
          })
          tokenCookie.value = response.access_token
        }

        // Find the org in our list and update
        const org = this.organizations.find(o => o.id === orgId)
        if (org) {
          // Update role from response if provided
          if (response?.data?.role) {
            org.role = response.data.role
          }
          this.setCurrentOrganization(org)

          // Set branch from response and keep persisted branch context in sync.
          if (response?.data?.branch) {
            this.setCurrentBranch(response.data.branch)
          }
        }

        return response
      } catch (err) {
        console.error('Error switching organization:', err)
        throw err
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Fetch organizations from API (useful after page refresh)
     */
    async fetchOrganizations() {
      if (this.isLoading) return

      this.isLoading = true

      try {
        const { data, error } = await useApi('/organizations').json()

        if (error.value) {
          console.error('Failed to fetch organizations:', error.value)
          return
        }

        if (data.value?.data) {
          this.setOrganizations(data.value.data)

          // Restore from cookie if available
          const savedOrgId = useCookie('current-organization-id').value
          if (savedOrgId) {
            const savedOrg = this.organizations.find(o => o.id === savedOrgId)
            if (savedOrg) {
              this.setCurrentOrganization(savedOrg)
            }
          }
        }
      } catch (err) {
        console.error('Error fetching organizations:', err)
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Clear all organization state (on logout)
     */
    clear() {
      this.organizations = []
      this.currentOrganization = null
      this.currentBranch = null
      this.isInitialized = false

      // Clear cookies
      const orgCookie = useCookie('current-organization-id')
      const branchCookie = useCookie('current-branch-id')
      orgCookie.value = null
      branchCookie.value = null
    },
  },
})
