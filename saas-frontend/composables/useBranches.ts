import { ref } from 'vue'
import { useApi } from './useApi'

export type BranchPayload = {
  name: string
  code?: string | null
  phone?: string | null
  email?: string | null
  is_active?: boolean
}

export const useBranches = () => {
  const branches = ref<any[]>([])
  const loadingBranches = ref(false)

  const createUrl = (url: string, params: Record<string, any> = {}) => {
    const query = new URLSearchParams()

    Object.keys(params).forEach(key => {
      if (params[key] !== undefined && params[key] !== null && params[key] !== '')
        query.append(key, params[key])
    })

    const queryString = query.toString()

    return queryString ? `${url}?${queryString}` : url
  }

  const fetchBranches = async (options: Record<string, any> = {}) => {
    loadingBranches.value = true

    try {
      const { data, response, error } = await useApi(createUrl('/branches', options)).json()

      if (data.value)
        branches.value = data.value.data || []

      return { data: data.value, response, error }
    }
    finally {
      loadingBranches.value = false
    }
  }

  const storeBranch = async (branchData: BranchPayload) => {
    return await useApi('/branches').post(branchData).json()
  }

  const updateBranch = async (id: string, branchData: BranchPayload) => {
    return await useApi(`/branches/${id}`).put(branchData).json()
  }

  const deleteBranch = async (id: string) => {
    return await useApi(`/branches/${id}`).delete().json()
  }

  return {
    branches,
    loadingBranches,
    fetchBranches,
    storeBranch,
    updateBranch,
    deleteBranch,
  }
}
