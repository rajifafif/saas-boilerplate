export default defineNuxtPlugin(() => {
  const token = useCookie('auth.token')

  // Sync token to localStorage backup
  // We ONLY write when the token is present. 
  // We DO NOT clear the backup here when the token is null, 
  // because that might be caused by an accidental clear (e.g. backend down).
  // The backup must be cleared explicitly by the logout action.
  watch(token, (newVal) => {
    if (newVal) {
      localStorage.setItem('auth.token.backup', newVal)
    }
  }, { immediate: true })
})
