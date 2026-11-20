// Progressive-enhancement mobile navigation controller for non-Livewire pages.
// Compatible with Livewire's bundled Alpine.js without double-initializing Alpine.
document.addEventListener('DOMContentLoaded', () => {
    const toggleButtons = document.querySelectorAll('[data-mobile-nav-toggle]');
    const drawer = document.getElementById('mobile-navigation-drawer');

    if (!drawer || toggleButtons.length === 0) {
        return;
    }

    const setOpen = (open) => {
        drawer.classList.toggle('hidden', !open);
        toggleButtons.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
    };

    toggleButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const isCurrentlyOpen = !drawer.classList.contains('hidden');
            setOpen(!isCurrentlyOpen);
        });
    });

    drawer.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !drawer.classList.contains('hidden')) {
            setOpen(false);
        }
    });
});
