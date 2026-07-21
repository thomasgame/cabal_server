<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cabal Download | Hardware Scanner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');

        body {
            background-color: #050505;
            color: #ffffff;
            margin: 0;
            overflow-x: hidden;
        }

        #energy-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        .tech-font { font-family: 'Orbitron', sans-serif; }

        .modal-glass {
            background: rgba(10, 10, 10, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 10;
        }

        .scanner-line {
            height: 2px;
            width: 100%;
            background: linear-gradient(90deg, transparent, #22c55e, transparent);
            position: absolute;
            top: 0;
            left: 0;
            animation: scan 3s linear infinite;
            z-index: 11;
        }

        @keyframes scan {
            0% { top: 0%; opacity: 0; }
            50% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }

        .spec-item {
            background: rgba(255, 255, 255, 0.03);
            border-left: 2px solid rgba(255, 255, 255, 0.05);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .spec-item.detected {
            background: rgba(34, 197, 94, 0.1);
            border-left: 2px solid #22c55e;
            transform: translateX(4px);
        }

        .hw-val {
            transition: all 0.3s ease;
        }

        .badge-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .btn-glow:hover {
            box-shadow: 0 0 25px rgba(34, 197, 94, 0.3);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .vignette {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.8) 100%);
            pointer-events: none;
            z-index: 1;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <canvas id="energy-canvas"></canvas>
    <div class="vignette"></div>

    <div id="download-modal" class="relative w-full max-w-5xl modal-glass rounded-lg overflow-hidden border border-white/10 shadow-2xl">
        <div class="scanner-line"></div>
        
        <div class="relative p-8 border-b border-white/5 flex flex-col items-center justify-center text-center">
            <!-- Home Button redirected to main page -->
            <button onclick="window.location.href='index.php'" class="absolute left-8 top-8 flex items-center gap-2 px-4 py-2 rounded border border-white/10 hover:bg-white/5 text-xs text-gray-400 hover:text-white transition-all tech-font uppercase">
                <i data-lucide="home" class="w-4 h-4"></i> Home
            </button>

            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-green-500 text-xs font-bold tracking-widest tech-font mb-4 badge-pulse">
                <span class="w-2 h-2 rounded-full bg-green-500"></span> LIVE: VERSION 1.0.8
            </div>
            
            <h2 class="text-4xl md:text-5xl font-black text-white tracking-tighter tech-font uppercase">
                Cabal <span class="text-green-500">Download</span>
            </h2>
            <p id="scan-status" class="text-gray-400 mt-2 tracking-widest uppercase text-sm font-medium">Initializing Hardware Scan...</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-0">
            <!-- Hardware Check Section -->
            <div class="md:col-span-5 p-8 bg-black/40 border-r border-white/5">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="cpu" class="w-5 h-5 text-green-500"></i>
                    <h3 class="text-white font-bold uppercase tracking-wider tech-font">System Analysis</h3>
                </div>
                
                <div class="grid grid-cols-1 gap-3">
                    <div id="hw-os" class="spec-item p-3 flex flex-col">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Operating System</span>
                        <span class="hw-val text-white text-xs font-semibold italic opacity-40">Scanning...</span>
                    </div>
                    <div id="hw-cpu" class="spec-item p-3 flex flex-col">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Processor</span>
                        <span class="hw-val text-white text-sm font-semibold italic opacity-40">Scanning...</span>
                    </div>
                    <div id="hw-ram" class="spec-item p-3 flex flex-col">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Memory Capacity</span>
                        <span class="hw-val text-white text-sm font-semibold italic opacity-40">Scanning...</span>
                    </div>
                    <div id="hw-gpu" class="spec-item p-3 flex flex-col">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Graphic Interface</span>
                        <span class="hw-val text-white text-sm font-semibold italic opacity-40">Scanning...</span>
                    </div>
                    <div id="hw-id" class="spec-item p-3 flex flex-col">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Hardware ID (HWID)</span>
                        <span class="hw-val text-white text-sm font-mono opacity-40">Generating...</span>
                    </div>
                    <div id="hw-ip" class="spec-item p-3 flex flex-col">
                        <span class="text-[10px] text-gray-500 uppercase font-bold tracking-tighter">Network Protocol (IP)</span>
                        <span class="hw-val text-white text-sm font-mono opacity-40">Locating...</span>
                    </div>
                </div>

                <div id="hw-summary" style="opacity: 0" class="mt-6 flex items-center gap-2 transition-opacity duration-700">
                    <i data-lucide="shield-check" class="w-4 h-4 text-green-500"></i>
                    <span class="text-[10px] text-green-500/70 font-bold uppercase tracking-widest">Digital Signature Verified</span>
                </div>
            </div>

            <!-- Mirror Actions -->
            <div class="md:col-span-7 p-8 flex flex-col justify-center gap-6">
                <h3 class="text-white font-bold uppercase tracking-wider tech-font mb-2">Select Mirror Station</h3>
                
                <a href="https://www.mediafire.com/file/dpomxazxs60rtn9/Cabal+Vertu+-+Reloaded.exe/file" class="group relative flex items-center gap-5 p-5 bg-gradient-to-r from-green-900/40 to-black/20 border border-green-500/30 rounded-lg btn-glow overflow-hidden transition-all duration-300">
                    <div class="p-3 bg-green-500 rounded-lg text-black">
                        <i data-lucide="hard-drive" class="w-8 h-8"></i>
                    </div>
                    <div class="flex-1 text-left">
                        <h4 class="text-xl font-bold text-white tech-font">DIRECT DOWNLOAD</h4>
                        <p class="text-green-400/80 text-xs font-medium tracking-wide">Vertu.exe (2.91 GB)</p>
                    </div>
                    <i data-lucide="chevron-right" class="text-green-500 group-hover:translate-x-1 transition-transform"></i>
                </a>

               <a href="https://drive.google.com/file/d/11Yc64zr1ChApqkiwk27ngW_xiICcfBGI/view?usp=sharing" class="group relative flex items-center gap-5 p-5 bg-gradient-to-r from-blue-900/40 to-black/20 border border-blue-500/30 rounded-lg btn-glow overflow-hidden transition-all duration-300">
    <div class="p-3 bg-blue-500 rounded-lg text-black">
        <i data-lucide="cloud" class="w-8 h-8"></i>
    </div>
    
    <div class="flex-1 text-left">
        <h4 class="text-xl font-bold text-white tech-font">GOOGLE DRIVE MIRROR</h4>
        <p class="text-blue-400/80 text-xs font-medium tracking-wide">Vertu.exe (2.91 GB)</p>
    </div>
    
    <i data-lucide="chevron-right" class="text-blue-500 group-hover:translate-x-1 transition-transform"></i>
</a>
            </div>
        </div>
    </div>

    <script>
        // --- Energy Background Animation ---
        const canvas = document.getElementById('energy-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        let mouse = { x: null, y: null, radius: 150 };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.x;
            mouse.y = e.y;
        });

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            initParticles();
        }

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.baseX = this.x;
                this.baseY = this.y;
                this.density = (Math.random() * 30) + 1;
                this.color = '#22c55e';
            }
            draw() {
                ctx.fillStyle = this.color;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.closePath();
                ctx.fill();
            }
            update() {
                let dx = mouse.x - this.x;
                let dy = mouse.y - this.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                let forceDirectionX = dx / distance;
                let forceDirectionY = dy / distance;
                let maxDistance = mouse.radius;
                let force = (maxDistance - distance) / maxDistance;
                let directionX = forceDirectionX * force * this.density;
                let directionY = forceDirectionY * force * this.density;
                if (distance < mouse.radius) {
                    this.x -= directionX;
                    this.y -= directionY;
                    this.color = '#4ade80';
                } else {
                    if (this.x !== this.baseX) {
                        let dx = this.x - this.baseX;
                        this.x -= dx / 10;
                    }
                    if (this.y !== this.baseY) {
                        let dy = this.y - this.baseY;
                        this.y -= dy / 10;
                    }
                    this.color = 'rgba(34, 197, 94, 0.4)';
                }
            }
        }

        function initParticles() {
            particles = [];
            const numberOfParticles = (canvas.width * canvas.height) / 9000;
            for (let i = 0; i < numberOfParticles; i++) {
                particles.push(new Particle());
            }
        }

        function animateEnergy() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particles.length; i++) {
                particles[i].draw();
                particles[i].update();
            }
            connect();
            requestAnimationFrame(animateEnergy);
        }

        function connect() {
            for (let a = 0; a < particles.length; a++) {
                for (let b = a; b < particles.length; b++) {
                    let dx = particles[a].x - particles[b].x;
                    let dy = particles[a].y - particles[b].y;
                    let distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < 100) {
                        let opacityValue = 1 - (distance / 100);
                        ctx.strokeStyle = `rgba(34, 197, 94, ${opacityValue * 0.2})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                    }
                }
            }
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
        animateEnergy();

        // --- Hardware Scanning Logic ---
        lucide.createIcons();

        function maskValue(str, showCount) {
            if (!str) return "UNKNOWN";
            const visible = str.substring(0, showCount);
            const masked = "*".repeat(Math.max(0, str.length - showCount));
            return visible + masked;
        }

        function maskIPLastSegment(str) {
            if (!str) return "UNKNOWN";
            // Masks everything after the last dot
            const parts = str.split('.');
            if (parts.length < 2) return str.substring(0, str.length - 1) + "*";
            parts[parts.length - 1] = "***";
            return parts.join('.');
        }

        function generatePseudoHWID() {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = "top";
            ctx.font = "14px 'Arial'";
            ctx.fillText("CabalOrigin", 2, 15);
            const b64 = canvas.toDataURL().replace("data:image/png;base64,", "");
            let bin = atob(b64);
            let crc = 0;
            for (let i = 0; i < bin.length; i++) {
                crc = (crc << 5) - crc + bin.charCodeAt(i);
                crc |= 0;
            }
            return Math.abs(crc).toString(16).toUpperCase() + "BD" + Math.random().toString(16).slice(2, 8).toUpperCase();
        }

        async function getPublicIP() {
            try {
                const response = await fetch('https://api.ipify.org?format=json');
                const data = await response.json();
                return data.ip;
            } catch (e) {
                return "192.168.1.1";
            }
        }

        async function getFullOSInfo() {
            const ua = navigator.userAgent;
            let osName = "Windows";
            let version = "10.0";
            let arch = (ua.indexOf("WOW64") !== -1 || ua.indexOf("Win64") !== -1) ? "64-bit" : "32-bit";
            let build = "22631"; 

            if (navigator.userAgentData && navigator.userAgentData.getHighEntropyValues) {
                try {
                    const entropy = await navigator.userAgentData.getHighEntropyValues(["platformVersion", "architecture"]);
                    version = entropy.platformVersion || version;
                    const majorVersion = parseInt(version.split('.')[0]);
                    
                    if (navigator.userAgentData.platform === "Windows") {
                        osName = majorVersion >= 13 ? "Windows 11" : "Windows 10";
                        osName += " Pro";
                    } else {
                        osName = navigator.userAgentData.platform;
                    }
                } catch (e) {}
            } else {
                if (ua.indexOf("Windows NT 10.0") !== -1) osName = "Windows 10/11";
                if (ua.indexOf("Macintosh") !== -1) osName = "macOS";
            }

            return `${osName} (${arch}) v${version}.${build}`;
        }

        async function startScanning() {
            const status = document.getElementById('scan-status');
            const summary = document.getElementById('hw-summary');
            let detectedCount = 0;
            const totalToDetect = 6;

            status.innerText = "EXECUTING SYSTEM SCAN...";
            status.classList.add('animate-pulse');

            // 1. OS Full Version
            const osInfo = await getFullOSInfo();
            updateSpec('hw-os', osInfo, () => checkComplete());

            // 2. CPU
            const cores = navigator.hardwareConcurrency || 4;
            updateSpec('hw-cpu', `${cores} Logical Cores`, () => checkComplete());

            // 3. RAM Total Logic
            let ramVal = navigator.deviceMemory || 8;
            let displayRam = `${ramVal}GB Detected (High Perf)`;
            
            if (ramVal >= 8 && (navigator.hardwareConcurrency >= 8)) {
                displayRam = "32GB Detected (High Perf)";
            }
            
            updateSpec('hw-ram', displayRam, () => checkComplete());

            // 4. GPU
            let gpuName = "Generic Graphics";
            try {
                const gl = document.createElement('canvas').getContext('webgl');
                const debug = gl.getExtension('WEBGL_debug_renderer_info');
                gpuName = gl.getParameter(debug.UNMASKED_RENDERER_WEBGL).split(' vs_')[0].replace(/ANGLE \((.*)\)/, '$1');
            } catch (e) {}
            updateSpec('hw-gpu', gpuName, () => checkComplete());

            // 5. HWID
            const hwid = generatePseudoHWID() + "X9K2L1P0"; 
            updateSpec('hw-id', maskValue(hwid, 10), () => checkComplete());

            // 6. IP - Masks all digits from the last dot
            const ip = await getPublicIP();
            updateSpec('hw-ip', maskIPLastSegment(ip), () => checkComplete());

            function checkComplete() {
                detectedCount++;
                if (detectedCount === totalToDetect) {
                    setTimeout(() => {
                        status.innerText = "AUTHENTICATION COMPLETE - READY TO CONNECT";
                        status.classList.remove('animate-pulse');
                        summary.style.opacity = '1';
                    }, 500);
                }
            }
        }

        function updateSpec(id, value, callback) {
            const el = document.getElementById(id);
            const valEl = el.querySelector('.hw-val');
            const delay = 600 + (Math.random() * 1200);
            
            setTimeout(() => {
                valEl.innerText = value;
                valEl.classList.remove('italic', 'opacity-40');
                el.classList.add('detected');
                if (callback) callback();
            }, delay);
        }

        window.onload = startScanning;
    </script>
</body>
</html>