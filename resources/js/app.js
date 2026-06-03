import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const revealDashboard = () => {
    const items = document.querySelectorAll('[data-reveal]');

    if (! items.length) {
        return;
    }

    if (! ('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        rootMargin: '0px 0px -18% 0px',
        threshold: 0.04,
    });

    items.forEach((item) => observer.observe(item));
};

document.addEventListener('DOMContentLoaded', revealDashboard);
document.addEventListener('alpine:navigated', revealDashboard);
