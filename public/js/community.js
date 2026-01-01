// Global variables
let currentPage = 1;
let isLoading = false;
let hasMore = true;
let currentCategory = 'all';
let currentSort = 'newest';
let currentTab = new URLSearchParams(window.location.search).get('tab') || 'posts';
let currentPostId = null;

// Base URL - will be set on DOMContentLoaded
let communityBase = '';

// Helper function for fetch with credentials
async function fetchWithCredentials(url, options = {}) {
    const defaultOptions = {
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            ...(options.headers || {})
        }
    };
    
    // Merge options, but don't override Content-Type for FormData
    const mergedOptions = { ...defaultOptions, ...options };
    if (options.body instanceof FormData) {
        // Let browser set Content-Type for FormData
        delete mergedOptions.headers['Content-Type'];
    }
    
    return fetch(url, mergedOptions);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set base URL from window.communityBaseUrl (set in community.php) or fallback to baseUrl from footer
    if (window.communityBaseUrl) {
        communityBase = window.communityBaseUrl;
    } else if (typeof baseUrl !== 'undefined' && baseUrl) {
        communityBase = baseUrl;
    } else {
        // Fallback: extract base URL from current page location
        const path = window.location.pathname;
        const basePath = path.substring(0, path.lastIndexOf('/') + 1).replace(/pages\/$/, '').replace(/public\/.*$/, '');
        communityBase = window.location.origin + basePath;
    }
    
    // Ensure trailing slash
    if (communityBase && !communityBase.endsWith('/')) {
        communityBase += '/';
    }
    
    
    initializeCommunity();
});

function initializeCommunity() {
    // Load initial content based on active tab
    if (currentTab === 'posts') {
        loadPosts();
        loadCommunityStats();
        loadTrendingTopics();
    } else if (currentTab === 'farmers') {
        loadNearbyFarmers();
    } else if (currentTab === 'officers') {
        loadOfficers();
    } else if (currentTab === 'bookmarks') {
        loadBookmarks();
    }

    setupEventListeners();
}

function setupEventListeners() {
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            switchTab(tab);
        });
    });

    // Post form submission
    const postForm = document.getElementById('postForm');
    if (postForm) {
        postForm.addEventListener('submit', handlePostSubmit);
    }

    // Edit post form
    const editPostForm = document.getElementById('editPostForm');
    if (editPostForm) {
        editPostForm.addEventListener('submit', handleEditPostSubmit);
    }

    // Image preview
    const photoInput = document.getElementById('postPhoto');
    if (photoInput) {
        photoInput.addEventListener('change', previewImage);
    }

    // Filter and sort
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            currentCategory = this.value;
            currentPage = 1;
            loadPosts(true);
        });
    }

    const sortBy = document.getElementById('sortBy');
    if (sortBy) {
        sortBy.addEventListener('change', function() {
            currentSort = this.value;
            currentPage = 1;
            loadPosts(true);
        });
    }

  

    // Refresh button
    const refreshBtn = document.getElementById('refreshPosts');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            currentPage = 1;
            loadPosts(true);
            loadCommunityStats();
            loadTrendingTopics();
        });
    }
    
    // Search input with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                loadPosts(true);
            }, 500);
        });
    }
    
    // Pagination button handlers
    const firstPageBtn = document.getElementById('firstPageBtn');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const lastPageBtn = document.getElementById('lastPageBtn');
    
    if (firstPageBtn) {
        firstPageBtn.addEventListener('click', () => {
            currentPage = 1;
            loadPosts(true);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadPosts(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
    
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', () => {
            if (window.totalPages && currentPage < window.totalPages) {
                currentPage++;
                loadPosts(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
    
    if (lastPageBtn) {
        lastPageBtn.addEventListener('click', () => {
            if (window.totalPages) {
                currentPage = window.totalPages;
                loadPosts(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // Farmers search
    const farmersSearchForm = document.getElementById('farmersSearchForm');
    if (farmersSearchForm) {
        farmersSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadNearbyFarmers();
        });
    }

    const clearFarmersSearch = document.getElementById('clearFarmersSearch');
    if (clearFarmersSearch) {
        clearFarmersSearch.addEventListener('click', function() {
            document.getElementById('farmersSearch').value = '';
            this.style.display = 'none';
            loadNearbyFarmers();
        });
    }

    const farmersSearch = document.getElementById('farmersSearch');
    if (farmersSearch) {
        farmersSearch.addEventListener('input', function() {
            const clearBtn = document.getElementById('clearFarmersSearch');
            if (clearBtn) {
                clearBtn.style.display = this.value ? 'block' : 'none';
            }
        });
    }

    // Distance filter
    const distanceFilter = document.getElementById('distanceFilter');
    if (distanceFilter) {
        distanceFilter.addEventListener('input', function() {
            const distanceValue = document.getElementById('distanceValue');
            if (distanceValue) {
                distanceValue.textContent = this.value;
            }
        });

        distanceFilter.addEventListener('change', function() {
            loadNearbyFarmers();
        });
    }

    // Officers search
    const officersSearchForm = document.getElementById('officersSearchForm');
    if (officersSearchForm) {
        officersSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loadOfficers();
        });
    }

    const clearOfficersSearch = document.getElementById('clearOfficersSearch');
    if (clearOfficersSearch) {
        clearOfficersSearch.addEventListener('click', function() {
            document.getElementById('officersSearch').value = '';
            this.style.display = 'none';
            loadOfficers();
        });
    }

    const officersSearch = document.getElementById('officersSearch');
    if (officersSearch) {
        officersSearch.addEventListener('input', function() {
            const clearBtn = document.getElementById('clearOfficersSearch');
            if (clearBtn) {
                clearBtn.style.display = this.value ? 'block' : 'none';
            }
        });
    }

    // Modal close on outside click
    window.addEventListener('click', function(e) {
        const postModal = document.getElementById('postModal');
        const editPostModal = document.getElementById('editPostModal');
        
        if (e.target === postModal) {
            closePostModal();
        }
        if (e.target === editPostModal) {
            closeEditPostModal();
        }
    });
}

function switchTab(tab) {
    currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tab) {
            btn.classList.add('active');
        }
    });

    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = 'none';
    });

    // Show active tab
    const activeTab = document.getElementById(tab + '-tab');
    if (activeTab) {
        activeTab.style.display = 'block';
    }

    // Load content for the tab
    if (tab === 'posts') {
        const postsContainer = document.getElementById('postsContainer');
        if (postsContainer && postsContainer.children.length === 0) {
            loadPosts();
            loadCommunityStats();
            loadTrendingTopics();
        }
    } else if (tab === 'farmers') {
        const farmersContainer = document.getElementById('farmersContainer');
        if (farmersContainer && farmersContainer.children.length === 0) {
            loadNearbyFarmers();
        }
    } else if (tab === 'officers') {
        const officersContainer = document.getElementById('officersContainer');
        if (officersContainer && officersContainer.children.length === 0) {
            loadOfficers();
        }
    } else if (tab === 'bookmarks') {
        loadBookmarks();
    }

    // Update URL
    history.pushState({tab: tab}, '', '?tab=' + tab);
}

function clearImagePreview() {
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('postPhoto').value = '';
}

function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

async function handlePostSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitPostBtn');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-icons rotating">refresh</span> Posting...';
    
    try {
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Post created successfully!', 'success');
            form.reset();
            document.getElementById('imagePreview').style.display = 'none';
            
            // Reload posts
            currentPage = 1;
            loadPosts(true);
            loadCommunityStats();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to create post. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<span class="material-icons">send</span> Post';
    }
}

async function handleEditPostSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'edit_post');
    
    try {
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Post updated successfully!', 'success');
            closeEditPostModal();
            
            // Reload posts
            currentPage = 1;
            loadPosts(true);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to update post. Please try again.', 'error');
    }
}

function openEditPostModal(postId, title, category, content) {
    document.getElementById('editPostId').value = postId;
    document.getElementById('editTitle').value = title;
    document.getElementById('editCategory').value = category;
    document.getElementById('editContent').value = content;
    document.getElementById('editPostModal').style.display = 'flex';
}

function closeEditPostModal() {
    document.getElementById('editPostModal').style.display = 'none';
}

async function loadPosts(reset = false) {
    if (isLoading) return;
    
    if (reset) {
        hasMore = true;
        const postsContainer = document.getElementById('postsContainer');
        if (postsContainer) {
            postsContainer.innerHTML = '';
        }
    }
    isLoading = true;
    const loader = document.getElementById('postsLoader');
    if (loader) loader.style.display = 'block';
    
    try {
        const searchInput = document.getElementById('searchInput');
        const searchQuery = searchInput ? searchInput.value.trim() : '';
        
        const params = new URLSearchParams({
            action: 'get_posts',
            page: currentPage,
            limit: 10,
            category: currentCategory,
            sortBy: currentSort,
            search: searchQuery
        });
        
        const fetchUrl = communityBase + 'ajax/community.php?' + params;
        console.log('Fetching posts from:', fetchUrl);
        console.log('communityBase:', communityBase);
        
        const response = await fetch(fetchUrl, {
            method: 'GET',
            credentials: 'include', // Include cookies for session
            headers: {
                'Accept': 'application/json'
            }
        });
        
        // Check if response is OK
        if (!response.ok) {
            console.error('HTTP Error:', response.status, response.statusText);
            console.error('Response URL:', response.url);
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        console.log('Response length:', text.length, 'First 200 chars:', text.substring(0, 200));
        
        // Try to parse JSON
        let data;
        try {
            if (!text || text.trim() === '') {
                console.error('Empty response from server. URL:', fetchUrl);
                console.error('Response headers:', [...response.headers.entries()]);
                throw new Error('Empty response from server - check if you are logged in');
            }
            data = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text.substring(0, 500));
            console.error('Fetch URL was:', fetchUrl);
            throw new Error('Invalid server response: ' + text.substring(0, 100));
        }
        
        // Check for error message from server
        if (!data.success && data.message) {
            console.error('Server returned error:', data.message);
            showNotification(data.message, 'error');
            return;
        }
        
        if (data.success && data.posts) {
            const totalPages = data.pagination ? data.pagination.totalPages : Math.ceil((data.total || 0) / 10);
            window.totalPages = totalPages;
            window.totalResults = data.total || 0;
            
            
            // Update pagination controls
            updatePaginationControls(currentPage, totalPages);
            
            // Hide/show pagination based on page count
            const paginationContainer = document.getElementById('paginationContainer');
            if (paginationContainer) {
                paginationContainer.style.display = totalPages > 1 ? 'flex' : 'none';
            }
            
            if (data.posts.length === 0) {
                hasMore = false;
                
                if (currentPage === 1) {
                    const postsContainer = document.getElementById('postsContainer');
                    if (postsContainer) {
                        postsContainer.innerHTML = '<div class="notice notice-info"><p>No posts found. Try different search terms or filters.</p></div>';
                    }
                }
            } else {
                renderPosts(data.posts, reset);
            }
            
            // Always hide "You've reached the end" message when using pagination
            const noMorePosts = document.getElementById('noMorePosts');
            if (noMorePosts) {
                noMorePosts.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        showNotification('Failed to load posts. Please try again.', 'error');
    } finally {
        if (loader) loader.style.display = 'none';
        isLoading = false;
    }
}

function renderPosts(posts, reset) {
    const container = document.getElementById('postsContainer');
    if (!container) return;
    
    if (reset) {
        container.innerHTML = '';
    }
    
    posts.forEach(post => {
        const postCard = createPostCard(post);
        container.appendChild(postCard);
    });
}

// Update pagination controls with page numbers
function updatePaginationControls(currentPageNum, totalPages) {
    const firstPageBtn = document.getElementById('firstPageBtn');
    const prevPageBtn = document.getElementById('prevPageBtn');
    const nextPageBtn = document.getElementById('nextPageBtn');
    const lastPageBtn = document.getElementById('lastPageBtn');
    const pageInfo = document.getElementById('pageInfo');
    const pageNumbersContainer = document.getElementById('pageNumbers');
    const resultsInfo = document.getElementById('resultsInfo');
    
    // Update page info
    if (pageInfo) {
        pageInfo.textContent = `Page ${currentPageNum} of ${totalPages || 1}`;
    }
    
    // Update results info
    if (resultsInfo && window.totalResults !== undefined) {
        const start = ((currentPageNum - 1) * 10) + 1;
        const end = Math.min(currentPageNum * 10, window.totalResults);
        resultsInfo.textContent = `Showing ${start}-${end} of ${window.totalResults} posts`;
    }
    
    // Enable/disable navigation buttons
    if (firstPageBtn) firstPageBtn.disabled = currentPageNum <= 1;
    if (prevPageBtn) prevPageBtn.disabled = currentPageNum <= 1;
    if (nextPageBtn) nextPageBtn.disabled = currentPageNum >= totalPages;
    if (lastPageBtn) lastPageBtn.disabled = currentPageNum >= totalPages;
    
    // Generate page numbers
    if (pageNumbersContainer) {
        pageNumbersContainer.innerHTML = '';
        
        if (totalPages <= 7) {
            // Show all pages if 7 or fewer
            for (let i = 1; i <= totalPages; i++) {
                pageNumbersContainer.appendChild(createPageButton(i, currentPageNum));
            }
        } else {
            // Show smart pagination with ellipsis
            // Always show first page
            pageNumbersContainer.appendChild(createPageButton(1, currentPageNum));
            
            if (currentPageNum > 3) {
                pageNumbersContainer.appendChild(createEllipsis());
            }
            
            // Show pages around current page
            const startPage = Math.max(2, currentPageNum - 1);
            const endPage = Math.min(totalPages - 1, currentPageNum + 1);
            
            for (let i = startPage; i <= endPage; i++) {
                pageNumbersContainer.appendChild(createPageButton(i, currentPageNum));
            }
            
            if (currentPageNum < totalPages - 2) {
                pageNumbersContainer.appendChild(createEllipsis());
            }
            
            // Always show last page
            pageNumbersContainer.appendChild(createPageButton(totalPages, currentPageNum));
        }
    }
}

// Create page number button
function createPageButton(pageNum, currentPageNum) {
    const button = document.createElement('button');
    button.className = 'page-number' + (pageNum === currentPageNum ? ' active' : '');
    button.textContent = pageNum;
    button.dataset.page = pageNum;
    button.addEventListener('click', function() {
        currentPage = pageNum;
        loadPosts(true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    return button;
}

// Create ellipsis
function createEllipsis() {
    const ellipsis = document.createElement('span');
    ellipsis.className = 'page-ellipsis';
    ellipsis.textContent = '...';
    return ellipsis;
}

function createPostCard(post) {
    const card = document.createElement('div');
    card.className = 'card post-card';
    card.dataset.postId = post.post_id;
    
    const likedClass = post.user_liked ? 'liked' : '';
    const likeIcon = post.user_liked ? 'favorite' : 'favorite_border';
    const bookmarkedClass = post.user_bookmarked ? 'bookmarked' : '';
    const bookmarkIcon = post.user_bookmarked ? 'bookmark' : 'bookmark_border';
    
    // Helpful/Unhelpful
    const helpfulVoted = post.user_helpfulness_vote === '1' ? 'voted' : '';
    const unhelpfulVoted = post.user_helpfulness_vote === '0' ? 'voted' : '';
    
    card.innerHTML = `
        <div class="card-header post-header">
            <div class="post-info">
                <h4 class="card-title" onclick="viewPostDetail(${post.post_id})" style="cursor: pointer;">
                    ${escapeHtml(post.title)}
                    ${post.is_pinned ? '<span class="badge-pinned">Pinned</span>' : ''}
                </h4>
                <small class="post-meta">
                    <span class="material-icons">person</span>
                    by ${escapeHtml(post.first_name + ' ' + (post.last_name || ''))} 
                    in <strong>${escapeHtml(post.category)}</strong>
                </small>
            </div>
            <span class="badge">${formatDate(post.created_at)}</span>
        </div>
        
        ${post.image_url ? `
            <div class="post-image-container" onclick="viewPostDetail(${post.post_id})" style="cursor: pointer;">
                <img src="${communityBase}${escapeHtml(post.image_url)}" alt="Post image" class="post-image" style="max-width: 100%; border-radius: 8px;">
            </div>
        ` : ''}
        
        <div class="card-content" onclick="viewPostDetail(${post.post_id})" style="cursor: pointer;">
            <p>${escapeHtml(post.content.substring(0, 200))}${post.content.length > 200 ? '...' : ''}</p>
        </div>
        
        <div class="card-footer post-actions">
            <button class="btn btn-small like-btn ${likedClass}" onclick="toggleLike(${post.post_id}, this)">
                <span class="material-icons">${likeIcon}</span> 
                <span class="like-count">${post.like_count || 0}</span>
            </button>
            
            <button class="btn btn-small" onclick="viewPostDetail(${post.post_id})">
                <span class="material-icons">comment</span> ${post.comment_count || 0}
            </button>
            
            <button class="btn btn-small helpful-btn ${helpfulVoted}" onclick="markHelpful(${post.post_id}, this)">
                <span class="material-icons">thumb_up</span> 
                <span class="helpful-count">${post.helpful_count || 0}</span>
            </button>
            
            <button class="btn btn-small unhelpful-btn ${unhelpfulVoted}" onclick="markUnhelpful(${post.post_id}, this)">
                <span class="material-icons">thumb_down</span> 
                <span class="unhelpful-count">${post.unhelpful_count || 0}</span>
            </button>
            
            <button class="btn btn-small bookmark-btn ${bookmarkedClass}" onclick="toggleBookmark(${post.post_id}, this)">
                <span class="material-icons">${bookmarkIcon}</span>
            </button>
        </div>
    `;
    
    return card;
}

async function toggleLike(postId, button) {
    const isLiked = button.classList.contains('liked');
    const action = isLiked ? 'unlike_post' : 'like_post';
    
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('postId', postId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const icon = button.querySelector('.material-icons');
            const countSpan = button.querySelector('.like-count');
            
            if (isLiked) {
                button.classList.remove('liked');
                icon.textContent = 'favorite_border';
            } else {
                button.classList.add('liked');
                icon.textContent = 'favorite';
            }
            
            if (countSpan) {
                countSpan.textContent = data.likeCount || 0;
            }
        }
    } catch (error) {
        console.error('Error toggling like:', error);
    }
}

async function markHelpful(postId, button) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_helpful');
        formData.append('postId', postId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const postCard = button.closest('.post-card');
            const helpfulBtn = postCard.querySelector('.helpful-btn');
            const unhelpfulBtn = postCard.querySelector('.unhelpful-btn');
            
            helpfulBtn.classList.add('voted');
            unhelpfulBtn.classList.remove('voted');
            
            helpfulBtn.querySelector('.helpful-count').textContent = data.helpfulCount || 0;
            unhelpfulBtn.querySelector('.unhelpful-count').textContent = data.unhelpfulCount || 0;
            
            showNotification('Marked as helpful!', 'success');
        }
    } catch (error) {
        console.error('Error marking helpful:', error);
    }
}

async function markUnhelpful(postId, button) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_unhelpful');
        formData.append('postId', postId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const postCard = button.closest('.post-card');
            const helpfulBtn = postCard.querySelector('.helpful-btn');
            const unhelpfulBtn = postCard.querySelector('.unhelpful-btn');
            
            unhelpfulBtn.classList.add('voted');
            helpfulBtn.classList.remove('voted');
            
            helpfulBtn.querySelector('.helpful-count').textContent = data.helpfulCount || 0;
            unhelpfulBtn.querySelector('.unhelpful-count').textContent = data.unhelpfulCount || 0;
            
            showNotification('Marked as not helpful', 'success');
        }
    } catch (error) {
        console.error('Error marking unhelpful:', error);
    }
}

async function toggleBookmark(postId, button) {
    try {
        const formData = new FormData();
        formData.append('action', 'bookmark_post');
        formData.append('postId', postId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const icon = button.querySelector('.material-icons');
            
            if (data.bookmarked) {
                button.classList.add('bookmarked');
                icon.textContent = 'bookmark';
                showNotification('Post bookmarked!', 'success');
            } else {
                button.classList.remove('bookmarked');
                icon.textContent = 'bookmark_border';
                showNotification('Bookmark removed', 'success');
            }
        }
    } catch (error) {
        console.error('Error toggling bookmark:', error);
    }
}

async function viewPostDetail(postId) {
    try {
        const response = await fetchWithCredentials(communityBase + `ajax/community.php?action=get_post&postId=${postId}`);
        const data = await response.json();
        
        if (data.success && data.post) {
            displayPostModal(data.post, data.comments || []);
        } else {
            showNotification('Failed to load post details', 'error');
        }
    } catch (error) {
        console.error('Error loading post:', error);
        showNotification('Failed to load post details', 'error');
    }
}

function displayPostModal(post, comments) {
    currentPostId = post.post_id;
    const modal = document.getElementById('postModal');
    const modalContent = document.getElementById('postModalContent');
    
    const likedClass = post.user_liked ? 'liked' : '';
    const likeIcon = post.user_liked ? 'favorite' : 'favorite_border';
    
    modalContent.innerHTML = `
        <div class="post-detail">
            <h2>${escapeHtml(post.title)}</h2>
            <div class="post-meta" style="margin-bottom: 1rem; color: #666;">
                <span><span class="material-icons" style="font-size: 1rem; vertical-align: middle;">person</span> ${escapeHtml(post.first_name + ' ' + (post.last_name || ''))}</span>
                <span style="margin: 0 1rem;">•</span>
                <span>${formatDate(post.created_at)}</span>
                <span style="margin: 0 1rem;">•</span>
                <span><strong>${escapeHtml(post.category)}</strong></span>
            </div>
            
            ${post.image_url ? `
                <div style="margin: 1rem 0;">
                    <img src="${communityBase}${escapeHtml(post.image_url)}" alt="Post image" style="max-width: 100%; border-radius: 8px;">
                </div>
            ` : ''}
            
            <div class="post-content" style="margin: 1.5rem 0; line-height: 1.6;">
                ${escapeHtml(post.content).replace(/\n/g, '<br>')}
            </div>
            
            <div class="post-actions" style="margin: 1rem 0;">
                <button class="btn btn-small like-btn ${likedClass}" onclick="toggleLike(${post.post_id}, this)">
                    <span class="material-icons">${likeIcon}</span> 
                    <span class="like-count">${post.like_count || 0}</span> Likes
                </button>
                <button class="btn btn-small" onclick="document.getElementById('commentInput').focus()">
                    <span class="material-icons">comment</span> ${post.comment_count || 0} Comments
                </button>
            </div>
            
            <div class="comment-section">
                <h3><span class="material-icons" style="vertical-align: middle;">comment</span> Comments</h3>
                
                <div class="reply-form" style="margin: 1rem 0;">
                    <textarea id="commentInput" placeholder="Write a comment..." rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                    <button class="btn btn-small" onclick="addComment(${post.post_id})" style="margin-top: 0.5rem;">
                        <span class="material-icons">send</span> Post Comment
                    </button>
                </div>
                
                <div id="commentsList">
                    ${comments.map(comment => createCommentHTML(comment)).join('')}
                </div>
                
                ${comments.length === 0 ? '<p style="text-align: center; color: #666; padding: 1rem;">No comments yet. Be the first to comment!</p>' : ''}
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
}

function createCommentHTML(comment) {
    const isOwner = window.currentUserId && comment.user_id == window.currentUserId;
    
    return `
        <div class="comment-item" data-comment-id="${comment.comment_id}">
            <div class="comment-header">
                <span class="comment-author">${escapeHtml(comment.first_name + ' ' + (comment.last_name || ''))}</span>
                <span class="comment-date">${formatDate(comment.created_at)}</span>
            </div>
            <div class="comment-content">${escapeHtml(comment.content)}</div>
            <div class="comment-actions">
                <button class="btn btn-small" onclick="likeComment(${comment.comment_id}, this)">
                    <span class="material-icons">thumb_up</span> ${comment.like_count || 0}
                </button>
                <button class="btn btn-small" onclick="replyToComment(${comment.comment_id})">
                    <span class="material-icons">reply</span> Reply
                </button>
                ${isOwner ? `
                    <button class="btn btn-small btn-danger" onclick="deleteComment(${comment.comment_id})" style="margin-left: auto;">
                        <span class="material-icons">delete</span> Delete
                    </button>
                ` : ''}
            </div>
        </div>
    `;
}

async function addComment(postId) {
    const commentInput = document.getElementById('commentInput');
    const content = commentInput.value.trim();
    
    if (!content) {
        showNotification('Please enter a comment', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('postId', postId);
        formData.append('content', content);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Comment added!', 'success');
            commentInput.value = '';
            
            // Reload post to show new comment
            viewPostDetail(postId);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error adding comment:', error);
        showNotification('Failed to add comment', 'error');
    }
}

async function likeComment(commentId, button) {
    try {
        const formData = new FormData();
        formData.append('action', 'like_comment');
        formData.append('commentId', commentId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.innerHTML = `<span class="material-icons">thumb_up</span> ${data.likeCount || 0}`;
        }
    } catch (error) {
        console.error('Error liking comment:', error);
    }
}

function closePostModal() {
    const modal = document.getElementById('postModal');
    modal.style.display = 'none';
    currentPostId = null;
}



async function loadCommunityStats() {
    try {
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php?action=get_community_stats');
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response for stats:', text);
            return;
        }
        
        if (data.success && data.stats) {
            const totalFarmers = document.getElementById('totalFarmers');
            const postsToday = document.getElementById('postsToday');
            const activeDiscussions = document.getElementById('activeDiscussions');
            
            if (totalFarmers) totalFarmers.textContent = data.stats.total_farmers || '0';
            if (postsToday) postsToday.textContent = data.stats.posts_today || '0';
            if (activeDiscussions) activeDiscussions.textContent = data.stats.active_discussions || '0';
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

async function loadTrendingTopics() {
    try {
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php?action=get_trending_topics');
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response for trending:', text);
            return;
        }
        
        if (data.success && data.topics) {
            const topicsList = document.getElementById('trendingTopics');
            if (topicsList) {
                topicsList.innerHTML = '';
                
                if (data.topics.length === 0) {
                    topicsList.innerHTML = '<li style="color: #666;">No trending topics yet</li>';
                } else {
                    data.topics.forEach(topic => {
                        const li = document.createElement('li');
                        li.innerHTML = `<span class="material-icons">chevron_right</span>${escapeHtml(topic.name)} (${topic.count})`;
                        li.style.cursor = 'pointer';
                        li.onclick = () => {
                            const categoryFilter = document.getElementById('categoryFilter');
                            if (categoryFilter) {
                                categoryFilter.value = topic.name;
                                currentCategory = topic.name;
                                currentPage = 1;
                                loadPosts(true);
                            }
                        };
                        topicsList.appendChild(li);
                    });
                }
            }
        }
    } catch (error) {
        console.error('Error loading trending topics:', error);
    }
}

async function loadNearbyFarmers() {
    const loader = document.getElementById('farmersLoader');
    const container = document.getElementById('farmersContainer');
    const noFarmers = document.getElementById('noFarmersFound');
    
    if (loader) loader.style.display = 'block';
    if (container) container.innerHTML = '';
    if (noFarmers) noFarmers.style.display = 'none';
    
    try {
        const searchInput = document.getElementById('farmersSearch');
        const distanceInput = document.getElementById('distanceFilter');
        
        const search = searchInput ? searchInput.value : '';
        const distance = distanceInput ? distanceInput.value : 50;
        
        const params = new URLSearchParams({
            action: 'get_nearby_farmers',
            search: search,
            distance: distance
        });
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php?' + params);
        const data = await response.json();
        
        if (data.success && data.farmers && data.farmers.length > 0) {
            data.farmers.forEach(farmer => {
                if (container) container.appendChild(createFarmerCard(farmer));
            });
        } else {
            if (noFarmers) noFarmers.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading farmers:', error);
        if (noFarmers) noFarmers.style.display = 'block';
    } finally {
        if (loader) loader.style.display = 'none';
    }
}

function createFarmerCard(farmer) {
    const card = document.createElement('div');
    card.className = 'card farmer-card';
    
    card.innerHTML = `
        <div class="farmer-header">
            <div class="farmer-info">
                <h4 class="farmer-name">${escapeHtml(farmer.first_name + ' ' + (farmer.last_name || ''))}</h4>
                <p class="farmer-location">
                    <span class="material-icons">location_on</span>
                    ${escapeHtml(farmer.region || farmer.district || 'Location not set')}
                </p>
                ${farmer.primary_crops ? `<p><span class="material-icons">agriculture</span> ${escapeHtml(farmer.primary_crops)}</p>` : ''}
            </div>
            <span class="distance-badge">${farmer.distance} km</span>
        </div>
        <div class="card-footer farmer-actions">
            <button class="btn btn-small" onclick="sendMessage(${farmer.user_id})">
                <span class="material-icons">message</span> Message
            </button>
            <button class="btn btn-small btn-secondary" onclick="viewProfile(${farmer.user_id})">
                <span class="material-icons">info</span> View Profile
            </button>
        </div>
    `;
    
    return card;
}

async function loadOfficers() {
    const loader = document.getElementById('officersLoader');
    const container = document.getElementById('officersContainer');
    const noOfficers = document.getElementById('noOfficersFound');
    
    if (loader) loader.style.display = 'block';
    if (container) container.innerHTML = '';
    if (noOfficers) noOfficers.style.display = 'none';
    
    try {
        const searchInput = document.getElementById('officersSearch');
        const search = searchInput ? searchInput.value : '';
        
        const params = new URLSearchParams({
            action: 'get_officers',
            search: search
        });
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php?' + params);
        const data = await response.json();
        
        if (data.success && data.officers && data.officers.length > 0) {
            data.officers.forEach(officer => {
                if (container) container.appendChild(createOfficerCard(officer));
            });
        } else {
            if (noOfficers) noOfficers.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading officers:', error);
        if (noOfficers) noOfficers.style.display = 'block';
    } finally {
        if (loader) loader.style.display = 'none';
    }
}

function createOfficerCard(officer) {
    const card = document.createElement('div');
    card.className = 'card officer-card';
    
    card.innerHTML = `
        <div class="officer-header">
            <div class="officer-info">
                <h4 class="officer-name">${escapeHtml(officer.first_name + ' ' + (officer.last_name || ''))}</h4>
                <p class="officer-title">
                    <span class="material-icons">work</span>
                    ${escapeHtml(officer.expertise_area || 'Agricultural Officer')}
                </p>
                <p class="officer-location">
                    <span class="material-icons">location_on</span>
                    ${escapeHtml(officer.region || officer.district || 'Location not specified')}
                </p>
            </div>
        </div>
        ${officer.phone ? `
            <div class="officer-contact">
                <p><span class="material-icons">phone</span> ${escapeHtml(officer.phone)}</p>
            </div>
        ` : ''}
        <div class="card-footer officer-actions">
            <button class="btn btn-small" onclick="contactOfficer(${officer.user_id})">
                <span class="material-icons">mail</span> Contact
            </button>
            <button class="btn btn-small btn-secondary" onclick="viewProfile(${officer.user_id})">
                <span class="material-icons">info</span> View Profile
            </button>
        </div>
    `;
    
    return card;
}

async function loadBookmarks() {
    const loader = document.getElementById('bookmarksLoader');
    const container = document.getElementById('bookmarksContainer');
    const noBookmarks = document.getElementById('noBookmarks');
    
    if (loader) loader.style.display = 'block';
    if (container) container.innerHTML = '';
    if (noBookmarks) noBookmarks.style.display = 'none';
    
    try {
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php?action=get_bookmarks');
        const data = await response.json();
        
        if (data.success && data.bookmarks && data.bookmarks.length > 0) {
            data.bookmarks.forEach(post => {
                if (container) {
                    const postCard = createPostCard(post);
                    container.appendChild(postCard);
                }
            });
        } else {
            if (noBookmarks) noBookmarks.style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading bookmarks:', error);
        if (noBookmarks) noBookmarks.style.display = 'block';
    } finally {
        if (loader) loader.style.display = 'none';
    }
}

function sendMessage(userId) {
    showNotification('Messaging feature coming soon!', 'info');
}

function contactOfficer(userId) {
    showNotification('Contact feature coming soon!', 'info');
}

function viewProfile(userId) {
    window.location.href = communityBase + 'farmer-profile-view?id=' + userId;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return 'Today';
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return diffDays + ' days ago';
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Reply to comment
function replyToComment(commentId) {
    const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!commentElement) return;
    
    // Check if reply form already exists
    let replyForm = commentElement.querySelector('.reply-form-inline');
    if (replyForm) {
        replyForm.remove();
        return;
    }
    
    // Create reply form
    replyForm = document.createElement('div');
    replyForm.className = 'reply-form-inline';
    replyForm.style.cssText = 'margin: 1rem 0 0 3rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;';
    
    replyForm.innerHTML = `
        <textarea class="reply-textarea" placeholder="Write your reply..." style="width: 100%; min-height: 80px; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
        <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem;">
            <button class="btn btn-small" onclick="submitReply(${commentId})">
                <span class="material-icons">send</span> Post Reply
            </button>
            <button class="btn btn-small btn-secondary" onclick="cancelReply(${commentId})">
                <span class="material-icons">close</span> Cancel
            </button>
        </div>
    `;
    
    commentElement.appendChild(replyForm);
    replyForm.querySelector('.reply-textarea').focus();
}

// Submit reply
async function submitReply(parentCommentId) {
    const commentElement = document.querySelector(`[data-comment-id="${parentCommentId}"]`);
    const replyForm = commentElement.querySelector('.reply-form-inline');
    const textarea = replyForm.querySelector('.reply-textarea');
    const content = textarea.value.trim();
    
    if (!content) {
        showNotification('Please write a reply', 'error');
        return;
    }
    
    if (!currentPostId) {
        showNotification('Error: Post ID not found', 'error');
        console.error('currentPostId is null or undefined');
        return;
    }
    
  
    
    try {
        const formData = new FormData();
        formData.append('action', 'add_comment');
        formData.append('postId', currentPostId);
        formData.append('content', content);
        formData.append('parentId', parentCommentId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Raw response:', text);
            showNotification('Server returned invalid response: ' + text.substring(0, 100), 'error');
            return;
        }
        
        
        if (data.success) {
            showNotification('Reply posted successfully!', 'success');
            replyForm.remove();
            // Reload the entire post to show the new reply
            viewPostDetail(currentPostId);
        } else {
            showNotification('Error: ' + (data.message || 'Unknown error'), 'error');
            console.error('Server error:', data.message);
        }
    } catch (error) {
        console.error('Error posting reply:', error);
        showNotification('Network error: ' + error.message, 'error');
    }
}

// Cancel reply
function cancelReply(commentId) {
    const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
    const replyForm = commentElement.querySelector('.reply-form-inline');
    if (replyForm) {
        replyForm.remove();
    }
}

// Delete comment
async function deleteComment(commentId) {
    if (!confirm('Are you sure you want to delete this comment? This action cannot be undone.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_comment');
        formData.append('commentId', commentId);
        
        const response = await fetchWithCredentials(communityBase + 'ajax/community.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            showNotification('Server error: ' + text.substring(0, 100), 'error');
            return;
        }
        
        if (data.success) {
            showNotification('Comment deleted successfully', 'success');
            // Reload the post to update comments
            if (currentPostId) {
                viewPostDetail(currentPostId);
            } else {
                // Remove the comment element from DOM
                const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentElement) {
                    commentElement.remove();
                }
            }
        } else {
            showNotification('Error: ' + (data.message || 'Failed to delete comment'), 'error');
        }
    } catch (error) {
        console.error('Error deleting comment:', error);
        showNotification('Failed to delete comment. Please try again.', 'error');
    }
}

// Auto-refresh stats every 30 seconds
setInterval(() => {
    if (currentTab === 'posts') {
        loadCommunityStats();
        loadTrendingTopics();
    }
}, 30000);
