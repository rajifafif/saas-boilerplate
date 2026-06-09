<script setup lang="ts">
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { useRoute, useRouter } from 'vue-router'

import authV2ResetIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ResetIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const route = useRoute()
const router = useRouter()

const token = ref<string | null>(route.query.token as string ?? null)
const email = ref<string | null>(route.query.email as string ?? null)
const password = ref('')
const passwordConfirmation = ref('')
const btnLoading = ref(false)
const success = ref(false)
const errors = ref<Record<string, string | undefined>>({})

const { showSnackbar } = useSnackbar()

const authThemeImg = useGenerateImageVariant(authV2ResetIllustrationLight, authV2ResetIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePageMeta({
  layout: 'blank',
  auth: {
    unauthenticatedOnly: true,
  }
})

const resetPassword = async () => {
  btnLoading.value = true

  try {
    const { data, error } = await useApi('/reset-password')
      .post({
        token: token.value,
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      })
      .json()

    if (error.value) {
      errors.value = error.value.data.errors ?? {}
      showSnackbar(error.value.data.message, 'error')
      btnLoading.value = false
      return
    }

    showSnackbar(data.value.message, 'success')
    success.value = true
    btnLoading.value = false

    // Redirect after short delay
    setTimeout(() => {
      router.push({ name: 'login' })
    }, 2000)

  } catch (err: any) {
    showSnackbar(err.message, 'error')
    btnLoading.value = false
  }
}
</script>

<template>
  <NuxtLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">{{ themeConfig.app.title }}</h1>
    </div>
  </NuxtLink>

  <VRow class="auth-wrapper bg-surface" no-gutters>
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div class="d-flex align-center justify-center w-100 h-100" style="padding-inline: 150px;">
          <VImg max-width="468" :src="authThemeImg" class="auth-illustration mt-16 mb-2" />
        </div>
        <img class="auth-footer-mask" :src="authThemeMask" alt="auth-footer-mask" height="280" width="100" />
      </div>
    </VCol>

    <VCol cols="12" md="4" class="d-flex align-center justify-center">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-4">

        <VCardText v-if="!success">
          <h4 class="text-h4 mb-1">Reset Your Password 🔐</h4>
          <p class="mb-0">Enter your new password below.</p>
        </VCardText>

        <VCardText v-if="!success">
          <VForm @submit.prevent="resetPassword">
            <VRow>
              <!-- Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="password"
                  label="New Password"
                  type="password"
                  :error-messages="errors?.password"
                  autocomplete="new-password"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Confirm Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="passwordConfirmation"
                  label="Confirm Password"
                  type="password"
                  :error-messages="errors?.password_confirmation"
                  autocomplete="new-password"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Submit -->
              <VCol cols="12">
                <VBtn block type="submit" :loading="btnLoading" :disabled="btnLoading">
                  Reset Password
                </VBtn>
              </VCol>

              <!-- Back -->
              <VCol cols="12">
                <NuxtLink class="d-flex align-center justify-center" :to="{ name: 'login' }">
                  <VIcon icon="tabler-chevron-left" size="20" class="me-1 flip-in-rtl" />
                  <span>Back to Login</span>
                </NuxtLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>

        <VCardText v-else>
          <h4 class="text-h4 mb-2 text-success">Password Updated Successfully 🎉</h4>
          <p>You’ll be redirected shortly...</p>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";
</style>
