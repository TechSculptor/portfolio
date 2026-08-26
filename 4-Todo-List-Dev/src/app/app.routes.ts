import { Routes } from '@angular/router';
import { TodoListComponent } from './todo-list/todo-list';
import { UsersComponent } from './users/users.component';

export const routes: Routes = [
  { path: '', redirectTo: 'todos', pathMatch: 'full' },
  { path: 'todos', component: TodoListComponent },
  { path: 'users', component: UsersComponent },
];
