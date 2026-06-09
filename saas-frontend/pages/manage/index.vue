<script setup lang="ts">
definePageMeta({
  auth: true,
})

const { userData } = useUserData()

const firstName = computed(() => userData.value?.name?.split(' ')[0] || 'there')

const metrics = [
  {
    title: 'Workspace status',
    value: 'Active',
    icon: 'tabler-building',
    tone: 'primary',
    description: 'Your SaaS organization shell is ready for tenant operations.',
  },
  {
    title: 'Access control',
    value: 'RBAC',
    icon: 'tabler-shield-check',
    tone: 'success',
    description: 'Roles and permissions keep team access scoped and auditable.',
  },
  {
    title: 'Team operations',
    value: 'Enabled',
    icon: 'tabler-users',
    tone: 'info',
    description: 'Invite users, manage staff, and keep member records current.',
  },
  {
    title: 'Branch ready',
    value: 'Multi-site',
    icon: 'tabler-map-pin',
    tone: 'warning',
    description: 'Use branch-ready structures for organizations with multiple locations.',
  },
]

const quickActions = [
  {
    title: 'Manage team',
    description: 'Review organization users, staff assignments, and tenant access.',
    icon: 'tabler-user-plus',
    to: { name: 'manage-users-tab', params: { tab: 'staff' } },
  },
  {
    title: 'Roles and permissions',
    description: 'Tune reusable SaaS access policies for administrators and members.',
    icon: 'tabler-lock-access',
    to: '/manage/roles',
  },
  {
    title: 'Account settings',
    description: 'Update profile, organization preferences, and security settings.',
    icon: 'tabler-settings',
    to: { name: 'manage-account-settings-tab', params: { tab: 'account' } },
  },
]
</script>

<template>
  <div class="neutral-dashboard">
    <VCard class="mb-6 overflow-hidden" color="primary" variant="tonal">
      <VCardText class="pa-6 pa-md-8">
        <div class="d-flex flex-column flex-md-row justify-space-between gap-6">
          <div>
            <p class="text-overline mb-2">SaaS boilerplate</p>
            <h1 class="text-h3 mb-3">Welcome back, {{ firstName }}</h1>
            <p class="text-body-1 mb-0 dashboard-copy">
              Run your organization from one neutral workspace: team, roles, permissions, profile, and branch-ready operations.
            </p>
          </div>

          <div class="d-flex align-center">
            <VBtn color="primary" size="large" rounded="xl" :to="{ name: 'manage-users-tab', params: { tab: 'staff' } }" prepend-icon="tabler-user-plus">
              Invite user
            </VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>

    <VRow class="match-height mb-2">
      <VCol v-for="metric in metrics" :key="metric.title" cols="12" sm="6" lg="3">
        <VCard>
          <VCardText>
            <VAvatar :color="metric.tone" variant="tonal" rounded size="48" class="mb-4">
              <VIcon :icon="metric.icon" size="26" />
            </VAvatar>
            <div class="text-caption text-secondary mb-1">{{ metric.title }}</div>
            <h2 class="text-h5 mb-2">{{ metric.value }}</h2>
            <p class="text-body-2 text-secondary mb-0">{{ metric.description }}</p>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow class="match-height">
      <VCol v-for="action in quickActions" :key="action.title" cols="12" md="4">
        <VCard>
          <VCardText class="pa-6">
            <VAvatar color="primary" variant="tonal" rounded="lg" class="mb-4">
              <VIcon :icon="action.icon" />
            </VAvatar>
            <h3 class="text-h6 mb-2">{{ action.title }}</h3>
            <p class="text-body-2 text-secondary mb-4">{{ action.description }}</p>
            <VBtn color="primary" variant="text" class="px-0" :to="action.to">Open workspace</VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </div>
</template>

<style scoped>
.neutral-dashboard { min-height: 100%; }
.gap-6 { gap: 24px; }
.dashboard-copy { max-width: 760px; }
</style>
