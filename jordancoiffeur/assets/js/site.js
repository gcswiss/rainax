// Mobiles Menü
const btn = document.querySelector('.menu-btn');
const nav = document.getElementById('nav');
if (btn && nav) {
  btn.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', String(open));
  });
  nav.addEventListener('click', (e) => { if (e.target.closest('a')) { nav.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); } });
}

// Sanftes Einblenden beim Scrollen
const items = document.querySelectorAll('.reveal');
if ('IntersectionObserver' in window) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
  }, { rootMargin: '0px 0px -8% 0px' });
  items.forEach((el) => io.observe(el));
} else {
  items.forEach((el) => el.classList.add('in'));
}

// Hero-Video: Pause-Knopf und Rücksicht auf «Bewegung reduzieren»
const vid = document.querySelector('.hero video');
const tog = document.querySelector('.video-toggle');
if (vid && tog) {
  const play = '<svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" aria-hidden="true"><path d="M3 1l10 6-10 6z"/></svg>';
  const pause = tog.innerHTML;
  const sync = () => { tog.innerHTML = vid.paused ? play : pause; tog.setAttribute('aria-label', vid.paused ? 'Video abspielen' : 'Video anhalten'); };
  if (matchMedia('(prefers-reduced-motion: reduce)').matches) vid.pause();
  tog.addEventListener('click', () => { vid.paused ? vid.play() : vid.pause(); });
  vid.addEventListener('play', sync); vid.addEventListener('pause', sync); sync();
}
