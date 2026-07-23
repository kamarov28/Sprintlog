import './bootstrap';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

// Initialize High-End GSAP Scrollytelling & Interactive System
document.addEventListener('DOMContentLoaded', () => {

    // -------------------------------------------------------------
    // 1. GSAP Interactive Cursor Glow Spotlight Follower
    // -------------------------------------------------------------
    const cursorGlow = document.getElementById('cursor-glow');
    if (cursorGlow) {
        const xTo = gsap.quickTo(cursorGlow, 'x', { duration: 0.6, ease: 'power3.out' });
        const yTo = gsap.quickTo(cursorGlow, 'y', { duration: 0.6, ease: 'power3.out' });

        window.addEventListener('mousemove', (e) => {
            gsap.to(cursorGlow, { opacity: 1, duration: 0.5 });
            xTo(e.clientX);
            yTo(e.clientY);
        });

        document.addEventListener('mouseleave', () => {
            gsap.to(cursorGlow, { opacity: 0, duration: 0.5 });
        });
    }

    // -------------------------------------------------------------
    // 2. Hero Particles System
    // -------------------------------------------------------------
    initHeroParticles();

    // -------------------------------------------------------------
    // 3. Hero Entrance Timeline & Parallax Scroll Scrub
    // -------------------------------------------------------------
    const badgeEl = document.querySelector('[data-gsap="badge"]');
    const titleEl = document.querySelector('[data-gsap="title"]');
    const subEl = document.querySelector('[data-gsap="subtitle"]');
    const ctaEl = document.querySelector('[data-gsap="cta"]');
    const tickerEl = document.querySelector('[data-gsap="ticker"]');

    if (titleEl) {
        const heroTl = gsap.timeline({ defaults: { ease: 'power4.out', duration: 1.2 } });
        
        if (badgeEl) {
            heroTl.fromTo(badgeEl, 
                { opacity: 0, y: -30, scale: 0.8 }, 
                { opacity: 1, y: 0, scale: 1, duration: 0.9 }
            );
        }

        heroTl.fromTo(titleEl, 
            { opacity: 0, y: 50, rotationX: -25, transformPerspective: 800 }, 
            { opacity: 1, y: 0, rotationX: 0, duration: 1.3 }, 
            '-=0.6'
        );

        if (subEl) {
            heroTl.fromTo(subEl, 
                { opacity: 0, y: 30 }, 
                { opacity: 1, y: 0, duration: 0.9 }, 
                '-=0.8'
            );
        }

        if (ctaEl) {
            heroTl.fromTo(ctaEl, 
                { opacity: 0, y: 30, scale: 0.95 }, 
                { opacity: 1, y: 0, scale: 1, duration: 0.9 }, 
                '-=0.7'
            );
        }

        if (tickerEl) {
            heroTl.fromTo(tickerEl, 
                { opacity: 0, y: 20, scale: 0.98 }, 
                { opacity: 1, y: 0, scale: 1, duration: 1 }, 
                '-=0.5'
            );
        }

        // Hero Parallax Scrub on Scroll
        const heroSection = titleEl.closest('section');
        if (heroSection) {
            gsap.to(heroSection, {
                yPercent: -20,
                opacity: 0.3,
                scale: 0.96,
                ease: 'none',
                scrollTrigger: {
                    trigger: heroSection,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.2
                }
            });
        }
    }

    // -------------------------------------------------------------
    // 4. Act II (Scrollytelling 4 Step Cards) with Physics Scrub
    // -------------------------------------------------------------
    const storyCards = gsap.utils.toArray('.story-card');
    if (storyCards.length > 0) {
        storyCards.forEach((card, idx) => {
            const counterEl = card.querySelector('[data-count]');
            const targetVal = counterEl ? parseInt(counterEl.dataset.count, 10) : null;
            const numberSpan = card.querySelector('.story-step-number');

            // ScrollTrigger with smooth scrub / eager start
            gsap.fromTo(card, 
                { 
                    opacity: 0, 
                    y: 60, 
                    scale: 0.94,
                    rotationX: -10,
                    transformPerspective: 1000 
                },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    rotationX: 0,
                    duration: 0.9,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: card,
                        start: 'top 92%',
                        toggleActions: 'play none none reverse',
                        onEnter: () => {
                            // Step Number Counter Up Animation
                            if (counterEl && targetVal !== null) {
                                const obj = { val: 0 };
                                gsap.to(obj, {
                                    val: targetVal,
                                    duration: 1,
                                    ease: 'power2.out',
                                    onUpdate: () => {
                                        counterEl.textContent = String(Math.ceil(obj.val)).padStart(2, '0');
                                    }
                                });
                            }
                            if (numberSpan) {
                                gsap.fromTo(numberSpan, 
                                    { scale: 0.6, opacity: 0.2 },
                                    { scale: 1, opacity: 1, duration: 0.8, ease: 'back.out(1.7)' }
                                );
                            }
                        }
                    }
                }
            );

            // Magnetic 3D Physics Hover Effect
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                gsap.to(card, {
                    rotationY: x * 0.02,
                    rotationX: -y * 0.02,
                    transformPerspective: 1000,
                    ease: 'power1.out',
                    duration: 0.3
                });
            });

            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    rotationY: 0,
                    rotationX: 0,
                    ease: 'power2.out',
                    duration: 0.6
                });
            });
        });
    }

    // -------------------------------------------------------------
    // 4.5 Horizontal Scroll Pinning Brand Manifesto (Multi-layer Parallax)
    // -------------------------------------------------------------
    const pinSection = document.getElementById('horizontal-pin-section');
    const textTrack = document.getElementById('horizontal-text-track');

    if (pinSection && textTrack) {
        const getScrollAmount = () => textTrack.scrollWidth - window.innerWidth + 150;

        const pinTl = gsap.timeline({
            scrollTrigger: {
                trigger: pinSection,
                pin: true,
                start: 'top top',
                end: () => `+=${getScrollAmount() + 600}`,
                scrub: 1.2,
                invalidateOnRefresh: true,
            }
        });

        // Track horizontal movement
        pinTl.to(textTrack, {
            x: () => -getScrollAmount(),
            ease: 'none'
        }, 0);

        // Floating parallax badges shift faster
        const fastBadges = textTrack.querySelectorAll('[data-parallax="fast"]');
        if (fastBadges.length > 0) {
            pinTl.to(fastBadges, {
                x: -140,
                rotation: 6,
                ease: 'none'
            }, 0);
        }
    }

    // -------------------------------------------------------------
    // 5. Act III Command Hub Scale & Morph Scroll Trigger
    // -------------------------------------------------------------
    const hubSection = document.getElementById('interactive-hub');
    if (hubSection) {
        gsap.fromTo(hubSection,
            { opacity: 0, scale: 0.93, y: 50 },
            {
                opacity: 1,
                scale: 1,
                y: 0,
                duration: 1,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: hubSection,
                    start: 'top 88%',
                    toggleActions: 'play none none reverse'
                }
            }
        );
    }

    // -------------------------------------------------------------
    // 6. Act IV Service Tier Cards Stagger & Glow Entrance
    // -------------------------------------------------------------
    const servicesSection = document.getElementById('services');
    if (servicesSection) {
        const cards = servicesSection.querySelectorAll('.story-card');
        if (cards.length > 0) {
            gsap.fromTo(cards,
                { opacity: 0, y: 45, scale: 0.92 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.8,
                    stagger: 0.15,
                    ease: 'power3.out',
                    scrollTrigger: {
                        trigger: servicesSection,
                        start: 'top 85%',
                        toggleActions: 'play none none reverse'
                    }
                }
            );
        }
    }

    // -------------------------------------------------------------
    // 7. General Glass Panels Observer Fallback
    // -------------------------------------------------------------
    const revealTargets = document.querySelectorAll('.section-animate, .glass-panel');
    if (!('IntersectionObserver' in window)) {
        revealTargets.forEach((target) => target.classList.add('is-visible'));
    } else {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.02,
            rootMargin: '0px 0px -2% 0px',
        });

        revealTargets.forEach((target) => {
            target.classList.add('reveal-on-scroll');
            observer.observe(target);
        });
    }

    // -------------------------------------------------------------
    // 8. Back to Top Floating Button
    // -------------------------------------------------------------
    const backToTopBtn = document.getElementById('back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.remove('opacity-0', 'invisible', 'scale-75');
                backToTopBtn.classList.add('opacity-100', 'scale-100');
            } else {
                backToTopBtn.classList.remove('opacity-100', 'scale-100');
                backToTopBtn.classList.add('opacity-0', 'invisible', 'scale-75');
            }
        });
    }

    // -------------------------------------------------------------
    // 9. Tool Tabs Switcher (Cek Resi & Cek Ongkir) with GSAP Flip
    // -------------------------------------------------------------
    document.querySelectorAll('.landing-tool-tabs').forEach((tabs) => {
        const section = tabs.closest('.landing-tools-section');
        const buttons = [...tabs.querySelectorAll('[data-tool-tab]')];
        const panels = section ? [...section.querySelectorAll('.landing-tool-panel')] : [];

        const activateTool = (targetId) => {
            const targetButton = buttons.find((item) => item.dataset.toolTab === targetId);
            if (!targetButton) return;

            buttons.forEach((item) => {
                const isActive = item === targetButton;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            
            panels.forEach((panel) => {
                const isTarget = panel.id === targetId;
                panel.classList.toggle('is-active', isTarget);
                if (isTarget) {
                    gsap.fromTo(panel, 
                        { opacity: 0, y: 20, scale: 0.98 }, 
                        { opacity: 1, y: 0, scale: 1, duration: 0.45, ease: 'power2.out' }
                    );
                }
            });
        };

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                activateTool(button.dataset.toolTab);
            });
        });

        const activateFromHash = () => activateTool(window.location.hash.replace('#', ''));
        activateFromHash();
        window.addEventListener('hashchange', activateFromHash);
    });

    // -------------------------------------------------------------
    // 10. Rate Calculator Event Listeners
    // -------------------------------------------------------------
    const currentServiceInput = document.getElementById('calc_service');
    if (currentServiceInput) {
        selectService(currentServiceInput.value || 'REGULAR', false);
    }

    ['origin_kota_id', 'destination_kota_id'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', checkAndAutoCalculate);
    });

    document.getElementById('calc_weight')?.addEventListener('input', triggerAutoCalculateDebounced);
});

// Interactive Particle System Implementation (Optimized with IntersectionObserver)
function initHeroParticles() {
    const canvas = document.getElementById('hero-particles');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let width = canvas.width = canvas.parentElement.clientWidth;
    let height = canvas.height = canvas.parentElement.clientHeight;

    window.addEventListener('resize', () => {
        if (!canvas.parentElement) return;
        width = canvas.width = canvas.parentElement.clientWidth;
        height = canvas.height = canvas.parentElement.clientHeight;
    }, { passive: true });

    const particles = [];
    const numParticles = Math.min(Math.floor(width / 28), 35);

    for (let i = 0; i < numParticles; i++) {
        particles.push({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.6,
            vy: (Math.random() - 0.5) * 0.6,
            radius: Math.random() * 2 + 1,
            color: Math.random() > 0.4 ? '#a6d800' : '#a78bfa',
            alpha: Math.random() * 0.5 + 0.2
        });
    }

    let mouseX = -1000;
    let mouseY = -1000;
    window.addEventListener('mousemove', (e) => {
        const rect = canvas.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;
    }, { passive: true });

    let animFrameId = null;
    let isVisible = false;

    function render() {
        if (!isVisible) return;
        ctx.clearRect(0, 0, width, height);

        for (let i = 0; i < particles.length; i++) {
            const p = particles[i];
            p.x += p.vx;
            p.y += p.vy;

            if (p.x < 0) p.x = width;
            if (p.x > width) p.x = 0;
            if (p.y < 0) p.y = height;
            if (p.y > height) p.y = 0;

            // Draw particle glow
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = p.color;
            ctx.globalAlpha = p.alpha;
            ctx.fill();

            // Draw connection lines between close particles
            for (let j = i + 1; j < particles.length; j++) {
                const p2 = particles[j];
                const dx = p.x - p2.x;
                const dy = p.y - p2.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < 100) {
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(p2.x, p2.y);
                    ctx.strokeStyle = p.color;
                    ctx.globalAlpha = (1 - dist / 100) * 0.18;
                    ctx.lineWidth = 0.6;
                    ctx.stroke();
                }
            }

            // Repel slightly on mouse proximity
            const mdx = p.x - mouseX;
            const mdy = p.y - mouseY;
            const mdist = Math.sqrt(mdx * mdx + mdy * mdy);
            if (mdist < 90) {
                const angle = Math.atan2(mdy, mdx);
                p.x += Math.cos(angle) * 1.2;
                p.y += Math.sin(angle) * 1.2;
            }
        }

        ctx.globalAlpha = 1;
        animFrameId = requestAnimationFrame(render);
    }

    // Auto-pause when Hero canvas is not visible to conserve GPU/CPU
    if ('IntersectionObserver' in window) {
        const particleObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                isVisible = entry.isIntersecting;
                if (isVisible) {
                    if (!animFrameId) render();
                } else {
                    if (animFrameId) {
                        cancelAnimationFrame(animFrameId);
                        animFrameId = null;
                    }
                }
            });
        }, { threshold: 0.01 });

        particleObserver.observe(canvas);
    } else {
        isVisible = true;
        render();
    }
}

// Copy to Clipboard Global Helper
window.copyToClipboard = function (text, btnElement) {
    if (!navigator.clipboard) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showCopyFeedback(btnElement);
        } catch (err) {
            console.error('Fallback copy failed', err);
        }
        document.body.removeChild(textArea);
        return;
    }
    navigator.clipboard.writeText(text).then(() => {
        showCopyFeedback(btnElement);
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
};

function showCopyFeedback(btnElement) {
    if (!btnElement) return;
    const origHTML = btnElement.innerHTML;
    btnElement.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>`;
    const originalTitle = btnElement.getAttribute('title') || 'Salin';
    btnElement.setAttribute('title', 'Tersalin!');
    
    setTimeout(() => {
        btnElement.innerHTML = origHTML;
        btnElement.setAttribute('title', originalTitle);
    }, 1800);
}

// Global Service Selector
window.selectService = function (serviceName, triggerCalc = true) {
    const input = document.getElementById('calc_service');
    if (input) input.value = serviceName;
    
    document.querySelectorAll('.service-card-btn').forEach(btn => {
        const isActive = btn.id === `btn-service-${serviceName}`;
        btn.classList.toggle('is-active', isActive);
    });
    
    if (triggerCalc) {
        checkAndAutoCalculate();
    }
};

// Global Swap Cities Handler
window.swapOriginDestination = function () {
    const originProv = document.getElementById('origin_prov');
    const originKota = document.getElementById('origin_kota_id');
    const destProv = document.getElementById('destination_prov');
    const destKota = document.getElementById('destination_kota_id');

    if (!originProv || !destProv) return;

    const tempProvVal = originProv.value;
    const tempKotaVal = originKota.value;

    const destProvVal = destProv.value;
    const destKotaVal = destKota.value;

    if (!tempProvVal && !destProvVal) return;

    originProv.value = destProvVal;
    destProv.value = tempProvVal;

    const p1 = loadKota('origin_prov', 'origin_kota_id', destKotaVal);
    const p2 = loadKota('destination_prov', 'destination_kota_id', tempKotaVal);

    Promise.all([p1, p2]).then(() => {
        checkAndAutoCalculate();
    });
};

// Global Location Loader
window.loadKota = function (provSelectId, kotaSelectId, selectedKotaId) {
    const provSelect = document.getElementById(provSelectId);
    const kotaSelect = document.getElementById(kotaSelectId);
    
    if (!provSelect || !kotaSelect) return Promise.resolve();

    const provId = provSelect.value;
    if (!provId) {
        kotaSelect.innerHTML = '<option value="">Pilih Provinsi dahulu...</option>';
        return Promise.resolve();
    }

    kotaSelect.innerHTML = '<option value="">Loading...</option>';
    kotaSelect.classList.add('loading-pulse');

    return fetch(`/api/locations/kota?provinsi_id=${provId}`)
        .then(r => r.json())
        .then(kota => {
            kotaSelect.classList.remove('loading-pulse');
            kotaSelect.innerHTML = '<option value="" disabled selected>Pilih Kota/Kabupaten...</option>';
            kota.forEach(k => {
                const opt = document.createElement('option');
                opt.value = k.id;
                opt.textContent = k.name;
                if (selectedKotaId && k.id == selectedKotaId) opt.selected = true;
                kotaSelect.appendChild(opt);
            });
            refreshOrderLink();
            maybeAutoCalculateQuote();
        })
        .catch(() => {
            kotaSelect.classList.remove('loading-pulse');
            kotaSelect.innerHTML = '<option value="">Gagal memuat kota.</option>';
        });
};

let autoQuoteDone = false;
window.maybeAutoCalculateQuote = function () {
    if (window.shouldAutoQuote === false || autoQuoteDone) return;

    const params = quoteParams();
    if (params.origin_kota_id && params.destination_kota_id && params.weight) {
        autoQuoteDone = true;
        calculateOngkir();
    }
};

window.quoteParams = function () {
    return {
        origin_prov: document.getElementById('origin_prov')?.value || '',
        origin_kota_id: document.getElementById('origin_kota_id')?.value || '',
        destination_prov: document.getElementById('destination_prov')?.value || '',
        destination_kota_id: document.getElementById('destination_kota_id')?.value || '',
        weight: document.getElementById('calc_weight')?.value || '',
        service_type: document.getElementById('calc_service')?.value || 'REGULAR',
    };
};

window.refreshOrderLink = function (show = false) {
    const link = document.getElementById('continue-order-link');
    if (!link) return;

    const params = quoteParams();
    const complete = params.origin_prov && params.origin_kota_id && params.destination_prov && params.destination_kota_id && params.weight;
    const query = new URLSearchParams(params);
    const baseUrl = link.dataset.orderRoute || '/order/create';
    link.href = `${baseUrl}?${query.toString()}`;
    link.style.display = show && complete ? 'inline-flex' : 'none';
};

let autoCalcTimeout = null;
window.triggerAutoCalculateDebounced = function () {
    if (autoCalcTimeout) clearTimeout(autoCalcTimeout);
    autoCalcTimeout = setTimeout(() => {
        checkAndAutoCalculate();
    }, 350);
};

window.checkAndAutoCalculate = function () {
    const originKotaInput = document.getElementById('origin_kota_id');
    const destKotaInput   = document.getElementById('destination_kota_id');
    const weightInput     = document.getElementById('calc_weight');
    const serviceInput    = document.getElementById('calc_service');

    if (!originKotaInput || !destKotaInput || !weightInput) return;

    const originKotaId = originKotaInput.value;
    const destKotaId   = destKotaInput.value;
    const weight       = weightInput.value;
    const service      = serviceInput ? serviceInput.value : 'REGULAR';

    const errorAlert = document.getElementById('rate-error-alert');
    const priceSpan = document.getElementById('res-total-price');
    const etdContainer = document.getElementById('res-etd-container');

    if (errorAlert) errorAlert.style.display = 'none';

    if (originKotaId && destKotaId && weight) {
        if (service === 'KARGO' && parseFloat(weight) < 10) {
            showRateError('Layanan KARGO membutuhkan berat minimal 10 kg.');
            if (priceSpan) priceSpan.textContent = 'Rp ---';
            if (etdContainer) etdContainer.style.display = 'none';
            refreshOrderLink(false);
            return;
        }
        calculateOngkir();
    } else {
        if (priceSpan) priceSpan.textContent = 'Rp ---';
        if (etdContainer) etdContainer.style.display = 'none';
        refreshOrderLink(false);
    }
};

window.showRateError = function (message) {
    const errorAlert = document.getElementById('rate-error-alert');
    const errorText = document.getElementById('rate-error-text');
    if (errorAlert && errorText) {
        let cleanMsg = message;
        if (message.includes('MINIMAL_BERAT_10KG')) {
            cleanMsg = 'Layanan KARGO membutuhkan berat minimal 10 kg.';
        } else if (message.includes('Rute tidak tersedia')) {
            cleanMsg = 'Rute pengiriman tidak didukung saat ini.';
        }
        errorText.textContent = cleanMsg;
        errorAlert.style.display = 'flex';
    }
};

window.calculateOngkir = function () {
    const originKotaId = document.getElementById('origin_kota_id')?.value;
    const destKotaId   = document.getElementById('destination_kota_id')?.value;
    const weight       = document.getElementById('calc_weight')?.value;
    const service      = document.getElementById('calc_service')?.value;

    if (!originKotaId || !destKotaId || !weight) return;

    const priceSpan = document.getElementById('res-total-price');
    const etdContainer = document.getElementById('res-etd-container');
    const etdSpan = document.getElementById('res-etd');
    const loadingOverlay = document.getElementById('rate-loading-overlay');
    const errorAlert = document.getElementById('rate-error-alert');

    if (errorAlert) errorAlert.style.display = 'none';

    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
        loadingOverlay.style.opacity = '1';
    }

    if (priceSpan) priceSpan.classList.add('loading-pulse');

    fetch(`/api/calculate-rate?origin_kota_id=${originKotaId}&destination_kota_id=${destKotaId}&weight=${weight}&service_type=${service}`)
        .then(response => response.json())
        .then(data => {
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.style.display = 'none', 200);
            }
            if (priceSpan) {
                priceSpan.classList.remove('loading-pulse');
            }

            if (data.error) {
                showRateError(data.error);
                if (priceSpan) priceSpan.textContent = 'Rp ---';
                if (etdContainer) etdContainer.style.display = 'none';
                refreshOrderLink(false);
                return;
            }
            if (priceSpan) priceSpan.textContent = data.total_price_fmt;
            if (etdSpan) etdSpan.textContent = data.estimated_days ? `${data.estimated_days} HARI` : 'DARI KURIR';
            if (etdContainer) etdContainer.style.display = 'block';
            refreshOrderLink(true);
        })
        .catch(err => {
            console.error(err);
            if (loadingOverlay) {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => loadingOverlay.style.display = 'none', 200);
            }
            if (priceSpan) {
                priceSpan.classList.remove('loading-pulse');
                priceSpan.textContent = 'Rp ---';
            }
            refreshOrderLink(false);
            showRateError('Gagal mengambil data ongkir dari server.');
        });
};
