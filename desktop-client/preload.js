const { contextBridge, ipcRenderer } = require("electron");

contextBridge.exposeInMainWorld("api", {
    getNotifications: () => ipcRenderer.invoke("get-notifications")
});