import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// Initialize Lucide icons function
function initializeLucideIcons() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

// Initialize theme from localStorage
function initializeTheme() {
    const theme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (theme === 'dark' || (!theme && prefersDark)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Run on initial page load
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();
    initializeLucideIcons();
});

// Re-run after Turbo navigation (for Symfony UX Turbo)
document.addEventListener('turbo:load', () => {
    initializeTheme();
    initializeLucideIcons();
});

// Also handle turbo:render for cached pages
document.addEventListener('turbo:render', () => {
    initializeLucideIcons();
});

// Handle turbo:before-render to ensure theme persists
document.addEventListener('turbo:before-render', (event) => {
    // Ensure dark class is preserved on the new document
    const theme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (theme === 'dark' || (!theme && prefersDark)) {
        event.detail.newBody.parentElement.classList.add('dark');
    } else {
        event.detail.newBody.parentElement.classList.remove('dark');
    }
});
