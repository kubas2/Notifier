// URL to the backend endpoint that returns notifications.
// Update this to match where you run your PHP server (e.g. http://localhost:8000/getNotifications.php).
const apiUrl = "http://notifier.w.zset.leszno.pl/actions.php";

async function showNotification() {
    new Notification("Notification", {
        body: "That's an example."
    });
}


async function loadNotifications() {
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
            <small>Email nadawcy: ${n.sender_email}</small><br>
            <small>Utworzono: ${new Date(n.created_at).toLocaleString()}</small>
            </li>
        `;
        }).join("");
    } catch (error) {
        console.error("Błąd podczas pobierania powiadomień:", error);
        alert("Nie można pobrać powiadomień: " + error.message);
    }
}

async function loadLoginPage() {
    try {
        if (!window.session || !window.session.loggedIn) {
            document.getElementById("loginForms").style.display = "block";
            document.getElementById("notificationSection").style.display = "none";
        }
        const login = await window.api.checkLogin(apiUrl, document.getElementById("email").value, document.getElementById("password").value);
        //alert("Odpowiedź z serwera: " + JSON.stringify(login));
        if (login.success) {
            window.session = {
                email: document.getElementById("email").value,
                password: document.getElementById("password").value,
                loggedIn: true,
            }
            
            loadNotifications();
            document.getElementById("loginForms").style.display = "none";
            document.getElementById("notificationSection").style.display = "block";
            document.getElementById("logoutButton").style.display = "block";
            document.getElementById("showLogin").style.display = "none";
            document.getElementById("showSignup").style.display = "none";
            alert("Zalogowano pomyślnie!");
        } else {
            window.session = {
                loggedIn: false
            }
            alert("Nieprawidłowy email lub hasło.");
        }
    } catch (error) {
        window.session = {
            loggedIn: false
        }
        console.error("Błąd podczas logowania:", error);
        alert("Nie można załadować systemu logowania: " + error.message);
    }
}

async function loadSignupPage() {
    try {
        /*if (!window.session || !window.session.loggedIn) {
                document.getElementById("loginForms").style.display = "block";
                document.getElementById("notificationSection").style.display = "none";
            }
            const login = await window.api.checkLogin(apiUrl, document.getElementById("email").value, document.getElementById("password").value);
            //alert("Odpowiedź z serwera: " + JSON.stringify(login));
            if (login.success) {
                window.session = {
                    email: document.getElementById("email").value,
                    password: document.getElementById("password").value,
                    loggedIn: true,
                }
                
                loadNotifications();
                document.getElementById("loginForms").style.display = "none";
                document.getElementById("notificationSection").style.display = "block";
                document.getElementById("logoutButton").style.display = "block";
                document.getElementById("showLogin").style.display = "none";
                document.getElementById("showSignup").style.display = "none";
                alert("Zalogowano pomyślnie!");
            } else {
                window.session = {
                    loggedIn: false
                }
                alert("Nieprawidłowy email lub hasło.");
            }*/
    } catch (error) {
        window.session = {
            loggedIn: false
        }
        console.error("Błąd podczas ładowania systemu rejestracji:", error);
        alert("Nie można załadować systemu rejestracji: " + error.message);
    }
}

// Po załadowaniu strony podłączamy przycisk i automatycznie wczytujemy listę
window.addEventListener("DOMContentLoaded", () => {
    const loadBtn = document.getElementById("loadNotifications");
    const loginBtn = document.getElementById("showLogin");
    const signupBtn = document.getElementById("showSignup");
    const submitLoginBtn = document.getElementById("loginButton");
    const submitSignupBtn = document.getElementById("signupButton");
    if (loadBtn) {
        loadBtn.addEventListener("click", loadNotifications);
    }

    if (loginBtn) {
        loginBtn.addEventListener("click", () => {
            document.getElementById("loginPage").style.display = "block";
            document.getElementById("signupPage").style.display = "none";
        });
    }

    if (signupBtn) {
        signupBtn.addEventListener("click", () => {
            document.getElementById("signupPage").style.display = "block";
            document.getElementById("loginPage").style.display = "none";
        });
    }

    if (submitLoginBtn) {
        submitLoginBtn.addEventListener("click", loadLoginPage);
    }

    if (submitSignupBtn) {
        submitSignupBtn.addEventListener("click", loadSignupPage);
    }
});

//https://github.com/puikinsh/login-forms/tree/main/forms/neon#credits
