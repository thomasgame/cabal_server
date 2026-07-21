
        // Tailwind Configuration
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'primary-purple': '#7F00FF' }
                }
            }
        };

        // Initialize Swiper
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Swiper !== 'undefined') {
                const swiper = new Swiper(".screenshot-slider", {
                    effect: "coverflow",
                    grabCursor: true,
                    centeredSlides: true,
                    slidesPerView: "auto",
                    loop: true,
                    speed: 1000,
                    autoplay: { 
                        delay: 3500, 
                        disableOnInteraction: false 
                    },
                    coverflowEffect: { 
                        rotate: 20, 
                        stretch: 0, 
                        depth: 150, 
                        modifier: 1.5, 
                        slideShadows: false
                    },
                    pagination: { 
                        el: ".swiper-pagination", 
                        clickable: true 
                    }
                });

                // Lightbox Logic
                const overlay = document.getElementById('lightbox-overlay');
                const lightboxContent = document.getElementById('lightbox-content');
                const lightboxImg = document.getElementById('lightbox-img');
                const lightboxTitle = document.getElementById('lightbox-title');
                const lightboxDesc = document.getElementById('lightbox-desc');
                const galleryItems = document.querySelectorAll('.gallery-item');

                galleryItems.forEach(item => {
                    item.addEventListener('click', () => {
                        if (item.classList.contains('swiper-slide-active')) {
                            const title = item.getAttribute('data-title');
                            const desc = item.getAttribute('data-desc');
                            const actualImg = item.querySelector('img').src;

                            lightboxImg.src = actualImg;
                            lightboxTitle.textContent = title;
                            lightboxDesc.textContent = desc;
                            
                            overlay.classList.add('active');
                            setTimeout(() => {
                                lightboxContent.classList.remove('scale-95');
                                lightboxContent.classList.add('scale-100');
                            }, 10);
                            
                            document.body.style.overflow = 'hidden';
                        }
                    });
                });

                const closeLightbox = () => {
                    lightboxContent.classList.remove('scale-100');
                    lightboxContent.classList.add('scale-95');
                    setTimeout(() => {
                        overlay.classList.remove('active');
                    }, 200);
                    document.body.style.overflow = 'auto';
                };

                // Close when clicking the overlay (outside the image content)
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        closeLightbox();
                    }
                });

                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') closeLightbox();
                });
            }
        });
