<?php include 'properties/sweetalert.php'; ?>

<!-- Enhanced Footer -->
<link rel="stylesheet" href="css/footer-enhanced.css?v=<?php echo filemtime('css/footer-enhanced.css'); ?>">

<footer class="footer-enhanced">
    <div class="footer-content">
        <!-- Footer Content - Single Layout -->
        <div class="footer-main">
            <!-- Combined Navigation & Social Section -->
            <div class="footer-section">
                <h3>Quick Navigation</h3>
                <div class="footer-links-container">
                    <a href="#top" class="footer-link">
                        <i class="fa fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="#about-section" class="footer-link">
                        <i class="fa fa-info-circle"></i>
                        <span>About Us</span>
                    </a>
                    <a href="#offer" class="footer-link">
                        <i class="fa fa-star"></i>
                        <span>Services</span>
                    </a>
                    <a href="#gall" class="footer-link">
                        <i class="fa fa-picture-o"></i>
                        <span>Gallery</span>
                    </a>
                    <a href="#feed" class="footer-link">
                        <i class="fa fa-comments"></i>
                        <span>Feedback</span>
                    </a>
                    <a href="#us" class="footer-link">
                        <i class="fa fa-phone"></i>
                        <span>Contact</span>
                    </a>
                </div>
                
                <h3 style="margin-top: 2.5rem;">Connect & Visit</h3>
                <div class="footer-social">
                    <a href="https://facebook.com" target="_blank" class="social-link">
                        <i class="fa fa-facebook"></i>
                        <span>Facebook</span>
                    </a>
                    <a href="https://instagram.com" target="_blank" class="social-link">
                        <i class="fa fa-instagram"></i>
                        <span>Instagram</span>
                    </a>
                    <a href="#" class="social-link" data-bs-toggle="modal" data-bs-target="#locationModal">
                        <i class="fa fa-map-marker"></i>
                        <span>View Map</span>
                    </a>
                    <a href="mailto:info@example.com" class="social-link">
                        <i class="fa fa-envelope"></i>
                        <span>Email Us</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="footer-bottom">
            <p>&copy; <span id="currentYear">2025</span> Shelton Beach Haven. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- Enhanced Location Modal -->
<div class="modal fade modal-enhanced" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content location-modal-themed">
            <div class="modal-header location-header">
                <h5 class="modal-title" id="locationModalLabel">Our Location</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3926.084!2d122.9069255!3d10.6020865!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33aecf7a15acf601%3A0x7226652524a1c46c!2sSHELTON%20BEACH%20HAVEN!5e0!3m2!1sen!2sph!4v1234567890"
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">Punta Taytay, Bacolod City</div>
                <div class="d-flex gap-2">
                    <a class="btn btn-theme-primary btn-sm" href="https://maps.google.com/?q=SHELTON+BEACH+HAVEN" target="_blank" rel="noopener">Open in Google Maps</a>
                    <a class="btn btn-theme-accent btn-sm" href="https://www.google.com/maps/dir/?api=1&destination=SHELTON+BEACH+HAVEN" target="_blank" rel="noopener">Get Directions</a>
                </div>
            </div>
        </div>
    </div>
    </div>

<style>
.location-modal-themed{border:1px solid rgba(122,180,161,.25); border-radius:16px; overflow:hidden; box-shadow:0 15px 40px rgba(0,0,0,.18)}
.location-modal-themed .modal-footer{border-top:1px solid rgba(122,180,161,.25)}
.location-header{background:linear-gradient(135deg, #7ab4a1, #5a9a8a); color:#fff}
.location-header .modal-title{color:#fff; font-weight:600}
.location-header .btn-close{filter:invert(1) grayscale(1) brightness(200%)}
</style>

<!-- Enhanced Footer JavaScript -->
<script>
    // Intersection Observer for footer animations
    document.addEventListener('DOMContentLoaded', function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Observe footer sections
        document.querySelectorAll('.footer-section').forEach(section => {
            observer.observe(section);
        });

        // Dynamic copyright year
        const yearSpan = document.getElementById('currentYear');
        if (yearSpan) {
            const currentYear = new Date().getFullYear();
            yearSpan.textContent = currentYear;
        }
    });
    
    // Add click effects to contact items
    document.querySelectorAll('.contact-item').forEach(item => {
        item.addEventListener('click', function() {
            this.style.transform = 'translateY(-1px) scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'translateY(-2px)';
            }, 150);
        });
    });
    
    // Enhanced modal functionality
    document.addEventListener('DOMContentLoaded', () => {
        const locationModal = document.getElementById('locationModal');
        if (locationModal) {
            locationModal.addEventListener('shown.bs.modal', function () {
                // Refresh iframe when modal is shown
                const iframe = this.querySelector('iframe');
                if (iframe) {
                    iframe.src = iframe.src;
                }
            });
        }
    });
    
    // Smooth scroll for footer links
    document.querySelectorAll('.footer-link[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                const headerHeight = document.querySelector('.navbar-enhanced')?.offsetHeight || 80;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add loading animation for external links
    document.querySelectorAll('.social-link[target="_blank"]').forEach(link => {
        link.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
</script>
