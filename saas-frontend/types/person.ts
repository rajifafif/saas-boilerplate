import type { User } from "./user"

export interface Person {
  id: string | null
  owner_id: string | null
  name_prefix: string | null
  name: string
  name_suffix: string | null
  gender: string | null
  birth_date: string | null
  birth_place: string | null
  default_address_id: string | null
  email: string
  phone: string | null
  user_id: string| null
  user: User | undefined | null
}
