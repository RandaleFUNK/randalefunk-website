const header = document.querySelector('[data-header]');
const menuButton = document.querySelector('.menu-toggle');
const navigation = document.querySelector('.primary-nav');
const navLinks = [...document.querySelectorAll('.primary-nav a')];
const sections = navLinks.map((link) => document.querySelector(link.getAttribute('href'))).filter(Boolean);

document.querySelectorAll('[data-year]').forEach((item) => { item.textContent = new Date().getFullYear(); });

if (menuButton && navigation) {
  menuButton.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!isOpen));
    menuButton.querySelector('.sr-only').textContent = isOpen ? 'Menü öffnen' : 'Menü schließen';
    navigation.classList.toggle('open', !isOpen);
  });

  navLinks.forEach((link) => link.addEventListener('click', () => {
    menuButton.setAttribute('aria-expanded', 'false');
    menuButton.querySelector('.sr-only').textContent = 'Menü öffnen';
    navigation.classList.remove('open');
  }));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      menuButton.setAttribute('aria-expanded', 'false');
      navigation.classList.remove('open');
      menuButton.focus();
    }
  });
}

const updateNavigation = () => {
  header?.classList.toggle('scrolled', window.scrollY > 20);
  const marker = window.scrollY + window.innerHeight * 0.35;
  let current = sections[0]?.id;
  sections.forEach((section) => { if (section.offsetTop <= marker) current = section.id; });
  navLinks.forEach((link) => {
    const active = link.getAttribute('href') === `#${current}`;
    link.classList.toggle('active', active);
    if (active) link.setAttribute('aria-current', 'location'); else link.removeAttribute('aria-current');
  });
};

window.addEventListener('scroll', updateNavigation, { passive: true });
updateNavigation();
