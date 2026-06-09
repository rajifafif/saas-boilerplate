<script setup lang="ts">
const { status } = useAuth()

onMounted(() => {
  if (status.value === 'authenticated')
    navigateTo('/manage')
})

definePageMeta({
  layout: 'marketing',
  auth: false,
  public: true,
})
</script>

<template>
  <VContainer class="py-16">
    <VRow align="center" class="py-12" justify="space-between">
      <VCol cols="12" md="6">
        <VChip color="primary" variant="tonal" class="mb-6">
          SaaS Boilerplate
        </VChip>
        <h1 class="text-h2 font-weight-bold mb-6">
          Start faster with a neutral SaaS foundation.
        </h1>
        <p class="text-h6 text-medium-emphasis mb-8">
          Authentication, organizations, roles, team access, and branch-ready administration in one reusable starter.
        </p>
        <div class="d-flex flex-wrap gap-4">
          <VBtn color="primary" size="large" to="/register">
            Create account
          </VBtn>
          <VBtn variant="outlined" size="large" to="/login">
            Sign in
          </VBtn>
        </div>
      </VCol>
      <VCol cols="12" md="5">
        <VCard class="pa-6" elevation="10">
          <VCardTitle>Included primitives</VCardTitle>
          <VList lines="two">
            <VListItem title="Organization workspace" subtitle="Tenant-ready account context" prepend-icon="tabler-building" />
            <VListItem title="Team access" subtitle="Users, roles, and permissions" prepend-icon="tabler-users" />
            <VListItem title="Branch structure" subtitle="Generic locations for SaaS operations" prepend-icon="tabler-map-pin" />
          </VList>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>
