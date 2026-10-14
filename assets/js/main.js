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
     Hintergrundvideos (YouTube, ohne Ton, Endlosschleife)
     <div class="video-bg" data-youtube-id="…"> … </div>
     ------------------------------------------------------------------ */
  var videoEls = document.querySelectorAll(".video-bg[data-youtube-id]");
  var apiPromise = null;

  function loadYouTubeApi() {
    if (apiPromise) return apiPromise;
    apiPromise = new Promise(function (resolve) {
      if (window.YT && window.YT.Player) return resolve(window.YT);
      var previous = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = function () {
        if (typeof previous === "function") previous();
        resolve(window.YT);
      };
      var tag = document.createElement("script");
      tag.src = "https://www.youtube.com/iframe_api";
      tag.async = true;
      document.head.appendChild(tag);
    });
    return apiPromise;
  }

  function mountVideo(stage) {
    if (stage.dataset.mounted) return;
    stage.dataset.mounted = "true";

    var id = stage.dataset.youtubeId;
    var frame = stage.querySelector(".video-bg__frame");
    var target = document.createElement("div");
    frame.appendChild(target);

    var section = stage.parentElement;
    var toggleBtn = section.querySelector(".video-toggle");
    var userPaused = false;

    loadYouTubeApi().then(function (YT) {
      var player = new YT.Player(target, {
        host: "https://www.youtube-nocookie.com",
        videoId: id,
        playerVars: {
          autoplay: 1,
          mute: 1,
          controls: 0,
          loop: 1,
          playlist: id,
          playsinline: 1,
          rel: 0,
          modestbranding: 1,
          disablekb: 1,
          fs: 0,
          iv_load_policy: 3,
          cc_load_policy: 0,
          origin: window.location.origin
        },
        events: {
          onReady: function (e) {
            var iframe = e.target.getIframe();
            iframe.setAttribute("tabindex", "-1");
            iframe.setAttribute("aria-hidden", "true");
            iframe.setAttribute("title", "Hintergrundvideo");
            e.target.mute();
            e.target.playVideo();
            section.classList.add("has-video");
          },
          onStateChange: function (e) {
            if (e.data === YT.PlayerState.PLAYING) {
              stage.classList.add("is-playing");
            } else if (e.data === YT.PlayerState.ENDED) {
              e.target.seekTo(0);
              e.target.playVideo();
            }
          }
        }
      });

      stage._player = player;

      if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
          if (typeof player.getPlayerState !== "function") return;
          userPaused = player.getPlayerState() === YT.PlayerState.PLAYING;
          if (userPaused) player.pauseVideo(); else player.playVideo();
          toggleBtn.classList.toggle("is-paused", userPaused);
          toggleBtn.setAttribute("aria-label", userPaused ? "Video abspielen" : "Video pausieren");
        });
      }

      // Video ausserhalb des Bildschirms anhalten (spart Akku und Daten)
      if ("IntersectionObserver" in window) {
        new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            if (typeof player.playVideo !== "function" || userPaused) return;
            if (entry.isIntersecting) player.playVideo(); else player.pauseVideo();
          });
        }).observe(stage);
      }
    });
  }

  if (videoEls.length && !reduceMotion) {
    if ("IntersectionObserver" in window) {
      var lazyObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            mountVideo(entry.target);
            lazyObserver.unobserve(entry.target);
          }
        });
      }, { rootMargin: "300px 0px" });
      videoEls.forEach(function (el) { lazyObserver.observe(el); });
    } else {
      videoEls.forEach(mountVideo);
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
        showMessage("Das Formular ist noch nicht mit einem Empfänger verbunden. Bitte schreiben Sie uns vorerst per E-Mail.", "error");
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
     Jahr im Footer
     ------------------------------------------------------------------ */
  document.querySelectorAll("[data-year]").forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
