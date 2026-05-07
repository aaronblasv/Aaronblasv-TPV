import { Injectable } from '@angular/core';
import { User } from '../types/user.model';

@Injectable({
  providedIn: 'root',
})
export class TpvSessionService {
  private readonly storageKey = 'tpv-active-user-v1';

  getUser(): User | null {
    try {
      const raw = sessionStorage.getItem(this.storageKey);
      return raw ? JSON.parse(raw) as User : null;
    } catch {
      return null;
    }
  }

  setUser(user: User) {
    try {
      sessionStorage.setItem(this.storageKey, JSON.stringify(user));
    } catch {
      // no-op
    }
  }

  clear() {
    try {
      sessionStorage.removeItem(this.storageKey);
    } catch {
      // no-op
    }
  }

  hasUser(): boolean {
    return !!this.getUser();
  }
}