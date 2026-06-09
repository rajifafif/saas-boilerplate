// composables/useUserData.ts

// Define your user data type based on your API response
export interface UserData {
  id: string
  email: string
  name: string
  code: string  // Member code for QR generation
  gender: string
  phone: string
  role: 'admin' | 'guest' | 'account'
  subscriptions: Array<{ id: number, status: 'ACTIVE' | 'INACTIVE' }>
  address: string
  avatar_url: string
  kecamatan_id: string,
  emergency_name: string,
  emergency_phone: string,
  emergency_relation: string,
  birth_date: null | string | Date,
  roles_names?: string[], // Added for role-based logic
}

export function useUserData() {
  console.log('useUserData')
  // Create a cookie for persistent storage
  const userDataCookie = useCookie<UserData | null>('user-data', { 
    maxAge: 60 * 60 * 24, // 1 day
    sameSite: 'lax'
  })
  
  // Initialize state from cookie if available
  // No JSON.parse needed as useCookie already handles serialization/deserialization
  const initialData = userDataCookie.value || null
  
  // Create reactive state with initial value from cookie
  const userData = useState<UserData | null>('user', () => initialData)
  
  // Auth helper from Nuxt Auth
  const { status, data, signOut } = useAuth()
  
  // Watch for session data changes
  watch(() => data.value, (newData) => {
    if (newData) {
      userData.value = newData as UserData
      // Update cookie when data changes
      // No JSON.stringify needed as useCookie handles serialization
      userDataCookie.value = newData as any
    } else {
      userData.value = null
      userDataCookie.value = null
    }
  }, { immediate: true })
  
  // Computed property for login status
  const isLoggedIn = computed(() => status.value === 'authenticated')
  
  // Logout function that also clears user data
  const loggingOut = async () => {
    console.log('asdadsasd')
    
    // Clear the token backup to ensure clean logout
    if (process.client) {
        localStorage.removeItem('auth.token.backup')
    }

    userData.value = null
    await signOut({ callbackUrl: '/login' })
  }


  const updateUser = async (data: Partial<UserData>) => {
    try {
      const { data: updatedData, error } = await (useApi(createUrl('/profile')) as any).put(data).json()

      if (error.value) {
        return { data: null, error: error.value }
      }

      if (updatedData.value) {
        userData.value = updatedData.value as UserData
        userDataCookie.value = updatedData.value as any
      }

      return { data: updatedData.value, error: null }
    } catch (err: any) {
      console.error('Error updating profile:', err)
      return { data: null, error: err }
    }
  }
  
  return {
    userData,
    isLoggedIn,
    loggingOut,
    updateUser,
  }
}
