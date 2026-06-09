/**
 * This plugin is DISABLED because @sidebase/nuxt-auth now handles
 * token refresh automatically via the refresh.isEnabled: true config.
 * 
 * The refreshTokenExpiration function no longer exists in useAuthRemember.
 */
export default defineNuxtPlugin(() => {
  // Plugin disabled - sidebase handles refresh automatically
  // See nuxt.config.ts auth.provider.refresh settings
})

