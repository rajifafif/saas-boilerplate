export const useAuthDebug = () => {
  const debugAuth = () => {
    if (process.client) {
      console.group('🔐 Auth Debug Info')
      
      // Check auth status
      const { status, data, token } = useAuth()
      console.log('Auth Status:', status.value)
      console.log('User Data:', data.value)
      console.log('Token:', token.value)
      
      // Check cookies
      const tokenCookie = useCookie('auth.token')
      const rememberCookie = useCookie('auth.remember')
      
      console.log('Token Cookie:', tokenCookie.value)
      console.log('Remember Cookie:', rememberCookie.value)
      
      // Check all auth-related cookies
      const allCookies = document.cookie.split(';')
      const authCookies = allCookies.filter(cookie => 
        cookie.trim().startsWith('auth.') || 
        cookie.trim().includes('auth')
      )
      
      console.log('All Auth Cookies:', authCookies)
      
      // Check cookie expiration
      if (tokenCookie.value) {
        console.log('Token exists - checking browser storage...')
        
        // Try to get cookie details from browser
        const cookieString = document.cookie
        console.log('Full cookie string:', cookieString)
      }
      
      console.groupEnd()
    }
  }

  return {
    debugAuth
  }
}
