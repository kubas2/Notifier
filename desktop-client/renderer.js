const apiUrl = "http://notifier.w.zset.leszno.pl/actions.php";

// Konfiguracja SweetAlert2 dla ciemnego motywu 
const MySwal = Swal.mixin({
    background: '#222',
    color: '#f5f5f5',
    confirmButtonColor: '#F5322C',
    iconColor: '#F5322C'
});

async function showNotification() {
    new Notification("Notification", {
        body: "That's an example."
    });
}


/*async function loadNotifications() {
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
        MySwal.fire("Błąd", "Nie można pobrać powiadomień: " + error.message, "error");
    }
}*/

async function loadNotifications() {
    try {
        const notifications = await window.api.getNotifications(apiUrl);

    
        const count = notifications.length;
        
   
        const countElement = document.getElementById("notifCount");
        if (countElement) {
            countElement.innerText = count;
        }

        console.log(`Aktualna liczba powiadomień: ${count}`);

        const list = document.getElementById("notificationList");
        list.innerHTML = notifications.map(n => {
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
        MySwal.fire("Błąd", "Nie można pobrać powiadomień: " + error.message, "error");
    }
}

async function loadLoginPage(afterSignup = false) {
    try {
        if (!window.session || !window.session.loggedIn) {
            document.getElementById("loginForms").style.display = "block";
            document.getElementById("notificationSection").style.display = "none";
        }
        let login;
        
        login = await window.api.checkLogin(apiUrl, document.getElementById("email").value, document.getElementById("password").value);
        if (afterSignup) {
            
            MySwal.fire({
                title: "Logowanie...",
                text: "Sprawdzanie danych logowania po rejestracji",
                timer: 1500,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });
            login = await window.api.checkLogin(apiUrl, document.getElementById("signupEmail").value, document.getElementById("signupPassword").value);
        }

        if (login.success) {
                if (afterSignup == false) {
                    window.session = {
                        email: document.getElementById("email").value,
                        password: document.getElementById("password").value,
                    };
                } else {
                    window.session = {
                        email: document.getElementById("signupEmail").value,
                        password: document.getElementById("signupPassword").value,
                    };
                }
                window.session.loggedIn = true;
            
            
            loadNotifications();
            document.getElementById("loginForms").style.display = "none";
            document.getElementById("notificationSection").style.display = "block";
            document.getElementById("logoutButton").style.display = "block";
            document.getElementById("showLogin").style.display = "none";
            document.getElementById("showSignup").style.display = "none";
            
            MySwal.fire("Sukces!", "Zalogowano pomyślnie!", "success"); 
        } else {
            window.session = {
                loggedIn: false
            }
            // Zastąpienie alertów debugujących ładnym oknem błędu
            MySwal.fire({
                icon: 'error',
                title: 'Błąd logowania',
                text: 'Nieprawidłowy email lub hasło.',
                footer: `<small>Próba logowania na: ${document.getElementById("email").value}</small>`
            });
        }
    } catch (error) {
        window.session = {
            loggedIn: false
        }
        console.error("Błąd podczas logowania:", error);
        MySwal.fire("Błąd", "Nie można załadować systemu logowania: " + error.message, "error"); 
    }
}

async function loadSignupPage() {
    try {
        if (!window.session || !window.session.loggedIn) {
                document.getElementById("loginForms").style.display = "block";
                document.getElementById("notificationSection").style.display = "none";
            }
            
            const signup = await window.api.signup(apiUrl, document.getElementById("signupEmail").value, document.getElementById("signupPassword").value, document.getElementById("signupName").value, document.getElementById("signupSurname").value);
            
            if (signup.success) {
                loadLoginPage(true);
                MySwal.fire("Gratulacje!", "Zarejestrowano pomyślnie!", "success"); 
            } else {
                window.session = {
                    loggedIn: false
                }
                MySwal.fire("Błąd rejestracji", "Nieprawidłowe dane rejestracji.", "error");
            }
    } catch (error) {
        window.session = {
            loggedIn: false
        }
        console.error("Błąd podczas ładowania systemu rejestracji:", error);
        MySwal.fire("Błąd", "Nie można załadować systemu rejestracji: " + error.message, "error"); 
    }
}

function startAutoRefresh() {
    setInterval(() => {
        if (window.session && window.session.loggedIn) {
            console.log("Automatyczne odświeżanie powiadomień...");
            loadNotifications();
        }
    }, 10000);  //10 sekund
}

// Po załadowaniu strony podłączamy przycisk i automatycznie wczytujemy listę
window.addEventListener("DOMContentLoaded", () => {
    const loadBtn = document.getElementById("loadNotifications");
    const loginBtn = document.getElementById("showLogin");
    const signupBtn = document.getElementById("showSignup");
    const submitLoginBtn = document.getElementById("loginButton");
    const submitSignupBtn = document.getElementById("signupButton");
    const logoutBtn = document.getElementById("logoutButton");
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
        submitLoginBtn.addEventListener("click", () => loadLoginPage(false));
    }

    if (submitSignupBtn) {
        submitSignupBtn.addEventListener("click", loadSignupPage);
    }

    
    if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
            delete window.session;
            window.session = { loggedIn: false };
            document.getElementById("loginForms").style.display = "block";
            document.getElementById("notificationSection").style.display = "none";
            document.getElementById("logoutButton").style.display = "none";
            document.getElementById("showLogin").style.display = "inline-block";
            document.getElementById("showSignup").style.display = "inline-block";
            document.getElementById("email").value = "";
            document.getElementById("password").value = "";
            document.getElementById("signupEmail").value = "";
            document.getElementById("signupPassword").value = "";
            document.getElementById("signupName").value = "";
            document.getElementById("signupSurname").value = "";
            MySwal.fire("Wylogowano", "Zostałeś wylogowany.", "success");
        });
    }

    if (document.getElementById("logoutButton")) {

    document.getElementById("min-btn").addEventListener("click", () => {
        window.api.minimize();});

    document.getElementById("max-btn").addEventListener("click", () => {
        window.api.maximize();});

    document.getElementById('close-btn').addEventListener('click', () => {
    window.api.close();});

    startAutoRefresh();

};
});
