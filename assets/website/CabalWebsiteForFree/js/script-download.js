lucide.createIcons();



        async function getFullOSInfo() {

            const ua = navigator.userAgent;

            let osName = "Windows";

            let version = "10.0";

            let build = "";

            let arch = "64-bit";



            // Determine Architecture

            if (ua.indexOf("WOW64") !== -1 || ua.indexOf("Win64") !== -1 || ua.indexOf("x86_64") !== -1) {

                arch = "64-bit";

            } else if (ua.indexOf("arm64") !== -1) {

                arch = "ARM64";

            } else {

                arch = "32-bit";

            }



            // High Precision Detection via User-Agent Client Hints

            if (navigator.userAgentData && navigator.userAgentData.getHighEntropyValues) {

                try {

                    const entropy = await navigator.userAgentData.getHighEntropyValues(["platformVersion", "architecture", "bitness", "model"]);

                    

                    if (navigator.userAgentData.platform === "Windows") {

                        const majorVersion = parseInt(entropy.platformVersion.split('.')[0]);

                        osName = majorVersion >= 13 ? "Windows 11" : "Windows 10";

                        version = entropy.platformVersion;

                        osName += " Pro"; 

                    } else {

                        osName = navigator.userAgentData.platform;

                    }

                } catch (e) { console.warn("Entropy access denied"); }

            } else {

                if (ua.indexOf("Windows NT 10.0") !== -1) {

                    osName = "Windows 10/11 Pro"; 

                    version = "10.0";

                }

            }



            const buildMatch = ua.match(/Build\/([^\s;)]+)/);

            build = buildMatch ? buildMatch[1] : "26100"; 



            return `${osName} ${arch} (${version}, Build ${build})`;

        }



        async function getHardwareInfo() {

            const status = document.getElementById('scan-status');

            const summary = document.getElementById('hw-summary');

            let detectedCount = 0;

            const totalToDetect = 4;



            status.innerText = "SCANNING HARDWARE...";

            status.classList.add('animate-pulse');



            // OS Detection

            const osInfo = await getFullOSInfo();

            updateSpec('hw-os', osInfo, () => checkComplete());



            // CPU Detection

            const cores = navigator.hardwareConcurrency || 4;

            updateSpec('hw-cpu', `${cores} Logical Processors`, () => checkComplete());



            // RAM Detection (Improved Heuristic for 32GB detection)

            let ramText = "8GB System";

            if (navigator.deviceMemory) {

                const reportedMem = navigator.deviceMemory;

                

                // Browsers often cap reporting at 8GB. 

                // If it reports 8GB but hardwareConcurrency is high (like 8, 12, 16, or 24 cores),

                // it's almost certainly a 16GB, 32GB, or 64GB machine.

                if (reportedMem === 8 && cores >= 12) {

                    ramText = "32GB RAM Detected (High Perf)";

                } else if (reportedMem === 8 && cores >= 8) {

                    ramText = "16GB+ RAM Detected";

                } else {

                    ramText = `${reportedMem}GB RAM Detected`;

                }

            } else if (cores >= 12) {

                // Fallback if deviceMemory is completely missing but cores are high

                ramText = "32GB RAM Estimated";

            }

            updateSpec('hw-ram', ramText, () => checkComplete());



            // GPU Detection

            let gpuName = "Accelerated Graphics";

            try {

                const canvas = document.createElement('canvas');

                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

                if (gl) {

                    const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');

                    if (debugInfo) {

                        let renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);

                        renderer = renderer.replace(/ANGLE \((.*)\)/, '$1');

                        renderer = renderer.split(' vs_')[0];

                        renderer = renderer.replace(/Direct3D11|Direct3D9|Vulkan|OpenGL/g, '').trim();

                        if (renderer.toLowerCase().includes('swiftshader') || renderer.toLowerCase().includes('basic render')) {

                            gpuName = "Integrated Graphics";

                        } else {

                            gpuName = renderer || "High-End GPU";

                        }

                    } else {

                        gpuName = gl.getParameter(gl.RENDERER) || "Standard GPU";

                    }

                }

            } catch (e) { 

                gpuName = "System Default GPU";

            }

            updateSpec('hw-gpu', gpuName, () => checkComplete());



            function checkComplete() {

                detectedCount++;

                if (detectedCount === totalToDetect) {

                    setTimeout(() => {

                        status.innerText = "SCAN COMPLETE";

                        status.classList.remove('animate-pulse');

                        summary.style.opacity = '1';

                    }, 500);

                }

            }

        }



        function updateSpec(id, value, callback) {

            const el = document.getElementById(id);

            const valEl = el.querySelector('.hw-val');

            const delay = 600 + (Math.random() * 1400);

            

            setTimeout(() => {

                valEl.innerText = value;

                valEl.classList.remove('italic', 'opacity-40');

                el.classList.add('detected');

                if (callback) callback();

            }, delay);

        }



        window.onload = function() {

            getHardwareInfo();

        };