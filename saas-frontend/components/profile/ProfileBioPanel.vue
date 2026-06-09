// @ts-nocheck
<script setup lang="ts">
const { userData } = useUserData()
const { signOut } = useAuth()

// Generate initials from name
const userInitials = computed(() => {
  if (!userData.value?.name) return '?'
  const names = userData.value.name.trim().split(' ')
  if (names.length >= 2) {
    return (names[0][0] + names[names.length - 1][0]).toUpperCase()
  }
  return names[0].substring(0, 2).toUpperCase()
})

// Format member code
const memberCode = computed(() => {
  if (!userData.value?.code) return null
  return `#${userData.value.code}`
})

async function logout() {
  try {
    await signOut({ redirect: false })
  } catch (error) {
    console.log('Failed Post Logout', error)
  }

  try {
    userData.value = null
    navigateTo('/')
  }
  catch (error) {
    throw createError(error)
  }
}

const qrDialog = ref(false)
const openQR = () => {
  qrDialog.value = true
}
</script>

<template>
  <VCard class="bio-panel-card">
    <!-- Gradient header background -->
    <div class="bio-header-bg" />
    
    <VCardText class="text-center bio-content">
      <!-- 👉 Avatar with initials fallback -->
      <VAvatar
        rounded
        :size="100"
        class="bio-avatar elevation-4"
        color="primary"
      >
        <VImg
          v-if="userData?.avatar"
          :src="userData.avatar"
          cover
        />
        <span v-else class="text-3xl font-weight-bold text-white">
          {{ userInitials }}
        </span>
      </VAvatar>

      <!-- 👉 Customer name -->
      <h4 class="text-h5 font-weight-semibold mt-4 mb-1">
        {{ userData?.name || 'Member' }}
      </h4>
      
      <!-- 👉 Member code -->
      <VChip
        v-if="memberCode"
        size="small"
        color="secondary"
        variant="tonal"
        class="mb-4"
      >
        <VIcon start icon="tabler-id" size="14" />
        {{ memberCode }}
      </VChip>
      <div v-else class="text-caption text-disabled mb-4">
        No member ID
      </div>

      <!-- 👉 QR Button - Primary action -->
      <VBtn
        block
        color="primary"
        size="large"
        prepend-icon="tabler-qrcode"
        class="mb-3"
        @click.prevent="openQR"
      >
        Show QR Code
      </VBtn>
      
      <!-- 👉 Logout - Secondary/subtle action -->
      <VBtn
        variant="text"
        color="error"
        size="small"
        prepend-icon="tabler-logout"
        @click="logout"
      >
        Sign Out
      </VBtn>
    </VCardText>
  </VCard>

  <ProfileShowQr 
    :is-dialog-visible="qrDialog"
    @update:isDialogVisible="val => qrDialog = val"
  />
</template>

<style lang="scss" scoped>
.bio-panel-card {
  overflow: hidden;
  position: relative;
}

.bio-header-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 80px;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.7) 100%);
}

.bio-content {
  position: relative;
  padding-top: 40px !important;
}

.bio-avatar {
  border: 4px solid rgb(var(--v-theme-surface));
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.8) 100%);
}
</style>
