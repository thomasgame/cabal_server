  
// ---REGISTER ---
tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-bg': '#0F0F0F',
                        'card-bg': '#171717',
                        'accent-orange': '#FF8800',
                        'accent-red': '#B44',
                        'shadow-orange': 'rgba(255, 136, 0, 0.6)',
                    },
                    boxShadow: {
                        'neon': '0 0 10px var(--tw-shadow-color), 0 0 20px var(--tw-shadow-color)',
                    }
                },
                // Use a fantasy/serif-style font for the title
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                    serif: ['"Copperplate"', 'Cambria', 'Georgia', 'serif'],
                }
            }
        }
   

        // Modal functions
       /* const openModal = (id) => {
            document.getElementById(`${id}-modal`).classList.remove('hidden');
        };
        
        const closeModal = (id) => {
            document.getElementById(`${id}-modal`).classList.add('hidden');
        };*/

        // Placeholder for simulating login flow, since we only created a Register modal
        const alertLoginSimulated = () => {
            closeModal('register');
            Swal.fire({
                icon: 'info',
                title: 'Login Access',
                text: 'If this were a full application, the Login form would appear here.'
            });
        };

        document.addEventListener('DOMContentLoaded', () => {

            // --- Form Submission Helpers ---
            // Validates all fields, including password confirmation
            const validateForm = (username, password, email, confirmPassword) => {
                if (username.length < 4 || password.length < 4) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Username and password must be at least 4 characters long.'
                    });
                    return false;
                }
                if (password !== confirmPassword) {
                     Swal.fire({
                        icon: 'warning',
                        title: 'Password Mismatch',
                        text: 'Password and confirmation password do not match.'
                    });
                    return false;
                }
                if (!/@/.test(email)) {
                     Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please enter a valid email address.'
                    });
                    return false;
                }
                return true;
            };

            // Handles the asynchronous submission to the backend endpoint
            const submitForm = async (url, formData) => {
                Swal.fire({
                    title: 'Processing...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        closeModal('register'); // Close modal on success
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'An unknown error occurred on the server.'
                        });
                    }

                } catch (error) {
                    console.error('Fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: 'Please try again later. Details: ' + error.message
                    });
                }
            };


           

            document.getElementById("modalSignupForm").addEventListener("submit", function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const email = formData.get("email").trim();
                const username = formData.get("username").trim();
                const password = formData.get("password").trim();
                const confirmPassword = formData.get("confirm_password").trim();

                if (validateForm(username, password, email, confirmPassword)) {
                    // Submit data to the backend PHP script
                    submitForm('signup.php', formData);
                }
            });

        });
 // --- END REGISTER ---
//			Disable Right Lick
// --- UI Utility: Custom Alert Function ---
        function showBlockedMessage() {
            const alertBox = document.getElementById('alertBox');
            // Show the alert
            alertBox.classList.remove('opacity-0', 'pointer-events-none');
            alertBox.classList.add('opacity-100');

            // Hide the alert after 3 seconds
            setTimeout(() => {
                alertBox.classList.remove('opacity-100');
                alertBox.classList.add('opacity-0', 'pointer-events-none');
            }, 3000);
        }

        // --- METHOD 1: Disable the Right-Click Context Menu ---
        // This prevents "Inspect Element" and "View Page Source" via the mouse.
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault(); // Stop the default context menu from appearing
            showBlockedMessage();
        });

        // --- METHOD 2: Disable Developer Tool Shortcuts (F12, Ctrl/Cmd + Shift + I) ---
        // This tries to block the most common keyboard commands for opening developer tools.
        document.addEventListener('keydown', (e) => {
            // Check for F12 key (keyCode 123)
            if (e.keyCode === 123) {
                e.preventDefault();
                showBlockedMessage();
            }

            // Check for Ctrl/Cmd + Shift + I (keyCode 73 is 'I')
            // These combinations often open the developer console
            if (e.key === 'I' || e.key === 'i') {
                if (e.ctrlKey || e.metaKey) { // Ctrl for Windows/Linux, metaKey for Mac (Cmd)
                    if (e.shiftKey) {
                        e.preventDefault();
                        showBlockedMessage();
                    }
                }
            }

            // Check for Ctrl/Cmd + U (View Source) (keyCode 85 is 'U')
            if (e.key === 'U' || e.key === 'u') {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    showBlockedMessage();
                }
            }
        });

        // Note on Security: These methods are easily bypassed.
        // A user can simply disable JavaScript in their browser settings
        // or use the browser's main menu to access developer tools.

        // Define Data First (Safe)
		
        const classes = {
            warrior: {
                title: 'WARRIOR',
                subtitle: 'The best melee fighter who possesses powerful fencing skills',
                desc: 'Successors of the earliest Rules of the Force. Training strictly focuses on physical strength, therefore preferring strong and reliable armors such as Armor Suit Set from the Midreth Continent.',
                stats: { str: 90, int: 20, dex: 40 },
                color: 'red',
                image: 'images/class/1_class.png',
				video: 'videos/class/WA.mp4' // ADD THIS
            },	
            blader: {
                title: 'BLADER',
                subtitle: 'The deadly blade dancer, the fastest dual sword user',
                desc: 'Successors of the intermediate Rules of the Force originated from ancient eastern martial art focusing on speed. They either use one single-edged sword or two double-edged swords. Skills require them to move quickly and nimbly, thus they prefer light Martial Suit Set from the Huan Continent at South.',
                stats: { str: 60, int: 30, dex: 90 },
                color: 'cyan',
                image: 'images/class/2_class.png',
				video: 'videos/class/BL.mp4' // ADD THIS
            },
            wizard: {
                title: 'WIZARD',
                subtitle: 'Ultimate Destroyer, the Ruler of the Force',
                desc: 'Third successors inherited from the Sage Tower, who manifest the force in its purest form. They use weapon called "Force Amplifier - Orb", worn on both wrists. For armors, they prefer light "Martial Suit Set".',
                stats: { str: 10, int: 95, dex: 50 },
                color: 'purple',
                image: 'images/class/3_class.png',
				video: 'videos/class/WI.mp4' // ADD THIS
            },
            'force-archer': {
                title: 'FORCE ARCHER',
                subtitle: 'The sniper that fires deadly force shots that cut through the wind',
                desc: 'Originated from Wizard faction, Wizard developed various ways of manifesting the force to attack from a distance. They mainly use Crystals as their weapons, however they also use Astral Bows as the carriers of the force to control the force more efficiently. They prefer Battle Suit Set from the Pastur Continent.',
                stats: { str: 30, int: 80, dex: 70 },
                color: 'green',
                image: 'images/class/4_class.png',
				video: 'videos/class/FA.mp4' // ADD THIS
            },
            'force-shielder': {
                title: 'FORCE SHIELDER',
                subtitle: 'The faithful warrior that uses the force to shield others',
                desc: 'Force Shielders hold Crystals to manifest an Astral Shield and use one-handed sword to attack. Since they can equip the Astral Shield they possess the highest defense among all the battle styles. Specialized in defending, Force Shielders prefer strong and reliable "Armor Suit Set".',
                stats: { str: 70, int: 60, dex: 50 },
                color: 'blue',
                image: 'images/class/5_class.png',
				video: 'videos/class/FS.mp4' // ADD THIS
            },
            'force-blader': {
                title: 'FORCE BLADER',
                subtitle: 'Swordsman whose blade flares with the force',
                desc: 'Force Bladers are the final successors of the Rules of the Force. They use orb and one-handed sword to cast aggressive forms of the force and to physically attack at the same time. They value physical balance and coordination, thus prefer "Battle Suit Set"',
                stats: { str: 60, int: 60, dex: 80 },
                color: 'indigo',
                image: 'images/class/6_class.png',
				video: 'videos/class/FB.mp4' // ADD THIS
            },
			 // NEW CLASSES
            gladiator: {
                title: 'GLADIATOR',
                subtitle: 'Strength gained by uprising rage',
                desc: 'Gladiator can control the anger towards their opponent, converting into a pure form of rage to create a mighty force of strength. Specialized in hard trained physical abilities, they prefer "Armor Suit Set" to pull their strengths to the limits.',
                stats: { str: 95, int: 10, dex: 60 },
                color: 'orange',
                image: 'images/class/7_class.png',
				video: 'videos/class/GL.mp4' // ADD THIS
            },
            'force-gunner': {
                title: 'FORCE GUNNER',
                subtitle: 'A genius mechanic and Force user',
                desc: 'Secret agents talented in using force and machines. They can make accurate shots blindfolded by maximizing the power of Astral Weapon and train to improve INT/DEX. Force Gunners prefers `Battle Suit Set` for more balance.',
                stats: { str: 20, int: 80, dex: 85 },
                color: 'teal',
                image: 'images/class/8_class.png',
				video: 'videos/class/FG.mp4' // ADD THIS
            },
            'dark-mage': {
                title: 'DARK MAGE',
                subtitle: 'Handler of twisted force',
                desc: 'Dark Mage combinds Soul and Force to create a new twisted force.\ They can neutralize enemies through power of souls. \ Souls are handled with orbs on both hands, and prefers light armors "Martial Suits" Set.',
                stats: { str: 10, int: 95, dex: 40 },
                color: 'fuchsia',
                image: 'images/class/9_class.png',
				video: 'videos/class/DM.mp4' // ADD THIS
            }
			
       
        };
		
		

        // Class Selection Logic
        function selectClass(className) {
            const data = classes[className];
            if (!data) return;

            // Update Text
            const titleEl = document.getElementById('class-title');
            if (titleEl) titleEl.innerText = data.title;

			const subtitle = document.getElementById('class-subtitle');
			if (subtitle) {
				// Change this line:
				subtitle.innerHTML = data.subtitle; 
				
				// The rest of your code remains the same
				subtitle.className = `text-${data.color}-400 text-lg md:text-xl font-medium mb-6 uppercase tracking-widest flex items-center gap-2`;
			}
            
            const descEl = document.getElementById('class-desc');
            if (descEl) descEl.innerText = data.desc;
			
			// --- VIDEO UPDATE LOGIC ---
    const videoEl = document.getElementById('class-video');
    const sourceEl = document.getElementById('video-source');
    
    if (videoEl && sourceEl && data.video) {
        sourceEl.src = data.video;
        videoEl.load(); 
        videoEl.play().catch(e => console.log("Autoplay blocked or video missing"));
    }
			

            // Update Background Image
            const displayEl = document.getElementById('class-display');
            if (displayEl) displayEl.style.backgroundImage = `url('${data.image}')`;

            // Update Stats
            updateStat('str', data.stats.str, data.color);
            updateStat('int', data.stats.int, data.color);
            updateStat('dex', data.stats.dex, data.color);

            // Update Buttons State
            document.querySelectorAll('.class-btn').forEach(btn => {
                btn.classList.remove('border-l-4', `border-${data.color}-500`, 'bg-gray-800');
                btn.classList.add('border-transparent', 'bg-gray-900/80');
                
                // Reset active styling
                const title = btn.querySelector('h4');
                const desc = btn.querySelector('p');
                if (title) {
                    title.classList.remove(`text-${data.color}-400`);
                    title.classList.add('text-white');
                }
            });

            // Highlight Active Button
            const activeBtn = document.querySelector(`button[data-class="${className}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'bg-gray-900/80');
                
                // Map color names to tailwind colors roughly for the active state
                const colorMap = {
                    red: 'border-red-500',
                    cyan: 'border-cyan-500',
                    purple: 'border-purple-500',
                    green: 'border-green-500',
                    blue: 'border-blue-500',
                    indigo: 'border-indigo-500',
                    orange: 'border-orange-500',
                    teal: 'border-teal-500',
                    fuchsia: 'border-fuchsia-500'
                };
                activeBtn.classList.add('border-l-4', colorMap[data.color], 'bg-gray-800');
                
                // Color active text
                const activeTitle = activeBtn.querySelector('h4');
                if (activeTitle) {
                    activeTitle.classList.remove('text-white');
                    activeTitle.classList.add(`text-${data.color}-400`);
                }
            }

            // Safe init for icons if library exists
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function updateStat(stat, value, color) {
            const bar = document.getElementById(`stat-${stat}`);
            const text = document.getElementById(`stat-${stat}-val`);
            
            if (!bar || !text) return;

            bar.style.width = '0%'; // Reset for animation
            
            // Map colors to tailwind classes
            bar.className = `h-full stat-fill relative shadow-[0_0_10px_rgba(255,255,255,0.3)] bg-${color}-600`;
            bar.innerHTML = '<div class="absolute right-0 top-0 bottom-0 w-1 bg-white/50"></div>';
            
            text.className = `text-${color}-400`;
            
            // Trigger reflow
            void bar.offsetWidth; 
            
            bar.style.width = `${value}%`;
            text.innerText = `${value}%`;
        }

        // Modal Logic
        function openModal(id) {
            const modal = document.getElementById(`${id}-modal`);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(`${id}-modal`);
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
        }

        // Mobile Menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            if (menu) menu.classList.toggle('hidden');
        }

        // Clock
        setInterval(() => {
            const now = new Date();
            const timeEl = document.getElementById('server-time');
            if (timeEl) timeEl.innerText = now.toLocaleTimeString('en-US', { hour12: false });
        }, 1000);

        // Particle System (Simple)
        function createParticles() {
            const container = document.getElementById('particles');
            if (!container) return;

            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const p = document.createElement('div');
                p.classList.add('particle');
                p.style.left = `${Math.random() * 100}%`;
                p.style.animationDelay = `${Math.random() * 5}s`;
                p.style.animationDuration = `${5 + Math.random() * 10}s`;
                container.appendChild(p);
            }
        }

        // Safe Initialization
        window.addEventListener('load', () => {
            // Check if lucide library is loaded before using
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            } else {
                console.warn('Lucide icons library not loaded yet.');
            }

            createParticles();
            selectClass('warrior'); // Default class
        });
		//Guild Ranking HOME
		tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'cabal-panel': '#151b23', // Very dark, near black base
                        'cabal-neon': '#00FFFF', // Bright Cyan/Aqua neon color
                    },
                    fontFamily: {
                        'mono': ['Space Mono', 'monospace'], // Use a sci-fi mono font
                        'title': ['Roboto', 'sans-serif'],
                    }
                },
            },
        }
		//Guild Ranking HOME
		$(document).ready(function () {
  const savedLogin = localStorage.getItem('saved_login');
  const savedSenha = localStorage.getItem('saved_senha');
  const lembrar = localStorage.getItem('lembrar');

  if (lembrar === 'true') {
    $('#login').val(savedLogin);
    $('#senha').val(savedSenha);
    $('#lembrar').prop('checked', true);
  }

  $('#formLogin').on('submit', function(e) {
    e.preventDefault();

    const login = $('#login').val();
    const senha = $('#senha').val();
    const lembrar = $('#lembrar').is(':checked');

    if (lembrar) {
      localStorage.setItem('saved_login', login);
      localStorage.setItem('saved_senha', senha);
      localStorage.setItem('lembrar', 'true');
    } else {
      localStorage.removeItem('saved_login');
      localStorage.removeItem('saved_senha');
      localStorage.removeItem('lembrar');
    }

    $.post('verifica_login.php', $(this).serialize())
      .done(res => {
        try {
          const json = typeof res === 'string' ? JSON.parse(res) : res;
          Swal.fire({
            icon: json.status === 'sucesso' ? 'success' : 'error',
            text: json.mensagem
          });
          if (json.status === 'sucesso') {
            setTimeout(() => window.location.href = '/template/request/user-painel.php', 1500);
          }
        } catch (err) {
          console.error('Erro ao interpretar resposta:', res);
          Swal.fire('Erro', 'Resposta inesperada do servidor.', 'error');
        }
      })
      .fail(() => {
        Swal.fire('Erro', 'Erro de conexão com o servidor.', 'error');
      });
  });

  $('#esqueceuSenhaLink').on('click', function(e) {
    e.preventDefault();

    $('#conteudoRecuperarSenha').html('<div class="text-center"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Carregando...</span></div></div>');
    const modal = new bootstrap.Modal(document.getElementById('recuperarSenhaModal'));
    modal.show();

    $.get('template/request/recover_password.php', function(data) {
      $('#conteudoRecuperarSenha').html(data);
    }).fail(() => {
      $('#conteudoRecuperarSenha').html('<p class="text-danger">Erro ao carregar o formulário de recuperação.</p>');
    });
  });
});
 document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
		//NATION WAR ACTIVE AND IN-ACTIVE
		 // Initialize Lucide icons
        lucide.createIcons();

        // =================================================================
        // WAR STATUS LOGIC (Time-based)
        // =================================================================

        function updateWarStatus() {
            const now = new Date();
            const hours = now.getHours(); // 0-23
            const minutes = now.getMinutes(); // 0-59

            // Select the HTML elements to update
            const statusText = document.getElementById('war-status-text');
            const pingIndicator = document.getElementById('war-ping-indicator');

            // --- Define War Active Time Windows (24-hour clock) ---
            // Rule: 3:00 to 3:59 (hours === 3)
            const isActiveTimeWindow = (hours === 3);
            
            if (isActiveTimeWindow) {
                // --- War IS Active ---
                if (statusText && pingIndicator) {
                    // Update text and color
                    statusText.textContent = 'War Active';
                    statusText.classList.remove('text-gray-400', 'text-green-400');
                    statusText.classList.add('text-green-400');

                    // Set ping indicator to red/active
                    pingIndicator.innerHTML = `
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    `;
                }
            } else {
                // --- War IS NOT Active ---
                if (statusText && pingIndicator) {
                    // Update text and color
                    statusText.textContent = 'War In-Active';
                    statusText.classList.remove('text-red-400');
                    statusText.classList.add('text-gray-400');

                    // Set ping indicator to gray/inactive (no ping animation)
                    pingIndicator.innerHTML = `
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-gray-500"></span>
                    `;
                }
            }
        }
		// Run updates when the script loads
        document.addEventListener('DOMContentLoaded', () => {
            updateWarStatus();
            updateWarData();
            
            // Update the status and data every second
            setInterval(updateWarStatus, 1000); 
            // Update data periodically (e.g., every 5 seconds) if it were real-time
            // setInterval(updateWarData, 5000); 
        });
		