// @ts-nocheck
<script lang="ts" setup>
import AccountSettingsSecurity from '@/components/AccountSettings/Security.vue'
import Staff from '@/components/users/Staff.vue'


const route = useRoute('users-tab')

const activeTab = computed({
  get: () => route.params.tab,
  set: () => route.params.tab,
})

// tabs
const tabs = [
  { title: 'Staff', icon: 'tabler-users', tab: 'staff' },
  { title: 'Permission', icon: 'tabler-lock', tab: 'permission' },
]

definePageMeta({
  navActiveLink: 'users-tab',
  role: 'admin'
})
</script>

<template>
  <div >
    <VTabs
      v-model="activeTab"
      class="v-tabs-pill"
    >
      <VTab
        v-for="item in tabs"
        :key="item.icon"
        :value="item.tab"
        :to="{ name: 'users-tab', params: { tab: item.tab } }"
      >
        <VIcon
          size="20"
          start
          :icon="item.icon"
        />
        {{ item.title }}
      </VTab>
    </VTabs>

    <ClientOnly>
      <VWindow
        v-model="activeTab"
        class="mt-6 disable-tab-transition"
        :touch="false"
      >
        <!-- Account -->
        <VWindowItem value="staff">
          <Staff />
        </VWindowItem>

        <!-- Security -->
        <VWindowItem value="permission">
          <AccountSettingsSecurity />
        </VWindowItem>

        <!-- Billing -->
        <!-- <VWindowItem value="billing-plans">
          <AccountSettingsBillingAndPlans />
        </VWindowItem> -->

        <!-- Notification -->
        <!-- <VWindowItem value="notification">
          <AccountSettingsNotification />
        </VWindowItem> -->

        <!-- Connections -->
        <!-- <VWindowItem value="connection">
          <AccountSettingsConnections />
        </VWindowItem> -->
      </VWindow>
    </ClientOnly>
  </div>
</template>
