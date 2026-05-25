<?php
// Створюємо або відкриваємо базу даних SQLite в файлі
$db = new PDO('sqlite:test_shop.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Створюємо таблицю товарів
$db->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    price REAL,
    currency TEXT,
    category_id INTEGER,
    image TEXT,
    available INTEGER,
    description TEXT
)");

// Очищуємо таблицю перед генерацією
$db->exec("DELETE FROM products");

echo "Генерація 10,000 товарів у базу даних... Зачекайте.\n";

// Використовуємо транзакцію для супер-швидкого запису в SQLite
$db->beginTransaction();
$stmt = $db->prepare("INSERT INTO products (name, price, currency, category_id, image, available, description) VALUES (?, ?, ?, ?, ?, ?, ?)");

for ($i = 1; $i <= 10000; $i++) {
    $stmt->execute([
        "Товар штучний №" . $i,
        rand(100, 5000) . '.' . rand(10, 99),
        "UAH",
        rand(1, 10),
        "https://example.com_" . $i . ".jpg",
        rand(0, 1),
        "Це чудовий опис для тестового товару безпечного формату №" . $i . ". <p>Тут може бути HTML тег.</p>"
    ]);
}
$db->commit();

echo "База даних 'test_shop.sqlite' успішно створена та заповнена 10 000 товарами!\n";
