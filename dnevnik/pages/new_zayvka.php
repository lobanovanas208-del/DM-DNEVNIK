<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/../helpers/db.php';
$pdo = db_connect();

$errors = [];
$success = false;

$zadanieId     = '';
$dataZapisi    = '';
$soderzhanie   = '';
$hours         = '';
$rukovoditel   = 0;

$zadania = $pdo->query('SELECT id_zadaniya, zadanie FROM zadania ORDER BY zadanie')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zadanieId     = (int)($_POST['zadanie_id']     ?? 0);
    $dataZapisi    = trim($_POST['data_zapisi']     ?? '');
    $soderzhanie   = trim($_POST['soderzhanie']     ?? '');
    $hours         = (int)($_POST['hours']          ?? 0);
    $rukovoditel   = (int)($_POST['rukovoditel']    ?? 0);

    // Валидация
    if ($zadanieId <= 0) $errors['zadanie_id'] = 'Выберите задание';
    if ($dataZapisi === '') $errors['data_zapisi'] = 'Укажите дату';
    if ($soderzhanie === '') $errors['soderzhanie'] = 'Введите содержание работы';
    if ($hours <= 0) $errors['hours'] = 'Укажите количество часов';

    if (empty($errors)) {
        try {
            // otziv_id = NULL (отзыва ещё нет)
            $stmt = $pdo->prepare("INSERT INTO zayvka 
                (polzovatelya_id, data_zapisi, zadaniya_id, soderzhanie, kolichestvo_chasov, status_id, otziv_id)
                VALUES (:user_id, :data_zapisi, :zadanie_id, :soderzhanie, :hours, 3, NULL)");
            $stmt->execute([
                ':user_id'      => $_SESSION['user_id'],
                ':data_zapisi'  => $dataZapisi,
                ':zadanie_id'   => $zadanieId,
                ':soderzhanie'  => $soderzhanie,
                ':hours'        => $hours,
            ]);

            $success = true;
            $zadanieId = $dataZapisi = $soderzhanie = $hours = '';
            $rukovoditel = 0;
        } catch (PDOException $e) {
            $errors['db'] = 'Ошибка БД: ' . $e->getMessage();
        }
    }
}

$title       = 'Дневник практики — Формирование записи';
$page_title  = 'Формирование записи';
$breadcrumbs = ['Главная' => 'index.php', 'Просмотр записей' => 'zayvki.php', 'Формирование записи' => ''];
$nav_buttons = [
    ['title' => 'Мои записи', 'url' => 'zayvki.php',  'active' => false],
    ['title' => 'Выход',      'url' => 'logout.php',  'active' => false],
    ['title' => 'Главная',    'url' => 'index.php',   'active' => false],
];

ob_start();
?>

<section class="new-request-page">
    <div class="form-wrapper">

        <?php if ($success): ?>
            <div class="success-message">
                <p>✅ Запись успешно создана!</p>
                <p><a href="zayvki.php">Просмотреть записи</a></p>
            </div>
        <?php else: ?>

        <?php if (!empty($errors['db'])): ?>
            <div class="error-message">❌ <?= htmlspecialchars($errors['db']) ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="new_zayvka.php" novalidate>

            <div class="form-group">
                <label for="data_zapisi">Дата записи (ДД.ММ.ГГГГ)</label>
                <input type="date" id="data_zapisi" name="data_zapisi"
                       value="<?= htmlspecialchars($dataZapisi) ?>"
                       class="<?= isset($errors['data_zapisi']) ? 'is-invalid' : '' ?>" required>
                <?php if (!empty($errors['data_zapisi'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['data_zapisi']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="zadanie_id">Задание</label>
                <select id="zadanie_id" name="zadanie_id"
                        class="<?= isset($errors['zadanie_id']) ? 'is-invalid' : '' ?>" required>
                    <option value="">-- Выберите задание --</option>
                    <?php foreach ($zadania as $z): ?>
                        <option value="<?= (int)$z['id_zadaniya'] ?>"
                            <?= (int)$zadanieId === (int)$z['id_zadaniya'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($z['zadanie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['zadanie_id'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['zadanie_id']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="soderzhanie">Содержание работы</label>
                <textarea id="soderzhanie" name="soderzhanie" rows="5"
                          placeholder="Опишите выполненную работу..."
                          class="<?= isset($errors['soderzhanie']) ? 'is-invalid' : '' ?>"
                          required><?= htmlspecialchars($soderzhanie) ?></textarea>
                <?php if (!empty($errors['soderzhanie'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['soderzhanie']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="hours">Количество часов</label>
                <input type="number" id="hours" name="hours" min="1" max="24"
                       value="<?= $hours ? htmlspecialchars($hours) : '' ?>"
                       class="<?= isset($errors['hours']) ? 'is-invalid' : '' ?>" required>
                <?php if (!empty($errors['hours'])): ?>
                    <span class="error-text"><?= htmlspecialchars($errors['hours']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="rukovoditel" value="1"
                        <?= $rukovoditel ? 'checked' : '' ?>>
                    Руководитель от организации ознакомился
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Отправить</button>

            <p class="auth-hint">
                Запись уже создана?<br>
                <a href="zayvki.php">Просмотреть</a>
            </p>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';