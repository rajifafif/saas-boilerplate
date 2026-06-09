import type { RouterConfig } from '@nuxt/schema'
import type { RouteRecordRaw } from 'vue-router'

// const emailRouteComponent = () => import('@/pages/apps/email/index.vue')

// 👉 Redirects
const redirects: RouteRecordRaw[] = [
  // ℹ️ We are redirecting to different pages based on role.
  // NOTE: Role is just for UI purposes. ACL is based on abilities.
  // {
  //   path: '/',
  //   name: 'index',
  //   // meta: {
  //   //   middleware: to => {
  //   //     const { data: sessionData } = useAuth()

  //   //     // TODO Fix this hardcoded role
  //   //     const userRole = sessionData.value?.user?.role ?? 'admin'

  //   //     if (userRole === 'admin')
  //   //       return { name: 'manage' }
  //   //     if (userRole === 'client')
  //   //       return { name: 'access-control' }

  //   //     return { name: 'login', query: to.query }
  //   //   },
  //   // },
  //   component: h('div'),
  // },
  // {
  //   path: '/pages/user-profile',
  //   name: 'pages-user-profile',
  //   redirect: () => ({ name: 'manage-pages-user-profile-tab', params: { tab: 'profile' } }),
  // },
  // {
  //   path: '/account-settings',
  //   name: 'manage-account-settings',
  //   redirect: () => ({ name: 'manage-account-settings-tab', params: { tab: 'account' } }),
  // },
]

const routes: RouteRecordRaw[] = [
  // Email filter
  // {
  //   path: '/apps/email/filter/:filter',
  //   name: 'apps-email-filter',
  //   component: emailRouteComponent,
  //   meta: {
  //     navActiveLink: 'apps-email',
  //     layoutWrapperClasses: 'layout-content-height-fixed',
  //   },
  // },

  // // Email label
  // {
  //   path: '/apps/email/label/:label',
  //   name: 'apps-email-label',
  //   component: emailRouteComponent,
  //   meta: {
  //     // contentClass: 'email-application',
  //     navActiveLink: 'apps-email',
  //     layoutWrapperClasses: 'layout-content-height-fixed',
  //   },
  // },

  // {
  //   path: '/dashboards/logistics',
  //   name: 'dashboards-logistics',
  //   component: () => import('@/pages/apps/logistics/dashboard.vue'),
  // },
  // {
  //   path: '/dashboards/academy',
  //   name: 'dashboards-academy',
  //   component: () => import('@/pages/apps/academy/dashboard.vue'),
  // },
  // {
  //   path: '/apps/ecommerce/dashboard',
  //   name: 'apps-ecommerce-dashboard',
  //   component: () => import('@/pages/dashboards/ecommerce.vue'),
  // },
]

// https://router.vuejs.org/api/interfaces/routeroptions.html
export default <RouterConfig>{
  routes: scannedRoutes => [
    ...redirects,
    ...routes,
    ...scannedRoutes,
  ],
  scrollBehaviorType: 'smooth',
  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
}
