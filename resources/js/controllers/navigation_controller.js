import { Controller } from '@hotwired/stimulus';

export class NavigationController extends Controller {
    static targets = ['menu', 'toggle'];

    connect() {
        this.onKeydown = this.onKeydown.bind(this);
        document.addEventListener('keydown', this.onKeydown);
        this.setExpanded(false);
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKeydown);
    }

    toggle() {
        this.setExpanded(this.menuTarget.hidden);
    }

    close() {
        this.setExpanded(false);
    }

    onKeydown(event) {
        if (event.key === 'Escape' && !this.menuTarget.hidden) {
            this.setExpanded(false);
            this.toggleTarget.focus();
        }
    }

    setExpanded(expanded) {
        this.menuTarget.hidden = !expanded;
        this.toggleTarget.setAttribute('aria-expanded', String(expanded));
    }
}
