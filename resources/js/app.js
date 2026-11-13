import { Application } from '@hotwired/stimulus';
import '@hotwired/turbo';
import { NavigationController } from './controllers/navigation_controller.js';

const application = Application.start();
application.register('navigation', NavigationController);
application.debug = import.meta.env.DEV;
