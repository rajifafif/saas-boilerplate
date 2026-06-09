<script setup lang="ts">
import AppDatatable from '@/components/common/AppDatatable.vue'
import { useBranches, type BranchPayload } from '@/composables/useBranches'

const { branches, loadingBranches, fetchBranches, storeBranch, updateBranch, deleteBranch } = useBranches()

const totalItems = ref(0)
const lastParams = ref<Record<string, any>>({})
const saving = ref(false)
const formRef = ref()

const formDialog = ref(false)
const confirmDelete = ref({ show: false, id: null as string | null })

const form = ref<BranchPayload & { id?: string }>({
  name: '',
  code: '',
  phone: '',
  email: '',
  is_active: true,
})

const headers = [
  { title: 'Branch Name', key: 'name', sortable: true, align: 'start' },
  { title: 'Code', key: 'code', sortable: false, align: 'start' },
  { title: 'Email', key: 'email', sortable: false, align: 'start' },
  { title: 'Phone', key: 'phone', sortable: false, align: 'start' },
  { title: 'Status', key: 'is_active', sortable: false, align: 'start' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end', width: 100 },
]

const requiredRule = (value: string) => !!value || 'Branch name is required'

const fetchData = async (params: Record<string, any>) => {
  lastParams.value = params

  const { data } = await fetchBranches({ ...params })

  if (data) {
    branches.value = data.data || []
    totalItems.value = data.total || branches.value.length
  }
}

const resetForm = () => {
  form.value = {
    name: '',
    code: '',
    phone: '',
    email: '',
    is_active: true,
  }
}

const openCreate = () => {
  resetForm()
  formDialog.value = true
}

const openEdit = (branch: any) => {
  form.value = {
    id: branch.id,
    name: branch.name || '',
    code: branch.code || '',
    phone: branch.phone || '',
    email: branch.email || '',
    is_active: Boolean(branch.is_active),
  }
  formDialog.value = true
}

const saveBranch = async () => {
  const result = await formRef.value?.validate?.()

  if (result && result.valid === false)
    return

  saving.value = true

  try {
    const payload: BranchPayload = {
      name: form.value.name,
      code: form.value.code || null,
      phone: form.value.phone || null,
      email: form.value.email || null,
      is_active: form.value.is_active,
    }

    if (form.value.id)
      await updateBranch(form.value.id, payload)
    else
      await storeBranch(payload)

    formDialog.value = false
    await fetchData(lastParams.value)
  }
  finally {
    saving.value = false
  }
}

const openDeleteConfirm = (id: string) => {
  confirmDelete.value = { show: true, id }
}

const confirmDeleteAndDelete = async () => {
  if (confirmDelete.value.id) {
    await deleteBranch(confirmDelete.value.id)
    await fetchData(lastParams.value)
  }

  confirmDelete.value = { show: false, id: null }
}
</script>

<template>
  <div>
    <VCard>
      <div class="d-flex flex-wrap gap-4 my-4 mx-6 align-center">
        <div>
          <div class="font-weight-medium text-lg">
            Branches
          </div>
          <div class="text-body-2 text-medium-emphasis">
            Manage neutral organization locations for SaaS operations.
          </div>
        </div>
        <VSpacer />
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          @click="openCreate"
        >
          Add Branch
        </VBtn>
      </div>

      <VDivider />

      <AppDatatable
        :headers="headers"
        :items="branches"
        :items-length="totalItems"
        :loading="loadingBranches"
        @load="fetchData"
      >
        <template #item.is_active="{ item }">
          <VChip
            :color="item.is_active ? 'success' : 'secondary'"
            size="small"
            variant="tonal"
          >
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </VChip>
        </template>

        <template #item.actions="{ item }">
          <IconBtn @click="openEdit(item)">
            <VIcon icon="tabler-edit" />
          </IconBtn>

          <IconBtn @click="openDeleteConfirm(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </AppDatatable>
    </VCard>

    <VDialog v-model="formDialog" max-width="640">
      <VCard>
        <VCardTitle>{{ form.id ? 'Edit Branch' : 'Add Branch' }}</VCardTitle>
        <VCardText>
          <VForm ref="formRef" @submit.prevent="saveBranch">
            <VRow>
              <VCol cols="12" md="8">
                <AppTextField
                  v-model="form.name"
                  label="Branch name"
                  :rules="[requiredRule]"
                />
              </VCol>
              <VCol cols="12" md="4">
                <AppTextField v-model="form.code" label="Code" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.email" label="Email" type="email" />
              </VCol>
              <VCol cols="12" md="6">
                <AppTextField v-model="form.phone" label="Phone" />
              </VCol>
              <VCol cols="12">
                <VSwitch v-model="form.is_active" label="Active branch" color="primary" />
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn variant="tonal" @click="formDialog = false">
            Cancel
          </VBtn>
          <VBtn color="primary" :loading="saving" @click="saveBranch">
            Save Branch
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="confirmDelete.show" max-width="400">
      <VCard>
        <VCardTitle class="text-h6">
          Confirm Delete
        </VCardTitle>
        <VCardText>Are you sure you want to delete this branch?</VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn text @click="confirmDelete.show = false">
            Cancel
          </VBtn>
          <VBtn color="error" @click="confirmDeleteAndDelete">
            Delete
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
