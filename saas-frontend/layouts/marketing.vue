<script setup lang="ts">
const navItems = [
  { title: 'Features', to: '#features' }, // Anchor link
  { title: 'Pricing', to: '#pricing' },   // Anchor link
  { title: 'About', to: '#about' },
]
</script>

<template>
  <div class="marketing-layout">
    <VAppBar flat color="white" class="border-b" density="comfortable">
      <VContainer class="d-flex align-center py-0">
        <NuxtLink to="/" class="d-flex align-center text-decoration-none">
          <span class="text-h5 font-weight-bold text-primary">Movana</span>
        </NuxtLink>

        <VSpacer />

        <div class="d-none d-md-flex gap-4 align-center">
          <VBtn
            v-for="item in navItems"
            :key="item.title"
            variant="text"
            :to="item.to"
          >
            {{ item.title }}
          </VBtn>
        </div>

        <VSpacer />

        <div class="d-flex gap-2">
          <VBtn variant="text" to="/login">Login</VBtn>
          <VBtn color="primary" variant="flat" to="/register">Get Started</VBtn>
        </div>
      </VContainer>
    </VAppBar>

    <VMain>
      <slot />
    </VMain>

    <VFooter class="bg-grey-100 py-8">
      <VContainer>
        <div class="text-center text-medium-emphasis">
          &copy; {{ new Date().getFullYear() }} Movana Inc. All rights reserved.
        </div>
      </VContainer>
    </VFooter>
  </div>
</template>

<style scoped>
.marketing-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
</style>
