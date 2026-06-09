<script setup lang="ts">
const isOpen = ref(false)
const search = ref('')
const router = useRouter()

const commands = [
  { id: 'dashboard', title: 'Open Dashboard', icon: 'tabler-layout-dashboard', to: '/manage' },
  { id: 'team', title: 'Manage Team', icon: 'tabler-users', to: '/manage/users/staff' },
  { id: 'branches', title: 'Manage Branches', icon: 'tabler-map-pin', to: '/manage/branches' },
  { id: 'roles', title: 'Manage Roles', icon: 'tabler-shield-check', to: '/manage/roles' },
  { id: 'profile', title: 'Open Profile', icon: 'tabler-user-circle', to: '/profile' },
  { id: 'settings', title: 'Account Settings', icon: 'tabler-settings', to: '/manage/account-settings/account' },
]

const filteredCommands = computed(() =>
  commands.filter(c => c.title.toLowerCase().includes(search.value.toLowerCase()))
)

const handleCommand = (to: string) => {
  router.push(to)
  isOpen.value = false
}

onMounted(() => {
  window.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault()
      isOpen.value = !isOpen.value
    }
  })
})
</script>

<template>
  <VDialog v-model="isOpen" max-width="500" content-class="command-palette-dialog">
    <VCard class="rounded-xl pa-2">
      <VTextField
        v-model="search"
        prepend-inner-icon="tabler-search"
        placeholder="Type a command..."
        variant="solo"
        flat
        autofocus
        hide-details
        class="mb-2"
      />
      <VList nav density="compact">
        <VListItem
          v-for="cmd in filteredCommands"
          :key="cmd.id"
          :prepend-icon="cmd.icon"
          :title="cmd.title"
          class="rounded-lg"
          @click="handleCommand(cmd.to)"
        />
      </VList>
      <VDivider />
      <div class="pa-2 text-caption text-secondary d-flex justify-space-between">
        <span>Search actions...</span>
        <span><kbd>ESC</kbd> to close</span>
      </div>
    </VCard>
  </VDialog>
</template>

<style>
.command-palette-dialog {
  align-self: flex-start;
  margin-top: 10vh !important;
}
kbd {
  background: #eee;
  padding: 2px 4px;
  border-radius: 4px;
  border-bottom: 2px solid #ccc;
}
</style>
