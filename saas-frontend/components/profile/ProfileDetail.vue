<script lang="ts" setup>
const { showSnackbar } = useSnackbar()
const { userData, updateUser } = useUserData()
const formUserData = ref<UserData>({ ...(userData.value as UserData) })
const dateOfBirth = ref<string>((formUserData.value.birth_date as string) || '')

const refInputEl = ref<HTMLElement>()

const isConfirmDialogOpen = ref(false)
const wilayahQuery = ref('');
const wilayahs = ref([])
const optionStore = useOptionStore()
const { fetchWilayah } = optionStore
const searchWilayah = async () => {
  fetchWilayah(wilayahQuery.value)
    .then(({data}) =>  {
      wilayahs.value = data.value.data
    })
}

const loading = ref(false)

// Sync userData to formUserData when it's available or changed
watch(() => userData.value, (newVal) => {
  if (newVal) {
    formUserData.value = { ...newVal }
    dateOfBirth.value = (newVal.birth_date as string) || ''
  }
}, { immediate: true })

const handleSave = async () => {
  try {
    loading.value = true
    
    // Update birth_date in form data
    formUserData.value.birth_date = dateOfBirth.value || null

    const { data, error } = await updateUser(formUserData.value)
    
    if (error) {
      showSnackbar(error.message || 'Failed to update profile', 'error')
      return
    }

    showSnackbar('Profile updated successfully', 'success')
  } catch (err) {
    console.error('Error updating profile:', err)
    showSnackbar('Failed to update profile', 'error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <VForm class="profile-form" @submit.prevent="handleSave">
    <!-- Section: Personal Information -->
    <VCard class="mb-4">
      <VCardItem class="section-header">
        <template #prepend>
          <VAvatar color="primary" variant="tonal" size="40">
            <VIcon icon="tabler-user" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6">Personal Information</VCardTitle>
        <VCardSubtitle>Manage your basic profile details</VCardSubtitle>
      </VCardItem>
      
      <VDivider />
      
      <VCardText>
        <VRow>
          <!-- Full Name -->
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formUserData.name"
              label="Full Name"
              placeholder="Enter your full name"
            />
          </VCol>

          <!-- E-mail (readonly) -->
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formUserData.email"
              label="E-mail"
              placeholder="your@email.com"
              type="email"
              disabled
              persistent-hint
              hint="Contact support to change email"
              class="readonly-field"
            />
          </VCol>

          <!-- Phone Number -->
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formUserData.phone"
              label="Phone Number"
              placeholder="e.g., 6281234567890"
              type="tel"
            />
          </VCol>

          <!-- Gender -->
          <VCol cols="12" md="6">
            <div class="text-subtitle-1 mb-1">Gender</div>
            <VRadioGroup
              v-model="formUserData.gender"
              inline
            >
              <VRadio label="Male" value="male" />
              <VRadio label="Female" value="female" />
            </VRadioGroup>
          </VCol>

          <!-- Date of Birth -->
          <VCol cols="12" md="6">
            <DatePicker 
              v-model="dateOfBirth" 
              label="Date of Birth"
              placeholder="Select your birth date"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Section: Address -->
    <VCard class="mb-4">
      <VCardItem class="section-header">
        <template #prepend>
          <VAvatar color="info" variant="tonal" size="40">
            <VIcon icon="tabler-map-pin" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6">Address</VCardTitle>
        <VCardSubtitle>Your current address information</VCardSubtitle>
      </VCardItem>
      
      <VDivider />
      
      <VCardText>
        <VRow>
          <VCol cols="12">
            <AppTextarea
              v-model="formUserData.address"
              label="Full Address"
              placeholder="Enter your complete address"
              rows="3"
            />
          </VCol>

          <!-- Kecamatan (hidden for now) -->
          <VCol v-if="false" cols="12" md="6">
            <AppAutocomplete
              v-model="formUserData.kecamatan_id"
              v-model:search="wilayahQuery"
              @update:search="searchWilayah"
              :items="wilayahs"
              no-filter
              label="Select Kecamatan"
              placeholder="Type to search..."
              item-title="full_name"
              item-value="kelurahan_id"
              :clearable="true"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Section: Emergency Contact -->
    <VCard class="mb-4">
      <VCardItem class="section-header">
        <template #prepend>
          <VAvatar color="error" variant="tonal" size="40">
            <VIcon icon="tabler-emergency-bed" />
          </VAvatar>
        </template>
        <VCardTitle class="text-h6">Emergency Contact</VCardTitle>
        <VCardSubtitle>Person to contact in case of emergency</VCardSubtitle>
      </VCardItem>
      
      <VDivider />
      
      <VCardText>
        <VRow>
          <!-- Contact Name -->
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formUserData.emergency_name"
              label="Contact Name"
              placeholder="Enter emergency contact name"
            />
          </VCol>

          <!-- Contact Phone -->
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formUserData.emergency_phone"
              label="Phone Number"
              placeholder="e.g., 6281234567890"
              type="tel"
            />
          </VCol>

          <!-- Relation -->
          <VCol cols="12" md="6">
            <AppTextField
              v-model="formUserData.emergency_relation"
              label="Relationship"
              placeholder="e.g., Spouse, Parent, Sibling"
            />
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <!-- Sticky Save Button -->
    <div class="save-button-container">
      <VBtn 
        type="submit"
        color="primary" 
        size="large"
        :loading="loading"
        prepend-icon="tabler-device-floppy"
      >
        Save Changes
      </VBtn>
    </div>
  </VForm>

  <!-- Confirm Dialog -->
  <ConfirmDialog
    v-model:is-dialog-visible="isConfirmDialogOpen"
    confirmation-question="Are you sure you want to deactivate your account?"
    confirm-title="Deactivated!"
    confirm-msg="Your account has been deactivated successfully."
    cancel-title="Cancelled"
    cancel-msg="Account Deactivation Cancelled!"
  />
</template>

<style lang="scss" scoped>
.profile-form {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.section-header {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.readonly-field {
  :deep(.v-field) {
    background: rgba(var(--v-theme-on-surface), 0.04);
  }
  
  :deep(.v-field__input) {
    color: rgba(var(--v-theme-on-surface), 0.6);
  }
}

.save-button-container {
  position: sticky;
  bottom: 16px;
  display: flex;
  justify-content: flex-end;
  padding: 16px;
  background: rgb(var(--v-theme-surface));
  border-radius: 8px;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
  margin-top: 8px;
}
</style>

