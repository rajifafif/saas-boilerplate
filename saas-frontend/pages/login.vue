<!-- ❗Errors in the form are set on line 60 -->
<script setup lang="ts">
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { VForm } from 'vuetify/components/VForm'

// Page meta setup
definePageMeta({
  layout: 'blank',
  auth: {
    unauthenticatedOnly: true,
    navigateAuthenticatedTo: '/'
  }
})

// Theme and appearance
const authThemeImg = useGenerateImageVariant(
  authV2LoginIllustrationLight, 
  authV2LoginIllustrationDark, 
  authV2LoginIllustrationBorderedLight, 
  authV2LoginIllustrationBorderedDark, 
  true
)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

// Form refs and state
const refVForm = ref<VForm | null>(null)
const isPasswordVisible = ref(false)
const isLoading = ref(false)

// Auth composable with remember me support
const { signInWithRemember, getRememberPreference } = useAuthRemember()
const { debugAuth } = useAuthDebug()

// Initialize remember me from stored preference
const rememberMe = ref(getRememberPreference())

// Form data
const credentials = ref({
  email: '',
  password: '',
})

// Form validation
const errors = ref<Record<string, string | undefined>>({
  email: undefined,
  password: undefined,
  general: undefined,
})

// Form validation rules
const rules = {
  email: [
    (v: string) => !!v || 'Email is required',
    (v: string) => /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) || 'Email must be valid',
  ],
  password: [
    (v: string) => !!v || 'Password is required',
    (v: string) => v.length >= 6 || 'Password must be at least 6 characters',
  ],
}

// Get URL params for redirect
const route = useRoute()
const router = useRouter()
const redirectPath = computed(() => {
  // Use the redirect query param if it exists and is a valid path
  const redirectParam = route.query.redirect as string | undefined
  
  // Make sure redirect is a valid path (starts with /) and not external URL
  if (redirectParam && redirectParam.startsWith('/') && !redirectParam.includes('://')) {
    return redirectParam
  }
  
  // Default redirect after login
  return '/'
})

// Handle form submission
const onSubmit = async () => {
  // Clear previous errors
  errors.value = {
    email: undefined,
    password: undefined,
    general: undefined,
  }
  
  try {
    // Validate form
    const { valid: isValid } = await refVForm.value?.validate() || { valid: false }
    
    if (!isValid) return
    
    // Set loading state
    isLoading.value = true
    
    // Attempt login with remember me support
    const result = await signInWithRemember({
      email: credentials.value.email,
      password: credentials.value.password,
      redirect: false, // Handle redirects manually
    }, rememberMe.value, {
      callbackUrl: redirectPath.value,
      external: true,
    })
    .catch((error: any) => {
      if (error?.data?.errors) {
        errors.value = error.data.errors
      } else {
        errors.value.general = error?.data?.message || 'An error occurred'
      }
      throw error
    })

    // Check if user data is in the response
    if (result?.user) {
      console.log('Login successful, user data received')
      
      // Debug authentication state
      setTimeout(() => {
        debugAuth()
      }, 1000)
      
      // Navigation will be handled by nuxt-auth automatically
    }
    
  } catch (error: any) {
    console.log('Error details:', error)
    debugAuth() // Debug on error too
  
    // Check for validation errors (422)
    if (error.status === 422 || error.response?.status === 422) {
      // Try to extract validation errors from response
      const validationErrors = error.response?.data || 'Validation failed'
      console.log('Validation errors:', validationErrors)
    }
    
    return error
    
  } finally {
    isLoading.value = false
  }
}

// Reset form errors when inputs change
watch([() => credentials.value.email, () => credentials.value.password], () => {
  errors.value = {
    email: undefined,
    password: undefined,
    general: undefined,
  }
})
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
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 6.25rem;"
        >
          <VImg
            max-width="613"
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
      class="auth-card-v2 d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Welcome to <span class="text-capitalize"> {{ themeConfig.app.title }} </span>! 👋🏻
          </h4>
          <p class="mb-0">
            Please sign-in to your account and start the adventure
          </p>
        </VCardText>
        <VCardText>
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <!-- General Errors -->
            <VAlert
              v-if="errors.general"
              color="error"
              variant="tonal"
              closable
              class="mb-4"
              @click:close="errors.general = undefined"
            >
              {{ errors.general }}
            </VAlert>

            <VRow>
              <!-- email -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.email"
                  label="Email"
                  placeholder="johndoe@email.com"
                  type="email"
                  autofocus
                  :error-messages="errors.email"
                  :disabled="isLoading"
                />
              </VCol>

              <!-- password -->
              <VCol cols="12">
                <AppTextField
                  v-model="credentials.password"
                  label="Password"
                  placeholder="············"
                  :rules="rules.password"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="password"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :disabled="isLoading"
                />

                <div class="d-flex align-center flex-wrap justify-space-between my-6">
                  <VCheckbox
                    :id="useId()"
                    v-model="rememberMe"
                    label="Remember me"
                    :disabled="isLoading"
                  />
                  <NuxtLink
                    class="text-primary ms-2 mb-1"
                    :to="{ name: 'forgot-password' }"
                  >
                    Forgot Password?
                  </NuxtLink>
                </div>

                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="isLoading"
                >
                  Login
                </VBtn>
              </VCol>

              <!-- auth providers -->
              <VCol
                cols="12"
                class="text-center"
              >
              <!-- login with google -->
                <AuthProvider type="login"/>
              </VCol>
              <VCol
                cols="12"
                class="d-flex align-center"
              >
                <VDivider />
                <span class="mx-4">or</span>
                <VDivider />
              </VCol>


              <!-- create account -->
              <VCol
                cols="12"
                class="text-center"
              >
                <span>New on our platform?</span>
                <NuxtLink
                  class="text-primary ms-1"
                  :to="{ name: 'register' }"
                >
                  Create an account
                </NuxtLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";
</style>
