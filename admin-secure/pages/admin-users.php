<?php
/**
 * Admin User Management
 * Full CRUD operations for user management
 */
$currPage = "Users";

require_once __DIR__ . '/../../config/config.php';

$db = new Database();

// Get user stats
$totalUsers = $db->single("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$activeUsers = $db->single("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'] ?? 0;
$verifiedUsers = $db->single("SELECT COUNT(*) as count FROM users WHERE is_verified = 1")['count'] ?? 0;
$newTodayUsers = $db->single("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count'] ?? 0;

// Get user listing
$users = $db->resultSet("
    SELECT 
        user_id, 
        email, 
        phone, 
        first_name, 
        last_name, 
        role, 
        is_active, 
        is_verified, 
        last_login, 
        created_at, 
        updated_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 100
") ?? [];

require_once __DIR__ . '/../layouts/admin-header.php';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage all users, roles, and permissions</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="exportUsers()">
            <span class="material-icons">download</span>
            Export
        </button>
        <button class="btn btn-primary" onclick="showCreateUserModal()">
            <span class="material-icons">person_add</span>
            Add User
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card mini">
        <div class="stat-icon users">
            <span class="material-icons">people</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $totalUsers; ?></span>
            <span class="stat-label">Total Users</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon sessions">
            <span class="material-icons">check_circle</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $activeUsers; ?></span>
            <span class="stat-label">inActive</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon logins">
            <span class="material-icons">verified</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $verifiedUsers; ?></span>
            <span class="stat-label">Verified</span>
        </div>
    </div>
    <div class="stat-card mini">
        <div class="stat-icon security">
            <span class="material-icons">person_add</span>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $newTodayUsers; ?></span>
            <span class="stat-label">New Today</span>
        </div>
    </div>
</div>

<!-- Filters & Table -->
<div class="card">
    <div class="card-header">
        <div class="table-filters">
            <div class="search-box">
                <span class="material-icons">search</span>
                <input type="text" id="searchInput" placeholder="Search users...">
            </div>
            <select id="roleFilter" class="filter-select">
                <option value="">All Roles</option>
                <option value="farmer">Farmer</option>
                <option value="officer">Officer</option>
                <option value="admin">Admin</option>
            </select>
            <select id="statusFilter" class="filter-select">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="table-actions">
            <span id="selectedCount" class="selected-count" style="display: none;">
                <span class="count">0</span> selected
            </span>
            <div class="bulk-actions" id="bulkActions" style="display: none;">
                <button class="btn btn-sm btn-secondary" onclick="bulkAction('activate')">Activate</button>
                <button class="btn btn-sm btn-secondary" onclick="bulkAction('deactivate')">Deactivate</button>
                <button class="btn btn-sm btn-danger" onclick="bulkAction('delete')">Delete</button>
            </div>
        </div>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th class="checkbox-col">
                            <input type="checkbox" id="selectAll" class="table-checkbox">
                        </th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>Last Login</th>
                        <th>Joined</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if(!empty($users)): ?>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td class="checkbox-col">
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['user_id']; ?>">
                                </td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-xs">
                                            <?php 
                                            $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                                            echo htmlspecialchars($initials);
                                            ?>
                                        </div>
                                        <div class="user-cell-info">
                                            <div class="user-cell-name">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                            <div class="user-cell-phone">
                                                <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                                </td>
                                <td>
                                    <span class="role-badge <?php echo htmlspecialchars($user['role']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($user['is_active']): ?>
                                        <span class="status-badge active">
                                            <span class="material-icons" style="font-size: 14px;">check_circle</span>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">
                                            <span class="material-icons" style="font-size: 14px;">cancel</span>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="verified-badge <?php echo $user['is_verified'] ? 'yes' : 'no'; ?>">
                                        <span class="material-icons">
                                            <?php echo $user['is_verified'] ? 'verified_user' : 'person'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <?php 
                                            $lastLogin = $user['last_login'] ? new DateTime($user['last_login']) : null;
                                            if($lastLogin) {
                                                echo $lastLogin->format('M d, Y');
                                                echo '<div class="time">' . $lastLogin->format('H:i') . '</div>';
                                            } else {
                                                echo 'Never';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <?php 
                                            $createdAt = new DateTime($user['created_at']);
                                            echo $createdAt->format('M d, Y');
                                            echo '<div class="time">' . $createdAt->format('H:i') . '</div>';
                                        ?>
                                    </div>
                                </td>
                                <td class="actions-col">
                                    <div class="action-buttons">
                                        <button class="action-btn" onclick="showViewUserModal(<?php echo $user['user_id']; ?>)" title="View">
                                            <span class="material-icons">visibility</span>
                                        </button>
                                        <button class="action-btn" onclick="showEditUserModal(<?php echo $user['user_id']; ?>)" title="Edit">
                                            <span class="material-icons">edit</span>
                                        </button>
                                        <?php if($user['is_active']): ?>
                                            <button class="action-btn" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 0)" title="Deactivate">
                                                <span class="material-icons">toggle_on</span>
                                            </button>
                                        <?php else: ?>
                                            <button class="action-btn" onclick="toggleUserStatus(<?php echo $user['user_id']; ?>, 1)" title="Activate">
                                                <span class="material-icons">toggle_off</span>
                                            </button>
                                        <?php endif; ?>
                                        <button class="action-btn danger" onclick="confirmDelete(<?php echo $user['user_id']; ?>)" title="Delete">
                                            <span class="material-icons">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state small">
                                    <span class="material-icons">people</span>
                                    <p>No users found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <div class="pagination-info">
            Showing <span id="showingFrom"><?php echo empty($users) ? '0' : '1'; ?></span>-<span id="showingTo"><?php echo count($users); ?></span> of <span id="totalCount"><?php echo $totalUsers; ?></span> users
        </div>
        <div class="pagination" id="pagination"></div>
    </div>
</div>

<script src="<?php echo $base_url; ?>admin-secure/assets/js/admin.js"></script>
<script>
// ========================================
// USER MANAGEMENT PAGE - INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize variables
    window.BASE_URL = document.getElementById('baseUrl')?.value || '/smartcashi/';
    window.CSRF_TOKEN = document.getElementById('csrfToken')?.value || '';

    // Load users on page load
    loadUsers(1);
    
    // Setup event listeners
    setupEventListeners();
});

// ========================================
// EVENT LISTENERS SETUP
// ========================================

function setupEventListeners() {
    // Search input
    document.getElementById('searchInput')?.addEventListener('input', function() {
        loadUsers(1);
    });
    
    // Role filter
    document.getElementById('roleFilter')?.addEventListener('change', function() {
        loadUsers(1);
    });
    
    // Status filter
    document.getElementById('statusFilter')?.addEventListener('change', function() {
        loadUsers(1);
    });
    
    // Form submissions
    document.getElementById('userForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
    
    document.getElementById('banForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        submitBan();
    });
    
    // Ban type change
    document.getElementById('banType')?.addEventListener('change', function() {
        const durationGroup = document.getElementById('banDurationGroup');
        if (durationGroup) {
            durationGroup.style.display = this.value === 'temporary' ? 'block' : 'none';
        }
    });
    
    // Select all checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateSelection();
    });
    
    // Individual checkboxes
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
}

// ========================================
// API REQUEST HANDLER
// ========================================

async function adminAPI(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('csrf_token', CSRF_TOKEN);
    
    Object.keys(data).forEach(key => {
        formData.append(key, data[key]);
    });
    
    try {
        const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'API Error: ' + error.message };
    }
}

// ========================================
// TOAST & NOTIFICATION
// ========================================

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function showConfirm(title, message, callback, options = {}) {
    if (confirm(`${title}\n\n${message}`)) {
        callback();
    }
}

// ========================================
// LOAD USERS
// ========================================

async function loadUsers(page = 1) {
    const search = document.getElementById('searchInput')?.value || '';
    const role = document.getElementById('roleFilter')?.value || '';
    const status = document.getElementById('statusFilter')?.value || '';
    
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = `<tr class="loading-row"><td colspan="9"><div class="loading-spinner"><span class="material-icons spinning">sync</span> Loading users...</div></td></tr>`;
    
    try {
        const data = await adminAPI('get_users', { page, search, role, status, limit: 20 });

        if (data.success) {
            renderUsers(data.data);
            renderPagination(data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="9" class="empty-state">${data.message || 'Failed to load users'}</td></tr>`;
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="9" class="empty-state">Error loading users</td></tr>`;
    }
}

// ========================================
// RENDER FUNCTIONS
// ========================================

function renderUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    if (!users || users.length === 0) {
        tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state small"><span class="material-icons">people</span><p>No users found</p></div></td></tr>`;
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr data-id="${user.user_id}">
            <td>
                <input type="checkbox" class="table-checkbox user-checkbox" value="${user.user_id}" ${selectedUsers.has(user.user_id.toString()) ? 'checked' : ''}>
            </td>
            <td>
                <div class="user-cell">
                    <div class="user-avatar-xs">${user.first_name.charAt(0).toUpperCase()}</div>
                    <div class="user-cell-info">
                        <div class="user-cell-name">${escapeHtml(user.first_name)} ${escapeHtml(user.last_name || '')}</div>
                        <div class="user-cell-phone">${user.phone || '-'}</div>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(user.email)}</td>
            <td><span class="role-badge ${user.role}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></td>
            <td><span class="status-badge ${parseInt(user.is_active) ? 'active' : 'inactive'}">${parseInt(user.is_active) ? 'Active' : 'Inactive'}</span></td>
            <td><div class="verified-badge ${user.is_verified ? 'yes' : 'no'}"><span class="material-icons">${user.is_verified ? 'verified' : 'cancel'}</span></div></td>
            <td class="date-cell">${user.last_login ? formatDate(user.last_login) : 'Never'}</td>
            <td class="date-cell">${formatDate(user.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn" onclick="viewUser(${user.user_id})" title="View"><span class="material-icons">visibility</span></button>
                    <button class="action-btn" onclick="editUser(${user.user_id})" title="Edit"><span class="material-icons">edit</span></button>
                    <button class="action-btn danger" onclick="deleteUser(${user.user_id})" title="Delete"><span class="material-icons">delete</span></button>
                </div>
            </td>
        </tr>
    `).join('');
    
    // Re-attach checkbox handlers
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
}

function renderPagination(pagination) {
    const { page, total, pages, limit } = pagination;
    
    const from = (page - 1) * limit + 1;
    const to = Math.min(page * limit, total);
    
    document.getElementById('showingFrom').textContent = total > 0 ? from : 0;
    document.getElementById('showingTo').textContent = to;
    document.getElementById('totalCount').textContent = total;
    
    const container = document.getElementById('pagination');
    if (!container) return;
    
    let html = '';
    
    // Previous button
    html += `<button class="pagination-btn" onclick="loadUsers(${page - 1})" ${page <= 1 ? 'disabled' : ''}><span class="material-icons">chevron_left</span></button>`;
    
    // Page numbers
    const startPage = Math.max(1, page - 2);
    const endPage = Math.min(pages, page + 2);
    
    if (startPage > 1) {
        html += `<button class="pagination-btn" onclick="loadUsers(1)">1</button>`;
        if (startPage > 2) html += `<span class="pagination-dots">...</span>`;
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === page ? 'active' : ''}" onclick="loadUsers(${i})">${i}</button>`;
    }
    
    if (endPage < pages) {
        if (endPage < pages - 1) html += `<span class="pagination-dots">...</span>`;
        html += `<button class="pagination-btn" onclick="loadUsers(${pages})">${pages}</button>`;
    }
    
    // Next button
    html += `<button class="pagination-btn" onclick="loadUsers(${page + 1})" ${page >= pages ? 'disabled' : ''}><span class="material-icons">chevron_right</span></button>`;
    
    container.innerHTML = html;
}

// ========================================
// SELECTION HANDLING
// ========================================

let selectedUsers = new Set();
let currentPage = 1;

function updateSelection() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    selectedUsers.clear();
    checked.forEach(cb => selectedUsers.add(cb.value));
    
    const count = selectedUsers.size;
    const selectedCountEl = document.getElementById('selectedCount');
    const bulkActionsEl = document.getElementById('bulkActions');
    
    if (selectedCountEl && bulkActionsEl) {
        if (count > 0) {
            selectedCountEl.style.display = 'flex';
            bulkActionsEl.style.display = 'flex';
            selectedCountEl.querySelector('.count').textContent = count;
        } else {
            selectedCountEl.style.display = 'none';
            bulkActionsEl.style.display = 'none';
        }
    }
    
    document.getElementById('selectAll').checked = checked.length === document.querySelectorAll('.user-checkbox').length && checked.length > 0;
}

// ========================================
// USER ACTIONS
// ========================================

function showCreateUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('password').required = true;
    document.getElementById('userModal').classList.add('active');
}

function hideUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

async function editUser(userId) {
    const data = await adminAPI('get_user', { user_id: userId });
    
    if (data.success) {
        const user = data.data;
        document.getElementById('userModalTitle').textContent = 'Edit User';
        document.getElementById('userId').value = user.user_id;
        document.getElementById('firstName').value = user.first_name;
        document.getElementById('lastName').value = user.last_name || '';
        document.getElementById('email').value = user.email;
        document.getElementById('phone').value = user.phone || '';
        document.getElementById('role').value = user.role;
        document.getElementById('status').value = user.is_active;
        document.getElementById('isVerified').checked = user.is_verified;
        document.getElementById('passwordGroup').style.display = 'none';
        document.getElementById('password').required = false;
        document.getElementById('userModal').classList.add('active');
    } else {
        showToast(data.message || 'Failed to load user', 'error');
    }
}

async function viewUser(userId) {
    const modalContent = document.getElementById('viewUserContent');
    if (!modalContent) return;
    
    document.getElementById('viewUserModal').classList.add('active');
    modalContent.innerHTML = '<div class="loading-spinner"><span class="material-icons spinning">sync</span></div>';
    
    const data = await adminAPI('get_user', { user_id: userId });
    
    if (data.success) {
        const user = data.data;
        modalContent.innerHTML = `
            <div class="user-profile-header">
                <div class="user-profile-avatar">${user.first_name.charAt(0).toUpperCase()}</div>
                <div class="user-profile-info">
                    <h4>${escapeHtml(user.first_name)} ${escapeHtml(user.last_name || '')}</h4>
                    <p>${escapeHtml(user.email)}</p>
                </div>
            </div>
            <div class="user-details-grid">
                <div class="detail-item">
                    <div class="label">Role</div>
                    <div class="value"><span class="role-badge ${user.role}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></div>
                </div>
                <div class="detail-item">
                    <div class="label">Status</div>
                    <div class="value"><span class="status-badge ${parseInt(user.is_active) ? 'active' : 'inactive'}">${parseInt(user.is_active) ? 'Active' : 'Inactive'}</span></div>
                </div>
                <div class="detail-item">
                    <div class="label">Phone</div>
                    <div class="value">${user.phone || '-'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Verified</div>
                    <div class="value">${user.is_verified ? 'Yes' : 'No'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Last Login</div>
                    <div class="value">${user.last_login ? formatDate(user.last_login) : 'Never'}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Joined</div>
                    <div class="value">${formatDate(user.created_at)}</div>
                </div>
            </div>
        `;
    } else {
        modalContent.innerHTML = '<div class="empty-state">Failed to load user</div>';
    }
}

function hideViewUserModal() {
    document.getElementById('viewUserModal').classList.remove('active');
}

function deleteUser(userId) {
    showConfirm('Delete User', 'Are you sure you want to delete this user? This action cannot be undone.', async () => {
        const data = await adminAPI('delete_user', { user_id: userId });
        
        if (data.success) {
            showToast('User deleted successfully', 'success');
            loadUsers(currentPage);
        } else {
            showToast(data.message || 'Failed to delete user', 'error');
        }
    }, { type: 'danger' });
}

function bulkAction(action) {
    if (selectedUsers.size === 0) return;
    
    const actions = {
        activate: 'Activate',
        deactivate: 'Deactivate',
        delete: 'Delete'
    };
    
    showConfirm(actions[action] + ' Users', `${actions[action]} ${selectedUsers.size} selected users?`, async () => {
        showToast(`${actions[action]} completed`, 'success');
        selectedUsers.clear();
        updateSelection();
        loadUsers(currentPage);
    }, { type: action === 'delete' ? 'danger' : 'warning' });
}

async function exportUsers() {
    try {
        const formData = new FormData();
        formData.append('action', 'export_users');
        formData.append('csrf_token', CSRF_TOKEN);
        
        const response = await fetch(BASE_URL + 'admin-secure/ajax/admin.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        if (data.success && data.downloadUrl) {
            showToast(`Exported ${data.recordCount} users`, 'success');
            const link = document.createElement('a');
            link.href = BASE_URL + data.downloadUrl.substring(1);
            link.download = data.fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            showToast(data.message || 'Export failed', 'error');
        }
    } catch (error) {
        console.error('Export error:', error);
        showToast('Error exporting users', 'error');
    }
}

// ========================================
// BAN MODAL
// ========================================

function showBanModal(userId) {
    document.getElementById('banUserId').value = userId;
    document.getElementById('banType').value = 'temporary';
    document.getElementById('banDuration').value = '7';
    document.getElementById('banReason').value = '';
    document.getElementById('banDurationGroup').style.display = 'block';
    document.getElementById('banUserModal').classList.add('active');
}

function hideBanModal() {
    document.getElementById('banUserModal').classList.remove('active');
}

function submitBan() {
    // Ban logic here
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// WRAPPER FUNCTIONS
// ========================================

function showEditUserModal(userId) {
    editUser(userId);
}

function showViewUserModal(userId) {
    viewUser(userId);
}

function confirmDelete(userId) {
    deleteUser(userId);
}

async function toggleUserStatus(userId, newStatus) {
    const statusText = newStatus === 1 ? 'activate' : 'deactivate';
    const confirmMessage = newStatus === 1 ? 'Activate this user?' : 'Deactivate this user?';
    
    showConfirm(confirmMessage.replace('?', ''), async () => {
        try {
            const response = await adminAPI('update_user', {
                user_id: userId,
                is_active: newStatus
            });
            
            if (response.success) {
                showToast(`User ${statusText}d successfully`, 'success');
                loadUsers(currentPage);
            } else {
                showToast(response.message || `Failed to ${statusText} user`, 'error');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            showToast('Error updating user status', 'error');
        }
    });
}

function showCreateUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordGroup').style.display = 'block';
    document.getElementById('password').required = true;
    document.getElementById('userModal').classList.add('active');
}

async function saveUser() {
    const userId = document.getElementById('userId').value;
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const role = document.getElementById('role').value;
    const status = document.getElementById('status').value;
    const password = document.getElementById('password').value;
    const isVerified = document.getElementById('isVerified').checked ? 1 : 0;
    const saveBtn = document.getElementById('saveUserBtn');
    
    // Validation
    if (!firstName.trim()) {
        showToast('First name is required', 'error');
        return;
    }
    if (!email.trim()) {
        showToast('Email is required', 'error');
        return;
    }
    if (!userId && !password) {
        showToast('Password is required for new users', 'error');
        return;
    }
    if (password && password.length < 8) {
        showToast('Password must be at least 8 characters', 'error');
        return;
    }
    
    // Disable button and show loading state
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="material-icons spinning">sync</span> Saving...';
    
    try {
        const data = {
            user_id: userId || '',
            first_name: firstName,
            last_name: lastName,
            email: email,
            phone: phone,
            role: role,
            is_active: status,
            is_verified: isVerified,
            password: password || ''
        };
        
        const response = await adminAPI(userId ? 'update_user' : 'create_user', data);
        
        if (response.success) {
            showToast(userId ? 'User updated successfully' : 'User created successfully', 'success');
            hideUserModal();
            loadUsers(currentPage);
        } else {
            showToast(response.message || 'Failed to save user', 'error');
        }
    } catch (error) {
        console.error('Save error:', error);
        showToast('Error saving user', 'error');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<span class="material-icons">save</span> Save User';
    }
}
</script>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>

<!-- Create/Edit User Modal -->
<div class="modal-overlay" id="userModal" onclick="if(event.target === this) hideUserModal()">
    <div class="modal-box user-modal">
        <div class="modal-header">
            <h3 id="userModalTitle">Add User</h3>
            <button class="modal-close" onclick="hideUserModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id" value="">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" id="firstName" name="first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="lastName" name="last_name" class="form-input">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-input">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select id="role" name="role" class="form-input" required>
                            <option value="farmer">Farmer</option>
                            <option value="officer">Officer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="status" name="is_active" class="form-input">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label class="form-label">Password *</label>
                    <input type="password" id="password" name="password" class="form-input" minlength="8">
                    <span class="form-hint">Minimum 8 characters</span>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="isVerified" name="is_verified" value="1">
                        <span>Mark as verified</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideUserModal()">Cancel</button>
            <button type="submit" form="userForm" class="btn btn-primary" id="saveUserBtn">Save User</button>
        </div>
    </div>
</div>

<!-- View User Modal -->
<div class="modal-overlay" id="viewUserModal" onclick="if(event.target === this) hideViewUserModal()">
    <div class="modal-box view-user-modal">
        <div class="modal-header">
            <h3>User Details</h3>
            <button class="modal-close" onclick="hideViewUserModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body" id="viewUserContent">
            <div class="loading-spinner">
                <span class="material-icons spinning">sync</span>
            </div>
        </div>
    </div>
</div>

<!-- Ban User Modal -->
<div class="modal-overlay" id="banUserModal" onclick="if(event.target === this) hideBanModal()">
    <div class="modal-box ban-modal">
        <div class="modal-header">
            <h3>Ban User</h3>
            <button class="modal-close" onclick="hideBanModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="banForm">
                <input type="hidden" id="banUserId" name="user_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Ban Type *</label>
                    <select id="banType" name="ban_type" class="form-input" required>
                        <option value="temporary">Temporary</option>
                        <option value="permanent">Permanent</option>
                    </select>
                </div>
                
                <div class="form-group" id="banDurationGroup">
                    <label class="form-label">Duration (days) *</label>
                    <input type="number" id="banDuration" name="duration" class="form-input" value="7" min="1" max="365">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reason *</label>
                    <textarea id="banReason" name="reason" class="form-input" rows="3" required placeholder="Explain why this user is being banned..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="hideBanModal()">Cancel</button>
            <button type="submit" form="banForm" class="btn btn-danger">Ban User</button>
        </div>
    </div>
</div>

<style>
/* ===================================
   USER MANAGEMENT PAGE STYLES
   =================================== */

/* PAGE HEADER */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.page-header-content {
    flex: 1;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 8px 0;
}

.page-subtitle {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 12px;
}

/* STATS GRID */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s;
}

.stat-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon.users {
    background: rgba(99, 102, 241, 0.2);
    color: var(--primary);
}

.stat-icon.sessions {
    background: rgba(16, 185, 129, 0.2);
    color: var(--secondary);
}

.stat-icon.logins {
    background: rgba(245, 158, 11, 0.2);
    color: var(--warning);
}

.stat-icon.security {
    background: rgba(239, 68, 68, 0.2);
    color: var(--danger);
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 4px;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 500;
}

/* CARD STYLES */
.card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    overflow: hidden;
    margin-bottom: 24px;
}

.card-header {
    padding: 20px;
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.card-body {
    padding: 20px;
}

.card-body.no-padding {
    padding: 0;
}

.card-footer {
    padding: 12px 20px;
    border-top: 1px solid var(--border);
    background: var(--bg-tertiary);
    flex-wrap: wrap;
    gap: 12px;
}

/* TABLE STYLES */
.table-filters {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 200px;
    max-width: 300px;
}

.search-box .material-icons {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.search-box input {
    width: 100%;
    padding: 8px 12px 8px 40px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
}

.search-box input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-box input::placeholder {
    color: var(--text-muted);
}

.filter-select {
    padding: 8px 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
    color: var(--text-primary);
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.selected-count {
    font-size: 13px;
    color: var(--text-muted);
    white-space: nowrap;
}

.selected-count .count {
    font-weight: 600;
    color: var(--primary);
}

.bulk-actions {
    display: flex;
    gap: 8px;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: var(--bg-tertiary);
}

.data-table thead th {
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-primary);
    border-bottom: 1px solid var(--border);
}

.data-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

.data-table tbody tr:hover {
    background: var(--bg-tertiary);
}

.data-table tbody td {
    padding: 16px;
    font-size: 13px;
    color: var(--text-secondary);
}

.checkbox-col {
    width: 40px;
}

.actions-col {
    width: 120px;
    text-align: right;
}

.table-checkbox {
    width: 16px;
    height: 16px;
    accent-color: var(--primary);
    cursor: pointer;
}

/* USER CELL & BADGES */
.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-xs {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.user-cell-info {
    min-width: 0;
    flex: 1;
}

.user-cell-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 2px;
}

.user-cell-phone {
    font-size: 12px;
    color: var(--text-muted);
}

.role-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.role-badge.farmer {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.role-badge.officer {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.role-badge.admin {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-badge.inactive {
    background: rgba(156, 163, 175, 0.2);
    color: #6b7280;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.verified-badge.yes {
    color: #10b981;
}

.verified-badge.no {
    color: #9ca3af;
}

.date-cell {
    font-size: 13px;
    color: var(--text-muted);
}


/* ACTION BUTTONS */
.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 4px;
}

.action-btn {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}

.action-btn:hover {
    background: var(--bg-secondary);
    color: var(--primary);
}

.action-btn.danger:hover {
    color: var(--danger);
}

.action-btn.active:hover {
    color: #10b981;
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    gap: 4px;
    margin-top: 20px;
}

.pagination-btn {
    padding: 8px 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
    border-color: var(--primary);
    color: var(--primary);
}

.pagination-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-dots {
    padding: 8px 4px;
    color: var(--text-muted);
}

.pagination-info {
    text-align: center;
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 16px;
}

/* MODAL STYLES */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 20px;
}

.modal.active {
    display: flex;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 999;
}

.modal-box {
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    width: 100%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
    position: relative;
    z-index: 1001;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.modal-close {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}

.modal-close:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: var(--bg-tertiary);
    border-top: 1px solid var(--border);
}

/* FORM ELEMENTS */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 6px;
    color: var(--text-primary);
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder {
    color: var(--text-muted);
}

.form-input:invalid {
    border-color: var(--danger);
}

.form-input:invalid:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-input[type="text"],
.form-input[type="email"],
.form-input[type="tel"],
.form-input[type="password"],
.form-input[type="number"],
.form-input select {
    appearance: none;
    background-image: none;
}

textarea.form-input {
    resize: vertical;
    min-height: 100px;
}

select.form-input {
    padding-right: 40px;
    cursor: pointer;
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
}

select.form-input::-ms-expand {
    display: none;
}

.form-hint {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--text-secondary);
    cursor: pointer;
}

.checkbox-label input {
    width: 16px;
    height: 16px;
    accent-color: var(--primary);
}

/* VIEW USER MODAL */
.user-profile-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 16px;
}

.user-profile-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 600;
}

.user-profile-info h4 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 4px 0;
}

.user-profile-info p {
    font-size: 14px;
    color: var(--text-muted);
    margin: 0;
}

.user-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.detail-item {
    padding: 12px;
    background: var(--bg-tertiary);
    border-radius: 8px;
}

.detail-item .label {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 4px;
    font-weight: 600;
}

.detail-item .value {
    font-size: 14px;
    color: var(--text-primary);
    font-weight: 500;
}

/* BUTTONS */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn .material-icons {
    font-size: 18px;
}

.btn-sm {
    padding: 8px 12px;
    font-size: 12px;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #5a67d8;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-primary:disabled:hover {
    background: var(--primary);
    box-shadow: none;
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border);
}

.btn-secondary:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* RESPONSIVE DESIGN */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .table-filters {
        width: 100%;
    }
    
    .search-box {
        max-width: none;
        width: 100%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .user-details-grid {
        grid-template-columns: 1fr;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .page-actions {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .page-actions .btn {
        flex: 1;
    }
    
    .data-table {
        font-size: 12px;
    }
    
    .data-table thead th,
    .data-table tbody td {
        padding: 8px 12px;
    }
    
    .modal-box {
        width: 95%;
        max-width: calc(100% - 20px);
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .modal-footer .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .modal {
        padding: 16px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .stat-card {
        padding: 16px;
    }
    
    .stat-value {
        font-size: 20px;
    }
    
    .table-filters {
        flex-direction: column;
        gap: 8px;
    }
    
    .search-box {
        width: 100%;
    }
    
    .filter-select {
        width: 100%;
    }
    
    .page-actions {
        width: 100%;
    }
    
    .page-actions .btn {
        width: 100%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-label {
        font-size: 12px;
    }
    
    .form-input {
        padding: 9px 10px;
        font-size: 16px;
    }
    
    .modal-header {
        padding: 16px;
    }
    
    .modal-body {
        padding: 16px;
    }
    
    .modal-footer {
        flex-direction: column;
        padding: 12px 16px;
        gap: 10px;
    }
    
    .modal-footer .btn {
        width: 100%;
        padding: 10px 12px;
        font-size: 13px;
    }
    
    .pagination {
        justify-content: center;
    }
    
    .action-buttons {
        gap: 2px;
    }
    
    .action-btn {
        padding: 4px;
    }
}

/* UTILITIES */
.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 20px;
    color: var(--text-muted);
}

.loading-row td {
    padding: 40px !important;
    text-align: center;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-state.small {
    padding: 40px 20px;
}

.empty-state .material-icons {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

/* TOAST NOTIFICATIONS */
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 4px;
    background: #333;
    color: white;
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 9999;
    max-width: 300px;
}

.toast.show {
    opacity: 1;
}

.toast-success {
    background: #10b981;
}

.toast-error {
    background: #ef4444;
}

.toast-warning {
    background: #f59e0b;
}

.toast-info {
    background: #3b82f6;
}

/* ANIMATIONS */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.spinning {
    animation: spin 1s linear infinite;
}

.action-btn .material-icons {
    font-size: 18px;
}
</style>

<?php include __DIR__ . '/../layouts/admin-footer.php'; ?>