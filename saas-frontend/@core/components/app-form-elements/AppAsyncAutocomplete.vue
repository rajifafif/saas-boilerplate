<script setup lang="ts">
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: [String, Number, null],  // Selected value
  searchQuery: String,                 // User input
  fetchItems: { type: Function, required: true },  // Function to fetch options
  label: String,
  placeholder: String,
  itemTitle: String,
  itemValue: String,
  clearable: Boolean,
  minChars: { type: Number, default: 2 },
  debounceTime: { type: Number, default: 500 },
});

const emit = defineEmits(['update:modelValue', 'update:searchQuery']);

const search = ref('');
const items = ref<any[]>([]);
const loading = ref(false);
const debounceTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

// Watch search query and fetch data with debounce
watch(() => props.searchQuery, (newQuery = '') => {
  if (debounceTimeout.value) clearTimeout(debounceTimeout.value);

  debounceTimeout.value = setTimeout(async () => {
    if (newQuery.length >= props.minChars) {
      loading.value = true;
      try {
        const response = await props.fetchItems(newQuery);
        items.value = Array.isArray(response) ? response : [];
      } catch (error) {
        console.error('Error fetching items:', error);
        items.value = [];
      } finally {
        loading.value = false;
      }
    }
  }, props.debounceTime);
});

</script>

<template>
  <AppAutocomplete
    :model-value="modelValue"
    v-model:search="search"
    :items="items"
    :loading="loading"
    :label="label"
    :placeholder="placeholder"
    :item-title="itemTitle"
    :item-value="itemValue"
    :clearable="clearable"
    @update:modelValue="emit('update:modelValue', $event)"
    @update:search="emit('update:searchQuery', $event)"
  />
</template>
