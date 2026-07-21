 function openModal(id) {
            const modal = document.getElementById(id + '-modal');
            modal.classList.remove('hidden');
            // Prevent scrolling on body when modal is open
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            const modal = document.getElementById(id + '-modal');
            modal.classList.add('hidden');
            // Re-enable scrolling
            document.body.style.overflow = 'auto';
        }

        // Close on Escape key press
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('[id$="-modal"]');
                modals.forEach(modal => {
                    if (!modal.classList.contains('hidden')) {
                        const id = modal.id.replace('-modal', '');
                        closeModal(id);
                    }
                });
            }
        });

        // Prevent the form submission from refreshing the page for this demo
        document.getElementById('modalSignupForm').addEventListener('submit', (e) => {
            e.preventDefault();
            alert('Account creation simulated!');
            closeModal('register');
        });
		