document.write(`
    <footer>
        <p>&copy; 2026 QueueStand. All rights reserved.</p>
    </footer>
`);

function toggleNav(btn) {
  const nav = document.getElementById('main-nav');
  const isOpen = nav.classList.toggle('open');
  btn.setAttribute('aria-expanded', isOpen);
  btn.querySelector('.nav-toggle-icon').textContent = isOpen ? '\u2715' : '\u2630';
}

document.addEventListener('click', function (e) {
  const nav = document.getElementById('main-nav');
  const btn = document.querySelector('.nav-toggle');
  if (nav && btn && !nav.contains(e.target) && !btn.contains(e.target)) {
    nav.classList.remove('open');
    btn.setAttribute('aria-expanded', 'false');
    btn.querySelector('.nav-toggle-icon').textContent = '\u2630';
  }
});