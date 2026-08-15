<?php
/**
 * blog.php
 *
 * Public blog listing page for IrtiJa portfolio.
 * Displays posts from the SQLite database in a 3-column grid.
 *
 * @package IrtiJa
 * @version 1.0
 */

// --- Include configuration and database ---
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/admin/db.php';

// --- Page-specific variables for the header ---
$page_title       = 'Blog · IrtiJa';
$page_description = 'Insights, explorations, and thoughts on cybersecurity, technology, and personal growth by Md. Irtija Azad Talha.';
$page_canonical   = 'https://irtizaa6x.github.io/blog.php';
$current_page     = 'blog';

// --- Fetch posts from the database ---
$posts = [];
$totalPosts = 0;

try {
    // Get published posts, ordered by display_date descending (newest first)
    $posts = db_fetch_all(
        "SELECT p.*, c.name as category_name 
         FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.status = 'published'
         ORDER BY p.created_at DESC"
    );
    $totalPosts = count($posts);
} catch (PDOException $e) {
    db_log_error('Failed to fetch posts for blog page', ['error' => $e->getMessage()]);
    // Continue with empty posts array
}

// --- Helper function to get category from tags ---
function getCategoryFromTags($tags) {
    if (empty($tags)) return 'General';
    $tag = strtolower($tags[0]);
    $map = [
        'code' => 'Code',
        'cybersecurity' => 'Cybersecurity',
        'club' => 'Club',
        'workshop' => 'Workshop',
        'visit' => 'Visit',
        'event' => 'Event',
        'project' => 'Project',
        'certification' => 'Certification',
    ];
    return $map[$tag] ?? ucfirst($tag);
}

// --- Include the shared header ---
include 'header.php';
?>

<!-- ============================================================
     PAGE HERO
     ============================================================ -->
<section class="page-hero" aria-labelledby="page-hero-title">
    <div class="container">
        <div class="page-hero-content">
            <span class="section-tag">Blog</span>
            <h1 class="page-hero-title" id="page-hero-title">
                Insights &amp; Explorations
            </h1>
            <p class="page-hero-subtitle">
                Documenting my journey into cybersecurity, technology, and
                personal growth — one post at a time.
            </p>
            <div class="page-hero-stats">
                <div class="hero-stat" id="postCount">
                    <span class="hero-stat-number"><?php echo $totalPosts; ?></span>
                    <span class="hero-stat-label">Posts</span>
                </div>
                <div class="hero-stat-divider"></div>
                <div class="hero-stat">
                    <span class="hero-stat-number"><i class="fas fa-tags" style="font-size:1.2rem;"></i></span>
                    <span class="hero-stat-label">Topics</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     BLOG TOOLBAR (Search & Filters)
     ============================================================ -->
<section class="blog-toolbar" aria-label="Blog search and filters">
    <div class="container">
        <div class="blog-toolbar-inner">

            <!-- Search -->
            <div class="blog-search">
                <i class="fas fa-search"></i>
                <input
                    type="text"
                    id="blogSearch"
                    placeholder="Search articles..."
                    aria-label="Search blog posts"
                />
            </div>

            <!-- Filters -->
            <div class="blog-filters" id="blogFilters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="cybersecurity">Cybersecurity</button>
                <button class="filter-btn" data-filter="web-dev">Web Dev</button>
                <button class="filter-btn" data-filter="projects">Projects</button>
                <button class="filter-btn" data-filter="events">Events &amp; Clubs</button>
		<button class="filter-btn" data-filter="bncc">BNCC</button>
            </div>

            <!-- Count -->
            <span class="blog-count" id="blogCount">
                <span class="count-number"><?php echo $totalPosts; ?></span>
                <span class="count-label"><?php echo $totalPosts === 1 ? 'Post' : 'Posts'; ?></span>
            </span>

        </div>
    </div>
</section>


<!-- ============================================================
     BLOG GRID
     ============================================================ -->
<section class="blog-grid-section" aria-labelledby="blog-grid-title">
    <div class="container">
        <div class="blog-grid" id="blogGrid">
            <?php if (empty($posts)): ?>
                <div class="blog-empty" style="grid-column:1/-1;">
                    <i class="fas fa-book-open"></i>
                    <p>No blog posts published yet.<br />Check back soon for updates on my journey!</p>
                </div>
            <?php else: ?>
  <?php foreach ($posts as $post):
    // --- Get tags and category ---
    $tags = !empty($post['tags_string']) ? explode(',', $post['tags_string']) : [];
    $category = !empty($post['category_name']) ? $post['category_name'] : '';
    
    // --- Build tag string for data-tags (includes category) ---
    $allTags = $tags;
    if (!empty($category)) {
        $allTags[] = $category;
    }
    $tagString = implode(',', array_map('strtolower', $allTags));
    
    // --- Get content preview for search ---
    $contentPreview = strip_tags($post['content']);
    $contentShort = substr($contentPreview, 0, 500);
    
    // --- Other post data ---
    $coverUrl = !empty($post['cover_image']) ? '../' . $post['cover_image'] : '';
    $title = $post['title'] ?? 'Untitled';
    $date = $post['display_date'] ?? '';
    $preview = $post['preview_text'] ?? 'Click to read more.';
    $slug = $post['slug'] ?? '';
?>
<article class="blog-card" 
         data-slug="<?php echo htmlspecialchars($slug); ?>" 
         data-tags="<?php echo htmlspecialchars($tagString); ?>"
         data-content="<?php echo htmlspecialchars($contentShort); ?>"
         role="article">
    <div class="blog-card-image-wrapper">
        <?php if ($coverUrl): ?>
            <img src="<?php echo htmlspecialchars($coverUrl); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="blog-card-image" loading="lazy" />
        <?php else: ?>
            <div class="blog-card-image" style="background: linear-gradient(135deg, var(--primary), var(--primary-light)); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.15); font-size:3rem;">
                <i class="fas fa-file-alt"></i>
            </div>
        <?php endif; ?>
        <span class="blog-card-category"><?php echo htmlspecialchars($category ?: 'General'); ?></span>
        <div class="blog-card-overlay">
            <h3 class="blog-card-title"><?php echo htmlspecialchars($title); ?></h3>
            <?php if ($date): ?>
                <time class="blog-card-date"><?php echo htmlspecialchars($date); ?></time>
            <?php endif; ?>
            <span class="blog-card-category-overlay"><?php echo htmlspecialchars($category ?: 'General'); ?></span>
            <p class="blog-card-excerpt-overlay"><?php echo htmlspecialchars($preview); ?></p>
            <span class="blog-card-hint">Click for more details →</span>
        </div>
    </div>
</article>
<?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="no-results" id="noResults" style="display:none;">
            <i class="fas fa-book-open"></i>
            <h3>No posts found</h3>
            <p>Try adjusting your search or filter criteria.</p>
        </div>
    </div>
</section>

<!-- ============================================================
     CALL TO ACTION
     ============================================================ -->
<section class="cta-section" aria-labelledby="cta-title">
    <div class="container">
        <div class="cta-card">
            <div class="cta-content">
                <h2 id="cta-title">Let's Connect</h2>
                <p>
                    Have a question about a post? Want to collaborate on a
                    project? I'd love to hear from you.
                </p>
                <div class="cta-actions">
                    <a href="contact.php" class="btn btn-cta-primary">
                        <i class="fas fa-paper-plane"></i> Get in Touch
                    </a>
                    <a href="projects.php" class="btn btn-cta-secondary">
                        <i class="fas fa-code-branch"></i> Explore Projects
                    </a>
                </div>
            </div>
            <div class="cta-decoration" aria-hidden="true">
                <i class="fas fa-book-open"></i>
            </div>
        </div>
    </div>
</section>

<?php
// --- Include the shared footer ---
include 'footer.php';
?>

<!-- ============================================================
     BLOG LOGIC: FILTER & SEARCH (Updated for Database)
     ============================================================ -->
<script src="config.js" defer></script>
<script>
    (function() {
        'use strict';

        // ============================================================
        //  1.  DOM REFS
        // ============================================================

        const grid = document.getElementById('blogGrid');
        const noResults = document.getElementById('noResults');
        const searchInput = document.getElementById('blogSearch');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const countEl = document.getElementById('blogCount');

        let currentFilter = 'all';
        let currentSearch = '';

        // ============================================================
        //  2.  UTILITY FUNCTIONS
        // ============================================================

        function debounce(fn, delay) {
            let timeoutId;
            return function(...args) {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => fn.apply(this, args), delay);
            };
        }

function cardMatches(card, filter, search) {
    // Filter check
    if (filter !== 'all') {
        const tags = card.dataset.tags || '';
        if (!tags.toLowerCase().includes(filter)) {
            return false;
        }
    }

    // Search check
    if (search.trim()) {
        const query = search.trim().toLowerCase();
        const title = card.querySelector('.blog-card-title')?.textContent?.toLowerCase() || '';
        const excerpt = card.querySelector('.blog-card-excerpt-overlay')?.textContent?.toLowerCase() || '';
        const tags = card.dataset.tags || '';
        const content = card.dataset.content || '';
        const fullText = title + ' ' + excerpt + ' ' + tags + ' ' + content;
        if (!fullText.toLowerCase().includes(query)) {
            return false;
        }
    }

    return true;
}
        function applyFilters() {
            if (!grid) return;

            const cards = grid.querySelectorAll('.blog-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const matches = cardMatches(card, currentFilter, currentSearch);
                card.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            // Update count
countEl.innerHTML = `
    <span class="count-number">${visibleCount}</span>
    <span class="count-label">${visibleCount === 1 ? 'Post' : 'Posts'}</span>
`;

            // Show/hide no results
            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }

            // Update active filter button state
            filterBtns.forEach(btn => {
                const isActive = btn.dataset.filter === currentFilter;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        function setFilter(filter) {
            currentFilter = filter;
            applyFilters();
        }

        function setSearch(query) {
            currentSearch = query;
            applyFilters();
        }

        // ============================================================
        //  3.  MOBILE INTERACTIONS
        // ============================================================

        function setupMobileInteractions() {
            if (!grid) return;

            const cards = grid.querySelectorAll('.blog-card');
            let isMobile = window.innerWidth <= 768;

            window.addEventListener('resize', debounce(function() {
                const wasMobile = isMobile;
                isMobile = window.innerWidth <= 768;
                if (!isMobile && wasMobile) {
                    cards.forEach(card => {
                        const overlay = card.querySelector('.blog-card-overlay');
                        if (overlay) {
                            overlay.classList.remove('mobile-expanded');
                            card._expanded = false;
                        }
                    });
                }
            }, 250));

            cards.forEach(card => {
                const overlay = card.querySelector('.blog-card-overlay');
                if (!overlay) return;

                let expanded = false;
                card._expanded = false;

                card.addEventListener('click', function(e) {
                    const slug = this.dataset.slug;

                    if (isMobile) {
                        e.preventDefault();

                        if (!expanded) {
                            overlay.classList.add('mobile-expanded');
                            expanded = true;
                            card._expanded = true;
                        } else {
                            if (slug) {
                                window.location.href = 'blog-detail.php?slug=' + encodeURIComponent(slug);
                            }
                        }
                    } else {
                        if (slug) {
                            window.location.href = 'blog-detail.php?slug=' + encodeURIComponent(slug);
                        }
                    }
                });

                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        const slug = this.dataset.slug;
                        if (slug) {
                            window.location.href = 'blog-detail.php?slug=' + encodeURIComponent(slug);
                        }
                    }
                });

                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
            });
        }

        // ============================================================
        //  4.  INIT
        // ============================================================

        function init() {
            // Get initial filter from active button
            filterBtns.forEach(btn => {
                if (btn.classList.contains('active')) {
                    currentFilter = btn.dataset.filter || 'all';
                }
            });

            // Setup search with debounce
            if (searchInput) {
                const debouncedSearch = debounce(function(e) {
                    setSearch(e.target.value);
                }, 300);
                searchInput.addEventListener('input', debouncedSearch);
            }

            // Setup filter buttons
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    setFilter(this.dataset.filter);
                });
            });

            // Setup mobile interactions
            setupMobileInteractions();

            // Apply initial filters
            applyFilters();

            console.log('✅ Blog loaded — ' + document.querySelectorAll('.blog-card').length + ' posts');
        }

        // ============================================================
        //  5.  BOOTSTRAP
        // ============================================================

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }

    })();
</script>

</body>
</html>
