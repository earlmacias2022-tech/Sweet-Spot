// SWEET SPOT — FRONT-END INTERACTIONS

const menuToggle = document.querySelector('.menu-toggle');
const mainNavigation = document.querySelector('.main-nav');

if (menuToggle && mainNavigation) {
    menuToggle.addEventListener('click', () => {
        const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';

        menuToggle.setAttribute('aria-expanded', String(!isOpen));
        menuToggle.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
        mainNavigation.classList.toggle('is-open', !isOpen);
        document.body.classList.toggle('menu-open', !isOpen);
    });

    mainNavigation.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'Open navigation');
            mainNavigation.classList.remove('is-open');
            document.body.classList.remove('menu-open');
        });
    });
}

// Filter the live products by category.
const filterButtons = document.querySelectorAll('.filter-btn');
const productCards = document.querySelectorAll('.product-card');

filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const selectedFilter = button.dataset.filter;

        filterButtons.forEach((item) => item.classList.remove('active'));
        button.classList.add('active');

        productCards.forEach((card) => {
            const matchesFilter = selectedFilter === 'all' || card.dataset.category === selectedFilter;
            card.hidden = !matchesFilter;
        });
    });
});

// Customer feedback is submitted securely through the homepage PHP form.


// Reveal customer-side content gently as it enters the viewport.
const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
const revealTargets = document.querySelectorAll(
    '.hero-content, .section-heading, .product-card, .promotions-heading, ' +
    '.customer-promotion-card, .announcements-heading, .announcement-card, ' +
    '.story-image, .story-content, .center-heading, .value-card, ' +
    '.contact-copy, .contact-form, .product-detail-grid, .product-poll-section, ' +
    '.reviews-heading, .review-card, .reviews-empty, .review-form'
);

if ('IntersectionObserver' in window && !motionPreference.matches) {
    document.body.classList.add('motion-ready');

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -35px 0px'
    });

    revealTargets.forEach((element) => {
        element.classList.add('reveal-on-scroll');
        revealObserver.observe(element);
    });
}
