// Faragoman v2 — Stories viewer (vanilla, zero external deps, local only).
(() => {
  'use strict';

  const rings = Array.from(document.querySelectorAll('.story-ring'));
  const viewer = document.getElementById('story-viewer');
  if (!rings.length || !viewer) return;

  const imgEl   = document.getElementById('story-viewer-img');
  const titleEl = document.getElementById('story-viewer-title');
  const ctaEl   = document.getElementById('story-viewer-cta');
  const barsEl  = document.getElementById('story-bars');
  const prevBtn = document.getElementById('story-prev');
  const nextBtn = document.getElementById('story-next');

  const STORIES = rings.map((r) => ({
    title: r.dataset.storyTitle || '',
    image: r.dataset.storyImage || '',
    link:  r.dataset.storyLink || '',
  }));
  const DURATION = 5000;

  let index = 0;
  let timer = null;
  let startedAt = 0;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function buildBars() {
    barsEl.innerHTML = '';
    STORIES.forEach(() => {
      const track = document.createElement('span');
      track.className = 'story-bar';
      const fill = document.createElement('span');
      fill.className = 'story-bar__fill';
      track.appendChild(fill);
      barsEl.appendChild(track);
    });
  }

  function paintBars() {
    const fills = barsEl.querySelectorAll('.story-bar__fill');
    fills.forEach((f, i) => {
      f.style.transition = 'none';
      f.style.width = i < index ? '100%' : '0%';
    });
  }

  function animateBar() {
    const fills = barsEl.querySelectorAll('.story-bar__fill');
    const active = fills[index];
    if (!active) return;
    if (reduceMotion) { active.style.width = '100%'; return; }
    active.style.transition = 'none';
    active.style.width = '0%';
    // force reflow so the transition restarts
    void active.offsetWidth;
    active.style.transition = `width ${DURATION}ms linear`;
    active.style.width = '100%';
  }

  function show(i) {
    if (i < 0) i = 0;
    if (i >= STORIES.length) { close(); return; }
    index = i;
    const s = STORIES[index];
    imgEl.src = s.image;
    imgEl.alt = s.title;
    titleEl.textContent = s.title;
    if (s.link) { ctaEl.href = s.link; ctaEl.hidden = false; } else { ctaEl.hidden = true; }
    paintBars();
    animateBar();
    schedule();
  }

  function schedule() {
    clearTimeout(timer);
    if (reduceMotion) return;
    startedAt = Date.now();
    timer = setTimeout(() => show(index + 1), DURATION);
  }

  function open(i) {
    buildBars();
    viewer.classList.add('is-open');
    viewer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    show(i);
  }

  function close() {
    clearTimeout(timer);
    viewer.classList.remove('is-open');
    viewer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    imgEl.src = '';
  }

  rings.forEach((r, i) => r.addEventListener('click', () => open(i)));
  nextBtn.addEventListener('click', () => show(index + 1));
  prevBtn.addEventListener('click', () => show(index - 1));
  viewer.querySelectorAll('[data-story-close]').forEach((el) => el.addEventListener('click', close));

  document.addEventListener('keydown', (e) => {
    if (!viewer.classList.contains('is-open')) return;
    if (e.key === 'Escape') close();
    else if (e.key === 'ArrowLeft') show(index + 1);   // RTL: left = next
    else if (e.key === 'ArrowRight') show(index - 1);
  });
})();
