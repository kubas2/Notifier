const { app, BrowserWindow, ipcMain} = require("electron");
const mysql = require("mysql2");

let mainWindow;

/*const connection = mysql.createConnection({
    host: "172.16.0.3",
    user: "notifier",
    password: "zmBJh6TErjv6Rpt",
    database: "notifier",
});*/

/*const { dbConfig } = require("./data");

const connection = mysql.createConnection(dbConfig);

connection.connect(err => {
  if (err) {
    console.error("Błąd połączenia z MySQL:", err.message);
    return;
  }
  console.log("Połączono z MySQL");
});
*/
function createWindow() {
    mainWindow = new BrowserWindow({
        width: 800,
        height: 600,
        webPreferences: {
            nodeIntegration: true,
            preload: `${__dirname}/preload.js`,
            contextIsolation: true
        }
    });

    mainWindow.loadFile("index.html");
}

app.whenReady().then(createWindow);

// ipcMain.handle tworzy endpoint na który renderer może wysyłać zapytania
/*ipcMain.handle("getNotifications", async ()=> {
    return new Promise((resolve, reject) => {
        connection.query("SELECT n.id,n.title,n.description,n.created_at,n.send_to,u.name AS sender_name,u.surname AS sender_surname,u.email AS sender_email FROM notifications n JOIN users u ON n.sender_id = u.id ORDER BY n.created_at DESC;",
            (err, results) => {
            if (err) reject(err);
            else resolve(results);
        });
    });
});*/

app.on("window-all-closed", () => {
  if (process.platform !== "darwin") app.quit();
});