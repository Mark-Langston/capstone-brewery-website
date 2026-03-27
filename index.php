<?php 
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$inventoryStmt = $pdo->query("
    SELECT inventory_id, item_name, abv, price, description, image_path
    FROM inventory
    ORDER BY created_at DESC, inventory_id DESC
");
$inventoryItems = $inventoryStmt->fetchAll();

$seasonalStmt = $pdo->query("
    SELECT seasonal_special_id, header_text, description, image_path
    FROM seasonal_specials
    ORDER BY created_at DESC, seasonal_special_id DESC
");
$seasonalSpecials = $seasonalStmt->fetchAll();

$merchStmt = $pdo->query("
    SELECT merch_id, name, price, image_path
    FROM merch
    ORDER BY created_at DESC, merch_id DESC
");
$merchItems = $merchStmt->fetchAll();

include 'header.php';
?>

<a href="#hero" class="skip-link">Skip to main content</a>

<section class="hero" id="hero" style="background-image: url('assets/images/pages/Stage.jpg');">
  <div class="hero-overlay" aria-hidden="true"></div>
  <div class="hero-content">
    <h1>Crafting Flavors, Celebrating Community.</h1>
    <p class="hero-tagline">Explore our unique brews and local favorites.</p>
    <a href="#about" class="hero-cta">Explore the Brewery</a>
  </div>
</section>

<section id="about" aria-labelledby="about-heading">
  <div class="container">
    <div class="section-title">
      <h2 id="about-heading">Behind the Brews at Main Channel</h2>
    </div>

    <div class="brews-pillars">
      <div class="brews-pillar">
        <h4>Best ingredients</h4>
        <p>Sourced locally for unparalleled quality and freshness. Each sip tells a rich story of community and craftsmanship.</p>
      </div>
      <div class="brews-pillar">
        <h4>Our Beers</h4>
        <p>Unique flavors, classic favorites, and exciting new adventures crafted with passion.</p>
      </div>
      <div class="brews-pillar">
        <h4>Our process</h4>
        <p>Age-old traditions meet modern innovation to craft the perfect brew every time.</p>
      </div>
      <div class="brews-pillar">
        <h4>Story behind</h4>
        <p>Every batch has a tale, deeply rooted in inspired beginnings and carefully crafted until the final pour.</p>
      </div>
    </div>

    <div class="about-block about-block--split">
      <div class="about-block-image" style="background-image: url('assets/images/pages/Bright_Tanks.jpg');"></div>
      <div class="about-block-text">
        <h3>Crafted with Care, Served with Pride.</h3>
        <p>Every beer tells a tale. We combine meticulous brewing methods with locally inspired ingredients and bold, balanced profiles. Redefine expectations with every sip of our signature brews.</p>
        <a href="#locations" class="about-cta">Visit Our Locations</a>
      </div>
    </div>

    <blockquote class="brewmaster">
      <p>I believe in beer that tells a story—a story of dedication and community. Each batch is a testament to our commitment to excellence and innovation in brewing.
          It's not just about the brew; it's about creating moments that bring people together. So, every sip you take is part of a larger narrative, one that I am proud to share with you.</p>
      <cite>— Tyke Jordan, Brew Master</cite>
    </blockquote>

    <div class="about-block about-block--split about-block--reverse">
      <div class="about-block-image" style="background-image: url('assets/capstonephoto6-4af5d255-0ac4-4d3b-ab76-d91984a51ce2.png');"></div>

      <div class="about-block-text">
        <h3>Meet the Crew</h3>
        <p>Discover the passionate team behind our exceptional craft beers.</p>

        <div class="crew-grid">
          <div class="crew-card">
            <div class="crew-card-image-clay" aria-hidden="true"></div>
            <h4>Clay Smith</h4>
            <p class="crew-card-role">Founder</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-jeff" aria-hidden="true"></div>
            <h4>Jeff Gossage</h4>
            <p class="crew-card-role">General Manager</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-prentice" aria-hidden="true"></div>
            <h4>Prentice Satterfield</h4>
            <p class="crew-card-role">Sales Manager</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-brian" aria-hidden="true"></div>
            <h4>Brian Satterfield</h4>
            <p class="crew-card-role">Cellar Operator</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-tyke" aria-hidden="true"></div>
            <h4>Tyke Jordan</h4>
            <p class="crew-card-role">Brew Master</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-drew" aria-hidden="true"></div>
            <h4>Drew Martin</h4>
            <p class="crew-card-role">Guntersville Taproom Manager</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-tomkoury" aria-hidden="true"></div>
            <h4>Tom Koury</h4>
            <p class="crew-card-role">Albertville Taproom Manager</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-aaliyah" aria-hidden="true"></div>
            <h4>Aaliyah Bird</h4>
            <p class="crew-card-role">Albertville Taproom Assistant Manager</p>
          </div>

          <div class="crew-card">
            <div class="crew-card-image-leigh-ellen" aria-hidden="true"></div>
            <h4>Leigh Ellen Styke</h4>
            <p class="crew-card-role">Event Coordinator, Bartender</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="seasonal-specials" aria-labelledby="seasonal-specials-heading">
  <div class="container seasonal-specials-container">
    <div class="section-title seasonal-section-title">
      <h2 id="seasonal-specials-heading">Seasonal Specials</h2>
      <p class="section-subtitle">
        Explore our seasonal and new release brews, crafted to embrace the spirit of each season.
        From bright, refreshing ales to richer, limited-time creations, each pour brings a fresh taste of now.
      </p>
    </div>

    <?php if (empty($seasonalSpecials)): ?>
      <div class="seasonal-empty">
        <h3>No seasonal specials are available right now.</h3>
        <p>Please check back again soon.</p>
      </div>
    <?php else: ?>
      <div class="seasonal-carousel-shell">
        <button class="seasonal-carousel-arrow seasonal-carousel-arrow--prev" type="button" aria-label="Previous seasonal special">&#8249;</button>
        <button class="seasonal-carousel-arrow seasonal-carousel-arrow--next" type="button" aria-label="Next seasonal special">&#8250;</button>

        <div class="seasonal-carousel" id="seasonalCarousel">
          <div class="seasonal-track" id="seasonalTrack">
            <?php foreach ($seasonalSpecials as $item): ?>
              <article class="seasonal-slide">
                <div class="seasonal-image-panel">
                  <div class="seasonal-image-wrap">
                    <?php if (!empty($item['image_path']) && is_file(__DIR__ . '/' . ltrim((string) $item['image_path'], '/'))): ?>
                      <img
                        src="/<?= htmlspecialchars(ltrim((string) $item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars((string) $item['header_text'], ENT_QUOTES, 'UTF-8') ?>"
                      >
                    <?php else: ?>
                      <div class="seasonal-no-image">No Image Available</div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="seasonal-content-panel">
                  <div class="seasonal-content-box">
                    <h3><?= htmlspecialchars((string) $item['header_text'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= nl2br(htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="seasonal-carousel-dots" id="seasonalCarouselDots">
          <?php foreach ($seasonalSpecials as $index => $item): ?>
            <button
              class="seasonal-carousel-dot<?= $index === 0 ? ' active' : '' ?>"
              type="button"
              aria-label="Go to seasonal special <?= $index + 1 ?>"
              data-slide-index="<?= $index ?>"
            ></button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="beer-menu" class="homepage-beer-menu" aria-labelledby="beer-menu-heading">
  <div class="beer-ambiance" style="background-image: url('assets/images/pages/Menu.jpg');" role="img" aria-label="Tap menu board behind the bar"></div>
  <div class="container beer-menu-container">
    <div class="section-title">
      <h2 id="beer-menu-heading">Beer Menu</h2>
      <p class="section-subtitle">Explore our current selection of beers on tap.</p>
    </div>

    <?php if (empty($inventoryItems)): ?>
      <div class="beer-menu-empty">
        <p>No beer menu items are available right now. Please check back soon.</p>
      </div>
    <?php else: ?>
      <div class="beer-menu-grid beer-menu-grid--homepage">
        <?php foreach ($inventoryItems as $item): ?>
          <article class="beer-card">
            <div class="beer-card-image">
              <?php if (!empty($item['image_path']) && is_file(__DIR__ . '/' . ltrim((string) $item['image_path'], '/'))): ?>
                <img
                  src="<?= htmlspecialchars('/' . ltrim((string) $item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8') ?>"
                >
              <?php else: ?>
                <div class="beer-card-no-image">No Image</div>
              <?php endif; ?>
            </div>

            <div class="beer-card-content">
              <div class="beer-card-header">
                <h3><?= htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <span class="beer-price">$<?= htmlspecialchars((string) $item['price'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>

              <p class="beer-abv">ABV: <?= htmlspecialchars((string) $item['abv'], ENT_QUOTES, 'UTF-8') ?>%</p>

              <p class="beer-description">
                <?= nl2br(htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8')) ?>
              </p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="merch" class="homepage-merch" aria-labelledby="merch-heading">
  <div class="container">
    <div class="section-title">
      <h2 id="merch-heading">Merch Store</h2>
      <p class="section-subtitle">Browse Main Channel Brewing merchandise and featured items.</p>
    </div>

    <?php if (empty($merchItems)): ?>
      <div class="merch-empty">
        <h3>No merch items are available right now.</h3>
        <p>Please check back again soon.</p>
      </div>
    <?php else: ?>
      <div class="carousel-shell">
        <button class="carousel-arrow prev" type="button" aria-label="Previous merch item">&#8249;</button>
        <button class="carousel-arrow next" type="button" aria-label="Next merch item">&#8250;</button>

        <div class="merch-carousel" id="merchCarousel">
          <div class="merch-track" id="merchTrack">
            <?php foreach ($merchItems as $item): ?>
              <div class="merch-slide">
                <div class="merch-image-panel">
                  <div class="merch-image-wrap">
                    <?php if (!empty($item['image_path']) && is_file(__DIR__ . '/' . ltrim((string) $item['image_path'], '/'))): ?>
                      <img
                        src="/<?= htmlspecialchars(ltrim((string) $item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?>"
                      >
                    <?php else: ?>
                      <div class="merch-no-image">No Image Available</div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="merch-content-panel">
                  <div class="merch-content-box">
                    <h3><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="merch-price">$<?= htmlspecialchars(number_format((float) $item['price'], 2), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="merch-copy">
                      Available while supplies last. Check in at Main Channel Brewing for current availability and featured releases.
                    </p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="carousel-dots" id="carouselDots">
          <?php foreach ($merchItems as $index => $item): ?>
            <button
              class="carousel-dot<?= $index === 0 ? ' active' : '' ?>"
              type="button"
              aria-label="Go to merch item <?= $index + 1 ?>"
              data-slide-index="<?= $index ?>"
            ></button>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="locations" aria-labelledby="locations-heading">
  <div class="container">
    <div class="section-title">
      <h2 id="locations-heading">Locations</h2>
      <p class="section-subtitle">A location finder or map experience can be added here next.</p>
    </div>

    <div class="locations-grid">
      <div class="location-card">
        <h3>Guntersville</h3>
        <p>This placeholder can become a detailed taproom card, including hours, address, phone number, and map integration.</p>
      </div>

      <div class="location-card">
        <h3>Albertville</h3>
        <p>This placeholder can also support future business partner locations, store availability, or interactive location pins.</p>
      </div>
    </div>

    <div class="upcoming-events">
      <h3>Future Enhancement</h3>
      <p>
        This section is reserved for a map, business locations carrying Main Channel Brewing products,
        or a taproom finder experience once the team is ready to implement it.
      </p>
    </div>
  </div>
</section>

<section id="contact" aria-labelledby="contact-heading">
  <div class="container">
    <div class="section-title">
      <h2 id="contact-heading">Contact Us</h2>
    </div>
    <div class="contact-block">
      <p>Email: <a href="mailto:beer@mainchannelbrewing.com">beer@mainchannelbrewing.com</a></p>
      <p>Guntersville: <a href="tel:+12569605070">(256) 960-5070</a> · Albertville: <a href="tel:+12566600335">(256) 660-0335</a></p>
    </div>
  </div>
</section>

<?php if (!empty($seasonalSpecials)): ?>
<script>
(function () {
    const slides = Array.from(document.querySelectorAll('.seasonal-slide'));
    const dots = Array.from(document.querySelectorAll('.seasonal-carousel-dot'));
    const prevBtn = document.querySelector('.seasonal-carousel-arrow--prev');
    const nextBtn = document.querySelector('.seasonal-carousel-arrow--next');
    const carousel = document.getElementById('seasonalCarousel');
    const track = document.getElementById('seasonalTrack');

    if (!slides.length || !track || !carousel || !prevBtn || !nextBtn) {
        return;
    }

    let currentIndex = 0;
    let intervalId = null;
    const delay = 5000;

    function updateDots() {
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }

    function showSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        updateDots();
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    function startAutoRotate() {
        stopAutoRotate();
        if (slides.length > 1) {
            intervalId = window.setInterval(nextSlide, delay);
        }
    }

    function stopAutoRotate() {
        if (intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    }

    nextBtn.addEventListener('click', function () {
        nextSlide();
        startAutoRotate();
    });

    prevBtn.addEventListener('click', function () {
        prevSlide();
        startAutoRotate();
    });

    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
            showSlide(index);
            startAutoRotate();
        });
    });

    carousel.addEventListener('mouseenter', stopAutoRotate);
    carousel.addEventListener('mouseleave', startAutoRotate);
    carousel.addEventListener('touchstart', stopAutoRotate, { passive: true });
    carousel.addEventListener('touchend', startAutoRotate);

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopAutoRotate();
        } else {
            startAutoRotate();
        }
    });

    showSlide(0);
    startAutoRotate();
})();
</script>
<?php endif; ?>

<?php if (!empty($merchItems)): ?>
<script>
(function () {
    const slides = Array.from(document.querySelectorAll('.merch-slide'));
    const dots = Array.from(document.querySelectorAll('.carousel-dot'));
    const prevBtn = document.querySelector('.carousel-arrow.prev');
    const nextBtn = document.querySelector('.carousel-arrow.next');
    const carousel = document.getElementById('merchCarousel');
    const track = document.getElementById('merchTrack');

    if (!slides.length || !track || !carousel || !prevBtn || !nextBtn) {
        return;
    }

    let currentIndex = 0;
    let intervalId = null;
    const delay = 5000;

    function updateDots() {
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }

    function showSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        updateDots();
    }

    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    function startAutoRotate() {
        stopAutoRotate();
        if (slides.length > 1) {
            intervalId = window.setInterval(nextSlide, delay);
        }
    }

    function stopAutoRotate() {
        if (intervalId !== null) {
            window.clearInterval(intervalId);
            intervalId = null;
        }
    }

    nextBtn.addEventListener('click', function () {
        nextSlide();
        startAutoRotate();
    });

    prevBtn.addEventListener('click', function () {
        prevSlide();
        startAutoRotate();
    });

    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
            showSlide(index);
            startAutoRotate();
        });
    });

    carousel.addEventListener('mouseenter', stopAutoRotate);
    carousel.addEventListener('mouseleave', startAutoRotate);
    carousel.addEventListener('touchstart', stopAutoRotate, { passive: true });
    carousel.addEventListener('touchend', startAutoRotate);

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopAutoRotate();
        } else {
            startAutoRotate();
        }
    });

    showSlide(0);
    startAutoRotate();
})();
</script>
<?php endif; ?>


<script>
(function () {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');

            if (!targetId || targetId === '#') {
                return;
            }

            const target = document.querySelector(targetId);
            if (!target) {
                return;
            }

            e.preventDefault();

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            history.replaceState(null, null, window.location.pathname);
        });
    });

    if (window.location.hash) {
        history.replaceState(null, null, window.location.pathname);
    }
})();
</script>


<?php include 'footer.php'; ?>
