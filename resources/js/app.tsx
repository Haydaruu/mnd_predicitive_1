import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { configureEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws'],
});

// Initialize CSRF protection with proper credentials
const initializeCSRF = async () => {
    try {
        // Get CSRF cookie first with proper credentials
        const response = await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        if (response.ok) {
            console.log('✅ CSRF cookie initialized successfully');
        } else {
            console.warn('⚠️ CSRF cookie response:', response.status);
        }
    } catch (error) {
        console.error('❌ Failed to initialize CSRF:', error);
    }
};

// Initialize CSRF on app load
initializeCSRF();

// Get CSRF token from meta tag or cookie
const getCSRFToken = () => {
    // Try meta tag first
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaToken) return metaToken;
    
    // Try XSRF cookie
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN') {
            return decodeURIComponent(value);
        }
    }
    
    return null;
};

// Override fetch for manual requests
const originalFetch = window.fetch;
window.fetch = (input, init = {}) => {
    const url = typeof input === 'string' ? input : input.url;
    const headers = new Headers(init.headers || {});
    
    // Add CSRF token for same-origin requests
    if (url.startsWith('/') || url.includes('127.0.0.1:8000') || url.includes('localhost:8000')) {
        const csrfToken = getCSRFToken();
        
        if (csrfToken) {
            headers.set('X-XSRF-TOKEN', csrfToken);
            headers.set('X-CSRF-TOKEN', csrfToken);
        }
        
        if (!headers.has('X-Requested-With')) {
            headers.set('X-Requested-With', 'XMLHttpRequest');
        }

        if (!headers.has('Accept')) {
            headers.set('Accept', 'application/json');
        }
    }

    return originalFetch(input, {
        ...init,
        headers,
        credentials: 'same-origin', // Use same-origin instead of include
    });
};

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();