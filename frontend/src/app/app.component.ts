import { Component } from '@angular/core';
import { IonApp, IonRouterOutlet } from '@ionic/angular/standalone';
import { AlertContainerComponent } from './components/alert-container/alert-container.component';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  imports: [IonApp, IonRouterOutlet, AlertContainerComponent],
})
export class AppComponent {
  constructor() {}
}
