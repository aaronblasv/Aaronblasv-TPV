import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User, UserFormData } from '../../types/user.model';

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

  create(data: UserFormData): Observable<User> {
    return this.http.post<User>(this.apiUrl, data);
  }

  update(uuid: string, data: Partial<UserFormData>): Observable<User> {
    return this.http.put<User>(`${this.apiUrl}/${uuid}`, data);
  }

  delete(uuid: string): Observable<void> {
    return this.http.delete<void>(`${this.apiUrl}/${uuid}`);
  }

  updatePhoto(uuid: string, imageSrc: string | null): Observable<User> {
    return this.http.patch<User>(`${environment.apiUrl}/tpv/users/${uuid}/photo`, { image_src: imageSrc });
  }
}
