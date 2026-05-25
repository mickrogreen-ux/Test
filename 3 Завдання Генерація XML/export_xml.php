<?php
// Оптимізація лімітів для CLI
set_time_limit(0);
ini_set('memory_limit', '32M'); // Навмисно занижуємо ліміт пам'яті, щоб довести оптимізацію

$dbFile = 'test_shop.sqlite';
if (!file_exists($dbFile)) {
    die("Помилка: Спочатку запустіть generate_data.php для створення бази даних!\n");
}

// 1. Підключення до нашої тестової бази
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Помилка підключення до БД: " . $e->getMessage() . "\n");
}

// 2. Ініціалізація XMLWriter (Потоковий запис на диск)
$xmlFile = 'marketplace_products.xml';
$writer = new XMLWriter();
$writer->openURI($xmlFile);
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(true); // Форматування з відступами для читабельності

// Головна структура XML (Стандарт YML для маркетплейсів)
$writer->startElement('yml_catalog');
$writer->writeAttribute('date', date('Y-m-d H:i'));

$writer->startElement('shop');
$writer->writeElement('name', 'Тестовий Магазин');
$writer->writeElement('company', 'ТОВ Оптіма-Трейд');
$writer->writeElement('url', 'https://example.com');

// Відкриваємо блок товарів
$writer->startElement('offers');

// 3. Налаштування обробки порціями (Chunks)
$chunkSize = 500; // Розмір однієї порції даних
$offset = 0;
$hasProducts = true;

// Готуємо SQL-запит з лімітами
$stmt = $pdo->prepare("SELECT * FROM products LIMIT :limit OFFSET :offset");

echo "Початок генерації XML файлу...\n";

while ($hasProducts) {
    // Прив'язуємо змінні пагінації
    $stmt->bindValue(':limit', $chunkSize, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        $hasProducts = false;
        break;
    }
    
    // Записуємо поточну порцію у файл
    foreach ($products as $product) {
        $writer->startElement('offer');
        $writer->writeAttribute('id', $product['id']);
        $writer->writeAttribute('available', $product['available'] ? 'true' : 'false');
        
        $writer->writeElement('price', $product['price']);
        $writer->writeElement('currencyId', $product['currency']);
        $writer->writeElement('categoryId', $product['category_id']);
        $writer->writeElement('picture', $product['image']);
        
        // Безпечний запис тексту із екрануванням спецсимволів
        $writer->startElement('name');
        $writer->text($product['name']);
        $writer->endElement();
        
        // Опис пакуємо в CDATA, бо там є HTML-теги
        $writer->startElement('description');
        $writer->writeCData($product['description']);
        $writer->endElement();
        
        $writer->endElement(); // кінець offer
    }
    
    // Очищуємо пам'ять від обробленої порції масиву
    unset($products);
    
    // Зсуваємо маркер для наступної порції
    $offset += $chunkSize;
    echo "Оброблено товарів: $offset...\n";
    
    // Скидаємо буфер з RAM прямо у файл на диску
    $writer->flush();
}

// 4. Закриваємо теги та фіналізуємо документ
$writer->endElement(); // offers
$writer->endElement(); // shop
$writer->endElement(); // yml_catalog
$writer->endDocument();
$writer->flush();

echo "\nУспіх! Згенеровано файл: $xmlFile\n";
echo "Всього оброблено товарів: $offset\n";
echo "Пікове споживання пам'яті: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
