import './bootstrap';
import './styles/app.css';

// Lucide icons + Turbo refresh
function initializeLucideIcons() {
  if (window.lucide?.createIcons) window.lucide.createIcons();
}

function initializeTheme() {
  const theme = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

  if (theme === 'dark' || (!theme && prefersDark)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  initializeTheme();
  initializeLucideIcons();
});

document.addEventListener('turbo:load', () => {
  initializeTheme();
  initializeLucideIcons();
});
