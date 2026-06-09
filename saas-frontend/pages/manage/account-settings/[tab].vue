<script lang="ts" setup>
import AccountSettingsAccount from '@/components/AccountSettings/Account.vue'
import AccountSettingsSecurity from '@/components/AccountSettings/Security.vue'


const route = useRoute('manage-account-settings-tab')

const activeTab = computed({
  get: () => route.params.tab,
  set: () => route.params.tab,
})

// tabs
const tabs = [
  { title: 'Account', icon: 'tabler-users', tab: 'account' },
  { title: 'Security', icon: 'tabler-lock', tab: 'security' },
  // { title: 'Billing & Plans', icon: 'tabler-file-text', tab: 'billing-plans' },
  // { title: 'Notifications', icon: 'tabler-bell', tab: 'notification' },
  // { title: 'Connections', icon: 'tabler-link', tab: 'connection' },
]

definePageMeta({
  navActiveLink: 'manage-account-settings-tab',

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
        :to="{ name: 'manage-account-settings-tab', params: { tab: item.tab } }"
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
        <VWindowItem value="account">
          <AccountSettingsAccount />
        </VWindowItem>

        <!-- Security -->
        <VWindowItem value="security">
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
