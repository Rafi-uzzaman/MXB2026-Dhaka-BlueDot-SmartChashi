<?php
include __DIR__ . '/../layouts/header.php';

if (!isLoggedIn()) {
    redirect('login');
}

$user = getCurrentUser();
$userRole = $user['role'];
$db = new Database();

// Get active tab
$activeTab = $_GET['tab'] ?? 'posts';
?>

<section class="hero">
    <h1><?php echo __('farmer_community'); ?></h1>
    <p><?php echo $userRole === 'officer' ? __('monitor_engage_community') : __('share_experiences'); ?></p>
</section>

<!-- Community Tabs Navigation -->
<div class="community-tabs">
    <button class="tab-btn <?php echo $activeTab === 'posts' ? 'active' : ''; ?>" data-tab="posts">
        <span class="material-icons">forum</span>
        <span><?php echo __('community_posts_tab'); ?></span>
    </button>
    <?php if ($userRole === 'farmer'): ?>
    <button class="tab-btn <?php echo $activeTab === 'farmers' ? 'active' : ''; ?>" data-tab="farmers">
        <span class="material-icons">agriculture</span>
        <span><?php echo __('nearby_farmers_tab'); ?></span>
    </button>
    <button class="tab-btn <?php echo $activeTab === 'officers' ? 'active' : ''; ?>" data-tab="officers">
        <span class="material-icons">supervised_user_circle</span>
        <span><?php echo __('officer_network_tab'); ?></span>
    </button>
    <?php elseif ($userRole === 'officer'): ?>
    <button class="tab-btn <?php echo $activeTab === 'farmers' ? 'active' : ''; ?>" data-tab="farmers">
        <span class="material-icons">agriculture</span>
        <span><?php echo __('farmers_in_my_region_tab'); ?></span>
    </button>
    <button class="tab-btn <?php echo $activeTab === 'analytics' ? 'active' : ''; ?>" data-tab="analytics">
        <span class="material-icons">analytics</span>
        <span><?php echo __('community_analytics_tab'); ?></span>
    </button>
    <?php endif; ?>
    <button class="tab-btn <?php echo $activeTab === 'bookmarks' ? 'active' : ''; ?>" data-tab="bookmarks">
        <span class="material-icons">bookmarks</span>
        <span><?php echo __('my_bookmarks_tab'); ?></span>
    </button>
</div>

<!-- Community Posts Tab -->
<div class="tab-content" id="posts-tab" style="display: <?php echo $activeTab === 'posts' ? 'block' : 'none'; ?>;">
    <div class="community-grid">
        <div class="community-form-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons" style="vertical-align: middle;">
                            <?php echo $userRole === 'officer' ? 'campaign' : 'post_add'; ?>
                        </span>
                        <?php echo $userRole === 'officer' ? __('post_official_advisory') : __('create_new_post'); ?>
                    </h3>
                </div>

                <form id="postForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_post">
                    <div class="form-group">
                        <label for="title"><?php echo __('title'); ?> *</label>
                        <input type="text" id="title" name="title" placeholder="<?php echo __('whats_on_mind'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category"><?php echo __('category'); ?></label>
                        <select id="category" name="category">
                            <option value="General Discussion"><?php echo __('general_discussion'); ?></option>
                            <option value="Crop Problems"><?php echo __('crop_problems'); ?></option>
                            <option value="Best Practices"><?php echo __('best_practices'); ?></option>
                            <option value="Market Updates"><?php echo __('market_updates'); ?></option>
                            <option value="Weather Discussion"><?php echo __('weather_discussion'); ?></option>
                            <option value="Pest Control"><?php echo __('pest_control'); ?></option>
                            <option value="Fertilizer Tips"><?php echo __('fertilizer_tips'); ?></option>
                            <?php if ($userRole === 'officer'): ?>
                            <option value="Official Advisory"><?php echo __('official_advisory'); ?></option>
                            <option value="Government Updates"><?php echo __('government_updates'); ?></option>
                            <option value="Training Programs"><?php echo __('training_programs'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="content"><?php echo __('content'); ?> *</label>
                        <textarea id="content" name="content" placeholder="<?php echo __('share_experience'); ?>" required rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="postPhoto"><?php echo __('add_photo_optional'); ?></label>
                        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 0.5rem;"><?php echo __('jpg_png_max_5mb'); ?></p>
                        <input type="file" id="postPhoto" name="postPhoto" accept="image/*" style="cursor: pointer;">
                        <small class="form-text"><?php echo __('photo_engagement_tip'); ?></small>
                        <div id="imagePreview" style="margin-top: 10px; display: none;">
                            <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                            <button type="button" onclick="clearImagePreview()" class="btn btn-small btn-secondary" style="margin-top: 5px;"><?php echo __('remove_image'); ?></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-block" id="submitPostBtn">
                            <span class="material-icons">send</span> <?php echo __('post'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="community-stats-section">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons" style="vertical-align: middle;">analytics</span>
                        <?php echo __('community_stats'); ?>
                    </h3>
                </div>

                <div class="stats-list" id="communityStats">
                    <p><span class="material-icons">people</span><strong><?php echo __('total_farmers'); ?>:</strong> <span id="totalFarmers" class="stat-value"><?php echo __('loading'); ?></span></p>
                    <p><span class="material-icons">article</span><strong><?php echo __('posts_today'); ?>:</strong> <span id="postsToday" class="stat-value"><?php echo __('loading'); ?></span></p>
                    <p><span class="material-icons">forum</span><strong><?php echo __('active_discussions'); ?>:</strong> <span id="activeDiscussions" class="stat-value"><?php echo __('loading'); ?></span></p>
                </div>
                <p class="popular-topics-title"><span class="material-icons">trending_up</span><strong><?php echo __('popular_topics'); ?>:</strong></p>
                <ul class="popular-topics-list" id="trendingTopics">
                    <li><span class="material-icons">hourglass_empty</span>Loading topics...</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filter and Sort Controls -->
    <div class="posts-controls" style="margin: 2rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
        <div class="filter-group" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1; min-width: 250px;">
                <span class="material-icons">search</span>
                <input type="text" id="searchInput" class="form-control" placeholder="<?php echo __('search_posts'); ?>" style="flex: 1;">
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="categoryFilter"><span class="material-icons">filter_list</span> <?php echo __('category'); ?>:</label>
                <select id="categoryFilter" class="filter-select">
                    <option value="all"><?php echo __('all_categories'); ?></option>
                    <option value="General Discussion"><?php echo __('general_discussion'); ?></option>
                    <option value="Crop Problems"><?php echo __('crop_problems'); ?></option>
                    <option value="Best Practices"><?php echo __('best_practices'); ?></option>
                    <option value="Market Updates"><?php echo __('market_updates'); ?></option>
                    <option value="Weather Discussion"><?php echo __('weather_discussion'); ?></option>
                    <option value="Pest Control"><?php echo __('pest_control'); ?></option>
                    <option value="Fertilizer Tips"><?php echo __('fertilizer_tips'); ?></option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label for="sortBy"><span class="material-icons">sort</span> <?php echo __('sort_by'); ?>:</label>
                <select id="sortBy" class="filter-select">
                    <option value="newest"><?php echo __('newest_first'); ?></option>
                    <option value="oldest"><?php echo __('oldest_first'); ?></option>
                    <option value="popular"><?php echo __('most_popular'); ?></option>
                    <option value="most_discussed"><?php echo __('most_discussed'); ?></option>
                </select>
            </div>

            <button id="refreshPosts" class="btn btn-small" style="margin-left: auto;">
                <span class="material-icons">refresh</span> <?php echo __('refresh'); ?>
            </button>
        </div>
    </div>

    <h2 class="section-title">
        <span class="material-icons" style="vertical-align: middle;">forum</span>
        <?php echo __('recent_posts'); ?>
    </h2>

    <!-- Loading indicator -->
    <div id="postsLoader" class="loader" style="display: none; text-align: center; padding: 20px;">
        <span class="material-icons rotating" style="animation: spin 1s linear infinite;">refresh</span> <?php echo __('loading_posts'); ?>
    </div>

    <!-- Posts container -->
    <div class="posts-container" id="postsContainer">
        <!-- Posts will be loaded dynamically -->
    </div>

    <!-- Load More Button -->
    <div style="text-align: center; margin-top: 2rem;">
        <div id="noMorePosts" style="display: none; padding: 20px; color: #666;">
            <span class="material-icons">check_circle</span> <?php echo __('youve_reached_end'); ?>
        </div>
    </div>
    
    <!-- Pagination Controls -->
    <div id="paginationContainer" class="pagination-wrapper">
        <div class="pagination">
            <button id="firstPageBtn" class="pagination-btn" title="First Page">
                <span class="material-icons">first_page</span>
            </button>
            <button id="prevPageBtn" class="pagination-btn" title="Previous Page">
                <span class="material-icons">chevron_left</span>
            </button>
            <div id="pageNumbers" class="page-numbers">
                <!-- Page numbers will be generated here -->
            </div>
            <button id="nextPageBtn" class="pagination-btn" title="Next Page">
                <span class="material-icons">chevron_right</span>
            </button>
            <button id="lastPageBtn" class="pagination-btn" title="Last Page">
                <span class="material-icons">last_page</span>
            </button>
        </div>
        <div class="pagination-info">
            <span id="pageInfo"><?php echo __('page'); ?> 1 <?php echo __('of'); ?> 1</span>
            <span class="pagination-separator">•</span>
            <span id="resultsInfo"><?php echo __('showing'); ?> 0 <?php echo __('posts'); ?></span>
        </div>
    </div>
</div>

<!-- Nearby Farmers Tab -->
<div class="tab-content" id="farmers-tab" style="display: <?php echo $activeTab === 'farmers' ? 'block' : 'none'; ?>;">
    <h2 class="section-title">
        <span class="material-icons" style="vertical-align: middle;">agriculture</span>
        <?php echo __('nearby_farmers'); ?>
    </h2>

    <!-- Search Form -->
    <div class="search-section" style="margin: 2rem 0;">
        <form id="farmersSearchForm" class="search-form">
            <div class="search-input-group" style="display: flex; gap: 0.5rem; align-items: center;">
                <span class="material-icons search-icon">search</span>
                <input type="text" id="farmersSearch" name="search" placeholder="<?php echo __('search_by_name_location'); ?>" class="search-input" style="flex: 1;">
                <button type="submit" class="btn btn-small">
                    <span class="material-icons">search</span>
                </button>
                <button type="button" id="clearFarmersSearch" class="btn btn-small btn-secondary" style="display: none;"><?php echo __('clear'); ?></button>
            </div>
        </form>
    </div>

    <!-- Distance Filter -->
    <div style="margin: 1rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
        <label for="distanceFilter"><span class="material-icons">straighten</span> <?php echo __('distance'); ?>: <span id="distanceValue">50</span> <?php echo __('km'); ?></label>
        <input type="range" id="distanceFilter" min="10" max="100" value="50" step="10" style="width: 100%; margin-top: 0.5rem;">
    </div>

    <!-- Loading indicator -->
    <div id="farmersLoader" class="loader" style="display: none; text-align: center; padding: 20px;">
        <span class="material-icons rotating" style="animation: spin 1s linear infinite;">refresh</span> <?php echo __('loading_farmers'); ?>
    </div>

    <!-- Farmers container -->
    <div class="farmers-grid" id="farmersContainer">
        <!-- Farmers will be loaded dynamically -->
    </div>

    <div id="noFarmersFound" style="display: none;" class="notice notice-info">
        <p><?php echo __('no_nearby_farmers'); ?></p>
    </div>
</div>

<!-- Officer Network Tab -->
<div class="tab-content" id="officers-tab" style="display: <?php echo $activeTab === 'officers' ? 'block' : 'none'; ?>;">
    <h2 class="section-title">
        <span class="material-icons" style="vertical-align: middle;">supervised_user_circle</span>
        <?php echo __('officer_network'); ?>
    </h2>

    <!-- Search Form -->
    <div class="search-section" style="margin: 2rem 0;">
        <form id="officersSearchForm" class="search-form">
            <div class="search-input-group" style="display: flex; gap: 0.5rem; align-items: center;">
                <span class="material-icons search-icon">search</span>
                <input type="text" id="officersSearch" name="search" placeholder="<?php echo __('search_by_name_location'); ?>" class="search-input" style="flex: 1;">
                <button type="submit" class="btn btn-small">
                    <span class="material-icons">search</span>
                </button>
                <button type="button" id="clearOfficersSearch" class="btn btn-small btn-secondary" style="display: none;"><?php echo __('clear'); ?></button>
            </div>
        </form>
    </div>

    <!-- Loading indicator -->
    <div id="officersLoader" class="loader" style="display: none; text-align: center; padding: 20px;">
        <span class="material-icons rotating" style="animation: spin 1s linear infinite;">refresh</span> <?php echo __('loading_officers'); ?>
    </div>

    <!-- Officers container -->
    <div class="officers-grid" id="officersContainer">
        <!-- Officers will be loaded dynamically -->
    </div>

    <div id="noOfficersFound" style="display: none;" class="notice notice-info">
        <p><?php echo __('no_officers_found'); ?></p>
    </div>
</div>

<!-- Bookmarks Tab -->
<div class="tab-content" id="bookmarks-tab" style="display: <?php echo $activeTab === 'bookmarks' ? 'block' : 'none'; ?>;">
    <h2 class="section-title">
        <span class="material-icons" style="vertical-align: middle;">bookmarks</span>
        <?php echo __('my_bookmarked_posts'); ?>
    </h2>

    <!-- Loading indicator -->
    <div id="bookmarksLoader" class="loader" style="display: none; text-align: center; padding: 20px;">
        <span class="material-icons rotating" style="animation: spin 1s linear infinite;">refresh</span> <?php echo __('loading_bookmarks'); ?>
    </div>

    <!-- Bookmarks container -->
    <div class="posts-container" id="bookmarksContainer">
        <!-- Bookmarks will be loaded dynamically -->
    </div>

    <div id="noBookmarks" style="display: none;" class="notice notice-info">
        <p><?php echo __('no_bookmarks_yet'); ?></p>
    </div>
</div>

<!-- Post Detail Modal -->
<div id="postModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <span class="close-modal" onclick="closePostModal()">&times;</span>
        <div id="postModalContent">
            <!-- Post detail will be loaded here -->
        </div>
    </div>
</div>

<!-- Edit Post Modal -->
<div id="editPostModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close-modal" onclick="closeEditPostModal()">&times;</span>
        <h2><span class="material-icons">edit</span> <?php echo __('edit_post'); ?></h2>
        <form id="editPostForm">
            <input type="hidden" id="editPostId" name="postId">
            <div class="form-group">
                <label for="editTitle"><?php echo __('title'); ?> *</label>
                <input type="text" id="editTitle" name="title" required>
            </div>
            <div class="form-group">
                <label for="editCategory"><?php echo __('category'); ?></label>
                <select id="editCategory" name="category">
                    <option value="General Discussion"><?php echo __('general_discussion'); ?></option>
                    <option value="Crop Problems"><?php echo __('crop_problems'); ?></option>
                    <option value="Best Practices"><?php echo __('best_practices'); ?></option>
                    <option value="Market Updates"><?php echo __('market_updates'); ?></option>
                    <option value="Weather Discussion"><?php echo __('weather_discussion'); ?></option>
                    <option value="Pest Control"><?php echo __('pest_control'); ?></option>
                    <option value="Fertilizer Tips"><?php echo __('fertilizer_tips'); ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="editContent"><?php echo __('content'); ?> *</label>
                <textarea id="editContent" name="content" required rows="6"></textarea>
            </div>
            <div class="form-group" style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn"><?php echo __('save_changes'); ?></button>
                <button type="button" class="btn btn-secondary" onclick="closeEditPostModal()"><?php echo __('cancel'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close-modal" onclick="closeShareModal()">&times;</span>
        <h2><span class="material-icons">share</span> <?php echo __('share_post'); ?></h2>
        <div id="shareOptions" style="padding: 1rem 0;">
            <p><?php echo __('share_this_post'); ?></p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1rem;">
                <button class="btn btn-small" onclick="shareToWhatsApp()">
                    <span class="material-icons">whatsapp</span> <?php echo __('whatsapp'); ?>
                </button>
                <button class="btn btn-small" onclick="shareToFacebook()">
                    <span class="material-icons">facebook</span> <?php echo __('facebook'); ?>
                </button>
                <button class="btn btn-small" onclick="copyShareLink()">
                    <span class="material-icons">link</span> <?php echo __('copy_link'); ?>
                </button>
            </div>
            <div style="margin-top: 1rem;">
                <label><?php echo __('share_url'); ?>:</label>
                <input type="text" id="shareUrl" readonly style="width: 100%; padding: 0.5rem; border-radius: 4px; border: 1px solid #ddd;">
            </div>
        </div>
    </div>
</div>

<style>
.rotating {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.tab-content {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.filter-select {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    cursor: pointer;
}

.stat-value {
    color: #4caf50;
    font-weight: bold;
}

.post-card {
    transition: transform 0.2s, box-shadow 0.2s;
    margin-bottom: 1rem;
    position: relative;
}

.post-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s;
}

.modal-content {
    background-color: #fefefe;
    margin: auto;
    padding: 25px;
    border: 1px solid #888;
    border-radius: 12px;
    width: 90%;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.close-modal {
    color: #aaa;
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.close-modal:hover,
.close-modal:focus {
    color: #000;
}

.like-btn {
    transition: all 0.3s;
}

.like-btn.liked .material-icons {
    color: #e91e63;
}

.bookmark-btn.bookmarked .material-icons {
    color: #ffc107;
}

.helpful-btn, .unhelpful-btn {
    transition: all 0.3s;
}

.helpful-btn.voted .material-icons {
    color: #4caf50;
}

.unhelpful-btn.voted .material-icons {
    color: #f44336;
}

.post-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.post-actions button {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.comment-section {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.comment-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.comment-author {
    font-weight: bold;
    color: #333;
}

.comment-date {
    font-size: 0.85rem;
    color: #666;
}

.comment-content {
    margin: 0.5rem 0;
    color: #444;
}

.comment-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
    align-items: center;
}

.btn-danger {
    background-color: #f44336;
    color: white;
}

.btn-danger:hover {
    background-color: #d32f2f;
}

.pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    margin: 2rem 0;
    padding: 1.5rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pagination {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.pagination-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0.5rem;
    border: 2px solid #e0e0e0;
    background: #fff;
    color: #333;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.pagination-btn:hover:not(:disabled) {
    background: #557A46;
    color: white;
    border-color: #557A46;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(85, 122, 70, 0.3);
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f5f5f5;
    border-color: #e0e0e0;
}

.page-numbers {
    display: flex;
    gap: 0.25rem;
    align-items: center;
}

.page-number {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0.5rem 0.75rem;
    border: 2px solid #e0e0e0;
    background: #fff;
    color: #333;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.95rem;
}

.page-number:hover {
    background: #f8f9fa;
    border-color: #557A46;
    transform: translateY(-1px);
}

.page-number.active {
    background: linear-gradient(135deg, #557A46 0%, #8FBC46 100%);
    color: white;
    border-color: #557A46;
    box-shadow: 0 4px 12px rgba(85, 122, 70, 0.4);
    font-weight: 600;
}

.page-ellipsis {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    color: #999;
    font-weight: bold;
}

.pagination-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #666;
    font-size: 0.9rem;
}

.pagination-separator {
    color: #ccc;
}

#pageInfo {
    font-weight: 600;
    color: #333;
}

#resultsInfo {
    color: #666;
}

@media (max-width: 768px) {
    .pagination {
        gap: 0.25rem;
    }
    
    .pagination-btn,
    .page-number {
        min-width: 36px;
        height: 36px;
        font-size: 0.85rem;
    }
    
    .pagination-btn span {
        font-size: 1.2rem;
    }
}

.reply-form {
    margin-top: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.nested-comment {
    margin-left: 2rem;
    border-left: 2px solid #e0e0e0;
    padding-left: 1rem;
}

.badge-pinned {
    background: #ff9800;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
}

.post-owner-badge {
    background: #2196f3;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}
</style>

<script>
// Pass base URL and user ID to JavaScript
window.communityBaseUrl = '<?php echo $base_url; ?>';
window.currentUserId = <?php echo $user['user_id']; ?>;
</script>
<script src="<?php echo $base_url; ?>public/js/community.js"></script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
