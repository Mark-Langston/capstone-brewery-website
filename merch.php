<?php include 'header.php'; ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$stmt = $pdo->query("
    SELECT merch_id, name, price, image_path
    FROM merch
    ORDER BY created_at DESC, merch_id DESC
");
$merchItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merch - Main Channel Brewing</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            color: #222;
        }

        .merch-section {
            max-width: 1300px;
            margin: 0 auto;
            padding: 70px 20px 90px;
        }

        .merch-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .merch-header h1 {
            margin: 0 0 18px;
            font-size: 46px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 400;
            font-family: Georgia, "Times New Roman", serif;
        }

        .merch-header p {
            max-width: 760px;
            margin: 0 auto;
            color: #d55a3a;
            line-height: 1.8;
            font-size: 16px;
        }

        .carousel-shell {
            position: relative;
            overflow: hidden;
            background: #ffffff;
        }

        .merch-carousel {
            overflow: hidden;
            background: #ffffff;
        }

        .merch-track {
            display: flex;
            transition: transform 0.6s ease;
            will-change: transform;
            background: #ffffff;
        }

        .merch-slide {
            min-width: 100%;
            flex: 0 0 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 50px;
            min-height: 500px;
            padding: 10px 70px 50px;
            background: #ffffff;
        }

        .merch-image-panel {
            flex: 1 1 52%;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 360px;
            background: #ffffff;
        }

        .merch-image-wrap {
            width: 100%;
            max-width: 560px;
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .merch-image-wrap img {
            max-width: 100%;
            max-height: 420px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            border: none;
            box-shadow: none;
            background: transparent;
        }

        .merch-no-image {
            width: 100%;
            max-width: 420px;
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #999;
            font-size: 16px;
        }

        .merch-content-panel {
            flex: 0 1 360px;
            display: flex;
            justify-content: center;
            background: #ffffff;
        }

        .merch-content-box {
            width: 100%;
            max-width: 320px;
            border: 1px solid #e48f79;
            background: #ffffff;
            padding: 22px 18px;
        }

        .merch-content-box h2 {
            margin: 0 0 14px;
            font-size: 22px;
            line-height: 1.35;
            font-weight: 400;
            font-family: Georgia, "Times New Roman", serif;
        }

        .merch-price {
            margin: 0 0 14px;
            color: #d85429;
            font-size: 22px;
            font-weight: bold;
        }

        .merch-copy {
            margin: 0;
            line-height: 1.85;
            font-size: 15px;
            color: #333;
        }

        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 52px;
            height: 52px;
            border: none;
            background: transparent;
            color: #e0562b;
            font-size: 56px;
            line-height: 1;
            cursor: pointer;
            z-index: 5;
            padding: 0;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .carousel-arrow:hover,
        .carousel-arrow:focus {
            color: #b8411e;
            outline: none;
        }

        .carousel-arrow.prev {
            left: 0;
        }

        .carousel-arrow.next {
            right: 0;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .carousel-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: none;
            background: #de8b72;
            opacity: 0.55;
            cursor: pointer;
            padding: 0;
        }

        .carousel-dot.active {
            opacity: 1;
            background: #d85429;
        }

        .merch-empty {
            text-align: center;
            background: #fff;
            border-radius: 10px;
            padding: 40px 20px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 980px) {
            .merch-header h1 {
                font-size: 38px;
            }

            .merch-slide {
                gap: 30px;
                padding: 10px 45px 40px;
            }

            .merch-content-panel {
                flex-basis: 300px;
            }
        }

        @media (max-width: 780px) {
            .merch-section {
                padding: 50px 16px 70px;
            }

            .merch-header h1 {
                font-size: 32px;
            }

            .merch-slide {
                flex-direction: column;
                gap: 22px;
                padding: 10px 36px 40px;
                min-height: auto;
            }

            .merch-image-panel,
            .merch-content-panel {
                width: 100%;
                flex: 1 1 auto;
            }

            .merch-content-box {
                max-width: 560px;
            }

            .merch-image-wrap {
                min-height: 260px;
            }

            .merch-image-wrap img {
                max-height: 320px;
            }

            .carousel-arrow {
                font-size: 46px;
                width: 42px;
                height: 42px;
            }
        }

        @media (max-width: 520px) {
            .merch-header h1 {
                font-size: 28px;
            }

            .merch-header p {
                font-size: 15px;
            }

            .merch-slide {
                padding: 0 26px 34px;
            }

            .merch-content-box {
                padding: 18px 15px;
            }

            .merch-content-box h2 {
                font-size: 18px;
            }

            .merch-price {
                font-size: 20px;
            }

            .merch-copy {
                font-size: 14px;
                line-height: 1.75;
            }

            .carousel-arrow {
                font-size: 38px;
            }
        }
    </style>
</head>
<body>

<section class="merch-section">
    <div class="merch-header">
        <h1>Merch</h1>
        <p>
            Browse Main Channel Brewing merchandise and featured items.
            Explore what is available and slide through each item to view its image and price.
        </p>
    </div>

    <?php if (empty($merchItems)): ?>
        <div class="merch-empty">
            <h2>No merch items are available right now.</h2>
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
                                    <h2><?= htmlspecialchars((string) $item['name'], ENT_QUOTES, 'UTF-8') ?></h2>
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
</section>

<?php if (!empty($merchItems)): ?>
<script>
    (function () {
        const slides = Array.from(document.querySelectorAll('.merch-slide'));
        const dots = Array.from(document.querySelectorAll('.carousel-dot'));
        const prevBtn = document.querySelector('.carousel-arrow.prev');
        const nextBtn = document.querySelector('.carousel-arrow.next');
        const carousel = document.getElementById('merchCarousel');
        const track = document.getElementById('merchTrack');

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
            intervalId = window.setInterval(nextSlide, delay);
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
            } else if (slides.length > 1) {
                startAutoRotate();
            }
        });

        showSlide(0);

        if (slides.length > 1) {
            startAutoRotate();
        }
    })();
</script>
<?php endif; ?>

</body>
</html>


<?php include 'footer.php'; ?>

