<script setup lang="ts">
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const email = ref('')
const btnLoading = ref(false)
const emailSent = ref(false)
const errors = ref<Record<string, string | undefined>>({
  email: undefined,
})
const {showSnackbar} = useSnackbar()

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePageMeta({
  layout: 'blank',
  auth: {
    unauthenticatedOnly: true,
  }
})

const requestResetLink = async () => {
  btnLoading.value = true
  try {
    const { data, error } =  await useApi('/forgot-password')
      .post({
        email: email.value,
      })
      .json()

    if (error.value) {
      errors.value = error.value.data.errors ?? {}

      // console.log(error.va)
      showSnackbar(error.value.data.message, 'error')

      btnLoading.value = false
      return false;
    }

    errors.value = {}
    emailSent.value = true

    // Success
    btnLoading.value = false
    return { success: true, message: data.value?.message }
  } catch (err: any) {

    btnLoading.value = false
    showSnackbar(err.message || 'An error occurred', 'error')
  }
}

</script>

<template>
  <NuxtLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </NuxtLink>

  <VRow
    class="auth-wrapper bg-surface"
    no-gutters
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 150px;"
        >
          <VImg
            max-width="468"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText v-if="!emailSent">
          <h4 class="text-h4 mb-1">
            Forgot Password? 🔒
          </h4>
          <p class="mb-0">
            Enter your email to reset your password
          </p>
        </VCardText>

        <VCardText v-else>
          <h4 class="text-h4 mb-1">
            Reset Your Password!
          </h4>
          <p class="mb-0">
            We've Sent a Password Reset Link to Your Email
          </p>
        </VCardText>

        <VCardText v-if="!emailSent">
          <VForm @submit.prevent="requestResetLink">
            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="email"
                  autofocus
                  label="Email"
                  type="email"
                  placeholder="johndoe@email.com"
                  :error-messages="errors?.email"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Reset link -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="btnLoading"
                  :disabled="btnLoading"
                >
                  Send Reset Link
                </VBtn>
              </VCol>

              <!-- back to login -->
              <VCol cols="12">
                <NuxtLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    icon="tabler-chevron-left"
                    size="20"
                    class="me-1 flip-in-rtl"
                  />
                  <span>Back to login</span>
                </NuxtLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>


        <VCardText v-else>
            <VRow>

              
              <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4">or</span>
                <VDivider />
              </VCol>

              <!-- Back To Login -->
              <VCol cols="12">
                <VBtn
                  block
                  to="login"
                >
                  Back To Login
                </VBtn>
              </VCol>
            </VRow>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth.scss";
</style>
