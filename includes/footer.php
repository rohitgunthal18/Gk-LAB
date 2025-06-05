<?php
/**
 * Universal Footer Include
 * 
 * @author Rohit Gunthal <rohitgunthal1819@gmail.com>
 * @copyright 2023 Rohit Gunthal
 * @license Proprietary - All Rights Reserved
 */
?>
    </main> <!-- End of main content -->
    
    <!-- Footer Section -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <div class="footer-logo">
                        <i class="fas fa-flask"></i>
                        <span>GK Lab</span>
                    </div>
                    <p class="footer-description">
                        GK Lab offers comprehensive medical tests and health checkups in Latur with doorstep sample collection.
                    </p>
                    <div class="footer-contact">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+91 8408088454</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>rohitgunthal1819@gmail.com</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-heading">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="tests.php">Tests</a></li>
                        <li><a href="checkups.php">Health Checkups</a></li>
                        <li><a href="pages/about.html">About Us</a></li>
                        <li><a href="pages/contact.html">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-heading">Legal</h3>
                    <ul class="footer-links">
                        <li><a href="pages/terms.html">Terms & Conditions</a></li>
                        <li><a href="pages/privacy.html">Privacy Policy</a></li>
                        <li><a href="pages/refund.html">Refund Policy</a></li>
                        <li><a href="LICENSE.txt">License</a></li>
                        <li><a href="humans.txt">Credits</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3 class="footer-heading">Subscribe</h3>
                    <p class="newsletter-text">Subscribe to our newsletter for updates and health tips.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your Email" required>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    &copy; <?php echo date('Y'); ?> GK Lab. All Rights Reserved. Designed and Developed by <a href="mailto:rohitgunthal1819@gmail.com">Rohit Gunthal</a>
                </div>
                <div class="footer-badges">
                    <img src="assets/images/secure-payment.png" alt="Secure Payment">
                    <img src="assets/images/iso-certified.png" alt="ISO Certified">
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Include Schema.org structured data -->
    <?php include_once 'includes/schema.php'; ?>
    
    <!-- JS Scripts -->
    <script src="js/main.js"></script>
    <script src="js/cart.js"></script>
    <script>
        // Check login status
        document.addEventListener('DOMContentLoaded', function() {
            fetch('check_login_status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.logged_in) {
                        document.getElementById('account-text').textContent = 'Hi, ' + data.name;
                        
                        // Show logged-in elements
                        document.querySelectorAll('.logged-in-only').forEach(el => {
                            el.style.display = 'block';
                        });
                        
                        // Hide login/register links
                        document.querySelectorAll('.user-dropdown li:not(.logged-in-only)').forEach(el => {
                            el.style.display = 'none';
                        });
                    }
                    
                    // Update cart count
                    const cartCountElement = document.getElementById('cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count || 0;
                    }
                })
                .catch(error => console.error('Error checking login status:', error));
        });
    </script>
</body>
</html><?php // End of file ?> 