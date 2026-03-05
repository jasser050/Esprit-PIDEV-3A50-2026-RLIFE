class AvatarClimbRuntime {
    constructor(containerId, avatarPath, deps) {
        this.container = document.getElementById(containerId);
        this.avatarPath = avatarPath;
        this.THREE = deps.THREE;
        this.GLTFLoader = deps.GLTFLoader;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.avatar = null;
        this.mixer = null;
        this.timer = typeof this.THREE.Timer === 'function' ? new this.THREE.Timer() : null;
        this.clock = this.timer ? null : new this.THREE.Clock();
        this.rafId = null;
        this.destroyed = false;
        this.onResizeBound = () => this.onResize();
    }

    async init() {
        if (!this.container) return;
        this.setupScene();
        await this.loadAvatar();
        this.startEntranceAnimation();
        this.renderLoop();
    }

    setupScene() {
        const THREE = this.THREE;
        const rect = this.container.getBoundingClientRect();
        const width = rect.width || 128;
        const height = rect.height || 128;

        this.scene = new THREE.Scene();
        this.scene.background = null;

        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        this.camera.position.set(0, 0, 2.5);
        this.camera.lookAt(0, 0, 0);

        this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setClearColor(0x000000, 0);
        this.container.appendChild(this.renderer.domElement);

        const ambient = new THREE.AmbientLight(0xffffff, 0.9);
        const main = new THREE.DirectionalLight(0xffffff, 1.2);
        const fill = new THREE.DirectionalLight(0xc7d2fe, 0.5);
        main.position.set(2, 4, 5);
        fill.position.set(-2, 1, 3);
        this.scene.add(ambient, main, fill);

        window.addEventListener('resize', this.onResizeBound);
    }

    async loadAvatar() {
        const THREE = this.THREE;
        const loader = new this.GLTFLoader();

        const tryLoad = (path) => new Promise((resolve, reject) => {
            loader.load(path, resolve, undefined, reject);
        });

        let gltf;
        try {
            gltf = await tryLoad(this.avatarPath);
        } catch (error) {
            if (this.avatarPath !== '/avatars/male-avatar.glb') {
                gltf = await tryLoad('/avatars/male-avatar.glb');
            } else {
                throw error;
            }
        }

        this.avatar = gltf.scene;
        this.avatar.scale.set(1.2, 1.2, 1.2);
        this.avatar.position.set(0, -4, 0);
        this.avatar.rotation.y = 0;

        this.avatar.traverse((child) => {
            if (!child.isMesh || !child.material) return;
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach((material) => {
                material.transparent = true;
                material.opacity = 0;
            });
        });

        this.scene.add(this.avatar);

        if (gltf.animations && gltf.animations.length > 0) {
            this.mixer = new THREE.AnimationMixer(this.avatar);
            this.mixer.clipAction(gltf.animations[0]).play();
        }
    }

    startEntranceAnimation() {
        const initials = document.getElementById('profile-initials');
        if (initials) {
            initials.style.transition = 'opacity 900ms ease-out';
            initials.style.opacity = '0';
        }

        const start = performance.now();
        const duration = 1500;
        const fromY = -4;
        const toY = -1.2;

        const easeOut = (t) => 1 - Math.pow(1 - t, 3);

        const tick = (now) => {
            if (this.destroyed || !this.avatar) return;
            const progress = Math.min((now - start) / duration, 1);
            const eased = easeOut(progress);
            this.avatar.position.y = fromY + (toY - fromY) * eased;
            this.avatar.rotation.z = Math.sin(eased * Math.PI) * -0.05;

            this.avatar.traverse((child) => {
                if (!child.isMesh || !child.material) return;
                const materials = Array.isArray(child.material) ? child.material : [child.material];
                materials.forEach((material) => {
                    material.opacity = eased;
                });
            });

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    }

    renderLoop() {
        if (this.destroyed) return;
        this.rafId = requestAnimationFrame(() => this.renderLoop());

        if (this.mixer) {
            if (this.timer) {
                this.timer.update();
                this.mixer.update(this.timer.getDelta());
            } else if (this.clock) {
                this.mixer.update(this.clock.getDelta());
            }
        }

        if (this.renderer && this.scene && this.camera) {
            this.renderer.render(this.scene, this.camera);
        }
    }

    onResize() {
        if (!this.container || !this.camera || !this.renderer) return;
        const rect = this.container.getBoundingClientRect();
        const w = rect.width || 128;
        const h = rect.height || 128;
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    }

    destroy() {
        this.destroyed = true;
        window.removeEventListener('resize', this.onResizeBound);

        if (this.rafId !== null) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }

        if (this.mixer) {
            this.mixer.stopAllAction();
            this.mixer = null;
        }

        if (this.scene) {
            this.scene.traverse((obj) => {
                if (!obj.isMesh) return;
                if (obj.geometry) obj.geometry.dispose();
                if (!obj.material) return;
                const materials = Array.isArray(obj.material) ? obj.material : [obj.material];
                materials.forEach((material) => material.dispose && material.dispose());
            });
        }

        if (this.renderer) {
            this.renderer.dispose();
            if (this.renderer.domElement && this.renderer.domElement.parentNode) {
                this.renderer.domElement.parentNode.removeChild(this.renderer.domElement);
            }
            this.renderer = null;
        }
    }
}

let runtime = null;
let initPromise = null;
let retryTimer = null;

async function loadDeps() {
    const [THREE, loaderModule] = await Promise.all([
        import('/vendor/three/build/three.module.min.js'),
        import('/vendor/three/examples/jsm/loaders/GLTFLoader.local.js'),
    ]);
    return { THREE, GLTFLoader: loaderModule.GLTFLoader };
}

function cleanup() {
    if (retryTimer) {
        clearTimeout(retryTimer);
        retryTimer = null;
    }
    if (runtime) {
        runtime.destroy();
        runtime = null;
    }
}

async function initNow() {
    const container = document.getElementById('avatar-climb-container');
    if (!container) return;

    cleanup();

    if (initPromise) {
        try { await initPromise; } catch (e) {}
    }

    const avatarType = container.dataset.avatarType || 'male-avatar.glb';
    initPromise = loadDeps()
        .then((deps) => {
            runtime = new AvatarClimbRuntime('avatar-climb-container', `/avatars/${avatarType}`, deps);
            return runtime.init();
        })
        .catch((error) => {
            console.error('[AvatarClimbRuntime] init failed:', error);
        })
        .finally(() => {
            initPromise = null;
        });

    await initPromise;
}

function scheduleInit() {
    if (retryTimer) {
        clearTimeout(retryTimer);
        retryTimer = null;
    }

    const attemptInit = async (attempt = 0) => {
        const container = document.getElementById('avatar-climb-container');
        if (container) {
            await initNow();
            return;
        }

        if (attempt >= 12) return;
        retryTimer = setTimeout(() => {
            attemptInit(attempt + 1);
        }, 100);
    };

    requestAnimationFrame(() => {
        attemptInit(0);
    });
}

document.addEventListener('DOMContentLoaded', scheduleInit);
document.addEventListener('turbo:render', scheduleInit);
document.addEventListener('turbo:load', scheduleInit);
document.addEventListener('turbo:before-cache', cleanup);
