import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        // Close dropdown when clicking outside
        this.closeOnClickOutside = this.closeOnClickOutside.bind(this);
        document.addEventListener('click', this.closeOnClickOutside);
    }

    disconnect() {
        document.removeEventListener('click', this.closeOnClickOutside);
    }

    toggle(event) {
        event.stopPropagation();
        const menu = this.menuTarget;
        menu.classList.toggle('hidden');
    }

    close() {
        this.menuTarget.classList.add('hidden');
    }

    closeOnClickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }
}
