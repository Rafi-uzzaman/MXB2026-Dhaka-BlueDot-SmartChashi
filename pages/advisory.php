<?php
/**
 * Advisory Management - For Agricultural Officers
 * Create and manage farming advisories
 */

// Authentication and role check
if (!isLoggedIn()) {
    redirect('login');
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'officer') {
    redirect('home');
}

include __DIR__ . '/../layouts/header.php';

$db = new Database();

// Get recent advisories
$advisories = $db->resultSet("SELECT a.*, u.first_name, u.last_name,
          DATE_FORMAT(a.created_at, '%M %d, %Y') as formatted_date
          FROM advisories a
          LEFT JOIN users u ON a.created_by = u.user_id
          WHERE a.created_by = ?
          ORDER BY a.created_at DESC LIMIT 20", [$_SESSION['user_id']]);

$crops = ['Rice', 'Wheat', 'Corn', 'Potato', 'Tomato', 'Onion', 'Garlic', 'Jute', 'Sugarcane', 'Tea'];
?>

<section class="hero">
    <h1><span class="material-icons">assignment</span> <?php echo __('advisory_management'); ?></h1>
    <p><?php echo __('create_publish_advisories_desc'); ?></p>
</section>

<!-- Create Advisory Form -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">note_add</span>
            <?php echo __('create_new_advisory'); ?>
        </h3>
    </div>
    <form id="advisoryForm" class="card-body">
        <div class="form-group">
            <label for="advisoryTitle"><?php echo __('advisory_title'); ?> *</label>
            <input type="text" id="advisoryTitle" name="title" placeholder="<?php echo __('eg_best_practices'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="cropType"><?php echo __('crop_type'); ?></label>
            <select id="cropType" name="crop_type">
                <option value="all"><?php echo __('all_crops'); ?></option>
                <?php foreach ($crops as $crop): ?>
                    <option value="<?php echo $crop; ?>"><?php echo $crop; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="category"><?php echo __('category'); ?> *</label>
            <select id="category" name="category" required>
                <option value=""><?php echo __('select_category'); ?></option>
                <option value="planting"><?php echo __('planting_sowing'); ?></option>
                <option value="irrigation"><?php echo __('irrigation'); ?></option>
                <option value="fertilizer"><?php echo __('fertilizer_application'); ?></option>
                <option value="pest"><?php echo __('pest_management'); ?></option>
                <option value="disease"><?php echo __('disease_control'); ?></option>
                <option value="harvest"><?php echo __('harvesting'); ?></option>
                <option value="post_harvest"><?php echo __('post_harvest'); ?></option>
                <option value="general"><?php echo __('general_advice'); ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="season"><?php echo __('season'); ?></label>
            <select id="season" name="season">
                <option value="all"><?php echo __('all_seasons'); ?></option>
                <option value="rabi"><?php echo __('rabi_winter'); ?></option>
                <option value="kharif"><?php echo __('kharif_summer'); ?></option>
                <option value="monsoon"><?php echo __('monsoon'); ?></option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="content"><?php echo __('advisory_content'); ?> *</label>
            <textarea id="content" name="content" rows="8" placeholder="<?php echo __('detailed_advisory_info'); ?>" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="recommendations"><?php echo __('key_recommendations'); ?></label>
            <textarea id="recommendations" name="recommendations" rows="4" placeholder="<?php echo __('bullet_points_key'); ?>"></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="publish" value="1" checked>
                <?php echo __('publish_immediately'); ?>
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <span class="material-icons">publish</span>
                <?php echo __('publish_advisory'); ?>
            </button>
            <button type="reset" class="btn btn-secondary">
                <span class="material-icons">clear</span>
                <?php echo __('clear'); ?>
            </button>
        </div>
    </form>
</div>

<!-- Recent Advisories -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <span class="material-icons">library_books</span>
            <?php echo __('my_published_advisories'); ?> (<?php echo count($advisories); ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($advisories)): ?>
            <div class="empty-state">
                <span class="material-icons" style="font-size: 64px; opacity: 0.3;">description</span>
                <p><?php echo __('no_advisories_published'); ?></p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 1rem;">
                <?php foreach ($advisories as $advisory): ?>
                    <div class="advisory-card" style="background: var(--bg-card); border-radius: 12px; padding: 1.5rem; border-left: 4px solid var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.2rem; color: var(--primary);">
                                    <?php echo htmlspecialchars($advisory['title']); ?>
                                </h4>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.875rem; color: var(--text-muted);">
                                    <?php if ($advisory['crop_type']): ?>
                                        <span>
                                            <span class="material-icons" style="font-size: 16px; vertical-align: middle;">agriculture</span>
                                            <?php echo htmlspecialchars($advisory['crop_type']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>
                                        <span class="material-icons" style="font-size: 16px; vertical-align: middle;">category</span>
                                        <?php echo ucfirst(str_replace('_', ' ', $advisory['category'])); ?>
                                    </span>
                                    <span>
                                        <span class="material-icons" style="font-size: 16px; vertical-align: middle;">calendar_today</span>
                                        <?php echo $advisory['formatted_date']; ?>
                                    </span>
                                </div>
                            </div>
                            <button class="btn btn-small btn-primary" onclick="viewAdvisory(<?php echo $advisory['advisory_id']; ?>)">
                                <span class="material-icons">visibility</span>
                                View
                            </button>
                        </div>
                        <p style="margin: 0; color: var(--text-secondary); line-height: 1.6;">
                            <?php echo htmlspecialchars(substr($advisory['content'], 0, 200)) . (strlen($advisory['content']) > 200 ? '...' : ''); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('advisoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'create_advisory');
    
    fetch('<?php echo $base_url; ?>ajax/officer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Advisory published successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to publish advisory. Please try again.');
    });
});

function viewAdvisory(id) {
    alert('Advisory details view - Feature coming soon! Advisory ID: ' + id);
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
