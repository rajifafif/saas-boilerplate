<script setup lang="ts">
defineProps<{
  type: 'login' | 'register'
}>()
</script>

<template>
  <VBtn
    prepend-icon="tabler-brand-google-filled"
    variant="outlined"
    block
    disabled
  >
    {{ type === 'login' ? 'Sign in' : 'Sign up' }} with Google unavailable
  </VBtn>
</template>
