document.addEventListener("DOMContentLoaded", () => {
    // Елементи для бургер-меню
    const burgerBtn = document.getElementById("burgerBtn");
    const menuClose = document.getElementById("menuClose");
    const navMenu = document.getElementById("navMenu");

    // Відкрити мобільне меню
    burgerBtn.addEventListener("click", () => {
        navMenu.classList.add("active");
        document.body.style.overflow = "hidden"; // Забороняємо гортати сторінку під меню
    });

    // Закрити мобільне меню
    menuClose.addEventListener("click", () => {
        navMenu.classList.remove("active");
        document.body.style.overflow = "auto"; // Повертаємо прокрутку
    });

    // Валідація та обробка форми контактів
    const contactForm = document.getElementById("contactForm");
    if (contactForm) {
        contactForm.addEventListener("submit", (event) => {
            event.preventDefault(); // Зупиняємо перезавантаження сторінки

            // Збираємо дані
            const firstName = document.getElementById("firstName").value;
            const lastName = document.getElementById("lastName").value;
            const email = document.getElementById("email").value;
            const message = document.getElementById("message").value;

            // Проста імітація успішної відправки
            alert(`Дякуємо, ${firstName}! Ваше повідомлення успішно надіслано.`);
            contactForm.reset(); // Очищаємо поля форми
        });
    }
});
