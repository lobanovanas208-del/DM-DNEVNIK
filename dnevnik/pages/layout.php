<?php
if (!isset($title))       $title       = 'Дневник практики';
if (!isset($page_title))  $page_title  = '';
if (!isset($breadcrumbs)) $breadcrumbs = [];
if (!isset($nav_buttons)) $nav_buttons = [];
if (!isset($content))     $content     = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/DM-DNEVNIK/dnevnik/styles/style.css">
</head>
<body>

<header class="site-header">
    <div class="header-inner">
        <a href="/DM-DNEVNIK/dnevnik/pages/index.php" class="logo">
            <img src="/DM-DNEVNIK/dnevnik/media/Dnevnik-logo.jpg" alt="Логотип">
        </a>
        <h1 class="site-title">Дневник практики</h1>

        <button type="button" class="burger" id="burgerBtn" aria-label="Меню">
            <span></span><span></span><span></span>
        </button>

        <nav class="header-nav" id="headerNav">
            <?php foreach ($nav_buttons as $btn): ?>
                <a href="<?= htmlspecialchars($btn['url']) ?>"
                   class="nav-btn <?= !empty($btn['active']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($btn['title']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<?php if (!empty($breadcrumbs)): ?>
    <nav class="breadcrumbs">
        <?php
        $i = 0;
        $total = count($breadcrumbs);
        foreach ($breadcrumbs as $label => $url):
            $i++;
            $isLast = ($i === $total);
        ?>
            <?php if (!$isLast && $url !== ''): ?>
                <a href="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($label) ?></a>
            <?php else: ?>
                <span class="current"><?= htmlspecialchars($label) ?></span>
            <?php endif; ?>
            <?php if (!$isLast): ?> / <?php endif; ?>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if ($page_title !== ''): ?>
    <section class="page-title-bar"><h2><?= htmlspecialchars($page_title) ?></h2></section>
<?php endif; ?>

<main class="site-main"><?= $content ?></main>

<footer class="site-footer">
    <div class="footer-inner">
        <p>&copy; <?= date('Y') ?> Портал «Дневник практики» — электронный дневник производственной практики</p>
    </div>
</footer>

<script src="/DM-DNEVNIK/dnevnik/scripts/script.js"></script>
</body>
</html>