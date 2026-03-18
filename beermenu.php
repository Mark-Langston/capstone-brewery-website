<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$stmt = $pdo->query("
    SELECT inventory_id, item_name, abv, price, description, image_path
    FROM inventory
    ORDER BY created_at DESC, inventory_id DESC
");
$inventoryItems = $stmt->fetchAll();

include 'header.php';
?>

<main class="beer-menu-page">
    <section class="beer-menu-intro">
        <h2>Beer Menu</h2>
        <p>Explore our current selection of beers on tap.</p>
    </section>

    <?php if (empty($inventoryItems)): ?>
        <section class="beer-menu-empty">
            <p>No beer menu items are available right now. Please check back soon.</p>
        </section>
    <?php else: ?>
        <section class="beer-menu-grid">
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
        </section>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
