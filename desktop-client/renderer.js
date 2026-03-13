const { secretKey } = require("./data");

async function showNotification() {
    new Notification("Notification", {
        body: "That's an example."
    });
}



document.getElementById("loadNotifications").addEventListener("click", async () => {
    try {
        // Pobranie powiadomień z main process
        const notifications = await window.api.getNotifications(apiUrl); // tu musi być await

        console.log(notifications);

        const list = document.getElementById("notificationList");

        list.innerHTML = notifications.map(n => {
            const recipients = JSON.parse(n.send_to || "[]"); // JSON -> tablica JS
            return `
            <li>
            <strong>${n.title}</strong> <em>od ${n.sender_name} ${n.sender_surname}</em><br>
            ${n.description}<br>
            <small>Do użytkowników: [${recipients.join(", ")}]</small><br>
            <small>Email nadawcy: ${n.sender_email}</small><br>
            <small>Utworzono: ${new Date(n.created_at).toLocaleString()}</small>
            </li>
        `; //<small>Do użytkowników: [${recipients.join(", ")}]</small><br>
        }).join("");
    } catch (error) {
        console.error("Błąd podczas pobierania powiadomień:", error);
        alert("Nie można pobrać powiadomień: " + error.message);
    }
});