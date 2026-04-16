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

$mapStmt = $pdo->query("
    SELECT map_location_id, name, address, beers_sold, latitude, longitude
    FROM map_locations
    WHERE latitude IS NOT NULL
      AND longitude IS NOT NULL
    ORDER BY name ASC, map_location_id ASC
");
$mapLocations = $mapStmt->fetchAll();

include 'header.php';
?>

<a href="#hero" class="skip-link">Skip to main content</a>

<section class="hero" id="hero" style="background-image: url('assets/images/pages/Stage.jpg');" role="img" aria-label="Main Channel Brewing stage and taproom">
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
      <div class="brews-pillar interactive-card" tabindex="0">
        <h4>Best ingredients</h4>
        <p>Sourced locally for unparalleled quality and freshness. Each sip tells a rich story of community and craftsmanship.</p>
      </div>
      <div class="brews-pillar interactive-card" tabindex="0">
        <h4>Our Beers</h4>
        <p>Unique flavors, classic favorites, and exciting new adventures crafted with passion.</p>
      </div>
      <div class="brews-pillar interactive-card" tabindex="0">
        <h4>Our process</h4>
        <p>Age-old traditions meet modern innovation to craft the perfect brew every time.</p>
      </div>
      <div class="brews-pillar interactive-card" tabindex="0">
        <h4>Story behind</h4>
        <p>Every batch has a tale, deeply rooted in inspired beginnings and carefully crafted until the final pour.</p>
      </div>
    </div>

    <div class="about-block about-block--split">
      <div class="about-block-image" style="background-image: url('assets/images/pages/Bright_Tanks.jpg');" role="img" aria-label="Bright tanks inside the brewery"></div>
      <div class="about-block-text interactive-card" tabindex="0">
        <h3>Crafted with Care, Served with Pride.</h3>
        <p>Every beer tells a tale. We combine meticulous brewing methods with locally inspired ingredients and bold, balanced profiles. Redefine expectations with every sip of our signature brews.</p>
        <a href="#locations" class="about-cta">Visit Our Locations</a>
      </div>
    </div>

    <blockquote class="brewmaster interactive-card" tabindex="0">
      <p>I believe in beer that tells a story—a story of dedication and community. Each batch is a testament to our commitment to excellence and innovation in brewing.
          It's not just about the brew; it's about creating moments that bring people together. So, every sip you take is part of a larger narrative, one that I am proud to share with you.</p>
      <cite>— Tyke Jordan, Brew Master</cite>
    </blockquote>

    <div class="about-block about-block--full about-block--crew">
      <div class="about-block-text interactive-card" tabindex="0">
        <h3>Meet the Crew</h3>
        <p>Discover the passionate team behind our exceptional craft beers.</p>

        <div class="crew-grid">
          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/clay.png" alt="Clay Smith" loading="lazy">
            </div>
            <h4>Clay Smith</h4>
            <p class="crew-card-role">Founder</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/jeff-gossage.jpg" alt="Jeff Gossage" loading="lazy">
            </div>
            <h4>Jeff Gossage</h4>
            <p class="crew-card-role">General Manager</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/prentice.jpg" alt="Prentice Satterfield" loading="lazy">
            </div>
            <h4>Prentice Satterfield</h4>
            <p class="crew-card-role">Sales Manager</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/brian.jpg" alt="Brian Satterfield" loading="lazy">
            </div>
            <h4>Brian Satterfield</h4>
            <p class="crew-card-role">Cellar Operator</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/tyke-jordan.jpg" alt="Tyke Jordan" loading="lazy">
            </div>
            <h4>Tyke Jordan</h4>
            <p class="crew-card-role">Brew Master</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/drew.png" alt="Drew Martin" loading="lazy">
            </div>
            <h4>Drew Martin</h4>
            <p class="crew-card-role">Guntersville Taproom Manager</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/tomkoury.png" alt="Tom Koury" loading="lazy">
            </div>
            <h4>Tom Koury</h4>
            <p class="crew-card-role">Albertville Taproom Manager</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/aaliyah-bird.jpg" alt="Aaliyah Bird" loading="lazy">
            </div>
            <h4>Aaliyah Bird</h4>
            <p class="crew-card-role">Albertville Taproom Assistant Manager</p>
          </div>

          <div class="crew-card interactive-card" tabindex="0">
            <div class="crew-card-image">
              <img src="assets/images/crew/leigh-ellen.png" alt="Leigh Ellen Styke" loading="lazy">
            </div>
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
      <div class="seasonal-empty interactive-card" tabindex="0">
        <h3>No seasonal specials are available right now.</h3>
        <p>Please check back again soon.</p>
      </div>
    <?php else: ?>
      <div class="seasonal-carousel-shell interactive-card">
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
                        loading="lazy"
                      >
                    <?php else: ?>
                      <div class="seasonal-no-image" aria-label="No image available for <?= htmlspecialchars((string) $item['header_text'], ENT_QUOTES, 'UTF-8') ?>">No Image Available</div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="seasonal-content-panel">
                  <div class="seasonal-content-box interactive-card" tabindex="0">
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
      <h2 id="beer-menu-heading">Beer & More</h2>
      <p class="section-subtitle">Explore our current menu of beer and assorted other drinks.</p>
    </div>

    <?php if (empty($inventoryItems)): ?>
      <div class="beer-menu-empty interactive-card" tabindex="0">
        <p>No beer menu items are available right now. Please check back soon.</p>
      </div>
    <?php else: ?>
      <div class="beer-menu-grid beer-menu-grid--homepage">
        <?php foreach ($inventoryItems as $item): ?>
          <article class="beer-card interactive-card" tabindex="0" aria-expanded="false">
            <div class="beer-card-image">
              <?php if (!empty($item['image_path']) && is_file(__DIR__ . '/' . ltrim((string) $item['image_path'], '/'))): ?>
                <img
                  src="<?= htmlspecialchars('/' . ltrim((string) $item['image_path'], '/'), ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8') ?>"
                  loading="lazy"
                >
              <?php else: ?>
                <div class="beer-card-no-image" aria-label="No image available for <?= htmlspecialchars((string) $item['item_name'], ENT_QUOTES, 'UTF-8') ?>">No Image</div>
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
      <div class="merch-empty interactive-card" tabindex="0">
        <h3>No merch items are available right now.</h3>
        <p>Please check back again soon.</p>
      </div>
    <?php else: ?>
      <div class="carousel-shell interactive-card">
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
                        loading="lazy"
                      >
                    <?php else: ?>
                      <div class="merch-no-image" aria-label="No image available for <?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?>">No Image Available</div>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="merch-content-panel">
                  <div class="merch-content-box interactive-card" tabindex="0">
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
  <div class="container map-section-container">
    <div class="section-title">
      <h2 id="locations-heading">Find Our Beer</h2>
      <p class="section-subtitle">
        Browse locations that carry Main Channel Brewing beers. Select a location in the list or choose a marker on the map for details.
      </p>
    </div>

    <div class="map-section-card interactive-card">
      <div class="map-layout">
        <div
          id="breweryMap"
          class="brewery-map"
          role="region"
          aria-label="Interactive map of locations carrying Main Channel Brewing beers"
        ></div>

        <aside class="locations-panel interactive-card" aria-labelledby="locations-list-heading">
          <h3 id="locations-list-heading">Locations</h3>

          <?php if (empty($mapLocations)): ?>
            <p class="empty-state">No mapped locations are available yet.</p>
          <?php else: ?>
            <ul class="location-list">
              <?php foreach ($mapLocations as $location): ?>
                <li>
                  <button
                    type="button"
                    class="location-button"
                    data-location-id="<?= (int) $location['map_location_id'] ?>"
                  >
                    <span class="location-name">
                      <?= htmlspecialchars((string) $location['name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <span class="location-address">
                      <?= htmlspecialchars((string) $location['address'], ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <?php if (!empty($location['beers_sold'])): ?>
                      <span class="location-beers">
                        Beers: <?= htmlspecialchars((string) $location['beers_sold'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    <?php endif; ?>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </div>
</section>

<section id="contact" aria-labelledby="contact-heading">
  <div class="container">
    <div class="section-title">
      <h2 id="contact-heading">Contact Us</h2>
    </div>

    <div class="contact-block interactive-card" tabindex="0">
      <p>Email: 
        <a href="mailto:beer@mainchannelbrewing.com">
          beer@mainchannelbrewing.com
        </a>
      </p>

      <div class="contact-locations">

        <!-- Guntersville -->
        <div class="contact-location interactive-card" tabindex="0">
          <h3>Guntersville - Our Original Location</h3>

          <p class="contact-info">
            <span class="address">
              2090 Gunter Ave, Guntersville, AL
            </span><br>
            <span class="phone">
              <a href="tel:+12569605070">(256) 960-5070</a>
            </span>
          </p>

          <div class="hours">
            <strong>Hours:</strong>
            <p>
              Mon–Thu: 3PM – 9PM<br>
              Fri: 3PM – 10PM<br>
              Sat: 12PM – 10PM<br>
              Sun: 12PM – 10PM
            </p>
          </div>
        </div>

        <!-- Albertville -->
        <div class="contact-location interactive-card" tabindex="0">
          <h3>Albertville - Our Second Location</h3>

          <p class="contact-info">
            <span class="address">
              210 Sand Mountain Dr, Albertville, AL
            </span><br>
            <span class="phone">
              <a href="tel:+12566600335">(256) 660-0335</a>
            </span>
          </p>

          <div class="hours">
            <strong>Hours:</strong>
            <p>
              Mon: 3PM – 9PM<br>
              Tue–Thu: 11AM – 9PM<br>
              Fri: 11AM – 10PM<br>
              Sat: 12PM – 10PM<br>
              Sun: Closed
            </p>
          </div>
        </div>

      </div>
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

<script>
(function () {
    const beerCards = Array.from(document.querySelectorAll('.beer-card'));
    const isTouchOrSmallScreen = window.matchMedia('(hover: none), (max-width: 860px)');

    function collapseOthers(activeCard) {
        beerCards.forEach(function (card) {
            if (card !== activeCard) {
                card.classList.remove('expanded');
                card.setAttribute('aria-expanded', 'false');
            }
        });
    }

    beerCards.forEach(function (card) {
        card.addEventListener('click', function () {
            if (!isTouchOrSmallScreen.matches) {
                return;
            }

            const isExpanded = card.classList.contains('expanded');
            collapseOthers(card);
            card.classList.toggle('expanded', !isExpanded);
            card.setAttribute('aria-expanded', String(!isExpanded));
        });

        card.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();

            if (!isTouchOrSmallScreen.matches) {
                return;
            }

            const isExpanded = card.classList.contains('expanded');
            collapseOthers(card);
            card.classList.toggle('expanded', !isExpanded);
            card.setAttribute('aria-expanded', String(!isExpanded));
        });
    });

    document.addEventListener('click', function (event) {
        if (!isTouchOrSmallScreen.matches) {
            return;
        }

        if (event.target.closest('.beer-card')) {
            return;
        }

        collapseOthers(null);
    });
})();
</script>


<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
(function () {
    const mapElement = document.getElementById('breweryMap');
    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const locations = <?php echo json_encode($mapLocations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    const map = L.map(mapElement);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const beerIcon = L.icon({
        iconUrl: '/assets/images/map/beer_pin.png',
        iconSize: [42, 42],
        iconAnchor: [21, 42],
        popupAnchor: [0, -38]
    });

    const bounds = [];
    const markersById = {};

    locations.forEach(function (loc) {
        if (!loc.latitude || !loc.longitude) {
            return;
        }

        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
            return;
        }

        bounds.push([lat, lng]);

        const popupBeers = loc.beers_sold
            ? `<div class="popup-line"><strong>Beers:</strong> ${escapeHtml(loc.beers_sold)}</div>`
            : '';

        const popupHtml = `
            <div class="popup-title">${escapeHtml(loc.name)}</div>
            <div class="popup-line">${escapeHtml(loc.address)}</div>
            ${popupBeers}
        `;

        const marker = L.marker([lat, lng], { icon: beerIcon })
            .addTo(map)
            .bindPopup(popupHtml);

        markersById[String(loc.map_location_id)] = marker;
    });

    if (bounds.length === 1) {
        map.setView(bounds[0], 13);
    } else if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [50, 50] });
    } else {
        map.setView([34.3584, -86.2944], 10);
    }

    document.querySelectorAll('.location-button').forEach(function (button) {
        button.addEventListener('click', function () {
            const locationId = button.getAttribute('data-location-id');
            const marker = markersById[locationId];

            document.querySelectorAll('.location-button').forEach(function (otherButton) {
                otherButton.classList.remove('active');
                otherButton.setAttribute('aria-pressed', 'false');
            });

            button.classList.add('active');
            button.setAttribute('aria-pressed', 'true');

            if (!marker) {
                return;
            }

            map.setView(marker.getLatLng(), 15, { animate: true });
            marker.openPopup();
        });
    });

    setTimeout(function () {
        map.invalidateSize();
    }, 150);

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
</script>

<?php include 'footer.php'; ?>

