<?php
// ==========================================
// 1. СЕРВЕРНА ОБРОБКА ТА ВАЛІДАЦІЯ (PHP)
// ==========================================
$errors = [];
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // --- ЗАХИСТ ВІД СПАМУ БЕЗ КАПЧІ (Метод Honeypot + Time trap) ---
    
    // Перевірка прихованого поля "пастки" (Роботи автоматично заповнюють усі поля, а людина його не бачить)
    if (!empty($_POST['website_hp'])) {
        // Повністю ігноруємо запит, або вдаємо, що все ок (щоб робот не знав, що його розкусили)
        die("Спам заблоковано!"); 
    }

    // Перевірка часу заповнення форми (Робот заповнює за 0-1 секунду, людина — мінімум за 3 секунди)
    $formTime = isset($_POST['form_time']) ? (int)$_POST['form_time'] : 0;
    if ((time() - $formTime) < 3) {
        die("Спам заблоковано! Форма заповнена занадто швидко.");
    }

    // --- ОСНОВНА ВАЛІДАЦІЯ ДАНИХ ---
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Валідація імені: Тільки літери (Українські, англійські) та пробіли
    if (empty($name)) {
        $errors['name'] = "Ім'я обов'язкове для заповнення.";
    } elseif (!preg_match("/^[a-zA-Zа-яА-ЯіІїЇєЄґҐ\s]+$/u", $name)) {
        $errors['name'] = "Ім'я повинно містити лише літери.";
    }

    // Валідація телефону (Очищуємо маску від зайвих символів для перевірки коду оператора)
    // Очікуваний чистий формат: 380XXXXXXXXX (всього 12 цифр)
    $cleanPhone = preg_replace('/\D/', '', $phone);

    // Список діючих кодів українських мобільних операторів
    $ukrainianCodes = ['39', '50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99'];
    
    // Витягуємо 2 цифри коду оператора (після 380)
    $operatorCode = substr($cleanPhone, 3, 2);

    if (empty($phone)) {
        $errors['phone'] = "Телефон обов'язковий для заповнення.";
    } elseif (strlen($cleanPhone) !== 12 || strpos($cleanPhone, '380') !== 0) {
        $errors['phone'] = "Некоректний формат номера. Формат: +38 (0XX) XXX-XX-XX.";
    } elseif (!in_array($operatorCode, $ukrainianCodes)) {
        $errors['phone'] = "Невідомий код оператора України.";
    }

    // Якщо помилок немає — дані успішно прийнято
    if (empty($errors)) {
        $successMessage = "Форму успішно відправлено! Дякуємо, " . htmlspecialchars($name) . ".";
        // Тут дані можна писати в базу або відправляти в Telegram/Email
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кастомна валідація та Анти-Спам</title>
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
        
        /* Хитрість: Ховаємо поле-пастку від очей реального користувача */
        .hidden-field { display: none !important; visibility: hidden !important; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Зворотній зв'язок</h2>

    <?php if (!empty($successMessage)): ?>
        <div class="success-box"><?= $successMessage ?></div>
    <?php endif; ?>

    <form action="" method="POST" id="customForm">
        
        <!-- АНТИ-СПАМ ПОЛЯ (Приховані від людини) -->
        <div class="hidden-field">
            <label>Не заповнюйте це поле, якщо ви людина:</label>
            <input type="text" name="website_hp" autocomplete="off">
        </div>
        <input type="hidden" name="form_time" value="<?= time() ?>">

        <!-- ПОЛЕ: ІМ'Я -->
        <div class="form-group">
            <label for="name">Ваше ім'я</label>
            <input type="text" id="name" name="name" placeholder="Тільки літери (напр. Олексій)" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
            <span class="error-text" id="nameError"><?= $errors['name'] ?? '' ?></span>
        </div>

        <!-- ПОЛЕ: ТЕЛЕФОН -->
        <div class="form-group">
            <label for="phone">Номер телефону</label>
            <input type="text" id="phone" name="phone" placeholder="+38 (0XX) XXX-XX-XX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            <span class="error-text" id="phoneError"><?= $errors['phone'] ?? '' ?></span>
        </div>

        <button type="submit">Надіслати дані</button>
    </form>
</div>

<!-- ==========================================
// 2. КЛІЄНТСЬКА ВАЛІДАЦІЯ ТА МАСКА (JavaScript)
// ========================================== -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const nameInput = document.getElementById("name");
    const phoneInput = document.getElementById("phone");
    const form = document.getElementById("customForm");

    // --- 1. Кастомна маска телефону для українських операторів (+38 (0XX) XXX-XX-XX) ---
    phoneInput.addEventListener("input", function(e) {
        let matrix = "+38 (0__) ___-__-__",
            i = 0,
            def = matrix.replace(/\D/g, ""),
            val = this.value.replace(/\D/g, "");
        
        // Якщо користувач намагається стерти початковий префікс 380, повертаємо його
        if (def.length >= val.length) val = def;
        
        this.value = matrix.replace(/./g, function(a) {
            return /[_\d]/.test(a) && i < val.length ? val.charAt(i++) : i >= val.length ? "" : a;
        });
    });

    // Автоматично підставляти старт маски при фокусі
    phoneInput.addEventListener("focus", function() {
        if (this.value === "") {
            this.value = "+38 (0";
        }
    });

    // --- 2. Валідація введення імені "на льоту" (заборона вводу цифр/символів) ---
    nameInput.addEventListener("input", function() {
        // Миттєво видаляємо цифри та спецсимволи під час друку
        this.value = this.value.replace(/[^a-zA-Zа-яА-ЯіІїЇєЄґҐ\s]/g, "");
    });

    // --- 3. Валідація перед безпосередньою відправкою форми ---
    form.addEventListener("submit", function(e) {
        let hasErrors = false;
        
        // Скидаємо попередні помилки
        document.getElementById("nameError").innerText = "";
        document.getElementById("phoneError").innerText = "";

        // Перевірка імені
        if (nameInput.value.trim().length < 2) {
            document.getElementById("nameError").innerText = "Ім'я занадто коротке.";
            hasErrors = true;
        }

        // Перевірка телефону (повна довжина маски має бути рівно 19 символів)
        if (phoneInput.value.length < 19) {
            document.getElementById("phoneError").innerText = "Введіть номер повністю: +38 (0XX) XXX-XX-XX.";
            hasErrors = true;
        }

        // Якщо є помилки, зупиняємо відправку форми на сервер
        if (hasErrors) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>
