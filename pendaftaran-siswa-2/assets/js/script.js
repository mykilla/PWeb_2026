document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) event.preventDefault();
    });
});
document.querySelectorAll('[data-print]').forEach((btn) => btn.addEventListener('click', () => window.print()));
document.querySelectorAll('[data-close]').forEach((btn) => btn.addEventListener('click', () => window.close()));
