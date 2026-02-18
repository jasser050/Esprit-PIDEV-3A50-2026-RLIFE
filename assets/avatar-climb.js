/**
 * 🎭 Avatar Climb Animation System
 * Creates a cute climbing animation for 3D avatar on settings page
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
        // Create Three.js scene
        this.setupScene();
        
        // Load avatar model
        await this.loadAvatar();
        
        // Start animation sequence
        this.startClimbAnimation();
        
        // Start render loop
        this.animate();
    }

    setupScene() {
        // Get container dimensions
        const rect = this.container.getBoundingClientRect();
        
        // Create scene
        this.scene = new THREE.Scene();
        this.scene.background = null; // Transparent background
        
        // Create camera - positioned for inside view
        this.camera = new THREE.PerspectiveCamera(
            45,
            rect.width / rect.height,
            0.1,
            1000
        );
        // Camera positioned to see entire profile pic area
        this.camera.position.set(0, 0, 2.8);
        this.camera.lookAt(0, 0, 0);
        
        // Create renderer
        this.renderer = new THREE.WebGLRenderer({ 
            alpha: true,
            antialias: true 
        });
        this.renderer.setSize(rect.width, rect.height);
        this.renderer.setPixelRatio(window.devicePixelRatio);
        this.renderer.shadowMap.enabled = true;
        this.container.appendChild(this.renderer.domElement);
        
        // Add beautiful lighting for the avatar
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.9);
        this.scene.add(ambientLight);
        
        // Main light from front-top
        const mainLight = new THREE.DirectionalLight(0xffffff, 1.2);
        mainLight.position.set(2, 4, 5);
        mainLight.castShadow = true;
        this.scene.add(mainLight);
        
        // Soft fill light (purple tint to match background)
        const fillLight = new THREE.DirectionalLight(0xc7d2fe, 0.5);
        fillLight.position.set(-2, 1, 3);
        this.scene.add(fillLight);
        
        // Rim light for depth
        const rimLight = new THREE.DirectionalLight(0xe0e7ff, 0.4);
        rimLight.position.set(0, -1, -2);
        this.scene.add(rimLight);
        
        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());
    }

    async loadAvatar() {
        return new Promise((resolve, reject) => {
            const loader = new GLTFLoader();
            
            const onSuccess = (gltf) => {
                this.avatar = gltf.scene;
                
                // Scale avatar - BIGGER from the start (will fill the profile pic)
                this.avatar.scale.set(1.6, 1.6, 1.6);
                
                // Position avatar EVEN LOWER - way below the bottom edge (completely hidden)
                // Starting much lower for longer, more dramatic rise
                this.avatar.position.set(0, -4.5, 0);
                
                // Face forward
                this.avatar.rotation.y = 0;
                
                // Start with 0 opacity for smooth fade in
                this.avatar.traverse((child) => {
                    if (child.isMesh) {
                        child.material.transparent = true;
                        child.material.opacity = 0;
                    }
                });
                
                // Add to scene
                this.scene.add(this.avatar);
                
                // Setup animation mixer if model has animations
                if (gltf.animations && gltf.animations.length > 0) {
                    this.mixer = new THREE.AnimationMixer(this.avatar);
                    
                    // Play idle or smile animation if available
                    const idleAction = this.mixer.clipAction(gltf.animations[0]);
                    idleAction.play();
                }
                
                console.log('✅ Avatar loaded successfully!');
                console.log('🎭 Ready for smooth rise from bottom!');
                resolve();
            };
            
            const onProgress = (progress) => {
                const percent = (progress.loaded / progress.total * 100).toFixed(0);
                console.log(`📥 Loading avatar: ${percent}%`);
            };
            
            const onError = (error) => {
                // If the avatar failed to load, try fallback to male avatar
                if (this.avatarPath !== '/avatars/male-avatar.glb') {
                    console.log('⚠️ Gender-specific avatar not found, using default avatar');
                    this.avatarPath = '/avatars/male-avatar.glb';
                    
                    // Try loading the fallback avatar
                    loader.load(
                        this.avatarPath,
                        onSuccess,
                        onProgress,
                        (fallbackError) => {
                            console.error('❌ Failed to load fallback avatar:', fallbackError);
                            reject(fallbackError);
                        }
                    );
                } else {
                    console.error('❌ Error loading avatar:', error);
                    reject(error);
                }
            };
            
            // Try loading the requested avatar
            loader.load(this.avatarPath, onSuccess, onProgress, onError);
        });
    }

    startClimbAnimation() {
        // Simple and beautiful smooth rise animation
        const tl = gsap.timeline({
            onComplete: () => {
                console.log('✨ Avatar animation complete - HEAD centered!');
            }
        });
        
        // Small delay before starting
        tl.addLabel('start', 0.7);
        
        // Step 1: Fade out initials smoothly
        tl.call(() => {
            const initials = document.getElementById('profile-initials');
            if (initials) {
                gsap.to(initials, {
                    opacity: 0,
                    duration: 1.0,
                    ease: 'power2.inOut'
                });
            }
        }, null, 'start');
        
        // Step 2: Fade in avatar VERY smoothly as it starts to rise
        tl.call(() => {
            this.avatar.traverse((child) => {
                if (child.isMesh) {
                    gsap.to(child.material, {
                        opacity: 1,
                        duration: 1.2,
                        ease: 'sine.inOut'
                    });
                }
            });
        }, null, 'start+=0.3');
        
        // Step 3: Avatar VERY SLOWLY and SMOOTHLY rises from WAY BELOW to HEAD-CENTERED position (MUCH LOWER)
        // Y = -1.7 centers the HEAD much lower (negative Y brings avatar down, showing head not feet)
        tl.to(this.avatar.position, {
            y: -1.7,
            duration: 4.5,
            ease: 'sine.inOut'
        }, 'start+=0.6');
        
        // Step 4: Small gentle head movement (friendly gesture) during the rise
        tl.to(this.avatar.rotation, {
            z: -0.06,
            duration: 1.5,
            ease: 'sine.inOut'
        }, '-=2.5');
        
        // Step 5: Return to neutral position
        tl.to(this.avatar.rotation, {
            z: 0,
            duration: 1.0,
            ease: 'sine.inOut'
        });
    }



    animate() {
        requestAnimationFrame(() => this.animate());
        
        // Update animation mixer
        if (this.mixer) {
            const delta = this.clock.getDelta();
            this.mixer.update(delta);
        }
        
        // Render scene
        this.renderer.render(this.scene, this.camera);
    }

    onWindowResize() {
        if (!this.container) return;
        
        const rect = this.container.getBoundingClientRect();
        
        this.camera.aspect = rect.width / rect.height;
        this.camera.updateProjectionMatrix();
        
        this.renderer.setSize(rect.width, rect.height);
    }

    destroy() {
        // Cleanup
        if (this.renderer) {
            this.renderer.dispose();
            if (this.container && this.renderer.domElement) {
                this.container.removeChild(this.renderer.domElement);
            }
        }
    }
}

// Auto-initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const avatarContainer = document.getElementById('avatar-climb-container');
    
    if (avatarContainer) {
        // Determine avatar based on gender
        const gender = avatarContainer.dataset.gender || 'male';
        const avatarPath = `/avatars/${gender}-avatar.glb`;
        
        // Create animation
        window.avatarClimb = new AvatarClimbAnimation(
            'avatar-climb-container',
            avatarPath
        );
    }
});
