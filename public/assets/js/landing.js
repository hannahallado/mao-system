(function () {
  const nav = document.querySelector(".nav");
  if (!nav) return;

  // ensure nav is visible on load (if desired)
  nav.classList.add("scroll-show");

  let lastScrollY = window.pageYOffset;
  let ticking = false;
  const THRESHOLD = 8;

  window.addEventListener(
    "scroll",
    () => {
      if (ticking) return;
      ticking = true;

      window.requestAnimationFrame(() => {
        const currentY = window.pageYOffset;
        const delta = currentY - lastScrollY;
        const menuOpen = document
          .querySelector(".menu-icon")
          ?.classList.contains("active");

const heroClone = document.getElementById("heroLoopClone");
        const footerLimit = heroClone
          ? heroClone.offsetTop - window.innerHeight
          : Infinity;
        const atFullFooter =
          heroClone &&
          currentY >= footerLimit - 2 &&
          currentY <= footerLimit + 10;

        if (atFullFooter) {
          nav.style.animation = "none";
          nav.classList.add("scroll-show");
          nav.classList.remove("scroll-hide");
          lastScrollY = currentY;
          ticking = false;
          return;
        }

        if (
          heroClone &&
          currentY > footerLimit + 10 &&
          !window.isLoopGliding &&
          !menuOpen
        ) {
          nav.style.animation = "none";
          nav.classList.add("scroll-hide");
          nav.classList.remove("scroll-show");
          lastScrollY = currentY;
          ticking = false;
          return;
        }

        if (Math.abs(delta) > THRESHOLD) {
          nav.style.animation = "none";
          if (delta > 0 && !menuOpen) {
            // scrolling DOWN -> hide
            nav.classList.add("scroll-hide");
            nav.classList.remove("scroll-show");
          } else {
            // scrolling UP -> show
            nav.classList.add("scroll-show");
            nav.classList.remove("scroll-hide");
          }
          lastScrollY = currentY;
        }
        if (window.isLoopGliding) {
            lastScrollY = currentY;
            ticking = false;
            return;
          }
        ticking = false;
      });
    },
    { passive: true },
  );
})();

let scrollVelocity = 0;
let isScrolling = false;

// Slippery wheel scroll
window.addEventListener(
  "wheel",
  (e) => {
    e.preventDefault();
    scrollVelocity += e.deltaY * 0.2;

    if (!isScrolling) {
      isScrolling = true;
      momentumScroll();
    }
  },
  { passive: false },
);

function momentumScroll() {
  if (Math.abs(scrollVelocity) > 0.7) {
    window.scrollBy(0, scrollVelocity);
    scrollVelocity *= 0.86;
    requestAnimationFrame(momentumScroll);
  } else {
    scrollVelocity = 0;
    isScrolling = false;
  }
}

// Smooth anchor link scrolling
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", (e) => {
    e.preventDefault();
    const href = anchor.getAttribute("href");
    if (href === "#") return;
    const target = document.querySelector(href);
    if (target) {
      if (window.cancelFooterLoop) window.cancelFooterLoop();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });
});

lucide.createIcons();

// Parallax effect for hero background only
window.addEventListener("scroll", () => {
  const scrolled = window.pageYOffset;
  const hero = document.querySelector(".hero");
  const bg = document.querySelector(".hero-bg");
  const limit = hero.offsetHeight;

  if (scrolled < limit) {
    const translateY = scrolled * 0.09; // control movement speed
    bg.style.transform = `translateY(${translateY}px)`;
  }
});

const menuButton = document.getElementById("menuButton");
menuButton.addEventListener("click", function (e) {
  e.stopPropagation(); // prevent document click from closing immediately
  this.classList.toggle("active"); // toggle the morph + links
  // update aria-hidden for accessibility
  const links = this.querySelector(".menu-links");
  links.setAttribute("aria-hidden", !this.classList.contains("active"));
});

// Close when clicking outside (but not when clicking inside)
document.addEventListener("click", function (e) {
  if (!menuButton.contains(e.target)) {
    if (menuButton.classList.contains("active")) {
      menuButton.classList.remove("active");
      const links = menuButton.querySelector(".menu-links");
      links.setAttribute("aria-hidden", "true");
    }
  }
});

// Prevent links from triggering the outside-close while clicking them (they still work)
menuButton.querySelectorAll(".menu-links a").forEach((a) => {
  a.addEventListener("click", function (e) {
    e.stopPropagation();
    menuButton.classList.remove("active");
    menuButton.querySelector(".menu-links").setAttribute("aria-hidden", "true");
  });
});

menuButton.querySelector(".menu-label")?.addEventListener("click", (e) => {
  e.stopPropagation();
});

setTimeout(() => {
  // The splash is not rendered when returning to log in (?login=1)
  const loadingScreen = document.querySelector(".loading-screen");
  if (loadingScreen) loadingScreen.style.display = "none";
}, 3000);
document;

// Modal Functions - FIXED VERSION
const modal = document.getElementById("modal");

// FAQ fade in/out, same timing as the login modal (see .faq-modal in landing.css)
const FAQ_FADE_MS = 450;
let faqFadeTimer = null;

function openModal() {
  clearTimeout(faqFadeTimer);
  modal.style.display = "flex";
  document.body.style.overflow = "hidden";
  // Force a reflow so the browser sees opacity 0 before we fade in
  void modal.offsetWidth;
  modal.classList.add("is-open");
}

function closeModal() {
  clearTimeout(faqFadeTimer);
  modal.classList.remove("is-open");
  // Hide only after the fade-out finishes
  faqFadeTimer = setTimeout(() => {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }, FAQ_FADE_MS);
}

// Modal Functions - FIXED VERSION
const modallogin = document.getElementById("loginModal");

// The logo is a large PNG (770px, ~800 KB). Decode it now, while the modal is
// hidden, so it doesn't pop in / flicker the first time the modal opens.
document.querySelector("#loginModal .login-logo")?.decode?.().catch(() => {});

const LOGIN_FADE_MS = 450; // keep in sync with #loginModal transition in landing.css
let loginFadeTimer = null;

function openLoginModal() {
  clearTimeout(loginFadeTimer);
  modallogin.style.display = "flex";
  // Hiding the scrollbar makes the page jump sideways; pad by its width instead
  const scrollbar = window.innerWidth - document.documentElement.clientWidth;
  if (scrollbar > 0) document.body.style.paddingRight = scrollbar + "px";
  document.body.style.overflow = "hidden";
  // Force a reflow so the browser sees opacity 0 before we fade in
  void modallogin.offsetWidth;
  modallogin.classList.add("is-open");
}

function closeLoginModal() {
  clearTimeout(loginFadeTimer);
  modallogin.classList.remove("is-open");
  // Hide only after the fade-out finishes
  loginFadeTimer = setTimeout(() => {
    document.body.style.overflow = "auto";
    document.body.style.paddingRight = "";
    modallogin.style.display = "none";
    exitTermsView();
  }, LOGIN_FADE_MS);
}

// Click outside to close
modallogin.addEventListener("click", function (e) {
  if (e.target === modallogin) {
    closeLoginModal();
  }
});

const loginModalBox = modallogin.querySelector(".modal-box");
const loginTermsBox = document.querySelector(".login-terms-box");
window.addEventListener(
  "wheel",
  (e) => {
    if (modallogin.style.display !== "flex") return;
    if (!loginModalBox || !loginModalBox.contains(e.target)) return;

    e.stopImmediatePropagation();
    if (
      !modallogin.classList.contains("is-terms") ||
      !loginTermsBox ||
      !loginTermsBox.contains(e.target)
    ) {
      e.preventDefault();
    }
  },
  { capture: true, passive: false }
);

const openTermsBtn = document.getElementById("openTermsBtn");
const loginSubmitBtn = document.getElementById("loginSubmitBtn");
const agreeCheckbox = document.getElementById("agree");

function enterTermsView() {
  const btn = document.getElementById("loginSubmitBtn");
  const termsBox = document.querySelector(".login-terms-box");
  modallogin.classList.add("is-terms");
  if (termsBox) termsBox.scrollTop = 0;
  if (btn) {
    btn.type = "button";
    btn.textContent = "Agree";
  }
}

function exitTermsView() {
  const btn = document.getElementById("loginSubmitBtn");
  modallogin.classList.remove("is-terms");
  if (btn) {
    btn.type = "submit";
    btn.textContent = "Log in";
  }
}

if (openTermsBtn) {
  openTermsBtn.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    enterTermsView();
  });

  openTermsBtn.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      e.stopPropagation();
      enterTermsView();
    }
  });
}

if (loginSubmitBtn) {
  loginSubmitBtn.addEventListener("click", () => {
    if (!modallogin.classList.contains("is-terms")) return;
    if (agreeCheckbox) agreeCheckbox.checked = true;
    exitTermsView();
  });
}

// Login -> dashboard transition. The submit event only fires once the browser's
// own validation passes (required fields + terms checkbox). We play the exit
// animation (box fades out, green curtain covers the screen), then submit for
// real. The dashboard continues from the same green (see .page-enter in style.css).
const LOGIN_EXIT_MS = 500; // keep in sync with .login-curtain in landing.css
const loginForm = document.getElementById("sweeshForm");
let loginSubmitting = false;

if (loginForm) {
  loginForm.addEventListener("submit", (e) => {
    if (loginSubmitting) return; // already on its way
    e.preventDefault();
    loginSubmitting = true;

    if (loginSubmitBtn) {
      loginSubmitBtn.disabled = true;
      loginSubmitBtn.textContent = "Logging in…";
    }
    document.body.classList.add("is-logging-in");

    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    // form.submit() skips the submit event, so this handler won't run again
    setTimeout(() => loginForm.submit(), reduceMotion ? 0 : LOGIN_EXIT_MS);
  });
}

// Back button from the dashboard can restore this page from cache with the
// curtain still up; reset it
window.addEventListener("pageshow", (e) => {
  if (!e.persisted) return;
  loginSubmitting = false;
  document.body.classList.remove("is-logging-in");
  if (loginSubmitBtn) {
    loginSubmitBtn.disabled = false;
    loginSubmitBtn.textContent = "Log in";
  }
});

function redirectLogin(event) {
  // Remove or comment out this line if it exists:
  // event.preventDefault();

  // Your validation code can stay here
  // Just let the form submit to admin_login.php
  return true;
}

// After logout (/?login=1) the server renders the modal already in the page
// (class is-autoopen) at opacity 0, so the landing content shows instantly and
// only the overlay + modal fade in. Otherwise start with the modal closed.
const autoOpenLogin = modallogin.classList.contains("is-autoopen");

if (!autoOpenLogin) {
  closeLoginModal();
} else {
  // Remove ?login=1 so a refresh doesn't reopen the modal
  history.replaceState(null, "", window.location.pathname + window.location.hash);

  // Page content is shown with no transitions; turn them back on after the
  // first frames so the nav's scroll hide/show still animates later
  requestAnimationFrame(() =>
    requestAnimationFrame(() => document.body.classList.remove("is-instant"))
  );

  // Let the landing page sit on screen for a moment, then fade in the
  // overlay + modal. Also wait for the hero photo to decode (capped) so the
  // fade isn't fighting the image decode for frames.
  const LOGIN_OPEN_DELAY_MS = 900;
  const heroBg = document.querySelector("#Hero .hero-bg");
  const heroReady =
    heroBg && heroBg.decode ? heroBg.decode().catch(() => {}) : Promise.resolve();
  const wait = (ms) => new Promise((r) => setTimeout(r, ms));

  Promise.all([
    Promise.race([heroReady, wait(1500)]),
    wait(LOGIN_OPEN_DELAY_MS),
  ]).then(() => {
    requestAnimationFrame(() => requestAnimationFrame(openLoginModal));
  });
}


// Click outside to close
modal.addEventListener("click", function (e) {
  if (e.target === modal) {
    closeModal();
  }
});

// Start with modal closed
closeModal();

window.addEventListener("scroll", applyParallaxEffect);
window.addEventListener("resize", applyParallaxEffect);

function applyParallaxEffect() {
  // CRUCIAL FIX: Select all elements inside the function
  const elementsToParallax = document.querySelectorAll(
    ".immersive-image, .portrait, .landscape, .split-image-box",
  );

  const windowHeight = window.innerHeight;

  elementsToParallax.forEach((element) => {
    const rect = element.getBoundingClientRect();

    // Check if the element is near the viewport
    if (rect.top < windowHeight && rect.bottom > 0) {
      // 1. Frame Parallax Calculation (Applied to all)
      let scrollProgress =
        (windowHeight - rect.top) / (windowHeight + rect.height);
      const frameMaxMovementPx = 30;
      let frameShiftPx = (scrollProgress - 0.5) * -frameMaxMovementPx;

      // Apply frame movement
      element.style.setProperty("--frame-y", `translateY(${frameShiftPx}px)`);

      // 2. Inner Image Parallax (Only for .immersive-image)
      if (element.classList.contains("immersive-image")) {
        const imageIntensity = 20;
        let imageShift = scrollProgress * imageIntensity;
        const imageTranslateY = `translateY(calc(-10% + ${imageShift}vh))`;

        element.style.setProperty("--image-y", imageTranslateY);
      } else if (
        element.classList.contains("portrait") ||
        element.classList.contains("landscape")
      ) {
        const intensity = element.classList.contains("portrait") ? 20 : 10; // vh of movement
        const shift = (scrollProgress - 0.5) * intensity;
        element.style.setProperty("--frame-y", `translateY(${shift}vh)`);
      }
    }
  });
}

applyParallaxEffect();

// Immersive Full View Scroll Scaling
function scaleImmersiveFullView() {
  const section = document.querySelector(".immersive-full-view");
  const rect = section.getBoundingClientRect();
  const windowHeight = window.innerHeight;

  // When the section starts entering view
  const start = windowHeight * 0.99;
  // When it's fully centered
  const end = windowHeight * 0.1;

  // Progress between 0 and 1
  let progress = (start - rect.top) / (start - end);
  progress = Math.min(1, Math.max(0, progress));

  section.style.transform = `scale(${0.5 + progress * 0.5})`;
  section.style.borderRadius = `${30 * (1 - progress)}px`;
}

window.addEventListener("scroll", scaleImmersiveFullView);

const target = document.querySelector(".immersive-full-view");
const text = document.querySelector(".full-view-text");

const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
        text.classList.add("show"); // fade in
      } else {
        text.classList.remove("show"); // fade out when scrolling back
      }
    });
  },
  { threshold: [0, 0.5] },
);

observer.observe(target);

function scaleTextOnScroll() {
  const section = target;
  const rect = section.getBoundingClientRect();
  const windowHeight = window.innerHeight;

  const start = windowHeight * 0.99;
  const end = windowHeight * 0.1;

  let progress = (start - rect.top) / (start - end);
  progress = Math.min(1, Math.max(0, progress));

  const minScale = window.innerWidth < 768 ? 0.8 : 0.5;
  const scale = minScale + progress * (1 - minScale);
  text.style.transform = `scale(${scale})`;
}

window.addEventListener("scroll", scaleTextOnScroll);

window.addEventListener("scroll", revealParallax);
window.addEventListener("resize", revealParallax);

function revealParallax() {
  const section = document.querySelector(".long-beige-section");
  if (!section) return;

  const rect = section.getBoundingClientRect();
  const windowHeight = window.innerHeight;

  // Trigger zone: Start when top enters bottom 10%, End when top reaches top 40%
  const triggerStart = windowHeight * 0.99;
  const triggerEnd = windowHeight * 0.05;

  // Master Scroll Progress (0.0 to 1.0)
  let scrollProgress = (triggerStart - rect.top) / (triggerStart - triggerEnd);
  scrollProgress = Math.max(0, Math.min(1, scrollProgress));

  // --- 1. SIDE IMAGES (Opposite Directions) ---
  // We calculate a raw pixel value (e.g., 200px) that goes from 200 -> 0
  const imgMaxX = 600;
  const imgMove = imgMaxX - scrollProgress * imgMaxX;
  section.style.setProperty("--img-move-x", `${imgMove}px`);

  // Helper function to map range
  const mapProgress = (start, end, value) => {
    let p = (value - start) / (end - start);
    return Math.max(0, Math.min(1, p));
  };

  // --- 3. SIDE CIRCLE (Same as before) ---
  const circleMaxY = 15;
  const circleY = circleMaxY - scrollProgress * circleMaxY;
  section.style.setProperty("--circle-move-y", `${circleY}vh`);
}

revealParallax();

/* ==========================================================================
   EXPERIENCE CAROUSEL
   desktop: endless right-to-left loop, eases to a stop on hover, hover parallax
   tablet / mobile: touch swipe
   ========================================================================== */
(function () {
  const section = document.getElementById("Experience");
  const carousel = document.getElementById("expCarousel");
  if (!section || !carousel) return;

  const heading = section.querySelector(".top-text");
  const originals = Array.from(carousel.querySelectorAll(".exp-card"));
  const N = originals.length; // number of images (5-9 works)
  const data = originals.map((c) => {
    const img = c.querySelector("img");
    return { src: img.getAttribute("src"), alt: img.getAttribute("alt") || "" };
  });

  const TOTAL = 9; // card elements on the loop (7 visible + 2 hidden)
  const HALF = TOTAL / 2; // cards wrap around at +-4.5 slots, fully invisible

  const SECONDS_PER_CARD = 3; // loop speed
  const STOP_TAU = 180; // ms: how softly the loop stops / restarts on hover
  const HOVER_DIV = 3; // parallax strength (higher = softer), same maths as your reference
  const INTRO_MS = 2000; // entry ripple length

  const mqDesktop = window.matchMedia("(min-width: 0px)");
  const mqReduced = window.matchMedia("(prefers-reduced-motion: reduce)");

  let cards = originals.slice();
  let mode = null; // 'desktop' | 'swipe' | 'off'
  let offset = Math.floor(N / 2); // loop position, in cards
  let speed = 1; // 0 (stopped) .. 1 (running)
  let visible = false;
  let raf = 0,
    lastT = 0,
    lastRendered = null;
  let pointer = null; // mouse position while over the carousel
  let hovered = null;
  let touching = false;                    // finger on the carousel pauses the loop
  let introPlayed = false;
  let cw = 0,
    ch = 0; // card size in px
  let active = Math.floor(N / 2); // swipe mode: centred card

  const mod = (n, m) => ((n % m) + m) % m;
  const BLUR = [
    [0, 0],
    [1, 0.5],
    [2, 1],
    [3, 1.6],
    [4, 3],
    [5, 3.5],
  ];
  function blurAt(a) {
    for (let i = 1; i < BLUR.length; i++) {
      if (a <= BLUR[i][0]) {
        const [a0, b0] = BLUR[i - 1],
          [a1, b1] = BLUR[i];
        return b0 + ((b1 - b0) * (a - a0)) / (a1 - a0);
      }
    }
    return BLUR[BLUR.length - 1][1];
  }

  /* ---------- layout: place the carousel under the heading ---------- */
  function measure() {
    try {
      let top = Math.round(window.innerHeight * 0.34);
      if (heading) {
        const range = document.createRange();
        range.selectNodeContents(heading);
        const hr = heading.getBoundingClientRect();
        const scale = heading.offsetHeight
          ? hr.height / heading.offsetHeight
          : 1;
        const textBottom =
          (range.getBoundingClientRect().bottom - hr.top) / (scale || 1);
        top = Math.round(
          heading.offsetTop +
            textBottom +
            Math.max(24, window.innerHeight * 0.04),
        );
      }
      const h = Math.max(260, Math.min(window.innerHeight - top - 16, 560));
      section.style.setProperty("--exp-top", top + "px");
      section.style.setProperty("--exp-h", h + "px");
    } catch (err) {
      console.error("[carousel] measure", err);
    }
    readSize();
    render(true);
  }

  function readSize() {
    cw = cards[0].offsetWidth;
    ch = cards[0].offsetHeight;
  }

  /* ---------- desktop: draw every card from its continuous position ---------- */
  function render(force) {
    if (mode !== "desktop" || !cw) return;
    if (!force && offset === lastRendered) return;
    lastRendered = offset;
    const box = carousel.getBoundingClientRect();
    cards.forEach((c, e) => {
      const j = e + TOTAL * Math.ceil((offset - HALF - e) / TOTAL); // which card of the endless strip this element shows
      if (c._j !== j) {
        // wrapped round (invisible): load its next image
        c._j = j;
        const item = data[mod(j, N)];
        const img = c.querySelector("img");
        if (img.getAttribute("src") !== item.src)
          img.setAttribute("src", item.src);
        img.setAttribute("alt", item.alt);
      }
      const p = j - offset;
      const a = Math.abs(p);
      const s = Math.max(0.5, 1 - 0.186 * a);
      const o = Math.min(1, Math.max(0, 4 - a)); // fades out only in the hidden zone
      const x = (1.01 - 0.07 * a) * p * cw; // side-to-side at center, wider gap as cards shrink                               // ~20% overlap between neighbours
      const b = blurAt(a);
      c._p = p;
      c._x = x;
      c._s = s;
      c._o = o;
      c.style.transform =
        "translate(-50%, -50%) translateX(" +
        x.toFixed(2) +
        "px) scale(" +
        s.toFixed(4) +
        ")";
      c.style.opacity = o.toFixed(3);
      c.style.filter = b > 0.05 ? "blur(" + b.toFixed(2) + "px)" : "none";
      c.style.zIndex = Math.round((10 - a) * 100); // continuous: closer to the centre = on top
      c.style.pointerEvents = o < 0.2 ? "none" : "";
    });
  }

  /* ---------- desktop: hover = soft stop + parallax ---------- */
  function updateHover() {
    const box = carousel.getBoundingClientRect();
    if (
      pointer &&
      (pointer.x < box.left ||
        pointer.x > box.right ||
        pointer.y < box.top ||
        pointer.y > box.bottom)
    )
      pointer = null;
    const cx0 = box.left + box.width / 2,
      cy0 = box.top + box.height / 2;
    let hit = null;
    if (pointer && !mqReduced.matches) {
      cards.forEach((c) => {
        if (c._o < 0.5) return;
        const w = cw * c._s,
          h = ch * c._s; // stationary geometry: ignores the parallax shift
        const l = cx0 + c._x - w / 2,
          t = cy0 - h / 2;
        if (
          pointer.x >= l &&
          pointer.x <= l + w &&
          pointer.y >= t &&
          pointer.y <= t + h
        ) {
          if (!hit || Math.abs(c._p) < Math.abs(hit._p)) hit = c; // top-most card under the cursor
        }
      });
    }
    hovered = hit;
    cards.forEach((c) => {
      let tx = 0,
        ty = 0;
      if (c === hit) {
        // cursor right -> card left, cursor down -> card up
        tx = -(pointer.x - (cx0 + c._x)) / HOVER_DIV;
        ty = -(pointer.y - cy0) / HOVER_DIV;
      }
      const key = tx.toFixed(1) + "," + ty.toFixed(1);
      if (c._tr !== key) {
        c._tr = key;
        c.style.translate = tx.toFixed(1) + "px " + ty.toFixed(1) + "px";
      }
    });
  }

  /* ---------- the loop ---------- */
  function frame(t) {
    raf = 0;
    if (mode !== "desktop" || !visible || mqReduced.matches) return;
    const dt = Math.min(50, t - lastT || 16);
    lastT = t;
    updateHover();
    const target = (hovered || touching) ? 0 : 1;
    speed += (target - speed) * (1 - Math.exp(-dt / STOP_TAU));
    if (speed < 0.001) speed = 0;
    offset += (dt / (SECONDS_PER_CARD * 1000)) * speed;
    render(false);
    raf = requestAnimationFrame(frame);
  }

  function startLoop() {
    if (raf || mode !== "desktop" || !visible || mqReduced.matches) return;
    lastT = performance.now();
    raf = requestAnimationFrame(frame);
  }

  carousel.addEventListener("pointermove", (e) => {
    if (e.pointerType !== "mouse") return;
    pointer = { x: e.clientX, y: e.clientY };
  });
    carousel.addEventListener('pointerdown', (e) => { if (e.pointerType !== 'mouse') touching = true; });
  ['pointerup', 'pointercancel'].forEach(t => window.addEventListener(t, () => { touching = false; }));

  /* ---------- entry ripple: once, while the section scrolls into view ---------- */
  function playIntro() {
    if (introPlayed || mqReduced.matches) return;
    introPlayed = true;
    cards.forEach((c, i) => {
      const d =
        mode === "desktop"
          ? Math.min(3, Math.round(Math.abs(c._p || 0)))
          : Math.abs(i - active);
      c.style.setProperty("--d", d);
    });
    carousel.classList.add("is-intro");
    setTimeout(() => {
      carousel.classList.remove("is-intro");
      cards.forEach((c) => c.style.removeProperty("--d"));
    }, INTRO_MS);
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((en) => {
        visible = en.isIntersecting;
        if (visible) startLoop();
        if (en.isIntersecting && en.intersectionRatio >= 0.2) playIntro();
      });
    },
    { threshold: [0, 0.2], rootMargin: "100px 0px" },
  );
  io.observe(carousel);

  /* ---------- swipe mode (tablet / mobile) ---------- */
  let swipeTick = false;

  function updateActive() {
    swipeTick = false;
    const box = carousel.getBoundingClientRect();
    const mid = box.left + box.width / 2;
    let best = 0,
      bestD = Infinity;
    originals.forEach((c, i) => {
      const r = c.getBoundingClientRect();
      const d = Math.abs(r.left + r.width / 2 - mid);
      if (d < bestD) {
        bestD = d;
        best = i;
      }
    });
    originals.forEach((c, i) => c.classList.toggle("is-active", i === best));
    active = best;
  }

  carousel.addEventListener(
    "scroll",
    () => {
      if (mode !== "swipe" || swipeTick) return;
      swipeTick = true;
      requestAnimationFrame(updateActive);
    },
    { passive: true },
  );

  /* ---------- switch between desktop / swipe ---------- */
  function setMode() {
    const next = mqDesktop.matches ? "desktop" : "swipe";
    if (next === mode) return;
    mode = next;

    cards.slice(N).forEach((c) => c.remove());
    cards = originals.slice();
    originals.forEach((c, i) => {
      c.removeAttribute("style");
      c.removeAttribute("aria-hidden");
      c.classList.remove("is-active");
      c._j = null;
      c._tr = null;
      c.querySelector("img").setAttribute("src", data[i].src);
      c.querySelector("img").setAttribute("alt", data[i].alt);
    });
    pointer = null;
    hovered = null;
    speed = 1;
    lastRendered = null;

    if (mode === "desktop") {
      offset = Math.floor(N / 2);
      for (let k = N; k < TOTAL; k++) {
        const clone = originals[k % N].cloneNode(true);
        clone.classList.add("exp-clone");
        clone.setAttribute("aria-hidden", "true");
        carousel.appendChild(clone);
        cards.push(clone);
      }
      readSize();
      render(true);
      startLoop();
    } else {
      active = Math.floor(N / 2);
      readSize();
      requestAnimationFrame(() => {
        const c = originals[active];
        carousel.scrollLeft =
          c.offsetLeft - (carousel.clientWidth - c.offsetWidth) / 2;
        updateActive();
      });
    }
  }

  function init() {
    try {
      setMode();
      measure();
    } catch (err) {
      mode = "off";
      console.error("[carousel] setup failed", err);
    }
  }

  mqDesktop.addEventListener("change", () => {
    try {
      setMode();
      measure();
    } catch (err) {
      mode = "off";
      console.error(err);
    }
  });
  mqReduced.addEventListener("change", () => {
    render(true);
    startLoop();
  });
  window.addEventListener("resize", measure);
  if (document.fonts && document.fonts.ready)
    document.fonts.ready.then(measure);
  window.addEventListener("load", measure);
  init();
})();

document.addEventListener("DOMContentLoaded", () => {
  const ibContainer = document.querySelector(".ib-list-container");
  const ibRows = document.querySelectorAll(".ib-row");
  const ibFollower = document.getElementById("cursorFollower");
  const ibFollowerImg = document.getElementById("followerImg");

  if (!ibContainer || !ibFollower || !ibFollowerImg) return;

  let targetX = 0,
    targetY = 0,
    currentX = 0,
    currentY = 0;
  const ease = 0.11;

  function smoothFollow() {
    currentX += (targetX - currentX) * ease;
    currentY += (targetY - currentY) * ease;
    ibFollower.style.left = `${currentX}px`;
    ibFollower.style.top = `${currentY}px`;
    requestAnimationFrame(smoothFollow);
  }
  requestAnimationFrame(smoothFollow);

  ibContainer.addEventListener("mousemove", (e) => {
    targetX = e.clientX - 150;
    targetY = e.clientY - 200;
  });

  ibRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      const newSrc = this.getAttribute("data-img");
      if (!newSrc) return;
      ibFollowerImg.style.opacity = "0";
      setTimeout(() => {
        ibFollowerImg.src = newSrc;
        ibFollowerImg.style.opacity = "1";
      }, 150);
      ibFollower.classList.add("is-visible");
    });
  });

  ibContainer.addEventListener("mouseleave", () => {
    ibFollower.classList.remove("is-visible");
  });
});

// ==========================================================================
// FAQ PANEL
// ==========================================================================
const faqToggleBtn = document.querySelector(".access-btn");
const faqScroll = document.querySelector(".faq-scroll");
const faqItems = document.querySelectorAll(".faq-item");
const faqNavEl = document.querySelector(".nav");
const faqMenuWrapper = document.querySelector(".menu-btn-wrapper");

function isFaqOpen() {
  // Class, not display: display stays "flex" during the fade-out
  return modal.classList.contains("is-open");
}

// Panel starts just below the nav (or the menu circle, whichever is lower)
function setFaqNavOffset() {
  const menuBottom = menuButton.offsetTop + faqMenuWrapper.offsetHeight;
  const offset = Math.max(faqNavEl.offsetHeight, menuBottom) + 0;
  modal.style.setProperty("--faq-nav-h", offset + "px");
}

function setFaqItem(item, open) {
  item.classList.toggle("is-open", open);
  item.querySelector(".faq-question").setAttribute("aria-expanded", open);
}

// Runs whenever the FAQ is closed, however it was closed
function faqReset() {
  faqToggleBtn.setAttribute("aria-expanded", "false");
  // Wait for the fade-out before collapsing answers / unlocking scroll,
  // so the panel doesn't change while it is fading
  setTimeout(() => {
    if (isFaqOpen()) return; // reopened during the fade
    document.documentElement.style.overflow = "";
    faqItems.forEach((item) => setFaqItem(item, false));
  }, FAQ_FADE_MS);
}

function toggleFaq() {
  if (isFaqOpen()) {
    closeModal();
    faqReset();
  } else {
    setFaqNavOffset();
    openModal();
    document.documentElement.style.overflow = "hidden";
    faqScroll.scrollTop = 0;
    faqToggleBtn.setAttribute("aria-expanded", "true");
    faqNavEl.classList.add("scroll-show");
    faqNavEl.classList.remove("scroll-hide");
  }
}

// Keep an opened question visible inside the panel (only the panel scrolls)
function faqKeepInView(item) {
  const r = item.getBoundingClientRect();
  const s = faqScroll.getBoundingClientRect();
  if (r.top < s.top) {
    faqScroll.scrollBy({ top: r.top - s.top - 16 });
  } else if (r.bottom > s.bottom) {
    faqScroll.scrollBy({
      top: Math.min(r.bottom - s.bottom + 16, r.top - s.top - 16),
    });
  }
}

// Accordion: one open at a time
faqItems.forEach((item) => {
  item.querySelector(".faq-question").addEventListener("click", () => {
    const willOpen = !item.classList.contains("is-open");
    faqItems.forEach((other) => setFaqItem(other, false));
    if (willOpen) {
      setFaqItem(item, true);
      setTimeout(() => faqKeepInView(item), 450);
    }
  });
});

// Let the FAQ content scroll natively; keep the page behind it still
window.addEventListener(
  "wheel",
  function (e) {
    if (!isFaqOpen()) return;
    e.stopPropagation();
    if (!faqScroll.contains(e.target)) e.preventDefault();
  },
  { capture: true, passive: false },
);

// Clicking the backdrop closes via the existing handler; sync our state
modal.addEventListener("click", function (e) {
  if (e.target === modal && !isFaqOpen()) faqReset();
});

// Click anywhere outside the FAQ panel closes it. Needed on top of the backdrop
// handler because the nav sits above the overlay, so clicks there never reach it.
document.addEventListener("click", function (e) {
  if (!isFaqOpen()) return;
  if (e.target.closest(".faq-panel")) return;
  // Buttons that open/toggle the FAQ already handle their own click
  if (e.target.closest('.access-btn, .footer-faq-btn, [onclick*="toggleFaq"]')) return;
  // Menu button has its own open/close behaviour; menu links close the FAQ already
  if (menuButton.contains(e.target)) return;
  toggleFaq();
});

document.addEventListener("keydown", function (e) {
  if (e.key === "Escape" && isFaqOpen()) {
    toggleFaq();
    faqToggleBtn.focus();
  }
});

// Menu links close the FAQ, then navigate as usual
menuButton.querySelectorAll(".menu-links a").forEach((a) => {
  a.addEventListener("click", () => {
    if (isFaqOpen()) toggleFaq();
  });
});

window.addEventListener("resize", () => {
  if (isFaqOpen()) setFaqNavOffset();
});

document.querySelector(".logo-link")?.addEventListener("click", () => {
  if (isFaqOpen()) toggleFaq();
});

// MAO Showcase reveal on enter/exit
const maoShowcase = document.getElementById("maoShowcase");
if (maoShowcase) {
  const maoObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        maoShowcase.classList.toggle("is-visible", entry.isIntersecting);
      });
    },
    { threshold: 0.2 },
  );
  maoObserver.observe(maoShowcase);
}

const ibHeader = document.querySelector(".ib-header");
const servicesTopBg = document.getElementById("servicesTopBg");

if (ibHeader && servicesTopBg) {
  const ibHeaderObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          servicesTopBg.classList.remove("is-passing");
          void servicesTopBg.offsetWidth;
          servicesTopBg.classList.add("is-passing");
        } else {
          servicesTopBg.classList.remove("is-passing");
        }
      });
    },
    { threshold: 0.3 },
  );
  ibHeaderObserver.observe(ibHeader);

  servicesTopBg.addEventListener("animationend", () => {
    servicesTopBg.classList.remove("is-passing");
  });

  ibHeader.addEventListener("mouseenter", () => {
    servicesTopBg.classList.add("is-hovered");
  });

  ibHeader.addEventListener("mouseleave", () => {
    servicesTopBg.classList.remove("is-hovered");
  });
}

// Transition .long-beige-section to primary when .part-1 reaches the top of the viewport
const beigeSection = document.querySelector(".long-beige-section");
const splitPart1 = document.querySelector(".split-section.part-1");

if (beigeSection && splitPart1) {
  function updateBeigeTheme() {
    const rect = splitPart1.getBoundingClientRect();
    beigeSection.classList.toggle("is-dark", rect.top <= 40);
  }
  window.addEventListener("scroll", updateBeigeTheme, { passive: true });
  updateBeigeTheme();
}

// ==========================================================================
// RUBBERBAND PULL-TO-LOOP (TRUE 1:1 PULL + SMOOTH NO-BOUNCE RECOIL)
// ==========================================================================
const heroLoopClone = document.getElementById("heroLoopClone");

if (heroLoopClone) {
  let state = "idle"; // "idle" | "pulling" | "bouncing" | "gliding"
  let animRaf = null;
  let releaseTimer = null;
  let pullDist = 0;
  let lastDelta = 0;
  let peakDelta = 0;
  let decayStreak = 0;
  let inertiaLocked = false;
  let lockUntil = 0;

  // TWEAKABLE SETTINGS
  const INITIAL_DRAG = 0.35; // Starting pull responsiveness (1.0 = full speed, lower = heavier)
  const MAX_DRAG = 0.08; // Heavy stiffness near breaking point
  const BREAK_RATIO = 0.32; // Pull 32% of screen height to trigger loop
  const RELEASE_MS = 45; // Delay before recoil if wheel stops abruptly (mouse)
  const RECOIL_SPEED = 0.22; // Recoil ease speed back to footer (higher = faster)
  const GLIDE_SPEED = 0.14; // Final glide speed into hero

  function getFooterLimit() {
    return heroLoopClone.offsetTop - window.innerHeight;
  }

  function springBackToFooter() {
    if (state === "bouncing" || state === "gliding") return;
    cancelAnimationFrame(animRaf);
    clearTimeout(releaseTimer);
    state = "bouncing";

    let currentY = window.scrollY;

    function step() {
      const limit = getFooterLimit();
      const dist = limit - currentY;

      if (Math.abs(dist) < 1) {
        window.scrollTo({ top: limit, behavior: "instant" });
        state = "idle";
        pullDist = 0;
        peakDelta = 0;
        decayStreak = 0;
        inertiaLocked = false;
      } else {
        currentY += dist * RECOIL_SPEED;
        window.scrollTo({ top: currentY, behavior: "instant" });
        animRaf = requestAnimationFrame(step);
      }
    }
    animRaf = requestAnimationFrame(step);
  }

  window.cancelFooterLoop = () => {
    cancelAnimationFrame(animRaf);
    clearTimeout(releaseTimer);
    state = "idle";
    pullDist = 0;
    peakDelta = 0;
    decayStreak = 0;
    inertiaLocked = false;
  };

  const navEl = document.querySelector(".nav");
  if (navEl) {
    navEl.classList.add("scroll-show");
    navEl.classList.remove("scroll-hide");
  }

  function glideToHero() {
    cancelAnimationFrame(animRaf);
    clearTimeout(releaseTimer);
    state = "gliding";
    window.isLoopGliding = true;
    scrollVelocity = 0;
    isScrolling = false;

    const navEl = document.querySelector(".nav");
    if (navEl) {
      navEl.style.animation = "none";
      navEl.classList.add("scroll-show");
      navEl.classList.remove("scroll-hide");
    }

    let currentY = window.scrollY;

    function step() {
      const maxScroll =
        document.documentElement.scrollHeight - window.innerHeight;
      const target = Math.min(heroLoopClone.offsetTop, maxScroll);
      const dist = target - currentY;

      if (Math.abs(dist) < 0.8) {
        const realBg = document.querySelector(".hero > .hero-bg");
        if (realBg) {
          realBg.style.transition = "none";
          realBg.style.transform = "translateY(0px)";
        }

        state = "idle";
        window.isLoopGliding = false;
        pullDist = 0;
        peakDelta = 0;
        decayStreak = 0;
        inertiaLocked = false;
        lockUntil = performance.now() + 500;
        window.scrollTo({ top: 0, behavior: "instant" });

        if (realBg) {
          requestAnimationFrame(() => {
            realBg.style.transition = "";
          });
        }
      } else {
        currentY += dist * GLIDE_SPEED;
        window.scrollTo({ top: currentY, behavior: "instant" });
        animRaf = requestAnimationFrame(step);
      }
    }
    animRaf = requestAnimationFrame(step);
  }

  window.addEventListener(
    "wheel",
    (e) => {
      if (typeof isFaqOpen === "function" && isFaqOpen()) return;

      if (state === "gliding" || performance.now() < lockUntil) {
        e.preventDefault();
        e.stopImmediatePropagation();
        scrollVelocity = 0;
        return;
      }

      const limit = getFooterLimit();
      const over = window.scrollY - limit;

      if (
        (over >= -2 && e.deltaY > 0) ||
        over > 1 ||
        state === "pulling" ||
        state === "bouncing"
      ) {
        // Scrolling back up at resting footer exits normally
        if (over <= 1 && e.deltaY < 0 && state !== "bouncing") {
          state = "idle";
          pullDist = 0;
          lastDelta = 0;
          peakDelta = 0;
          decayStreak = 0;
          inertiaLocked = false;
          return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();
        scrollVelocity = 0;
        isScrolling = false;

        if (e.deltaY > 0) {
          // A fresh intentional push unlocks inertia
          const isNewPush = e.deltaY > lastDelta + 3 || e.deltaY >= 40;
          if (inertiaLocked && !isNewPush) {
            lastDelta = e.deltaY;
            return;
          }
          if (isNewPush) {
            inertiaLocked = false;
            decayStreak = 0;
          }

          if (e.deltaY > peakDelta) peakDelta = e.deltaY;

          // Detect decaying trackpad inertia right after hand lifts
          if (
            state === "pulling" &&
            (e.deltaY < lastDelta || (decayStreak > 0 && e.deltaY <= 6))
          ) {
            decayStreak++;
          } else {
            decayStreak = 0;
          }
          lastDelta = e.deltaY;

          if (decayStreak >= 2 && e.deltaY < peakDelta * 0.85) {
            inertiaLocked = true;
            springBackToFooter();
            return;
          }
        } else {
          lastDelta = e.deltaY;
          decayStreak = 0;
          inertiaLocked = false;
        }

        if (state !== "pulling") {
          cancelAnimationFrame(animRaf);
          state = "pulling";
          pullDist = Math.max(0, over);
          peakDelta = Math.max(0, e.deltaY);
        }

        const breakPx = window.innerHeight * BREAK_RATIO;
        if (e.deltaY > 0) {
          const tension = Math.min(1, pullDist / breakPx);
          const drag = INITIAL_DRAG - tension * (INITIAL_DRAG - MAX_DRAG);
          pullDist += e.deltaY * drag;
        } else {
          pullDist = Math.max(0, pullDist + e.deltaY * 0.5);
        }

        // Direct 1:1 instant scroll (zero animation lag)
        window.scrollTo({ top: limit + pullDist, behavior: "instant" });

        if (pullDist >= breakPx) {
          glideToHero();
          return;
        }

        clearTimeout(releaseTimer);
        releaseTimer = setTimeout(() => {
          if (state === "pulling") {
            springBackToFooter();
          }
        }, RELEASE_MS);
      }
    },
    { capture: true, passive: false },
  );

  // Bumper if page momentum arrives at the footer fast
  window.addEventListener(
    "scroll",
    () => {
      if (state !== "idle") return;
      const limit = getFooterLimit();
      if (window.scrollY > limit + 2) {
        scrollVelocity = 0;
        isScrolling = false;
        springBackToFooter();
      }
    },
    { passive: true },
  );

  // ---- TOUCH (phones / tablets): same pull, stretch and glide ----
  const TOUCH_DRAG = 0.6; // starting finger-to-page ratio (lower = heavier)
  let touchY = null;
  let touchPulling = false;

  window.addEventListener(
    "touchstart",
    (e) => {
      if (typeof isFaqOpen === "function" && isFaqOpen()) return;
      if (state === "gliding") return;
      if (state === "bouncing") {
        cancelAnimationFrame(animRaf);
        state = "idle";
      }
      clearTimeout(releaseTimer);
      touchY = e.touches[0].clientY;
      touchPulling = false;
    },
    { passive: true },
  );

  window.addEventListener(
    "touchmove",
    (e) => {
      if (touchY === null || state === "gliding") return;
      if (typeof isFaqOpen === "function" && isFaqOpen()) return;

      const y = e.touches[0].clientY;
      const dy = touchY - y; // > 0 = finger moving up (scrolling down)
      touchY = y;

      const limit = getFooterLimit();
      const over = window.scrollY - limit;

      if (!touchPulling) {
        if (over < -2 || dy <= 0) return; // not at the footer end, or going up
        touchPulling = true;
        state = "pulling";
        pullDist = Math.max(0, over);
      }

      e.preventDefault();
      const breakPx = window.innerHeight * BREAK_RATIO;

      if (dy > 0) {
        const tension = Math.min(1, pullDist / breakPx);
        pullDist += dy * (TOUCH_DRAG - tension * (TOUCH_DRAG - MAX_DRAG));
      } else {
        pullDist = Math.max(0, pullDist + dy * 0.7);
      }

      window.scrollTo({ top: limit + pullDist, behavior: "instant" });

      if (pullDist >= breakPx) {
        touchY = null;
        touchPulling = false;
        glideToHero();
      }
    },
    { passive: false },
  );

  ["touchend", "touchcancel"].forEach((t) =>
    window.addEventListener(
      t,
      () => {
        touchY = null;
        if (touchPulling && state === "pulling") {
          touchPulling = false;
          springBackToFooter();
        }
      },
      { passive: true },
    ),
  );

  const realHero = document.getElementById("Hero");
  if (realHero) {
    const syncCloneHeight = () => {
      heroLoopClone.style.height = realHero.offsetHeight + "px";
    };
    syncCloneHeight();
    window.addEventListener("resize", syncCloneHeight);
  }
}
