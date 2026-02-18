/**
 * 🎭 Avatar Climb Animation System
 * Creates a cute climbing animation for 3D avatar on settings page
 */

class AvatarClimbAnimation {
    constructor(containerId, avatarPath, apiKey) {
        this.container = document.getElementById(containerId);
        this.avatarPath = avatarPath;
        this.apiKey = apiKey;
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
        
        // Create camera
        this.camera = new THREE.PerspectiveCamera(
            45,
            rect.width / rect.height,
            0.1,
            1000
        );
        this.camera.position.set(0, 1, 3);
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
        
        // Add lights
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 10, 7);
        directionalLight.castShadow = true;
        this.scene.add(directionalLight);
        
        const fillLight = new THREE.DirectionalLight(0x4fc3f7, 0.3);
        fillLight.position.set(-5, 0, -5);
        this.scene.add(fillLight);
        
        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());
    }

    async loadAvatar() {
        return new Promise((resolve, reject) => {
            const loader = new THREE.GLTFLoader();
            
            loader.load(
                this.avatarPath,
                (gltf) => {
                    this.avatar = gltf.scene;
                    
                    // Scale avatar to fit
                    this.avatar.scale.set(0.8, 0.8, 0.8);
                    
                    // Position avatar hanging below (starting position)
                    this.avatar.position.set(0, -2, 0);
                    this.avatar.rotation.y = 0;
                    
                    // Add to scene
                    this.scene.add(this.avatar);
                    
                    // Setup animation mixer if model has animations
                    if (gltf.animations && gltf.animations.length > 0) {
                        this.mixer = new THREE.AnimationMixer(this.avatar);
                        
                        // Play idle or smile animation
                        const smileAction = this.mixer.clipAction(gltf.animations[0]);
                        smileAction.play();
                    }
                    
                    console.log('✅ Avatar loaded successfully!');
                    resolve();
                },
                (progress) => {
                    console.log(`Loading: ${(progress.loaded / progress.total * 100).toFixed(0)}%`);
                },
                (error) => {
                    console.error('❌ Error loading avatar:', error);
                    reject(error);
                }
            );
        });
    }

    startClimbAnimation() {
        // Create climbing animation timeline with GSAP
        const tl = gsap.timeline({
            onComplete: () => {
                console.log('🎉 Climb animation complete!');
                this.triggerSmile();
            }
        });
        
        // Step 1: Hanging and swaying
        tl.to(this.avatar.rotation, {
            z: 0.1,
            duration: 0.5,
            yoyo: true,
            repeat: 2,
            ease: 'sine.inOut'
        });
        
        // Step 2: Start climbing (hand over hand movement)
        tl.to(this.avatar.position, {
            y: -1.5,
            duration: 0.8,
            ease: 'power2.out'
        }, '+=0.2');
        
        // Add slight rotation during climb (realistic)
        tl.to(this.avatar.rotation, {
            y: 0.2,
            duration: 0.4,
            yoyo: true,
            repeat: 1,
            ease: 'sine.inOut'
        }, '-=0.8');
        
        // Step 3: Climbing higher
        tl.to(this.avatar.position, {
            y: -0.5,
            duration: 0.8,
            ease: 'power2.out'
        });
        
        // Step 4: Pull up to top
        tl.to(this.avatar.position, {
            y: 0,
            duration: 1,
            ease: 'back.out(1.2)'
        });
        
        // Step 5: Settle into position with a little bounce
        tl.to(this.avatar.position, {
            y: 0.1,
            duration: 0.3,
            ease: 'bounce.out'
        });
        
        // Step 6: Face the camera and smile
        tl.to(this.avatar.rotation, {
            y: 0,
            duration: 0.5,
            ease: 'power2.inOut'
        }, '-=0.3');
        
        // Step 7: Slight celebratory bounce
        tl.to(this.avatar.scale, {
            x: 0.85,
            y: 0.85,
            z: 0.85,
            duration: 0.2,
            yoyo: true,
            repeat: 1,
            ease: 'power2.inOut'
        });
    }

    triggerSmile() {
        // If model has facial animations, trigger smile
        if (this.mixer) {
            console.log('😊 Avatar is smiling!');
        }
        
        // Add a little wave animation
        if (this.avatar) {
            gsap.to(this.avatar.rotation, {
                z: -0.1,
                duration: 0.3,
                yoyo: true,
                repeat: 3,
                ease: 'sine.inOut'
            });
        }
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    const avatarContainer = document.getElementById('avatar-climb-container');
    
    if (avatarContainer) {
        // Determine avatar based on gender
        const gender = avatarContainer.dataset.gender || 'male';
        const avatarPath = `/avatars/${gender}-avatar.glb`;
        const apiKey = 'RXBN1GR-4514DET-QGS0VEE-9XN4FKN';
        
        // Create animation
        window.avatarClimb = new AvatarClimbAnimation(
            'avatar-climb-container',
            avatarPath,
            apiKey
        );
    }
});
