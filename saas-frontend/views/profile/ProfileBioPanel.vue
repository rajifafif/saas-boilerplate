<script setup lang="ts">

const isUserInfoEditDialogVisible = ref(false)
const isUpgradePlanDialogVisible = ref(false)


const { userData } = useUserData()
const { signOut } = useAuth()

async function logout() {
  try {
    await signOut({ redirect: false })
  } catch (error) {
    console.log('Failed Post Logout', error)
  }

  try {
    // Remove "userData" from cookie
    userData.value = null

    // Reset user abilities
    // ability.update([])

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
  <VRow>
    <!-- SECTION Customer Details -->
    <VCol cols="12">
      <VCard>
        <VCardText class="text-center pt-12">
          <!-- 👉 Avatar -->
          <VAvatar
            rounded
            :size="120"
            :color="true ? 'primary' : undefined"
            :variant="true ? 'tonal' : undefined"
          >
            <VImg
              v-if="userData.avatar"
              :src="userData.avatar"
            />
            <span
              v-else
              class="text-5xl font-weight-medium"
            >
              {{ avatarText(userData.customer) }}
            </span>
          </VAvatar>

          <!-- 👉 Customer fullName -->
          <h5 class="text-h5 mt-4">
            {{ userData.name }}
          </h5>
          <div class="text-body-1">
            Customer ID #{{ userData.code }}
          </div>

        </VCardText>

        <VCardText>
          <h5 class="text-h5">
            Credit
          </h5>

          <VDivider class="my-4" />
          
          <div class="d-flex justify-space-evenly gap-x-5 mt-6">
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded-full
                class="me-3"
              >
                <VIcon icon="tabler-circle-letter-n" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <h5 class="text-h5">
                  {{ userData.group_credits }}
                </h5>
                <div class="text-body-1">
                  Group
                </div>
              </div>
            </div>
            <div class="d-flex align-center">
              <VAvatar
                variant="tonal"
                color="primary"
                rounded-full
                class="me-3"
              >
                <VIcon icon="tabler-circle-letter-n" />
              </VAvatar>
              <div class="d-flex flex-column align-start">
                <h5 class="text-h5">
                  {{ userData.private_credits }}
                </h5>
                <div class="text-body-1">
                  Private
                </div>
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="text-center">
          <VBtn
            block
            prepend-icon="tabler-qrcode"
            @click.prevent="openQR"
          >
            Show QR
          </VBtn>

          <ProfileShowQr 
            :is-dialog-visible="qrDialog"
            @update:isDialogVisible="val => qrDialog = val"
          />
        </VCardText>
        
        <VCardText class="text-center">
          <VBtn
            block
            :to="{ name: 'profile-tab', params: { tab: 'detail' } }"
            prepend-icon="tabler-pencil"
          >
            Edit Profile
          </VBtn>
        </VCardText>

        <VCardText class="text-center">
          <VBtn
            block
            :to="{ name: 'profile-tab', params: { tab: 'change-password' } }"
            prepend-icon="tabler-shield"
          >
            Change Password
          </VBtn>
        </VCardText>

        <VDivider />

        <VCardText class="text-center">
          <VBtn
            color="error"
            block
            prepend-icon="tabler-logout"
            @click="logout"
          >
            Logout
          </VBtn>
        </VCardText>
      </VCard>
    </VCol>
    <!-- !SECTION -->

  </VRow>
  <UserInfoEditDialog
    v-model:is-dialog-visible="isUserInfoEditDialogVisible"
    :user-data="customerData"
  />
  <UserUpgradePlanDialog v-model:is-dialog-visible="isUpgradePlanDialogVisible" />
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 0.5rem;
}

.current-plan {
  background: linear-gradient(45deg, rgb(var(--v-theme-primary)) 0%, #9e95f5 100%);
  color: #fff;
}
</style>
