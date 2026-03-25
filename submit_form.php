<?php
require_once 'config.php';

// Логирование для отладки
error_log("Received POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $business_type = $_POST['business'] ?? ''; // исправлено с 'business_type' на 'business'
    $message = $_POST['message'] ?? '';

    // Валидация
    if (empty($name) || empty($phone) || empty($email) || empty($business_type)) {
        error_log("Validation failed: name=$name, phone=$phone, email=$email, business=$business_type");
        http_response_code(400);
        echo "Пожалуйста, заполните обязательные поля.";
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO requests (name, phone, email, business_type, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $email, $business_type, $message]);
        
        error_log("Data saved successfully: $name, $phone, $email, $business_type");
        echo "success";
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo "Ошибка при сохранении заявки: " . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo "Метод не разрешен";
}
?>