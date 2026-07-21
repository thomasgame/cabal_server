  document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.rankTab');
            const contents = document.querySelectorAll('.rankTabContent');

            const activateTab = (targetId) => {
                // Deactivate all
                tabs.forEach(tab => tab.classList.remove('active-tab'));
                contents.forEach(content => content.classList.add('hidden'));

                // Activate selected tab and content
                const activeTab = document.querySelector(`.rankTab[data-target="${targetId}"]`);
                const activeContent = document.getElementById(targetId);

                if (activeTab) activeTab.classList.add('active-tab');
                if (activeContent) activeContent.classList.remove('hidden');

                // Store active tab in localStorage
                localStorage.setItem('activeRankingTab', targetId);
            };

            // Event listeners
            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    const target = e.currentTarget.getAttribute('data-target');
                    activateTab(target);
                });
            });

            // Check for stored active tab or default to 'charRank'
            const storedTab = localStorage.getItem('activeRankingTab');
            if (storedTab && document.getElementById(storedTab)) {
                activateTab(storedTab);
            } else {
                activateTab('charRank');
            }
        });