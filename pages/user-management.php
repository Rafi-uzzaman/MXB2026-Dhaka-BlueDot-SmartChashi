<?php

if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    redirect('dashboard');
}
include __DIR__ . '/../layouts/header.php';

$db = new Database();

// Handle search
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

if ($roleFilter) {
    $query .= " AND role = ?";
    $params[] = $roleFilter;
}

$query .= " ORDER BY created_at DESC LIMIT 50";

$users = $db->resultSet($query, $params);
?>

<section class="hero">
    <h1>👥 User Management</h1>
    <p>Manage all users and their roles</p>
</section>

<!-- Search and Filter -->
<div class="card mt-3">
    <form method="GET" action="<?php echo $base_url; ?>user-management">
        <div class="form-row">
            <div class="form-group">
                <label for="search">Search Users</label>
                <input type="text" id="search" name="search" placeholder="Name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label for="role">Filter by Role</label>
                <select id="role" name="role">
                    <option value="">All Roles</option>
                    <option value="farmer" <?php echo $roleFilter === 'farmer' ? 'selected' : ''; ?>>Farmers</option>
                    <option value="officer" <?php echo $roleFilter === 'officer' ? 'selected' : ''; ?>>Officers</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">🔍 Search</button>
        <?php if ($search || $roleFilter): ?>
            <a href="<?php echo $base_url; ?>user-management" class="btn btn-secondary">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">All Users (<?php echo count($users); ?>)</h3>
        <a href="<?php echo $base_url; ?>add-user" class="btn btn-small btn-success">➕ Add User</a>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users): ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($u['first_name'] . ' ' . ($u['last_name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['phone']); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $u['role'] === 'admin' ? 'danger' : 
                                    ($u['role'] === 'officer' ? 'info' : 'success'); 
                            ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-success">Active</span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="<?php echo $base_url; ?>edit-user?id=<?php echo $u['user_id']; ?>" class="btn btn-small btn-info">✏️ Edit</a>
                                <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
                                    <button class="btn btn-small btn-danger" onclick="deleteUser(<?php echo $u['user_id']; ?>)">🗑️</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Role Distribution -->
<div class="grid mt-3">
    <?php
    $farmers = count(array_filter($users, fn($u) => $u['role'] === 'farmer'));
    $officers = count(array_filter($users, fn($u) => $u['role'] === 'officer'));
    $admins = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
    ?>
    
    <div class="card text-center">
        <h3 class="text-success"><?php echo $farmers; ?></h3>
        <p>🌾 Farmers</p>
    </div>
    
    <div class="card text-center">
        <h3 class="text-info"><?php echo $officers; ?></h3>
        <p>👨‍🌾 Officers</p>
    </div>
    
    <div class="card text-center">
        <h3 class="text-danger"><?php echo $admins; ?></h3>
        <p>👨‍💼 Admins</p>
    </div>
</div>

<script>
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch(baseUrl + 'api/handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=delete-user&userId=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('User deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting user');
            console.error(error);
        });
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
