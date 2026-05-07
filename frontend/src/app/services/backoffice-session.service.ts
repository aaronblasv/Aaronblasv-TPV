import { Injectable } from '@angular/core';
import { User } from '../types/user.model';

@Injectable({
  providedIn: 'root',
})
export class BackofficeSessionService {
  private readonly actingUserStorageKey = 'backoffice_acting_user';

  setActingUser(user: User): void {
    sessionStorage.setItem(this.actingUserStorageKey, JSON.stringify(user));
  }

  getActingUser(): User | null {
    const rawValue = sessionStorage.getItem(this.actingUserStorageKey);

    if (!rawValue) {
      return null;
    }

    try {
      return JSON.parse(rawValue) as User;
    } catch {
      this.clearActingUser();

      return null;
    }
  }

  getActingUserUuid(): string | null {
    return this.getActingUser()?.uuid ?? null;
  }

  getEffectiveRole(): string | null {
    return this.getActingUser()?.role ?? null;
  }

  clearActingUser(): void {
    sessionStorage.removeItem(this.actingUserStorageKey);
  }
}
