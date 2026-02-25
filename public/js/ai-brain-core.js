/**
 * AI Brain Core - Stunning 3D Visualization
 * A neural-inspired brain with particles, energy rings, and dynamic animations
 */

class AIBrainCore {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found:', containerId);
            return;
        }

        // State management
        this.currentState = 'idle';
        this.animationId = null;
        
        // Scene components
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.brain = null;
        this.rings = [];
        this.particles = null;
        this.lights = [];
        
        // Animation parameters
        this.time = 0;
        this.pulseSpeed = 1;
        this.rotationSpeed = 0.003;
        
        this.init();
    }

    init() {
        this.createScene();
        this.createCamera();
        this.createRenderer();
        this.createLights();
        this.createBrain();
        this.createRings();
        this.createParticles();
        this.createBeam();
        
        // Handle window resize
        window.addEventListener('resize', () => this.onWindowResize());
        
        // Start animation loop
        this.animate();
        
        console.log('🧠 AI Brain Core initialized');
    }

    createScene() {
        this.scene = new THREE.Scene();
        this.scene.fog = new THREE.Fog(0x0a0e27, 10, 50);
    }

    createCamera() {
        const aspect = this.container.clientWidth / this.container.clientHeight;
        this.camera = new THREE.PerspectiveCamera(45, aspect, 0.1, 1000);
        this.camera.position.set(0, 0, 12);
        this.camera.lookAt(0, 0, 0);
    }

    createRenderer() {
        this.renderer = new THREE.WebGLRenderer({ 
            antialias: true, 
            alpha: true,
            powerPreference: 'high-performance'
        });
        this.renderer.setSize(this.container.clientWidth, this.container.clientHeight);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setClearColor(0x000000, 0);
        this.container.appendChild(this.renderer.domElement);
    }

    createLights() {
        // Ambient light
        const ambient = new THREE.AmbientLight(0x404040, 0.5);
        this.scene.add(ambient);

        // Main point lights
        const light1 = new THREE.PointLight(0x00d4ff, 2, 50);
        light1.position.set(5, 5, 5);
        this.scene.add(light1);
        this.lights.push(light1);

        const light2 = new THREE.PointLight(0x764ba2, 1.5, 50);
        light2.position.set(-5, -5, 5);
        this.scene.add(light2);
        this.lights.push(light2);

        // Rim light
        const rimLight = new THREE.DirectionalLight(0x00ffff, 0.5);
        rimLight.position.set(0, 0, -10);
        this.scene.add(rimLight);
    }

    createBrain() {
        // Create icosahedron geometry for neural core
        const geometry = new THREE.IcosahedronGeometry(2, 2);
        
        // Create material with glow effect
        const material = new THREE.MeshPhongMaterial({
            color: 0x00d4ff,
            emissive: 0x00d4ff,
            emissiveIntensity: 0.3,
            shininess: 100,
            specular: 0x00ffff,
            transparent: true,
            opacity: 0.9,
            wireframe: false
        });

        this.brain = new THREE.Mesh(geometry, material);
        this.scene.add(this.brain);

        // Add wireframe overlay
        const wireframeGeometry = new THREE.IcosahedronGeometry(2.05, 2);
        const wireframeMaterial = new THREE.MeshBasicMaterial({
            color: 0x00ffff,
            wireframe: true,
            transparent: true,
            opacity: 0.2
        });
        this.brainWireframe = new THREE.Mesh(wireframeGeometry, wireframeMaterial);
        this.scene.add(this.brainWireframe);
    }

    createRings() {
        const ringConfigs = [
            { radius: 3.5, tube: 0.08, rotation: [Math.PI / 2, 0, 0], speed: 0.5 },
            { radius: 3.8, tube: 0.06, rotation: [0, Math.PI / 3, 0], speed: -0.7 },
            { radius: 4.1, tube: 0.05, rotation: [Math.PI / 4, Math.PI / 4, 0], speed: 0.3 }
        ];

        ringConfigs.forEach((config, index) => {
            const geometry = new THREE.TorusGeometry(config.radius, config.tube, 16, 100);
            const material = new THREE.MeshPhongMaterial({
                color: 0x667eea,
                emissive: 0x667eea,
                emissiveIntensity: 0.5,
                transparent: true,
                opacity: 0.4,
                shininess: 100
            });

            const ring = new THREE.Mesh(geometry, material);
            ring.rotation.set(...config.rotation);
            ring.userData.speed = config.speed;
            ring.userData.initialRotation = { ...ring.rotation };
            
            this.scene.add(ring);
            this.rings.push(ring);
        });
    }

    createParticles() {
        const particleCount = 1000;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);
        const sizes = new Float32Array(particleCount);

        for (let i = 0; i < particleCount; i++) {
            const i3 = i * 3;
            
            // Random spherical distribution
            const radius = 8 + Math.random() * 4;
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.acos((Math.random() * 2) - 1);
            
            positions[i3] = radius * Math.sin(phi) * Math.cos(theta);
            positions[i3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
            positions[i3 + 2] = radius * Math.cos(phi);

            // Cyan to purple gradient
            const color = new THREE.Color();
            color.setHSL(0.5 + Math.random() * 0.2, 1, 0.5 + Math.random() * 0.3);
            colors[i3] = color.r;
            colors[i3 + 1] = color.g;
            colors[i3 + 2] = color.b;

            sizes[i] = Math.random() * 2 + 0.5;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        const material = new THREE.PointsMaterial({
            size: 0.1,
            vertexColors: true,
            transparent: true,
            opacity: 0.6,
            blending: THREE.AdditiveBlending,
            sizeAttenuation: true
        });

        this.particles = new THREE.Points(geometry, material);
        this.scene.add(this.particles);
    }

    createBeam() {
        // Success beam (hidden initially)
        const beamGeometry = new THREE.CylinderGeometry(0.3, 0.5, 5, 32);
        const beamMaterial = new THREE.MeshBasicMaterial({
            color: 0x00ff88,
            transparent: true,
            opacity: 0,
            blending: THREE.AdditiveBlending
        });
        
        this.beam = new THREE.Mesh(beamGeometry, beamMaterial);
        this.beam.position.y = -4;
        this.scene.add(this.beam);
    }

    animate() {
        this.animationId = requestAnimationFrame(() => this.animate());
        
        this.time += 0.01;
        
        // Update based on current state
        this.updateState();
        
        // Render scene
        this.renderer.render(this.scene, this.camera);
    }

    updateState() {
        switch (this.currentState) {
            case 'idle':
                this.updateIdleState();
                break;
            case 'processing':
                this.updateProcessingState();
                break;
            case 'success':
                this.updateSuccessState();
                break;
            case 'error':
                this.updateErrorState();
                break;
        }
    }

    updateIdleState() {
        // Gentle rotation
        this.brain.rotation.y += this.rotationSpeed;
        this.brain.rotation.x += this.rotationSpeed * 0.5;
        this.brainWireframe.rotation.y = this.brain.rotation.y;
        this.brainWireframe.rotation.x = this.brain.rotation.x;

        // Gentle pulse
        const pulse = 1 + Math.sin(this.time * this.pulseSpeed) * 0.05;
        this.brain.scale.set(pulse, pulse, pulse);

        // Rotate rings slowly
        this.rings.forEach(ring => {
            ring.rotation.z += ring.userData.speed * 0.002;
        });

        // Floating particles
        const positions = this.particles.geometry.attributes.position.array;
        for (let i = 0; i < positions.length; i += 3) {
            positions[i + 1] += Math.sin(this.time + i) * 0.002;
        }
        this.particles.geometry.attributes.position.needsUpdate = true;
        this.particles.rotation.y += 0.0005;
    }

    updateProcessingState() {
        // Fast rotation
        this.brain.rotation.y += 0.02;
        this.brain.rotation.x += 0.01;
        this.brainWireframe.rotation.y = this.brain.rotation.y;
        this.brainWireframe.rotation.x = this.brain.rotation.x;

        // Rapid pulse
        const pulse = 1 + Math.sin(this.time * 5) * 0.15;
        this.brain.scale.set(pulse, pulse, pulse);

        // Fast ring rotation
        this.rings.forEach(ring => {
            ring.rotation.z += ring.userData.speed * 0.01;
        });

        // Particles swirl inward
        const positions = this.particles.geometry.attributes.position.array;
        for (let i = 0; i < positions.length; i += 3) {
            const x = positions[i];
            const y = positions[i + 1];
            const z = positions[i + 2];
            const distance = Math.sqrt(x * x + y * y + z * z);
            
            if (distance > 3) {
                positions[i] *= 0.99;
                positions[i + 1] *= 0.99;
                positions[i + 2] *= 0.99;
            }
        }
        this.particles.geometry.attributes.position.needsUpdate = true;
        this.particles.rotation.y += 0.02;

        // Intense glow
        this.brain.material.emissiveIntensity = 0.5 + Math.sin(this.time * 10) * 0.2;
    }

    updateSuccessState() {
        // Big pulse
        const pulse = 1 + Math.sin(this.time * 3) * 0.3;
        this.brain.scale.set(pulse, pulse, pulse);

        // Slow rotation
        this.brain.rotation.y += 0.005;
        this.brainWireframe.rotation.y = this.brain.rotation.y;

        // Rings glow and stop
        this.rings.forEach(ring => {
            ring.material.emissiveIntensity = 0.8;
            ring.material.opacity = 0.7;
        });

        // Beam shoots down
        this.beam.material.opacity = Math.min(this.beam.material.opacity + 0.05, 0.6);
        this.beam.position.y -= 0.1;

        // Particles explode outward
        const positions = this.particles.geometry.attributes.position.array;
        for (let i = 0; i < positions.length; i += 3) {
            positions[i] *= 1.01;
            positions[i + 1] *= 1.01;
            positions[i + 2] *= 1.01;
        }
        this.particles.geometry.attributes.position.needsUpdate = true;

        // Green glow
        this.brain.material.color.setHex(0x00ff88);
        this.brain.material.emissive.setHex(0x00ff88);
        this.brain.material.emissiveIntensity = 0.7;
    }

    updateErrorState() {
        // Shake animation
        const shake = Math.sin(this.time * 20) * 0.1;
        this.brain.position.x = shake;
        this.brain.position.y = Math.cos(this.time * 25) * 0.1;

        // Shrink slightly
        const scale = 0.9 + Math.sin(this.time * 10) * 0.05;
        this.brain.scale.set(scale, scale, scale);

        // Rings wobble
        this.rings.forEach((ring, index) => {
            ring.rotation.z += Math.sin(this.time * 5 + index) * 0.01;
        });

        // Particles scatter
        const positions = this.particles.geometry.attributes.position.array;
        for (let i = 0; i < positions.length; i += 3) {
            positions[i] += (Math.random() - 0.5) * 0.05;
            positions[i + 1] += (Math.random() - 0.5) * 0.05;
            positions[i + 2] += (Math.random() - 0.5) * 0.05;
        }
        this.particles.geometry.attributes.position.needsUpdate = true;

        // Red glow
        this.brain.material.color.setHex(0xff4444);
        this.brain.material.emissive.setHex(0xff4444);
        this.brain.material.emissiveIntensity = 0.6;
    }

    setState(newState) {
        console.log('🧠 Brain state:', this.currentState, '→', newState);
        
        const oldState = this.currentState;
        this.currentState = newState;
        
        // Reset beam when leaving success state
        if (oldState === 'success' && newState !== 'success') {
            this.beam.material.opacity = 0;
            this.beam.position.y = -4;
        }

        // Reset brain position when leaving error state
        if (oldState === 'error') {
            this.brain.position.set(0, 0, 0);
        }

        // Reset colors when changing states
        if (newState === 'idle' || newState === 'processing') {
            this.brain.material.color.setHex(0x00d4ff);
            this.brain.material.emissive.setHex(0x00d4ff);
            this.brain.material.emissiveIntensity = 0.3;
            
            this.rings.forEach(ring => {
                ring.material.emissiveIntensity = 0.5;
                ring.material.opacity = 0.4;
            });
        }
    }

    onWindowResize() {
        const width = this.container.clientWidth;
        const height = this.container.clientHeight;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();

        this.renderer.setSize(width, height);
    }

    destroy() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        if (this.renderer) {
            this.renderer.dispose();
            this.container.removeChild(this.renderer.domElement);
        }
        
        window.removeEventListener('resize', () => this.onWindowResize());
    }
}

// Export for use in terminal
window.AIBrainCore = AIBrainCore;
