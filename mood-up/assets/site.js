(function(){
  document.documentElement.classList.add("js");
  var reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;

  var header = document.querySelector("[data-header]");
  var solid = function(){ header.classList.toggle("is-solid", scrollY > 10); };
  solid(); addEventListener("scroll", solid, {passive:true});

  /* Scrollytelling: aktiver Schritt wechselt den Screen */
  var steps = document.querySelectorAll(".step");
  var shots = document.querySelectorAll(".shot");
  var bar = document.querySelector(".progress i");
  var current = 0;
  var bignum = document.querySelector("[data-bignum]");
  function activate(i){
    if (i === current) return;
    current = i;
    steps.forEach(function(s,j){ s.classList.toggle("is-active", j === i); });
    shots.forEach(function(s,j){ s.classList.toggle("is-on", j === i); });
    if (bar) bar.style.height = ((i + 1) / steps.length * 100) + "%";
    if (bignum) { bignum.classList.add("swap"); setTimeout(function(){ bignum.textContent = "0" + (i + 1); bignum.classList.remove("swap"); }, 200); }
  }
  if (bar) bar.style.height = (100 / steps.length) + "%";
  var mobile = matchMedia("(max-width: 900px)").matches;
  var io = new IntersectionObserver(function(entries){
    entries.forEach(function(e){ if (e.isIntersecting) activate(+e.target.dataset.step); });
  }, { rootMargin: mobile ? "-60% 0px -32% 0px" : "-48% 0px -48% 0px" });
  steps.forEach(function(s){ io.observe(s); });

  /* Erscheinen beim Scrollen */
  var els = document.querySelectorAll(".reveal");
  if (reduce) { els.forEach(function(e){ e.classList.add("in"); }); }
  else {
    var ro = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ if (e.isIntersecting){ e.target.classList.add("in"); ro.unobserve(e.target); } });
    }, { rootMargin: "0px 0px -8% 0px" });
    els.forEach(function(e){ ro.observe(e); });
  }

  /* Wort-Wechsel */
  var words = document.querySelectorAll(".rotator span");
  if (!reduce && words.length) {
    var w = 0;
    setInterval(function(){
      var cur = words[w]; w = (w + 1) % words.length; var nxt = words[w];
      cur.classList.remove("is-on"); cur.classList.add("is-out");
      nxt.classList.remove("is-out"); nxt.classList.add("is-on");
      setTimeout(function(){ cur.classList.remove("is-out"); }, 560);
    }, 2200);
  }

  /* Handy-Fächer folgt der Maus */
  var fan = document.querySelector("[data-fan] .fan__in");
  if (fan && !reduce && matchMedia("(hover:hover) and (pointer:fine)").matches) {
    var hero = document.querySelector(".hero");
    hero.addEventListener("mousemove", function(e){
      var x = e.clientX / innerWidth - .5, y = e.clientY / innerHeight - .5;
      fan.style.transform = "rotateY(" + (x*10).toFixed(2) + "deg) rotateX(" + (-y*8).toFixed(2) + "deg)";
    });
    hero.addEventListener("mouseleave", function(){ fan.style.transform = ""; });
  }

  /* Laufband mit Scroll-Bewegung */
  var rows = document.querySelectorAll("[data-speed]");
  var ticking = false;
  function moveRows(){
    rows.forEach(function(r){
      var rect = r.getBoundingClientRect();
      var off = (rect.top - innerHeight) * parseFloat(r.dataset.speed);
      r.style.transform = "translate3d(" + (off - (parseFloat(r.dataset.speed) > 0 ? r.scrollWidth*.25 : 0)).toFixed(1) + "px,0,0)";
    });
    ticking = false;
  }
  if (rows.length && !reduce) {
    moveRows();
    addEventListener("scroll", function(){ if (!ticking){ ticking = true; requestAnimationFrame(moveRows); } }, {passive:true});
  }

  /* Wortmarke: Buchstaben weichen der Maus aus */
  var wm = document.querySelector("[data-wordmark]");
  if (wm && !reduce && matchMedia("(hover:hover) and (pointer:fine)").matches) {
    var letters = wm.querySelectorAll("span");
    wm.addEventListener("mousemove", function(e){
      letters.forEach(function(l){
        var r = l.getBoundingClientRect(), cx = r.left + r.width/2;
        var d = Math.max(0, 1 - Math.abs(e.clientX - cx) / 260);
        l.style.transform = "translateY(" + (-d*28).toFixed(1) + "px)";
      });
    });
    wm.addEventListener("mouseleave", function(){ letters.forEach(function(l){ l.style.transform = ""; }); });
  }

  /* Smart Filter: Beispiele wechseln automatisch (nur zur Information) */
  var sentence = document.querySelector("[data-sentence]");
  if (sentence && !reduce) {
    var examples = [
      ["Clubs", "Techno", "ab 18", "heute"],
      ["Bars", "Rock", "ab 18", "am Wochenende"],
      ["Specials", "80s Hits", "ab 18", "morgen"]
    ];
    var dotsEl = document.querySelectorAll(".result__dots i"), ex = 0;
    setInterval(function(){
      ex = (ex + 1) % examples.length;
      sentence.classList.add("swap");
      setTimeout(function(){
        var e = examples[ex];
        sentence.innerHTML = "Zeig mir <b>" + e[0] + "</b> mit <b>" + e[1] + "</b>, <b>" + e[2] + "</b>, <span class=\"nw\"><b>" + e[3] + "</b>.</span>";
        sentence.classList.remove("swap");
        dotsEl.forEach(function(d, i){ d.classList.toggle("is-on", i === ex); });
      }, 350);
    }, 3200);
  }

  /* Mobiles Menü */
  var burger = document.querySelector(".burger"), menu = document.getElementById("menu");
  if (burger && menu) {
    var setMenu = function(open){
      burger.setAttribute("aria-expanded", String(open));
      burger.setAttribute("aria-label", open ? "Menü schliessen" : "Menü öffnen");
      menu.hidden = !open;
      header.classList.toggle("is-solid", open || scrollY > 10);
    };
    burger.addEventListener("click", function(){ setMenu(menu.hidden); });
    menu.addEventListener("click", function(e){ if (e.target.closest("a")) setMenu(false); });
    document.addEventListener("keydown", function(e){ if (e.key === "Escape" && !menu.hidden){ setMenu(false); burger.focus(); } });
  }

  /* Kontaktformular: prüft Felder, öffnet E-Mail mit fertiger Nachricht */
  var form = document.querySelector("form[data-mailto]");
  if (form) {
    form.addEventListener("submit", function(e){
      e.preventDefault();
      var checks = [["f-name","e-name",function(v){return v.trim().length>1;}],["f-mail","e-mail",function(v){return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v.trim());}],["f-msg","e-msg",function(v){return v.trim().length>2;}]];
      var first = null;
      checks.forEach(function(c){
        var f = document.getElementById(c[0]), er = document.getElementById(c[1]), ok = c[2](f.value);
        f.setAttribute("aria-invalid", ok ? "false" : "true");
        f.setAttribute("aria-describedby", c[1]);
        er.hidden = ok;
        if (!ok && !first) first = f;
      });
      if (first) { first.focus(); return; }
      var who = form.querySelector("input[name=who]:checked").value;
      var body = "Name: " + document.getElementById("f-name").value + "\nE-Mail: " + document.getElementById("f-mail").value + "\nIch bin: " + who + "\n\n" + document.getElementById("f-msg").value;
      location.href = "mailto:" + form.dataset.mailto + "?subject=" + encodeURIComponent("Anfrage über mood-up.ch (" + who + ")") + "&body=" + encodeURIComponent(body);
    });
  }

  document.querySelectorAll("[data-year]").forEach(function(e){ e.textContent = new Date().getFullYear(); });
})();
