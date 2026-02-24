// assets/js/confetti.js

class ConfettiManager {
    constructor() {
        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.confetti = [];
        this.animationId = null;
        this.init();
    }

    init() {
        this.canvas.style.position = 'fixed';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.width = '100%';
        this.canvas.style.height = '100%';
        this.canvas.style.pointerEvents = 'none';
        this.canvas.style.zIndex = '9999';
        document.body.appendChild(this.canvas);

        this.resize();
        window.addEventListener('resize', () => this.resize());
    }

    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }

    createConfetti() {
        const colors = [
            '#6366f1', '#8b5cf6', '#d946ef', '#ec4899', 
            '#06b6d4', '#10b981', '#f59e0b', '#ef4444',
            '#3b82f6', '#a855f7'
        ];
        
        return {
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height - this.canvas.height,
            size: Math.random() * 10 + 5,
            speedX: Math.random() * 2 - 1,
            speedY: Math.random() * 3 + 2,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * 360,
            rotationSpeed: Math.random() * 8 - 4,
            shape: Math.floor(Math.random() * 3), // 0: rectangle, 1: cercle, 2: triangle
            opacity: 0.8 + Math.random() * 0.2
        };
    }

    start(count = 120) {
        // Arrêter l'animation précédente
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.confetti = [];
        }

        // Créer les confettis
        for (let i = 0; i < count; i++) {
            this.confetti.push(this.createConfetti());
        }

        this.animate();

        // Arrêt automatique après 5 secondes
        setTimeout(() => this.stop(), 5000);
    }

    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        let stillActive = false;
        
        this.confetti.forEach((c, index) => {
            // Mise à jour position
            c.x += c.speedX;
            c.y += c.speedY;
            c.rotation += c.rotationSpeed;
            
            // Ajouter un peu de physique
            c.speedY += 0.05; // Gravité
            c.speedX *= 0.99; // Friction
            
            if (c.y < this.canvas.height + 100) {
                stillActive = true;
            }
            
            this.ctx.save();
            this.ctx.translate(c.x, c.y);
            this.ctx.rotate((c.rotation * Math.PI) / 180);
            
            this.ctx.fillStyle = c.color;
            this.ctx.globalAlpha = c.opacity * (1 - c.y / (this.canvas.height + 200));
            this.ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
            this.ctx.shadowBlur = 10;
            this.ctx.shadowOffsetX = 2;
            this.ctx.shadowOffsetY = 2;
            
            // Dessiner selon la forme
            if (c.shape === 0) {
                // Rectangle
                this.ctx.fillRect(-c.size / 2, -c.size / 4, c.size, c.size / 2);
            } else if (c.shape === 1) {
                // Cercle
                this.ctx.beginPath();
                this.ctx.arc(0, 0, c.size / 2, 0, Math.PI * 2);
                this.ctx.fill();
            } else {
                // Triangle
                this.ctx.beginPath();
                this.ctx.moveTo(0, -c.size / 2);
                this.ctx.lineTo(c.size / 2, c.size / 2);
                this.ctx.lineTo(-c.size / 2, c.size / 2);
                this.ctx.closePath();
                this.ctx.fill();
            }
            
            this.ctx.restore();
        });
        
        // Nettoyer les confettis hors écran
        this.confetti = this.confetti.filter(c => c.y < this.canvas.height + 100);
        
        if (stillActive) {
            this.animationId = requestAnimationFrame(() => this.animate());
        }
    }

    stop() {
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
            this.animationId = null;
        }
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.confetti = [];
    }

    celebrate(type = 'success') {
        this.start(type === 'success' ? 120 : 80);
    }
}

// Instance globale
let confettiManager;
document.addEventListener('DOMContentLoaded', () => {
    confettiManager = new ConfettiManager();
});

// Fonctions globales
function startConfetti() {
    if (confettiManager) {
        confettiManager.celebrate('success');
    }
}

function stopConfetti() {
    if (confettiManager) {
        confettiManager.stop();
    }
}