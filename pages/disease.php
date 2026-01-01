<?php

if (!isLoggedIn()) {
    redirect('login');
}

include __DIR__ . '/../layouts/header.php';

$user = getCurrentUser();
$db = new Database();
$crops = $db->resultSet("SELECT * FROM crop_data WHERE farmer_id = ? ORDER BY created_at DESC", [$_SESSION['user_id']]);
?>

<style>
:root {
    --disease-primary: var(--primary);
    --disease-secondary: var(--secondary);
    --disease-danger: #ef4444;
    --disease-warning: #f59e0b;
    --disease-success: #22c55e;
}

.disease-detection-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.disease-hero-section {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 3rem 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    text-align: center;
    color: white;
    box-shadow: 0 10px 40px rgba(85, 122, 70, 0.3);
}

.disease-hero-section h1 {
    font-size: 2.5rem;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.disease-hero-section p {
    font-size: 1.1rem;
    opacity: 0.95;
    margin: 0;
}

.disease-main-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.disease-upload-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.crop-selector {
    margin-bottom: 2rem;
}

.crop-selector label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 1.05rem;
}

.crop-selector select {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    background: white;
    transition: all 0.3s ease;
}

.crop-selector select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(85, 122, 70, 0.1);
}

.upload-zone {
    border: 3px dashed #d1d5db;
    border-radius: 16px;
    padding: 3rem 2rem;
    text-align: center;
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.upload-zone:hover {
    border-color: var(--primary);
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    transform: translateY(-2px);
}

.upload-zone.drag-over {
    border-color: var(--primary);
    background: #d1fae5;
    border-style: solid;
}

.upload-icon {
    font-size: 5rem !important;
    color: #9ca3af;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.upload-zone:hover .upload-icon {
    color: var(--primary);
    transform: scale(1.1);
}

.upload-zone h3 {
    color: #374151;
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
}

.upload-zone p {
    color: #6b7280;
    margin: 0;
}

.image-preview-section {
    display: none;
    position: relative;
    margin-bottom: 2rem;
}

.image-preview-section.active {
    display: block;
}

.preview-image-container {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.preview-image-container img {
    width: 100%;
    height: auto;
    max-height: 500px;
    object-fit: contain;
    background: #000;
}

.remove-preview-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.95);
    color: white;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.remove-preview-btn:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.capture-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin: 2rem 0;
}

.capture-option-btn {
    padding: 1.5rem;
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.capture-option-btn:hover {
    border-color: var(--primary);
    background: #f0fdf4;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(85, 122, 70, 0.2);
}

.capture-option-btn .material-icons {
    font-size: 3.5rem;
    color: var(--primary);
}

.capture-option-btn span:last-child {
    font-weight: 600;
    color: #374151;
    font-size: 1.05rem;
}

.analyze-btn {
    width: 100%;
    padding: 1.25rem;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

.analyze-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(85, 122, 70, 0.4);
}

.analyze-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.analyze-btn .material-icons {
    font-size: 1.5rem;
}

.info-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.info-card h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
    margin: 0 0 1.25rem 0;
    font-size: 1.2rem;
}

.instruction-steps {
    list-style: none;
    padding: 0;
    margin: 0;
    counter-reset: step;
}

.instruction-steps li {
    counter-increment: step;
    padding: 1rem 1rem 1rem 3.5rem;
    margin-bottom: 0.75rem;
    background: #f9fafb;
    border-radius: 12px;
    position: relative;
    font-size: 0.95rem;
    color: #4b5563;
}

.instruction-steps li::before {
    content: counter(step);
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.tips-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
}

.tips-card h3 {
    color: white;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tips-list li {
    padding: 0.875rem 1rem;
    margin-bottom: 0.75rem;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    border-left: 4px solid rgba(255, 255, 255, 0.5);
    font-size: 0.95rem;
    backdrop-filter: blur(10px);
}

.loading-overlay {
    display: none;
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.loading-overlay.active {
    display: block;
}

.spinner {
    font-size: 5rem !important;
    color: var(--primary);
    animation: spin 1.5s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.loading-overlay h3 {
    color: #374151;
    margin: 1.5rem 0 0.5rem 0;
}

.loading-overlay p {
    color: #6b7280;
    margin: 0;
}

.results-section {
    display: none;
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-top: 2rem;
}

.results-section.active {
    display: block;
    animation: slideUp 0.4s ease-out;
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

.result-header {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid #f3f4f6;
}

.result-icon-container {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.result-icon-container .material-icons {
    font-size: 3rem;
}

.severity-high .result-icon-container {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: var(--disease-danger);
}

.severity-medium .result-icon-container {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: var(--disease-warning);
}

.severity-low .result-icon-container {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: var(--disease-success);
}

.result-info h2 {
    margin: 0 0 0.5rem 0;
    color: #111827;
    font-size: 1.8rem;
}

.severity-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.severity-high .severity-badge {
    background: var(--disease-danger);
    color: white;
}

.severity-medium .severity-badge {
    background: var(--disease-warning);
    color: white;
}

.severity-low .severity-badge {
    background: var(--disease-success);
    color: white;
}

.confidence-section {
    margin: 2rem 0;
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 12px;
}

.confidence-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    color: #374151;
    font-weight: 600;
}

.confidence-percentage {
    font-size: 1.5rem;
    color: var(--primary);
}

.confidence-bar {
    height: 12px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    border-radius: 10px;
    transition: width 1s ease-out;
    box-shadow: 0 2px 8px rgba(85, 122, 70, 0.4);
}

.treatment-section {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    padding: 2rem;
    border-radius: 16px;
    border: 2px solid #bbf7d0;
}

.treatment-section h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #065f46;
    margin: 0 0 1.25rem 0;
    font-size: 1.3rem;
}

.treatment-section h3 .material-icons {
    font-size: 1.8rem;
}

.treatment-text {
    color: #065f46;
    line-height: 1.8;
    white-space: pre-line;
    font-size: 1rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.action-btn {
    flex: 1;
    padding: 1rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.action-btn-primary {
    background: var(--primary);
    color: white;
}

.action-btn-primary:hover {
    background: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(85, 122, 70, 0.3);
}

.action-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.action-btn-secondary:hover {
    background: #e5e7eb;
}

.camera-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

.camera-modal.active {
    display: flex;
}

.camera-content {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
}

.camera-content h3 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 1.5rem 0;
    color: #111827;
    font-size: 1.5rem;
}

.camera-video-container {
    position: relative;
    background: #000;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.camera-video {
    width: 100%;
    height: auto;
    display: block;
}

.camera-canvas {
    display: none;
}

.camera-controls {
    display: flex;
    gap: 1rem;
}

.camera-btn {
    flex: 1;
    padding: 1.25rem;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.05rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.camera-btn-capture {
    background: var(--primary);
    color: white;
}

.camera-btn-capture:hover {
    background: var(--secondary);
    transform: translateY(-2px);
}

.camera-btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.camera-btn-cancel:hover {
    background: #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.empty-state .material-icons {
    font-size: 6rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #374151;
    margin: 0 0 1rem 0;
}

.empty-state p {
    color: #6b7280;
    margin: 0 0 2rem 0;
}

@media (max-width: 1024px) {
    .disease-main-container {
        grid-template-columns: 1fr;
    }
    
    .info-sidebar {
        order: -1;
    }
}

@media (max-width: 640px) {
    .disease-hero-section h1 {
        font-size: 1.8rem;
    }
    
    .capture-options {
        grid-template-columns: 1fr;
    }
    
    .result-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="disease-detection-page">
    <div class="disease-hero-section">
        <h1>
            <span class="material-icons" style="font-size: 3rem;">bug_report</span>
            <?php echo __('disease_detection'); ?>
        </h1>
        <p><?php echo __('ai_powered_plant_disease_detection'); ?></p>
    </div>

    <div class="disease-main-container">
        <div class="disease-upload-card">
            <form id="diseaseDetectionForm">
                <?php if ($crops && count($crops) > 0): ?>
                <div class="crop-selector">
                    <label>
                        <span class="material-icons">agriculture</span>
                        <?php echo __('select_crop'); ?> (<?php echo __('optional'); ?>)
                    </label>
                    <select id="cropSelect" name="cropId">
                        <option value=""><?php echo __('no_crop_selected'); ?></option>
                        <?php foreach ($crops as $crop): ?>
                            <option value="<?php echo $crop['crop_id']; ?>">
                                <?php echo htmlspecialchars($crop['crop_name']); ?> 
                                - <?php echo __('planted'); ?>: <?php echo date('M d, Y', strtotime($crop['planting_date'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div id="uploadZone" class="upload-zone">
                    <span class="material-icons upload-icon">cloud_upload</span>
                    <h3><?php echo __('drag_drop_or_click'); ?></h3>
                    <p><?php echo __('supports_jpg_png_max_5mb'); ?></p>
                </div>

                <div id="imagePreview" class="image-preview-section">
                    <div class="preview-image-container">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="remove-preview-btn" onclick="removeImage()">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                </div>

                <div class="capture-options" id="captureOptions">
                    <button type="button" class="capture-option-btn" onclick="openCamera()">
                        <span class="material-icons">photo_camera</span>
                        <span><?php echo __('use_camera'); ?></span>
                    </button>
                    <button type="button" class="capture-option-btn" onclick="selectFile()">
                        <span class="material-icons">photo_library</span>
                        <span><?php echo __('choose_from_gallery'); ?></span>
                    </button>
                </div>

                <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">

                <button type="submit" class="analyze-btn" id="analyzeButton" disabled>
                    <span class="material-icons">analytics</span>
                    <?php echo __('analyze_now'); ?>
                </button>
            </form>

            <div id="loadingOverlay" class="loading-overlay">
                <span class="material-icons spinner">sync</span>
                <h3><?php echo __('analyzing_image'); ?></h3>
                <p><?php echo __('ai_is_detecting_diseases'); ?></p>
            </div>

            <div id="resultsSection" class="results-section">
                <!-- Results will be dynamically inserted here -->
            </div>
        </div>

        <div class="info-sidebar">
            <div class="info-card">
                <h3>
                    <span class="material-icons">help_outline</span>
                    <?php echo __('how_it_works'); ?>
                </h3>
                <ol class="instruction-steps">
                    <li><?php echo __('select_your_crop_from_list'); ?></li>
                    <li><?php echo __('take_clear_photo_affected_area'); ?></li>
                    <li><?php echo __('ai_analyzes_and_identifies'); ?></li>
                    <li><?php echo __('get_treatment_recommendations'); ?></li>
                </ol>
            </div>

            <div class="info-card tips-card">
                <h3>
                    <span class="material-icons">lightbulb</span>
                    <?php echo __('pro_tips'); ?>
                </h3>
                <ul class="tips-list">
                    <li><?php echo __('use_natural_daylight'); ?></li>
                    <li><?php echo __('focus_on_damaged_leaves'); ?></li>
                    <li><?php echo __('avoid_blurry_images'); ?></li>
                    <li><?php echo __('capture_multiple_symptoms'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Camera Modal -->
    <div id="cameraModal" class="camera-modal">
        <div class="camera-content">
            <h3>
                <span class="material-icons">photo_camera</span>
                <?php echo __('capture_plant_image'); ?>
            </h3>
            <div class="camera-video-container">
                <video id="cameraVideo" class="camera-video" autoplay playsinline></video>
                <canvas id="cameraCanvas" class="camera-canvas"></canvas>
            </div>
            <div class="camera-controls">
                <button type="button" class="camera-btn camera-btn-capture" onclick="captureImage()">
                    <span class="material-icons">camera</span>
                    <?php echo __('capture'); ?>
                </button>
                <button type="button" class="camera-btn camera-btn-cancel" onclick="closeCamera()">
                    <span class="material-icons">close</span>
                    <?php echo __('cancel'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedFile = null;
let cameraStream = null;
const baseUrl = '<?php echo $base_url; ?>';

// Drag and drop functionality
const uploadZone = document.getElementById('uploadZone');

if (uploadZone) {
    uploadZone.addEventListener('click', () => selectFile());
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('drag-over');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });
}

function selectFile() {
    document.getElementById('fileInput').click();
}

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        handleFile(file);
    }
}

function handleFile(file) {
    if (!file.type.match('image.*')) {
        alert('<?php echo __('please_select_valid_image'); ?>');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('<?php echo __('file_too_large_max_5mb'); ?>');
        return;
    }
    
    selectedFile = file;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('imagePreview').classList.add('active');
        document.getElementById('uploadZone').style.display = 'none';
        document.getElementById('captureOptions').style.display = 'none';
        document.getElementById('analyzeButton').disabled = false;
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    selectedFile = null;
    document.getElementById('previewImg').src = '';
    document.getElementById('imagePreview').classList.remove('active');
    document.getElementById('uploadZone').style.display = 'block';
    document.getElementById('captureOptions').style.display = 'grid';
    document.getElementById('fileInput').value = '';
    document.getElementById('analyzeButton').disabled = true;
    document.getElementById('resultsSection').classList.remove('active');
}

async function openCamera() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: false
        });
        
        video.srcObject = cameraStream;
        modal.classList.add('active');
    } catch (error) {
        console.error('Camera error:', error);
        alert('<?php echo __('camera_access_denied'); ?>');
    }
}

function closeCamera() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    
    video.srcObject = null;
    modal.classList.remove('active');
}

function captureImage() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    canvas.toBlob(function(blob) {
        selectedFile = new File([blob], 'camera-capture.jpg', { type: 'image/jpeg' });
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').classList.add('active');
            document.getElementById('uploadZone').style.display = 'none';
            document.getElementById('captureOptions').style.display = 'none';
            document.getElementById('analyzeButton').disabled = false;
        };
        reader.readAsDataURL(selectedFile);
        
        closeCamera();
    }, 'image/jpeg', 0.95);
}

// Form submission
document.getElementById('diseaseDetectionForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const cropSelect = document.getElementById('cropSelect');
    const cropId = cropSelect ? cropSelect.value : '';
    
    if (!selectedFile) {
        alert('<?php echo __('please_upload_or_capture_image'); ?>');
        return;
    }
    
    // Show loading
    document.getElementById('loadingOverlay').classList.add('active');
    document.getElementById('resultsSection').classList.remove('active');
    document.getElementById('analyzeButton').disabled = true;
    
    const formData = new FormData();
    formData.append('image', selectedFile);
    formData.append('cropId', cropId);
    
    try {
        const response = await fetch(baseUrl + 'api/disease/analyze.php', {
            method: 'POST',
            body: formData
        });
        
        // Get the raw response text first
        const responseText = await response.text();
        console.log('Raw API response:', responseText);
        
        if (!response.ok) {
            throw new Error('Network response was not ok (Status: ' + response.status + ')');
        }
        
        // Try to parse JSON
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Invalid response format. Check console for details.');
        }
        
        document.getElementById('loadingOverlay').classList.remove('active');
        
        if (result.success) {
            displayResults(result.data);
        } else {
            let errorMsg = result.message || '<?php echo __('unknown_error'); ?>';
            
            // Add debug info if available
            if (result.debug) {
                console.error('Backend error details:', result.debug);
                errorMsg += '\n\nDebug: ' + JSON.stringify(result.debug, null, 2);
            }
            
            alert('<?php echo __('analysis_failed'); ?>: ' + errorMsg);
            document.getElementById('analyzeButton').disabled = false;
        }
    } catch (error) {
        console.error('Analysis error:', error);
        console.error('Error details:', error.message, error.stack);
        document.getElementById('loadingOverlay').classList.remove('active');
        
        // Show more detailed error if available
        let errorMessage = '<?php echo __('error_analyzing_image'); ?>';
        if (error.message) {
            errorMessage += '\n\nDetails: ' + error.message;
        }
        
        alert(errorMessage);
        document.getElementById('analyzeButton').disabled = false;
    }
});

function displayResults(data) {
    const severityIcons = {
        'high': 'error',
        'medium': 'warning',
        'low': 'check_circle'
    };
    
    const severityLabels = {
        'high': '<?php echo __('high_risk'); ?>',
        'medium': '<?php echo __('medium_risk'); ?>',
        'low': '<?php echo __('low_risk'); ?>'
    };
    
    const confidencePercent = Math.round(data.confidence * 100);
    
    const resultsHTML = `
        <div class="severity-${data.severity}">
            <div class="result-header">
                <div class="result-icon-container">
                    <span class="material-icons">${severityIcons[data.severity]}</span>
                </div>
                <div class="result-info">
                    <h2>${data.disease}</h2>
                    <span class="severity-badge">
                        <span class="material-icons" style="font-size: 1.2rem;">${severityIcons[data.severity]}</span>
                        ${severityLabels[data.severity]}
                    </span>
                </div>
            </div>
            
            <div class="confidence-section">
                <div class="confidence-label">
                    <span><?php echo __('confidence_level'); ?></span>
                    <span class="confidence-percentage">${confidencePercent}%</span>
                </div>
                <div class="confidence-bar">
                    <div class="confidence-fill" style="width: ${confidencePercent}%"></div>
                </div>
            </div>
            
            <div class="treatment-section">
                <h3>
                    <span class="material-icons">medical_services</span>
                    <?php echo __('recommended_treatment'); ?>
                </h3>
                <div class="treatment-text">${data.treatment}</div>
            </div>
            
            <div class="action-buttons">
                <button type="button" class="action-btn action-btn-primary" onclick="analyzeAnother()">
                    <span class="material-icons">refresh</span>
                    <?php echo __('analyze_another'); ?>
                </button>
                <button type="button" class="action-btn action-btn-secondary" onclick="window.print()">
                    <span class="material-icons">print</span>
                    <?php echo __('print_results'); ?>
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('resultsSection').innerHTML = resultsHTML;
    document.getElementById('resultsSection').classList.add('active');
    document.getElementById('analyzeButton').disabled = false;
    
    // Smooth scroll to results
    setTimeout(() => {
        document.getElementById('resultsSection').scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }, 100);
}

function analyzeAnother() {
    removeImage();
    document.getElementById('resultsSection').classList.remove('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
