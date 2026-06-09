import type { Person } from "./person";
import type { User } from "./user";

export interface Staff extends Person, User {
  status: string
  password?: string
}

export interface StaffInput extends Staff {
  password?: string
}
