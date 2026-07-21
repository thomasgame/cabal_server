 tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { 'capella-blue': '#7F00FF', 'procyon-blue': '#0000FF' }
                }
            }
        };

        lucide.createIcons();

        const audio = document.getElementById('bg-audio');
        const toggleBtn = document.getElementById('music-toggle');
        const icon = document.getElementById('music-icon');
        const statusText = document.getElementById('music-status');
        const volumeSlider = document.getElementById('volume-slider');

        // Set initial volume
        audio.volume = volumeSlider.value;

        function updateUI(playing) {
            if (playing && !audio.muted) {
                if (audio.volume === 0) {
                    icon.setAttribute('data-lucide', 'volume-x');
                } else if (audio.volume < 0.5) {
                    icon.setAttribute('data-lucide', 'volume-1');
                } else {
                    icon.setAttribute('data-lucide', 'volume-2');
                }
                statusText.innerText = "Music Playing";
                toggleBtn.classList.add('music-playing');
                toggleBtn.classList.add('bg-indigo-500/20');
            } else {
                icon.setAttribute('data-lucide', 'volume-x');
                statusText.innerText = "Music Muted";
                toggleBtn.classList.remove('music-playing');
                toggleBtn.classList.remove('bg-indigo-500/20');
            }
            lucide.createIcons();
        }

        // Handle Volume Adjustment
        volumeSlider.addEventListener('input', (e) => {
            const val = e.target.value;
            audio.volume = val;
            if (val > 0 && audio.muted) {
                audio.muted = false;
            }
            updateUI(!audio.paused);
        });

        // The Manual Toggle
        toggleBtn.addEventListener('click', () => {
            if (audio.muted) {
                audio.muted = false;
                if (audio.volume === 0) {
                    audio.volume = 0.5;
                    volumeSlider.value = 0.5;
                }
                audio.play();
                updateUI(true);
            } else {
                if (audio.paused) {
                    audio.play();
                    updateUI(true);
                } else {
                    audio.pause();
                    updateUI(false);
                }
            }
        });

        // AUTO-PLAY STRATEGY
        window.addEventListener('load', () => {
            audio.play().then(() => {
                console.log("Autoplay started (muted)");
                updateUI(false); 
            }).catch(err => console.log("Autoplay failed completely"));
        });

        const unlockAudio = () => {
            if (audio.muted) {
                audio.muted = false;
                if (audio.paused) audio.play();
                updateUI(true);
            }
            window.removeEventListener('click', unlockAudio);
            window.removeEventListener('touchstart', unlockAudio);
            window.removeEventListener('keydown', unlockAudio);
        };

        window.addEventListener('click', unlockAudio);
        window.addEventListener('touchstart', unlockAudio);
        window.addEventListener('keydown', unlockAudio);
