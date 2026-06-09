// @ts-nocheck
<script setup lang="ts">
import { useRoles } from '@/composables/useRoles';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();
const { updateRole, fetchRole, fetchPermissions, loadingPermissions, permissions } = useRoles();

const form = ref({
  name: '',
  permissions: [] as string[]
});

const loading = ref(false);
const errorMsg = ref('');
const roleId = route.params.id as string;
const searchQuery = ref('');

const formatLabel = (permName: string, groupName: string) => {
    // Remove group name from permission
    const lowerGroup = groupName.toLowerCase();
    let label = permName.replace(lowerGroup + '.', '');
    label = label.replace(lowerGroup + '_', ''); 
    // Format camelCase or dots
    return label.split(/[\._]/).map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const permissionGroups = computed(() => {
    if (!permissions.value) return {};
    
    // Grouping
    const groups = permissions.value.reduce((result: any, currentValue: any) => {
        const parts = currentValue.name.split('.');
        let groupKey = parts[0]; 
        groupKey = groupKey.charAt(0).toUpperCase() + groupKey.slice(1);
        (result[groupKey] = result[groupKey] || []).push(currentValue);
        return result;
    }, {});

    // Filter by Search Query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        Object.keys(groups).forEach(key => {
            const groupMatches = key.toLowerCase().includes(query);
            const filteredPerms = groups[key].filter((p: any) => 
                p.name.toLowerCase().includes(query) || 
                formatLabel(p.name, key).toLowerCase().includes(query)
            );
            
            if (groupMatches) {
                 // Keep group
            } else if (filteredPerms.length > 0) {
                 groups[key] = filteredPerms;
            } else {
                delete groups[key];
            }
        });
    }

    // Sort Groups Keys
    const sortedKeys = Object.keys(groups).sort();
    const sortedGroups: any = {};
    sortedKeys.forEach(key => {
        sortedGroups[key] = groups[key];
    });

    return sortedGroups;
});

const submit = async () => {
    loading.value = true;
    errorMsg.value = '';
    
    const { error } = await updateRole(roleId, form.value);
    
    if (error.value) {
        errorMsg.value = error.value.data?.message || 'Failed to update role';
    } else {
        router.push('/manage/roles');
    }
    loading.value = false;
}

const loadData = async () => {
    loading.value = true;
    // Load permissions first
    await fetchPermissions({ perPage: 1000 });
    
    // Load role
    const { data, error } = await fetchRole(roleId);
    if (error.value) {
        errorMsg.value = 'Failed to load role';
    } else {
        const role = data.data;
        form.value.name = role.name;
        // Map permissions to names
        if (role.permissions) {
            form.value.permissions = role.permissions.map((p: any) => p.name);
        }
    }
    loading.value = false;
}

const toggleGroup = (groupName: string, perms: any[]) => {
    const allSelected = perms.every(p => form.value.permissions.includes(p.name));
    if (allSelected) {
        // Deselect all
        form.value.permissions = form.value.permissions.filter(p => !perms.some(perm => perm.name === p));
    } else {
        // Select all
        const currentIds = new Set(form.value.permissions);
        perms.forEach(p => currentIds.add(p.name));
        form.value.permissions = Array.from(currentIds);
    }
}

const isGroupSelected = (perms: any[]) => {
    return perms.every(p => form.value.permissions.includes(p.name));
}

const isGroupIndeterminate = (perms: any[]) => {
    const selectedCount = perms.filter(p => form.value.permissions.includes(p.name)).length;
    return selectedCount > 0 && selectedCount < perms.length;
}

onMounted(() => {
    loadData();
});
</script>

<template>
  <div>
    <VCard class="pa-6">
      <div class="d-flex align-center mb-6">
        <VBtn icon="tabler-arrow-left" variant="text" to="/manage/roles" class="mr-2" />
        <h2 class="text-h5">Edit Role</h2>
      </div>

      <VForm @submit.prevent="submit">
        
        <VAlert v-if="errorMsg" type="error" class="mb-6">{{ errorMsg }}</VAlert>

        <VRow>
            <VCol cols="12" md="6">
                <VTextField
                  v-model="form.name"
                  label="Role Name"
                  placeholder="e.g. Receptionist"
                  outlined
                  required
                />
            </VCol>
        </VRow>

        <div class="d-flex align-center justify-space-between mt-6 mb-4">
            <div class="text-h6">Permissions</div>
            <div style="width: 300px">
                <VTextField
                    v-model="searchQuery"
                    prepend-inner-icon="tabler-search"
                    label="Search Permissions"
                    density="compact"
                    variant="outlined"
                    hide-details
                    clearable
                />
            </div>
        </div>

        <div v-if="loading" class="d-flex justify-center pa-8">
             <VProgressCircular indeterminate color="primary" size="40" />
        </div>
        
        <div v-else>
            <div class="permission-masonry">
                <div v-for="(perms, group) in permissionGroups" :key="group" class="mb-4 break-inside-avoid">
                    <VCard variant="outlined">
                         <VCardTitle class="d-flex align-center py-2 px-4 bg-grey-lighten-4">
                            <span class="text-subtitle-1 font-weight-bold">{{ group }}</span>
                            <VSpacer />
                            <VCheckbox
                                :model-value="isGroupSelected(perms)"
                                :indeterminate="isGroupIndeterminate(perms)"
                                @click.stop="toggleGroup(group, perms)"
                                density="compact"
                                hide-details
                                color="primary"
                            />
                        </VCardTitle>
                        <VDivider />
                        <VCardText class="pa-4">
                            <div class="d-flex flex-column gap-1">
                                <VCheckbox
                                    v-for="perm in perms"
                                    :key="perm.id"
                                    v-model="form.permissions"
                                    :label="formatLabel(perm.name, group)"
                                    :value="perm.name"
                                    hide-details
                                    density="compact"
                                    color="primary"
                                    class="permission-checkbox"
                                />
                            </div>
                        </VCardText>
                    </VCard>
                </div>
            </div>
        </div>

        <VDivider class="my-6" />

        <div class="d-flex justify-end gap-4">
          <VBtn variant="tonal" to="/manage/roles" size="large">Cancel</VBtn>
          <VBtn color="primary" type="submit" :loading="loading" size="large">Save Changes</VBtn>
        </div>
      </VForm>
    </VCard>
  </div>
</template>

<style scoped>
.permission-checkbox :deep(.v-label) {
    font-size: 0.875rem;
    color: #4b5563;
}

.permission-masonry {
    column-count: 1;
    column-gap: 1.5rem;
}

@media (min-width: 960px) {
    .permission-masonry {
        column-count: 2;
    }
}

@media (min-width: 1280px) {
    .permission-masonry {
        column-count: 3;
    }
}

.break-inside-avoid {
    break-inside: avoid;
}
</style>
