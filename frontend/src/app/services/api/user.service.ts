import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../../types/user.model';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private apiUrl = `${environment.apiUrl}/users`;

  constructor(private http: HttpClient) {}

  getAll(): Observable<User[]> {
    return this.http.get<User[]>(this.apiUrl);
  }

  getAllTpv(): Observable<User[]> {
    return this.http.get<User[]>(`${environment.apiUrl}/tpv/users`);
  }

  create(data: Partial<User> & { password: string }): Observable<User> {
    return this.http.post<User>(this.apiUrl, data);
  }

  update(uuid: string, data: Partial<User>): Observable<User> {
    return this.http.put<User>(`${this.apiUrl}/${uuid}`, data);
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }
}
