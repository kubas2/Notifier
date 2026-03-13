const { contextBridge } = require("electron");
const { secretKey } = require("./data"); // pobieramy tylko secretKey

contextBridge.exposeInMainWorld("api", {
  getNotifications: async (apiUrl) => {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `secret=${encodeURIComponent(secretKey)}`
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