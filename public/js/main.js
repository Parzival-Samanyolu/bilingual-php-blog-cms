/* ==========================================================================
   My Blog — Public interactions (vanilla JS, no framework)
   Selectors match the js-* hooks and component classes in the views.
   ========================================================================== */
(function () {
  "use strict";

  var doc = document;

  function csrfToken() {
    var m = doc.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute("content") || "" : "";
  }

  // Base path when hosted under a subfolder (e.g. "/yeni"); "" at the domain root.
  var BASE = (function () {
    var m = doc.querySelector('meta[name="base-path"]');
    return m ? (m.getAttribute("content") || "") : "";
  })();

  /* Form-encoded POST with CSRF in both header and body. */
  function postForm(url, data) {
    var body = new URLSearchParams();
    body.set("_csrf", csrfToken());
    if (data) {
      Object.keys(data).forEach(function (k) { body.set(k, data[k]); });
    }
    return fetch(url, {
      method: "POST",
      headers: {
        "X-CSRF-Token": csrfToken(),
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      credentials: "same-origin",
      body: body.toString()
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (j) {
        return { ok: r.ok, status: r.status, data: j };
      });
    });
  }

  function debounce(fn, wait) {
    var t;
    return function () {
      var ctx = this, args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }

  /* --- (1) Like / (2) Bookmark ------------------------------------------- */
  function initReactions() {
    doc.querySelectorAll(".btn-like[data-slug]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (btn.disabled) return;
        btn.disabled = true;
        var slug = btn.getAttribute("data-slug");
        postForm(BASE + "/yazi/" + encodeURIComponent(slug) + "/begen").then(function (res) {
          if (res.status === 401) { window.location.href = "/giris"; return; }
          if (res.ok && typeof res.data.liked !== "undefined") {
            btn.classList.toggle("is-active", !!res.data.liked);
            var count = btn.querySelector("[data-like-count]");
            if (count && typeof res.data.count !== "undefined") {
              count.textContent = String(res.data.count);
            }
          }
        }).finally(function () { btn.disabled = false; });
      });
    });

    doc.querySelectorAll(".btn-bookmark[data-slug]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (btn.disabled) return;
        btn.disabled = true;
        var slug = btn.getAttribute("data-slug");
        postForm(BASE + "/yazi/" + encodeURIComponent(slug) + "/kaydet").then(function (res) {
          if (res.status === 401) { window.location.href = "/giris"; return; }
          if (res.ok && typeof res.data.bookmarked !== "undefined") {
            btn.classList.toggle("is-active", !!res.data.bookmarked);
          }
        }).finally(function () { btn.disabled = false; });
      });
    });
  }

  /* --- (3) Comment form (AJAX) ------------------------------------------- */
  function initCommentForm() {
    var form = doc.querySelector(".js-comment-form");
    if (!form) return;
    var feedback = form.querySelector(".js-comment-feedback");
    var textarea = form.querySelector("textarea[name='content']");

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var submitBtn = form.querySelector("[type='submit']");
      if (submitBtn) submitBtn.disabled = true;
      postForm(form.getAttribute("action"), { content: textarea ? textarea.value : "" })
        .then(function (res) {
          if (res.status === 401) { window.location.href = "/giris"; return; }
          if (feedback) {
            feedback.hidden = false;
            feedback.textContent = res.data.message || "";
            feedback.classList.toggle("is-success", !!res.data.success);
            feedback.classList.toggle("is-error", !res.data.success);
          }
          if (res.data.success && textarea) { textarea.value = ""; }
        })
        .finally(function () { if (submitBtn) submitBtn.disabled = false; });
    });
  }

  /* --- (4) Search suggestions -------------------------------------------- */
  function initSearchSuggest() {
    var input = doc.querySelector(".js-search-input");
    var panel = doc.querySelector(".js-search-suggest");
    if (!input || !panel) return;
    var form = input.closest("form");
    var base = form ? form.getAttribute("action") : "/ara";

    function hide() { panel.hidden = true; panel.innerHTML = ""; }

    var run = debounce(function () {
      var q = input.value.trim();
      if (q.length < 2) { hide(); return; }
      fetch(base + "?q=" + encodeURIComponent(q), { credentials: "same-origin" })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var d = new DOMParser().parseFromString(html, "text/html");
          var links = Array.prototype.slice
            .call(d.querySelectorAll(".article-card__title a"), 0, 5);
          if (!links.length) { hide(); return; }
          panel.innerHTML = "";
          links.forEach(function (a) {
            var el = doc.createElement("a");
            el.href = a.getAttribute("href");
            el.textContent = a.textContent.trim();
            panel.appendChild(el);
          });
          panel.hidden = false;
        }).catch(hide);
    }, 300);

    input.addEventListener("input", run);
    doc.addEventListener("click", function (e) {
      if (!panel.contains(e.target) && e.target !== input) hide();
    });
    input.addEventListener("keydown", function (e) { if (e.key === "Escape") hide(); });
  }

  /* --- (5) Language switcher --------------------------------------------- */
  function initLangSwitch() {
    doc.querySelectorAll(".lang-switch__pill[data-lang]").forEach(function (pill) {
      pill.addEventListener("click", function (e) {
        e.preventDefault();
        if (pill.classList.contains("is-active")) return;
        var lang = pill.getAttribute("data-lang");
        postForm(BASE + "/lang", { lang: lang })
          .then(function () { window.location.reload(); })
          .catch(function () { window.location.reload(); });
      });
    });
  }

  /* --- (6) Mobile hamburger ---------------------------------------------- */
  function initNav() {
    var toggle = doc.querySelector(".js-nav-toggle");
    var nav = doc.querySelector(".js-nav");
    if (!toggle || !nav) return;
    toggle.addEventListener("click", function () {
      var open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
  }

  /* --- (7) User menu dropdown -------------------------------------------- */
  function initUserMenu() {
    var menu = doc.querySelector(".js-user-menu");
    var trigger = doc.querySelector(".js-user-menu-trigger");
    if (!menu || !trigger) return;
    trigger.addEventListener("click", function (e) {
      e.stopPropagation();
      var open = menu.classList.toggle("is-open");
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
    });
    doc.addEventListener("click", function (e) {
      if (!menu.contains(e.target)) {
        menu.classList.remove("is-open");
        trigger.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* --- (8) Flash messages ------------------------------------------------ */
  function dismissFlash(flash) {
    flash.classList.add("is-hiding");
    setTimeout(function () { if (flash.parentNode) flash.parentNode.removeChild(flash); }, 260);
  }
  function initFlash() {
    doc.querySelectorAll(".flash__close").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var flash = btn.closest(".flash");
        if (flash) dismissFlash(flash);
      });
    });
    doc.querySelectorAll(".flash--success").forEach(function (flash) {
      setTimeout(function () { if (flash.parentNode) dismissFlash(flash); }, 5000);
    });
  }

  /* --- (9) Lazy images --------------------------------------------------- */
  function initLazyImages() {
    var imgs = doc.querySelectorAll("img[data-src]");
    if (!imgs.length) return;
    if (!("IntersectionObserver" in window)) {
      imgs.forEach(function (img) { img.src = img.getAttribute("data-src"); });
      return;
    }
    var io = new IntersectionObserver(function (entries, obs) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var img = entry.target;
          img.src = img.getAttribute("data-src");
          img.removeAttribute("data-src");
          obs.unobserve(img);
        }
      });
    }, { rootMargin: "200px" });
    imgs.forEach(function (img) { io.observe(img); });
  }

  /* --- (10) Quill editors (author + admin) ------------------------------- */
  function initEditors() {
    if (typeof window.Quill === "undefined") return;
    var editors = [
      { el: "#editor", field: "#contentField" },
      { el: "#admin-editor", field: "#content-input" }
    ];
    var toolbar = [
      [{ header: [2, 3, false] }],
      ["bold", "italic", "underline", "strike"],
      ["blockquote", "code-block"],
      [{ list: "ordered" }, { list: "bullet" }],
      ["link", "image"],
      [{ align: [] }],
      ["clean"]
    ];
    editors.forEach(function (cfg) {
      var host = doc.querySelector(cfg.el);
      if (!host) return;
      var field = doc.querySelector(cfg.field);
      var form = host.closest("form");
      var q = new window.Quill(cfg.el, { theme: "snow", modules: { toolbar: toolbar } });
      if (form) {
        form.addEventListener("submit", function () {
          if (field) {
            var html = q.root.innerHTML;
            if (html === "<p><br></p>") html = "";
            field.value = html;
          }
        });
      }
    });
  }

  /* --- init -------------------------------------------------------------- */
  function ready(fn) {
    if (doc.readyState !== "loading") fn();
    else doc.addEventListener("DOMContentLoaded", fn);
  }
  ready(function () {
    initReactions();
    initCommentForm();
    initSearchSuggest();
    initLangSwitch();
    initNav();
    initUserMenu();
    initFlash();
    initLazyImages();
    initEditors();
  });
})();
