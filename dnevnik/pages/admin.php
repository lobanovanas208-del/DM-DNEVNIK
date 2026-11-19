<?php
session_start();

// Включаем отображение ошибок для отладки (можно закомментировать после проверки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка прав доступа (только администратор, role_id = 2)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 2) {
    header('Location: auth.php');
    exit;
}

require_once __DIR__ . '/../helpers/db.php';
$pdo = db_connect();

$successMsg = '';
$errorMsg   = '';

// ---------- Обработка POST-запросов ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Удаление записи
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        if ($deleteId > 0) {
            try {
                // Сначала узнаем, есть ли привязанный отзыв
                $stmt = $pdo->prepare("SELECT otziv_id FROM zayvka WHERE id_zayavki = :id");
                $stmt->execute([':id' => $deleteId]);
                $row = $stmt->fetch();

                // Удаляем саму запись
                $stmt = $pdo->prepare("DELETE FROM zayvka WHERE id_zayavki = :id");
                $stmt->execute([':id' => $deleteId]);

                // Если был отзыв, удаляем и его, чтобы не было мусора в БД
                if (!empty($row['otziv_id'])) {
                    $stmt = $pdo->prepare("DELETE FROM otziv WHERE id_otziv = :id");
                    $stmt->execute([':id' => (int)$row['otziv_id']]);
                }

                $successMsg = "Запись №{$deleteId} успешно удалена.";
            } catch (PDOException $e) {
                $errorMsg = 'Ошибка при удалении: ' . $e->getMessage();
            }
        }
    } 
    // 2. Смена статуса
    elseif (isset($_POST['request_id']) && isset($_POST['status_id'])) {
        $requestId = (int)$_POST['request_id'];
        $statusId  = (int)$_POST['status_id'];

        if ($requestId > 0 && $statusId > 0) {
            $stmt = $pdo->prepare("UPDATE zayvka SET status_id = :status_id WHERE id_zayavki = :id");
            $stmt->execute([':status_id' => $statusId, ':id' => $requestId]);
            $successMsg = "Статус записи №{$requestId} изменён.";
        } else {
            $errorMsg = 'Некорректные данные.';
        }
    }
}

// ---------- Фильтр и пагинация ----------
$perPage      = 5;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $perPage;
$filterStatus = (int)($_GET['status'] ?? 0);
$filterDate   = trim($_GET['date'] ?? '');

$where  = "";
$params = [];

if ($filterStatus > 0) {
    $where = "WHERE z.status_id = :status_id";
    $params[':status_id'] = $filterStatus;
}
if ($filterDate !== '') {
    $where .= ($where === "" ? "WHERE" : " AND") . " z.data_zapisi = :data_zapisi";
    $params[':data_zapisi'] = $filterDate;
}

// Счётчик записей для пагинации
$countSql  = "SELECT COUNT(*) AS cnt FROM zayvka z $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total      = (int)$countStmt->fetch()['cnt'];
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;

// ---------- Получение данных ----------
$sql = "SELECT z.id_zayavki, z.otziv_id,
               p.fio AS user_fio, p.login AS user_login,
               zad.zadanie AS zadanie_name,
               s.status AS status_name, s.id_statusi AS status_id,
               z.data_zapisi, z.kolichestvo_chasov,
               ot.coment AS review_text, ot.zvezda AS review_stars
        FROM zayvka z
        LEFT JOIN polzovateli p ON z.polzovatelya_id = p.id_polzovatelya
        LEFT JOIN zadania zad ON z.zadaniya_id = zad.id_zadaniya
        LEFT JOIN statusi s ON z.status_id = s.id_statusi
        LEFT JOIN otziv ot ON z.otziv_id = ot.id_otziv
        $where
        ORDER BY z.id_zayavki DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Получаем список всех статусов для фильтров и выпадающих списков
$statusi = $pdo->query('SELECT id_statusi, status FROM statusi ORDER BY id_statusi')->fetchAll();

// ---------- Данные для layout.php ----------
$title       = 'Дневник практики — Админ-панель';
$page_title  = 'Управление записями';
$breadcrumbs = ['Главная' => 'index.php', 'Админ-панель' => ''];
$nav_buttons = [
    ['title' => 'Главная', 'url' => 'index.php',  'active' => false],
    ['title' => 'Выход',   'url' => 'logout.php', 'active' => false],
];

ob_start();
?>

<section class="admin-page">

    <?php if ($successMsg): ?>
        <div class="success-message">✅ <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="error-message">❌ <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- Информация об администраторе -->
    <div class="admin-info">
        <p>Администратор: <strong><?= htmlspecialchars($_SESSION['user_fio']) ?></strong>
           (<?= htmlspecialchars($_SESSION['user_login']) ?>)</p>
        <p>Всего записей: <strong><?= $total ?></strong></p>
    </div>

    <!-- Фильтры -->
    <div class="filter-bar">
        <form method="GET" action="admin.php" class="filter-form">
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

            <label for="dateFilter">Фильтр по дате:</label>
            <input type="date" name="date" id="dateFilter"
                   value="<?= htmlspecialchars($filterDate) ?>"
                   onchange="this.form.submit()">
        </form>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state"><p>Записей не найдено</p></div>
    <?php else: ?>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Пользователь</th>
                        <th>Дата</th>
                        <th>Задание</th>
                        <th>Часы</th>
                        <th>Отзыв</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?= (int)$req['id_zayavki'] ?></td>
                            <td>
                                <?= htmlspecialchars($req['user_fio'] ?? '—') ?><br>
                                <small class="text-muted"><?= htmlspecialchars($req['user_login'] ?? '') ?></small>
                            </td>
                            <td><?= htmlspecialchars($req['data_zapisi'] ?? '—') ?></td>
                            
                            <!-- Только название задания, без содержания под ним -->
                            <td><?= htmlspecialchars($req['zadanie_name'] ?? '—') ?></td>
                            
                            <td><?= (int)$req['kolichestvo_chasov'] ?></td>
                            
                            <!-- Столбец ОТЗЫВ -->
                            <td>
                                <?php if (!empty($req['otziv_id']) && $req['review_text'] !== null && $req['review_text'] !== ''): ?>
                                    <div class="review-cell">
                                        <div class="review-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?= $i <= (int)$req['review_stars'] ? 'filled' : '' ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="review-text"><?= htmlspecialchars($req['review_text']) ?></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Столбец СТАТУС (смена) -->
                            <td>
                                <form method="POST" action="admin.php" class="status-form">
                                    <input type="hidden" name="request_id" value="<?= (int)$req['id_zayavki'] ?>">
                                    <select name="status_id">
                                        <?php foreach ($statusi as $st): ?>
                                            <option value="<?= (int)$st['id_statusi'] ?>"
                                                <?= (int)$req['status_id'] === (int)$st['id_statusi'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($st['status']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm">OK</button>
                                </form>
                            </td>
                            
                            <!-- Столбец ДЕЙСТВИЯ (удаление) -->
                            <td>
                                <form method="POST" action="admin.php" class="delete-form"
                                      onsubmit="return confirm('Вы уверены, что хотите удалить запись №<?= (int)$req['id_zayavki'] ?>?\nЭто действие нельзя отменить.');">
                                    <input type="hidden" name="delete_id" value="<?= (int)$req['id_zayavki'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Удалить запись">
                                        🗑 Удалить
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $filterStatus ?>&date=<?= urlencode($filterDate) ?>" class="page-link">&lt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&lt;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $filterStatus ?>&date=<?= urlencode($filterDate) ?>"
                       class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $filterStatus ?>&date=<?= urlencode($filterDate) ?>" class="page-link">&gt;</a>
                <?php else: ?>
                    <span class="page-link disabled">&gt;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</section>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';