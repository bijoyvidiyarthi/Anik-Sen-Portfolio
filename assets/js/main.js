/**
 * Anik Sen — Portfolio
 * Vanilla JS: nav, scroll reveals, project filter, reviews carousel, contact form.
 */

(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {
        initNavbar();
        initMobileNav();
        initTypingAccent();
        initScrollReveals();
        initScrollSpy();
        initProjectFilter();
        initReviewsCarousel();
        initContactForm();
        initBackToTop();
    });

    /* ---------- Scroll-spy for nav links ---------- */
    function initScrollSpy() {
        const links = Array.from(document.querySelectorAll(
            ".nav-mobile-link, .nav-desktop a"
        )).filter(a => (a.getAttribute("href") || "").startsWith("#"));
        if (!links.length) return;

        const map = new Map();
        links.forEach(link => {
            const id = link.getAttribute("href").slice(1);
            const section = document.getElementById(id);
            if (section) {
                if (!map.has(id)) map.set(id, []);
                map.get(id).push(link);
            }
        });
        if (!map.size) return;

        const setActive = (id) => {
            links.forEach(l => l.classList.remove("is-active"));
            (map.get(id) || []).forEach(l => l.classList.add("is-active"));
        };

        if (!("IntersectionObserver" in window)) return;

        const io = new IntersectionObserver((entries) => {
            const visible = entries
                .filter(e => e.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (visible) setActive(visible.target.id);
        }, { threshold: [0.35, 0.6], rootMargin: "-80px 0px -40% 0px" });

        map.forEach((_, id) => {
            const sec = document.getElementById(id);
            if (sec) io.observe(sec);
        });
    }

    /* ---------- Typing accent (hero) ---------- */
    function initTypingAccent() {
        const el = document.getElementById("typingAccent");
        if (!el) return;

        let phrases;
        try {
            phrases = JSON.parse(el.dataset.phrases || "[]");
        } catch (_) {
            phrases = [];
        }
        if (!phrases.length) return;

        const typeSpeed   = parseInt(el.dataset.typeSpeed,   10) || 80;
        const deleteSpeed = parseInt(el.dataset.deleteSpeed, 10) || 50;
        const pauseAtEnd  = parseInt(el.dataset.pause,       10) || 2000;
        const pauseAtStart = 350;

        let phraseIdx = 0;
        let charIdx   = 0;
        let deleting  = false;

        const tick = () => {
            const phrase = phrases[phraseIdx];

            if (!deleting) {
                charIdx++;
                el.textContent = phrase.slice(0, charIdx);
                if (charIdx === phrase.length) {
                    deleting = true;
                    return setTimeout(tick, pauseAtEnd);
                }
                return setTimeout(tick, typeSpeed);
            }

            charIdx--;
            el.textContent = phrase.slice(0, charIdx);
            if (charIdx === 0) {
                deleting  = false;
                phraseIdx = (phraseIdx + 1) % phrases.length;
                return setTimeout(tick, pauseAtStart);
            }
            return setTimeout(tick, deleteSpeed);
        };

        setTimeout(tick, 600);
    }

    /* ---------- Navbar shrink-on-scroll ---------- */
    function initNavbar() {
        const navbar = document.getElementById("navbar");
        if (!navbar) return;
        const update = () => navbar.classList.toggle("scrolled", window.scrollY > 40);
        update();
        window.addEventListener("scroll", update, { passive: true });
    }

    /* ---------- Mobile nav ---------- */
    function initMobileNav() {
        const toggle = document.getElementById("navToggle");
        const close  = document.getElementById("navMobileClose");
        const drawer = document.getElementById("navMobile");
        if (!toggle || !drawer) return;

        const open = () => {
            drawer.classList.add("open");
            drawer.setAttribute("aria-hidden", "false");
            toggle.setAttribute("aria-expanded", "true");
            document.body.classList.add("menu-open");
            // focus the close button for keyboard users
            requestAnimationFrame(() => close && close.focus({ preventScroll: true }));
        };
        const shut = () => {
            if (!drawer.classList.contains("open")) return;
            drawer.classList.remove("open");
            drawer.setAttribute("aria-hidden", "true");
            toggle.setAttribute("aria-expanded", "false");
            document.body.classList.remove("menu-open");
            toggle.focus({ preventScroll: true });
        };

        toggle.addEventListener("click", open);
        close && close.addEventListener("click", shut);

        // Close when an in-page link inside the panel is clicked
        drawer.querySelectorAll(".nav-mobile-link, .nav-mobile-cta").forEach(a => {
            a.addEventListener("click", shut);
        });

        // Backdrop click closes (clicks outside the panel)
        drawer.addEventListener("click", (e) => {
            if (e.target === drawer) shut();
        });

        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") shut();
        });

        // Dev/preview helper: ?menu=open auto-opens the menu (no-op otherwise)
        try {
            const params = new URLSearchParams(window.location.search);
            if (params.get("menu") === "open") open();
        } catch (_) { /* noop */ }
    }

    /* ---------- Scroll-triggered reveal ---------- */
    function initScrollReveals() {
        const items = document.querySelectorAll(".reveal");
        if (!("IntersectionObserver" in window)) {
            items.forEach(el => el.classList.add("in-view"));
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("in-view");
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: "0px 0px -60px 0px" });
        items.forEach(el => io.observe(el));
    }

    /* ---------- Projects: two-tier filter + pagination + media modals ---------- */
    function initProjectFilter() {
        const grid = document.getElementById("projectsGrid");
        if (!grid) return;
        const cards = grid.querySelectorAll(".project-card");
        const mainBtns  = document.querySelectorAll(".main-filter .filter-btn");
        const subBars   = document.querySelectorAll(".sub-filter");
        const counter   = document.getElementById("projectsCounter");
        const emptyEl   = document.getElementById("projectsEmpty");
        const loadMore  = document.getElementById("projectsLoadMore");
        const loadMoreCount = document.getElementById("projectsLoadMoreCount");

        const PAGE_SIZE = 10;
        let activeMain = "all";
        let activeSub  = "all";
        let pageShown  = PAGE_SIZE;

        function applyFilter() {
            let matched = 0, visible = 0;
            cards.forEach(card => {
                const m = card.dataset.main;
                const s = card.dataset.sub;
                const match = (activeMain === "all" || m === activeMain) &&
                              (activeSub  === "all" || s === activeSub);
                card.classList.toggle("is-hidden", !match);
                card.classList.remove("is-paged-hidden");
                if (!match) return;
                matched++;
                if (matched > pageShown) {
                    card.classList.add("is-paged-hidden");
                } else {
                    visible++;
                }
            });
            updateUi(visible, matched);
        }

        function updateUi(visible, matched) {
            if (counter) {
                counter.innerHTML = matched
                    ? `Showing <strong>${visible}</strong> of <strong>${matched}</strong> project${matched === 1 ? '' : 's'}`
                    : `<strong>0</strong> projects in this view`;
            }
            if (emptyEl) emptyEl.hidden = matched > 0;
            if (loadMore) {
                const remaining = Math.max(0, matched - visible);
                loadMore.hidden = remaining === 0;
                if (loadMoreCount) loadMoreCount.textContent = remaining ? `+${Math.min(PAGE_SIZE, remaining)}` : "";
            }
        }

        function showSubBarFor(main) {
            subBars.forEach(bar => {
                const isMine = bar.dataset.subFor === main;
                bar.hidden = !(isMine && main !== "all");
                if (isMine) {
                    bar.querySelectorAll(".filter-btn").forEach(b =>
                        b.classList.toggle("active", b.dataset.sub === activeSub)
                    );
                }
            });
        }

        mainBtns.forEach(btn => btn.addEventListener("click", () => {
            mainBtns.forEach(b => b.classList.toggle("active", b === btn));
            activeMain = btn.dataset.main;
            activeSub  = "all";
            pageShown  = PAGE_SIZE;
            showSubBarFor(activeMain);
            applyFilter();
        }));

        subBars.forEach(bar => bar.querySelectorAll(".filter-btn").forEach(btn => {
            btn.addEventListener("click", () => {
                bar.querySelectorAll(".filter-btn").forEach(b => b.classList.toggle("active", b === btn));
                activeSub = btn.dataset.sub;
                pageShown = PAGE_SIZE;
                applyFilter();
            });
        }));

        if (loadMore) {
            loadMore.addEventListener("click", () => {
                pageShown += PAGE_SIZE;
                applyFilter();
                // Smooth scroll to the first newly-revealed card
                requestAnimationFrame(() => {
                    const visibleCards = grid.querySelectorAll(".project-card:not(.is-hidden):not(.is-paged-hidden)");
                    const firstNewIndex = Math.max(0, visibleCards.length - PAGE_SIZE);
                    const target = visibleCards[firstNewIndex];
                    if (target) target.scrollIntoView({ behavior: "smooth", block: "start" });
                });
            });
        }

        // Run once on load to compute counter + Load More visibility
        applyFilter();

        /* ---- Project data + modal wiring ---- */
        let DATA = [];
        const dataNode = document.getElementById("pjData");
        if (dataNode) {
            try { DATA = JSON.parse(dataNode.textContent || "[]"); } catch (_) { DATA = []; }
        }
        const byId = new Map(DATA.map(p => [p.id, p]));

        cards.forEach(card => {
            card.addEventListener("click", () => {
                const p = byId.get(parseInt(card.dataset.id, 10));
                if (!p) return;
                if (p.kind === "video") openVideoModal(p);
                else if (p.kind === "gallery") openLightbox(p, 0);
                else if (p.extUrl) window.open(p.extUrl, "_blank", "noopener");
                else openLightbox(p, 0);
            });
        });

        /* ----- VIDEO modal ----- */
        const vModal = document.getElementById("pjVideo");
        const vMount = document.getElementById("pjVideoMount");
        function openVideoModal(p) {
            // Prefer the self-hosted MP4/WebM file when one is uploaded — the
            // <video> element gives us a proper poster, browser-native controls
            // and direct CDN-style playback. The YouTube/Vimeo URL only acts as
            // a fallback for legacy projects without a local file.
            const file = (p.videoFile || "").trim();
            const url  = (p.videoUrl  || "").trim();
            let html = "";
            if (file) {
                const mime   = (p.videoMime || "").trim();
                const poster = (p.poster   || "").trim();
                html =
                    `<video controls autoplay playsinline preload="metadata"` +
                    (poster ? ` poster="${poster}"` : "") + `>` +
                        `<source src="${file}"` + (mime ? ` type="${mime}"` : "") + `>` +
                        `Your browser does not support HTML5 video.` +
                    `</video>`;
            } else if (url) {
                html = embedHtml(url);
            }
            vMount.innerHTML = html ||
                `<div class="pj-empty"><i class="fa-solid fa-circle-exclamation"></i> No video set for this project yet.</div>`;
            showModal(vModal);
        }
        function closeVideo() {
            vMount.innerHTML = ""; // stop playback
            hideModal(vModal);
        }

        /* ----- LIGHTBOX -----
         * Builds a flat playlist of every image belonging to the currently-visible
         * gallery cards (i.e. respects the active main + sub filter), then prev/next
         * cycles across that whole playlist — including jumping between projects in
         * the same category instead of being stuck inside one project.
         */
        const lb       = document.getElementById("pjLightbox");
        const lbImg    = document.getElementById("pjLbImg");
        const lbCount  = document.getElementById("pjLbCounter");
        const lbStage  = document.getElementById("pjLbStage");
        const zoomLvl  = document.getElementById("pjZoomLevel");
        let lbList = [];   // [{ p, src, projTitle, indexInProject, totalInProject }]
        let lbPos  = 0;
        let lbZoom = 1;

        function buildLbList(startProjectId, startImgIdx) {
            const list = [];
            let startPos = 0;
            cards.forEach(card => {
                if (card.classList.contains("is-hidden")) return;
                if (card.dataset.kind !== "gallery") return;
                const proj = byId.get(parseInt(card.dataset.id, 10));
                if (!proj || !Array.isArray(proj.images)) return;
                proj.images.forEach((src, j) => {
                    if (proj.id === startProjectId && j === startImgIdx) {
                        startPos = list.length;
                    }
                    list.push({
                        p: proj,
                        src,
                        projTitle: proj.title,
                        indexInProject: j,
                        totalInProject: proj.images.length,
                    });
                });
            });
            // Fallback: if the clicked project is somehow not visible, still show it alone.
            if (!list.length) {
                const proj = byId.get(startProjectId);
                if (proj) {
                    proj.images.forEach((src, j) => list.push({
                        p: proj, src, projTitle: proj.title,
                        indexInProject: j, totalInProject: proj.images.length,
                    }));
                    startPos = startImgIdx || 0;
                }
            }
            return { list, startPos };
        }

        function openLightbox(p, idx) {
            const built = buildLbList(p.id, idx || 0);
            lbList = built.list;
            lbPos  = built.startPos;
            lbZoom = 1;
            renderLb();
            showModal(lb);
        }
        function renderLb() {
            if (!lbList.length) return;
            const item = lbList[lbPos];
            lbImg.src = item.src;
            lbImg.alt = item.projTitle;
            const projPart = item.totalInProject > 1
                ? ` · ${item.projTitle} (${item.indexInProject + 1}/${item.totalInProject})`
                : ` · ${item.projTitle}`;
            lbCount.textContent = `${lbPos + 1} / ${lbList.length}${projPart}`;
            applyZoom();
        }
        function applyZoom() {
            lbImg.style.transform = `scale(${lbZoom})`;
            zoomLvl.textContent = `${Math.round(lbZoom * 100)}%`;
            lbStage.style.cursor = lbZoom > 1 ? "grab" : "zoom-in";
        }
        function closeLb() { hideModal(lb); lbList = []; lbPos = 0; }
        function closeAnyModal(e) {
            if (e) { try { e.preventDefault(); e.stopPropagation(); } catch (_) {} }
            closeVideo();
            closeLb();
        }

        // Expose globally so the inline `onclick="__pjClose(event)"` on each
        // close button can fire even if every other handler fails to bind.
        window.__pjClose = closeAnyModal;

        // Belt + suspenders: bind a direct click listener on every X button as
        // well as a delegated handler on the document. Either path closes the
        // modal — there is no way for a single failure to leave it open.
        document.querySelectorAll(".pj-modal [data-close]").forEach(btn => {
            btn.addEventListener("click", closeAnyModal);
            // Also handle touchend (some mobile browsers swallow click after a long-press)
            btn.addEventListener("touchend", (ev) => { ev.preventDefault(); closeAnyModal(); }, { passive: false });
        });

        // Document-level delegation — covers any close target injected later.
        document.addEventListener("click", (e) => {
            const closer = e.target.closest("[data-close]");
            if (closer && closer.closest(".pj-modal")) {
                closeAnyModal(e);
                return;
            }
            // Backdrop click — only when the click landed on the modal background itself.
            if (e.target.classList && e.target.classList.contains("pj-modal")) {
                closeAnyModal();
            }
        });
        document.querySelector("[data-lb-prev]")?.addEventListener("click", () => {
            if (!lbList.length) return;
            lbPos = (lbPos - 1 + lbList.length) % lbList.length;
            lbZoom = 1; renderLb();
        });
        document.querySelector("[data-lb-next]")?.addEventListener("click", () => {
            if (!lbList.length) return;
            lbPos = (lbPos + 1) % lbList.length;
            lbZoom = 1; renderLb();
        });
        document.querySelectorAll("[data-zoom]").forEach(btn => btn.addEventListener("click", () => {
            const dir = parseInt(btn.dataset.zoom, 10);
            if (dir === 0) lbZoom = 1;
            else lbZoom = Math.min(4, Math.max(0.5, lbZoom + dir * 0.25));
            applyZoom();
        }));
        lbStage?.addEventListener("click", e => {
            if (e.target === lbImg && lbZoom === 1) { lbZoom = 2; applyZoom(); }
            else if (e.target === lbImg) { lbZoom = 1; applyZoom(); }
        });
        lbStage?.addEventListener("wheel", e => {
            if (lb.hidden) return;
            e.preventDefault();
            lbZoom = Math.min(4, Math.max(0.5, lbZoom + (e.deltaY < 0 ? 0.15 : -0.15)));
            applyZoom();
        }, { passive: false });
        document.addEventListener("keydown", e => {
            if (!vModal.hidden && e.key === "Escape") closeVideo();
            if (!lb.hidden) {
                if (e.key === "Escape") closeLb();
                if (e.key === "ArrowLeft")  document.querySelector("[data-lb-prev]")?.click();
                if (e.key === "ArrowRight") document.querySelector("[data-lb-next]")?.click();
                if (e.key === "+" || e.key === "=") { lbZoom = Math.min(4, lbZoom + 0.25); applyZoom(); }
                if (e.key === "-") { lbZoom = Math.max(0.5, lbZoom - 0.25); applyZoom(); }
                if (e.key === "0") { lbZoom = 1; applyZoom(); }
            }
        });

        /* Clean up close-confirm hooks for forms in admin (no-op on public) */
        document.querySelectorAll("[data-confirm-form]").forEach(form => {
            form.addEventListener("submit", e => {
                const btn = form.querySelector("[data-confirm]");
                if (btn && !confirm(btn.dataset.confirm)) e.preventDefault();
            });
        });

        function showModal(m) {
            m.hidden = false;
            m.removeAttribute("style"); // clear any leftover inline display:none
            document.body.classList.add("pj-locked");
        }
        function hideModal(m) {
            m.hidden = true;
            m.setAttribute("hidden", "");          // belt + braces — guarantee the attr is set
            m.style.display = "none";              // hard fallback in case CSS specificity loses
            if (vModal.hidden && lb.hidden) document.body.classList.remove("pj-locked");
        }

        function embedHtml(url) {
            if (!url) return "";
            // YouTube
            let m = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/);
            if (m) return `<iframe src="https://www.youtube.com/embed/${m[1]}?autoplay=1&rel=0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>`;
            // Vimeo
            m = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
            if (m) return `<iframe src="https://player.vimeo.com/video/${m[1]}?autoplay=1" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>`;
            // Local mp4 / webm
            if (/\.(mp4|webm|mov|m4v)(\?.*)?$/i.test(url)) {
                return `<video src="${url}" controls autoplay playsinline></video>`;
            }
            // Generic fallback iframe
            return `<iframe src="${url}" allow="autoplay; fullscreen" allowfullscreen></iframe>`;
        }
    }

    /* ---------- Reviews carousel ---------- */
    function initReviewsCarousel() {
        const track = document.getElementById("reviewsTrack");
        const prev  = document.getElementById("reviewsPrev");
        const next  = document.getElementById("reviewsNext");
        if (!track || !prev || !next) return;

        const slides = track.querySelectorAll(".carousel-slide");
        if (!slides.length) return;

        let index = 0;
        const slidesPerView = () => {
            if (window.matchMedia("(min-width: 1024px)").matches) return 2;
            if (window.matchMedia("(min-width: 768px)").matches) return 1.6;
            return 1;
        };
        const maxIndex = () => Math.max(0, slides.length - Math.floor(slidesPerView()));

        const apply = () => {
            const slideWidth = slides[0].getBoundingClientRect().width;
            track.style.transform = `translateX(${-index * slideWidth}px)`;
        };

        prev.addEventListener("click", () => {
            index = index <= 0 ? maxIndex() : index - 1;
            apply();
        });
        next.addEventListener("click", () => {
            index = index >= maxIndex() ? 0 : index + 1;
            apply();
        });

        let timer = null;
        const startAuto = () => {
            stopAuto();
            timer = window.setInterval(() => {
                index = index >= maxIndex() ? 0 : index + 1;
                apply();
            }, 6000);
        };
        const stopAuto = () => { if (timer) { clearInterval(timer); timer = null; } };

        const carousel = document.getElementById("reviewsCarousel");
        carousel.addEventListener("mouseenter", stopAuto);
        carousel.addEventListener("mouseleave", startAuto);

        window.addEventListener("resize", () => {
            if (index > maxIndex()) index = maxIndex();
            apply();
        });

        apply();
        startAuto();
    }

    /* ---------- Contact form ---------- */
    function initContactForm() {
        const form = document.getElementById("contactForm");
        if (!form) return;
        const submit = document.getElementById("contactSubmit");

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearFormErrors(form);

            const errors = validateForm(form);
            if (Object.keys(errors).length) {
                paintFormErrors(form, errors);
                return;
            }

            submit.disabled = true;
            const label = submit.querySelector(".btn-label");
            const originalLabel = label ? label.textContent : "";
            if (label) label.textContent = "Sending...";

            try {
                const data = new FormData(form);
                const res  = await fetch(form.action, { method: "POST", body: data });
                const json = await res.json().catch(() => ({}));

                if (res.ok && json.success) {
                    showToast(json.message || "Message sent successfully.", "success");
                    form.reset();
                } else if (json.fields) {
                    paintFormErrors(form, json.fields);
                    showToast(json.error || "Please correct the errors.", "error");
                } else {
                    showToast(json.error || "Something went wrong. Please try again.", "error");
                }
            } catch (err) {
                showToast("Network error. Please try again.", "error");
            } finally {
                submit.disabled = false;
                if (label) label.textContent = originalLabel || "Send Message";
            }
        });
    }

    function validateForm(form) {
        const errors = {};
        const name    = form.name.value.trim();
        const email   = form.email.value.trim();
        const subject = form.subject.value.trim();
        const message = form.message.value.trim();

        if (name.length < 2) errors.name = "Name must be at least 2 characters.";
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.email = "Please enter a valid email address.";
        if (subject.length < 5) errors.subject = "Subject must be at least 5 characters.";
        if (message.length < 10) errors.message = "Message must be at least 10 characters.";
        return errors;
    }
    function clearFormErrors(form) {
        form.querySelectorAll(".field").forEach(f => f.classList.remove("has-error"));
        form.querySelectorAll(".field-error").forEach(e => e.textContent = "");
    }
    function paintFormErrors(form, errors) {
        Object.entries(errors).forEach(([key, msg]) => {
            const errEl = form.querySelector(`[data-error-for="${key}"]`);
            if (errEl) errEl.textContent = msg;
            const input = form.querySelector(`[name="${key}"]`);
            if (input) input.closest(".field").classList.add("has-error");
        });
    }

    /* ---------- Toast ---------- */
    let toastTimer = null;
    function showToast(text, type) {
        const el = document.getElementById("toast");
        if (!el) return;
        el.textContent = text;
        el.className = "toast show" + (type ? " " + type : "");
        clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            el.classList.remove("show");
        }, 4000);
    }

    /* ---------- Back to top ---------- */
    function initBackToTop() {
        const btn = document.getElementById("backToTop");
        if (!btn) return;
        btn.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }
})();
