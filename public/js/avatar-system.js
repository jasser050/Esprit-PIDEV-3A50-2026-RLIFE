/**
 * RLIFE 3D AVATAR SYSTEM
 * Hybrid: GLB Model + Code-Generated Fallback
 */

class AvatarSystem {
    constructor(containerId, avatarPath = '/avatars/male-avatar.glb') {
        this.containerId = containerId;
        this.avatarPath = avatarPath;
        this.wrapper = null;
        this.canvasContainer = null;
        this.avatar = null;
        this.model = null;
        this.mixer = null;
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.clock = null;
        this.isInitialized = false;
        this.isModelLoaded = false;
        this.isUsingFallback = false;
        this.bones = {};
        this.meshes = {};
        this.animations = [];
        this.animationQueue = [];
        this.initPromise = null;
    }

    async init() {
        if (this.initPromise) return this.initPromise;
        
        this.initPromise = (async () => {
            if (this.isInitialized) return;
            
            console.log('🎬 Starting Avatar System...');
            
            // Create containers FIRST
            this.createContainers();
            this.setupScene();
            
            // Try to load GLB, fall back to code character if it fails
            try {
                await this.loadThreeJS();
                await this.loadGLTFLoader();
                console.log('📦 Attempting to load GLB model...');
                
                await Promise.race([
                    this.loadGLBModel(),
                    new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('GLB loading timeout')), 10000)
                    )
                ]);
                
                console.log('✅ Using GLB model!');
                this.isModelLoaded = true;
                this.isUsingFallback = false;
                
            } catch (error) {
                console.warn('⚠️ GLB failed, using fallback character:', error.message);
                this.createChibiCharacter();
                this.isUsingFallback = true;
                this.isModelLoaded = true;
            }
            
            // Start animation loop
            this.startAnimation();
            this.isInitialized = true;
            
            console.log('✅ Avatar Ready! (Using: ' + (this.isUsingFallback ? 'Code Character' : 'GLB Model') + ')');
            
            // Start idle animation if using GLB
            if (!this.isUsingFallback) {
                this.startIdleAnimation();
            }
            
            // Process queued animations
            this.processAnimationQueue();
            
        })();
        
        return this.initPromise;
    }

    async loadThreeJS() {
        if (window.THREE) return;
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load Three.js'));
            document.head.appendChild(script);
            
            setTimeout(() => {
                if (!window.THREE) reject(new Error('Three.js timeout'));
            }, 5000);
        });
    }

    async loadGLTFLoader() {
        if (window.THREE && window.THREE.GLTFLoader) return;
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load GLTFLoader'));
            document.head.appendChild(script);
            
            setTimeout(() => {
                if (!window.THREE?.GLTFLoader) reject(new Error('GLTFLoader timeout'));
            }, 5000);
        });
    }

    createContainers() {
        const old = document.getElementById('avatar-wrapper');
        if (old) old.remove();
        
        this.wrapper = document.createElement('div');
        this.wrapper.id = 'avatar-wrapper';
        this.wrapper.style.cssText = `
            position: fixed;
            width: 350px;
            height: 350px;
            z-index: 999999;
            pointer-events: none;
            display: block;
            bottom: -400px;
            left: 50%;
            transform: translateX(-50%);
        `;
        
        this.canvasContainer = document.createElement('div');
        this.canvasContainer.style.cssText = 'width: 100%; height: 100%;';
        
        this.wrapper.appendChild(this.canvasContainer);
        document.body.appendChild(this.wrapper);
    }

    setupScene() {
        this.clock = new THREE.Clock();
        this.scene = new THREE.Scene();
        
        // Transparent background - no color!
        this.scene.background = null;
        
        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
        this.camera.position.set(0, 1.2, 2.5);
        this.camera.lookAt(0, 0.8, 0);
        
        this.renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        this.renderer.setSize(350, 350);
        this.renderer.setClearColor(0x000000, 0); // Transparent!
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        this.canvasContainer.appendChild(this.renderer.domElement);
        
        // Super bright ambient light
        const ambient = new THREE.AmbientLight(0xffffff, 1.5);
        this.scene.add(ambient);
        
        // Main key light - warm white
        const mainLight = new THREE.DirectionalLight(0xfff5e6, 1.2);
        mainLight.position.set(3, 5, 3);
        mainLight.castShadow = true;
        mainLight.shadow.mapSize.width = 1024;
        mainLight.shadow.mapSize.height = 1024;
        this.scene.add(mainLight);
        
        // Fill light - cool blue
        const fillLight = new THREE.DirectionalLight(0xe6f0ff, 0.8);
        fillLight.position.set(-3, 3, 2);
        this.scene.add(fillLight);
        
        // Rim light - pink accent
        const rimLight = new THREE.DirectionalLight(0xffc0cb, 0.6);
        rimLight.position.set(0, 2, -3);
        this.scene.add(rimLight);
        
        // Front light - bright
        const frontLight = new THREE.DirectionalLight(0xffffff, 0.8);
        frontLight.position.set(0, 2, 4);
        this.scene.add(frontLight);
        
        // Bottom fill - soft
        const bottomLight = new THREE.DirectionalLight(0xf0f0ff, 0.3);
        bottomLight.position.set(0, -2, 0);
        this.scene.add(bottomLight);
    }

    async loadGLBModel() {
        return new Promise((resolve, reject) => {
            const loader = new THREE.GLTFLoader();
            
            loader.load(
                this.avatarPath,
                (gltf) => {
                    console.log('✅ GLB loaded, analyzing structure...');
                    this.model = gltf.scene;
                    
                    // Detect emotion variants and main character
                    this.detectEmotionVariants();
                    
                    // Apply vibrant colors
                    this.applyVibrantColors();
                    
                    // Setup animations
                    if (gltf.animations?.length > 0) {
                        this.mixer = new THREE.AnimationMixer(this.model);
                        gltf.animations.forEach(clip => {
                            this.mixer.clipAction(clip).play();
                        });
                        console.log(`🎬 Found ${gltf.animations.length} animations`);
                    }
                    
                    // Add to scene
                    this.avatar = new THREE.Group();
                    this.avatar.add(this.model);
                    this.scene.add(this.avatar);
                    
                    // Center and scale
                    this.centerModel();
                    
                    // Find bones for animation
                    this.findBones();
                    
                    resolve();
                },
                (progress) => {
                    if (progress.total > 0) {
                        const percent = (progress.loaded / progress.total * 100).toFixed(0);
                        console.log(`📥 Loading GLB: ${percent}%`);
                    }
                },
                (error) => reject(error)
            );
        });
    }
    
    detectEmotionVariants() {
        console.log('🔍 Detecting emotion variants...');
        
        this.emotionMeshes = {};
        const meshNames = [];
        
        this.model.traverse((child) => {
            if (child.isMesh) {
                const name = child.name.toLowerCase();
                meshNames.push(child.name);
                
                // Detect emotion variants by name patterns
                if (name.includes('smile') || name.includes('happy') || name.includes('joy')) {
                    this.emotionMeshes.smile = child;
                    console.log('  😊 Found: smile/happy mesh');
                }
                if (name.includes('sad') || name.includes('cry') || name.includes('tear')) {
                    this.emotionMeshes.sad = child;
                    console.log('  😢 Found: sad/cry mesh');
                }
                if (name.includes('angry') || name.includes('mad') || name.includes('rage')) {
                    this.emotionMeshes.angry = child;
                    console.log('  😠 Found: angry mesh');
                }
                if (name.includes('surprise') || name.includes('shock') || name.includes('wow')) {
                    this.emotionMeshes.surprise = child;
                    console.log('  😲 Found: surprise mesh');
                }
                if (name.includes('fear') || name.includes('scared') || name.includes('panic')) {
                    this.emotionMeshes.panic = child;
                    console.log('  😨 Found: panic/fear mesh');
                }
                if (name.includes('neutral') || name.includes('normal') || name.includes('idle')) {
                    this.emotionMeshes.neutral = child;
                    console.log('  😐 Found: neutral mesh');
                }
                if (name.includes('wink') || name.includes('blink')) {
                    this.emotionMeshes.wink = child;
                    console.log('  😉 Found: wink mesh');
                }
            }
        });
        
        console.log('📋 All mesh names:', meshNames.join(', '));
        console.log('😊 Found emotions:', Object.keys(this.emotionMeshes).join(', '));
    }
    
    applyVibrantColors() {
        console.log('🎨 Applying vibrant colors...');
        
        const colorPalette = {
            // Skin tones - warm and healthy
            skin: 0xffd4a3,
            face: 0xffdbac,
            body: 0xffcba4,
            
            // Hair - vibrant brown
            hair: 0x6b4423,
            mustache: 0x5a3a1a,
            
            // Eyes - bright and expressive
            eye: 0x2d1f1a,
            pupil: 0x3d2817,
            iris: 0x4a3728,
            
            // Clothing - bright school uniform colors
            shirt: 0x4a90e2,
            uniform: 0x5c7cfa,
            vest: 0x3b5bdb,
            blazer: 0x2f54eb,
            tie: 0xff6b35,
            collar: 0xffffff,
            pants: 0x1e3a5f,
            shoes: 0x1a1a2e,
            
            // Accessories - colorful
            glasses: 0x1a1a1a,
            glassesFrame: 0x000000,
            lens: 0x2a2a3a,
            
            // Background/props
            background: 0xf0f8ff,
            prop: 0x888888,
            
            // Default bright color
            default: 0xe8b4b8
        };
        
        let coloredCount = 0;
        
        this.model.traverse((child) => {
            if (child.isMesh && child.material) {
                const name = child.name.toLowerCase();
                let newColor = null;
                
                // Match mesh name to color
                for (const [key, color] of Object.entries(colorPalette)) {
                    if (name.includes(key)) {
                        newColor = color;
                        break;
                    }
                }
                
                // Apply vibrant material
                if (newColor) {
                    child.material = new THREE.MeshStandardMaterial({
                        color: newColor,
                        metalness: 0.1,
                        roughness: 0.7,
                        emissive: newColor,
                        emissiveIntensity: 0.05
                    });
                    coloredCount++;
                } else {
                    // Brighten dark materials
                    if (child.material.color) {
                        const currentColor = child.material.color.getHex();
                        if (currentColor < 0x444444) {
                            child.material.color.setHex(0x888888);
                            child.material.roughness = 0.6;
                        }
                    }
                }
                
                child.castShadow = true;
                child.receiveShadow = true;
            }
        });
        
        console.log(`✅ Applied vibrant colors to ${coloredCount} meshes`);
    }

    centerModel() {
        const box = new THREE.Box3().setFromObject(this.model);
        const center = new THREE.Vector3();
        box.getCenter(center);
        
        this.model.position.x = -center.x;
        this.model.position.y = -box.min.y;
        this.model.position.z = -center.z;
        
        const size = new THREE.Vector3();
        box.getSize(size);
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 1.5 / maxDim;
        this.model.scale.set(scale, scale, scale);
        
        console.log(`✓ Model scaled to ${scale.toFixed(2)}x`);
    }

    findBones() {
        this.bones = {};
        
        // Store all bones found
        const allBones = [];
        
        this.model.traverse((child) => {
            if (child.isBone) {
                allBones.push(child.name);
                console.log('  Found bone:', child.name);
            }
            
            if (!child.name) return;
            const name = child.name.toLowerCase();
            
            // Head
            if (name.includes('head') || name.includes('skull') || name.includes('neck')) {
                if (!this.bones.head) this.bones.head = child;
            }
            if (name.includes('neck')) this.bones.neck = child;
            
            // Spine
            if (name.includes('spine') || name.includes('torso') || name.includes('chest')) {
                if (!this.bones.spine) this.bones.spine = child;
            }
            
            // Left arm
            if ((name.includes('arm') || name.includes('shoulder')) && 
                (name.includes('l') || name.includes('left'))) {
                if (!this.bones.leftArm) this.bones.leftArm = child;
            }
            if ((name.includes('forearm') || name.includes('hand')) && 
                (name.includes('l') || name.includes('left'))) {
                if (!this.bones.leftHand) this.bones.leftHand = child;
            }
            
            // Right arm
            if ((name.includes('arm') || name.includes('shoulder')) && 
                (name.includes('r') || name.includes('right'))) {
                if (!this.bones.rightArm) this.bones.rightArm = child;
            }
            if ((name.includes('forearm') || name.includes('hand')) && 
                (name.includes('r') || name.includes('right'))) {
                if (!this.bones.rightHand) this.bones.rightHand = child;
            }
            
            // Left leg
            if ((name.includes('thigh') || name.includes('leg')) && 
                (name.includes('l') || name.includes('left'))) {
                if (!this.bones.leftLeg) this.bones.leftLeg = child;
            }
            if ((name.includes('calf') || name.includes('foot')) && 
                (name.includes('l') || name.includes('left'))) {
                if (!this.bones.leftFoot) this.bones.leftFoot = child;
            }
            
            // Right leg
            if ((name.includes('thigh') || name.includes('leg')) && 
                (name.includes('r') || name.includes('right'))) {
                if (!this.bones.rightLeg) this.bones.rightLeg = child;
            }
            if ((name.includes('calf') || name.includes('foot')) && 
                (name.includes('r') || name.includes('right'))) {
                if (!this.bones.rightFoot) this.bones.rightFoot = child;
            }
        });
        
        console.log('🦴 All bones:', allBones.join(', '));
        console.log('🦴 Mapped bones:', Object.keys(this.bones).join(', '));
    }
    
    // Idle breathing animation
    startIdleAnimation() {
        console.log('🎵 Starting idle breathing animation...');
        
        const animate = () => {
            if (!this.avatar || !this.isInitialized) return;
            
            const time = Date.now() * 0.001;
            
            // Breathing motion (subtle)
            if (this.avatar.position) {
                this.avatar.position.y = Math.sin(time * 1.5) * 0.01;
            }
            
            // Subtle head sway
            if (this.bones.head) {
                this.bones.head.rotation.y = Math.sin(time * 0.5) * 0.05;
                this.bones.head.rotation.x = Math.sin(time * 0.7) * 0.02;
            }
            
            requestAnimationFrame(animate);
        };
        
        animate();
    }

    // ===== CODE-GENERATED CHIBI CHARACTER =====
    createChibiCharacter() {
        console.log('🎨 Creating code-generated CHIBI character...');
        
        const character = new THREE.Group();
        
        // Materials
        const skinMat = new THREE.MeshStandardMaterial({ color: 0xffd4a3, roughness: 0.8 });
        const hairMat = new THREE.MeshStandardMaterial({ color: 0x4a3728, roughness: 0.9 });
        const shirtMat = new THREE.MeshStandardMaterial({ color: 0x4a90e2, roughness: 0.7 });
        const pantsMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.8 });
        const shoesMat = new THREE.MeshStandardMaterial({ color: 0x1e293b, roughness: 0.6 });
        const glassesMat = new THREE.MeshStandardMaterial({ color: 0x111111, metalness: 0.3, roughness: 0.2 });
        const tieMat = new THREE.MeshStandardMaterial({ color: 0xff6b35, roughness: 0.7 });
        const whiteMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.8 });
        const pupilMat = new THREE.MeshStandardMaterial({ color: 0x4a90e2, roughness: 0.3 });
        
        // === HEAD (big chibi style) ===
        const headGroup = new THREE.Group();
        
        const headGeo = new THREE.SphereGeometry(0.28, 32, 32);
        const head = new THREE.Mesh(headGeo, skinMat);
        headGroup.add(head);
        this.bones.head = headGroup;
        
        // Hair
        const hairGeo = new THREE.SphereGeometry(0.3, 32, 32, 0, Math.PI * 2, 0, Math.PI / 2);
        const hair = new THREE.Mesh(hairGeo, hairMat);
        hair.position.y = 0.05;
        headGroup.add(hair);
        
        // Hair front
        const hairFrontGeo = new THREE.SphereGeometry(0.15, 16, 16);
        const hairFront = new THREE.Mesh(hairFrontGeo, hairMat);
        hairFront.position.set(0, 0.15, 0.22);
        headGroup.add(hairFront);
        
        // === EYES ===
        const eyeWhiteGeo = new THREE.SphereGeometry(0.07, 16, 16);
        const eyeWhiteMat = new THREE.MeshStandardMaterial({ color: 0xffffff, roughness: 0.3 });
        
        // Left eye
        const leftEyeGroup = new THREE.Group();
        const leftEye = new THREE.Mesh(eyeWhiteGeo, eyeWhiteMat);
        leftEyeGroup.add(leftEye);
        
        const leftPupilGeo = new THREE.SphereGeometry(0.04, 16, 16);
        const leftPupil = new THREE.Mesh(leftPupilGeo, pupilMat);
        leftPupil.position.z = 0.05;
        leftEyeGroup.add(leftPupil);
        
        leftEyeGroup.position.set(-0.1, 0.05, 0.22);
        headGroup.add(leftEyeGroup);
        
        // Right eye
        const rightEyeGroup = new THREE.Group();
        const rightEye = new THREE.Mesh(eyeWhiteGeo, eyeWhiteMat);
        rightEyeGroup.add(rightEye);
        
        const rightPupil = new THREE.Mesh(leftPupilGeo, pupilMat);
        rightPupil.position.z = 0.05;
        rightEyeGroup.add(rightPupil);
        
        rightEyeGroup.position.set(0.1, 0.05, 0.22);
        headGroup.add(rightEyeGroup);
        
        this.bones.leftPupil = leftPupil;
        this.bones.rightPupil = rightPupil;
        
        // Nose
        const noseGeo = new THREE.SphereGeometry(0.02, 8, 8);
        const nose = new THREE.Mesh(noseGeo, skinMat);
        nose.position.set(0, -0.02, 0.26);
        headGroup.add(nose);
        
        // Smile
        const smileGeo = new THREE.TorusGeometry(0.06, 0.015, 8, 16, Math.PI);
        const smile = new THREE.Mesh(smileGeo, new THREE.MeshStandardMaterial({ color: 0xcc6666, roughness: 0.5 }));
        smile.rotation.x = Math.PI;
        smile.position.set(0, -0.1, 0.22);
        smile.scale.y = 0.5;
        headGroup.add(smile);
        this.bones.smile = smile;
        
        // === GLASSES ===
        const glassesGroup = new THREE.Group();
        
        const lensGeo = new THREE.CylinderGeometry(0.08, 0.08, 0.02, 32);
        
        const leftLens = new THREE.Mesh(lensGeo, glassesMat);
        leftLens.rotation.x = Math.PI / 2;
        leftLens.position.x = -0.1;
        glassesGroup.add(leftLens);
        
        const rightLens = new THREE.Mesh(lensGeo, glassesMat);
        rightLens.rotation.x = Math.PI / 2;
        rightLens.position.x = 0.1;
        glassesGroup.add(rightLens);
        
        // Bridge
        const bridgeGeo = new THREE.CylinderGeometry(0.01, 0.01, 0.08, 8);
        const bridge = new THREE.Mesh(bridgeGeo, glassesMat);
        bridge.rotation.z = Math.PI / 2;
        glassesGroup.add(bridge);
        
        glassesGroup.position.z = 0.24;
        headGroup.add(glassesGroup);
        
        // === CAP (pink/blue baseball cap) ===
        const capGroup = new THREE.Group();
        
        const capTopGeo = new THREE.SphereGeometry(0.18, 32, 16, 0, Math.PI * 2, 0, Math.PI / 2);
        const capTopMat = new THREE.MeshStandardMaterial({ color: 0x74c0fc, roughness: 0.8 });
        const capTop = new THREE.Mesh(capTopGeo, capTopMat);
        capTop.rotation.x = Math.PI;
        capTop.position.y = 0.15;
        capGroup.add(capTop);
        
        // Brim
        const brimGeo = new THREE.SphereGeometry(0.22, 32, 16, 0, Math.PI, 0, Math.PI / 4);
        const brimMat = new THREE.MeshStandardMaterial({ color: 0xf48fb1, roughness: 0.8 });
        const brim = new THREE.Mesh(brimGeo, brimMat);
        brim.rotation.x = Math.PI / 2;
        brim.position.set(0, 0.08, 0.08);
        brim.scale.z = 0.3;
        capGroup.add(brim);
        
        // Button
        const buttonGeo = new THREE.SphereGeometry(0.025, 16, 16);
        const buttonMat = new THREE.MeshStandardMaterial({ color: 0xf48fb1, roughness: 0.6 });
        const button = new THREE.Mesh(buttonGeo, buttonMat);
        button.position.y = 0.28;
        capGroup.add(button);
        
        capGroup.position.y = 0.25;
        headGroup.add(capGroup);
        
        headGroup.position.y = 0.85;
        character.add(headGroup);
        
        // === BODY (tiny chibi body) ===
        const bodyGroup = new THREE.Group();
        
        // Vest
        const vestGeo = new THREE.CylinderGeometry(0.18, 0.15, 0.35, 16);
        const vestMat = new THREE.MeshStandardMaterial({ color: 0x5c7cfa, roughness: 0.8 });
        const vest = new THREE.Mesh(vestGeo, vestMat);
        bodyGroup.add(vest);
        
        // White collar
        const collarGeo = new THREE.CylinderGeometry(0.12, 0.14, 0.08, 16);
        const collar = new THREE.Mesh(collarGeo, whiteMat);
        collar.position.y = 0.2;
        bodyGroup.add(collar);
        
        // Orange tie
        const tieGeo = new THREE.CylinderGeometry(0.025, 0.035, 0.15, 8);
        const tie = new THREE.Mesh(tieGeo, tieMat);
        tie.position.y = 0.12;
        bodyGroup.add(tie);
        
        bodyGroup.position.y = 0.45;
        character.add(bodyGroup);
        
        // === ARMS ===
        // Left arm
        const leftArmGroup = new THREE.Group();
        const armGeo = new THREE.CylinderGeometry(0.04, 0.035, 0.25, 12);
        const leftArm = new THREE.Mesh(armGeo, skinMat);
        leftArm.position.y = -0.1;
        leftArmGroup.add(leftArm);
        
        const leftHandGeo = new THREE.SphereGeometry(0.05, 12, 12);
        const leftHand = new THREE.Mesh(leftHandGeo, skinMat);
        leftHand.position.y = -0.25;
        leftArmGroup.add(leftHand);
        
        leftArmGroup.position.set(-0.22, 0.55, 0);
        leftArmGroup.rotation.z = 0.4;
        character.add(leftArmGroup);
        this.bones.leftArm = leftArmGroup;
        
        // Right arm
        const rightArmGroup = new THREE.Group();
        const rightArm = new THREE.Mesh(armGeo, skinMat);
        rightArm.position.y = -0.1;
        rightArmGroup.add(rightArm);
        
        const rightHand = new THREE.Mesh(leftHandGeo, skinMat);
        rightHand.position.y = -0.25;
        rightArmGroup.add(rightHand);
        
        rightArmGroup.position.set(0.22, 0.55, 0);
        rightArmGroup.rotation.z = -0.4;
        character.add(rightArmGroup);
        this.bones.rightArm = rightArmGroup;
        
        // === LEGS ===
        // Left leg
        const leftLegGroup = new THREE.Group();
        const legGeo = new THREE.CylinderGeometry(0.055, 0.05, 0.3, 12);
        const leftLeg = new THREE.Mesh(legGeo, pantsMat);
        leftLeg.position.y = -0.15;
        leftLegGroup.add(leftLeg);
        
        const leftShoeGeo = new THREE.BoxGeometry(0.1, 0.08, 0.14);
        const leftShoe = new THREE.Mesh(leftShoeGeo, shoesMat);
        leftShoe.position.set(0, -0.32, 0.02);
        leftLegGroup.add(leftShoe);
        
        leftLegGroup.position.set(-0.08, 0.22, 0);
        character.add(leftLegGroup);
        this.bones.leftLeg = leftLegGroup;
        
        // Right leg
        const rightLegGroup = new THREE.Group();
        const rightLeg = new THREE.Mesh(legGeo, pantsMat);
        rightLeg.position.y = -0.15;
        rightLegGroup.add(rightLeg);
        
        const rightShoe = new THREE.Mesh(leftShoeGeo, shoesMat);
        rightShoe.position.set(0, -0.32, 0.02);
        rightLegGroup.add(rightShoe);
        
        rightLegGroup.position.set(0.08, 0.22, 0);
        character.add(rightLegGroup);
        this.bones.rightLeg = rightLegGroup;
        
        // Add to scene
        this.avatar = character;
        this.scene.add(this.avatar);
        
        console.log('✅ Chibi character created with all body parts!');
    }

    startAnimation() {
        const animate = () => {
            requestAnimationFrame(animate);
            
            const delta = this.clock.getDelta();
            
            if (this.mixer) {
                this.mixer.update(delta);
            }
            
            if (this.renderer && this.scene && this.camera) {
                this.renderer.render(this.scene, this.camera);
            }
        };
        
        animate();
    }

    // ===== ANIMATION METHODS =====
    
    // Set character emotion
    setEmotion(emotion) {
        console.log(`🎭 Setting emotion: ${emotion}`);
        
        // Hide all emotion meshes first
        for (const [key, mesh] of Object.entries(this.emotionMeshes || {})) {
            if (mesh) mesh.visible = false;
        }
        
        // Show requested emotion
        if (this.emotionMeshes && this.emotionMeshes[emotion]) {
            this.emotionMeshes[emotion].visible = true;
        }
        
        // Also change body language based on emotion
        if (this.bones.spine || this.bones.head) {
            switch(emotion) {
                case 'happy':
                case 'smile':
                    // Upright, confident
                    break;
                case 'sad':
                    // Slumped shoulders
                    break;
                case 'panic':
                    // Tense, erratic
                    break;
                case 'surprise':
                    // Stiff, alert
                    break;
            }
        }
    }

    async playLoginAnimation() {
        if (!this.isModelLoaded) {
            console.log('⏳ Queueing animation...');
            this.animationQueue.push({ method: 'playLoginAnimation', args: [] });
            await this.init();
            return;
        }
        
        console.log('🎬 Playing EMOTIONAL ROTATION ENTRY animation!');
        
        const wrapper = this.wrapper;
        const avatar = this.avatar;
        
        // Reset position
        wrapper.style.left = '50%';
        wrapper.style.bottom = '30px';
        wrapper.style.transform = 'translateX(-50%) scale(0.5) rotate(-180deg)';
        wrapper.style.display = 'block';
        wrapper.style.opacity = '0';
        
        const tl = gsap.timeline();
        
        // ===== SCENE 1: DRAMATIC ENTRY =====
        console.log('🎬 Scene 1: Dramatic spin entrance...');
        
        // Spin in from 360 degrees
        tl.to(wrapper, {
            duration: 1.5,
            opacity: 1,
            scale: 1,
            rotation: 0,
            ease: 'power3.out'
        });
        
        // Character does a full spin
        if (avatar) {
            tl.to(avatar.rotation, {
                duration: 1.5,
                y: Math.PI * 2,
                ease: 'power2.inOut'
            }, 0);
        }
        
        // Sparkle trail during spin
        tl.add(() => {
            this.createParticles(0.2, 0.4, 12, 0xffd700);
            this.createParticles(0.5, 0.6, 12, 0xff69b4);
            this.createParticles(0.8, 0.4, 12, 0x00ced1);
        }, 0.3);
        
        tl.add(() => {
            this.createParticles(0.3, 0.3, 12, 0xffd700);
            this.createParticles(0.6, 0.5, 12, 0x9370db);
            this.createParticles(0.7, 0.7, 12, 0x20b2aa);
        }, 0.8);
        
        // ===== SCENE 2: HAPPY EXPRESSION =====
        tl.add(() => {
            console.log('😊 Showing happy expression!');
            this.setEmotion('smile');
        });
        
        // ===== SCENE 3: EXCITED WAVE =====
        tl.add(() => console.log('👋 Excited waving!'), '+=0.2');
        
        // Both arms wave excitedly
        if (this.bones.rightArm) {
            tl.to(this.bones.rightArm.rotation, {
                duration: 0.3,
                z: -2.2,
                x: -0.3,
                ease: 'back.out(1.5)'
            });
            
            // Rapid excited waves
            for (let i = 0; i < 6; i++) {
                tl.to(this.bones.rightArm.rotation, {
                    duration: 0.1,
                    z: (i % 2 === 0) ? -2.5 : -1.9,
                    x: (i % 2 === 0) ? -0.5 : -0.1,
                    ease: 'power1.inOut'
                });
            }
        }
        
        if (this.bones.leftArm) {
            tl.to(this.bones.leftArm.rotation, {
                duration: 0.3,
                z: 2.0,
                x: -0.2,
                ease: 'back.out(1.5)'
            }, '-=1.0');
            
            // Wave in sync
            for (let i = 0; i < 6; i++) {
                tl.to(this.bones.leftArm.rotation, {
                    duration: 0.1,
                    z: (i % 2 === 0) ? 2.3 : 1.7,
                    ease: 'power1.inOut'
                }, '<');
            }
        }
        
        // Lower arms
        if (this.bones.rightArm) {
            tl.to(this.bones.rightArm.rotation, { duration: 0.4, z: 0, x: 0, ease: 'power2.inOut' });
        }
        if (this.bones.leftArm) {
            tl.to(this.bones.leftArm.rotation, { duration: 0.4, z: 0, x: 0, ease: 'power2.inOut' }, '-=0.3');
        }
        
        // ===== SCENE 4: HAPPY BOUNCE =====
        console.log('🎵 Happy bounce!');
        
        if (avatar) {
            tl.to(avatar.position, { duration: 0.15, y: 0.08, ease: 'power2.out' });
            tl.to(avatar.position, { duration: 0.15, y: 0, ease: 'power2.in' });
            tl.to(avatar.position, { duration: 0.2, y: 0.05, ease: 'power2.out' });
            tl.to(avatar.position, { duration: 0.25, y: 0, ease: 'bounce.out' });
        }
        
        // ===== SCENE 5: JOYFUL SPARKLES =====
        tl.add(() => {
            console.log('✨ JOYFUL SPARKLES!');
            this.createParticles(0.3, 0.3, 20, 0xffd700);  // Gold
            this.createParticles(0.5, 0.2, 15, 0xff69b4); // Pink
            this.createParticles(0.7, 0.4, 15, 0x00ced1); // Cyan
            this.createParticles(0.4, 0.5, 15, 0x9370db); // Purple
        });
        
        // ===== SCENE 6: HAPPY HEAD BOB =====
        if (this.bones.head) {
            tl.to(this.bones.head.rotation, { duration: 0.2, x: 0.15, y: -0.1, ease: 'power2.out' });
            tl.to(this.bones.head.rotation, { duration: 0.2, x: -0.1, y: 0.1, ease: 'power2.inOut' });
            tl.to(this.bones.head.rotation, { duration: 0.2, x: 0.1, y: 0, ease: 'power2.inOut' });
            tl.to(this.bones.head.rotation, { duration: 0.3, x: 0, y: 0, ease: 'power2.inOut' });
        }
        
        // ===== SCENE 7: IDLE HAPPY STATE =====
        tl.add(() => {
            console.log('😊 Settling into happy idle state...');
            
            // Start idle breathing animation
            this.startHappyIdleAnimation();
        });
        
        console.log('✅ EMOTIONAL Login animation complete!');
    }
    
    startHappyIdleAnimation() {
        console.log('🎵 Starting happy idle animation...');
        
        let time = 0;
        const originalHeadRot = { x: 0, y: 0, z: 0 };
        const originalArmRot = { x: 0, y: 0, z: 0 };
        
        if (this.bones.head) {
            originalHeadRot.x = this.bones.head.rotation.x;
            originalHeadRot.y = this.bones.head.rotation.y;
        }
        if (this.bones.rightArm) {
            originalArmRot.z = this.bones.rightArm.rotation.z;
        }
        
        const idleAnimation = () => {
            if (!this.avatar || !this.isInitialized) return;
            
            time += 0.016;
            
            // Gentle breathing
            if (this.avatar.position) {
                this.avatar.position.y = Math.sin(time * 1.5) * 0.01;
            }
            
            // Happy head sway
            if (this.bones.head) {
                this.bones.head.rotation.y = originalHeadRot.y + Math.sin(time * 0.8) * 0.08;
                this.bones.head.rotation.x = originalHeadRot.x + Math.sin(time * 1.2) * 0.03;
            }
            
            // Subtle arm movement
            if (this.bones.rightArm) {
                this.bones.rightArm.rotation.z = originalArmRot.z + Math.sin(time * 0.6) * 0.05;
            }
            
            requestAnimationFrame(idleAnimation);
        };
        
        idleAnimation();
    }

    async playDashboardAnimation() {
        if (!this.isModelLoaded) {
            this.animationQueue.push({ method: 'playDashboardAnimation', args: [] });
            await this.init();
            return;
        }
        
        console.log('🎬 Playing EXCITED DASHBOARD animation!');
        
        const wrapper = this.wrapper;
        wrapper.style.right = '30px';
        wrapper.style.top = '-350px';
        wrapper.style.left = 'auto';
        wrapper.style.bottom = 'auto';
        wrapper.style.display = 'block';
        wrapper.style.opacity = '1';
        
        const tl = gsap.timeline();
        
        // ===== SCENE 1: SURPRISE ENTRY =====
        console.log('😲 Surprise entry!');
        this.setEmotion('surprise');
        
        // Pop in with surprise
        tl.to(wrapper, { 
            duration: 0.4, 
            top: '60px', 
            scale: 1.1,
            ease: 'back.out(2.5)' 
        });
        
        // Scale bounce
        tl.to(wrapper, { 
            duration: 0.2, 
            scale: 1,
            ease: 'power2.out' 
        });
        
        // ===== SCENE 2: HAPPY EXCITEMENT =====
        tl.add(() => {
            console.log('😊 Switching to happy!');
            this.setEmotion('smile');
        });
        
        // Excited head shake
        if (this.bones.head) {
            tl.to(this.bones.head.rotation, { 
                duration: 0.15, 
                y: 0.3, 
                ease: 'power2.out' 
            });
            tl.to(this.bones.head.rotation, { 
                duration: 0.15, 
                y: -0.3, 
                ease: 'power2.inOut' 
            });
            tl.to(this.bones.head.rotation, { 
                duration: 0.15, 
                y: 0.2, 
                ease: 'power2.inOut' 
            });
            tl.to(this.bones.head.rotation, { 
                duration: 0.15, 
                y: -0.1, 
                ease: 'power2.inOut' 
            });
            tl.to(this.bones.head.rotation, { 
                duration: 0.3, 
                y: 0, 
                ease: 'power2.inOut' 
            });
        }
        
        // ===== SCENE 3: WAVE =====
        if (this.bones.rightArm) {
            tl.to(this.bones.rightArm.rotation, {
                duration: 0.2,
                z: -1.8,
                ease: 'power2.out'
            });
            
            // Quick waves
            for (let i = 0; i < 4; i++) {
                tl.to(this.bones.rightArm.rotation, {
                    duration: 0.1,
                    z: (i % 2 === 0) ? -2.1 : -1.5,
                    ease: 'power1.inOut'
                });
            }
            
            tl.to(this.bones.rightArm.rotation, { duration: 0.3, z: 0, ease: 'power2.inOut' });
        }
        
        // ===== SCENE 4: HAPPY SPARKLES =====
        tl.add(() => {
            console.log('✨ Happy sparkles!');
            this.createParticles(0.5, 0.3, 20, 0xffd700);
            this.createParticles(0.6, 0.5, 15, 0xff69b4);
            this.createParticles(0.4, 0.4, 10, 0x00ced1);
        });
        
        // ===== SCENE 5: HAPPY IDLE =====
        tl.add(() => {
            console.log('😊 Starting happy idle...');
            this.startHappyIdleAnimation();
        }, '+=0.5');
        
        // Fade out after delay
        tl.to(wrapper, { duration: 0.5, opacity: 0, delay: 2 });
        tl.add(() => wrapper.style.display = 'none');
    }

    async playSettingsClimbAnimation(targetX, targetY) {
        if (!this.isModelLoaded) {
            this.animationQueue.push({ method: 'playSettingsClimbAnimation', args: [targetX, targetY] });
            await this.init();
            return;
        }
        
        console.log('🎬 Playing SETTINGS CLIMB animation!');
        
        const wrapper = this.wrapper;
        wrapper.style.left = targetX + 'px';
        wrapper.style.top = (targetY + 200) + 'px';
        wrapper.style.right = 'auto';
        wrapper.style.bottom = 'auto';
        wrapper.style.display = 'block';
        
        // Climb up
        gsap.to(wrapper, {
            duration: 1.2,
            top: (targetY - 80) + 'px',
            ease: 'power2.out',
            onUpdate: () => {
                if (this.bones.leftArm && this.bones.rightArm) {
                    const progress = gsap.getProperty(wrapper, 'top') / (targetY + 200);
                    this.bones.leftArm.rotation.z = Math.sin(progress * Math.PI * 8) * 0.4;
                    this.bones.rightArm.rotation.z = -Math.sin(progress * Math.PI * 8) * 0.4;
                }
            }
        });
        
        // Sparkles
        setTimeout(() => this.createParticles(0.5, 0.5, 20, 0xffd700), 1000);
    }

    createParticles(centerX, centerY, count, color) {
        if (!this.wrapper) return;
        
        const particles = [];
        const wrapperRect = this.wrapper.getBoundingClientRect();
        
        for (let i = 0; i < count; i++) {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: absolute;
                width: 6px;
                height: 6px;
                background: #${color.toString(16).padStart(6, '0')};
                border-radius: 50%;
                pointer-events: none;
                left: ${centerX * wrapperRect.width}px;
                top: ${centerY * wrapperRect.height}px;
                box-shadow: 0 0 8px #${color.toString(16).padStart(6, '0')};
            `;
            
            this.wrapper.appendChild(particle);
            particles.push(particle);
            
            const angle = (i / count) * Math.PI * 2;
            const distance = 40 + Math.random() * 80;
            const tx = Math.cos(angle) * distance;
            const ty = Math.sin(angle) * distance;
            
            gsap.to(particle, {
                duration: 0.8 + Math.random() * 0.4,
                x: tx,
                y: ty,
                opacity: 0,
                scale: 0,
                ease: 'power2.out',
                onComplete: () => particle.remove()
            });
        }
    }

    hide() {
        if (this.wrapper) this.wrapper.style.display = 'none';
    }

    show() {
        if (this.wrapper) this.wrapper.style.display = 'block';
    }

    processAnimationQueue() {
        console.log(`📋 Processing ${this.animationQueue.length} queued animations`);
        while (this.animationQueue.length > 0) {
            const { method, args } = this.animationQueue.shift();
            this[method](...args);
        }
    }
}

// Global instance
window.avatarSystem = null;

// Auto-init on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.avatarSystem = new AvatarSystem('avatar-container');
    });
} else {
    window.avatarSystem = new AvatarSystem('avatar-container');
}
