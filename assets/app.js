import './bootstrap.js';
import * as Turbo from '@hotwired/turbo';
import '@symfony/ux-turbo';
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
    if (typeof window !== 'undefined' && window.lucide) {
        window.lucide.createIcons();
    }
}

function initializeLucideIconsWithRetry(retries = 10, delayMs = 100) {
    if (typeof window !== 'undefined' && window.lucide) {
        initializeLucideIcons();
        return;
    }

    if (retries <= 0) {
        return;
    }

    setTimeout(() => initializeLucideIconsWithRetry(retries - 1, delayMs), delayMs);
}

let iconsObserverAttached = false;
function attachLucideObserver() {
    if (iconsObserverAttached || typeof window === 'undefined' || !window.MutationObserver) {
        return;
    }

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (!mutation.addedNodes || mutation.addedNodes.length === 0) {
                continue;
            }

            for (const node of mutation.addedNodes) {
                if (!(node instanceof HTMLElement)) {
                    continue;
                }

                if (node.matches('[data-lucide]') || node.querySelector('[data-lucide]')) {
                    initializeLucideIconsWithRetry();
                    return;
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
    iconsObserverAttached = true;
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
    initializeLucideIconsWithRetry();
    attachLucideObserver();
});

// Re-run after Turbo navigation (for Symfony UX Turbo)
document.addEventListener('turbo:load', () => {
    initializeTheme();
    initializeLucideIconsWithRetry();
    attachLucideObserver();
});

// Also handle turbo:render for cached pages
document.addEventListener('turbo:render', () => {
    initializeLucideIconsWithRetry();
});

// Also handle Turbo frame updates
document.addEventListener('turbo:frame-load', () => {
    initializeLucideIconsWithRetry();
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

// Expose a single global helper used by Twig templates and inline scripts.
if (typeof window !== 'undefined') {
    window.initIcons = initializeLucideIconsWithRetry;
    if (!window.Turbo) {
        window.Turbo = Turbo;
    }
    if (window.Turbo?.session) {
        window.Turbo.session.drive = true;
    }
    if (typeof window.Turbo?.start === 'function') {
        window.Turbo.start();
    }
}
