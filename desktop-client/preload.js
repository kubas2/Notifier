const { contextBridge } = require("electron");
const { secretKey } = require("./data"); // pobieramy tylko secretKey

contextBridge.exposeInMainWorld("api", {
  getNotifications: async (apiUrl) => {
    const response = await fetch(apiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `secret=${encodeURIComponent(secretKey)}`
    });

    if (!response.ok) {
      throw new Error(`Błąd sieci: ${response.status}`);
    }

    return response.json();
  }
});