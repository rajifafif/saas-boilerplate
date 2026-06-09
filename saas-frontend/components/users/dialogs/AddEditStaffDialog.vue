<script setup lang="ts">
// @ts-nocheck
import { useOptionStore } from '@/stores/optionStore'

interface Props {
  resource?: Staff | null
  isDialogVisible: boolean
}
interface Emit {
  (e: 'update:isDialogVisible', value: boolean): void
  (e: 'submit', value: StaffInput): void
}

const props = withDefaults(defineProps<Props>(), {
  resource: () => ({
    id: '',
    owner_id: '',
    name_prefix: '',
    name: '',
    name_suffix: '',
    gender: null,
    birth_date: '',
    birth_place: '',
    default_address_id: '',
    email: '',
    phone: '',
    user_id: '',
    user: '',
  }),
})

const emit = defineEmits<Emit>()

const resource = ref<StaffInput>(structuredClone(toRaw(props.resource)))

// When Change Resource
watch(
  () => props.resource,
  (newResource) => {
    resource.value = structuredClone(toRaw(newResource))
  },
  { deep: true }
)

const resetForm = () => {
  emit('update:isDialogVisible', false)
  resource.value = structuredClone(toRaw(props.resource))
}

const onFormSubmit = async () => {
  if (resource.value.id) {
    // Update
    await updateResource()
  } else {
    await storeResource()
  }
  emit('update:isDialogVisible', false)
  emit('submit', resource.value)
}

const optionStore = useOptionStore()
const { status, genders } = storeToRefs(optionStore)
// Fetch filter options from server
const fetchFilterOptions = async () => {
  try {
  } catch (error) {
    console.error('Failed to fetch filter options:', error)
  }
}

const wilayahQuery = ref('');
const wilayahs = ref([])
const { fetchWilayah } = optionStore
const searchWilayah = async () => {
  fetchWilayah(wilayahQuery.value)
    .then(({data}) =>  {
      console.log('ss', data.value.data)
      wilayahs.value = data.value.data
    })
}
const kecamatan_id = ref(null)


const { storeStaff, updateStaff } = useStaff();
const storeResource = async () => {
  try {
    await storeStaff(resource.value)
  } catch (error) {
    console.error('Failed to delete class:', error)
  }
}

const updateResource = async () => {
  try {
    await updateStaff(resource.value.id, resource.value)
  } catch (error) {
    console.error('Failed to delete class:', error)
  }
}


</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900 "
    :model-value="props.isDialogVisible"
    @update:model-value="val => $emit('update:isDialogVisible', val)"
  >
    <!-- 👉 Dialog close btn -->
    <DialogCloseBtn @click="$emit('update:isDialogVisible', false)" />
    <VCard class="pa-2 pa-sm-10">
      <VCardText>
        <!-- 👉 Title -->
        <h4 class="text-h4 text-center mb-2">
          {{ (props.resource.id) ? 'Edit' : 'Add New' }} Staff
        </h4>

        <!-- 👉 Form -->
        <VForm @submit.prevent="onFormSubmit">
          <VRow>

            <!-- 👉 Name -->
            <VCol cols="12" >
              <AppTextField
                v-model="resource.name"
                label="Name"
                placeholder="Full Name"
              />
            </VCol>

            <!-- 👉 Gender -->
            <VCol cols="12">
              <AppAutocomplete
                v-model="resource.gender"
                label="Select Gender"
                placeholder="Select Gender"
                :items="genders"
                :clearable="true"
              />
            </VCol>

            <!-- 👉 Email -->
            <VCol cols="12">
              <AppTextField
                v-model="resource.email"
                label="E-Mail"
                placeholder="user@gmail.com"
              />
            </VCol>

            <!-- 👉 Password -->
            <VCol cols="12">
              <AppTextField
                label="Password (default)"
                placeholder="password"
                disabled
              />
            </VCol>

            <!-- 👉 Gender -->
            <VCol cols="12">
              <AppAutocomplete
                v-model="kecamatan_id"
                v-model:search="wilayahQuery"
                @update:search="searchWilayah"
                :items="wilayahs"
                no-filter
                label="Select Kecamatan"
                placeholder="Type to search..."
                item-title="full_name"
                item-value="kelurahan_id"
                :clearable="true"
              />
            </VCol>

            <!-- 👉 Submit and Cancel button -->
            <VCol
              cols="12"
              class="text-center"
            >
              <VBtn
                type="submit"
                class="me-3"
              >
                Submit
              </VBtn>

              <VBtn
                variant="tonal"
                color="secondary"
                @click="resetForm"
              >
                Cancel
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
