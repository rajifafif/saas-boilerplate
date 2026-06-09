<script setup>
import { ref, watch } from 'vue'
import { VDialog } from 'vuetify/components'

// Props
const props = defineProps({
  modelValue: String,
  label: {
    type: String,
    default: 'Select Date',
  },
  min: String,
  max: String,
})

// Emits
const emit = defineEmits(['update:modelValue'])

// Internal state
const dialog = ref(false)
const internalDate = ref(props.modelValue)
const formatDate = (val) => {
  if (!val) return ''
  const date = new Date(val)
  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

// Emit value when changed
const emitDate = (val) => {
  const formatted = formatDate(val)
  emit('update:modelValue', formatted)
}

// Sync external modelValue
watch(() => props.modelValue, (val) => {
  internalDate.value = formatDate(val)
})

// Open dialog manually
const openDialog = () => {
  dialog.value = true
}
</script>

<template>
  <AppTextField
    v-model="internalDate"
    :label="label"
    prepend-icon="tabler-calendar-event"
    readonly
    @click:prepend="openDialog"
  >
    <VDialog
      v-model="dialog"
      activator="parent"
      width="auto"
    >
      <VDatePicker
        v-if="dialog"
        v-model="internalDate"
        :min="min"
        :max="max"
        show-adjacent-months
        :title="label"
        :header="internalDate"
        @update:model-value="emitDate"
      />
    </VDialog>
  </AppTextField>
</template>
