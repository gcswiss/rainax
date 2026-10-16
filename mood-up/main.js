/* MoodUp — Interaktion: Menü, Live-Map, Filter, Tabs, Scroll-Effekte */
(function () {
  "use strict";

  document.documentElement.classList.add("js");
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;

  /* ---------- Kopfzeile & Menü ---------- */

  var header = document.querySelector("[data-header]");
  function onScroll() { header.classList.toggle("is-scrolled", window.scrollY > 12); }
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  var toggle = document.querySelector(".menu-toggle");
  var menu = document.getElementById("menu");
  function setMenu(open) {
    toggle.setAttribute("aria-expanded", String(open));
    toggle.setAttribute("aria-label", open ? "Menü schliessen" : "Menü öffnen");
    menu.hidden = !open;
    header.classList.toggle("is-scrolled", open || window.scrollY > 12);
  }
  toggle.addEventListener("click", function () { setMenu(menu.hidden); });
  menu.addEventListener("click", function (e) { if (e.target.closest("a")) setMenu(false); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape" && !menu.hidden) { setMenu(false); toggle.focus(); } });

  /* ---------- Live-Map (Beispieldaten, keine echten Lokale) ---------- */

  var ICONS = {
    Bar: '<svg viewBox="0 0 24 24"><path d="M5 4h14l-7 8z"/><path d="M12 12v7M8 20h8"/></svg>',
    Club: '<svg viewBox="0 0 24 24"><path d="M9 18V6l11-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="17.5" cy="16" r="2.5"/></svg>',
    Event: '<svg viewBox="0 0 24 24"><path d="M12 3l2.6 5.6 6 .7-4.5 4.1 1.2 6L12 16.4 6.7 19.4l1.2-6L3.4 9.3l6-.7z"/></svg>'
  };
  var MOODS = {
    chillig: { label: "Chillig", color: "var(--teal)" },
    gesellig: { label: "Gesellig", color: "var(--amber)" },
    party: { label: "Party", color: "var(--pink)" }
  };
  var SPOTS = [
    { type: "Bar", name: "Rooftop-Bar", music: "House", age: "25+", mood: "chillig", x: 26, y: 38 },
    { type: "Club", name: "Techno-Club", music: "Techno", age: "18+", mood: "party", x: 68, y: 30 },
    { type: "Event", name: "Live-Konzert", music: "Rock", age: "16+", mood: "gesellig", x: 58, y: 70 },
    { type: "Bar", name: "Cocktail-Bar", music: "Latin", age: "20+", mood: "gesellig", x: 34, y: 62 },
    { type: "Club", name: "Hip-Hop-Night", music: "Hip-Hop", age: "18+", mood: "party", x: 80, y: 54 },
    { type: "Bar", name: "Weinbar", music: "Jazz", age: "25+", mood: "chillig", x: 16, y: 78 },
    { type: "Event", name: "Afterwork", music: "Charts", age: "21+", mood: "gesellig", x: 46, y: 44 }
  ];

  var STREETS =
    '<svg class="map__svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid slice" aria-hidden="true">' +
    '<rect class="block" x="6" y="8" width="22" height="14" rx="2"/><rect class="block" x="38" y="12" width="18" height="18" rx="2"/>' +
    '<rect class="block" x="70" y="62" width="22" height="20" rx="2"/><rect class="block" x="8" y="46" width="16" height="16" rx="2"/>' +
    '<rect class="park" x="62" y="8" width="30" height="14" rx="3"/><rect class="park" x="24" y="84" width="22" height="12" rx="3"/>' +
    '<path class="river" stroke-width="6" d="M-5 92 C 20 80, 30 96, 55 86 S 90 74, 110 80"/>' +
    '<path class="road road--main" stroke-width="2.4" d="M-5 34 L 105 26"/>' +
    '<path class="road road--main" stroke-width="2.4" d="M40 -5 L 52 105"/>' +
    '<path class="road" stroke-width="1.4" d="M-5 56 C 30 52, 60 60, 105 48"/>' +
    '<path class="road" stroke-width="1.4" d="M18 -5 L 30 105"/>' +
    '<path class="road" stroke-width="1.4" d="M74 -5 L 66 105"/>' +
    '<path class="road" stroke-width="1" d="M-5 16 L 105 12"/>' +
    '<path class="road" stroke-width="1" d="M-5 74 L 105 70"/>' +
    '<path class="road" stroke-width="1" d="M88 -5 L 92 105"/>' +
    "</svg>";

  function buildMap(el) {
    el.innerHTML = STREETS;
    var pins = SPOTS.map(function (s, i) {
      var pin = document.createElement("span");
      pin.className = "pin";
      pin.dataset.mood = s.mood;
      pin.style.setProperty("--x", s.x + "%");
      pin.style.setProperty("--y", s.y + "%");
      pin.style.setProperty("--c", MOODS[s.mood].color);
      pin.style.setProperty("--delay", (i * 0.37).toFixed(2) + "s");
      pin.innerHTML = ICONS[s.type];
      el.appendChild(pin);
      return pin;
    });
    var me = document.createElement("span");
    me.className = "me";
    el.appendChild(me);
    return pins;
  }

  /* Hero: Karte mit wechselnder Detailkarte */
  var heroMap = document.querySelector('[data-map="hero"]');
  if (heroMap) {
    var heroPins = buildMap(heroMap);
    var sheet = document.querySelector("[data-sheet]");
    var sName = sheet.querySelector("[data-sheet-name]");
    var sMeta = sheet.querySelector("[data-sheet-meta]");
    var sMood = sheet.querySelector("[data-sheet-mood]");
    var sIcon = sheet.querySelector("[data-sheet-icon]");
    var current = 0;

    function show(i) {
      var s = SPOTS[i];
      heroPins.forEach(function (p, j) { p.classList.toggle("is-active", j === i); });
      sName.textContent = s.name;
      sMeta.textContent = s.type + " · " + s.music + " · " + s.age;
      sMood.textContent = MOODS[s.mood].label;
      sIcon.innerHTML = ICONS[s.type];
      sheet.style.setProperty("--c", MOODS[s.mood].color);
      sheet.classList.remove("is-swapping");
      void sheet.offsetWidth;
      sheet.classList.add("is-swapping");
    }
    show(0);

    if (!reduceMotion) {
      var timer = null;
      var start = function () { if (!timer) timer = setInterval(function () { current = (current + 1) % SPOTS.length; show(current); }, 2800); };
      var stop = function () { clearInterval(timer); timer = null; };
      new IntersectionObserver(function (entries) {
        entries[0].isIntersecting ? start() : stop();
      }).observe(heroMap);
    }
  }

  /* Funktionen: Filter nach Stimmung */
  var demoMap = document.querySelector('[data-map="demo"]');
  if (demoMap) {
    var demoPins = buildMap(demoMap);
    var buttons = document.querySelectorAll(".mood-btn");
    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        var mood = btn.dataset.mood;
        buttons.forEach(function (b) {
          var on = b === btn;
          b.classList.toggle("is-on", on);
          b.setAttribute("aria-pressed", String(on));
        });
        demoPins.forEach(function (p) {
          p.classList.toggle("is-off", mood !== "alle" && p.dataset.mood !== mood);
        });
      });
    });
  }

  /* ---------- Tabs Gäste / Betriebe ---------- */

  var tabList = document.querySelector(".tabs__list");
  if (tabList) {
    var tabs = Array.prototype.slice.call(tabList.querySelectorAll('[role="tab"]'));
    var pill = tabList.querySelector(".tabs__pill");

    function movePill(tab) {
      pill.style.width = tab.offsetWidth + "px";
      pill.style.transform = "translateX(" + tab.offsetLeft + "px)";
    }
    function select(tab) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.setAttribute("aria-selected", String(on));
        t.tabIndex = on ? 0 : -1;
        document.getElementById(t.getAttribute("aria-controls")).hidden = !on;
      });
      movePill(tab);
    }
    tabs.forEach(function (t, i) {
      t.addEventListener("click", function () { select(t); });
      t.addEventListener("keydown", function (e) {
        if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
        var next = tabs[(i + (e.key === "ArrowRight" ? 1 : tabs.length - 1)) % tabs.length];
        select(next);
        next.focus();
      });
    });
    var sync = function () { movePill(tabList.querySelector('[aria-selected="true"]')); };
    sync();
    window.addEventListener("resize", sync);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(sync);
  }

  /* ---------- Erscheinen beim Scrollen ---------- */

  var revealEls = document.querySelectorAll(".reveal, .step");
  if (reduceMotion || !("IntersectionObserver" in window)) {
    revealEls.forEach(function (el) { el.classList.add("is-in"); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("is-in"); io.unobserve(e.target); }
      });
    }, { rootMargin: "0px 0px -8% 0px" });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Aussage: Wörter leuchten beim Lesen auf ---------- */

  var statement = document.querySelector("[data-words]");
  if (statement && !reduceMotion) {
    var wrap = function (node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var frag = document.createDocumentFragment();
          child.textContent.split(/(\s+)/).forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
            var span = document.createElement("span");
            span.className = "w";
            span.textContent = part;
            frag.appendChild(span);
          });
          node.replaceChild(frag, child);
        } else if (child.nodeType === 1) {
          wrap(child);
        }
      });
    };
    wrap(statement);
    var words = statement.querySelectorAll(".w");
    var light = function () {
      var r = statement.getBoundingClientRect();
      var vh = window.innerHeight;
      var progress = Math.min(1, Math.max(0, (vh * 0.85 - r.top) / (r.height + vh * 0.35)));
      var n = Math.round(progress * words.length);
      words.forEach(function (w, i) { w.classList.toggle("is-lit", i < n); });
    };
    light();
    window.addEventListener("scroll", light, { passive: true });
  }

  /* ---------- Maus: Phone kippt, Lichtkegel auf Kacheln ---------- */

  if (finePointer && !reduceMotion) {
    var phone = document.querySelector("[data-tilt]");
    if (phone) {
      var hero = document.querySelector(".hero");
      hero.addEventListener("mousemove", function (e) {
        var x = e.clientX / window.innerWidth - 0.5;
        var y = e.clientY / window.innerHeight - 0.5;
        phone.style.transform = "perspective(1200px) rotateY(" + (x * 14).toFixed(2) + "deg) rotateX(" + (-y * 10).toFixed(2) + "deg)";
      });
      hero.addEventListener("mouseleave", function () { phone.style.transform = ""; });
    }
    document.querySelectorAll(".tile").forEach(function (tile) {
      tile.addEventListener("mousemove", function (e) {
        var r = tile.getBoundingClientRect();
        tile.style.setProperty("--mx", e.clientX - r.left + "px");
        tile.style.setProperty("--my", e.clientY - r.top + "px");
      });
    });
  }

  /* ---------- Jahr im Footer ---------- */

  document.querySelectorAll("[data-year]").forEach(function (el) { el.textContent = new Date().getFullYear(); });
})();
