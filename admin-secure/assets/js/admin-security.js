/**
 * Admin Security Center - JavaScript
 * Handles all security-related functionality
 */

// Global variables
let currentTab = 'events';
let refreshInterval = null;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeModals();
    initializeAutoRefresh();
    
    // Load initial data
    refreshStats();
});

// ==========================================
// TAB MANAGEMENT
// ==========================================

function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });
}

function switchTab(tabId) {
    // Update buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    
    // Update content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`tab-${tabId}`).classList.add('active');
    
    currentTab = tabId;
}

// ==========================================
// SECURITY EVENTS
// ==========================================

function acknowledgeEvent(eventId) {
    if (!confirm('Acknowledge this security event?')) return;
    
    showLoader('Acknowledging event...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'acknowledge_security_event',
            event_id: eventId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('Event acknowledged successfully', 'success');
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to acknowledge event', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

function acknowledgeAllEvents() {
    if (!confirm('Acknowledge all unacknowledged security events?')) return;
    
    showLoader('Acknowledging all events...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'acknowledge_all_events'
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('All events acknowledged successfully', 'success');
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to acknowledge events', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

// ==========================================
// SESSION MANAGEMENT
// ==========================================

function terminateSession(sessionId) {
    if (!confirm('Are you sure you want to terminate this session?')) return;
    
    showLoader('Terminating session...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'terminate_session',
            session_id: sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('Session terminated successfully', 'success');
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to terminate session', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

function terminateAllSessions() {
    if (!confirm('WARNING: This will terminate ALL other active admin sessions. Continue?')) return;
    
    showLoader('Terminating all sessions...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'terminate_all_sessions'
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification(`${data.count || 0} session(s) terminated successfully`, 'success');
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to terminate sessions', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

// ==========================================
// IP RULES MANAGEMENT
// ==========================================

function showAddIPModal() {
    const modal = document.getElementById('add-ip-modal');
    modal.classList.add('active');
    
    // Reset form
    document.getElementById('ip-form').reset();
}

function hideAddIPModal() {
    const modal = document.getElementById('add-ip-modal');
    modal.classList.remove('active');
}

function addIPRule(event) {
    if (event) event.preventDefault();
    
    const ipAddress = document.getElementById('ip-address').value.trim();
    const ruleType = document.getElementById('rule-type').value;
    const reason = document.getElementById('ip-reason').value.trim();
    
    // Validate IP address
    if (!ipAddress) {
        showNotification('Please enter an IP address', 'error');
        return;
    }
    
    // Simple IP validation
    const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;
    if (!ipPattern.test(ipAddress)) {
        showNotification('Please enter a valid IP address', 'error');
        return;
    }
    
    showLoader('Adding IP rule...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'add_ip_rule',
            ip_address: ipAddress,
            rule_type: ruleType,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('IP rule added successfully', 'success');
            hideAddIPModal();
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to add IP rule', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

function blockIP(ipAddress) {
    if (!confirm(`Block IP address ${ipAddress}?`)) return;
    
    showLoader('Blocking IP address...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'add_ip_rule',
            ip_address: ipAddress,
            rule_type: 'blacklist',
            reason: 'Blocked due to suspicious activity'
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('IP address blocked successfully', 'success');
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to block IP address', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

function deleteIPRule(ruleId) {
    if (!confirm('Delete this IP rule?')) return;
    
    showLoader('Deleting IP rule...');
    
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'delete_ip_rule',
            rule_id: ruleId
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('IP rule deleted successfully', 'success');
            reloadPage();
        } else {
            showNotification(data.message || 'Failed to delete IP rule', 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Network error occurred', 'error');
        console.error('Error:', error);
    });
}

// ==========================================
// AUTO-REFRESH & STATS
// ==========================================

function initializeAutoRefresh() {
    // Refresh stats every 30 seconds
    refreshInterval = setInterval(refreshStats, 30000);
}

function refreshStats() {
    fetch(window.adminBaseUrl + 'admin-secure/ajax/admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_security_stats'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            updateStatsDisplay(data.data);
        }
    })
    .catch(error => {
        console.error('Error refreshing stats:', error);
    });
}

function updateStatsDisplay(stats) {
    // Update stat cards
    if (document.getElementById('blockedIPsCard')) {
        document.getElementById('blockedIPsCard').textContent = stats.blocked_ips || 0;
    }
    if (document.getElementById('failedLoginsCard')) {
        document.getElementById('failedLoginsCard').textContent = stats.failed_logins || 0;
    }
    if (document.getElementById('activeSessionsCard')) {
        document.getElementById('activeSessionsCard').textContent = stats.active_sessions || 0;
    }
    if (document.getElementById('securityEventsCard')) {
        document.getElementById('securityEventsCard').textContent = stats.unacknowledged_events || 0;
    }
    
    // Update threat level
    if (stats.threat_level) {
        const threatLevelEl = document.getElementById('threatLevel');
        const threatValueEl = document.getElementById('threatValue');
        
        if (threatLevelEl && threatValueEl) {
            // Remove all threat level classes
            threatLevelEl.classList.remove('low', 'medium', 'high', 'critical');
            // Add current threat level
            threatLevelEl.classList.add(stats.threat_level);
            threatValueEl.textContent = stats.threat_level.toUpperCase();
        }
    }
    
    // Update threat stats
    if (document.getElementById('unacknowledgedCount')) {
        document.getElementById('unacknowledgedCount').textContent = 
            `${stats.unacknowledged_events || 0} unacknowledged events`;
    }
    if (document.getElementById('failedLoginsCount')) {
        document.getElementById('failedLoginsCount').textContent = 
            `${stats.failed_logins || 0} failed logins (24h)`;
    }
}

// ==========================================
// SECURITY SETTINGS
// ==========================================

function showSecuritySettings() {
    // Navigate to admin settings page
    window.location.href = window.adminBaseUrl + 'admin-settings';
}

// ==========================================
// MODAL MANAGEMENT
// ==========================================

function initializeModals() {
    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
    
    // Close modal on close button click
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });
    
    // Prevent form submission on Enter in modal forms
    document.querySelectorAll('.modal-box form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    });
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

function showLoader(message = 'Loading...') {
    // Check if loader already exists
    let loader = document.getElementById('global-loader');
    
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'global-loader';
        loader.className = 'global-loader';
        loader.innerHTML = `
            <div class="loader-content">
                <div class="spinner"></div>
                <p class="loader-message">${message}</p>
            </div>
        `;
        document.body.appendChild(loader);
    } else {
        loader.querySelector('.loader-message').textContent = message;
    }
    
    loader.classList.add('active');
}

function hideLoader() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.classList.remove('active');
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    }[type] || 'info';
    
    notification.innerHTML = `
        <span class="material-icons">${icon}</span>
        <span class="notification-message">${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <span class="material-icons">close</span>
        </button>
    `;
    
    // Add to container or create one
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

function reloadPage() {
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
