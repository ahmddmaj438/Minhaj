import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const revealInterface = () => {
    const items = [...new Set(document.querySelectorAll('[data-reveal], main section, main article, .auth-card'))];

    if (! items.length) {
        return;
    }

    items.forEach((item, index) => {
        item.classList.add('dashboard-reveal');
        item.setAttribute('data-reveal', '');

        if (! item.style.getPropertyValue('--reveal-delay')) {
            item.style.setProperty('--reveal-delay', `${Math.min(index * 35, 180)}ms`);
        }
    });

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || ! ('IntersectionObserver' in window)) {
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

const enhanceInteractiveForms = () => {
    const requiredTextValue = document.documentElement.lang?.startsWith('ar') ? 'مطلوب' : 'required';

    document.querySelectorAll('form:not([data-motion-ready])').forEach((form) => {
        form.dataset.motionReady = 'true';

        form.querySelectorAll('input[required], select[required], textarea[required]').forEach((field) => {
            field.setAttribute('aria-required', 'true');

            if (field.id && window.CSS?.escape) {
                const label = form.querySelector(`label[for="${CSS.escape(field.id)}"]`);

                if (label && ! label.querySelector('.required-indicator')) {
                    const indicator = document.createElement('span');
                    indicator.className = 'required-indicator';
                    indicator.setAttribute('aria-hidden', 'true');
                    indicator.textContent = '*';
                    label.append(' ', indicator);

                    const requiredText = document.createElement('span');
                    requiredText.className = 'sr-only';
                    requiredText.textContent = requiredTextValue;
                    label.append(requiredText);
                }
            }
        });

        form.addEventListener('submit', () => {
            if (form.hasAttribute('data-no-loading')) {
                return;
            }

            if (typeof form.checkValidity === 'function' && ! form.checkValidity()) {
                return;
            }

            form.classList.add('is-submitting');
            form.setAttribute('aria-busy', 'true');
        });

        form.addEventListener('invalid', () => {
            form.classList.remove('is-submitting');
            form.removeAttribute('aria-busy');
        }, true);

        form.addEventListener('invalid', (event) => {
            const field = event.target;

            if (! (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                return;
            }

            field.setAttribute('aria-invalid', 'true');

            if (! form.dataset.focusedInvalid) {
                form.dataset.focusedInvalid = 'true';

                window.requestAnimationFrame(() => {
                    field.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    field.focus({ preventScroll: true });
                    delete form.dataset.focusedInvalid;
                });
            }
        }, true);

        form.addEventListener('input', (event) => {
            const field = event.target;

            if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
                if (field.checkValidity()) {
                    field.removeAttribute('aria-invalid');
                }
            }
        });

        form.addEventListener('change', (event) => {
            const field = event.target;

            if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                if (field.checkValidity()) {
                    field.removeAttribute('aria-invalid');
                }
            }
        });
    });
};

const resetSubmittingForms = () => {
    document.querySelectorAll('form.is-submitting').forEach((form) => {
        form.classList.remove('is-submitting');
        form.removeAttribute('aria-busy');
    });
};

const enhanceTableFilters = () => {
    document.querySelectorAll('[data-table-filter]:not([data-filter-ready])').forEach((input) => {
        input.dataset.filterReady = 'true';

        const table = document.querySelector(input.dataset.tableFilter);

        if (! table) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('[data-filter-row]'));
        const emptyRow = table.querySelector('[data-filter-empty]');

        const filterRows = () => {
            const term = input.value.trim().toLocaleLowerCase();
            let visibleRows = 0;

            rows.forEach((row) => {
                const isMatch = ! term || row.textContent.toLocaleLowerCase().includes(term);
                row.hidden = ! isMatch;

                if (isMatch) {
                    visibleRows += 1;
                }
            });

            if (emptyRow) {
                emptyRow.hidden = visibleRows > 0;
            }
        };

        input.addEventListener('input', filterRows);
        filterRows();
    });
};

const enhanceInterfaceMotion = () => {
    revealInterface();
    enhanceInteractiveForms();
    enhanceTableFilters();
};

document.addEventListener('DOMContentLoaded', enhanceInterfaceMotion);
document.addEventListener('alpine:navigated', enhanceInterfaceMotion);
window.addEventListener('pageshow', resetSubmittingForms);
