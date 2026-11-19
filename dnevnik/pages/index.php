<?php
session_start();

$title       = 'Дневник практики — Главная';
$page_title  = '';
$breadcrumbs = ['Главная' => ''];

if (!empty($_SESSION['user_id'])) {
    $nav_buttons = [
        ['title' => 'Мои записи', 'url' => 'zayvki.php',  'active' => false],
        ['title' => 'Выход',      'url' => 'logout.php',  'active' => false],
    ];
} else {
    $nav_buttons = [
        ['title' => 'Регистрация', 'url' => 'register.php', 'active' => false],
        ['title' => 'Вход',        'url' => 'auth.php',     'active' => false],
    ];
}

ob_start();
?>

<section class="hero">
    <div class="hero-inner">
        <h2 class="hero-title">Портал «Дневник практики»</h2>
        <p class="hero-text">
            Портал «Дневник практики» представляет собой информационную систему
            для ведения электронного дневника производственной практики студентами техникума.
        </p>

        <div class="slider" id="mainSlider">
            <div class="slider-track" id="sliderTrack">
                <img src="/DM-DNEVNIK/dnevnik/media/1slaid.jpg" alt="Слайд 1">
                <img src="/DM-DNEVNIK/dnevnik/media/2slaid.jpg" alt="Слайд 2">
                <img src="/DM-DNEVNIK/dnevnik/media/3slaid.jpg" alt="Слайд 3">
                <img src="/DM-DNEVNIK/dnevnik/media/4slaid.jpg" alt="Слайд 4">
            </div>

            <div class="slider-dots" id="sliderDots">
                <span class="dot active" data-slide="0"></span>
                <span class="dot" data-slide="1"></span>
                <span class="dot" data-slide="2"></span>
                <span class="dot" data-slide="3"></span>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';