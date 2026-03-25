<?php
require_once 'config.php';

// Временная отладка - включите для диагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Отладочная информация
    error_log("Login attempt - Username: $username, Password: $password");
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Отладочная информация
        error_log("Admin found: " . ($admin ? 'YES' : 'NO'));
        if ($admin) {
            error_log("Stored hash: " . $admin['password']);
            error_log("Password verify result: " . (password_verify($password, $admin['password']) ? 'TRUE' : 'FALSE'));
        }
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            error_log("Login successful for user: $username");
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = 'Неверное имя пользователя или пароль.';
            error_log("Login failed for user: $username");
        }
    } catch (Exception $e) {
        $error = 'Ошибка базы данных: ' . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в панель администратора - AutoAroma</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: var(--surface);
            padding: 50px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border);
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
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

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
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
            width: 100%;
            justify-content: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(139, 92, 246, 0.4);
        }

        .error {
            color: #EF4444;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #FEE2E2;
            border-radius: var(--radius);
        }

        .debug-info {
            background: #EFF6FF;
            padding: 15px;
            border-radius: var(--radius);
            margin-top: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Вход в панель администратора</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Имя пользователя</label>
                <input type="text" id="username" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" value="admin123" required>
            </div>
            <button type="submit" class="btn">Войти</button>
        </form>
        
    </div>
</body>
</html>