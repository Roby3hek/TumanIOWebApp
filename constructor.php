<?php
require_once 'config.php';

// Получаем список компонентов из базы
try {
    $stmt = $pdo->query("SELECT * FROM components ORDER BY category, price");
    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Группируем по категориям
    $components_by_category = [];
    foreach ($components as $component) {
        $components_by_category[$component['category']][] = $component;
    }
} catch (PDOException $e) {
    die("Ошибка загрузки компонентов: " . $e->getMessage());
}

// Обработка отправки конфигурации
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_config'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $selected_components = $_POST['components'] ?? [];
    
    if (!empty($name) && !empty($email) && !empty($phone)) {
        try {
            // Рассчитываем стоимость
            $base_price = 200000;
            $total_price = $base_price;
            $selected_components_data = [];
            
            foreach ($selected_components as $component_id) {
                $stmt = $pdo->prepare("SELECT * FROM components WHERE id = ?");
                $stmt->execute([$component_id]);
                $component = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($component) {
                    $total_price += $component['price'];
                    $selected_components_data[] = $component;
                }
            }
            
            // Сохраняем конфигурацию
            $stmt = $pdo->prepare("INSERT INTO configurations (name, email, phone, base_price, total_price, components) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $name, 
                $email, 
                $phone, 
                $base_price, 
                $total_price, 
                json_encode($selected_components_data, JSON_UNESCAPED_UNICODE)
            ]);
            
            $success_message = "Конфигурация успешно отправлена! Мы свяжемся с вами в ближайшее время.";
            
        } catch (PDOException $e) {
            $error_message = "Ошибка при сохранении конфигурации: " . $e->getMessage();
        }
    } else {
        $error_message = "Пожалуйста, заполните все обязательные поля.";
    }
}
?>