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
