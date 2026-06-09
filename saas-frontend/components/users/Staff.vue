<script setup lang="ts">
// @ts-nocheck
import { useOptionStore } from '@/stores/optionStore'
import type { PaginationInfo } from '@/types/pagination'
import { useRoute, useRouter } from 'vue-router'

const optionStore = useOptionStore()

const route = useRoute()
const router = useRouter()

// ============================ 1 ============================
// Initialize from query params
const selectedStatus = ref(route.query.status as string || undefined)
const searchQuery = ref(route.query.q as string || '')
const searchInput = ref(searchQuery.value)
const selectedRows = ref<number[]>([])

// Initialize pagination and sorting from query params
const itemsPerPage = ref(Number(route.query.itemsPerPage) || 15) // Changed default to 15 to match Laravel
const page = ref(Number(route.query.page) || 1)
const sortBy = ref(route.query.sortBy as string || undefined)
const orderBy = ref(route.query.orderBy as string || undefined)


// Pagination information from Laravel
const paginationInfo = ref<PaginationInfo>({
  currentPage: page.value,
  lastPage: 1,
  total: 0,
  perPage: itemsPerPage.value
})

// ============================ 2 ============================
// Update query parameters when filters change
const updateQueryParams = () => {
  console.log('updateQueryParams');

  const query: Record<string, string | undefined> = {
    ...route.query,
    q: searchQuery.value || undefined,
    status: selectedStatus.value || undefined,
    page: page.value !== 1 ? String(page.value) : undefined,
    itemsPerPage: itemsPerPage.value !== 15 ? String(itemsPerPage.value) : undefined,
    sortBy: sortBy.value || undefined,
    orderBy: orderBy.value || undefined,
  };

  // Remove undefined values safely
  Object.keys(query).forEach((key) => {
    if (query[key] === undefined) {
      delete query[key as keyof typeof query]; // Type assertion for safety
    }
  });

  // Update URL without reloading the page
  router.replace({ query });
};

// Watch for changes and update query params
watch(
  [searchQuery, selectedStatus, itemsPerPage, page, sortBy, orderBy],
  () => {
    updateQueryParams()
  }
)


// ============================ 3 ============================
// Refetch data when query params change
watch(
  () => route.query,
  () => {
    // Only update if the route change came from outside (like browser back/forward)
    if (
      route.query.q !== searchQuery.value ||
      route.query.status !== selectedStatus.value ||

      Number(route.query.page || 1) !== page.value ||
      Number(route.query.itemsPerPage || 15) !== itemsPerPage.value ||
      route.query.sortBy !== sortBy.value ||
      route.query.orderBy !== orderBy.value
    ) {
      // Update local state from query params
      searchQuery.value = route.query.q as string || '';
      selectedStatus.value = route.query.status as string || undefined;

      page.value = Number(route.query.page) || 1;
      itemsPerPage.value = Number(route.query.itemsPerPage) || 15;
      sortBy.value = route.query.sortBy as string || undefined;
      orderBy.value = route.query.orderBy as string || undefined;
    }

    // Refetch with new params
    fetchData();
  },
  { deep: true }
);










// FILTER
// Debounce search input changes
let debounceTimeout: ReturnType<typeof setTimeout> | null = null
watch(searchInput, (newVal) => {
  if (debounceTimeout) clearTimeout(debounceTimeout)
  
  debounceTimeout = setTimeout(() => {
    searchQuery.value = newVal
    // Reset to first page when search changes
    if (page.value !== 1) page.value = 1
  }, 500) // 500ms debounce time
})

onUnmounted(() => {
  if (debounceTimeout) clearTimeout(debounceTimeout)
})

const { status } = storeToRefs(optionStore)
// Fetch filter options from server
const fetchFilterOptions = async () => {
  try {
    // optionStore.fetchOptions('categories', '/options/class-categories')

    // Validate selected values against available options
    validateSelectedValues()
  } catch (error) {
    console.error('Failed to fetch filter options:', error)
  }
}

// Validate that selected values exist in the fetched options
const validateSelectedValues = () => {
  // Validate status
  if (selectedStatus.value && !status.value.some(item => item.value === selectedStatus.value)) {
    selectedStatus.value = ''
  }
}

// Update data table options
const updateOptions = (options: any) => {
  page.value = options.page
  itemsPerPage.value = options.itemsPerPage
  
  if (options.sortBy && options.sortBy.length > 0) {
    sortBy.value = options.sortBy[0]?.key
    orderBy.value = options.sortBy[0]?.order
  } else {
    sortBy.value = undefined
    orderBy.value = undefined
  }
}
const staffsResponse = ref<LaravelPaginationResponse<Staff> | null>(null);
const { fetchStaffs, deleteStaff, loadingStaffs, reactivateStaff } = useStaff();
const records = ref<Staff[]>([]);

// Create a wrapper function that updates the URL and calls the API
const fetchData = async () => {
  const params = {
    q: searchQuery.value,

    status: selectedStatus.value,
    page: page.value,
    per_page: itemsPerPage.value,
    sort_by: sortBy.value,
    order_by: orderBy.value,
  };

  const { data, response, error } = await fetchStaffs({ query: params });

  records.value = data.data

  staffsResponse.value = data;

  return { data, response, error };
};

// Update pagination info when data changes
watch(staffsResponse, (newData: LaravelPaginationResponse<Staff>) => {
  if (newData) {
    paginationInfo.value = {
      currentPage: newData.current_page,
      lastPage: newData.last_page,
      total: newData.total,
      perPage: newData.per_page
    };

    // Update local page and itemsPerPage from response
    page.value = newData.current_page;
    itemsPerPage.value = newData.per_page;
  }
}, { deep: true });


// TABLE PROPERTIES
const headers: TableHeader[] = [
  { title: "Name", key: "name", sortable: true, align: "start" },
  { title: "E-Mail", key: "email", sortable: true, align: "start" },
  { title: "Phone", key: "phone", sortable: true, align: "start" },
  { title: "Gender", key: "gender", sortable: true, align: "center" },
  { title: "Status", key: "status", sortable: false, align: "end", width: 100 },
  { title: "Actions", key: "actions", sortable: false, align: "end", width: 100 },
];

// VIEW RESOLVERS
const resolveStatus = (statusMsg: string) => {
  if (statusMsg === 'active')
    return { text: 'Active', color: 'success' }
  if (statusMsg === 'inactive')
    return { text: 'Inactive', color: 'error' }
}

const resolveGender = (gender: string) => {
  if (gender === 'M')
    return { color: 'primary', icon: 'tabler-mars' }
  if (gender === 'F')
    return { color: 'error', icon: 'tabler-venus' }
}




const deleteRecord = async (id: string) => {
  try {
    await deleteStaff(id)

    // Refetch classes
    fetchData(lastParams.value)
  } catch (error) {
    console.error('Failed to delete class:', error)
  }
}

const reactivateResource = async (id: string) => {
  try {
    await reactivateStaff(id)

    // Refetch classes
    fetchData(lastParams.value)
  } catch (error) {
    console.error('Failed to delete class:', error)
  }
}

// Initialize component
onMounted(async () => {
  await fetchData() // Make sure to fetch classes after filter options
})

// DIALOGS
const isCardAddDialogVisible = ref(false)
const isCardEditDialogVisible = ref(false)
const editedResource = ref<Staff | null>(null)
const showEdit= (resource: Staff) => {
  editedResource.value = resource
  isCardEditDialogVisible.value = true
}
</script>
<template>
  <div>
    <!-- 👉 staff records -->
    <VCard>

    <div class="d-flex flex-wrap gap-4 my-4 mx-6">
      <div class="font-weight-medium text-lg align-self-center">Staff</div>

      <VSpacer />
        <div class="d-flex gap-4 flex-wrap align-center">
          <!-- 👉 Export button -->
          <!-- <VBtn
            variant="tonal"
            color="secondary"
            prepend-icon="tabler-upload"
          >
            Export
          </VBtn> -->

          <VBtn
            color="primary"
            prepend-icon="tabler-plus"
            @click="() => {isCardAddDialogVisible = true}"
          >
            Add Staff
          </VBtn>

          <AddEditStaffDialog v-model:is-dialog-visible="isCardAddDialogVisible" @submit="fetchData(lastParams.value)"/>

          <AddEditStaffDialog v-model:is-dialog-visible="isCardEditDialogVisible" :resource="editedResource" @submit="fetchData(lastParams.value)" />
        </div>
      </div>

      <VDivider />
    

      <div class="d-flex flex-wrap gap-4 ma-6">
        <AppSelect
          v-model="filterState.status"
          placeholder="Status"
          :items="status"
          clearable
          clear-icon="tabler-x"
          style="min-width: 150px;"
        />
      </div>

      <VDivider class="mt-4" />

      <AppDatatable
        :headers="headers"
        :items="records"
        :items-length="paginationInfo.total"
        :loading="loadingStaffs"
        :filters="filterState"
        @load="fetchData"
      >
        <!-- gender -->
        <template #item.gender="{ item }">
          <VAvatar
            size="30"
            variant="tonal"
            :color="resolveGender(item.gender)?.color"
            class="me-4"
          >
            <VIcon
              :icon="resolveGender(item.gender)?.icon"
              size="18"
            />
          </VAvatar>
        </template>

        <!-- status -->
        <template #item.status="{ item }">
          <VChip
            v-bind="resolveStatus(item.status)"
            density="default"
            label
            size="small"
          />
        </template>

        <!-- Actions -->
        <template #item.actions="{ item }">
          <IconBtn @click="showEdit(item)">
            <VIcon icon="tabler-edit" />
          </IconBtn>

          <IconBtn>
            <VIcon icon="tabler-dots-vertical" />
            <VMenu activator="parent">
              <VList>
                <VListItem
                  value="delete"
                  prepend-icon="tabler-trash"
                  @click="deleteRecord(item.id)"
                >
                  Delete
                </VListItem>
                
                <VListItem v-if="item.status === 'inactive'"
                  value="reactivate"
                  prepend-icon="tabler-check"
                  @click="reactivateResource(item.id)"
                  base-color="success"
                >
                  Activate
                </VListItem>
              </VList>
            </VMenu>
          </IconBtn>
        </template>
      </AppDatatable>
    </VCard>
  </div>
</template>
