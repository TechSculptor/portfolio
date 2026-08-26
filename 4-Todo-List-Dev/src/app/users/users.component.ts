import { Component, inject } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { UserService, User } from '../services/user.service';

@Component({
  selector: 'app-users',
  standalone: true,
  template: `
    <h2>Liste des utilisateurs</h2>

    @if (users(); as userList) {
      <ul>
        @for (user of userList; track user.id) {
          <li>
            <strong>{{ user.name }}</strong> ({{ user.email }})
          </li>
        } @empty {
          <li>Aucun utilisateur trouvé.</li>
        }
      </ul>
    } @else {
      <p>Chargement des données en cours...</p>
    }
  `,
})
export class UsersComponent {
  private userService = inject(UserService);

  // Conversion directe de l'appel HTTP en Signal réactif
  users = toSignal(this.userService.getUsers(), { initialValue: null });
}