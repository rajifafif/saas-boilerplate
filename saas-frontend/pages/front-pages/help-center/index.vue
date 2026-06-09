<script setup lang="ts">
import Footer from '@/views/front-pages/front-page-footer.vue'
import Navbar from '@/views/front-pages/front-page-navbar.vue'
import { useConfigStore } from '@core/stores/config'

const store = useConfigStore()
store.skin = 'default'

definePageMeta({
  layout: 'blank',
  public: true,
})

const supportTopics = [
  {
    icon: 'tabler-building-community',
    title: 'Organizations',
    description: 'Set up organization profile, branches, and workspace basics.',
  },
  {
    icon: 'tabler-users',
    title: 'Teams',
    description: 'Invite administrators, managers, and members with clear roles.',
  },
  {
    icon: 'tabler-shield-check',
    title: 'Roles & permissions',
    description: 'Review access levels before adding or changing team permissions.',
  },
]
</script>

<template>
  <div class="help-center-page">
    <Navbar />

    <AppSearchHeader
      subtitle="Common troubleshooting topics: organizations, teams, roles, and workspace setup"
      custom-class="rounded-0"
      placeholder="Search help articles"
    >
      <template #title>
        <h4
          class="text-h4 font-weight-medium"
          style="color: rgba(var(--v-theme-primary), 1);"
        >
          Hello, how can we help?
        </h4>
      </template>
    </AppSearchHeader>

    <VContainer class="help-center-section">
      <VRow>
        <VCol
          v-for="topic in supportTopics"
          :key="topic.title"
          cols="12"
          md="4"
        >
          <VCard class="h-100">
            <VCardText class="pa-6">
              <VAvatar
                color="primary"
                variant="tonal"
                class="mb-4"
              >
                <VIcon :icon="topic.icon" />
              </VAvatar>
              <h5 class="text-h5 mb-2">
                {{ topic.title }}
              </h5>
              <p class="mb-0 text-body-1">
                {{ topic.description }}
              </p>
            </VCardText>
          </VCard>
        </VCol>
      </VRow>
    </VContainer>

    <VContainer class="pb-12">
      <VCard color="primary" variant="tonal">
        <VCardText class="pa-8 text-center">
          <h4 class="text-h4 mb-2">
            Still need help?
          </h4>
          <p class="mb-0">
            Contact your workspace administrator or support owner for account and access questions.
          </p>
        </VCardText>
      </VCard>
    </VContainer>

    <Footer />
  </div>
</template>

<style lang="scss">
.help-center-page {
  .search-header {
    background-size: cover !important;
    padding-block: 9.25rem 4.75rem !important;
  }

  .help-center-section {
    padding-block: 5.25rem;
  }
}
</style>
