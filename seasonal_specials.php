<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$stmt = $pdo->query("
    SELECT seasonal_special_id, header_text, description, image_path
    FROM seasonal_specials
    ORDER BY created_at DESC, seasonal_special_id DESC
");
$seasonalSpecials = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seasonal Specials - Main Channel Brewing</title>

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

        .seasonal-section {
            max-width: 1300px;
            margin: 0 auto;
            padding: 70px 20px 90px;
        }

        .seasonal-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .seasonal-header h1 {
            margin: 0 0 18px;
            font-size: 46px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 400;
            font-family: Georgia, "Times New Roman", serif;
        }

        .seasonal-header p {
            max-width: 760px;
            margin: 0 auto;
            color: #d55a3a;
            line-height: 1.8;
            font-size: 16px;
        }

        .carousel-shell {
            position: relative;
            min-height: 520px;
        }

        .seasonal-carousel {
            position: relative;
            overflow: hidden;
        }

        .seasonal-slide {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 50px;
            min-height: 500px;
            padding: 10px 70px 50px;
        }

        .seasonal-slide.active {
            display: flex;
        }

        .seasonal-image-panel {
            flex: 1 1 52%;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 360px;
        }

        .seasonal-image-wrap {
            width: 100%;
            max-width: 560px;
            min-height: 360px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .seasonal-image-wrap img {
            max-width: 100%;
            max-height: 420px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .seasonal-no-image {
            width: 100%;
            max-width: 420px;
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e6c6bc;
            background: #faf7f6;
            color: #999;
            font-size: 16px;
        }

        .seasonal-content-panel {
            flex: 0 1 360px;
            display: flex;
            justify-content: center;
        }

        .seasonal-content-box {
            width: 100%;
            max-width: 320px;
            border: 1px solid #e48f79;
            background: rgba(255, 255, 255, 0.6);
            padding: 22px 18px;
        }

        .seasonal-content-box h2 {
            margin: 0 0 14px;
            font-size: 20px;
            line-height: 1.35;
            font-weight: 400;
            font-family: Georgia, "Times New Roman", serif;
        }

        .seasonal-content-box p {
            margin: 0;
            line-height: 1.95;
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

        .seasonal-empty {
            text-align: center;
            background: #fff;
            border-radius: 10px;
            padding: 40px 20px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 980px) {
            .seasonal-header h1 {
                font-size: 38px;
            }

            .seasonal-slide {
                gap: 30px;
                padding: 10px 45px 40px;
            }

            .seasonal-content-panel {
                flex-basis: 300px;
            }
        }

        @media (max-width: 780px) {
            .seasonal-section {
                padding: 50px 16px 70px;
            }

            .seasonal-header h1 {
                font-size: 32px;
            }

            .seasonal-slide {
                flex-direction: column;
                gap: 22px;
                padding: 10px 36px 40px;
                min-height: auto;
            }

            .seasonal-image-panel,
            .seasonal-content-panel {
                width: 100%;
                flex: 1 1 auto;
            }

            .seasonal-content-box {
                max-width: 560px;
            }

            .seasonal-image-wrap {
                min-height: 260px;
            }

            .seasonal-image-wrap img {
                max-height: 320px;
            }

            .carousel-arrow {
                font-size: 46px;
                width: 42px;
                height: 42px;
            }
        }

        @media (max-width: 520px) {
            .seasonal-header h1 {
                font-size: 28px;
            }

            .seasonal-header p {
                font-size: 15px;
            }

            .seasonal-slide {
                padding: 0 26px 34px;
            }

            .seasonal-content-box {
                padding: 18px 15px;
            }

            .seasonal-content-box h2 {
                font-size: 18px;
            }

            .seasonal-content-box p {
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

<section class="seasonal-section">
    <div class="seasonal-header">
        <h1>Seasonal Specials</h1>
        <p>
            Explore our seasonal and new release brews, crafted to embrace the spirit of each season.
            From bright, refreshing ales to richer, limited-time creations, each pour brings a fresh taste of now.
        </p>
    </div>

    <?php if (empty($seasonalSpecials)): ?>
        <div class="seasonal-empty">
            <h2>No seasonal specials are available right now.</h2>
            <p>Please check back again soon.</p>
        </div>
    <?php else: ?>
        <div class="carousel-shell">
            <button class="carousel-arrow prev" type="button" aria-label="Previous seasonal special">&#8249;</button>
            <button class="carousel-arrow next" type="button" aria-label="Next seasonal special">&#8250;</button>

            <div class="seasonal-carousel" id="seasonalCarousel">
                <?php foreach ($seasonalSpecials as $index => $item): ?>
                    <div class="seasonal-slide<?= $index === 0 ? ' active' : '' ?>">
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
                                <h2><?= htmlspecialchars((string) $item['header_text'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p><?= nl2br(htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="carousel-dots" id="carouselDots">
                <?php foreach ($seasonalSpecials as $index => $item): ?>
                    <button
                        class="carousel-dot<?= $index === 0 ? ' active' : '' ?>"
                        type="button"
                        aria-label="Go to seasonal special <?= $index + 1 ?>"
                        data-slide-index="<?= $index ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($seasonalSpecials)): ?>
<script>
    (function () {
        const slides = Array.from(document.querySelectorAll('.seasonal-slide'));
        const dots = Array.from(document.querySelectorAll('.carousel-dot'));
        const prevBtn = document.querySelector('.carousel-arrow.prev');
        const nextBtn = document.querySelector('.carousel-arrow.next');
        const carousel = document.getElementById('seasonalCarousel');

        let currentIndex = 0;
        let intervalId = null;
        const delay = 5000;

        function showSlide(index) {
            slides[currentIndex].classList.remove('active');
            dots[currentIndex].classList.remove('active');

            currentIndex = (index + slides.length) % slides.length;

            slides[currentIndex].classList.add('active');
            dots[currentIndex].classList.add('active');
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
            } else {
                startAutoRotate();
            }
        });

        if (slides.length > 1) {
            startAutoRotate();
        }
    })();
</script>
<?php endif; ?>

</body>
</html>
