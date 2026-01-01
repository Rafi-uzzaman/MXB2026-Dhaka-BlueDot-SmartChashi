<?php
include __DIR__ . '/../layouts/header.php';
?>

<div class="container" style="padding: 60px 20px; text-align: center; min-height: 70vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
    <div style="max-width: 600px; margin: 0 auto;">
        <!-- 404 Icon -->
        <div style="margin-bottom: 30px;">
            <span class="material-symbols-outlined" style="font-size: 120px; color: #557A46; opacity: 0.7;">
                report_problem
            </span>
        </div>
        
        <!-- 404 Text -->
        <h1 style="font-size: 72px; font-weight: bold; color: #557A46; margin: 0 0 10px 0; line-height: 1;">404</h1>
        <h2 style="font-size: 28px; color: #333; margin: 0 0 20px 0; font-weight: 600;">Page Not Found</h2>
        
        <!-- Description -->
        <p style="font-size: 16px; color: #666; margin: 0 0 40px 0; line-height: 1.6;">
            <?php 
            if (get_language() === 'bn') {
                echo 'দুঃখিত! আপনি যে পৃষ্ঠাটি খুঁজছেন তা পাওয়া যায়নি।<br>এটি সরানো, মুছে ফেলা বা কখনো বিদ্যমান ছিল না।';
            } else {
                echo 'Sorry! The page you\'re looking for doesn\'t exist.<br>It may have been moved, deleted, or never existed at all.';
            }
            ?>
        </p>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo $base_url; ?>" class="btn btn-primary" style="padding: 12px 30px; text-decoration: none; background: #557A46; color: white; border-radius: 8px; font-weight: 500; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;">
                <span class="material-icons" style="font-size: 20px;">home</span>
                <?php echo get_language() === 'bn' ? 'হোম পেজে যান' : 'Go to Home'; ?>
            </a>
            
            <button onclick="window.history.back()" class="btn btn-secondary" style="padding: 12px 30px; text-decoration: none; background: #fff; color: #557A46; border: 2px solid #557A46; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;">
                <span class="material-icons" style="font-size: 20px;">arrow_back</span>
                <?php echo get_language() === 'bn' ? 'ফিরে যান' : 'Go Back'; ?>
            </button>
        </div>
        
        <!-- Helpful Links -->
        <div style="margin-top: 50px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
            <p style="font-size: 14px; color: #999; margin-bottom: 15px;">
                <?php echo get_language() === 'bn' ? 'সাহায্য প্রয়োজন? এই লিংকগুলো চেষ্টা করুন:' : 'Need help? Try these links:'; ?>
            </p>
            <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; font-size: 14px;">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo $base_url; ?>?page=dashboard" style="color: #557A46; text-decoration: none;">
                        <?php echo get_language() === 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard'; ?>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=crops" style="color: #557A46; text-decoration: none;">
                        <?php echo get_language() === 'bn' ? 'ফসল' : 'Crops'; ?>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=community" style="color: #557A46; text-decoration: none;">
                        <?php echo get_language() === 'bn' ? 'কমিউনিটি' : 'Community'; ?>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=marketplace" style="color: #557A46; text-decoration: none;">
                        <?php echo get_language() === 'bn' ? 'মার্কেটপ্লেস' : 'Marketplace'; ?>
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>?page=login" style="color: #557A46; text-decoration: none;">
                        <?php echo get_language() === 'bn' ? 'লগইন' : 'Login'; ?>
                    </a>
                    <a href="<?php echo $base_url; ?>?page=register" style="color: #557A46; text-decoration: none;">
                        <?php echo get_language() === 'bn' ? 'রেজিস্টার' : 'Register'; ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .btn-primary:hover {
        background: #446335 !important;
    }
    
    .btn-secondary:hover {
        background: #557A46 !important;
        color: white !important;
    }
    
    a[style*="color: #557A46"]:hover {
        text-decoration: underline !important;
    }
</style>

<?php
include __DIR__ . '/../layouts/footer.php';
?>
