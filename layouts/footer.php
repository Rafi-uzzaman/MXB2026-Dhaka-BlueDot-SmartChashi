    </div><!-- End container -->
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo __('about_smart_chashi'); ?></h4>
                    <p><?php echo __('footer_description'); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo __('quick_links'); ?></h4>
                    <ul>
                        <li><a href="<?php echo $base_url; ?>"><?php echo __('home'); ?></a></li>
                        <li><a href="<?php echo $base_url; ?>chat"><?php echo __('chat'); ?></a></li>
                        <li><a href="<?php echo $base_url; ?>weather"><?php echo __('weather'); ?></a></li>
                        <li><a href="<?php echo $base_url; ?>marketplace"><?php echo __('marketplace'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo __('contact'); ?></h4>
                    <p><?php echo __('email'); ?>: info@cashibhai.com</p>
                    <p><?php echo __('phone'); ?>: +880 1234 567890</p>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo __('follow_us'); ?></h4>
                    <div class="social-links">
                        <a href="#" class="social">Facebook</a>
                        <a href="#" class="social">Twitter</a>
                        <a href="#" class="social">LinkedIn</a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 <?php echo __('smart_chashi'); ?>. <?php echo __('all_rights_reserved'); ?>. <?php echo __('version'); ?> <?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </footer>

    </main>
    </div><!-- End container -->
    
    <?php
    if(isLoggedIn()){
        include __DIR__ . '/../layouts/agent.php';
    } ?>
    
    <script src="<?php echo $base_url; ?>public/js/app.js"></script>
    
    <script>
        // Set base URL for JavaScript
        const baseUrl = '<?php echo $base_url; ?>';
        
        // Enhanced Mobile Navigation & Language Switcher
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile Menu Toggle
            const menuToggle = document.getElementById('menuToggle');
            const navbarNav = document.getElementById('navbarNav');
            const navbarOverlay = document.getElementById('navbarOverlay');
            
            if (menuToggle && navbarNav) {
                menuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    this.classList.toggle('active');
                    navbarNav.classList.toggle('show');
                    navbarOverlay.classList.toggle('show');
                    document.body.style.overflow = navbarNav.classList.contains('show') ? 'hidden' : '';
                });

                // Close menu when clicking overlay
                navbarOverlay.addEventListener('click', function() {
                    menuToggle.classList.remove('active');
                    navbarNav.classList.remove('show');
                    navbarOverlay.classList.remove('show');
                    document.body.style.overflow = '';
                });

                // Close menu when clicking on a link
                const menuLinks = navbarNav.querySelectorAll('.nav-link');
                menuLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        menuToggle.classList.remove('active');
                        navbarNav.classList.remove('show');
                        navbarOverlay.classList.remove('show');
                        document.body.style.overflow = '';
                    });
                });

                // Close menu on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && navbarNav.classList.contains('show')) {
                        menuToggle.classList.remove('active');
                        navbarNav.classList.remove('show');
                        navbarOverlay.classList.remove('show');
                        document.body.style.overflow = '';
                    }
                });
            }

            // User Dropdown Menu (for logged in users)
            const userMenuToggle = document.getElementById('userMenuToggle');
            const userDropdown = document.getElementById('userDropdown');
            const languageSelector = document.querySelector('.language-selector');
            const langOptionsInDropdown = document.getElementById('langOptionsInDropdown');

            if (userMenuToggle && userDropdown) {
                // Remove any existing event listeners by cloning the element
                const newToggle = userMenuToggle.cloneNode(true);
                userMenuToggle.parentNode.replaceChild(newToggle, userMenuToggle);
                
                newToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isShowing = userDropdown.classList.contains('show');
                    
                    // Close all other dropdowns first
                    document.querySelectorAll('.user-dropdown.show').forEach(d => {
                        if (d !== userDropdown) d.classList.remove('show');
                    });
                    
                    userDropdown.classList.toggle('show');
                    console.log('User dropdown toggled. Show:', userDropdown.classList.contains('show'));
                    
                    // Reset language submenu when closing
                    if (isShowing && languageSelector) {
                        languageSelector.classList.remove('active');
                        if (langOptionsInDropdown) {
                            langOptionsInDropdown.classList.remove('show');
                        }
                    }
                });

                // Handle clicks on dropdown links (like Profile)
                // Use event delegation on the dropdown itself
                userDropdown.addEventListener('click', function(e) {
                    // Check if clicked element is a link (but not language selector)
                    const link = e.target.closest('a.user-dropdown-item');
                    if (link && !link.classList.contains('language-selector')) {
                        // Allow the link to navigate naturally
                        // The browser will handle the navigation
                        console.log('Profile link clicked, navigating to:', link.href);
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target) && !newToggle.contains(e.target)) {
                        userDropdown.classList.remove('show');
                        if (languageSelector) {
                            languageSelector.classList.remove('active');
                            if (langOptionsInDropdown) {
                                langOptionsInDropdown.classList.remove('show');
                            }
                        }
                    }
                });

                // Language selector inside user dropdown
                if (languageSelector && langOptionsInDropdown) {
                    languageSelector.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.classList.toggle('active');
                        langOptionsInDropdown.classList.toggle('show');
                    });

                    // Close language submenu when clicking outside
                    document.addEventListener('click', function(e) {
                        if (languageSelector && !languageSelector.contains(e.target) && !langOptionsInDropdown.contains(e.target)) {
                            languageSelector.classList.remove('active');
                            langOptionsInDropdown.classList.remove('show');
                        }
                    });

                    const langOptionBtns = langOptionsInDropdown.querySelectorAll('.lang-option-btn');
                    langOptionBtns.forEach(option => {
                        option.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const lang = this.dataset.lang;
                            console.log('Language button clicked:', lang);
                            
                            // Show loading indicator
                            this.innerHTML = '<span class="material-icons">sync</span> Switching...';
                            
                            fetch(baseUrl + 'api/set-language.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'language=' + lang
                            })
                            .then(response => {
                                console.log('Language response:', response);
                                return response.json();
                            })
                            .then(data => {
                                console.log('Language data:', data);
                                if (data.success) {
                                    location.reload();
                                } else {
                                    console.error('Language switch failed:', data.message);
                                    alert('Failed to switch language. Please try again.');
                                    location.reload();
                                }
                            })
                            .catch(error => {
                                console.error('Error switching language:', error);
                                alert('Error switching language. Please try again.');
                                location.reload();
                            });
                        });
                    });
                }
            }

            // Language Switcher (for logged out users)
            const langToggle = document.getElementById('langToggle');
            const langMenu = document.getElementById('langMenu');
            const langOptions = document.querySelectorAll('.lang-option');

            if (langToggle && langMenu) {
                langToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    langMenu.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!langMenu.contains(e.target) && !langToggle.contains(e.target)) {
                        langMenu.classList.remove('show');
                    }
                });

                langOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const lang = this.dataset.lang;
                        console.log('Language option clicked (logged out):', lang);
                        
                        // Show loading
                        this.innerHTML = '<span class="material-icons">sync</span> Switching...';
                        
                        fetch(baseUrl + 'api/set-language.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'language=' + lang
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Language switch response:', data);
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Failed to switch language. Please try again.');
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error switching language:', error);
                            alert('Error switching language. Please try again.');
                            location.reload();
                        });
                    });
                });
            }

            // Password Toggle Functionality (supports multiple password fields)
            const passwordToggles = document.querySelectorAll('.password-toggle');
            
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.dataset.target || 'password';
                    const passwordInput = document.getElementById(targetId);
                    
                    if (passwordInput) {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        
                        // Toggle Material Icon
                        const eyeIcon = this.querySelector('.eye-icon');
                        if (eyeIcon) {
                            eyeIcon.textContent = type === 'password' ? 'visibility' : 'visibility_off';
                        }
                    }
                });
            });

            // Touch device optimization
            if ('ontouchstart' in window) {
                document.body.classList.add('touch-device');
            }

            // Handle orientation changes
            window.addEventListener('orientationchange', function() {
                // Close mobile menu on orientation change
                if (menuToggle && navbarNav) {
                    menuToggle.classList.remove('active');
                    navbarNav.classList.remove('show');
                }
            });

            // Floating Chat Button & Modal with macOS-like animation
            const chatFloatingBtn = document.getElementById('chatFloatingBtn');
            const chatModal = document.getElementById('chatModal');
            const chatModalClose = document.getElementById('chatModalClose');

            if (chatFloatingBtn && chatModal) {
                // Open chat modal with animation
                chatFloatingBtn.addEventListener('click', function() {
                    chatModal.classList.add('opening');
                    chatModal.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    
                    // Remove opening class after animation completes
                    setTimeout(() => {
                        chatModal.classList.remove('opening');
                    }, 400);
                });

                // Close chat modal with animation
                const closeModal = function() {
                    chatModal.classList.add('closing');
                    chatModal.classList.remove('show');
                    
                    // Wait for animation to complete before cleaning up
                    setTimeout(() => {
                        chatModal.classList.remove('closing');
                        document.body.style.overflow = '';
                    }, 400);
                };

                if (chatModalClose) {
                    chatModalClose.addEventListener('click', closeModal);
                }

                // Close on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && chatModal.classList.contains('show')) {
                        closeModal();
                    }
                });
            }
        });
    </script>
</body>
</html>
