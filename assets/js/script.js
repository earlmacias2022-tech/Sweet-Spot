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

// Carry the selected product into the contact form.
const productLinks = document.querySelectorAll('.product-link');
const subjectField = document.querySelector('#contact-subject');

productLinks.forEach((link) => {
    link.addEventListener('click', () => {
        if (subjectField) {
            subjectField.value = 'Product inquiry: ' + link.dataset.product;
        }
    });
});

// The form is a visual preview until the backend is connected.
const contactForm = document.querySelector('#contact-form');
const formFeedback = document.querySelector('#form-feedback');

if (contactForm && formFeedback) {
    contactForm.addEventListener('submit', (event) => {
        event.preventDefault();
        formFeedback.textContent = 'Thanks! This is a preview form. Message sending will be enabled in the backend phase.';
        contactForm.reset();
    });
}