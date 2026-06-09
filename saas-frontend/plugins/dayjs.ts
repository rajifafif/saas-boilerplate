// plugins/dayjs.ts
import dayjs from 'dayjs'
import 'dayjs/locale/id'

export default defineNuxtPlugin((nuxtApp) => {
  dayjs.locale('id')

  // Optional: add format helper
  const formatDate = (date: string | Date, format = 'dddd, DD MMMM YYYY') => {
    return dayjs(date).format(format)
  }

  // Inject to Nuxt app context
  nuxtApp.provide('dayjs', dayjs)
  nuxtApp.provide('formatDate', formatDate)
})

// Type augmentation for Vue
declare module '#app' {
  interface NuxtApp {
    $dayjs: typeof dayjs
    $formatDate: (date: string | Date, format?: string) => string
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $dayjs: typeof dayjs
    $formatDate: (date: string | Date, format?: string) => string
  }
}
