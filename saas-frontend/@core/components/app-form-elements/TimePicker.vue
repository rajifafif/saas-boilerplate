
<script setup>
import { ref, watch } from 'vue'
import { VDialog, VTimePicker } from 'vuetify/components'

// Props
const props = defineProps({
  modelValue: String,
  label: {
    type: String,
    default: 'Select Time',
  },
})

// Emits
const emit = defineEmits(['update:modelValue'])

// Internal state
const dialog = ref(false)
const internalTime = ref(props.modelValue)

// Emit value when changed
const emitTime = (val) => {
  emit('update:modelValue', val)
}

// Watch for external updates
watch(() => props.modelValue, (val) => {
  internalTime.value = val
})

// Open dialog manually when icon clicked
const openDialog = () => {
  dialog.value = true
}
</script>

<template>
  <AppTextField
    v-model="internalTime"
    :label="label"
    prepend-icon="tabler-clock"
    readonly
    @click:prepend="openDialog"
  >
    <VDialog
      v-model="dialog"
      activator="parent"
      width="auto"
    >
      <VTimePicker
        v-if="dialog"
        v-model="internalTime"
        :title="label"
        format="24hr"
        :scrollable="true"
        @update:model-value="emitTime"
      />
    </VDialog>
  </AppTextField>
</template>
