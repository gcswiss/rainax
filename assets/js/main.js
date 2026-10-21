/* RAINAX AG — Interaktionen */
(function () {
  "use strict";

  var doc = document.documentElement;
  doc.classList.add("js");

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ------------------------------------------------------------------
     Kopfzeile: durchsichtig über dem Hero, beim Scrollen mit Hintergrund
     ------------------------------------------------------------------ */
  var header = document.querySelector(".site-header");
  if (header) {
    var onScroll = function () {
      header.classList.toggle("is-scrolled", window.scrollY > 24);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  /* ------------------------------------------------------------------
     Menü auf dem Handy
     ------------------------------------------------------------------ */
  var toggle = document.querySelector(".nav-toggle");
  var menu = document.getElementById("mobile-menu");
  if (toggle && menu) {
    var setOpen = function (open) {
      doc.classList.toggle("nav-open", open);
      toggle.setAttribute("aria-expanded", String(open));
      toggle.setAttribute("aria-label", open ? "Menü schliessen" : "Menü öffnen");
      menu.inert = !open;
    };
    setOpen(false);
    toggle.addEventListener("click", function () {
      setOpen(!doc.classList.contains("nav-open"));
    });
    menu.addEventListener("click", function (e) {
      if (e.target.closest("a")) setOpen(false);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && doc.classList.contains("nav-open")) {
        setOpen(false);
        toggle.focus();
      }
    });
    window.matchMedia("(min-width: 861px)").addEventListener("change", function (e) {
      if (e.matches) setOpen(false);
    });
  }

  /* ------------------------------------------------------------------
     Sanftes Einblenden beim Scrollen
     ------------------------------------------------------------------ */
  var revealEls = document.querySelectorAll(".reveal");
  if (!reduceMotion && "IntersectionObserver" in window) {
    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          revealObserver.unobserve(entry.target);
        }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: 0.08 });
    revealEls.forEach(function (el) { revealObserver.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add("is-visible"); });
  }

  /* ------------------------------------------------------------------
     Hintergrundvideos (selbst gehostet, ohne Ton, Endlosschleife)
     <div class="video-bg"><video class="video-bg__media" data-video> <source data-src="…"> </video></div>
     Die Quellen stehen in data-src und werden erst geladen, wenn das
     Video in die Nähe des Bildschirms kommt.
     ------------------------------------------------------------------ */
  var videos = document.querySelectorAll("video[data-video]");

  function mountVideo(video) {
    if (video.dataset.mounted) return;
    video.dataset.mounted = "true";

    var stage = video.closest(".video-bg");
    var section = stage.parentElement;
    var toggleBtn = section.querySelector(".video-toggle");
    var userPaused = false;

    video.muted = true;
    video.querySelectorAll("source[data-src]").forEach(function (source) {
      source.src = source.dataset.src;
    });
    video.load();

    video.addEventListener("playing", function () {
      stage.classList.add("is-playing");
      section.classList.add("has-video");
    });

    var play = function () {
      var p = video.play();
      if (p && typeof p.catch === "function") p.catch(function () { /* Autoplay blockiert: Poster bleibt sichtbar */ });
    };
    play();

    if (toggleBtn) {
      toggleBtn.addEventListener("click", function () {
        userPaused = !video.paused;
        if (userPaused) video.pause(); else play();
        toggleBtn.classList.toggle("is-paused", userPaused);
        toggleBtn.setAttribute("aria-label", userPaused ? "Video abspielen" : "Video pausieren");
      });
    }

    // Video ausserhalb des Bildschirms anhalten (spart Akku und Daten)
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (userPaused) return;
          if (entry.isIntersecting) play(); else video.pause();
        });
      }).observe(stage);
    }
  }

  if (videos.length && !reduceMotion) {
    if ("IntersectionObserver" in window) {
      var lazyObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            mountVideo(entry.target);
            lazyObserver.unobserve(entry.target);
          }
        });
      }, { rootMargin: "300px 0px" });
      videos.forEach(function (el) { lazyObserver.observe(el); });
    } else {
      videos.forEach(mountVideo);
    }
  }

  /* ------------------------------------------------------------------
     Kontaktformular
     ------------------------------------------------------------------ */
  var form = document.getElementById("contact-form");
  if (form) {
    // ?thema=idee wählt «Ich habe eine Idee» vor
    var params = new URLSearchParams(window.location.search);
    var topic = params.get("thema");
    var select = form.querySelector("#topic");
    if (topic && select) {
      var option = select.querySelector('option[data-key="' + topic + '"]');
      if (option) select.value = option.value;
    }

    var message = form.querySelector(".form-message");
    var submit = form.querySelector('button[type="submit"]');

    var showMessage = function (text, type) {
      message.textContent = text;
      message.className = "form-message form-message--" + type;
      message.hidden = false;
    };

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      // Formular-Empfänger (z. B. Formspree, Web3Forms o. ä.) im Attribut data-endpoint eintragen.
      var endpoint = form.dataset.endpoint;
      if (!endpoint) {
        showMessage("Das Formular ist noch nicht mit einem Empfänger verbunden. Bitte schreiben Sie uns vorerst an info@rainax.ch.", "error");
        return;
      }

      submit.disabled = true;
      fetch(endpoint, {
        method: "POST",
        body: new FormData(form),
        headers: { Accept: "application/json" }
      })
        .then(function (res) {
          if (!res.ok) throw new Error(res.status);
          form.reset();
          showMessage("Danke für Ihre Nachricht. Wir melden uns in Kürze persönlich bei Ihnen.", "success");
        })
        .catch(function () {
          showMessage("Leider ist etwas schiefgelaufen. Bitte versuchen Sie es später noch einmal.", "error");
        })
        .then(function () {
          submit.disabled = false;
        });
    });
  }

  /* ------------------------------------------------------------------
     Bewegung: Scroll-Effekte, Maus-Parallaxe, Hover
     ------------------------------------------------------------------ */
  var finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  var clamp = function (v, min, max) { return Math.min(max, Math.max(min, v)); };

  // Text in einzelne Wörter zerlegen (für Auftritt und Aufleuchten)
  function splitWords(el) {
    var words = [];
    Array.prototype.slice.call(el.childNodes).forEach(function (node) {
      if (node.nodeType !== 3) return;
      var frag = document.createDocumentFragment();
      node.textContent.split(/(\s+)/).forEach(function (part) {
        if (!part) return;
        if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
        var span = document.createElement("span");
        span.className = "word";
        span.textContent = part;
        span.style.setProperty("--i", words.length);
        words.push(span);
        frag.appendChild(span);
      });
      el.replaceChild(frag, node);
    });
    return words;
  }

  if (!reduceMotion) {
    var hero = document.querySelector(".hero");
    var heroTitle = document.querySelector(".hero__title");
    var intro = document.querySelector(".intro__statement");
    var finale = document.querySelector(".finale");
    if (heroTitle) splitWords(heroTitle);
    var introWords = intro ? splitWords(intro) : [];

    // Scroll-gekoppelte Effekte
    var ticking = false;
    var updateScroll = function () {
      ticking = false;
      var vh = window.innerHeight;
      if (hero) {
        var hp = clamp(window.scrollY / hero.offsetHeight, 0, 1);
        hero.style.setProperty("--hero-progress", hp.toFixed(4));
      }
      if (introWords.length) {
        var r = intro.getBoundingClientRect();
        var ip = clamp((vh * 0.85 - r.top) / (r.height + vh * 0.3), 0, 1);
        var lit = Math.round(ip * introWords.length);
        introWords.forEach(function (w, n) { w.classList.toggle("is-lit", n < lit); });
      }
      if (finale) {
        var fr = finale.getBoundingClientRect();
        if (fr.top < vh && fr.bottom > 0) {
          finale.style.setProperty("--finale-progress", clamp((vh - fr.top) / vh, 0, 1).toFixed(4));
        }
      }
    };
    var onScrollMotion = function () {
      if (!ticking) { ticking = true; requestAnimationFrame(updateScroll); }
    };
    updateScroll();
    window.addEventListener("scroll", onScrollMotion, { passive: true });
    window.addEventListener("resize", onScrollMotion);

    // Linien zeichnen sich, sobald sie sichtbar werden
    if ("IntersectionObserver" in window) {
      var drawObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-drawn");
            drawObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.35 });
      document.querySelectorAll(".steps, .timeline").forEach(function (el) { drawObserver.observe(el); });
    }

    // Live-Map: pulsierende Signale
    document.querySelectorAll(".livemap").forEach(function (map) {
      [["30%", "38%", "0s"], ["46%", "66%", "1.1s"], ["72%", "30%", "2.2s"]].forEach(function (pt) {
        var ping = document.createElement("span");
        ping.className = "livemap__ping";
        ping.style.left = pt[0];
        ping.style.top = pt[1];
        ping.style.setProperty("--d", pt[2]);
        map.appendChild(ping);
      });
    });

    if (finePointer) {
      // Hero: Erde reagiert mit Tiefe auf die Maus (weich nachgezogen)
      if (hero) {
        var tx = 0, ty = 0, cx = 0, cy = 0, raf = null;
        var follow = function () {
          cx += (tx - cx) * 0.06;
          cy += (ty - cy) * 0.06;
          hero.style.setProperty("--px", cx.toFixed(2) + "px");
          hero.style.setProperty("--py", cy.toFixed(2) + "px");
          raf = (Math.abs(tx - cx) > 0.05 || Math.abs(ty - cy) > 0.05) ? requestAnimationFrame(follow) : null;
        };
        hero.addEventListener("pointermove", function (e) {
          tx = (e.clientX / window.innerWidth - 0.5) * -28;
          ty = (e.clientY / window.innerHeight - 0.5) * -18;
          if (!raf) raf = requestAnimationFrame(follow);
        });
        hero.addEventListener("pointerleave", function () {
          tx = 0; ty = 0;
          if (!raf) raf = requestAnimationFrame(follow);
        });
      }

      // Handy kippt in Richtung Maus
      document.querySelectorAll(".phone").forEach(function (phone) {
        var area = phone.closest("section") || phone.parentElement;
        area.addEventListener("pointermove", function (e) {
          var r = phone.getBoundingClientRect();
          var dx = clamp((e.clientX - (r.left + r.width / 2)) / (window.innerWidth / 2), -1, 1);
          var dy = clamp((e.clientY - (r.top + r.height / 2)) / (window.innerHeight / 2), -1, 1);
          phone.style.setProperty("--ry", (dx * 12).toFixed(2) + "deg");
          phone.style.setProperty("--rx", (dy * -8).toFixed(2) + "deg");
        });
        area.addEventListener("pointerleave", function () {
          phone.style.setProperty("--ry", "0deg");
          phone.style.setProperty("--rx", "0deg");
        });
      });

      // Lichtkegel und Kippen bei Kacheln
      document.querySelectorAll(".tile, .feature, .project, .cta-panel").forEach(function (card) {
        card.classList.add("spot");
        var tilt = card.classList.contains("tile");
        card.addEventListener("pointermove", function (e) {
          var r = card.getBoundingClientRect();
          var x = e.clientX - r.left, y = e.clientY - r.top;
          card.style.setProperty("--sx", x + "px");
          card.style.setProperty("--sy", y + "px");
          if (tilt) {
            card.classList.add("is-tilting");
            card.style.setProperty("--ry", ((x / r.width - 0.5) * 7).toFixed(2) + "deg");
            card.style.setProperty("--rx", ((y / r.height - 0.5) * -7).toFixed(2) + "deg");
          }
        });
        card.addEventListener("pointerleave", function () {
          if (!tilt) return;
          card.classList.remove("is-tilting");
          card.style.setProperty("--ry", "0deg");
          card.style.setProperty("--rx", "0deg");
        });
      });

      // Magnetische Buttons
      document.querySelectorAll(".btn").forEach(function (btn) {
        btn.addEventListener("pointermove", function (e) {
          var r = btn.getBoundingClientRect();
          btn.classList.add("is-magnet");
          btn.style.setProperty("--mx", clamp((e.clientX - (r.left + r.width / 2)) * 0.3, -8, 8).toFixed(1) + "px");
          btn.style.setProperty("--my", clamp((e.clientY - (r.top + r.height / 2)) * 0.4, -6, 6).toFixed(1) + "px");
        });
        btn.addEventListener("pointerleave", function () {
          btn.classList.remove("is-magnet");
          btn.style.setProperty("--mx", "0px");
          btn.style.setProperty("--my", "0px");
        });
      });
    }
  }

  /* ------------------------------------------------------------------
     Uhr im Hero (Ortszeit Wil)
     ------------------------------------------------------------------ */
  var clocks = document.querySelectorAll("[data-clock]");
  if (clocks.length && window.Intl) {
    var fmt = new Intl.DateTimeFormat("de-CH", { timeZone: "Europe/Zurich", hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: false });
    var tick = function () {
      var t = fmt.format(new Date());
      clocks.forEach(function (c) { c.textContent = t; });
    };
    tick();
    setInterval(tick, 1000);
  }

  /* ------------------------------------------------------------------
     Mono-Labels «dekodieren» sich beim Erscheinen (Terminal-Effekt)
     ------------------------------------------------------------------ */
  if (!reduceMotion && "IntersectionObserver" in window) {
    var glyphs = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/·";
    var scramble = function (el) {
      var final = el.textContent;
      var start = performance.now();
      var dur = 260 + final.length * 22;
      var step = function (now) {
        var p = Math.min(1, (now - start) / dur);
        var fixed = Math.floor(p * final.length);
        var out = "";
        for (var k = 0; k < final.length; k++) {
          var ch = final.charAt(k);
          out += (k < fixed || ch === " ") ? ch : glyphs.charAt((Math.random() * glyphs.length) | 0);
        }
        el.textContent = out;
        if (p < 1) requestAnimationFrame(step); else el.textContent = final;
      };
      requestAnimationFrame(step);
    };
    var scrambleObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        scrambleObserver.unobserve(entry.target);
        scramble(entry.target);
      });
    }, { threshold: 0.6 });
    document.querySelectorAll(".eyebrow").forEach(function (el) {
      if (el.children.length === 0 && el.textContent.trim()) {
        el.setAttribute("aria-label", el.textContent.trim());
        scrambleObserver.observe(el);
      }
    });
  }

  /* ------------------------------------------------------------------
     Partikel-Kugel: rotiert langsam, kippt zur Maus, Punkte weichen aus
     <div class="orb" data-orb></div>
     ------------------------------------------------------------------ */
  function initOrb(host) {
    var canvas = document.createElement("canvas");
    host.insertBefore(canvas, host.firstChild);
    var ctx = canvas.getContext("2d");
    if (!ctx) return;

    var N = 1500;
    var golden = Math.PI * (3 - Math.sqrt(5));
    var pts = [];
    for (var i = 0; i < N; i++) {
      var y = 1 - (2 * (i + 0.5)) / N;
      var r = Math.sqrt(1 - y * y);
      var phi = i * golden;
      var j = 1 + (Math.random() - 0.5) * 0.06;
      pts.push({ x: Math.cos(phi) * r * j, y: y * j, z: Math.sin(phi) * r * j, hi: Math.random() < 0.06 });
    }

    var w = 0, h = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
    var rotY = 0, rotX = -0.35, tiltX = 0, tiltY = 0, targetX = 0, targetY = 0;
    var mouse = null, running = false, visible = false;

    var resize = function () {
      w = host.clientWidth;
      h = host.clientHeight;
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      draw();
    };

    var draw = function () {
      if (!w || !h) return;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, w, h);
      ctx.globalCompositeOperation = "lighter";
      var cx = w / 2, cy = h / 2 * 0.96;
      var R = Math.min(w, h) * 0.36;
      var ay = rotY + tiltY, ax = rotX + tiltX;
      var cosY = Math.cos(ay), sinY = Math.sin(ay), cosX = Math.cos(ax), sinX = Math.sin(ax);
      for (var k = 0; k < N; k++) {
        var p = pts[k];
        var x1 = p.x * cosY - p.z * sinY;
        var z1 = p.x * sinY + p.z * cosY;
        var y1 = p.y * cosX - z1 * sinX;
        var z2 = p.y * sinX + z1 * cosX;
        var depth = (z2 + 1) / 2;
        var persp = 1 + z2 * 0.18;
        var sx = cx + x1 * R * persp;
        var sy = cy + y1 * R * persp;
        if (mouse) {
          var dx = sx - mouse.x, dy = sy - mouse.y;
          var d2 = dx * dx + dy * dy;
          if (d2 < 9000) {
            var f = (1 - d2 / 9000) * 22 * depth;
            var d = Math.sqrt(d2) || 1;
            sx += (dx / d) * f;
            sy += (dy / d) * f;
          }
        }
        var size = (0.6 + depth * 1.5);
        var a = 0.08 + depth * 0.82;
        if (p.hi) {
          ctx.fillStyle = "rgba(238,96,24," + (a * 0.9).toFixed(3) + ")";
          size += 0.6;
        } else {
          var rr = Math.round(47 + (236 - 47) * depth);
          var gg = Math.round(107 + (216 - 107) * depth);
          ctx.fillStyle = "rgba(" + rr + "," + gg + ",255," + a.toFixed(3) + ")";
        }
        ctx.fillRect(sx - size / 2, sy - size / 2, size, size);
      }
      ctx.globalCompositeOperation = "source-over";
    };

    var loop = function () {
      if (!visible) { running = false; return; }
      rotY += 0.0022;
      tiltX += (targetX - tiltX) * 0.05;
      tiltY += (targetY - tiltY) * 0.05;
      draw();
      requestAnimationFrame(loop);
    };

    if ("ResizeObserver" in window) new ResizeObserver(resize).observe(host); else window.addEventListener("resize", resize);
    resize();

    if (reduceMotion) return;

    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (entries) {
        visible = entries[0].isIntersecting;
        if (visible && !running) { running = true; requestAnimationFrame(loop); }
      }).observe(host);
    }

    if (finePointer) {
      window.addEventListener("pointermove", function (e) {
        if (!visible) return;
        var rect = host.getBoundingClientRect();
        var nx = (e.clientX - (rect.left + rect.width / 2)) / window.innerWidth;
        var ny = (e.clientY - (rect.top + rect.height / 2)) / window.innerHeight;
        targetY = clamp(nx, -1, 1) * 0.9;
        targetX = clamp(ny, -1, 1) * -0.6;
        var inside = e.clientX >= rect.left && e.clientX <= rect.right && e.clientY >= rect.top && e.clientY <= rect.bottom;
        mouse = inside ? { x: e.clientX - rect.left, y: e.clientY - rect.top } : null;
      }, { passive: true });
    }
  }
  document.querySelectorAll("[data-orb]").forEach(initOrb);

  /* ------------------------------------------------------------------
     Jahr im Footer
     ------------------------------------------------------------------ */
  document.querySelectorAll("[data-year]").forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
