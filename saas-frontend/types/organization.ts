/**
 * Organization-related types for multi-tenant support
 */

export interface Organization {
  id: string
  name: string
  slug: string
  type: OrganizationType
  role: OrganizationRole
  is_default: boolean
  joined_at?: string
  branches?: Branch[]
}

export type OrganizationType = 'organization' | 'company'

export type OrganizationRole = 'owner' | 'admin' | 'member' | 'staff'

export interface Branch {
  id: string
  name: string
  code?: string
  is_active: boolean
}

/**
 * Auth response from login endpoint
 */
export interface AuthResponse {
  user: AuthUser
  token: string
  organizations: Organization[]
  current_organization: Organization | null
}

export interface AuthUser {
  id: string
  email: string
  name?: string
  person?: {
    id: string
    name: string
    avatar_url?: string
  }
}

/**
 * Organization API response types
 */
export interface OrganizationListResponse {
  data: Organization[]
  current_organization_id: string | null
  current_role: OrganizationRole | null
}

export interface OrganizationSwitchResponse {
  message: string
  data: {
    organization: Organization
    role: OrganizationRole
    branch: Branch | null
  }
}
