<?php
session_start();

// Включаем отображение ошибок для отладки (потом можно убрать)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/../helpers/db.php';
$pdo = db_connect();

// ---------- Обработка отзыва ----------
$reviewSuccess = false;
$reviewError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $stars     = (int)($_POST['stars']      ?? 0);
    $comment   = trim($_POST['comment']     ?? '');

    if ($requestId <= 0) {
        $reviewError = 'Некорректный ID записи';
    } elseif ($stars < 1 || $stars > 5) {
        $reviewError = 'Оценка должна быть от 1 до 5';
    } elseif ($comment === '') {
        $reviewError = 'Введите комментарий';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT otziv_id FROM zayvka WHERE id_zayavki = :id AND polzovatelya_id = :user_id");
            $stmt->execute([':id' => $requestId, ':user_id' => $_SESSION['user_id']]);
            $row = $stmt->fetch();

            if (!$row) {
                $reviewError = 'Запись не найдена';
            } else {
                $existingOtzivId = $row['otziv_id'];

                if (!empty($existingOtzivId)) {
                    // Обновляем существующий отзыв
                    $stmt = $pdo->prepare("UPDATE otziv SET coment = :coment, zvezda = :zvezda WHERE id_otziv = :id");
                    $stmt->execute([
                        ':coment' => $comment,
                        ':zvezda' => $stars,
                        ':id'     => (int)$existingOtzivId
                    ]);
                } else {
                    // Создаём новый отзыв
                    $stmt = $pdo->prepare("INSERT INTO otziv (coment, zvezda) VALUES (:coment, :zvezda)");
                    $stmt->execute([
                        ':coment' => $comment,
                        ':zvezda' => $stars
                    ]);
                    $newOtzivId = (int)$pdo->lastInsertId();

                    if ($newOtzivId > 0) {
                        $stmt = $pdo->prepare("UPDATE zayvka SET otziv_id = :otziv_id WHERE id_zayavki = :id");
                        $stmt->execute([':otziv_id' => $newOtzivId, ':id' => $requestId]);
                    } else {
                        throw new PDOException('Не удалось создать отзыв');
                    }
                }
                $reviewSuccess = true;
            }
        } catch (PDOException $e) {
            $reviewError = 'Ошибка БД: ' . $e->getMessage();
        }
    }
}

// ---------- Фильтр и пагинация ----------
$perPage      = 3;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;
$filterStatus = (int)($_GET['status'] ?? 0);

$where = "WHERE z.polzovatelya_id = :user_id";
$params = [':user_id' => $_SESSION['user_id']];

if ($filterStatus > 0) {
    $where .= " AND z.status_id = :status_id";
    $params[':status_id'] = $filterStatus;
}

$countSql  = "SELECT COUNT(*) AS cnt FROM zayvka z $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total      = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

$sql = "SELECT 
            z.id_zayavki, 
            z.otziv_id,
            zad.zadanie AS zadanie_name, 
            s.status AS status_name,
            s.id_statusi AS status_id,
            z.data_zapisi, 
            z.kolichestvo_chasov, 
            z.soderzhanie,
            ot.coment AS review_text, 
            ot.zvezda AS review_stars
        FROM zayvka z
        LEFT JOIN zadania zad ON z.zadaniya_id = zad.id_zadaniya
        LEFT JOIN statusi s ON z.status_id = s.id_statusi
        LEFT JOIN otziv ot ON z.otziv_id = ot.id_otziv
        $where
        ORDER BY z.id_zayavki DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$statusi = $pdo->query('SELECT id_statusi, status FROM statusi ORDER BY id_statusi')->fetchAll();

$title       = 'Дневник практики — Мои записи';
$page_title  = 'Просмотр записей дневника';
$breadcrumbs = ['Главная' => 'index.php', 'Просмотр записей' => ''];
$nav_buttons = [
    ['title' => 'Выход',   'url' => 'logout.php', 'active' => false],
    ['title' => 'Главная', 'url' => 'index.php',  'active' => false],
];

ob_start();
?>

<section class="requests-page">

    <div class="requests-header">
        <a href="new_zayvka.php" class="btn btn-primary">Создать запись</a>
    </div>

    <?php if ($reviewSuccess): ?>
        <div class="success-message">✅ Отзыв сохранён!</div>
    <?php endif; ?>
    <?php if ($reviewError): ?>
        <div class="error-message">❌ <?= htmlspecialchars($reviewError) ?></div>
    <?php endif; ?>

    <div class="filter-bar">
        <form method="GET" action="zayvki.php" class="filter-form">
            <label for="statusFilter">Фильтр по статусу:</label>
            <select name="status" id="statusFilter" onchange="this.form.submit()">
                <option value="0" <?= $filterStatus === 0 ? 'selected' : '' ?>>Все статусы</option>
                <?php foreach ($statusi as $st): ?>
                    <option value="<?= (int)$st['id_statusi'] ?>"
                        <?= $filterStatus === (int)$st['id_statusi'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['status']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <p>Записей не найдено</p>
            <a href="new_zayvka.php" class="link-primary">Создать первую</a>
        </div>
    <?php else: ?>
        <div class="requests-grid">
            <?php foreach ($requests as $req): ?>
                <?php
                $hasReview = !empty($req['otziv_id']) && $req['review_text'] !== null && $req['review_text'] !== '';
                ?>
                <div class="request-card">
                    <div class="request-card-header">
                        <h3>Запись №<?= (int)$req['id_zayavki'] ?></h3>
                        <span class="status-badge status-user-<?= (int)$req['status_id'] ?>">
                            <?= htmlspecialchars($req['status_name'] ?? '—') ?>
                        </span>
                    </div>
                    <div class="request-card-body">
                        <div class="request-row">
                            <span class="label">Дата:</span>
                            <span class="value"><?= htmlspecialchars($req['data_zapisi'] ?? '—') ?></span>
                        </div>
                        <div class="request-row">
                            <span class="label">Задание:</span>
                            <span class="value"><?= htmlspecialchars($req['zadanie_name'] ?? 'Не указано') ?></span>
                        </div>
                        <div class="request-row">
                            <span class="label">Содержание работы:</span>
                            <span class="value"><?= htmlspecialchars($req['soderzhanie'] ?? 'Не указано') ?></span>
                        </div>
                        <div class="request-row">
                            <span class="label">Часы:</span>
                            <span class="value"><?= (int)$req['kolichestvo_chasov'] ?></span>
                        </div>
                        <?php if ((int)$req['status_id'] === 1): ?>
                            <div class="request-row">
                                <span class="label">Отзыв:</span>
                                <?php if ($hasReview): ?>
                                    <span class="value">
                                        <?= htmlspecialchars($req['review_text']) ?>
                                        (<?= (int)$req['review_stars'] ?> ⭐)
                                    </span>
                                <?php else: ?>
                                    <span class="value text-muted">Не оставлен</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="request-card-footer">
                        <?php if ((int)$req['status_id'] === 1): ?>
                            <button type="button" class="btn-review"
                                    onclick="openReviewModal(<?= (int)$req['id_zayavki'] ?>, '<?= addslashes($req['review_text'] ?? '') ?>', <?= (int)($req['review_stars'] ?? 5) ?>)">
                                <?= $hasReview ? 'Изменить отзыв' : 'Оставить отзыв' ?>
                            </button>
                        <?php else: ?>
                            <span class="text-muted">Отзыв доступен после утверждения</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $filterStatus ?>" class="page-link">&lt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&lt;</span>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $filterStatus ?>"
                       class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $filterStatus ?>" class="page-link">&gt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&gt;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</section>

<!-- Модальное окно отзыва -->
<div id="reviewModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h3>Оставить отзыв</h3>
        <form method="POST" action="zayvki.php">
            <input type="hidden" name="request_id" id="reviewRequestId" value="">

            <div class="form-group">
                <label>Оценка (1-5):</label>
                <input type="number" name="stars" id="reviewStars"
                       min="1" max="5" step="1" value="5" required
                       style="width: 80px; padding: 8px 12px; font-size: 16px; text-align: center;">
            </div>

            <div class="form-group">
                <label>Комментарий:</label>
                <textarea name="comment" id="reviewComment" rows="4" placeholder="Ваш отзыв..."></textarea>
            </div>

            <div class="modal-buttons">
                <button type="submit" name="submit_review" class="btn btn-primary">Сохранить</button>
                <button type="button" class="btn" onclick="closeReviewModal()">Отмена</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(requestId, comment, stars) {
    document.getElementById('reviewRequestId').value = requestId;
    document.getElementById('reviewComment').value = comment;
    document.getElementById('reviewStars').value = stars || 5;
    document.getElementById('reviewModal').style.display = 'flex';
}
function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}
document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';