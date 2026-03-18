const { contextBridge } = require("electron");
const { secretKey } = require("./data"); // pobieramy tylko secretKey

contextBridge.exposeInMainWorld("api", {
  getNotifications: async (apiUrl) => {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `secret=${encodeURIComponent(secretKey)}&action=getNotifications`
    });

    const text = await response.text();

    if (!response.ok) {
      throw new Error(`Błąd sieci: ${response.status} ${response.statusText} — odpowiedź: ${text}`);
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error(`Nieprawidłowe JSON w odpowiedzi: ${e.message}. Odpowiedź serwera: ${text}`);
    }
  },

  checkLogin: async (apiUrl, email, password) => {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `secret=${encodeURIComponent(secretKey)}&action=checkLogin&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
    });

    const text = await response.text();

    if (!response.ok) {
      throw new Error(`Błąd sieci: ${response.status} ${response.statusText} — odpowiedź: ${text}`);
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error(`Nieprawidłowe JSON w odpowiedzi: ${e.message}. Odpowiedź serwera: ${text}`);
    }
  },

  signUp: async (apiUrl, email, password, name, surname) => {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `secret=${encodeURIComponent(secretKey)}&action=signUp&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&name=${encodeURIComponent(name)}&surname=${encodeURIComponent(surname)}`
    });

    const text = await response.text();

    if (!response.ok) {
      throw new Error(`Błąd sieci: ${response.status} ${response.statusText} — odpowiedź: ${text}`);
    }

    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error(`Nieprawidłowe JSON w odpowiedzi: ${e.message}. Odpowiedź serwera: ${text}`);
    }
  }
});