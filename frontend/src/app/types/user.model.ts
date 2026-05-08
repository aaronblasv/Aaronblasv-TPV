export type UserRole = 'admin' | 'supervisor' | 'waiter';

export interface User {
  uuid: string;
  name: string;
  email: string;
  role: UserRole | string;
  active: boolean;
  image_src: string | null;
}

export interface UserFormData {
  name: string;
  email: string;
  password: string;
  role: UserRole;
  image_src: string | null;
}

export interface AuthenticatedUser extends User {
  active_restaurant_uuid?: string | null;
  restaurant_name?: string | null;
}
