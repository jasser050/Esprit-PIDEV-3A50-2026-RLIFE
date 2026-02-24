// assets/js/calendar-3d-bg.js - Version Ultra Premium avec Three.js

class Calendar3DManager {
    constructor() {
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.particles = [];
        this.floatingShapes = [];
        this.mouseX = 0;
        this.mouseY = 0;
        this.clock = new THREE.Clock();
        this.raycaster = new THREE.Raycaster();
        this.mouse = new THREE.Vector2();
        this.isHovering = false;
        
        this.init();
        this.setupEventListeners();
    }

    init() {
        const container = document.getElementById('calendar-bg-3d');
        if (!container) return;

        // Configuration de la scène
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x050510);
        this.scene.fog = new THREE.FogExp2(0x050510, 0.002);

        // Caméra
        this.camera = new THREE.PerspectiveCamera(75, window.innerWidth / 450, 0.1, 1000);
        this.camera.position.set(0, 0, 40);
        this.camera.lookAt(0, 0, 0);

        // Renderer avec effets
        this.renderer = new THREE.WebGLRenderer({ 
            antialias: true, 
            alpha: true,
            powerPreference: "high-performance"
        });
        this.renderer.setSize(window.innerWidth, 450);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        container.appendChild(this.renderer.domElement);

        // Lumières avancées
        this.setupLights();

        // Création des éléments 3D
        this.createParticleSystem();
        this.createFloatingCubes();
        this.createFloatingSpheres();
        this.createFloatingTorus();
        this.createFloatingIcosahedrons();
        this.createConnectingLines();
        this.createLightBeams();

        // Démarrer l'animation
        this.animate();
    }

    setupLights() {
        // Lumière ambiante
        const ambientLight = new THREE.AmbientLight(0x404060);
        this.scene.add(ambientLight);

        // Lumière principale avec ombres
        const mainLight = new THREE.DirectionalLight(0xffffff, 1);
        mainLight.position.set(10, 20, 10);
        mainLight.castShadow = true;
        mainLight.receiveShadow = true;
        mainLight.shadow.mapSize.width = 1024;
        mainLight.shadow.mapSize.height = 1024;
        this.scene.add(mainLight);

        // Lumières colorées
        const colors = [0x6366f1, 0x8b5cf6, 0xd946ef, 0xec4899];
        colors.forEach((color, index) => {
            const light = new THREE.PointLight(color, 1, 30);
            light.position.set(
                Math.sin(index * Math.PI / 2) * 15,
                Math.cos(index * Math.PI / 2) * 15,
                10
            );
            this.scene.add(light);
            
            // Ajouter une petite sphère pour visualiser la lumière
            const sphere = new THREE.Mesh(
                new THREE.SphereGeometry(0.2, 16, 16),
                new THREE.MeshBasicMaterial({ color: color })
            );
            sphere.position.copy(light.position);
            this.scene.add(sphere);
        });
    }

    createParticleSystem() {
        const particleCount = 2000;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);
        const sizes = new Float32Array(particleCount);

        for (let i = 0; i < particleCount; i++) {
            // Positions dans un volume sphérique
            const radius = 30 + Math.random() * 20;
            const theta = Math.random() * Math.PI * 2;
            const phi = Math.acos(2 * Math.random() - 1);
            
            positions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
            positions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
            positions[i * 3 + 2] = radius * Math.cos(phi);

            // Couleurs basées sur la position
            const color = new THREE.Color().setHSL(
                0.65 + Math.sin(positions[i * 3]) * 0.1,
                0.8,
                0.5 + Math.cos(positions[i * 3 + 1]) * 0.2
            );
            
            colors[i * 3] = color.r;
            colors[i * 3 + 1] = color.g;
            colors[i * 3 + 2] = color.b;
            
            sizes[i] = Math.random() * 0.5 + 0.1;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

        const material = new THREE.PointsMaterial({
            size: 0.1,
            vertexColors: true,
            map: this.createParticleTexture(),
            blending: THREE.AdditiveBlending,
            depthWrite: false,
            transparent: true
        });

        const particles = new THREE.Points(geometry, material);
        this.scene.add(particles);
        this.particleSystem = particles;
    }

    createParticleTexture() {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 32;
        const ctx = canvas.getContext('2d');
        
        ctx.fillStyle = 'white';
        ctx.beginPath();
        ctx.arc(16, 16, 16, 0, Math.PI * 2);
        ctx.fill();
        
        ctx.globalCompositeOperation = 'source-atop';
        const gradient = ctx.createRadialGradient(16, 16, 0, 16, 16, 16);
        gradient.addColorStop(0, 'rgba(255,255,255,1)');
        gradient.addColorStop(0.5, 'rgba(255,255,255,0.5)');
        gradient.addColorStop(1, 'rgba(255,255,255,0)');
        
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 32, 32);
        
        return new THREE.CanvasTexture(canvas);
    }

    createFloatingCubes() {
        const geometries = [
            new THREE.BoxGeometry(0.8, 0.8, 0.8),
            new THREE.BoxGeometry(1.2, 0.4, 0.4),
            new THREE.BoxGeometry(0.4, 1.2, 0.4),
            new THREE.BoxGeometry(0.4, 0.4, 1.2)
        ];

        for (let i = 0; i < 20; i++) {
            const geometry = geometries[Math.floor(Math.random() * geometries.length)];
            const material = new THREE.MeshStandardMaterial({
                color: new THREE.Color().setHSL(0.65 + Math.random() * 0.2, 0.8, 0.6),
                emissive: new THREE.Color().setHSL(0.65 + Math.random() * 0.2, 0.8, 0.2),
                roughness: 0.2,
                metalness: 0.8,
                transparent: true,
                opacity: 0.6
            });

            const cube = new THREE.Mesh(geometry, material);
            
            cube.position.x = (Math.random() - 0.5) * 40;
            cube.position.y = (Math.random() - 0.5) * 20;
            cube.position.z = (Math.random() - 0.5) * 30;
            
            cube.rotation.x = Math.random() * Math.PI;
            cube.rotation.y = Math.random() * Math.PI;
            
            cube.castShadow = true;
            cube.receiveShadow = true;
            
            cube.userData = {
                speed: 0.001 + Math.random() * 0.002,
                rotationSpeedX: 0.002 + Math.random() * 0.004,
                rotationSpeedY: 0.002 + Math.random() * 0.004,
                floatAmplitude: 0.5 + Math.random() * 1,
                originalY: cube.position.y,
                originalX: cube.position.x,
                phase: Math.random() * Math.PI * 2
            };

            this.scene.add(cube);
            this.floatingShapes.push(cube);
        }
    }

    createFloatingSpheres() {
        for (let i = 0; i < 15; i++) {
            const geometry = new THREE.SphereGeometry(0.6 + Math.random() * 0.4, 32, 32);
            const material = new THREE.MeshStandardMaterial({
                color: new THREE.Color().setHSL(0.75 + Math.random() * 0.2, 0.9, 0.5),
                emissive: new THREE.Color().setHSL(0.75 + Math.random() * 0.2, 0.9, 0.1),
                roughness: 0.1,
                metalness: 0.9,
                transparent: true,
                opacity: 0.5
            });

            const sphere = new THREE.Mesh(geometry, material);
            
            sphere.position.x = (Math.random() - 0.5) * 45;
            sphere.position.y = (Math.random() - 0.5) * 25;
            sphere.position.z = (Math.random() - 0.5) * 35;
            
            sphere.castShadow = true;
            sphere.receiveShadow = true;
            
            sphere.userData = {
                speed: 0.001 + Math.random() * 0.002,
                rotationSpeed: 0.001 + Math.random() * 0.003,
                floatAmplitude: 0.3 + Math.random() * 0.8,
                originalY: sphere.position.y,
                phase: Math.random() * Math.PI * 2
            };

            this.scene.add(sphere);
            this.floatingShapes.push(sphere);
        }
    }

    createFloatingTorus() {
        for (let i = 0; i < 10; i++) {
            const geometry = new THREE.TorusGeometry(0.8, 0.2, 16, 100);
            const material = new THREE.MeshStandardMaterial({
                color: new THREE.Color().setHSL(0.85 + Math.random() * 0.1, 0.8, 0.6),
                emissive: new THREE.Color().setHSL(0.85 + Math.random() * 0.1, 0.8, 0.2),
                roughness: 0.3,
                metalness: 0.7,
                transparent: true,
                opacity: 0.4,
                wireframe: Math.random() > 0.5
            });

            const torus = new THREE.Mesh(geometry, material);
            
            torus.position.x = (Math.random() - 0.5) * 50;
            torus.position.y = (Math.random() - 0.5) * 25;
            torus.position.z = (Math.random() - 0.5) * 40;
            
            torus.rotation.x = Math.random() * Math.PI;
            torus.rotation.y = Math.random() * Math.PI;
            
            torus.userData = {
                speed: 0.001 + Math.random() * 0.002,
                rotationSpeed: 0.002 + Math.random() * 0.004,
                floatAmplitude: 0.4 + Math.random() * 0.8,
                originalY: torus.position.y,
                phase: Math.random() * Math.PI * 2
            };

            this.scene.add(torus);
            this.floatingShapes.push(torus);
        }
    }

    createFloatingIcosahedrons() {
        for (let i = 0; i < 12; i++) {
            const geometry = new THREE.IcosahedronGeometry(0.7, 0);
            const material = new THREE.MeshStandardMaterial({
                color: new THREE.Color().setHSL(0.55 + Math.random() * 0.2, 0.9, 0.6),
                emissive: new THREE.Color().setHSL(0.55 + Math.random() * 0.2, 0.9, 0.2),
                roughness: 0.2,
                metalness: 0.8,
                transparent: true,
                opacity: 0.5,
                wireframe: Math.random() > 0.6
            });

            const ico = new THREE.Mesh(geometry, material);
            
            ico.position.x = (Math.random() - 0.5) * 45;
            ico.position.y = (Math.random() - 0.5) * 25;
            ico.position.z = (Math.random() - 0.5) * 35;
            
            ico.castShadow = true;
            ico.receiveShadow = true;
            
            ico.userData = {
                speed: 0.001 + Math.random() * 0.002,
                rotationSpeed: 0.003 + Math.random() * 0.005,
                floatAmplitude: 0.5 + Math.random() * 0.8,
                originalY: ico.position.y,
                phase: Math.random() * Math.PI * 2
            };

            this.scene.add(ico);
            this.floatingShapes.push(ico);
        }
    }

    createConnectingLines() {
        const points = [];
        for (let i = 0; i < 50; i++) {
            points.push(new THREE.Vector3(
                (Math.random() - 0.5) * 60,
                (Math.random() - 0.5) * 30,
                (Math.random() - 0.5) * 50
            ));
        }

        const geometry = new THREE.BufferGeometry().setFromPoints(points);
        const material = new THREE.LineBasicMaterial({ 
            color: 0x6366f1,
            transparent: true,
            opacity: 0.1
        });

        const lines = new THREE.LineSegments(
            new THREE.EdgesGeometry(new THREE.BoxGeometry(60, 30, 50)),
            material
        );
        lines.position.set(0, 0, 0);
        this.scene.add(lines);

        // Lignes connectant les particules proches
        const lineMaterial = new THREE.LineBasicMaterial({ color: 0x8b5cf6, opacity: 0.05, transparent: true });
        const linePositions = [];
        
        for (let i = 0; i < points.length; i++) {
            for (let j = i + 1; j < points.length; j++) {
                const dist = points[i].distanceTo(points[j]);
                if (dist < 15) {
                    linePositions.push(points[i].x, points[i].y, points[i].z);
                    linePositions.push(points[j].x, points[j].y, points[j].z);
                }
            }
        }
        
        const lineGeometry = new THREE.BufferGeometry();
        lineGeometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));
        const lines2 = new THREE.LineSegments(lineGeometry, lineMaterial);
        this.scene.add(lines2);
    }

    createLightBeams() {
        const beamCount = 5;
        for (let i = 0; i < beamCount; i++) {
            const geometry = new THREE.CylinderGeometry(0.1, 0.1, 30, 8);
            const material = new THREE.MeshStandardMaterial({
                color: new THREE.Color().setHSL(0.65 + i * 0.05, 0.9, 0.5),
                emissive: new THREE.Color().setHSL(0.65 + i * 0.05, 0.9, 0.2),
                transparent: true,
                opacity: 0.1
            });

            const beam = new THREE.Mesh(geometry, material);
            beam.position.set(
                Math.sin(i * Math.PI * 2 / beamCount) * 15,
                0,
                Math.cos(i * Math.PI * 2 / beamCount) * 15
            );
            beam.rotation.x = Math.PI / 2;
            beam.rotation.z = i * Math.PI * 2 / beamCount;
            
            this.scene.add(beam);
        }
    }

    setupEventListeners() {
        document.addEventListener('mousemove', (e) => {
            this.mouseX = (e.clientX / window.innerWidth - 0.5) * 2;
            this.mouseY = (e.clientY / window.innerHeight - 0.5) * 2;
            
            this.mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
            this.mouse.y = -(e.clientY / window.innerHeight) * 2 + 1;
            
            // Mettre à jour la position pour l'effet de spotlight
            document.documentElement.style.setProperty('--x', `${e.clientX / window.innerWidth * 100}%`);
            document.documentElement.style.setProperty('--y', `${e.clientY / window.innerHeight * 100}%`);
        });

        window.addEventListener('resize', () => this.onWindowResize());
    }

    onWindowResize() {
        if (!this.camera || !this.renderer) return;
        
        this.camera.aspect = window.innerWidth / 450;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(window.innerWidth, 450);
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        const delta = this.clock.getDelta();
        const elapsedTime = performance.now() / 1000;

        // Mouvement de caméra basé sur la souris
        this.camera.position.x += (this.mouseX * 8 - this.camera.position.x) * 0.02;
        this.camera.position.y += (this.mouseY * 4 - this.camera.position.y) * 0.02;
        this.camera.lookAt(0, 0, 0);

        // Animation des formes flottantes
        this.floatingShapes.forEach(shape => {
            if (!shape.userData) return;
            
            // Mouvement de flottaison
            shape.position.y = shape.userData.originalY + 
                Math.sin(elapsedTime * 2 + shape.userData.phase) * shape.userData.floatAmplitude;
            
            // Mouvement horizontal subtil
            if (shape.userData.originalX) {
                shape.position.x = shape.userData.originalX + 
                    Math.cos(elapsedTime * 1.5 + shape.userData.phase) * 0.5;
            }
            
            // Rotation
            if (shape.userData.rotationSpeed) {
                shape.rotation.x += shape.userData.rotationSpeed * delta * 30;
                shape.rotation.y += shape.userData.rotationSpeed * 1.5 * delta * 30;
            }
            
            // Effet de pulsation
            const scale = 1 + Math.sin(elapsedTime * 3 + shape.position.x) * 0.05;
            shape.scale.set(scale, scale, scale);
        });

        // Animation du système de particules
        if (this.particleSystem) {
            this.particleSystem.rotation.y += 0.0002;
            this.particleSystem.rotation.x += 0.0001;
        }

        this.renderer.render(this.scene, this.camera);
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    if (typeof THREE !== 'undefined') {
        window.calendar3D = new Calendar3DManager();
    }
});