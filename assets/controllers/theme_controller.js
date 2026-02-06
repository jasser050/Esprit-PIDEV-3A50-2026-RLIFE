import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Theme is already set in the head via inline script
        // This controller handles toggling
        console.log('Theme controller connected');
    }

    toggle() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        
        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            console.log('Switched to light mode');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            console.log('Switched to dark mode');
        }

        // Re-initialize Lucide icons after theme change
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}
