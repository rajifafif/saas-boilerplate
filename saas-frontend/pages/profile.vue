<script lang="ts" setup>
import ProfileBioPanel from '@/components/profile/ProfileBioPanel.vue'
import ProfileChangePassword from '@/components/profile/ProfileChangePassword.vue'
import ProfileDetail from '@/components/profile/ProfileDetail.vue'

const route = useRoute()
const router = useRouter()

const defaultTab = 'detail'
const activeTab = ref((route.query.tab as string) || defaultTab)

watch(() => route.query.tab, (newTab) => {
  const tabValue = (newTab as string) || defaultTab
  if (tabValue !== activeTab.value)
    activeTab.value = tabValue
})

watch(activeTab, (newTab) => {
  if (newTab !== route.query.tab)
    router.replace({ query: { ...route.query, tab: newTab } })
})

definePageMeta({
  auth: true,
})

const tabs = [
  { title: 'Profile', icon: 'tabler-user-circle', tab: 'detail' },
  { title: 'Change Password', icon: 'tabler-lock', tab: 'change-password' },
]
</script>

<template>
  <div class="landing-page-wrapper profile-page" style="min-height: 100vh;">
    <div style="height: 90px;" />
    <VContainer>
      <VRow>
        <VCol cols="12" md="4" class="bio-col">
          <div class="bio-sticky">
            <ProfileBioPanel />
          </div>
        </VCol>

        <VCol cols="12" md="8">
          <div class="tabs-container">
            <VTabs v-model="activeTab" class="v-tabs-pill" show-arrows>
              <VTab v-for="item in tabs" :key="item.tab" :value="item.tab">
                <VIcon size="18" start :icon="item.icon" />
                <span class="tab-title">{{ item.title }}</span>
              </VTab>
            </VTabs>
          </div>

          <VWindow v-model="activeTab" class="mt-6 disable-tab-transition" :touch="false">
            <VWindowItem value="detail">
              <ProfileDetail />
            </VWindowItem>

            <VWindowItem value="change-password">
              <ProfileChangePassword />
            </VWindowItem>
          </VWindow>
        </VCol>
      </VRow>
    </VContainer>
  </div>
</template>

<style lang="scss" scoped>
.profile-page { background: rgb(var(--v-theme-surface)); }
.bio-col { @media (min-width: 960px) { position: relative; } }
.bio-sticky { @media (min-width: 960px) { position: sticky; top: 100px; } }
.tabs-container {
  margin: 0 -12px;
  padding: 0 12px;
  :deep(.v-tabs) { overflow-x: auto; .v-slide-group__content { padding-block: 4px; } }
}
@media (max-width: 480px) {
  .tab-title { display: none; }
  :deep(.v-tab) { min-width: 48px; padding: 0 12px; }
}
@media (min-width: 481px) { .tab-title { display: inline; } }
</style>
