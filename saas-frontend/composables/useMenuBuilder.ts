import { useApi } from './useApi'

export interface NavigationItemPayload {
  parent_id?: string | null
  type: 'page' | 'action'
  title: string
  slug: string
  route?: string | null
  icon?: string | null
  permission_name?: string | null
  sort_order?: number
  is_active?: boolean
  meta?: Record<string, unknown> | null
}

export interface NavigationItem extends NavigationItemPayload {
  id: string
  children?: NavigationItem[]
  actions?: NavigationItem[]
}

export function useMenuBuilder() {
  const items = ref<NavigationItem[]>([])
  const loading = ref(false)
  const saving = ref(false)
  const error = ref<string | null>(null)

  const fetchItems = async () => {
    loading.value = true
    error.value = null

    try {
      const { data, error: fetchError } = await useApi('/platform/navigation-items').get().json<{ data: NavigationItem[] }>()

      if (fetchError.value) {
        error.value = fetchError.value.message || 'Failed to fetch menu items'
        return
      }

      items.value = data.value?.data ?? []
    }
    finally {
      loading.value = false
    }
  }

  const createItem = async (payload: NavigationItemPayload) => {
    saving.value = true
    try {
      return await useApi('/platform/navigation-items').post(payload).json<{ data: NavigationItem }>()
    }
    finally {
      saving.value = false
    }
  }

  const updateItem = async (id: string, payload: NavigationItemPayload) => {
    saving.value = true
    try {
      return await useApi(`/platform/navigation-items/${id}`).put(payload).json<{ data: NavigationItem }>()
    }
    finally {
      saving.value = false
    }
  }

  const deleteItem = async (id: string, force = false) => {
    saving.value = true
    try {
      return await useApi(`/platform/navigation-items/${id}${force ? '?force=1' : ''}`).delete().json()
    }
    finally {
      saving.value = false
    }
  }

  const reorderItems = async (payload: { items: Array<{ id: string; parent_id?: string | null; sort_order: number }> }) => {
    saving.value = true
    try {
      return await useApi('/platform/navigation-items/reorder').post(payload).json()
    }
    finally {
      saving.value = false
    }
  }

  return {
    items,
    loading,
    saving,
    error,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
    reorderItems,
  }
}
