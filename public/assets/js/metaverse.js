// assets/js/metaverse.js - Gestionnaire Metaverse

class MetaverseManager {
    constructor() {
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.portal = null;
        this.particles = [];
        this.isInMetaverse = false;
        
        this.init();
        this.createParticles();
        this.setupPortal();
    }

    init() {
        // Créer les particules flottantes
        this.createFloatingParticles();
        
        // Initialiser Three.js pour le portail
        this.initThree();
    }

    initThree() {
        const container = document.getElementById('metaverse-portal-3d');
        if (!container) return;

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000);
        this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        
        this.renderer.setSize(60, 60);
        container.appendChild(this.renderer.domElement);

        // Créer un trou de ver
        const geometry = new THREE.TorusGeometry(1, 0.3, 16, 100);
        const material = new THREE.MeshStandardMaterial({
            color: 0x00f3ff,
            emissive: 0x00f3ff,
            wireframe: true,
            transparent: true,
            opacity: 0.8
        });
        
        this.portal = new THREE.Mesh(geometry, material);
        this.portal.rotation.x = Math.PI / 2;
        this.scene.add(this.portal);

        // Ajouter des particules autour du portail
        const particleGeo = new THREE.BufferGeometry();
        const particleCount = 50;
        const positions = new Float32Array(particleCount * 3);
        
        for (let i = 0; i < particleCount; i++) {
            const angle = (i / particleCount) * Math.PI * 2;
            positions[i * 3] = Math.cos(angle) * 1.5;
            positions[i * 3 + 1] = Math.sin(angle) * 1.5;
            positions[i * 3 + 2] = 0;
        }
        
        particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const particleMat = new THREE.PointsMaterial({ 
            color: 0xff00e5,
            size: 0.05
        });
        
        const particles = new THREE.Points(particleGeo, particleMat);
        this.scene.add(particles);

        this.camera.position.z = 3;

        this.animate();
    }

    animate() {
        requestAnimationFrame(() => this.animate());

        if (this.portal) {
            this.portal.rotation.z += 0.01;
        }

        this.renderer.render(this.scene, this.camera);
    }

    createFloatingParticles() {
        const particlesContainer = document.createElement('div');
        particlesContainer.className = 'particles';
        
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = 10 + Math.random() * 10 + 's';
            particlesContainer.appendChild(particle);
        }
        
        document.body.appendChild(particlesContainer);
    }

    setupPortal() {
        const portal = document.createElement('div');
        portal.className = 'metaverse-portal';
        portal.innerHTML = '🌐';
        
        // Ajouter le conteneur Three.js
        const threeContainer = document.createElement('div');
        threeContainer.id = 'metaverse-portal-3d';
        threeContainer.style.width = '100%';
        threeContainer.style.height = '100%';
        portal.appendChild(threeContainer);
        
        portal.onclick = () => this.enterMetaverse();
        
        document.body.appendChild(portal);
    }

    enterMetaverse() {
        this.isInMetaverse = true;
        
        // Afficher l'écran de chargement
        const loadingScreen = document.createElement('div');
        loadingScreen.className = 'metaverse-loading active';
        loadingScreen.innerHTML = '<div class="loading-text">Entering Metaverse...</div>';
        document.body.appendChild(loadingScreen);

        // Changer le thème
        document.body.style.transition = 'all 1s';
        document.body.style.background = 'linear-gradient(135deg, #000000 0%, #0a0a2a 50%, #1a0033 100%)';
        
        // Ajouter la grille cyberpunk
        const grid = document.createElement('div');
        grid.className = 'cyber-grid';
        document.body.appendChild(grid);

        // Ajouter l'effet scanline
        const scanline = document.createElement('div');
        scanline.className = 'scanline';
        document.body.appendChild(scanline);

        // Changer les couleurs des éléments
        document.querySelectorAll('.calendar-cell').forEach(cell => {
            cell.classList.add('calendar-cell-metaverse');
        });

        document.querySelectorAll('.event-item').forEach(event => {
            event.classList.add('event-metaverse');
        });

        document.querySelectorAll('.badge').forEach(badge => {
            if (badge.classList.contains('badge-primary')) {
                badge.classList.add('badge-cyber-primary');
            }
        });

        // Animation d'entrée
        setTimeout(() => {
            loadingScreen.classList.remove('active');
            setTimeout(() => loadingScreen.remove(), 500);
            
            // Lancer des confettis
            if (typeof startConfetti === 'function') {
                startConfetti();
            }
            
            // Jouer un son (optionnel)
            this.playMetaverseSound();
        }, 2000);
    }

    playMetaverseSound() {
        // Créer un contexte audio pour un effet sonore
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(440, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(880, audioContext.currentTime + 1);
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 1);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 1);
    }

    exitMetaverse() {
        this.isInMetaverse = false;
        
        // Restaurer le thème normal
        document.body.style.background = '';
        
        document.querySelectorAll('.cyber-grid, .scanline').forEach(el => el.remove());
        
        document.querySelectorAll('.calendar-cell-metaverse').forEach(cell => {
            cell.classList.remove('calendar-cell-metaverse');
        });
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.metaverse = new MetaverseManager();
});