import { Application } from '@hotwired/stimulus';
import '@hotwired/turbo';
import { NavigationController } from './controllers/navigation_controller.js';
import { PushSubscriptionController } from './controllers/push_subscription_controller.js';

const application = Application.start();
application.register('navigation', NavigationController);
application.register('push-subscription', PushSubscriptionController);
application.debug = import.meta.env.DEV;
