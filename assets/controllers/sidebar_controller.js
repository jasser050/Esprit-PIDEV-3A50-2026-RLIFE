import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['sidebar', 'backdrop', 'brandText', 'navText', 'sectionLabel', 'badge', 'userCard'];

    connect() {
        // Check if we're on mobile
        this.isMobile = window.innerWidth < 1024;

        // Set initial state for mobile
        if (this.isMobile && this.hasSidebarTarget) {
            this.sidebarTarget.classList.add('-translate-x-full');
        }

        // Listen for resize
        this.handleResize = this.handleResize.bind(this);
        this.handleKeydown = this.handleKeydown.bind(this);

        window.addEventListener('resize', this.handleResize);
        document.addEventListener('keydown', this.handleKeydown);
    }

    disconnect() {
        window.removeEventListener('resize', this.handleResize);
        document.removeEventListener('keydown', this.handleKeydown);
    }

    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 1024;

        // If switching from mobile to desktop, ensure sidebar is visible
        if (wasMobile && !this.isMobile && this.hasSidebarTarget) {
            this.sidebarTarget.classList.remove('-translate-x-full');
            this.hideBackdrop();
        }

        // If switching from desktop to mobile, ensure sidebar is hidden
        if (!wasMobile && this.isMobile && this.hasSidebarTarget) {
            this.sidebarTarget.classList.add('-translate-x-full');
        }
    }

    handleKeydown(event) {
        // Close on Escape key
        if (event.key === 'Escape' && this.isMobile) {
            this.close();
        }
    }

    toggle() {
        if (this.hasSidebarTarget) {
            const isHidden = this.sidebarTarget.classList.contains('-translate-x-full');

            if (isHidden) {
                this.open();
            } else {
                this.close();
            }
        }
    }

    open() {
        if (this.hasSidebarTarget) {
            this.sidebarTarget.classList.remove('-translate-x-full');
            this.showBackdrop();
            document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
        }
    }

    close() {
        if (this.hasSidebarTarget && this.isMobile) {
            this.sidebarTarget.classList.add('-translate-x-full');
            this.hideBackdrop();
            document.body.classList.remove('overflow-hidden');
        }
    }

    showBackdrop() {
        if (this.hasBackdropTarget && this.isMobile) {
            this.backdropTarget.classList.remove('hidden');
            requestAnimationFrame(() => {
                this.backdropTarget.classList.add('animate-fade-in');
            });
        }
    }

    hideBackdrop() {
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.add('hidden');
            this.backdropTarget.classList.remove('animate-fade-in');
        }
    }
}
