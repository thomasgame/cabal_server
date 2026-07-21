// =================================================================
    // NEWS FEED & MODAL LOGIC
    // =================================================================

    const newsData = [
        {
            id: 1,
            title: "Double EXP Weekend: Rise of Power",
            type: "Event",
            typeColor: "red",
            date: "Nov 26, 2025",
            summary: "Prepare your weapons, warriors! This weekend we are boosting all experience gains by 200%. It's the perfect time to level up your alts or reach the cap on your main.",
            fullContent: `
                <p class="mb-4"><strong>The time has come to seize destiny!</strong> This highly anticipated Double EXP Weekend event is officially live, starting Friday at 00:00 PST and concluding Monday at 23:59 PST.</p>
                <h4 class="text-xl font-semibold mt-6 mb-3 text-cyan-400 border-b border-gray-700 pb-1">Boost Details & Schedule:</h4>
                <ul class="list-disc list-inside space-y-2 ml-4 text-gray-300">
                    <li><strong>Experience:</strong> +200% EXP from all monster kills.</li>
                    <li><strong>Skill EXP:</strong> +100% Skill EXP gain.</li>
                    <li><strong>Drops:</strong> +50% Drop Rate boost in all dungeons (excluding raids).</li>
                    <li><strong>Duration:</strong> November 26, 2025 (00:00 PST) - November 29, 2025 (23:59 PST).</li>
                </ul>
                <p class="mt-6 text-lg text-white">Don't miss out on this opportunity to power-level your character and prepare for the upcoming raid content. May the odds be ever in your favor, champion!</p>
                <div class="mt-8 p-4 bg-yellow-900/30 border border-yellow-700/50 rounded-lg text-sm text-yellow-300">
                    <i data-lucide="alert-triangle" class="w-5 h-5 inline mr-2"></i> Note: Premium service bonus stacks additively with this event.
                </div>
            `
        },
        {
            id: 2,
            title: "Update v4.5: The Dark Mage Arrives",
            type: "Patch",
            typeColor: "blue",
            date: "Nov 24, 2025",
            summary: "A new force has entered the battlefield. The Dark Mage class is now available for early access. Check out the full patch notes for skill balances and new dungeon drops.",
            fullContent: `
                <p class="mb-4">The veil between worlds has thinned, and the Dark Mage is now a playable class! This class focuses on high-risk, high-reward shadow magic, utilizing dark energy to decimate enemies from afar.</p>
                <h4 class="text-xl font-semibold mt-6 mb-3 text-cyan-400 border-b border-gray-700 pb-1">Key Patch Notes (v4.5):</h4>
                <h5 class="font-bold text-white mt-4 mb-2">I. New Class: Dark Mage</h5>
                <ul class="list-disc list-inside space-y-2 ml-6 text-gray-300">
                    <li>Available at character creation.</li>
                    <li>Specializes in Chaos Magic and DOT (Damage over Time) effects.</li>
                    <li>Resource: Dark Nexus energy (recharges upon taking damage).</li>
                </ul>
                <h5 class="font-bold text-white mt-4 mb-2">II. Skill Balance Changes</h5>
                <ul class="list-disc list-inside space-y-2 ml-6 text-gray-300">
                    <li><strong>Warrior:</strong> 'Rage Burst' cooldown increased from 15s to 20s.</li>
                    <li><strong>Blader:</strong> Minor adjustment to ‘Flash Step’ damage coefficient (+5%).</li>
                </ul>
                <h5 class="font-bold text-white mt-4 mb-2">III. New Content</h5>
                <ul class="list-disc list-inside space-y-2 ml-6 text-gray-300">
                    <li><strong>New Dungeon:</strong> Shadowfell Crypt (Level 170+ required). Drops new Tier-10 materials.</li>
                    <li><strong>Bug Fixes:</strong> Addressed a critical issue where players could fall through the floor in Frozen Tower.</li>
                </ul>
                <p class="mt-6 text-lg text-white">Log in now to experience the terror of the Dark Mage and conquer the new Crypt!</p>
            `
        },
        {
            id: 3,
            title: "New Astral Bike: Type-Zero",
            type: "Shop",
            typeColor: "amber",
            date: "Nov 20, 2025",
            summary: "Speed through the deserts of Arcane Trace with the new Type-Zero Astral Bike. Available now in the Item Shop with exclusive neon skins.",
            fullContent: `
                <p class="mb-4">Introducing the sleekest, fastest Astral Bike yet: the Type-Zero! Engineered with advanced ancient technology, this bike offers a significant speed boost and a stunning visual design, perfect for traversing the vast landscapes of Nevareth.</p>
                <div class="mt-6 mb-6 p-6 bg-gray-800/50 rounded-xl">
                    <h4 class="text-2xl font-bold text-cyan-300 mb-3">Astral Bike: Type-Zero Specifications</h4>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex justify-between items-center border-b border-gray-700 pb-1">
                            <span class="font-semibold">Max Speed Tier:</span> <span class="text-cyan-400">Tier 5 (Max)</span>
                        </li>
                        <li class="flex justify-between items-center border-b border-gray-700 pb-1">
                            <span class="font-semibold">Acceleration:</span> <span class="text-cyan-400">Improved (15% faster than Type-X)</span>
                        </li>
                        <li class="flex justify-between items-center border-b border-gray-700 pb-1">
                            <span class="font-semibold">Exclusive Skins:</span> <span class="text-cyan-400">Neon Blaze, Shadow Stealth</span>
                        </li>
                        <li class="flex justify-between items-center">
                            <span class="font-semibold">Price (Launch Sale):</span> <span class="text-red-400">2,500 AP</span>
                        </li>
                    </ul>
                </div>
                <p class="text-lg text-white">Upgrade your traversal and dominate the world of Nevareth in style. The promotional price is only valid until December 5th, 2025. Find the Type-Zero in the Item Shop today!</p>
            `
        }
    ];
        
    // --- DOM REFERENCES ---
    const contentContainer = document.getElementById('dynamic-news-content');
    const newsModal = document.getElementById('news-modal');
    const modalContent = document.getElementById('modal-content');


    /**
     * Safely resolves Tailwind utility classes for dynamic colors.
     * @param {string} color - The base color name (red, blue, amber).
     * @returns {{bg: string, text: string}} - Object with Tailwind classes.
     */
    function getColorClasses(color) {
        switch (color) {
            case 'red': return { bg: 'bg-red-500/20', text: 'text-red-400' };
            case 'blue': return { bg: 'bg-blue-500/20', text: 'text-blue-400' };
            case 'amber': return { bg: 'bg-amber-500/20', text: 'text-amber-400' };
            default: return { bg: 'bg-gray-500/20', text: 'text-gray-400' };
        }
    }

    /**
     * Closes the modal pop-up and restores body scrolling.
     */
    window.closeModal = function() {
        newsModal.classList.add('hidden');
        document.body.classList.remove('modal-open');
    }

    /**
     * Renders the main news grid view based on newsData.
     */
    window.renderNewsGrid = function() {
        closeModal();

        const gridHtml = newsData.map(item => {
            const { bg, text } = getColorClasses(item.typeColor);

            return `
                <!-- News Item ${item.id} -->
                <article class="tech-border rounded-lg group p-6 bg-gray-900/40 hover:bg-gray-800/60 transition-colors duration-300 cursor-pointer shadow-xl" onclick="showFullPatch(${item.id})">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="${bg} ${text} text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">${item.type}</span>
                        <span class="text-gray-500 text-xs"><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i> ${item.date}</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3 group-hover:text-cyan-400 transition-colors">${item.title}</h3>
                    <p class="text-gray-400 text-sm mb-4 line-clamp-3">
                        ${item.summary}
                    </p>
                    <a href="#" class="text-sm text-cyan-500 hover:text-cyan-300 font-bold uppercase tracking-wider flex items-center gap-1 transition-transform group-hover:translate-x-1" onclick="event.stopPropagation(); showFullPatch(${item.id}); return false;">
                        Read More <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                </article>
            `;
        }).join('');

        contentContainer.innerHTML = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">${gridHtml}</div>`;
        
        // Re-render Lucide icons after updating the DOM
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    /**
     * Shows the detailed view of a single news item in a modal.
     * @param {number} id - The ID of the news item.
     */
    window.showFullPatch = function(id) {
        const item = newsData.find(d => d.id === id);
        if (!item) {
            console.error("News item not found for ID:", id);
            return;
        }

        const { bg, text } = getColorClasses(item.typeColor);

        // Construct the modal content HTML using the rich formatting provided in fullContent
        const detailHtml = `
            <div class="p-6 sm:p-12 rounded-xl relative">
                <!-- Close Button -->
                <button onclick="closeModal()" aria-label="Close" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors p-2 rounded-full bg-gray-700/50 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 z-20">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start border-b border-gray-700 pb-4 mb-6 pr-10">
                    <div class="mb-4 sm:mb-0">
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">${item.title}</h1>
                        <div class="flex items-center gap-4">
                            <span class="${bg} ${text} font-bold px-3 py-1 rounded-full uppercase text-sm tracking-wider">${item.type}</span>
                            <span class="text-gray-500 text-sm"><i data-lucide="calendar" class="w-4 h-4 inline mr-1 text-gray-500"></i> Published: ${item.date}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="text-gray-400 leading-relaxed space-y-4 full-content-display">
                    ${item.fullContent}
                </div>

                <!-- Footer - Simple close link at the bottom -->
                <div class="mt-10 pt-6 border-t border-gray-800 text-center">
                    <a href="#" class="inline-flex items-center gap-2 text-cyan-400 hover:text-white transition-colors font-bold" onclick="closeModal(); return false;">
                        <i data-lucide="arrow-up-circle" class="w-5 h-5"></i> Back to Grid
                    </a>
                </div>
            </div>
        `;

        // Inject content and show modal
        modalContent.innerHTML = detailHtml;
        newsModal.classList.remove('hidden');
        document.body.classList.add('modal-open'); // Prevent body scrolling

        // Scroll modal content to the top
        newsModal.scrollTo(0, 0);

        // Re-render Lucide icons for the newly injected HTML
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    };

    // Close modal when Escape key is pressed
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !newsModal.classList.contains('hidden')) {
            closeModal();
        }
    });

