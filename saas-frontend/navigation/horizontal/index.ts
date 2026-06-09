import type { HorizontalNavItems } from '@layouts/types'

export default [
  { title: 'Dashboard', icon: { icon: 'tabler-layout-dashboard' }, to: 'manage' },
  { title: 'Organizations', icon: { icon: 'tabler-building' }, to: 'manage-organizations' },
  { title: 'Branches', icon: { icon: 'tabler-building-community' }, to: 'manage-branches' },
  { title: 'Team', icon: { icon: 'tabler-users' }, to: 'manage-users-tab', params: { tab: 'staff' } },
  { title: 'Roles', icon: { icon: 'tabler-shield-lock' }, to: 'manage-roles' },
  { title: 'Account Settings', icon: { icon: 'tabler-settings' }, to: { name: 'manage-account-settings-tab', params: { tab: 'account' } } },
] as HorizontalNavItems
