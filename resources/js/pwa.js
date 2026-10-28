/**
 * eHadir Progressive Web App (PWA) Client Controller
 *
 * Handles:
 *  - Service Worker lifecycle & updates
 *  - Native install prompt capture (Chromium/Android/Edge)
 *  - iOS Safari standalone detection & helper utilities
 *  - Network connectivity state dispatching
 */

(function () {
    let deferredPrompt = null;

    const isIos = () => {
        const ua = window.navigator.userAgent.toLowerCase();
        return /iphone|ipad|ipod/.test(ua) || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
    };

    const isStandalone = () => {
        return (
            window.matchMedia('(display-mode: standalone)').matches ||
            ('standalone' in window.navigator && window.navigator.standalone === true)
        );
    };

    // Public PWA API exposed on window.eHadirPwa
    window.eHadirPwa = {
        isIos: isIos(),
        isStandalone: isStandalone(),
        isInstallable: false,
        promptInstall: async function () {
            if (!deferredPrompt) {
                if (isIos() && !isStandalone()) {
                    window.dispatchEvent(new CustomEvent('ehadir:ios-install-guide'));
                    return 'ios-guide';
                }
                return null;
            }

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            window.eHadirPwa.isInstallable = false;
            window.dispatchEvent(new CustomEvent('ehadir:pwa-installed', { detail: { outcome } }));
            return outcome;
        }
    };

    // Capture beforeinstallprompt for Android Chrome, Edge & Desktop Chrome
    window.addEventListener('beforeinstallprompt', (event) => {
        // Prevent the mini-infobar from appearing on mobile
        event.preventDefault();
        deferredPrompt = event;
        window.eHadirPwa.isInstallable = true;

        // Notify application components (e.g. Alpine.js or header buttons)
        window.dispatchEvent(new CustomEvent('ehadir:pwa-installable', { detail: { prompt: deferredPrompt } }));
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        window.eHadirPwa.isInstallable = false;
        console.log('[eHadir PWA] Aplikasi telah berjaya dipasang ke peranti.');
    });

    // Register Service Worker on window load
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then((registration) => {
                    // Check for background updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        if (!newWorker) return;

                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New version available
                                window.dispatchEvent(new CustomEvent('ehadir:pwa-update-available', {
                                    detail: {
                                        reload: () => {
                                            newWorker.postMessage({ type: 'SKIP_WAITING' });
                                            window.location.reload();
                                        }
                                    }
                                }));
                            }
                        });
                    });
                })
                .catch((error) => {
                    console.warn('[eHadir PWA] Pendaftaran Service Worker gagal:', error);
                });
        });

        // Auto reload on controller change (when new SW takes over)
        let refreshing = false;
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (!refreshing) {
                refreshing = true;
                window.location.reload();
            }
        });
    }

    // Network connectivity broadcasts
    window.addEventListener('online', () => {
        window.dispatchEvent(new CustomEvent('ehadir:network-online'));
    });

    window.addEventListener('offline', () => {
        window.dispatchEvent(new CustomEvent('ehadir:network-offline'));
    });
})();

