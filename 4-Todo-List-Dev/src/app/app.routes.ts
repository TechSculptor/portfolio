import { Routes } from '@angular/router';
import { TodoListComponent } from './todo-list/todo-list';

export const routes: Routes = [
  { path: '', redirectTo: 'todos', pathMatch: 'full' },
  { path: 'todos', component: TodoListComponent },
];
