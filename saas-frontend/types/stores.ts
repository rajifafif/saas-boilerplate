import type { User } from "./user"

export interface Store {
  id: string
  project_id: string
  name: string
  address_id: string
  project: Project
}

export interface Project {
  id: string
  owner_id: string
  user: User | undefined | null
}
