<script setup lang="ts">
import { useOrganizationStore } from '@/stores/organizationStore'

const orgStore = useOrganizationStore()
const isLoading = ref(false)

// Get organizations from store
const organizations = computed(() => orgStore.organizations)
const currentOrganization = computed(() => orgStore.currentOrganization)
const hasMultipleOrgs = computed(() => orgStore.hasMultipleOrganizations)

// Role badge color mapping
const roleBadgeColor = (role: string) => {
  switch (role) {
    case 'owner':
    case 'admin':
      return 'primary'
    case 'manager':
      return 'info'
    case 'staff':
      return 'warning'
    default:
      return 'secondary'
  }
}

// Handle organization switch
const switchOrganization = async (orgId: string) => {
  if (orgId === currentOrganization.value?.id) return

  isLoading.value = true
  try {
    await orgStore.switchOrganization(orgId)
    
    // Reload the page to refresh all data with new org context
    // This is the safest way to ensure all data is refreshed
    window.location.reload()
  } catch (error) {
    console.error('Failed to switch organization:', error)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <!-- Only show if user has multiple organizations -->
  <VMenu
    v-if="hasMultipleOrgs"
    :close-on-content-click="true"
    location="bottom end"
    offset="8"
  >
    <template #activator="{ props }">
      <VBtn
        v-bind="props"
        variant="text"
        class="text-none"
        :loading="isLoading"
      >
        <VIcon
          start
          icon="tabler-building"
          size="20"
        />
        <span class="d-none d-sm-inline">
          {{ currentOrganization?.name || 'Select Organization' }}
        </span>
        <VIcon
          end
          icon="tabler-chevron-down"
          size="16"
        />
      </VBtn>
    </template>

    <VList
      min-width="220"
      max-width="320"
    >
      <VListSubheader>Switch Organization</VListSubheader>

      <VListItem
        v-for="org in organizations"
        :key="org.id"
        :active="org.id === currentOrganization?.id"
        :disabled="isLoading"
        @click="switchOrganization(org.id)"
      >
        <template #prepend>
          <VIcon
            icon="tabler-building"
            :color="org.id === currentOrganization?.id ? 'primary' : undefined"
          />
        </template>

        <VListItemTitle>{{ org.name }}</VListItemTitle>

        <template #append>
          <VChip
            :color="roleBadgeColor(org.role)"
            size="x-small"
            label
          >
            {{ org.role }}
          </VChip>
          <VIcon
            v-if="org.is_default"
            icon="tabler-star"
            color="warning"
            size="16"
            class="ms-2"
          />
        </template>
      </VListItem>
    </VList>
  </VMenu>

  <!-- Single org: just show the name without dropdown -->
  <div
    v-else-if="currentOrganization"
    class="d-flex align-center text-body-1"
  >
    <VIcon
      icon="tabler-building"
      size="20"
      class="me-2"
    />
    <span class="d-none d-sm-inline">{{ currentOrganization.name }}</span>
  </div>
</template>
