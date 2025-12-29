import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    return toUrl(urlToCheck) === currentUrl;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

// Route helper with wayfinder support and fallback
export function route(name: string, params?: Record<string, any> | number): string {
    try {
        // Try to use wayfinder routes dynamically
        const routeModules: any = {};
        
        // Import wayfinder routes dynamically
        const routeMap: Record<string, any> = {
            'home': () => import('@/routes').then(m => m.home),
            'login': () => import('@/routes').then(m => m.login),
            'register': () => import('@/routes').then(m => m.register),
            'dashboard': () => import('@/routes').then(m => m.dashboard),
            'admin.dashboard': () => import('@/routes/admin').then(m => m.default?.dashboard),
            'admin.subscriptions.index': () => import('@/routes/admin/subscriptions').then(m => m.default?.index),
            'admin.subscriptions.store': () => import('@/routes/admin/subscriptions').then(m => m.default?.store),
            'admin.subscriptions.update': () => import('@/routes/admin/subscriptions').then(m => m.default?.update),
            'admin.subscriptions.destroy': () => import('@/routes/admin/subscriptions').then(m => m.default?.destroy),
            'admin.users.index': () => import('@/routes/admin/users').then(m => m.default?.index),
            'admin.users.edit': () => import('@/routes/admin/users').then(m => m.default?.edit),
            'admin.users.update': () => import('@/routes/admin/users').then(m => m.default?.update),
        'admin.users.destroy': () => import('@/routes/admin/users').then(m => m.default?.destroy),
        'admin.payments.index': () => import('@/routes/admin/payments').then(m => m.default?.index),
        'admin.ai-settings.index': () => import('@/routes/admin/ai-settings').then(m => m.default?.index),
        'admin.ai-settings.update': () => import('@/routes/admin/ai-settings').then(m => m.default?.update),
        'user.dashboard': () => import('@/routes/user').then(m => m.default?.dashboard),
            'user.bidding.index': () => import('@/routes/user/bidding').then(m => m.default?.index),
            'user.bidding.settings': () => import('@/routes/user/bidding').then(m => m.default?.settings),
            'user.bidding.start': () => import('@/routes/user/bidding').then(m => m.default?.start),
            'user.bidding.stop': () => import('@/routes/user/bidding').then(m => m.default?.stop),
            'user.bidding.refresh-data': () => import('@/routes/user/bidding').then(m => m.default?.refreshData),
            'user.projects.index': () => import('@/routes/user/projects').then(m => m.default?.index),
            'user.portfolio.index': () => import('@/routes/user/portfolio').then(m => m.default?.index),
            'user.portfolio.store': () => import('@/routes/user/portfolio').then(m => m.default?.store),
            'user.portfolio.update': () => import('@/routes/user/portfolio').then(m => m.default?.update),
            'user.portfolio.destroy': () => import('@/routes/user/portfolio').then(m => m.default?.destroy),
            'freelancer.redirect': () => import('@/routes/freelancer').then(m => m.default?.redirect),
            'freelancer.callback': () => import('@/routes/freelancer').then(m => m.default?.callback),
        };

        // For now, use fallback routes to avoid async issues
        // In production, you can implement async route loading
        return fallbackRoute(name, params);
    } catch (error) {
        return fallbackRoute(name, params);
    }
}

// Fallback route helper
function fallbackRoute(name: string, params?: Record<string, any> | number): string {
    const routes: Record<string, string | ((id: number) => string)> = {
        'admin.dashboard': '/admin/dashboard',
        'admin.subscriptions.index': '/admin/subscriptions',
        'admin.subscriptions.store': '/admin/subscriptions',
        'admin.subscriptions.update': (id: number) => `/admin/subscriptions/${id}`,
        'admin.subscriptions.destroy': (id: number) => `/admin/subscriptions/${id}`,
        'admin.users.index': '/admin/users',
        'admin.users.edit': (id: number) => `/admin/users/${id}/edit`,
        'admin.users.update': (id: number) => `/admin/users/${id}`,
        'admin.users.destroy': (id: number) => `/admin/users/${id}`,
        'admin.payments.index': '/admin/payments',
        'admin.ai-settings.index': '/admin/ai-settings',
        'admin.ai-settings.update': '/admin/ai-settings',
        'user.dashboard': '/user/dashboard',
        'user.bidding.index': '/user/bidding',
        'user.bidding.settings': '/user/bidding/settings',
        'user.bidding.start': '/user/bidding/start',
        'user.bidding.stop': '/user/bidding/stop',
        'user.bidding.refresh-data': '/user/bidding/refresh-data',
        'user.projects.index': '/user/projects',
        'user.portfolio.index': '/user/portfolio',
        'user.portfolio.store': '/user/portfolio',
        'user.portfolio.update': (id: number) => `/user/portfolio/${id}`,
        'user.portfolio.destroy': (id: number) => `/user/portfolio/${id}`,
        'freelancer.redirect': '/auth/freelancer/redirect',
        'freelancer.callback': '/auth/freelancer/callback',
        'login': '/login',
        'register': '/register',
        'dashboard': '/dashboard',
        'home': '/',
        'password.request': '/forgot-password',
    };
    
    const routePath = routes[name];
    if (typeof routePath === 'function') {
        let id: number;
        if (typeof params === 'number') {
            id = params;
        } else if (params && typeof params === 'object' && 'id' in params) {
            id = params.id as number;
        } else if (params && typeof params === 'object' && 'portfolio' in params) {
            id = typeof params.portfolio === 'number' ? params.portfolio : (params.portfolio as any).id;
        } else {
            return `/#${name}`;
        }
        return routePath(id);
    }
    return routePath || `/#${name}`;
}
