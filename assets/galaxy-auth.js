/**
 * Galaxy Auth — Two features:
 *   1. Login: warp portal overlay on form submit
 *   2. Welcome: dramatic 4-phase star birth cinematic
 */

import * as THREE from 'three';

// ═══════════════════════════════════════════════════════════════════════════════
// 1. LOGIN PORTAL OVERLAY
// ═══════════════════════════════════════════════════════════════════════════════

function initLoginPortal() {
    const form = document.getElementById('login-form')
               || document.querySelector('form[action*="login"]')
               || document.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        const emailInput    = form.querySelector('input[name="_username"], input[type="email"]');
        const passwordInput = form.querySelector('input[name="_password"], input[type="password"]');
        if (!emailInput?.value || !passwordInput?.value) return;

        e.preventDefault();

        const overlay = document.createElement('div');
        overlay.id    = 'galaxy-portal-overlay';
        overlay.style.cssText = `
            position:fixed; inset:0; z-index:99999;
            background:#00000c; overflow:hidden;
            display:flex; align-items:center; justify-content:center;
        `;

        const canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:absolute; inset:0; width:100%; height:100%;';
        overlay.appendChild(canvas);

        const label = document.createElement('div');
        label.style.cssText = `
            position:relative; z-index:10; color:#c7d2fe;
            font-family:system-ui,sans-serif; font-size:1.1rem;
            letter-spacing:0.28em; text-transform:uppercase;
            opacity:0; transition:opacity 0.5s;
            text-shadow:0 0 24px rgba(139,92,246,0.9), 0 0 48px rgba(99,102,241,0.5);
        `;
        label.textContent = 'Entering your universe…';
        overlay.appendChild(label);
        document.body.appendChild(overlay);
        setTimeout(() => { label.style.opacity = '1'; }, 300);

        const scene    = new THREE.Scene();
        scene.background = new THREE.Color(0x00000c);
        const W = window.innerWidth, H = window.innerHeight;
        const camera   = new THREE.PerspectiveCamera(65, W / H, 0.1, 500);
        camera.position.set(0, 5, 18);
        camera.lookAt(0, 0, 0);
        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
        renderer.setSize(W, H);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        _buildPortalGalaxy(scene, 60000);

        let startTime = null;
        const WARP_MS = 2000;

        const animate = (ts) => {
            if (!startTime) startTime = ts;
            const t    = Math.min((ts - startTime) / WARP_MS, 1);
            const ease = t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;

            camera.position.z = 18 - ease * 36;
            camera.fov        = 65 + ease * 65;
            camera.updateProjectionMatrix();
            renderer.render(scene, camera);

            if (t < 1) {
                requestAnimationFrame(animate);
            } else {
                overlay.style.background = 'white';
                overlay.style.transition = 'background 0.18s';
                setTimeout(() => form.submit(), 220);
            }
        };
        requestAnimationFrame(animate);
    });
}

function _buildPortalGalaxy(scene, count) {
    const pos    = new Float32Array(count * 3);
    const colors = new Float32Array(count * 3);
    const sizes  = new Float32Array(count);
    const tmp    = new THREE.Color();
    const palette = [0xa5b4fc, 0x818cf8, 0x60a5fa, 0x93c5fd, 0xc4b5fd, 0xfef3c7, 0xfde68a, 0xe879f9];

    for (let i = 0; i < count; i++) {
        const arm   = Math.floor(Math.random() * 5);
        const t     = Math.pow(Math.random(), 0.55);
        const r     = 0.7 + t * 14;
        const theta = (Math.log(r / 0.45) / 0.42) + (arm / 5) * Math.PI * 2;
        const sc    = (Math.random() - 0.5) * 1.8;

        pos[i*3]     = r * Math.cos(theta) + sc * 0.22;
        pos[i*3 + 1] = (Math.random() - 0.5) * 0.28;
        pos[i*3 + 2] = r * Math.sin(theta) + sc * 0.22;

        tmp.setHex(palette[Math.floor(Math.random() * palette.length)]);
        colors[i*3]     = tmp.r;
        colors[i*3 + 1] = tmp.g;
        colors[i*3 + 2] = tmp.b;
        sizes[i] = 1.8 + Math.random() * 4.5;
    }

    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    geo.setAttribute('color',    new THREE.BufferAttribute(colors, 3));
    geo.setAttribute('size',     new THREE.BufferAttribute(sizes,  1));

    const mat = new THREE.ShaderMaterial({
        uniforms: { uPixelRatio: { value: window.devicePixelRatio } },
        vertexShader: `
            attribute float size; attribute vec3 color; varying vec3 vColor;
            uniform float uPixelRatio;
            void main() {
                vColor = color;
                vec4 mv = modelViewMatrix * vec4(position,1.0);
                gl_PointSize = size * uPixelRatio * (280.0 / -mv.z);
                gl_Position  = projectionMatrix * mv;
            }`,
        fragmentShader: `
            varying vec3 vColor;
            void main() {
                float d = length(gl_PointCoord - 0.5) * 2.0;
                float a = 1.0 - smoothstep(0.0, 1.0, d);
                if (a < 0.02) discard;
                gl_FragColor = vec4(vColor * 2.0, a * 0.95);
            }`,
        transparent: true, depthWrite: false,
        blending: THREE.AdditiveBlending, vertexColors: true,
    });
    scene.add(new THREE.Points(geo, mat));

    // Bright core glow
    const gc = document.createElement('canvas');
    gc.width  = gc.height = 256;
    const gCtx = gc.getContext('2d');
    const grd  = gCtx.createRadialGradient(128, 128, 0, 128, 128, 128);
    grd.addColorStop(0,   'rgba(255,248,200,1)');
    grd.addColorStop(0.2, 'rgba(255,220,120,0.8)');
    grd.addColorStop(0.5, 'rgba(180,120,255,0.3)');
    grd.addColorStop(1,   'rgba(0,0,0,0)');
    gCtx.fillStyle = grd;
    gCtx.fillRect(0, 0, 256, 256);

    const glow = new THREE.Sprite(new THREE.SpriteMaterial({
        map: new THREE.CanvasTexture(gc),
        blending: THREE.AdditiveBlending, transparent: true, depthWrite: false, opacity: 0.85,
    }));
    glow.scale.set(7, 7, 1);
    scene.add(glow);
}

// ═══════════════════════════════════════════════════════════════════════════════
// 2. WELCOME — STAR BIRTH CINEMATIC
//    4 phases: dust cloud → ignition → corona → supernova burst
// ═══════════════════════════════════════════════════════════════════════════════

function initStarBirth() {
    const canvas = document.getElementById('star-birth-canvas');
    if (!canvas) return;

    const W = canvas.clientWidth  || window.innerWidth;
    const H = canvas.clientHeight || window.innerHeight;

    const scene    = new THREE.Scene();
    scene.background = new THREE.Color(0x00000c);

    const camera   = new THREE.PerspectiveCamera(60, W / H, 0.1, 300);
    camera.position.set(0, 0, 12);
    camera.lookAt(0, 0, 0);

    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
    renderer.setSize(W, H);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    _addBgStars(scene);
    const dust  = _buildDustCloud(scene);
    const star  = _buildBirthStar(scene);
    const rays  = _buildCoronaRays(scene);
    const bursts = [];
    const rings  = [];

    const startMs  = performance.now();
    const starNameEl = document.getElementById('star-birth-name');
    const starTypeEl = document.getElementById('star-birth-type');
    let phase3Done   = false;
    let phase4Done   = false;

    function animate(ts) {
        requestAnimationFrame(animate);
        const elapsed = (ts - startMs) / 1000;

        // Phase 1: dust swirls inward (0–2.5s)
        if (elapsed < 2.5) {
            const t = elapsed / 2.5;
            dust.forEach(p => {
                const angle = p.userData.angle + t * p.userData.speed * 2.2;
                const r     = p.userData.radius * (1 - t * 0.65);
                p.position.x = Math.cos(angle) * r;
                p.position.z = Math.sin(angle) * r;
                p.position.y = p.userData.yBase * (1 - t * 0.75);
                p.material.opacity = 0.2 + t * 0.3;
            });
        }

        // Phase 2: ignition (2.5–4.0s)
        if (elapsed >= 2.5 && elapsed < 4.0) {
            const t = (elapsed - 2.5) / 1.5;
            star.scale.setScalar(t * 0.7);
            star.material.opacity = t * 0.9;
            star.material.color.setHSL(0.03 + t * 0.07, 1.0, 0.50 + t * 0.22);
            dust.forEach(p => { p.material.opacity = Math.max(0, 0.5 - t * 0.5); });
        }

        // Phase 3: corona + rays (4.0–5.8s)
        if (elapsed >= 4.0 && elapsed < 5.8) {
            const t     = (elapsed - 4.0) / 1.8;
            const pulse = 0.65 + 0.10 * Math.sin(elapsed * 9);
            star.scale.setScalar(pulse);
            star.material.opacity = 0.88 + 0.12 * Math.sin(elapsed * 6);
            star.material.color.setHSL(0.12 + t * 0.06, 1.0, 0.72);

            // Animate corona rays
            rays.forEach((ray, i) => {
                ray.material.opacity = t * 0.7 * (0.6 + 0.4 * Math.sin(elapsed * 4 + i));
                const s = t * (0.8 + 0.25 * Math.sin(elapsed * 3 + i));
                ray.scale.set(s, s, 1);
            });

            if (!phase3Done && t > 0.5) {
                phase3Done = true;
                if (starNameEl) { starNameEl.style.opacity = '1'; starNameEl.style.transform = 'translateY(0)'; }
                if (starTypeEl) {
                    setTimeout(() => { starTypeEl.style.opacity = '1'; starTypeEl.style.transform = 'translateY(0)'; }, 400);
                }
            }
        }

        // Phase 4: supernova (5.8–7.5s)
        if (elapsed >= 5.8 && elapsed < 7.5) {
            const t = (elapsed - 5.8) / 1.7;
            if (!phase4Done) {
                phase4Done = true;
                _triggerBurst(scene, bursts);
                _triggerRings(scene, rings);
            }

            const eOut = 1 - Math.pow(1 - t, 3);
            star.scale.setScalar(0.65 + eOut * 2.2);
            star.material.opacity = 1.0 - t * 0.35;
            star.material.color.setHSL(0.13, 1.0, 0.80 + t * 0.10);

            rays.forEach(ray => { ray.material.opacity = Math.max(0, 0.7 - t * 0.9); });

            bursts.forEach(b => {
                if (!b.userData.vel) return;
                b.position.addScaledVector(b.userData.vel, 0.14);
                b.material.opacity = Math.max(0, 0.95 - t * 1.2);
            });
            rings.forEach(ring => {
                ring.userData.r = (ring.userData.r || 0) + 0.08;
                ring.scale.setScalar(ring.userData.r);
                ring.material.opacity = Math.max(0, 0.6 - t * 0.8);
            });
        }

        // Idle glow (7.5s+)
        if (elapsed >= 7.5) {
            const pulse = 0.72 + 0.08 * Math.sin(elapsed * 3.2);
            star.scale.setScalar(pulse);
            star.material.opacity = 0.90 + 0.10 * Math.sin(elapsed * 5);
            rays.forEach((ray, i) => {
                ray.material.opacity = 0.25 + 0.12 * Math.sin(elapsed * 2.5 + i);
            });
        }

        renderer.render(scene, camera);
    }
    requestAnimationFrame(animate);

    window.addEventListener('resize', () => {
        const nW = canvas.clientWidth, nH = canvas.clientHeight;
        if (!nW || !nH) return;
        camera.aspect = nW / nH;
        camera.updateProjectionMatrix();
        renderer.setSize(nW, nH);
    });
}

// ── Scene helpers ─────────────────────────────────────────────────────────────

function _addBgStars(scene) {
    const count = 6000;
    const pos   = new Float32Array(count * 3);
    for (let i = 0; i < count; i++) {
        const theta = Math.random() * Math.PI * 2;
        const phi   = Math.acos(2 * Math.random() - 1);
        const r     = 70 + Math.random() * 90;
        pos[i*3]     = r * Math.sin(phi) * Math.cos(theta);
        pos[i*3 + 1] = r * Math.cos(phi);
        pos[i*3 + 2] = r * Math.sin(phi) * Math.sin(theta);
    }
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    scene.add(new THREE.Points(geo, new THREE.PointsMaterial({
        size: 0.22, color: 0xddeeff, transparent: true, opacity: 0.75,
        blending: THREE.AdditiveBlending, depthWrite: false,
    })));
}

function _buildDustCloud(scene) {
    const particles = [];
    for (let i = 0; i < 380; i++) {
        const angle  = Math.random() * Math.PI * 2;
        const radius = 1.6 + Math.random() * 5.0;
        const hue    = Math.floor(220 + Math.random() * 80);

        const c    = document.createElement('canvas');
        c.width    = c.height = 64;
        const ctx  = c.getContext('2d');
        const grad = ctx.createRadialGradient(32, 32, 0, 32, 32, 32);
        grad.addColorStop(0,   `hsla(${hue},75%,72%,1)`);
        grad.addColorStop(0.5, `hsla(${hue},65%,55%,0.4)`);
        grad.addColorStop(1,   'transparent');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 64, 64);

        const sp = new THREE.Sprite(new THREE.SpriteMaterial({
            map: new THREE.CanvasTexture(c),
            blending: THREE.AdditiveBlending, transparent: true, depthWrite: false, opacity: 0.15,
        }));
        const sz = 0.9 + Math.random() * 2.0;
        sp.scale.set(sz, sz, 1);
        const yBase = (Math.random() - 0.5) * 2.2;
        sp.position.set(Math.cos(angle) * radius, yBase, Math.sin(angle) * radius);
        sp.userData = { angle, radius, yBase, speed: 0.5 + Math.random() * 1.0 };
        scene.add(sp);
        particles.push(sp);
    }
    return particles;
}

function _buildBirthStar(scene) {
    const c    = document.createElement('canvas');
    c.width    = c.height = 256;
    const ctx  = c.getContext('2d');
    const grad = ctx.createRadialGradient(128, 128, 0, 128, 128, 128);
    grad.addColorStop(0,   'rgba(255,255,255,1)');
    grad.addColorStop(0.1, 'rgba(255,252,210,0.98)');
    grad.addColorStop(0.3, 'rgba(255,200,80,0.7)');
    grad.addColorStop(0.6, 'rgba(255,120,30,0.3)');
    grad.addColorStop(1,   'transparent');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, 256, 256);

    const star = new THREE.Sprite(new THREE.SpriteMaterial({
        map: new THREE.CanvasTexture(c),
        blending: THREE.AdditiveBlending, transparent: true, depthWrite: false,
        opacity: 0, color: new THREE.Color(1, 0.5, 0.2),
    }));
    star.scale.set(0.01, 0.01, 1);
    scene.add(star);
    return star;
}

function _buildCoronaRays(scene) {
    const rays = [];
    const rayCount = 12;
    for (let i = 0; i < rayCount; i++) {
        const angle = (i / rayCount) * Math.PI * 2;
        const len   = 1.4 + (i % 3 === 0 ? 0.8 : 0) + Math.random() * 0.6;
        const c    = document.createElement('canvas');
        c.width    = 16;
        c.height   = 128;
        const ctx  = c.getContext('2d');
        const grad = ctx.createLinearGradient(0, 128, 0, 0);
        grad.addColorStop(0.0, 'rgba(255,240,160,1.0)');
        grad.addColorStop(0.4, 'rgba(255,200,80,0.5)');
        grad.addColorStop(1.0, 'rgba(255,180,50,0.0)');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, 16, 128);

        const sp = new THREE.Sprite(new THREE.SpriteMaterial({
            map: new THREE.CanvasTexture(c),
            blending: THREE.AdditiveBlending, transparent: true, depthWrite: false, opacity: 0,
        }));
        sp.scale.set(0.12, len, 1);
        sp.position.set(Math.cos(angle) * len * 0.5, Math.sin(angle) * len * 0.5, 0);
        sp.material.rotation = angle + Math.PI / 2;
        scene.add(sp);
        rays.push(sp);
    }
    return rays;
}

function _triggerBurst(scene, bursts) {
    const count  = 240;
    const colors = [0xff9900, 0xffcc44, 0xff6622, 0xffeeaa, 0xff4400, 0xfff8aa, 0xffffff];

    for (let i = 0; i < count; i++) {
        const theta = Math.random() * Math.PI * 2;
        const phi   = Math.acos(2 * Math.random() - 1);
        const speed = 0.08 + Math.random() * 0.22;
        const vel   = new THREE.Vector3(
            Math.sin(phi) * Math.cos(theta) * speed,
            Math.cos(phi)                   * speed,
            Math.sin(phi) * Math.sin(theta) * speed
        );

        const geo = new THREE.SphereGeometry(0.05 + Math.random() * 0.08, 4, 4);
        const mat = new THREE.MeshBasicMaterial({
            color: colors[Math.floor(Math.random() * colors.length)],
            transparent: true, opacity: 0.95,
            blending: THREE.AdditiveBlending, depthWrite: false,
        });
        const mesh  = new THREE.Mesh(geo, mat);
        mesh.userData.vel = vel;
        scene.add(mesh);
        bursts.push(mesh);
    }
}

function _triggerRings(scene, rings) {
    const ringColors = [0xffd966, 0xff9966, 0xcc88ff, 0x66ccff];
    ringColors.forEach((col, i) => {
        const geo = new THREE.RingGeometry(0.8, 1.0, 48);
        const mat = new THREE.MeshBasicMaterial({
            color: col, transparent: true, opacity: 0.6, side: THREE.DoubleSide,
            blending: THREE.AdditiveBlending, depthWrite: false,
        });
        const ring  = new THREE.Mesh(geo, mat);
        ring.rotation.x = Math.random() * Math.PI;
        ring.rotation.y = Math.random() * Math.PI;
        ring.userData.r = 1.0 + i * 0.3;
        scene.add(ring);
        rings.push(ring);
    });
}

// ═══════════════════════════════════════════════════════════════════════════════
// Boot
// ═══════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    initLoginPortal();
    initStarBirth();
});
