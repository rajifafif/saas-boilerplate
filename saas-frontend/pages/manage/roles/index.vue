// @ts-nocheck
<script setup lang="ts">
import AppDatatable from '@/components/common/AppDatatable.vue';
import { useRoles } from '@/composables/useRoles';
import { ref } from 'vue';

const { roles, loadingRoles, fetchRoles, deleteRole } = useRoles();
const totalItems = ref(0);
const lastParams = ref({});

const headers = [
  { title: "Role Name", key: "name", sortable: true, align: "start" },
  { title: "Guard", key: "guard_name", sortable: false, align: "start" },
  { title: "Actions", key: "actions", sortable: false, align: "end", width: 100 },
];

const fetchData = async (params: any) => {
  lastParams.value = params;
  const { data } = await fetchRoles({ ...params });
  if (data) {
     roles.value = data.data;
     totalItems.value = data.total;
  }
};

const confirmDelete = ref({
  show: false,
  id: null as null | string,
});

function openDeleteConfirm(id: string) {
  confirmDelete.value.show = true;
  confirmDelete.value.id = id;
}

async function confirmDeleteAndDelete() {
  if (confirmDelete.value.id) {
    await deleteRole(confirmDelete.value.id);
    fetchData(lastParams.value);
  }
  confirmDelete.value.show = false;
}
</script>

<template>
  <div>
    <VCard>
      <div class="d-flex flex-wrap gap-4 my-4 mx-6">
        <div class="font-weight-medium text-lg align-self-center">Roles</div>
        <VSpacer />
        <VBtn
          color="primary"
          prepend-icon="tabler-plus"
          to="/manage/roles/create"
        >
          Add Role
        </VBtn>
      </div>

      <VDivider />

      <AppDatatable
        :headers="headers"
        :items="roles"
        :items-length="totalItems"
        :loading="loadingRoles"
        @load="fetchData"
      >
        <template #item.actions="{ item }">
          <IconBtn :to="`/manage/roles/${item.id}/edit`">
            <VIcon icon="tabler-edit" />
          </IconBtn>

          <IconBtn @click="openDeleteConfirm(item.id)">
            <VIcon icon="tabler-trash" />
          </IconBtn>
        </template>
      </AppDatatable>
    </VCard>

    <!-- Confirmation Dialog -->
    <VDialog v-model="confirmDelete.show" max-width="400">
      <VCard>
        <VCardTitle class="text-h6">Confirm Delete</VCardTitle>
        <VCardText>Are you sure you want to permanently delete this role?</VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn text @click="confirmDelete.show = false">Cancel</VBtn>
          <VBtn color="error" @click="confirmDeleteAndDelete">Delete</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
