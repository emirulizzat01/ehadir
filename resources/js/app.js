import './pwa';

/**
 * Global Password Visibility Toggle Utility
 *
 * Provides reactive show/hide visibility toggle for password fields.
 * Integrates with Alpine.js when present, and provides resilient
 * event delegation and programmatic helpers for pure DOM / dynamically inserted inputs.
 */

(function () {
    /**
     * Toggles visibility for an input element and updates its toggle button state.
     * @param {HTMLInputElement} input
     * @param {HTMLElement|null} button
     */
    function toggleInputState(input, button) {
        if (!input) return;

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        if (!button) {
            const container = input.closest('.relative') || input.parentElement;
            button = container?.querySelector('[data-password-toggle]');
        }

        if (button) {
            const eyeIcon = button.querySelector('[data-icon="eye"]');
            const eyeSlashIcon = button.querySelector('[data-icon="eye-slash"]');

            if (eyeIcon && eyeSlashIcon) {
                eyeIcon.style.display = isPassword ? 'none' : 'inline-flex';
                eyeSlashIcon.style.display = isPassword ? 'inline-flex' : 'none';
            }

            const label = isPassword ? 'Sembunyikan kata laluan' : 'Papar kata laluan';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
        }
    }

    /**
     * Delegated click handler for password toggle buttons.
     */
    document.addEventListener('click', (event) => {
        const toggleBtn = event.target.closest('[data-password-toggle]');
        if (!toggleBtn) return;

        // If Alpine is active on this component and handles reactivity, allow Alpine to execute
        if (window.Alpine && toggleBtn.closest('[x-data]')) {
            return;
        }

        const container = toggleBtn.closest('.relative') || toggleBtn.parentElement;
        if (!container) return;

        const input = container.querySelector('input[data-password-input]') ||
                      container.querySelector('input[type="password"], input[type="text"]');

        if (input) {
            toggleInputState(input, toggleBtn);
        }
    });

    /**
     * Expose global helper on window for programmatic access or custom scripts.
     * Usage:
     *   togglePasswordVisibility('#password')
     *   togglePasswordVisibility(buttonElement)
     */
    window.togglePasswordVisibility = function (target) {
        let input = null;
        let button = null;

        if (typeof target === 'string') {
            input = document.querySelector(target);
        } else if (target instanceof HTMLElement) {
            if (target.matches('input')) {
                input = target;
            } else {
                button = target;
                const container = button.closest('.relative') || button.parentElement;
                input = container?.querySelector('input[data-password-input]') ||
                        container?.querySelector('input[type="password"], input[type="text"]');
            }
        }

        if (input) {
            toggleInputState(input, button);
        }
    };
})();
