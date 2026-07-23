import './bootstrap';

const revealItems = document.querySelectorAll('[data-reveal]');

if (revealItems.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100', 'translate-y-0');
                    entry.target.classList.remove('opacity-0', 'translate-y-6');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.18 }
    );

    revealItems.forEach((el) => observer.observe(el));
}

document.querySelectorAll('[data-plan-group]').forEach((group) => {
    const options = group.querySelectorAll('[data-plan-option]');

    options.forEach((option) => {
        option.addEventListener('click', () => {
            options.forEach((item) => item.setAttribute('aria-checked', 'false'));
            option.setAttribute('aria-checked', 'true');
        });
    });
});
