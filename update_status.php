<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo 'Не авторизован';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';

    $allowed_statuses = ['новый', 'проконсультирован', 'отменен', 'заказан', 'выполнен'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo 'Неверный статус';
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);
        echo 'OK';
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Ошибка базы данных: ' . $e->getMessage();
    }
} else {
    http_response_code(405);
    echo 'Метод не разрешен';
}
?>