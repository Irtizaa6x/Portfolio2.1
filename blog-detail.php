<?php
/**
 * blog-detail.php
 *
 * Public blog detail page for IrtiJa portfolio.
 * Displays a single blog post from the SQLite database.
 *
 * @package IrtiJa
 * @version 1.0
 */

// --- Include configuration and database ---
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/db.php';

// --- Get the slug from the query string ---
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// --- Initialize variables ---
$post = null;
$tags = [];
$gallery = [];
$error = false;

// --- Fetch the post from the database ---
if (!empty($slug)) {
    try {
        // Fetch the post by slug, only if published
        $post = db_fetch_one(
            "SELECT p.*, c.name as category_name 
             FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.slug = :slug AND p.status = 'published'",
            ['slug' => $slug]
        );

        if ($post) {
            // Fetch tags
            $tagRows = db_fetch_all(
                "SELECT t.name 
                 FROM post_tags pt
                 JOIN tags t ON pt.tag_id = t.id
                 WHERE pt.post_id = :post_id",
                ['post_id' => $post['id']]
            );
            $tags = array_column($tagRows, 'name');

            // Fetch gallery images
            $galleryRows = db_fetch_all(
                "SELECT image_path 
                 FROM gallery_images 
                 WHERE post_id = :post_id 
                 ORDER BY sort_order",
                ['post_id' => $post['id']]
            );
            $gallery = array_column($galleryRows, 'image_path');
        }
    } catch (PDOException $e) {
        db_log_error('Failed to fetch post for blog detail', ['slug' => $slug, 'error' => $e->getMessage()]);
        $error = true;
    }
}

// --- If post not found or error, set 404 status and show error ---
if (!$post || $error) {
    http_response_code(404);
    $page_title = 'Post Not Found · IrtiJa';
    $page_description = 'The blog post you\'re looking for does not exist.';
    $page_canonical = 'https://irtizaa6x.github.io/blog-detail.php';
    $current_page = 'blog';
    include 'header.php';
    ?>
    <div class="blog-detail-container" style="padding-top:120px;text-align:center;">
        <div class="blog-error" style="display:block;">
            <i class="fas fa-exclamation-circle"></i>
            <h2>Post Not Found</h2>
            <p>The blog post you're looking for doesn't exist or hasn't been published yet.</p>
            <a href="blog.php" class="btn btn-primary" style="margin-top:var(--space-4);">
                <i class="fas fa-arrow-left"></i> Back to Blog
            </a>
        </div>
    </div>
    <?php
    include 'footer.php';
    exit;
}

// --- Prepare page metadata ---
$page_title = htmlspecialchars($post['title']) . ' · IrtiJa';
$page_description = htmlspecialchars($post['preview_text'] ?? substr(strip_tags($post['content']), 0, 160));
$page_canonical = 'https://irtizaa6x.github.io/blog-detail.php?slug=' . urlencode($slug);
$current_page = 'blog';

// --- Include the shared header ---
include 'header.php';
?>

<!-- ============================================================
     BLOG DETAIL PAGE
     ============================================================ -->

<!-- Reading Progress Bar -->
<div class="reading-progress" role="progressbar" aria-label="Reading progress" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
    <div class="reading-progress-bar" id="readingProgressBar"></div>
</div>

<!-- Lightbox (gallery viewer) -->
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
    <button class="lightbox-close" id="lightboxClose" aria-label="Close image viewer">
        <i class="fas fa-times"></i>
    </button>
    <img id="lightboxImage" src="" alt="Gallery image" />
</div>

<main class="blog-detail-page" id="blogDetailPage">
    <article class="blog-detail-container" id="blogDetailContainer" aria-label="Blog post content">

        <!-- Back button -->
        <a href="blog.php" class="back-to-blog">
            <i class="fas fa-arrow-left"></i> Back to Blog
        </a>

        <!-- Cover image -->
        <?php if (!empty($post['cover_image'])): ?>
            <div id="postCover">
                <img src="../<?php echo htmlspecialchars($post['cover_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" class="detail-cover" loading="lazy" />
            </div>
        <?php endif; ?>

        <!-- Title -->
        <h1 class="detail-title" id="postTitle"><?php echo htmlspecialchars($post['title']); ?></h1>

        <!-- Meta -->
        <div class="detail-meta" id="postMeta">
            <span><i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($post['display_date'] ?? ''); ?></span>
            <span><i class="far fa-clock"></i> <span id="readingTime">~<?php echo max(1, round(str_word_count(strip_tags($post['content'])) / 200)); ?> min read</span></span>
            <span><i class="fas fa-user"></i> <span class="detail-author">Md. Irtija Azad Talha</span></span>
        </div>

        <!-- Tags -->
        <?php if (!empty($tags)): ?>
            <div class="detail-tags" id="postTags">
                <?php foreach ($tags as $tag): ?>
                    <a href="blog.php?tag=<?php echo urlencode($tag); ?>" class="tag">#<?php echo htmlspecialchars($tag); ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Table of Contents (generated by JS) -->
        <div class="toc-container" id="tocContainer" style="display:none;">
            <div class="toc-title">
                <i class="fas fa-list-ul"></i> Table of Contents
            </div>
            <ul class="toc-list" id="tocList"></ul>
        </div>

        <!-- Body -->
        <div class="detail-body" id="postBody">
            <?php echo $post['content']; ?>
        </div>

        <!-- Gallery -->
        <?php if (!empty($gallery)): ?>
            <div class="detail-gallery-container" id="galleryContainer">
                <button class="gallery-toggle-btn" id="galleryToggle">
                    <i class="fas fa-images"></i> Hide Gallery
                </button>
                <div class="detail-gallery" id="galleryGrid">
                    <?php foreach ($gallery as $img): ?>
                        <img src="../<?php echo htmlspecialchars($img); ?>" alt="Gallery image" loading="lazy" data-lightbox />
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Video (if any) -->
        <?php if (!empty($post['video_url'])): ?>
            <div class="detail-video">
                <iframe src="<?php echo htmlspecialchars($post['video_url']); ?>" frameborder="0" allowfullscreen loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>
        <?php endif; ?>

        <!-- Certificate -->
        <?php if (!empty($post['certificate_url'])): ?>
            <div id="certContainer">
                <a href="<?php echo htmlspecialchars($post['certificate_url']); ?>" target="_blank" rel="noopener noreferrer" class="detail-cert-btn">
                    <i class="fas fa-certificate"></i> View Certificate
                </a>
            </div>
        <?php endif; ?>

        <!-- Share -->
        <div class="share-section">
            <span class="share-label"><i class="fas fa-share-alt"></i> Share this post</span>
            <div class="share-buttons" id="shareButtons">
                <?php
                $shareUrl = urlencode('https://irtizaa6x.github.io/blog-detail.php?slug=' . $slug);
                $shareTitle = urlencode($post['title']);
                ?>
                <button class="share-btn twitter" onclick="window.open('https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>','_blank','width=600,height=400'); return false;">
                    <i class="fab fa-x-twitter"></i> <span>Twitter</span>
                </button>
                <button class="share-btn linkedin" onclick="window.open('https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>','_blank','width=600,height=400'); return false;">
                    <i class="fab fa-linkedin-in"></i> <span>LinkedIn</span>
                </button>
                <button class="share-btn facebook" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>','_blank','width=600,height=400'); return false;">
                    <i class="fab fa-facebook-f"></i> <span>Facebook</span>
                </button>
                <button class="share-btn whatsapp" onclick="window.open('https://api.whatsapp.com/send?text=<?php echo $shareTitle; ?>%20<?php echo $shareUrl; ?>','_blank','width=600,height=400'); return false;">
                    <i class="fab fa-whatsapp"></i> <span>WhatsApp</span>
                </button>
                <button class="share-btn copy-link" onclick="navigator.clipboard.writeText('https://irtizaa6x.github.io/blog-detail.php?slug=<?php echo urlencode($slug); ?>').then(() => { this.innerHTML = '<i class=\'fas fa-check\'></i> Copied!'; setTimeout(() => { this.innerHTML = '<i class=\'fas fa-link\'></i> Copy Link'; }, 2000); });">
                    <i class="fas fa-link"></i> <span>Copy Link</span>
                </button>
            </div>
        </div>

        <!-- Previous / Next navigation (optional – we can implement later) -->
        <nav class="post-navigation" id="postNavigation" aria-label="Post navigation">
            <!-- Will be populated by JavaScript or server -->
        </nav>

    </article>
</main>

<?php
// --- Include the shared footer ---
include 'footer.php';
?>

<!-- ============================================================
     BLOG DETAIL SCRIPTS (for TOC, gallery, progress)
     ============================================================ -->
<script>
    (function() {
        'use strict';

        // --- Table of Contents ---
        const body = document.getElementById('postBody');
        const tocContainer = document.getElementById('tocContainer');
        const tocList = document.getElementById('tocList');

        if (body && tocContainer && tocList) {
            const headings = body.querySelectorAll('h2, h3, h4');
            if (headings.length >= 2) {
                tocContainer.style.display = 'block';
                headings.forEach((heading, index) => {
                    const level = heading.tagName.toLowerCase();
                    const text = heading.textContent;
                    const id = heading.id || 'heading-' + index;
                    heading.id = id;

                    const li = document.createElement('li');
                    li.className = 'toc-' + level;
                    const a = document.createElement('a');
                    a.href = '#' + id;
                    a.innerHTML = '<span class="toc-level">' + level.toUpperCase() + '</span> ' + text;
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        const target = document.getElementById(id);
                        if (target) {
                            const offset = 88;
                            const top = target.getBoundingClientRect().top + window.scrollY - offset;
                            window.scrollTo({ top, behavior: 'smooth' });
                        }
                    });
                    li.appendChild(a);
                    tocList.appendChild(li);
                });
            }
        }

        // --- Gallery toggle ---
        const galleryToggle = document.getElementById('galleryToggle');
        const galleryGrid = document.getElementById('galleryGrid');
        if (galleryToggle && galleryGrid) {
            let isVisible = true;
            galleryToggle.addEventListener('click', function() {
                isVisible = !isVisible;
                galleryGrid.style.display = isVisible ? 'grid' : 'none';
                this.innerHTML = isVisible ?
                    '<i class="fas fa-times"></i> Hide Gallery' :
                    '<i class="fas fa-images"></i> View Gallery';
            });
        }

        // --- Lightbox ---
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxClose = document.getElementById('lightboxClose');

        function openLightbox(src) {
            lightboxImage.src = src;
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('open');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-lightbox]').forEach(img => {
            img.addEventListener('click', function() {
                openLightbox(this.src);
            });
        });

        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) closeLightbox();
        });
        lightboxClose.addEventListener('click', closeLightbox);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });

        // --- Reading progress ---
        const progressBar = document.getElementById('readingProgressBar');
        function updateProgress() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            if (progressBar) {
                progressBar.style.width = Math.min(progress, 100) + '%';
                progressBar.setAttribute('aria-valuenow', Math.round(progress));
            }
        }
        window.addEventListener('scroll', updateProgress, { passive: true });
        updateProgress();

        // --- Optional: Previous/Next navigation via AJAX (skip for now) ---

    })();
</script>

</body>
</html>