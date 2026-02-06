import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        target: String
    };

    connect() {
        // Close modal on Escape key
        this.closeOnEscape = this.closeOnEscape.bind(this);
        document.addEventListener('keydown', this.closeOnEscape);
    }

    disconnect() {
        document.removeEventListener('keydown', this.closeOnEscape);
    }

    open(event) {
        event.preventDefault();
        const targetId = this.targetValue || this.element.dataset.modalTargetValue;
        const modal = document.getElementById(targetId);
        
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    close() {
        // Check if this is the modal itself or a button inside
        const modal = this.element.closest('.modal') || this.element;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    closeOnEscape(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal:not(.hidden)');
            openModals.forEach(modal => {
                modal.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }
    }
}
