(function () {
  'use strict';

  /* ── helpers ─────────────────────────────────── */
  const $ = s => document.querySelector(s);
  const $$ = s => [...document.querySelectorAll(s)];
  const root = document.documentElement;

  /* ── THEME picker ─────────────────────────────── */
  const sel = $('#theme');
  if (sel) {
    sel.value = root.dataset.theme || 'neon-blue';
    sel.onchange = () => {
      root.dataset.theme = sel.value;
      try { localStorage.setItem('nb-theme', sel.value); } catch (_) {}
    };
  }

  /* ── SIDEBAR collapse ─────────────────────────── */
  $('#collapse')?.addEventListener('click', () => {
    root.classList.toggle('collapsed');
    try { localStorage.setItem('nb-nav', root.classList.contains('collapsed') ? '1' : '0'); } catch (_) {}
  });

  /* ── BURGER (mobile) ──────────────────────────── */
  $('#burger')?.addEventListener('click', () => {
    $('.side').classList.toggle('open');
  });
  // close sidebar when clicking outside on mobile
  document.addEventListener('click', e => {
    const side = $('.side');
    if (side?.classList.contains('open') && !side.contains(e.target) && !$('#burger').contains(e.target)) {
      side.classList.remove('open');
    }
  });

  /* ── TOP BAR: glassmorphism on scroll ─────────── */
  const topBar = $('.top');
  if (topBar) {
    const onScroll = () => topBar.classList.toggle('scrolled', window.scrollY > 30);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── HERO CROSSFADE SLIDER ────────────────────── */
  const slides = $$('.slide');
  if (slides.length) {
    let cur = 0;
    let timer = null;
    const INTERVAL = 6000; // ms between auto-advance
    const progressBar = $('#hero-progress');
    const dots = $$('.hero-dot');
    let progressStart = null;

    function setSlide(idx) {
      const prev = cur;
      cur = (idx + slides.length) % slides.length;

      slides[prev].classList.remove('active');
      slides[prev].classList.add('prev');
      slides[cur].classList.add('active');

      // remove 'prev' class after transition
      setTimeout(() => slides[prev].classList.remove('prev'), 1000);

      // update dots
      dots.forEach((d, i) => d.classList.toggle('active', i === cur));

      // reset progress bar
      if (progressBar) {
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
        // force reflow
        void progressBar.offsetWidth;
        progressBar.style.transition = `width ${INTERVAL}ms linear`;
        progressBar.style.width = '100%';
      }
    }

    function startAuto() {
      stopAuto();
      timer = setInterval(() => setSlide(cur + 1), INTERVAL);
      // restart progress bar animation
      if (progressBar) {
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
        void progressBar.offsetWidth;
        progressBar.style.transition = `width ${INTERVAL}ms linear`;
        progressBar.style.width = '100%';
      }
    }

    function stopAuto() {
      clearInterval(timer);
      if (progressBar) {
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
      }
    }

    // initial state
    slides[0].classList.add('active');
    dots[0]?.classList.add('active');

    // arrow buttons
    $('.prev')?.addEventListener('click', () => { setSlide(cur - 1); startAuto(); });
    $('.next')?.addEventListener('click', () => { setSlide(cur + 1); startAuto(); });

    // dot buttons
    dots.forEach((d, i) => d.addEventListener('click', () => { setSlide(i); startAuto(); }));

    // pause on hover
    const hero = $('.hero');
    if (hero && !matchMedia('(prefers-reduced-motion:reduce)').matches) {
      hero.addEventListener('mouseenter', stopAuto);
      hero.addEventListener('mouseleave', startAuto);
      startAuto();
    } else {
      // still show dots / allow navigation, just no auto
      slides[0].classList.add('active');
    }
  }

  /* ── HORIZONTAL STRIP DRAG-SCROLL ─────────────── */
  $$('.strip').forEach(strip => {
    let isDown = false, startX, scrollLeft;
    strip.addEventListener('mousedown', e => {
      isDown = true;
      strip.style.cursor = 'grabbing';
      startX = e.pageX - strip.offsetLeft;
      scrollLeft = strip.scrollLeft;
    });
    strip.addEventListener('mouseleave', () => { isDown = false; strip.style.cursor = 'grab'; });
    strip.addEventListener('mouseup', () => { isDown = false; strip.style.cursor = 'grab'; });
    strip.addEventListener('mousemove', e => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - strip.offsetLeft;
      strip.scrollLeft = scrollLeft - (x - startX) * 1.4;
    });
  });

  /* ── INTERSECTION OBSERVER — fade-up & strip ──── */
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        io.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  $$('.fade-up, .strip').forEach(el => io.observe(el));

  /* ── USER DROPDOWN ──────────────────────────────── */
  const userBtn  = $('#user-menu-btn');
  const userDrop = $('#user-dropdown');
  if (userBtn && userDrop) {
    userBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = !userDrop.hidden;
      userDrop.hidden = open;
      userBtn.setAttribute('aria-expanded', String(!open));
    });
    document.addEventListener('click', (e) => {
      if (!userDrop.hidden && !userDrop.contains(e.target) && e.target !== userBtn) {
        userDrop.hidden = true;
        userBtn.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !userDrop.hidden) {
        userDrop.hidden = true;
        userBtn.setAttribute('aria-expanded', 'false');
        userBtn.focus();
      }
    });
  }

  /* ── THEME: inject --pri-raw for rgba() usage ─── */
  function applyThemePriRaw(theme) {
    const map = {
      'neon-blue':       '0,212,255',
      'matrix-green':    '0,255,70',
      'synthwave':       '255,0,200',
      'amber-terminal':  '255,176,0',
      'blood-red':       '255,30,30',
    };
    const raw = map[theme] || '0,212,255';
    root.style.setProperty('--pri-raw', raw);
  }
  applyThemePriRaw(root.dataset.theme || 'neon-blue');
  if (sel) {
    const origChange = sel.onchange;
    sel.onchange = () => {
      origChange?.();
      applyThemePriRaw(sel.value);
    };
  }

  /* ── HUE PICKER preview (submit/edit form) ─────── */
  const hueInput  = $('#hue');
  const hueMarker = $('.hue-marker');
  if (hueInput && hueMarker) {
    function updateHueMarker() {
      const pct = parseInt(hueInput.value, 10) / 360 * 100;
      hueMarker.style.left = pct + '%';
    }
    hueInput.addEventListener('input', updateHueMarker);
    updateHueMarker();
  }

})();


/* ── Motion & polish (v2) ───────────────────────── */
(function(){
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  // staggered reveal inside each parent
  const groups = new Map();
  document.querySelectorAll('.fade-up').forEach(el=>{
    const n = groups.get(el.parentNode) || 0; groups.set(el.parentNode, n+1);
    el.style.transitionDelay = Math.min(n,6)*70 + 'ms';
  });
  // cursor spotlight on cards
  document.addEventListener('pointermove', e=>{
    const c = e.target.closest && e.target.closest('.card,.explore-card,.cat-card,.stat-card');
    if(!c) return;
    const r = c.getBoundingClientRect();
    c.style.setProperty('--mx', (e.clientX-r.left)+'px');
    c.style.setProperty('--my', (e.clientY-r.top)+'px');
  }, {passive:true});
  // count-up numbers
  if(!reduce && 'IntersectionObserver' in window){
    const io = new IntersectionObserver(es=>es.forEach(en=>{
      if(!en.isIntersecting) return; io.unobserve(en.target);
      const el = en.target, end = parseInt(el.textContent.replace(/[^\d]/g,''),10);
      if(isNaN(end) || end < 2) return;
      const t0 = performance.now(), dur = 900;
      (function tick(t){const p=Math.min(1,(t-t0)/dur), v=Math.round(end*(1-Math.pow(1-p,3)));
        el.textContent = v.toLocaleString('en-US'); if(p<1) requestAnimationFrame(tick);})(t0);
    }),{threshold:.4});
    document.querySelectorAll('.stat-val').forEach(el=>io.observe(el));
  }
})();
