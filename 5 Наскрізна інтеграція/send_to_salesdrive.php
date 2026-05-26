<?php
// Вимикаємо вивід внутрішніх помилок PHP на екран
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ВАШ API-КЛЮЧ З SALESDRIVE
    $apiKey = "eysHVGgxZ9RenJzZ06VhL4gYafWnCH2ipn__BRVvMl6S5wHkb0-82SrnFtw1NGcD5i7qugBhOLPIpv97kIW5qbkvn8ZYkSGOUOyn"; 
    
    // Офіційний REST API URL для вашого акаунту
    $url = "https://salesdrive.ua";

    // Очищення даних з HTML-форми
    $name    = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
    $phone   = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
    $email   = isset($_POST['email']) ? trim(strip_tags($_POST['email'])) : '';
    $comment = isset($_POST['comment']) ? trim(strip_tags($_POST['comment'])) : '';

    // Формуємо масив даних за офіційним стандартом REST API SalesDrive
    $data = [
        'f_name'       => $name,
        'phone'        => $phone,
        'email'        => $email,
        'comment'      => $comment,
        'external_id'  => 'Форма з сайту VS Code',
        'getResultData'=> 1 // Просимо сервер повернути ID створеної заявки
    ];

    // Ініціалізація cURL запиту
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Передаємо заголовок X-API-Key
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Відображення результату
    if ($httpCode === 200 || $httpCode === 201) {
        echo "<h3>Успішно! Заявка в CRM створена.</h3>";
        // Тепер тут замість великого сайту буде короткий JSON рядок, наприклад: {"success":true,"data":{"orderId":123}}
        echo "Відповідь системи: " . htmlspecialchars($response);
    } else {
        echo "<h3>Помилка відправки!</h3>";
        echo "Код HTTP статусу: " . $httpCode . "<br>";
        echo "Відповідь сервера: " . htmlspecialchars($response);
    }
}
?>



