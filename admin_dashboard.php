<?php
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Параметры сортировки
$sort_field = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'desc';

// Валидация поля сортировки
$allowed_fields = ['id', 'name', 'phone', 'email', 'business_type', 'status', 'created_at'];
if (!in_array($sort_field, $allowed_fields)) {
    $sort_field = 'id';
}

// Валидация порядка сортировки
if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'desc';
}

// Определение следующего порядка сортировки
$next_order = $sort_order === 'asc' ? 'desc' : 'asc';

// Получение заявок с сортировкой
$stmt = $pdo->prepare("SELECT * FROM requests ORDER BY $sort_field $sort_order");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка добавления новой заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_request'])) {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $business_type = $_POST['business_type'] ?? '';
    $message = $_POST['message'] ?? '';
    $status = $_POST['status'] ?? 'новый';

    if (!empty($name) && !empty($phone) && !empty($email)) {
        $stmt = $pdo->prepare("INSERT INTO requests (name, phone, email, business_type, message, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $business_type, $message, $status]);
        header('Location: admin_dashboard.php?sort=' . $sort_field . '&order=' . $sort_order);
        exit;
    }
}

// Обработка редактирования заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_request'])) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $business_type = $_POST['business_type'] ?? '';
    $message = $_POST['message'] ?? '';
    $status = $_POST['status'] ?? 'новый';

    if (!empty($id) && !empty($name) && !empty($phone) && !empty($email)) {
        $stmt = $pdo->prepare("UPDATE requests SET name = ?, phone = ?, email = ?, business_type = ?, message = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $email, $business_type, $message, $status, $id]);
        header('Location: admin_dashboard.php?sort=' . $sort_field . '&order=' . $sort_order);
        exit;
    }
}

// Обработка удаления заявки
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin_dashboard.php?sort=' . $sort_field . '&order=' . $sort_order);
    exit;
}

// Получение данных для редактирования
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Обработка смены пароля
$password_change_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Проверка текущего пароля
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($current_password, $admin['password'])) {
        if ($new_password === $confirm_password) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $_SESSION['admin_id']]);
            $password_change_message = 'success';
        } else {
            $password_change_message = 'Новые пароли не совпадают.';
        }
    } else {
        $password_change_message = 'Текущий пароль неверен.';
    }
}

// Обработка изменения статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $request_id]);
    header('Location: admin_dashboard.php?sort=' . $sort_field . '&order=' . $sort_order);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - AutoAroma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B5CF6;
            --primary-light: #A78BFA;
            --primary-dark: #7C3AED;
            --background: #F8FAFC;
            --surface: #FFFFFF;
            --text-primary: #1E293B;
            --text-secondary: #64748B;
            --border: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            color: var(--text-primary);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .admin-header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px 0 rgba(139, 92, 246, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(139, 92, 246, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-primary);
            box-shadow: none;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-success {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .btn-danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
        }

        .btn-warning {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .btn-info {
            background: linear-gradient(135deg, #06B6D4, #0891B2);
        }

        main {
            margin-top: 100px;
            padding: 40px 0;
        }

        .section-title {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section-title h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
            background: linear-gradient(135deg, var(--text-primary), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
            position: relative;
            transition: var(--transition);
        }

        th:hover {
            background: var(--primary-dark);
        }

        .sortable::after {
            content: '';
            display: inline-block;
            margin-left: 8px;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            opacity: 0.6;
        }

        .sort-asc::after {
            border-bottom: 5px solid white;
        }

        .sort-desc::after {
            border-top: 5px solid white;
        }

        tr:hover {
            background: #F8FAFC;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-new { background: #DBEAFE; color: #1E40AF; }
        .status-consulted { background: #D1FAE5; color: #065F46; }
        .status-cancelled { background: #FEE2E2; color: #991B1B; }
        .status-ordered { background: #FEF3C7; color: #92400E; }
        .status-completed { background: #E0E7FF; color: #3730A3; }

        .action-buttons-small {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: var(--surface);
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--radius);
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow);
            position: relative;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .close:hover {
            color: var(--text-primary);
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .message {
            padding: 12px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .message.error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .modal-title {
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            th, td {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons-small {
                flex-direction: column;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }
            
            .admin-header-actions {
                flex-direction: column;
                gap: 10px;
                align-items: flex-end;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.html" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-wind"></i>
                    </div>
                    AutoAroma - Админ панель
                </a>
                <div class="admin-header-actions">
                    <button class="btn btn-info" onclick="openPasswordModal()">
                        <i class="fas fa-key"></i>Сменить пароль
                    </button>
                    <span>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="btn-outline btn">Выйти</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="section-title">
                <h2>Управление заявками</h2>
                <p>Текущая сортировка: <?php echo $sort_field . ' (' . $sort_order . ')'; ?></p>
            </div>

            <div class="action-buttons">
                <button class="btn btn-success" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>Добавить заявку
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="sortable <?php echo $sort_field === 'id' ? 'sort-' . $sort_order : ''; ?>" 
                            onclick="sortTable('id', '<?php echo $sort_field === 'id' ? $next_order : 'desc'; ?>')">
                            ID
                        </th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Тип бизнеса</th>
                        <th>Сообщение</th>
                        <th class="sortable <?php echo $sort_field === 'created_at' ? 'sort-' . $sort_order : ''; ?>" 
                            onclick="sortTable('created_at', '<?php echo $sort_field === 'created_at' ? $next_order : 'desc'; ?>')">
                            Дата
                        </th>
                        <th class="sortable <?php echo $sort_field === 'status' ? 'sort-' . $sort_order : ''; ?>" 
                            onclick="sortTable('status', '<?php echo $sort_field === 'status' ? $next_order : 'asc'; ?>')">
                            Статус
                        </th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo $request['id']; ?></td>
                        <td><?php echo htmlspecialchars($request['name']); ?></td>
                        <td><?php echo htmlspecialchars($request['phone']); ?></td>
                        <td><?php echo htmlspecialchars($request['email']); ?></td>
                        <td><?php echo htmlspecialchars($request['business_type']); ?></td>
                        <td title="<?php echo htmlspecialchars($request['message']); ?>">
                            <?php echo strlen($request['message']) > 50 ? substr($request['message'], 0, 50) . '...' : $request['message']; ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                <?php 
                                $status_labels = [
                                    'новый' => 'Новый',
                                    'проконсультирован' => 'Проконсультирован',
                                    'отменен' => 'Отменен',
                                    'заказан' => 'Заказан',
                                    'выполнен' => 'Выполнен'
                                ];
                                echo $status_labels[$request['status']];
                                ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons-small">
                                <button class="btn btn-warning btn-small" onclick="openEditModal(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $request['id']; ?>&sort=<?php echo $sort_field; ?>&order=<?php echo $sort_order; ?>" 
                                   class="btn btn-danger btn-small" 
                                   onclick="return confirm('Вы уверены, что хотите удалить эту заявку?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; border: 1px solid var(--border); font-size: 0.8rem;">
                                        <option value="новый" <?php echo $request['status'] == 'новый' ? 'selected' : ''; ?>>Новый</option>
                                        <option value="проконсультирован" <?php echo $request['status'] == 'проконсультирован' ? 'selected' : ''; ?>>Проконсультирован</option>
                                        <option value="отменен" <?php echo $request['status'] == 'отменен' ? 'selected' : ''; ?>>Отменен</option>
                                        <option value="заказан" <?php echo $request['status'] == 'заказан' ? 'selected' : ''; ?>>Заказан</option>
                                        <option value="выполнен" <?php echo $request['status'] == 'выполнен' ? 'selected' : ''; ?>>Выполнен</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="sort" value="<?php echo $sort_field; ?>">
                                    <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Модальное окно добавления -->
            <div id="addModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeAddModal()">&times;</span>
                    <h3 class="modal-title">Добавить новую заявку</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="add_name">Имя *</label>
                            <input type="text" id="add_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="add_phone">Телефон *</label>
                            <input type="text" id="add_phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="add_email">Email *</label>
                            <input type="email" id="add_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="add_business_type">Тип бизнеса</label>
                            <select id="add_business_type" name="business_type">
                                <option value="carwash">Автомойка</option>
                                <option value="service">Автосервис</option>
                                <option value="investor">Инвестор</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_message">Сообщение</label>
                            <textarea id="add_message" name="message"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="add_status">Статус</label>
                            <select id="add_status" name="status">
                                <option value="новый">Новый</option>
                                <option value="проконсультирован">Проконсультирован</option>
                                <option value="отменен">Отменен</option>
                                <option value="заказан">Заказан</option>
                                <option value="выполнен">Выполнен</option>
                            </select>
                        </div>
                        <button type="submit" name="add_request" class="btn btn-success">Добавить заявку</button>
                    </form>
                </div>
            </div>

            <!-- Модальное окно редактирования -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeEditModal()">&times;</span>
                    <h3 class="modal-title">Редактировать заявку</h3>
                    <form method="POST">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_name">Имя *</label>
                            <input type="text" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Телефон *</label>
                            <input type="text" id="edit_phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email *</label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_business_type">Тип бизнеса</label>
                            <select id="edit_business_type" name="business_type">
                                <option value="carwash">Автомойка</option>
                                <option value="service">Автосервис</option>
                                <option value="investor">Инвестор</option>
                                <option value="other">Другое</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_message">Сообщение</label>
                            <textarea id="edit_message" name="message"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Статус</label>
                            <select id="edit_status" name="status">
                                <option value="новый">Новый</option>
                                <option value="проконсультирован">Проконсультирован</option>
                                <option value="отменен">Отменен</option>
                                <option value="заказан">Заказан</option>
                                <option value="выполнен">Выполнен</option>
                            </select>
                        </div>
                        <button type="submit" name="edit_request" class="btn btn-warning">Сохранить изменения</button>
                    </form>
                </div>
            </div>

            <!-- Модальное окно смены пароля -->
            <div id="passwordModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closePasswordModal()">&times;</span>
                    <h3 class="modal-title">Смена пароля</h3>
                    
                    <?php if ($password_change_message): ?>
                        <div class="message <?php echo $password_change_message === 'success' ? 'success' : 'error'; ?>">
                            <?php echo $password_change_message === 'success' ? 'Пароль успешно изменен!' : $password_change_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Подтвердите новый пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn">Сменить пароль</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function sortTable(field, order) {
            window.location.href = `admin_dashboard.php?sort=${field}&order=${order}`;
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(id) {
            window.location.href = `admin_dashboard.php?edit=${id}&sort=<?php echo $sort_field; ?>&order=<?php echo $sort_order; ?>`;
        }

        function closeEditModal() {
            window.location.href = `admin_dashboard.php?sort=<?php echo $sort_field; ?>&order=<?php echo $sort_order; ?>`;
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
            // Очищаем форму при открытии
            document.querySelector('#passwordModal form').reset();
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            // Очищаем сообщения при закрытии
            const messages = document.querySelectorAll('#passwordModal .message');
            messages.forEach(message => message.remove());
        }

        // Автоматическое открытие модальных окон при наличии данных
        <?php if ($edit_data): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('edit_id').value = '<?php echo $edit_data['id']; ?>';
            document.getElementById('edit_name').value = '<?php echo addslashes($edit_data['name']); ?>';
            document.getElementById('edit_phone').value = '<?php echo addslashes($edit_data['phone']); ?>';
            document.getElementById('edit_email').value = '<?php echo addslashes($edit_data['email']); ?>';
            document.getElementById('edit_business_type').value = '<?php echo addslashes($edit_data['business_type']); ?>';
            document.getElementById('edit_message').value = '<?php echo addslashes($edit_data['message']); ?>';
            document.getElementById('edit_status').value = '<?php echo addslashes($edit_data['status']); ?>';
            document.getElementById('editModal').style.display = 'block';
        });
        <?php endif; ?>

        <?php if ($password_change_message && $password_change_message !== 'success'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('passwordModal').style.display = 'block';
        });
        <?php endif; ?>

        // Закрытие модальных окон при клике вне их
        window.onclick = function(event) {
            const modals = ['addModal', 'editModal', 'passwordModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    if (modalId === 'editModal') {
                        closeEditModal();
                    } else if (modalId === 'passwordModal') {
                        closePasswordModal();
                    } else {
                        modal.style.display = 'none';
                    }
                }
            });
        }

        // Автоматическое скрытие сообщений через 5 секунд
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);

        // Автоматическое закрытие модального окна пароля при успешной смене
        <?php if ($password_change_message === 'success'): ?>
        setTimeout(() => {
            closePasswordModal();
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>