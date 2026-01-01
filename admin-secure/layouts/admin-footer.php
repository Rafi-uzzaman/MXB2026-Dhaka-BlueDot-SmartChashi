        </main>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box confirm-modal">
            <div class="modal-icon" id="confirmIcon">
                <span class="material-icons">warning</span>
            </div>
            <h3 class="modal-title" id="confirmTitle">Confirm Action</h3>
            <p class="modal-message" id="confirmMessage">Are you sure you want to proceed?</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="confirmCancel">Cancel</button>
                <button class="btn btn-danger" id="confirmOk">Confirm</button>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner-large"></div>
            <p id="loadingText">Loading...</p>
        </div>
    </div>

    <script>
        // Initialize Mermaid with theme support
        function initMermaid() {
            const theme = document.documentElement.getAttribute('data-theme') || 'light';
            mermaid.initialize({ 
                startOnLoad: true, 
                theme: theme === 'dark' ? 'dark' : 'default',
                themeVariables: theme === 'dark' ? {
                    primaryColor: '#6366f1',
                    primaryTextColor: '#fff',
                    primaryBorderColor: '#818cf8',
                    lineColor: '#6c7086',
                    secondaryColor: '#1e1e2e',
                    tertiaryColor: '#313244'
                } : {
                    primaryColor: '#6366f1',
                    primaryTextColor: '#1e293b',
                    primaryBorderColor: '#818cf8',
                    lineColor: '#cbd5e1',
                    secondaryColor: '#f1f5f9',
                    tertiaryColor: '#e2e8f0'
                }
            });
        }
        initMermaid();

        const BASE_URL = document.getElementById('baseUrl').value;
        const CSRF_TOKEN = document.getElementById('csrfToken').value;

        // Sidebar Toggle
        const sidebar = document.getElementById('adminSidebar');
        const mainContent = document.getElementById('adminMain');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileToggle = document.getElementById('mobileToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        function toggleMobileSidebar() {
            sidebar.classList.toggle('mobile-open');
        }

        sidebarToggle?.addEventListener('click', toggleSidebar);
        mobileToggle?.addEventListener('click', toggleMobileSidebar);

        // Restore sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !mobileToggle.contains(e.target) &&
                sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
            }
        });

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        function setTheme(theme) {
            html.setAttribute('data-theme', theme);
            localStorage.setItem('adminTheme', theme);
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const currentTheme = html.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
                // Reinitialize Mermaid with new theme
                initMermaid();
            });
        }

        // Restore theme
        const savedTheme = localStorage.getItem('adminTheme') || 'light';
        setTheme(savedTheme);

        // Dropdown handling
        function setupDropdown(buttonId, menuId) {
            const button = document.getElementById(buttonId);
            const menu = document.getElementById(menuId);
            
            if (!button || !menu) {
                console.warn('Dropdown setup failed:', buttonId, menuId);
                return;
            }

            // Handle click on button or any child element
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                
                // Close other dropdowns
                document.querySelectorAll('.dropdown-menu.active').forEach(m => {
                    if (m !== menu) m.classList.remove('active');
                });
                
                // Toggle this dropdown
                menu.classList.toggle('active');
            });
        }

        setupDropdown('notificationBtn', 'notificationMenu');
        setupDropdown('userMenuBtn', 'userMenu');

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu.active').forEach(m => {
                m.classList.remove('active');
            });
        });

        // Prevent dropdown close when clicking inside menu (but allow link clicks)
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.addEventListener('click', (e) => {
                // Allow links to work normally
                if (e.target.tagName === 'A' || e.target.closest('a')) {
                    return; // Let the link navigate
                }
                e.stopPropagation();
            });
        });

        // Notifications
        async function loadNotifications() {
            const list = document.getElementById('notificationList');
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_notifications');
                formData.append('csrf_token', CSRF_TOKEN);
                formData.append('limit', '5');

                const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    list.innerHTML = data.data.map(n => `
                        <div class="notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.notification_id}">
                            <div class="notification-icon ${n.notification_type}">
                                <span class="material-icons">${getNotificationIcon(n.notification_type)}</span>
                            </div>
                            <div class="notification-content">
                                <p class="notification-title">${escapeHtml(n.title)}</p>
                                <p class="notification-text">${escapeHtml(n.message || '')}</p>
                                <span class="notification-time">${timeAgo(n.created_at)}</span>
                            </div>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<div class="empty-state"><span class="material-icons">notifications_off</span><p>No notifications</p></div>';
                }
            } catch (error) {
                list.innerHTML = '<div class="error-state">Failed to load</div>';
            }
        }

        function getNotificationIcon(type) {
            const icons = {
                security: 'security',
                system: 'settings',
                report: 'description',
                user: 'person',
                backup: 'backup',
                error: 'error',
                warning: 'warning',
                info: 'info',
                success: 'check_circle'
            };
            return icons[type] || 'notifications';
        }

        // Mark all notifications as read
        document.getElementById('markAllRead')?.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'mark_notification_read');
            formData.append('csrf_token', CSRF_TOKEN);

            await fetch(BASE_URL + 'admin-secure/ajax/admin.php', { method: 'POST', body: formData });
            
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.style.display = 'none';
        });

        // Load notifications when dropdown opens
        document.getElementById('notificationBtn')?.addEventListener('click', loadNotifications);

        // Logout - now handled by direct link, but keep this for compatibility
        document.getElementById('adminLogout')?.addEventListener('click', function(e) {
            // Let the direct link work - don't prevent default
            // The link now goes to admin-logout.php which handles everything
        });

        // Global Search (Ctrl+K)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch')?.focus();
            }
        });

        // Toast notifications
        function showToast(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const icons = {
                success: 'check_circle',
                error: 'error',
                warning: 'warning',
                info: 'info'
            };

            toast.innerHTML = `
                <span class="material-icons toast-icon">${icons[type]}</span>
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <span class="material-icons">close</span>
                </button>
            `;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // Confirm modal
        function showConfirm(title, message, onConfirm, options = {}) {
            const modal = document.getElementById('confirmModal');
            const iconEl = document.getElementById('confirmIcon');
            const titleEl = document.getElementById('confirmTitle');
            const messageEl = document.getElementById('confirmMessage');
            const okBtn = document.getElementById('confirmOk');
            const cancelBtn = document.getElementById('confirmCancel');

            titleEl.textContent = title;
            messageEl.textContent = message;

            const type = options.type || 'warning';
            iconEl.className = `modal-icon ${type}`;
            iconEl.querySelector('.material-icons').textContent = 
                type === 'danger' ? 'error' : type === 'warning' ? 'warning' : 'help';

            okBtn.className = `btn btn-${type === 'danger' ? 'danger' : 'primary'}`;
            okBtn.textContent = options.confirmText || 'Confirm';

            modal.classList.add('active');

            const handleConfirm = () => {
                modal.classList.remove('active');
                okBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
                onConfirm();
            };

            const handleCancel = () => {
                modal.classList.remove('active');
                okBtn.removeEventListener('click', handleConfirm);
                cancelBtn.removeEventListener('click', handleCancel);
            };

            okBtn.addEventListener('click', handleConfirm);
            cancelBtn.addEventListener('click', handleCancel);
        }

        // Loading overlay
        function showLoading(text = 'Loading...') {
            document.getElementById('loadingText').textContent = text;
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        // Utility functions
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
            return date.toLocaleDateString();
        }

        function formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        }

        // API helper
        async function adminAPI(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('csrf_token', CSRF_TOKEN);
            
            Object.keys(data).forEach(key => {
                formData.append(key, data[key]);
            });

            const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', {
                method: 'POST',
                body: formData
            });

            return response.json();
        }

        // Chart.js default config
        Chart.defaults.color = '#6c7086';
        Chart.defaults.borderColor = '#45475a';
        Chart.defaults.font.family = "'Inter', sans-serif";
    </script>
</body>
</html>
