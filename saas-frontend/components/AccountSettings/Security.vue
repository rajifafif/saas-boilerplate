<script lang="ts" setup>
const { showSnackbar } = useSnackbar();


const isCurrentPasswordVisible = ref(false)
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const passwordRequirements = [
  'Minimum 8 characters long - the more, the better',
  'At least one lowercase character',
  'At least one number, symbol, or whitespace character',
]
const newPasswordRules = [
  (value: string) => !!value || 'New password is required',
  (value: string) => value.length >= 8 || 'Password must be at least 8 characters long',
  (value: string) => /[a-z]/.test(value) || 'Password must contain at least one lowercase letter',
  (value: string) => /[0-9!@#$%^&*(),.?":{}|<>]/.test(value) || 'Password must contain at least one number or special character',
]

// Password validation rules for confirm password
const confirmPasswordRules = [
  (value: string) => !!value || 'Confirm password is required',
  (value: string) => value === newPassword.value || 'Passwords do not match', // Ensure it matches the new password
]

const form = ref()

async function validate () {
  const { valid } = await form.value.validate()

  return valid
}

function reset () {
  form.value.reset()
}

const submitForm = async () => {
  if (!(await validate())) return; // Early return for invalid form state

  try {
    const { data, response, error } = await useApi('/change-password')
      .post({
        current_password: currentPassword.value,
        password: newPassword.value,
        password_confirmation: confirmPassword.value,
      })
      .json()

    if (!response.value?.ok) {
      showSnackbar(error.value?.message || 'Failed Changing Password', 'error');
      return;
    }

    // Reset the form on success
    reset();
    showSnackbar(data.value.message, 'success');
  } catch (err) {
    console.log('Error', err)
    showSnackbar("Oops Something went wrong : " + err, 'error');
  }
};

</script>

<template>
  <VRow>
    <!-- SECTION: Change Password -->
    <VCol cols="12">
      <VCard title="Change Password">
        <VForm ref="form">
          <VCardText class="pt-0">
            <!-- 👉 Current Password -->
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 current password -->
                <AppTextField
                  v-model="currentPassword"
                  :type="isCurrentPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isCurrentPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Current Password"
                  autocomplete="on"
                  placeholder="············"
                  @click:append-inner="isCurrentPasswordVisible = !isCurrentPasswordVisible"
                />
              </VCol>
            </VRow>

            <!-- 👉 New Password -->
            <VRow>
              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 new password -->
                <AppTextField
                  v-model="newPassword"
                  :type="isNewPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  :rules="newPasswordRules"
                  label="New Password"
                  autocomplete="on"
                  placeholder="············"
                  @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
                />
              </VCol>

              <VCol
                cols="12"
                md="6"
              >
                <!-- 👉 confirm password -->
                <AppTextField
                  v-model="confirmPassword"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  label="Confirm New Password"
                  autocomplete="on"
                  :rules="confirmPasswordRules"
                  placeholder="············"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>
            </VRow>
          </VCardText>

          <!-- 👉 Password Requirements -->
          <VCardText>
            <h6 class="text-h6 text-medium-emphasis mb-4">
              Password Requirements:
            </h6>

            <VList class="card-list">
              <VListItem
                v-for="item in passwordRequirements"
                :key="item"
                :title="item"
                class="text-medium-emphasis"
              >
                <template #prepend>
                  <VIcon
                    size="10"
                    icon="tabler-circle-filled"
                  />
                </template>
              </VListItem>
            </VList>
          </VCardText>

          <!-- 👉 Action Buttons -->
          <VCardText class="d-flex flex-wrap gap-4">
            <VBtn @click.prevent="submitForm">Save changes</VBtn>

            <VBtn
              type="reset"
              color="secondary"
              variant="tonal"
              @click.prevent="reset"
            >
              Reset
            </VBtn>
          </VCardText>
        </VForm>
      </VCard>
    </VCol>
    <!-- !SECTION -->

  </VRow>
</template>

<style lang="scss" scoped>
.card-list {
  --v-card-list-gap: 16px;
}

.server-close-btn {
  inset-inline-end: 0.5rem;
}
</style>
