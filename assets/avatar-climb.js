/**
 * Avatar Climb Animation - Fixed Size (not affected by font size)
 */

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import gsap from 'gsap';

export class AvatarClimbAnimation {
    constructor(containerId, avatarPath) {
        this.container = document.getElementById(containerId);
        this.avatarPath = avatarPath;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.avatar = null;
        this.mixer = null;
        this.clock = new THREE.Clock();
        
        if (this.container) {
            this.init();
        }
    }

    async init() {
        this.setupScene();
        await this.loadAvatar();
        this.startClimbAnimation();
        this.animate();
    }

    setupScene() {
        // FIXED SIZE - use container's actual pixel dimensions
        const rect = this.container.getBoundingClientRect();
        const width = rect.width || 128;
        const height = rect.height || 128;
        
        this.scene = new THREE.Scene();
        this.scene.background = null;
        
        // Camera with fixed aspect ratio
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
        this.camera.position.set(0, 0, 2.5);
        this.camera.lookAt(0, 0, 0);
        
        // Renderer with fixed size
        this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.container.appendChild(this.renderer.domElement);
        
        // Lighting
        const ambient = new THREE.AmbientLight(0xffffff, 0.9);
        this.scene.add(ambient);
        
        const main = new THREE.DirectionalLight(0xffffff, 1.2);
        main.position.set(2, 4, 5);
        this.scene.add(main);
        
        const fill = new THREE.DirectionalLight(0xc7d2fe, 0.5);
        fill.position.set(-2, 1, 3);
        this.scene.add(fill);
        
        // Handle resize
        window.addEventListener('resize', () => this.onResize());
    }

    async loadAvatar() {
        return new Promise((resolve, reject) => {
            const loader = new GLTFLoader();
            
            loader.load(this.avatarPath, 
                (gltf) => {
                    this.avatar = gltf.scene;
                    
                    // FIXED scale - doesn't change with font size
                    this.avatar.scale.set(1.2, 1.2, 1.2);
                    
                    // Start below container
                    this.avatar.position.set(0, -4, 0);
                    this.avatar.rotation.y = 0;
                    
                    // Fade in
                    this.avatar.traverse((child) => {
                        if (child.isMesh) {
                            child.material.transparent = true;
                            child.material.opacity = 0;
                        }
                    });
                    
                    this.scene.add(this.avatar);
                    
                    if (gltf.animations?.length > 0) {
                        this.mixer = new THREE.AnimationMixer(this.avatar);
                        this.mixer.clipAction(gltf.animations[0]).play();
                    }
                    
                    resolve();
                },
                undefined,
                (error) => {
                    // Fallback to male avatar
                    if (this.avatarPath !== '/avatars/male-avatar.glb') {
                        this.avatarPath = '/avatars/male-avatar.glb';
                        loader.load(this.avatarPath, 
                            (gltf) => {
                                this.avatar = gltf.scene;
                                this.avatar.scale.set(1.2, 1.2, 1.2);
                                this.avatar.position.set(0, -4, 0);
                                this.avatar.rotation.y = 0;
                                this.avatar.traverse((child) => {
                                    if (child.isMesh) {
                                        child.material.transparent = true;
                                        child.material.opacity = 0;
                                    }
                                });
                                this.scene.add(this.avatar);
                                resolve();
                            },
                            undefined,
                            reject
                        );
                    } else {
                        reject(error);
                    }
                }
            );
        });
    }

    startClimbAnimation() {
        const tl = gsap.timeline();
        
        // Fade out initials
        tl.call(() => {
            const initials = document.getElementById('profile-initials');
            if (initials) {
                gsap.to(initials, { opacity: 0, duration: 1, ease: 'power2.out' });
            }
        }, null, 0.5);
        
        // Fade in avatar
        tl.call(() => {
            this.avatar.traverse((child) => {
                if (child.isMesh) {
                    gsap.to(child.material, { opacity: 1, duration: 1.2, ease: 'sine.out' });
                }
            });
        }, null, 0.8);
        
        // Rise to center - FIXED position
        tl.to(this.avatar.position, {
            y: -1.2,
            duration: 4,
            ease: 'sine.out'
        }, 1);
        
        // Small head tilt
        tl.to(this.avatar.rotation, {
            z: -0.05,
            duration: 1.5,
            ease: 'sine.inOut',
            yoyo: true,
            repeat: 1
        }, '-=2');
    }

    animate() {
        requestAnimationFrame(() => this.animate());
        if (this.mixer) {
            this.mixer.update(this.clock.getDelta());
        }
        this.renderer.render(this.scene, this.camera);
    }

    onResize() {
        if (!this.container) return;
        const rect = this.container.getBoundingClientRect();
        const w = rect.width || 128;
        const h = rect.height || 128;
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    }
}

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('avatar-climb-container');
    if (container) {
        const avatarType = container.dataset.avatarType || 'male-avatar.glb';
        window.avatarClimb = new AvatarClimbAnimation('avatar-climb-container', `/avatars/${avatarType}`);
    }
});
