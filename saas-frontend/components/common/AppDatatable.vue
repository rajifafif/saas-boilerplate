<script setup lang="ts">
import type { TableHeader } from '@/types/pagination';
import { ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const props = defineProps<{
  headers: TableHeader[];
  items: any[];
  itemsLength: number;
  loading: boolean;
  filters?: Record<string, any>; // Reactive object from parent
  showSelect?: boolean;
  syncUrl?: boolean;
}>();

const emit = defineEmits<{
  (e: 'load', params: any): void;
  (e: 'click:add'): void;
  (e: 'update:modelValue', value: any[]): void;
}>();

const route = useRoute();
const router = useRouter();

// Internal State
const searchInput = ref('');
const searchQuery = ref('');
const page = ref(1);
const itemsPerPage = ref(15);
const sortBy = ref<string | undefined>(undefined);
const orderBy = ref<string | undefined>(undefined);
const selectedRows = ref([]);

// Debounce for search
let debounceTimeout: ReturnType<typeof setTimeout> | null = null;
watch(searchInput, (newVal) => {
  if (debounceTimeout) clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    searchQuery.value = newVal;
    if (page.value !== 1) page.value = 1;
  }, 500);
});

// Update URL from State
const updateUrl = () => {
  if (props.syncUrl === false) return;

  const query: Record<string, any> = {
    ...route.query,
    q: searchQuery.value || undefined,
    page: page.value !== 1 ? String(page.value) : undefined,
    itemsPerPage: itemsPerPage.value !== 15 ? String(itemsPerPage.value) : undefined,
    sortBy: sortBy.value || undefined,
    orderBy: orderBy.value || undefined,
    ...props.filters, // Spread custom filters
  };

  // Clean undefined
  Object.keys(query).forEach((key) => {
    if (query[key] === undefined || query[key] === null || query[key] === '') {
      delete query[key];
    }
  });

  router.replace({ query });
};

// Sync State -> URL
watch(
  [searchQuery, page, itemsPerPage, sortBy, orderBy, () => props.filters],
  () => {
    if (props.syncUrl === false) {
      emitLoad();
    } else {
      updateUrl();
    }
  },
  { deep: true }
);

const syncFromUrl = (newQuery: any) => {
    if (props.syncUrl === false) return;

    // 1. Update internal simple state
    const newQ = (newQuery.q as string) || '';
    if (searchQuery.value !== newQ) {
      searchQuery.value = newQ;
      searchInput.value = newQ;
    }

    const newPage = Number(newQuery.page) || 1;
    if (page.value !== newPage) page.value = newPage;

    const newPerPage = Number(newQuery.itemsPerPage) || 15;
    if (itemsPerPage.value !== newPerPage) itemsPerPage.value = newPerPage;

    const newSortBy = (newQuery.sortBy as string) || undefined;
    if (sortBy.value !== newSortBy) sortBy.value = newSortBy;

    const newOrderBy = (newQuery.orderBy as string) || undefined;
    if (orderBy.value !== newOrderBy) orderBy.value = newOrderBy;

    // 2. Update parent filters object
    if (props.filters) {
      for (const key in props.filters) {
        const val = newQuery[key];
        // Only update if changed to avoid unnecessary re-reactivity if possible,
        // though strictly assigning same value is fine in Vue 3.
        // We cast to the type of the prop filter if possible or just string.
        // Usually query params are strings.
        if (props.filters[key] !== val) {
             props.filters[key] = val || ''; 
        }
      }
    }
}

// Sync URL -> State & Trigger Load
watch(
  () => route.query,
  (newQuery) => {
    syncFromUrl(newQuery);
    // 3. Emit load event
    emitLoad();
  },
  { deep: true }
);

onMounted(() => {
  if (props.syncUrl !== false) {
    syncFromUrl(route.query);
  }
  emitLoad();
});

function emitLoad() {
  const params = {
    q: searchQuery.value,
    page: page.value,
    per_page: itemsPerPage.value,
    sort_by: sortBy.value,
    order_by: orderBy.value,
    ...props.filters,
  };
  emit('load', params);
}

// Handle VDataTableServer options update
const updateOptions = (options: any) => {
  page.value = options.page;
  itemsPerPage.value = options.itemsPerPage;
  if (options.sortBy && options.sortBy.length > 0) {
    sortBy.value = options.sortBy[0].key;
    orderBy.value = options.sortBy[0].order;
  } else {
    sortBy.value = undefined;
    orderBy.value = undefined;
  }
};
</script>

<template>
  <div>
    <!-- Filters Bar -->
    <div class="d-flex flex-wrap gap-4 ma-6">
      <!-- Search -->
      <AppTextField
        v-model="searchInput"
        placeholder="Search"
        clearable
        style="min-width: 200px;"
      />

      <!-- Custom Filters Slot -->
      <slot name="filters"></slot>
    </div>

    <VDivider class="mt-4" />

    <!-- DataTable -->
    <VDataTableServer
      v-bind="$attrs"
      v-model:items-per-page="itemsPerPage"
      v-model:model-value="selectedRows"
      v-model:page="page"
      :headers="headers"
      :show-select="showSelect"
      :items="items"
      :items-length="itemsLength"
      :loading="loading"
      class="text-no-wrap"
      @update:options="updateOptions"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <!-- Pass through all slots from parent -->
      <template v-for="(_, name) in $slots" #[name]="slotProps">
        <slot :name="name" v-bind="slotProps || {}"></slot>
      </template>
      
      <!-- Pagination -->
      <template #bottom>
        <TablePagination
          v-model:page="page"
          :items-per-page="itemsPerPage"
          :total-items="itemsLength"
        />
      </template>
    </VDataTableServer>
  </div>
</template>
