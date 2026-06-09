/**
 * Permission-related types for CASL integration
 */

export type Actions = 'create' | 'read' | 'update' | 'delete' | 'manage'

export type Subjects =
  | 'Member'
  | 'Report'
  | 'Settings'
  | 'User'
  | 'Organization'
  | 'Branch'
  | 'all'

export interface PermissionRule {
  action: Actions | Actions[]
  subject: Subjects | Subjects[]
  conditions?: Record<string, any>
  inverted?: boolean
}

/**
 * Navigation item with permission metadata
 */
export interface NavItemWithPermission {
  title?: string
  heading?: string
  icon?: { icon: string }
  to?: string | { name: string; params?: Record<string, any> }
  subject?: Subjects
  action?: Actions
  children?: NavItemWithPermission[]
}
