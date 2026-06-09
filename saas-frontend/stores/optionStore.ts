import { defineStore } from 'pinia'

interface Option {
  title: string
  value: string | boolean
}

export const useOptionStore = defineStore('optionStore', {
  state: () => ({
    status: [
      { title: 'Active', value: 'active' },
      { title: 'Inactive', value: 'inactive' },
    ] as Option[],
    genders: [
      { title: 'Male', value: 'M' },
      { title: 'Female', value: 'F' },
    ] as Option[],
    days: [
      { title: 'Senin', value: '1' },
      { title: 'Selasa', value: '2' },
      { title: 'Rabu', value: '3' },
      { title: 'Kamis', value: '4' },
      { title: 'Jumat', value: '5' },
      { title: 'Sabtu', value: '6' },
      { title: 'Minggu', value: '0' },
    ] as Option[],
    members: [] as Option[],
    kecamatans: [],

    debounceMap: {} as Record<string, ReturnType<typeof setTimeout> | null>
  }),

  actions: {
    async fetchOptions(key: keyof typeof this.$state, endpoint: string) {
      try {
        const { data } = await useApi(endpoint).json()
        this[key] = data.value || []
      } catch (error) {
        console.error(`Failed to fetch ${key} options:`, error)
      }
    },

    debounceFetch<T>(key: string, fetcher: () => Promise<T>, wait = 500): Promise<T> {
      if (this.debounceMap[key]) clearTimeout(this.debounceMap[key]!)

      return new Promise((resolve, reject) => {
        this.debounceMap[key] = setTimeout(async () => {
          try {
            const result = await fetcher()
            resolve(result)
          } catch (err) {
            reject(err)
          }
        }, wait)
      })
    },

    fetchWilayah(q: string) {
      return this.debounceFetch('wilayah', async () => {
        const response = await useApi(createUrl('/options/wilayah', { query: { search: q } })).json()
        return response
      })
    },

    fetchMemberOption(q: string) {
      return this.debounceFetch('members', async () => {
        const response = await useApi(createUrl('/options/members', { query: { search: q } })).json()
        return response
      })
    }
  },
})
