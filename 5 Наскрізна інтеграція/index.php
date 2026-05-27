<?php
// ==========================================
// 1. СЕРВЕРНА ОБРОБКА ТА ВАЛІДАЦІЯ (PHP)
// ==========================================

ini_set('display_errors', 0);
error_reporting(E_ALL);

$errors = [];
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- ЗАХИСТ ВІД СПАМУ ---
    if (!empty($_POST['website_hp'])) {
        die("Спам заблоковано!");
    }

    $formTime = isset($_POST['form_time']) ? (int)$_POST['form_time'] : 0;

    if ((time() - $formTime) < 3) {
        die("Спам заблоковано! Занадто швидко.");
    }

    // --- ОЧИЩЕННЯ ПОЛІВ ---
    $fName    = trim($_POST['fName'] ?? '');
    $lName    = trim($_POST['lName'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $comment  = trim($_POST['comment'] ?? '');
    $shipping = trim($_POST['shipping_method'] ?? 'novaposhta');

    // --- ВАЛІДАЦІЯ ІМЕНІ ---
    if (empty($fName)) {
        $errors['fName'] = "Введіть ім'я.";
    } elseif (!preg_match("/^[a-zA-Zа-яА-ЯіІїЇєЄґҐ\s]+$/u", $fName)) {
        $errors['fName'] = "Ім'я повинно містити лише літери.";
    }

    // --- ВАЛІДАЦІЯ ПРІЗВИЩА ---
    if (empty($lName)) {
        $errors['lName'] = "Введіть прізвище.";
    } elseif (!preg_match("/^[a-zA-Zа-яА-ЯіІїЇєЄґҐ\s]+$/u", $lName)) {
        $errors['lName'] = "Прізвище повинно містити лише літери.";
    }

 $cleanPhone = preg_replace('/\D/', '', $phone);

// якщо номер починається з 0 → додаємо 38
if (strlen($cleanPhone) == 10 && strpos($cleanPhone, '0') === 0) {
    $cleanPhone = '38' . $cleanPhone;
}

$ukrainianCodes = [
    '39', '50', '63', '66', '67',
    '68', '73', '91', '92', '93',
    '94', '95', '96', '97', '98', '99'
];

// беремо код оператора
$operatorCode = substr($cleanPhone, 3, 2);

if (empty($phone)) {

    $errors['phone'] = "Телефон обов'язковий.";

} elseif (strlen($cleanPhone) !== 12 || strpos($cleanPhone, '380') !== 0) {

    $errors['phone'] = "Некоректний формат номера.";

} elseif (!in_array($operatorCode, $ukrainianCodes)) {

    $errors['phone'] = "Невідомий код оператора.";

}

    // ==========================================
    // ВІДПРАВКА В SALESDRIVE
    // ==========================================

    if (empty($errors)) {

        $salesDriveKey = "8rkaXBsDKxywO1kYC880uZsH3gtelx7Jgx39Q4HOWiQ2QgZHMy9gmkNpdHSK4zSJxisB6xIBS00d8PuHCpytxk2fxm1DSXlGnV1A";

        $urlSalesDrive = "https://mickrogreen.salesdrive.me/handler/";

        $dataSalesDrive = [
            'form'            => $salesDriveKey,
            'f_name'          => $fName,
            'l_name'          => $lName,
            'phone'           => $cleanPhone,
            'email'           => $email,
            'shipping_method' => $shipping,
            'comment'         => $comment,
            'company'         => 'Мікрогрін ЛТД'
        ];

        $ch = curl_init($urlSalesDrive);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataSalesDrive));

        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $responseObj = json_decode($response, true);

        $orderId = null;

        if (isset($responseObj['data']['orderId'])) {

            $orderId = $responseObj['data']['orderId'];

        } elseif (isset($responseObj['id'])) {

            $orderId = $responseObj['id'];

        }

        if (
            $httpCode === 200 ||
            $httpCode === 201 ||
            (isset($responseObj['success']) && $responseObj['success'] == true)
        ) {

            if ($orderId) {

                $successMessage = "Успішно! Заявка створена №" . $orderId;

            } else {

                $successMessage = "Успішно! Заявка створена.";

            }

            $_POST = [];

        } else {

            $errors['server'] = "Помилка CRM (Код: " . $httpCode . ")";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Форма замовлення</title>

<style>

body{
    font-family: Arial, sans-serif;
    background:#f5f5f5;
    margin:0;
    padding:0;
}

.form-container{
    width:450px;
    max-width:95%;
    margin:30px auto;
    background:white;
    padding:30px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:5px;
    font-weight:bold;
}

input,
textarea,
select{
    width:100%;
    padding:12px;
    margin-bottom:15px;
    box-sizing:border-box;
    border:1px solid #ccc;
    border-radius:5px;
    font-size:16px;
}

button{
    width:100%;
    padding:14px;
    background:#0077ff;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-size:16px;
    font-weight:bold;
}

button:hover{
    background:#005fd1;
}

.error{
    color:red;
    margin-bottom:15px;
    font-size:14px;
}

.success{
    color:green;
    margin-bottom:15px;
    padding:15px;
    background:#e2f5ea;
    border:1px solid #a3e0be;
    border-radius:5px;
}

.hidden-field{
    display:none;
}

</style>

</head>
<body>

<div class="form-container">

<h2>Оформлення замовлення</h2>

<?php if(!empty($successMessage)): ?>
<div class="success">
    <?= $successMessage ?>
</div>
<?php endif; ?>

<?php if(isset($errors['server'])): ?>
<div class="error">
    <?= $errors['server'] ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="hidden-field">
    <input type="text" name="website_hp">
</div>

<input type="hidden" name="form_time" value="<?= time() ?>">

<label for="lName">Прізвище *</label>

<input
    type="text"
    id="lName"
    name="lName"
    placeholder="Шевчук"
    value="<?= htmlspecialchars($_POST['lName'] ?? '') ?>"
>

<?php if(isset($errors['lName'])): ?>
<div class="error"><?= $errors['lName'] ?></div>
<?php endif; ?>

<label for="fName">Ім'я *</label>

<input
    type="text"
    id="fName"
    name="fName"
    placeholder="Петро"
    value="<?= htmlspecialchars($_POST['fName'] ?? '') ?>"
>

<?php if(isset($errors['fName'])): ?>
<div class="error"><?= $errors['fName'] ?></div>
<?php endif; ?>

<label for="phone">Телефон *</label>

<input
    type="text"
    id="phone"
    name="phone"
    placeholder="+38 (099) 123-45-67"
    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
>

<?php if(isset($errors['phone'])): ?>
<div class="error"><?= $errors['phone'] ?></div>
<?php endif; ?>

<label for="email">Email</label>

<input
    type="email"
    id="email"
    name="email"
    placeholder="user@example.com"
    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
>

<label for="shipping_method">Доставка</label>

<select id="shipping_method" name="shipping_method">

    <option value="novaposhta">
        Nova Poshta
    </option>

    <option value="ukrposhta">
        Укрпошта
    </option>

    <option value="meest">
        Meest Express
    </option>

</select>

<label for="comment">Коментар</label>

<textarea
    id="comment"
    name="comment"
    rows="3"
    placeholder="Ваш коментар..."
><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>

<button type="submit">
    Підтвердити замовлення
</button>

</form>

</div>

<script>

const phoneInput = document.getElementById('phone');

phoneInput.addEventListener('input', function () {

    let x = this.value.replace(/\D/g, '');

    if (x.startsWith('380')) {
        x = x.substring(2);
    }

    if (!x.startsWith('0')) {
        x = '0' + x;
    }

    x = x.substring(0, 10);

    let formatted = '+38';

    if (x.length > 0) {
        formatted += ' (' + x.substring(0, 3);
    }

    if (x.length >= 4) {
        formatted += ') ' + x.substring(3, 6);
    }

    if (x.length >= 7) {
        formatted += '-' + x.substring(6, 8);
    }

    if (x.length >= 9) {
        formatted += '-' + x.substring(8, 10);
    }

    this.value = formatted;
});

</script>

</body>
</html>