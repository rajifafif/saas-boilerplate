import type { Actions, Subjects } from '@/types/permission'

/**
 * Neutral SaaS boilerplate navigation.
 * Keep this shell domain-free: auth/profile/org/users/roles/settings only.
 */
export default [
  {
    heading: 'ORGANIZATION',
    subject: 'Organization' as Subjects,
  },
  {
    title: 'Dashboard',
    icon: { icon: 'tabler-layout-dashboard' },
    to: 'manage',
  },
  {
    title: 'Team',
    icon: { icon: 'tabler-user-check' },
    to: { name: 'manage-users-tab', params: { tab: 'staff' } },
    subject: 'User' as Subjects,
    action: 'read' as Actions,
  },
  {
    title: 'Roles',
    icon: { icon: 'tabler-shield-check' },
    to: 'manage-roles',
    subject: 'Role' as Subjects,
    action: 'read' as Actions,
  },
  {
    title: 'Account Settings',
    icon: { icon: 'tabler-settings' },
    to: { name: 'manage-account-settings-tab', params: { tab: 'account' } },
    subject: 'Settings' as Subjects,
    action: 'read' as Actions,
  },
]
