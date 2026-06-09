<template>
  <v-snackbar
    v-model="show"
    :color="color"
    :timeout="3000"
    location="top right"
  >
    {{ message }}

    <template #actions>
      <v-btn @click="show = false" color="white" variant="text">Close</v-btn>
    </template>
  </v-snackbar>
</template>

<script setup>
import { useSnackbar } from '@/composables/useSnackbar';

const { show, message, color } = useSnackbar();
</script>
