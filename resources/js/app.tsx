import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { configureEcho } from '@laravel/echo-react';

configureEcho({
    broadcaster: 'pusher',
    key: 'my-key',
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
    enabledTransports: ['ws'],
});

// Global CSRF token management
let csrfToken = null;

// Initialize CSRF protection
const initializeCSRF = async () => {
    try {
        console.log('🔄 Initializing CSRF protection...');
        
        const response = await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        if (response.ok) {
            // Update CSRF token from meta tag after cookie is set
            setTimeout(() => {
                const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (metaToken) {
                    csrfToken = metaToken;
                    console.log('✅ CSRF token updated from meta tag');
                }
            }, 100);
            
            console.log('✅ CSRF cookie initialized successfully');
        } else {
            console.warn('⚠️ CSRF cookie response:', response.status);
        }
    } catch (error) {
        console.error('❌ Failed to initialize CSRF:', error);
    }
};

// Get fresh CSRF token
const getCSRFToken = () => {
    // Always try to get fresh token from meta tag first
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaToken && metaToken !== csrfToken) {
        csrfToken = metaToken;
        console.log('🔄 CSRF token refreshed from meta tag');
    }
    
    if (csrfToken) return csrfToken;
    
    // Fallback to XSRF cookie
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN') {
            const token = decodeURIComponent(value);
            csrfToken = token;
            return token;
        }
    }
    
    console.warn('⚠️ No CSRF token found');
    return null;
};

// Refresh CSRF token function
const refreshCSRFToken = async () => {
    try {
        console.log('🔄 Refreshing CSRF token...');
        await initializeCSRF();
        return getCSRFToken();
    } catch (error) {
        console.error('❌ Failed to refresh CSRF token:', error);
        return null;
    }
};

// Enhanced fetch with automatic CSRF token refresh
const originalFetch = window.fetch;
window.fetch = async (input, init = {}) => {
    const url = typeof input === 'string' ? input : input.url;
    const headers = new Headers(init.headers || {});
    
    // Only handle same-origin requests
    if (url.startsWith('/') || url.includes('127.0.0.1:8000') || url.includes('localhost:8000')) {
        let token = getCSRFToken();
        
        // For POST requests, ensure we have a fresh token
        if (init.method === 'POST' && !token) {
            token = await refreshCSRFToken();
        }
        
        if (token) {
            headers.set('X-CSRF-TOKEN', token);
            headers.set('X-XSRF-TOKEN', token);
        }
        
        if (!headers.has('X-Requested-With')) {
            headers.set('X-Requested-With', 'XMLHttpRequest');
        }

        if (!headers.has('Accept')) {
            headers.set('Accept', 'application/json');
        }

        const response = await originalFetch(input, {
            ...init,
            headers,
            credentials: 'same-origin',
        });

        // If we get 419 (CSRF mismatch), try to refresh token and retry once
        if (response.status === 419 && init.method === 'POST') {
            console.log('🔄 CSRF mismatch detected, refreshing token and retrying...');
            
            const newToken = await refreshCSRFToken();
            if (newToken) {
                headers.set('X-CSRF-TOKEN', newToken);
                headers.set('X-XSRF-TOKEN', newToken);
                
                return originalFetch(input, {
                    ...init,
                    headers,
                    credentials: 'same-origin',
                });
            }
        }

        return response;
    }

    return originalFetch(input, init);
};

// Initialize CSRF on app load
initializeCSRF();

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