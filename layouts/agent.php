<button class="floating-chat-btn" id="chatFloatingBtn" aria-label="Open AI Chat">
        <span class="material-icons">chat</span>
    </button>

    <!-- Full Page Chat Modal -->
    <div class="chat-modal" id="chatModal">
        <button class="chat-modal-close" id="chatModalClose" aria-label="Close Chat">
            <span class="material-icons">close</span>
        </button>
        
        <div class="chat-modal-body" style="padding: 0; overflow: hidden;">
            <iframe id="agentIframe" src="<?php echo $base_url; ?>agent/index.php" style="width: 100%; height: 100%; border: none; display: block;"></iframe>
        </div>
    </div>
  