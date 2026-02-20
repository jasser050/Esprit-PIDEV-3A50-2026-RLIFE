/**
 * Galaxy Landing — Full-screen hero galaxy portal for the landing page.
 * Replaces the hero background with an interactive 3D spiral galaxy.
 * Clicking "Get Started" / CTA triggers a warp fly-through.
 */

import { GalaxyCore } from './galaxy-core.js';

document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('galaxy-landing-canvas');
    if (!wrapper) return;

    // ── Boot the galaxy ────────────────────────────────────────────────────────
    const galaxy = new GalaxyCore(wrapper, {
        starCount:       110000,
        arms:            6,
        armTightness:    0.42,
        coreRadius:      2.0,
        galaxyRadius:    15.0,
        coreStarFrac:    0.20,
        nebulaCount:     32,
        enableControls:  true,
        autoRotate:      true,
        autoRotateSpeed: 0.10,
        fov:             62,
        background:      0x00000a,
    });

    // ── Overlay text fade-in ────────────────────────────────────────────────────
    const heroContent = document.getElementById('galaxy-hero-content');
    if (heroContent) {
        heroContent.style.opacity = '0';
        heroContent.style.transform = 'translateY(30px)';
        heroContent.style.transition = 'opacity 1.4s ease 0.6s, transform 1.4s ease 0.6s';
        setTimeout(() => {
            heroContent.style.opacity = '1';
            heroContent.style.transform = 'translateY(0)';
        }, 100);
    }

    // ── CTA button — warp portal fly-through ─────────────────────────────────
    const ctaButtons = document.querySelectorAll('[data-galaxy-cta]');
    ctaButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const href = btn.getAttribute('href') || btn.dataset.galaxyCta;

            // Add warp flash overlay
            const flash = document.createElement('div');
            flash.style.cssText = `
                position: fixed; inset: 0; z-index: 9999;
                background: radial-gradient(circle at center, rgba(255,255,255,0.0), transparent);
                pointer-events: none; transition: background 0.3s;
            `;
            document.body.appendChild(flash);

            galaxy.flyThrough(() => {
                // White flash at peak warp
                flash.style.background = 'radial-gradient(circle at center, rgba(180,210,255,0.85), rgba(100,150,255,0.4) 40%, transparent 75%)';
                flash.style.transition = 'background 0.15s';
                setTimeout(() => {
                    flash.style.background = 'white';
                    flash.style.transition = 'background 0.25s';
                    setTimeout(() => {
                        if (href) window.location.href = href;
                    }, 280);
                }, 150);
            });
        });
    });

    // ── Shooting stars ─────────────────────────────────────────────────────────
    _spawnShootingStars(galaxy);

    window._galaxyLanding = galaxy;
});

// ── Shooting star effect ───────────────────────────────────────────────────────

function _spawnShootingStars(galaxy) {
    if (!galaxy || !galaxy.scene) return;

    const THREE = galaxy.scene.constructor.__THREE || null;
    // Re-import Three to create shooting stars
    import('three').then(THREE => {
        function spawnOne() {
            if (!galaxy.scene) return;

            const startX = (Math.random() - 0.5) * 30;
            const startY = Math.random() * 8 + 2;
            const startZ = (Math.random() - 0.5) * 20;
            const len    = Math.random() * 4 + 2;
            const angle  = Math.random() * Math.PI * 2;

            const points = [
                new THREE.Vector3(startX, startY, startZ),
                new THREE.Vector3(
                    startX + Math.cos(angle) * len,
                    startY - len * 0.4,
                    startZ + Math.sin(angle) * len
                ),
            ];

            const geo = new THREE.BufferGeometry().setFromPoints(points);
            const mat = new THREE.LineBasicMaterial({
                color:       0xaad4ff,
                transparent: true,
                opacity:     0.9,
                blending:    THREE.AdditiveBlending,
                depthWrite:  false,
            });
            const line = new THREE.Line(geo, mat);
            galaxy.scene.add(line);

            // Animate fade out
            const start   = performance.now();
            const fadeMs  = 500 + Math.random() * 400;
            const travel  = len * 1.5;

            const tick = (now) => {
                const t = Math.min((now - start) / fadeMs, 1);
                mat.opacity = (1 - t) * 0.9;
                line.position.x += Math.cos(angle) * travel * 0.016;
                line.position.y -= travel * 0.4 * 0.016;
                line.position.z += Math.sin(angle) * travel * 0.016;

                if (t < 1) {
                    requestAnimationFrame(tick);
                } else {
                    galaxy.scene.remove(line);
                    geo.dispose();
                    mat.dispose();
                }
            };
            requestAnimationFrame(tick);
        }

        // Spawn randomly every 2–5 seconds
        const scheduleNext = () => {
            const delay = 2000 + Math.random() * 3000;
            setTimeout(() => {
                spawnOne();
                scheduleNext();
            }, delay);
        };
        scheduleNext();
    });
}
