import type { OrganizationRole } from '@/types/organization'
import type { MongoAbility } from '@casl/ability'
import { AbilityBuilder, createMongoAbility } from '@casl/ability'

export type Actions = 'create' | 'read' | 'update' | 'delete' | 'manage'

export type Subjects =
  | 'Member'
  | 'Report'
  | 'Settings'
  | 'User'
  | 'Organization'
  | 'Branch'
  | 'Dashboard'
  | 'all'

export type AppAbility = MongoAbility<[Actions, Subjects]>

export interface Rule {
  action: Actions
  subject: Subjects
}

export const ability = createMongoAbility<[Actions, Subjects]>()

export function defineAbilitiesFor(role: OrganizationRole | null): AppAbility {
  const { can, cannot, build } = new AbilityBuilder<AppAbility>(createMongoAbility)

  if (!role) {
    cannot('manage', 'all')
    return build()
  }

  switch (role) {
    case 'owner':
    case 'admin':
      can('manage', 'all')
      break

    case 'staff':
      can('manage', ['Member', 'User', 'Branch'])
      can('read', ['Report', 'Dashboard', 'Organization'])
      cannot('manage', ['Organization', 'Settings'])
      break

    case 'member':
      can('read', ['Dashboard'])
      cannot('manage', ['Member', 'Report', 'Settings', 'User', 'Organization', 'Branch'])
      break

    default:
      cannot('manage', 'all')
  }

  return build()
}

export function getInitialRules(): Rule[] {
  return []
}
