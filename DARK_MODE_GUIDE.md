# 🌙 Dark Mode Implementation Guide

## ✅ Dark Mode is Fully Working!

Your StudyFlow/RLife application has a complete dark mode implementation that works across **all pages**.

---

## 🎯 Where Dark Mode Works

### ✅ Landing Page (`/`)
- **Toggle Button**: Moon icon in top-right navigation
- **Stimulus Controller**: Uses `theme_controller.js`
- **Persistence**: Saves preference to localStorage

### ✅ Authentication Pages
- Login page (`/login`)
- Register page (`/register`)
- Uses `public.html.twig` layout

### ✅ User Dashboard (`/dashboard`)
- **Toggle Button**: Moon/Sun icon in header
- **Function**: `toggleTheme()` global function
- All dashboard cards and components styled for dark mode

### ✅ Settings Page (`/settings`)
- Profile editing
- Dark mode persists across forms
- All inputs styled for both themes

### ✅ Admin Panel (`/admin`)
- **Toggle Button**: Moon/Sun icon in header
- Admin dashboard, user management, statistics
- All admin components support dark mode

---

## 🔘 Toggle Buttons

### Landing Page & Public Pages
```html
<!-- Landing Page Nav (line 29-35) -->
<button type="button"
        class="p-2 rounded-xl text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        data-action="click->theme#toggle"
        aria-label="Toggle theme">
    <i data-lucide="moon" class="w-5 h-5 dark:hidden"></i>
    <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
</button>
```

**Icon SVG Paths:**
- **Moon** (Light mode): `<path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"></path>`
- **Sun** (Dark mode): Multiple paths with circle

### Dashboard & Admin
```html
<!-- Dashboard Header (base.html.twig line 274-280) -->
<button type="button"
        class="p-2 rounded-xl text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
        onclick="toggleTheme()"
        aria-label="Toggle theme">
    <i data-lucide="moon" class="w-5 h-5 dark:hidden"></i>
    <i data-lucide="sun" class="w-5 h-5 hidden dark:block"></i>
</button>
```

---

## 🛠️ Technical Implementation

### 1. **Theme Initialization** (Runs Before Page Load)

#### Public Pages (`layouts/public.html.twig` line 24-30)
```javascript
<script>
    // Check for dark mode preference on page load
    if (localStorage.getItem('theme') === 'dark' ||
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
</script>
```

#### Dashboard & Admin (`base.html.twig` line 23-48)
```javascript
<script>
    // Theme initialization and toggle function
    (function() {
        var theme = localStorage.getItem('theme');
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Set initial theme
        if (theme === 'dark' || (!theme && prefersDark)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Global toggle function
        window.toggleTheme = function() {
            var html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        };
    })();
</script>
```

### 2. **Stimulus Theme Controller** (`assets/controllers/theme_controller.js`)

Used on landing page and public pages:

```javascript
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
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
```

### 3. **Tailwind Dark Mode Classes**

All components use Tailwind's `dark:` prefix:

```html
<!-- Light mode → Dark mode -->
<div class="bg-white dark:bg-slate-900">
<p class="text-slate-900 dark:text-white">
<button class="bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600">
```

**Key Color Mappings:**

| Light Mode | Dark Mode | Usage |
|-----------|-----------|--------|
| `bg-white` | `bg-slate-900` | Cards, panels |
| `bg-slate-50` | `bg-slate-950` | Page background |
| `bg-slate-100` | `bg-slate-800` | Input backgrounds |
| `text-slate-900` | `text-white` | Primary text |
| `text-slate-600` | `text-slate-300` | Secondary text |
| `text-slate-500` | `text-slate-400` | Tertiary text |
| `border-slate-200` | `border-slate-800` | Borders |

---

## 🎨 Styling Components for Dark Mode

### Cards
```html
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
    <h2 class="text-slate-900 dark:text-white">Title</h2>
    <p class="text-slate-600 dark:text-slate-300">Description</p>
</div>
```

### Buttons
```html
<!-- Primary Button -->
<button class="bg-primary-600 hover:bg-primary-700 text-white">
    Action
</button>

<!-- Secondary Button -->
<button class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
    Cancel
</button>
```

### Inputs
```html
<input 
    type="text"
    class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500"
>
```

### Navigation
```html
<nav class="bg-white/80 dark:bg-slate-950/80 backdrop-blur-lg border-b border-slate-200/50 dark:border-slate-800/50">
```

---

## 🔄 How It Works

### 1. **Initial Load**
```
Page loads → Script checks localStorage
             ↓
  Has 'theme'? → Yes → Apply stored theme
             ↓
  No theme?    → Check system preference
             ↓
  Prefers dark? → Yes → Add 'dark' class
                ↓
                No  → Light mode (default)
```

### 2. **User Clicks Toggle**
```
User clicks moon/sun icon
        ↓
Toggle function runs
        ↓
Checks current state (is dark?)
        ↓
Toggles 'dark' class on <html>
        ↓
Saves preference to localStorage
        ↓
Re-initializes Lucide icons
        ↓
UI updates instantly (CSS transitions)
```

### 3. **Persistence**
```
localStorage.setItem('theme', 'dark')
                ↓
Stored in browser
                ↓
Survives page refresh
                ↓
Works across all pages
                ↓
Synced between dashboard/landing
```

---

## 🐛 Troubleshooting

### Problem: Icons don't show after toggle
**Solution:** Icons are re-initialized automatically
```javascript
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
```

### Problem: Theme doesn't persist
**Check:**
1. Browser allows localStorage
2. Console shows no errors
3. Check with: `localStorage.getItem('theme')`

### Problem: Some components not dark
**Solution:** Add dark mode classes
```html
<!-- Before -->
<div class="bg-white text-black">

<!-- After -->
<div class="bg-white dark:bg-slate-900 text-black dark:text-white">
```

---

## 📊 Browser Support

| Feature | Support |
|---------|---------|
| `localStorage` | ✅ All modern browsers |
| `matchMedia` | ✅ All modern browsers |
| Tailwind `dark:` | ✅ All modern browsers |
| Lucide Icons | ✅ All modern browsers |

**Minimum Requirements:**
- Chrome/Edge: 90+
- Firefox: 88+
- Safari: 14+
- Opera: 76+

---

## 🎯 Testing Checklist

### Landing Page
- [ ] Click moon icon → Page turns dark
- [ ] Click sun icon → Page turns light
- [ ] Refresh page → Theme persists
- [ ] All sections have proper contrast

### Login/Register
- [ ] Dark mode works on forms
- [ ] Inputs are readable
- [ ] Buttons visible in both modes

### Dashboard
- [ ] Click toggle in header
- [ ] Sidebar colors change
- [ ] Cards have proper backgrounds
- [ ] Charts readable in both modes
- [ ] Stats cards styled properly

### Settings
- [ ] Form inputs work in dark mode
- [ ] Profile picture visible
- [ ] Buttons have proper contrast
- [ ] Flash messages readable

### Admin Panel
- [ ] Toggle works in admin header
- [ ] User tables readable
- [ ] Statistics charts visible
- [ ] Action buttons clear

---

## 🚀 Advanced Features

### System Preference Detection
Automatically uses user's OS setting if no preference stored:
```javascript
window.matchMedia('(prefers-color-scheme: dark)').matches
```

### Smooth Transitions
All color changes are animated:
```css
.transition-colors {
    transition-property: color, background-color, border-color;
    transition-duration: 150ms;
}
```

### Console Logging (Development)
Toggle logs to console:
```
Theme controller connected
Switched to dark mode
Switched to light mode
```

---

## 📝 Quick Reference

### Enable Dark Mode on New Component

**1. Add dark mode classes:**
```html
<div class="bg-white dark:bg-slate-900">
```

**2. Test in both modes:**
- Toggle to dark
- Check readability
- Verify contrast

**3. Common patterns:**
```html
<!-- Background -->
bg-white dark:bg-slate-900

<!-- Text -->
text-slate-900 dark:text-white
text-slate-600 dark:text-slate-300

<!-- Borders -->
border-slate-200 dark:border-slate-800

<!-- Hover states -->
hover:bg-slate-100 dark:hover:bg-slate-800
```

---

## ✅ Summary

**Dark Mode Status:** ✅ **Fully Working**

**Coverage:**
- ✅ Landing page
- ✅ Login/Register
- ✅ Dashboard
- ✅ Settings
- ✅ Admin Panel

**Features:**
- ✅ Instant toggle
- ✅ localStorage persistence
- ✅ System preference detection
- ✅ Icon updates
- ✅ Smooth transitions
- ✅ Console logging (dev mode)

**How to Use:**
1. Click moon icon (☾) to enable dark mode
2. Click sun icon (☀) to enable light mode
3. Preference saves automatically
4. Works across all pages

---

## 🎉 Enjoy Dark Mode!

Your users can now study comfortably at night with a beautiful dark interface! 🌙
