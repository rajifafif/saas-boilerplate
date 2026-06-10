<script setup lang="ts">
import { useRoles } from '@/composables/useRoles'
import { type NavigationItem, type NavigationItemPayload, useMenuBuilder } from '@/composables/useMenuBuilder'

const {
  items,
  loading,
  saving,
  error,
  fetchItems,
  createItem,
  updateItem,
  deleteItem,
} = useMenuBuilder()

const { permissions, fetchPermissions } = useRoles()

const selected = ref<NavigationItem | null>(null)
const formRef = ref()
const form = reactive<NavigationItemPayload>({
  parent_id: null,
  type: 'page',
  title: '',
  slug: '',
  route: '',
  icon: '',
  permission_name: null,
  sort_order: 0,
  is_active: true,
  meta: null,
})

const snackbar = reactive({
  show: false,
  color: 'success',
  text: '',
})

const flatPages = computed(() => {
  const result: NavigationItem[] = []
  const walk = (nodes: NavigationItem[]) => {
    nodes.forEach((item) => {
      if (item.type === 'page') {
        result.push(item)
        walk(item.children ?? [])
      }
    })
  }

  walk(items.value)

  return result
})

const permissionOptions = computed(() => permissions.value.map((permission: any) => permission.name ?? permission))

function resetForm(parentId: string | null = null, type: 'page' | 'action' = 'page') {
  selected.value = null
  Object.assign(form, {
    parent_id: parentId,
    type,
    title: '',
    slug: '',
    route: '',
    icon: '',
    permission_name: null,
    sort_order: 0,
    is_active: true,
    meta: null,
  })
}

function editItem(item: NavigationItem) {
  selected.value = item
  Object.assign(form, {
    parent_id: item.parent_id ?? null,
    type: item.type,
    title: item.title,
    slug: item.slug,
    route: item.route ?? '',
    icon: item.icon ?? '',
    permission_name: item.permission_name ?? null,
    sort_order: item.sort_order ?? 0,
    is_active: item.is_active ?? true,
    meta: item.meta ?? null,
  })
}

async function saveItem() {
  const payload: NavigationItemPayload = {
    parent_id: form.parent_id || null,
    type: form.type,
    title: form.title,
    slug: form.slug,
    route: form.type === 'page' ? form.route || null : null,
    icon: form.type === 'page' ? form.icon || null : null,
    permission_name: form.permission_name || null,
    sort_order: Number(form.sort_order ?? 0),
    is_active: Boolean(form.is_active),
    meta: form.meta ?? null,
  }

  const result = selected.value
    ? await updateItem(selected.value.id, payload)
    : await createItem(payload)

  if (result.error.value) {
    snackbar.color = 'error'
    snackbar.text = result.error.value.message || 'Failed to save menu item'
    snackbar.show = true
    return
  }

  snackbar.color = 'success'
  snackbar.text = selected.value ? 'Menu item updated' : 'Menu item created'
  snackbar.show = true
  resetForm()
  await fetchItems()
}

async function removeItem(item: NavigationItem) {
  const result = await deleteItem(item.id, true)

  if (result.error.value) {
    snackbar.color = 'error'
    snackbar.text = result.error.value.message || 'Failed to delete menu item'
    snackbar.show = true
    return
  }

  snackbar.color = 'success'
  snackbar.text = 'Menu item deleted'
  snackbar.show = true
  if (selected.value?.id === item.id)
    resetForm()
  await fetchItems()
}

function childItems(item: NavigationItem) {
  return [...(item.children ?? []), ...(item.actions ?? [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
}

onMounted(async () => {
  await Promise.all([
    fetchItems(),
    fetchPermissions(),
  ])
})
</script>

<template>
  <div>
    <div class="d-flex align-center mb-6">
      <div>
        <h4 class="text-h4 mb-1">
          Menu Builder
        </h4>
        <p class="text-body-2 mb-0">
          Build sidebar pages, child pages, and action permissions for buttons/features.
        </p>
      </div>
      <VSpacer />
      <VBtn
        prepend-icon="tabler-plus"
        @click="resetForm()"
      >
        New Page
      </VBtn>
    </div>

    <VAlert
      v-if="error"
      type="error"
      class="mb-4"
    >
      {{ error }}
    </VAlert>

    <VRow>
      <VCol
        cols="12"
        md="7"
      >
        <VCard>
          <VCardTitle>Navigation Tree</VCardTitle>
          <VCardText>
            <VProgressLinear
              v-if="loading"
              indeterminate
              class="mb-4"
            />

            <VList lines="two">
              <template
                v-for="item in items"
                :key="item.id"
              >
                <VListItem
                  :title="item.title"
                  :subtitle="`${item.type} • ${item.permission_name || 'no permission'} • ${item.route || 'no route'}`"
                  @click="editItem(item)"
                >
                  <template #prepend>
                    <VIcon :icon="item.icon || 'tabler-file'" />
                  </template>
                  <template #append>
                    <div class="d-flex gap-1">
                      <IconBtn @click.stop="resetForm(item.id, 'page')">
                        <VIcon icon="tabler-file-plus" />
                      </IconBtn>
                      <IconBtn @click.stop="resetForm(item.id, 'action')">
                        <VIcon icon="tabler-bolt" />
                      </IconBtn>
                      <IconBtn @click.stop="removeItem(item)">
                        <VIcon icon="tabler-trash" />
                      </IconBtn>
                    </div>
                  </template>
                </VListItem>

                <VListItem
                  v-for="child in childItems(item)"
                  :key="child.id"
                  class="ps-10"
                  :title="child.title"
                  :subtitle="`${child.type} • ${child.permission_name || 'no permission'} • ${child.route || 'no route'}`"
                  @click="editItem(child)"
                >
                  <template #prepend>
                    <VIcon :icon="child.type === 'action' ? 'tabler-bolt' : child.icon || 'tabler-file'" />
                  </template>
                  <template #append>
                    <div class="d-flex gap-1">
                      <IconBtn
                        v-if="child.type === 'page'"
                        @click.stop="resetForm(child.id, 'action')"
                      >
                        <VIcon icon="tabler-bolt" />
                      </IconBtn>
                      <IconBtn @click.stop="removeItem(child)">
                        <VIcon icon="tabler-trash" />
                      </IconBtn>
                    </div>
                  </template>
                </VListItem>
              </template>
            </VList>
          </VCardText>
        </VCard>
      </VCol>

      <VCol
        cols="12"
        md="5"
      >
        <VCard>
          <VCardTitle>{{ selected ? 'Edit Menu Item' : 'Create Menu Item' }}</VCardTitle>
          <VCardText>
            <VForm ref="formRef" @submit.prevent="saveItem">
              <VSelect
                v-model="form.type"
                label="Type"
                :items="['page', 'action']"
                :disabled="Boolean(selected)"
                class="mb-4"
              />

              <VSelect
                v-model="form.parent_id"
                label="Parent Page"
                clearable
                :items="flatPages"
                item-title="title"
                item-value="id"
                :disabled="form.type === 'action' && Boolean(selected)"
                class="mb-4"
              />

              <VTextField
                v-model="form.title"
                label="Title"
                class="mb-4"
              />

              <VTextField
                v-model="form.slug"
                label="Slug"
                class="mb-4"
              />

              <VTextField
                v-if="form.type === 'page'"
                v-model="form.route"
                label="Route"
                placeholder="/manage/roles"
                class="mb-4"
              />

              <VTextField
                v-if="form.type === 'page'"
                v-model="form.icon"
                label="Icon"
                placeholder="tabler-menu-2"
                class="mb-4"
              />

              <VAutocomplete
                v-model="form.permission_name"
                label="Permission"
                clearable
                :items="permissionOptions"
                class="mb-4"
              />

              <VTextField
                v-model.number="form.sort_order"
                label="Sort Order"
                type="number"
                class="mb-4"
              />

              <VSwitch
                v-model="form.is_active"
                label="Active"
                class="mb-4"
              />

              <div class="d-flex gap-2">
                <VBtn
                  type="submit"
                  :loading="saving"
                >
                  Save
                </VBtn>
                <VBtn
                  variant="tonal"
                  color="secondary"
                  @click="resetForm()"
                >
                  Reset
                </VBtn>
              </div>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VSnackbar
      v-model="snackbar.show"
      :color="snackbar.color"
    >
      {{ snackbar.text }}
    </VSnackbar>
  </div>
</template>
