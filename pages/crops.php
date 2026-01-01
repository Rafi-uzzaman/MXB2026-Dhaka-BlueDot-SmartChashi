<?php

if (!isLoggedIn()) {
    redirect('login');
}

include __DIR__ . '/../layouts/header.php';

$user = getCurrentUser();
$db = new Database();
$crops = $db->resultSet("SELECT * FROM crop_data WHERE farmer_id = ? ORDER BY created_at DESC LIMIT 10", [$_SESSION['user_id']]);
?>

<section class="hero">
    <h1><span class="material-icons" style="vertical-align: middle; font-size: 2rem;">agriculture</span> <?php echo __('my_crops'); ?></h1>
    <p><?php echo __('track_manage_crops'); ?></p>
</section>

<div class="card mt-3">
        <h2><span class="material-icons" style="vertical-align: middle;">add_circle</span> <?php echo __('add_crop'); ?></h2>
        <form id="cropForm" method="POST" class="crop-form">
            <div class="form-group">
                <label for="cropName"><?php echo __('crop_name'); ?> *</label>
                <input type="text" id="cropName" name="cropName" placeholder="<?php echo __('rice_wheat_etc'); ?>" required>
            </div>

            <div class="form-group">
                <label for="variety"><?php echo __('variety'); ?></label>
                <input type="text" id="variety" name="variety" placeholder="<?php echo __('variety_eg'); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="area"><?php echo __('area_hectares'); ?> *</label>
                    <input type="number" id="area" name="area" placeholder="0.00" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="plantedDate"><?php echo __('planted_date'); ?> *</label>
                    <input type="date" id="plantedDate" name="plantedDate" required>
                </div>
            </div>

            <button type="submit" class="btn"><?php echo __('add_crop'); ?></button>
        </form>
    </div>

    <?php if ($crops): ?>
        <h2 class="mt-3"><span class="material-icons" style="vertical-align: middle;">list</span> <?php echo __('your_crops'); ?></h2>
        <div class="crops-list-grid">
        <?php foreach ($crops as $crop): ?>
        <div class="card crop-card">
            <div class="card-header">
                <h4 class="card-title"><?php echo htmlspecialchars($crop['crop_name']); ?></h4>
                <span class="badge badge-<?php echo $crop['status'] === 'growing' ? 'success' : 'info'; ?>">
                    <?php echo ucfirst($crop['status']); ?>
                </span>
            </div>

            <div class="card-content">
                <?php if ($crop['variety']): ?>
                    <p><span class="material-icons crop-detail-icon">category</span><strong><?php echo __('variety'); ?>:</strong> <?php echo htmlspecialchars($crop['variety']); ?></p>
                <?php endif; ?>
                <p><span class="material-icons crop-detail-icon">landscape</span><strong><?php echo __('area_hectares'); ?>:</strong> <?php echo $crop['area_hectares']; ?></p>
                <p><span class="material-icons crop-detail-icon">event</span><strong><?php echo __('planted'); ?>:</strong> <?php echo date('M d, Y', strtotime($crop['planted_date'])); ?></p>
                <?php if ($crop['expected_harvest']): ?>
                    <p><span class="material-icons crop-detail-icon">calendar_today</span><strong><?php echo __('expected_harvest'); ?>:</strong> <?php echo date('M d, Y', strtotime($crop['expected_harvest'])); ?></p>
                <?php endif; ?>
            </div>

            <div class="card-footer crop-actions">
                <a href="<?php echo $base_url; ?>disease/<?php echo $crop['crop_id']; ?>" class="btn btn-small"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">bug_report</span> <?php echo __('disease_check'); ?></a>
                <a href="#" class="btn btn-small btn-danger"><span class="material-icons" style="font-size: 16px; vertical-align: middle;">delete</span> <?php echo __('delete'); ?></a>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
<?php else: ?>
    <div class="card text-center mt-4">
        <h3><?php echo __('no_crops_added'); ?></h3>
        <p><?php echo __('add_first_crop_form'); ?></p>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
