<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_config'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $components = json_decode($_POST['components'] ?? '[]', true);
    $base_price = $_POST['base_price'] ?? 200000;
    $total_price = $_POST['total_price'] ?? 200000;

    if (empty($name) || empty($email) || empty($phone)) {
        http_response_code(400);
        echo "Пожалуйста, заполните все обязательные поля.";
        exit;
    }

    try {
        // Получаем информацию о выбранных компонентах
        $components_data = [];
        if (!empty($components)) {
            $placeholders = str_repeat('?,', count($components) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM components WHERE id IN ($placeholders)");
            $stmt->execute($components);
            $components_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Сохраняем конфигурацию
        $stmt = $pdo->prepare("INSERT INTO configurations (name, email, phone, base_price, total_price, components) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name,
            $email,
            $phone,
            $base_price,
            $total_price,
            json_encode($components_data, JSON_UNESCAPED_UNICODE)
        ]);

        // Здесь можно добавить отправку email уведомления
        echo "success";

    } catch (PDOException $e) {
        http_response_code(500);
        echo "Ошибка базы данных: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Метод не разрешен";
}
?>