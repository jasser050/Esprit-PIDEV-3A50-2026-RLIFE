/**
 * Avatar Selector - 3D Avatar Preview and Selection
 * Handles loading, rendering, and animating GLB avatars in registration step 3
 */

import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';

class AvatarSelector {
    constructor() {
        this.avatars = new Map();
        this.loader = new GLTFLoader();
        this.selectedAvatar = 'male-avatar.glb'; // default
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        console.log('Avatar selector: Setting up...');
        
        // Find all avatar containers
        const containers = document.querySelectorAll('.avatar-canvas-container');
        console.log('Found', containers.length, 'avatar containers');
        
        if (containers.length === 0) {
            console.warn('No avatar containers found. Retrying in 500ms...');
            setTimeout(() => this.setup(), 500);
            return;
        }

        // Load and render each avatar
        containers.forEach(container => {
            const avatarPath = container.dataset.avatarPath;
            if (!avatarPath) return;

            this.loadAvatar(container, avatarPath);
        });

        // Setup click handlers for avatar selection
        // Delay slightly to ensure DOM is fully ready
        setTimeout(() => {
            this.setupSelection();
        }, 100);
    }

    loadAvatar(container, avatarPath) {
        const width = container.clientWidth || 150;
        const height = container.clientHeight || 150;

        // Create scene
        const scene = new THREE.Scene();
        scene.background = null; // transparent

        // Create camera
        const camera = new THREE.PerspectiveCamera(35, width / height, 0.1, 1000);
        camera.position.set(0, 1.3, 3.5);
        camera.lookAt(0, 0.9, 0);

        // Create renderer
        const renderer = new THREE.WebGLRenderer({ 
            antialias: true, 
            alpha: true,
            preserveDrawingBuffer: true
        });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        
        // Make canvas non-interactive so clicks pass through to parent card
        renderer.domElement.style.pointerEvents = 'none';
        
        container.appendChild(renderer.domElement);

        // Lighting setup (same as settings page)
        const ambientLight = new THREE.AmbientLight(0xffffff, 1.2);
        scene.add(ambientLight);

        const frontLight = new THREE.DirectionalLight(0xffffff, 1.8);
        frontLight.position.set(2, 3, 4);
        frontLight.castShadow = true;
        frontLight.shadow.mapSize.width = 1024;
        frontLight.shadow.mapSize.height = 1024;
        scene.add(frontLight);

        const backLight = new THREE.DirectionalLight(0xb8c5ff, 0.8);
        backLight.position.set(-2, 2, -3);
        scene.add(backLight);

        const fillLight = new THREE.DirectionalLight(0xffd6a5, 0.5);
        fillLight.position.set(0, -1, 2);
        scene.add(fillLight);

        // Load GLB model
        this.loader.load(
            avatarPath,
            (gltf) => {
                const model = gltf.scene;
                
                // Enable shadows
                model.traverse((child) => {
                    if (child.isMesh) {
                        child.castShadow = true;
                        child.receiveShadow = true;
                    }
                });

                // Position model
                const box = new THREE.Box3().setFromObject(model);
                const center = box.getCenter(new THREE.Vector3());
                const size = box.getSize(new THREE.Vector3());
                
                model.position.x = -center.x;
                model.position.y = -box.min.y;
                model.position.z = -center.z;

                scene.add(model);

                // Store for animations
                this.avatars.set(container, {
                    scene,
                    camera,
                    renderer,
                    model,
                    mixer: gltf.animations.length > 0 ? new THREE.AnimationMixer(model) : null,
                    isHovered: false,
                    rotationY: 0,
                    bobOffset: 0
                });

                // Play idle animation if available
                if (gltf.animations.length > 0) {
                    const avatar = this.avatars.get(container);
                    const idleClip = gltf.animations[0]; // assume first animation is idle
                    const action = avatar.mixer.clipAction(idleClip);
                    action.play();
                }

                // Start render loop
                this.animate(container);

                // Setup hover effect
                this.setupHover(container);
            },
            (xhr) => {
                // Loading progress (optional)
            },
            (error) => {
                console.error('Error loading avatar:', avatarPath, error);
            }
        );
    }

    setupHover(container) {
        const card = container.closest('.avatar-card');
        if (!card) return;

        card.addEventListener('mouseenter', () => {
            const avatar = this.avatars.get(container);
            if (avatar) avatar.isHovered = true;
        });

        card.addEventListener('mouseleave', () => {
            const avatar = this.avatars.get(container);
            if (avatar) avatar.isHovered = false;
        });
    }

    setupSelection() {
        const cards = document.querySelectorAll('.avatar-card');
        const hiddenInput = document.getElementById('avatar_type');

        console.log('Avatar selector: Found', cards.length, 'avatar cards');

        cards.forEach((card, index) => {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Avatar clicked:', card.dataset.avatar);

                // Remove previous selection
                document.querySelectorAll('.avatar-card').forEach(c => {
                    c.classList.remove('selected', 'avatar-jump');
                    
                    // Remove inline styles from previously selected
                    const prevInner = c.querySelector('div.relative');
                    if (prevInner) {
                        prevInner.style.borderColor = '';
                        prevInner.style.borderWidth = '';
                        prevInner.style.boxShadow = '';
                        prevInner.style.transform = '';
                    }
                    
                    // Hide checkmark from previous selection
                    const prevCheckmark = c.querySelector('.avatar-check');
                    if (prevCheckmark) {
                        prevCheckmark.style.opacity = '0';
                        prevCheckmark.style.display = 'none';
                    }
                });

                // Add selection to clicked card
                card.classList.add('selected');
                
                // Apply inline styles for guaranteed visibility
                const innerDiv = card.querySelector('div.relative');
                if (innerDiv) {
                    innerDiv.style.borderColor = 'rgb(99, 102, 241)';
                    innerDiv.style.borderWidth = '3px';
                    innerDiv.style.boxShadow = '0 0 0 4px rgba(99, 102, 241, 0.3), 0 10px 40px rgba(99, 102, 241, 0.4)';
                    innerDiv.style.transform = 'scale(1.02)';
                }
                
                // Show checkmark
                const checkmark = card.querySelector('.avatar-check');
                if (checkmark) {
                    checkmark.style.opacity = '1';
                    checkmark.style.display = 'flex';
                }
                
                // Trigger jump animation
                card.classList.add('avatar-jump');
                setTimeout(() => {
                    card.classList.remove('avatar-jump');
                }, 700);

                // Update hidden input
                const avatarType = card.dataset.avatar;
                if (hiddenInput && avatarType) {
                    hiddenInput.value = avatarType;
                    this.selectedAvatar = avatarType;
                    console.log('Avatar selected:', avatarType);
                }
            });
            
            console.log('Added click handler to card', index);
        });

        // Select first avatar by default
        if (cards.length > 0) {
            const firstCard = cards[0];
            firstCard.classList.add('selected');
            
            // Apply selection styles
            const innerDiv = firstCard.querySelector('div.relative');
            if (innerDiv) {
                innerDiv.style.borderColor = 'rgb(99, 102, 241)';
                innerDiv.style.borderWidth = '3px';
                innerDiv.style.boxShadow = '0 0 0 4px rgba(99, 102, 241, 0.3), 0 10px 40px rgba(99, 102, 241, 0.4)';
                innerDiv.style.transform = 'scale(1.02)';
            }
            
            // Show checkmark
            const checkmark = firstCard.querySelector('.avatar-check');
            if (checkmark) {
                checkmark.style.opacity = '1';
                checkmark.style.display = 'flex';
            }
            
            if (hiddenInput) {
                hiddenInput.value = cards[0].dataset.avatar;
            }
            console.log('Default avatar selected:', cards[0].dataset.avatar);
        }
    }

    animate(container) {
        const avatar = this.avatars.get(container);
        if (!avatar) return;

        const clock = new THREE.Clock();

        const loop = () => {
            if (!this.avatars.has(container)) return; // stopped

            const delta = clock.getDelta();

            // Update animation mixer
            if (avatar.mixer) {
                avatar.mixer.update(delta);
            }

            // Hover effect: gentle sway and bob
            if (avatar.isHovered) {
                avatar.rotationY += delta * 0.5;
                avatar.bobOffset += delta * 3;
                
                avatar.model.rotation.y = Math.sin(avatar.rotationY) * 0.15;
                avatar.model.position.y += Math.sin(avatar.bobOffset) * 0.002;
            } else {
                // Return to center smoothly
                avatar.model.rotation.y *= 0.95;
                avatar.rotationY *= 0.95;
                avatar.bobOffset *= 0.95;
            }

            avatar.renderer.render(avatar.scene, avatar.camera);
            requestAnimationFrame(loop);
        };

        loop();
    }

    dispose() {
        this.avatars.forEach((avatar, container) => {
            if (avatar.mixer) avatar.mixer.stopAllAction();
            if (avatar.renderer) {
                avatar.renderer.dispose();
                avatar.renderer.domElement.remove();
            }
            if (avatar.scene) {
                avatar.scene.traverse((object) => {
                    if (object.geometry) object.geometry.dispose();
                    if (object.material) {
                        if (Array.isArray(object.material)) {
                            object.material.forEach(mat => mat.dispose());
                        } else {
                            object.material.dispose();
                        }
                    }
                });
            }
        });
        this.avatars.clear();
    }
}

// Only initialize if avatar containers exist on the page
let selector = null;

function initAvatarSelector() {
    // Skip if already initializing
    if (window.selectorInitializing) {
        return;
    }
    
    // Check if we're on a page that needs the avatar selector
    const hasAvatarContainers = document.querySelectorAll('.avatar-canvas-container').length > 0;
    const isWizardPage = document.querySelector('[data-controller*="wizard"]') !== null;
    
    if (hasAvatarContainers || isWizardPage) {
        console.log('Avatar selector: Initializing for wizard page...');
        
        window.selectorInitializing = true;
        
        if (selector) {
            selector.dispose();
        }
        selector = new AvatarSelector();
        
        // Mark as initialized after a short delay
        setTimeout(() => {
            window.selectorInitializing = false;
            window.selectorInitialized = true;
        }, 1000);
    }
}

// Initialize on all page loads and Turbo navigations
function setupAvatarSelectorInit() {
    // Reset flag when leaving the page
    document.addEventListener('turbo:before-cache', () => {
        console.log('Avatar selector: Cleaning up on before-cache');
        window.selectorInitialized = false;
        window.selectorInitializing = false;
        if (selector) {
            selector.dispose();
            selector = null;
        }
    });
    
    // Classic page load (non-Turbo)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAvatarSelector);
    } else {
        initAvatarSelector();
    }
    
    // Turbo Drive navigations - use timeout to ensure DOM is ready
    if (typeof Turbo !== 'undefined' || typeof window.Turbo !== 'undefined') {
        document.addEventListener('turbo:load', () => {
            setTimeout(initAvatarSelector, 50);
        });
        document.addEventListener('turbo:frame-load', () => {
            setTimeout(initAvatarSelector, 50);
        });
    }
    
    document.addEventListener('turbo:ready', () => {
        setTimeout(initAvatarSelector, 50);
    });
}

// Setup initialization
setupAvatarSelectorInit();

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (selector) {
        selector.dispose();
    }
});

export default selector;
