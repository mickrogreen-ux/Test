<?php
// ==========================================
// 1. СЕРВЕРНА ОБРОБКА ТА ВАЛІДАЦІЯ (PHP)
// ==========================================
ini_set('display_errors', 0);
error_reporting(E_ALL);

$errors = [];
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // --- ЗАХИСТ ВІД СПАМУ БЕЗ КАПЧІ ---
    if (!empty($_POST['website_hp'])) {
        die("Спам заблоковано!"); 
    }

    $formTime = isset($_POST['form_time']) ? (int)$_POST['form_time'] : 0;
    if ((time() - $formTime) < 3) {
        die("Спам заблоковано! Форма заповнена занадто швидко.");
    }

    // --- ОСНОВНА ВАЛІДАЦІЯ ДАНИХ ---
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        $errors['name'] = "Ім'я обов'язкове для заповнення.";
    } elseif (!preg_match("/^[a-zA-Zа-яА-ЯіІїЇєЄґҐ\s]+$/u", $name)) {
        $errors['name'] = "Ім'я повинно містити лише літери.";
    }

    $cleanPhone = preg_replace('/\D/', '', $phone);
    $ukrainianCodes = ['39', '50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
    $operatorCode = substr($cleanPhone, 3, 2);

    if (empty($phone)) {
        $errors['phone'] = "Телефон обов'язковий для заповнення.";
    } elseif (strlen($cleanPhone) !== 12 || strpos($cleanPhone, '380') !== 0) {
        $errors['phone'] = "Некоректний формат номера. Формат: +38 (0XX) XXX-XX-XX.";
    } elseif (!in_array($operatorCode, $ukrainianCodes)) {
        $errors['phone'] = "Невідомий код оператора України.";
    }

    // --- НАСКРІЗНА ІНТЕГРАЦІЯ (ЯКЩО НЕМАЄ ПОМИЛОК) ---
    if (empty($errors)) {
        
        // -------------------------------------------------------------
        // КРОК 1: НАДІСЛАННЯ ДАНИХ З ФОРМИ ДО SALESDRIVE
        // -------------------------------------------------------------
        $salesDriveKey = "BKUXrsK3s74q6GfSj01OitC_5i6E2oWoNpJSzkxLRKiAq_FXrCb8iLOr7r9teglu3t30VV-2mTbrK2jIUrj1fDrz6k8nYXsQT8rX"; 
        $urlSalesDrive = "https://salesdrive.ua";

        $dataSalesDrive = [
            'form'        => $salesDriveKey,
            'f_name'      => $name,
            'phone'       => $phone,
            'external_id' => 'Локальний сервер (Ланцюжок)'
        ];

        $ch1 = curl_init($urlSalesDrive);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($dataSalesDrive)); 
        curl_setopt($ch1, CURLOPT_TIMEOUT, 10);
        
        $resSalesDrive = curl_exec($ch1);
        $codeSalesDrive = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
        curl_close($ch1);

        // -------------------------------------------------------------
        // КРОК 2: ПЕРЕДАЧА ДАНИХ ДАЛІ В ДІЛОВОД (ЗА ПОДІЄЮ УСПІХУ SALESDRIVE)
        // -------------------------------------------------------------
        if ($codeSalesDrive === 200 || $codeSalesDrive === 302) {
            
            // ВСТАВТЕ СВІЙ API-КЛЮЧ З ДІЛОВОД ТУТ:
            $dilovodApiKey = "Bz5z11E9s2GSKKOuhXBH5vCs9L2Q43"; 

            $urlDilovod = "https://dilovod.ua";

            // Формуємо чисту картку клієнта без прив'язки до папки
            $dataDilovod = [
                "name"    => $name,
                "phone"   => $phone,
                "comment" => "Наскрізна інтеграція: Передано далі за подією створення в SalesDrive"
            ];

            $ch2 = curl_init($urlDilovod);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($dataDilovod));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-API-Key: ' . $dilovodApiKey
            ]);
            
            $resDilovod = curl_exec($ch2);
            $codeDilovod = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            // Виводимо повідомлення про успішне виконання всього ланцюжка
            $successMessage = "Форму успішно відправлено! Заявка створена в SalesDrive та передана далі в Діловод.";
            
            // Очищуємо поля форми для нового заповнення
            $_POST['name'] = '';
            $_POST['phone'] = '';
        } else {
            $errors['name'] = "Помилка першого кроку (CRM). Ланцюжок передачі розірвано.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Наскрізна інтеграція: Форма -> SalesDrive -> Діловод</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; color: #333; text-align: center; }
        .form-group { margin-bottom: 20px; position: relative; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; transition: border-color 0.3s; }
        input[type="text"]:focus { border-color: #4a90e2; outline: none; }
        .error-text { color: #e74c3c; font-size: 13px; margin-top: 5px; display: block; }
        .success-box { background: #2ecc71; color: white; padding: 15px; border-radius: 4px; text-align: center; margin-bottom: 20px; }
        button { width: 100%; padding: 12px; background: #4a90e2; border: none; color: white; font-size: 16px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
        button:hover { background: #357abd; }
        .hidden-field { display: none !important; visibility: hidden !important; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Наскрізна інтеграція</h2>

    <?php if (!empty($successMessage)): ?>
        <div class="success-box"><?= $successMessage ?></div>
    <?php endif; ?>

    <form action="" method="POST" id="customForm">
        <div class="hidden-field">
            <input type="text" name="website_hp" autocomplete="off">
        </div>
        <input type="hidden" name="form_time" value="<?= time() ?>">

        <div class="form-group">
            <label for="name">Ваше ім'я</label>
            <input type="text" id="name" name="name" placeholder="Тільки літери" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            <span class="error-text" id="nameError"><?= $errors['name'] ?? '' ?></span>
        </div>

        <div class="form-group">
            <label for="phone">Номер телефону</label>
            <input type="text" id="phone" name="phone" placeholder="+38 (0XX) XXX-XX-XX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            <span class="error-text" id="phoneError"><?= $errors['phone'] ?? '' ?></span>
        </div>

        <button type="submit">Надіслати дані</button>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const nameInput = document.getElementById("name");
    const phoneInput = document.getElementById("phone");
    const form = document.getElementById("customForm");

    if (!form) return;

    phoneInput.addEventListener("input", function(e) {
        let matrix = "+38 (0__) ___-__-__", i = 0,
            def = matrix.replace(/\D/g, ""), val = this.value.replace(/\D/g, "");
        if (def.length >= val.length) val = def;
        this.value = matrix.replace(/./g, function(a) {
            return /[_\d]/.test(a) && i < val.length ? val.charAt(i++) : i >= val.length ? "" : a;
        });
    });

    phoneInput.addEventListener("focus", function() { if (this.value === "") this.value = "+38 (0"; });
    nameInput.addEventListener("input", function() { this.value = this.value.replace(/[^a-zA-Zа-яА-ЯіІїЇєЄґҐ\s]/g, ""); });

    form.addEventListener("submit", function(e) {
        let hasErrors = false;
        document.getElementById("nameError").innerText = "";
        document.getElementById("phoneError").innerText = "";
        if (nameInput.value.trim().length < 2) { document.getElementById("nameError").innerText = "Ім'я занадто коротке."; hasErrors = true; }
        if (phoneInput.value.length < 19) { document.getElementById("phoneError").innerText = "Введіть номер повністю."; hasErrors = true; }
        if (hasErrors) e.preventDefault();
    });
});
</script>
</body>
</html>

