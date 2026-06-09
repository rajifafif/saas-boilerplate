<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'

import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'

import authV2RegisterIllustrationBorderedDark from '@images/pages/auth-v2-register-illustration-bordered-dark.png'
import authV2RegisterIllustrationBorderedLight from '@images/pages/auth-v2-register-illustration-bordered-light.png'
import authV2RegisterIllustrationDark from '@images/pages/auth-v2-register-illustration-dark.png'
import authV2RegisterIllustrationLight from '@images/pages/auth-v2-register-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const { showSnackbar } = useSnackbar()

// Subdomain detection for multi-tenant registration
const { isSubdomain, organizationSlug, organization, isLoading: orgLoading, fetchOrganization } = useSubdomain()

// Fetch org details if on subdomain
onMounted(async () => {
  if (isSubdomain.value && !organization.value) {
    await fetchOrganization()
  }
})

const imageVariant = useGenerateImageVariant(authV2RegisterIllustrationLight,
  authV2RegisterIllustrationDark,
  authV2RegisterIllustrationBorderedLight,
  authV2RegisterIllustrationBorderedDark, true)

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePageMeta({
  layout: 'blank',
  auth: {
    unauthenticatedOnly: true,
  }
})

const registerFormRef = ref<VForm>()

// Form data - different fields based on registration type
const form = ref({
  name: '',
  email: '',
  password: '',
  privacyPolicies: false,
  // Organization fields (only for main domain registration)
  organization_name: '',
  organization_type: 'company',
})

const errors = ref<Record<string, string | undefined>>({
  name: undefined,
  email: undefined,
  password: undefined,
  organization_name: undefined,
})

const isPasswordVisible = ref(false)
const btnLoading = ref(false)
const registerSuccess = ref(false)

// Organization type options
const organizationTypes = [
  { title: 'Company', value: 'company' },
  { title: 'Nonprofit', value: 'nonprofit' },
  { title: 'Education', value: 'education' },
  { title: 'Other', value: 'other' },
]

// Dynamic page title based on context
const pageTitle = computed(() => {
  if (isSubdomain.value && organization.value) {
    return `Join ${organization.value.name}`
  }
  return 'Create Your Organization'
})

const pageSubtitle = computed(() => {
  if (isSubdomain.value && organization.value) {
    return `Register to become a member of ${organization.value.name}`
  }
  return 'Start managing your organization today!'
})

const handleSubmitRegister = async () => {
  if (btnLoading.value) return

  btnLoading.value = true

  // Validate the form before making the request
  const { valid } = await registerFormRef.value?.validate() || {}

  if (!valid) {
    showSnackbar('Please complete the form correctly.', 'error')
    btnLoading.value = false
    return
  }

  try {
    // Build payload based on registration type
    const payload: Record<string, any> = {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      privacyPolicies: form.value.privacyPolicies,
    }

    // Add organization context
    if (isSubdomain.value && organizationSlug.value) {
      // Member registration: joining existing org
      payload.organization_slug = organizationSlug.value
    } else {
      // Owner registration: creating new org
      payload.organization_name = form.value.organization_name
      payload.organization_type = form.value.organization_type
    }

    const { data, error } = await useApi('/register')
      .post(payload)
      .json()

    if (error.value) {
      errors.value = error.value.data?.errors ?? {}
      showSnackbar(error.value.data?.message || 'Registration failed.', 'error')
      btnLoading.value = false
      return { success: false }
    }

    errors.value = {}
    registerSuccess.value = true
    showSnackbar(data.value?.message || 'Registration successful!', 'success')

    return { success: true, message: data.value?.message }
  } catch (err: any) {
    showSnackbar(err.message || 'Something went wrong.', 'error')
    return { success: false }
  } finally {
    btnLoading.value = false
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

  <VRow no-gutters class="auth-wrapper bg-surface">
    <VCol md="8" class="d-none d-md-flex">
      <div class="position-relative bg-background w-100 me-0">
        <div class="d-flex align-center justify-center w-100 h-100" style="padding-inline: 100px;">
          <VImg max-width="500" :src="imageVariant" class="auth-illustration mt-16 mb-2" />
        </div>

        <img class="auth-footer-mask" :src="authThemeMask" alt="auth-footer-mask" height="280" width="100" />
      </div>
    </VCol>

    <VCol cols="12" md="4" class="auth-card-v2 d-flex align-center justify-center" style="background-color: rgb(var(--v-theme-surface));">
      <VCard flat :max-width="500" class="mt-12 mt-sm-0 pa-4">
        <!-- Loading state for org fetch -->
        <VCardText v-if="isSubdomain && orgLoading" class="text-center">
          <VProgressCircular indeterminate color="primary" />
          <p class="mt-4">Loading organization...</p>
        </VCardText>

        <!-- Organization not found on subdomain -->
        <VCardText v-else-if="isSubdomain && !organization" class="text-center">
          <VIcon icon="tabler-alert-circle" size="64" color="error" class="mb-4" />
          <h4 class="text-h4 mb-2">Organization Not Found</h4>
          <p class="mb-4">The organization "{{ organizationSlug }}" doesn't exist or is no longer active.</p>
          <VBtn :href="`https://movana.id/register`" color="primary">
            Create Your Own Organization
          </VBtn>
        </VCardText>

        <!-- Registration Form -->
        <VCardText v-else-if="!registerSuccess">
          <!-- Dynamic header based on context -->
          <div class="mb-4">
            <!-- Subdomain: Show org badge -->
            <VChip v-if="isSubdomain && organization" color="primary" size="small" class="mb-2">
              {{ organization.name }}
            </VChip>
            
            <h4 class="text-h4 mb-1">{{ pageTitle }} 🚀</h4>
            <p class="mb-0">{{ pageSubtitle }}</p>
          </div>

          <VForm ref="registerFormRef" @submit.prevent="handleSubmitRegister">
            <VRow>
              <!-- Organization Name (only for main domain / owner registration) -->
              <VCol v-if="!isSubdomain" cols="12">
                <AppTextField
                  v-model="form.organization_name"
                  :rules="[requiredValidator]"
                  label="Organization Name"
                  placeholder="e.g. Acme Operations"
                  :error-messages="errors.organization_name"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Organization Type (only for main domain / owner registration) -->
              <VCol v-if="!isSubdomain" cols="12">
                <AppSelect
                  v-model="form.organization_type"
                  label="Business Type"
                  :items="organizationTypes"
                  item-title="title"
                  item-value="value"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Divider for owner registration -->
              <VCol v-if="!isSubdomain" cols="12">
                <VDivider class="my-2" />
                <p class="text-body-2 text-medium-emphasis mb-0">Your Account Details</p>
              </VCol>

              <!-- Name -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.name"
                  :rules="[requiredValidator]"
                  label="Full Name"
                  type="text"
                  placeholder="John Doe"
                  :error-messages="errors.name"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Email -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email"
                  type="email"
                  placeholder="johndoe@email.com"
                  :error-messages="errors.email"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  :rules="[requiredValidator]"
                  label="Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="new-password"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                  :disabled="btnLoading"
                />
              </VCol>

              <!-- Privacy Policy -->
              <VCol cols="12">
                <div class="d-flex align-center">
                  <VCheckbox id="privacy-policy" v-model="form.privacyPolicies" inline :disabled="btnLoading" />
                  <VLabel for="privacy-policy" style="opacity: 1;">
                    <span class="me-1 text-high-emphasis">I agree to</span>
                    <NuxtLink to="/privacy-policy" class="text-primary" target="_blank">privacy policy & terms</NuxtLink>
                  </VLabel>
                </div>
                <div v-if="errors.privacyPolicies" class="text-error text-sm">{{ errors.privacyPolicies }}</div>
              </VCol>

              <!-- Submit Button -->
              <VCol cols="12">
                <VBtn 
                  block 
                  type="submit" 
                  :loading="btnLoading" 
                  :disabled="btnLoading"
                  :color="isSubdomain ? 'primary' : 'success'"
                >
                  {{ isSubdomain ? 'Join Now' : 'Create Organization' }}
                </VBtn>
              </VCol>
            </VRow>
          </VForm>

          <!-- Auth providers -->
          <VRow>
            <VCol cols="12" class="text-center">
              <AuthProvider type="register" />
            </VCol>
            <VCol cols="12" class="d-flex align-center">
              <VDivider />
              <span class="mx-4">or</span>
              <VDivider />
            </VCol>
            <VCol cols="12" class="text-center">
              <span>Already have an account?</span>
              <NuxtLink class="text-primary ms-1" :to="{ name: 'login' }">
                Login
              </NuxtLink>
            </VCol>
          </VRow>
        </VCardText>

        <!-- Success State -->
        <VCardText v-else>
          <VIcon icon="tabler-circle-check" size="64" color="success" class="mb-4" />
          <h4 class="text-h4 mb-1">
            {{ isSubdomain ? 'Welcome!' : 'Organization Created!' }} 🎉
          </h4>
          <p class="mb-4">
            {{ isSubdomain 
              ? `You're now a member of ${organization?.name}. Please login to continue.`
              : 'Your organization has been created. Please login to get started.'
            }}
          </p>

          <VBtn block :to="{ name: 'login' }" color="primary">Go to Login</VBtn>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";
</style>
