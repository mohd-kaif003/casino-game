# 💣 Mines Pro - Smart Rigged Gaming WebApp

Mines Pro ek premium, mobile-first gaming web application hai jo popular "Mines" casino games par based hai. Isme advanced **Smart Rigging Logic** ka use kiya gaya hai taaki admin ka profit hamesha secure rahe aur users ko shuruat mein lalach (bait) mil sake.

---

## 🔥 Key Features

* **📱 Mobile-First Design:** Ekdum clean aur professional UI, jo mobile screen par kisi native app ki tarah chalta hai.
* **💎 Smart Rigging Algorithm:** * **Bait Mode:** Shuruat ke pehle 2 clicks hamesha 100% safe rahenge (User trust build-up).
    * **Profit Ceiling:** Jaise hi user ₹500 se zyada profit hit karega, Bomb phatne ke chances 95% ho jayenge.
    * **2x Multiplier:** Har Diamond par paisa double hota hai ($100 \rightarrow 200 \rightarrow 400 \dots$).
* **⚡ Real-time Performance:** Bina page refresh kiye game chalta hai (AJAX & Fetch API use ki gayi hai).
* **💰 Wallet System:** Session-based wallet management jo instant balance update karta hai.

---

## 🛠️ Technical Stack

* **Frontend:** HTML5, CSS3 (Tailwind CSS for styling), FontAwesome (Icons).
* **Backend:** PHP (Logic & Session Management).
* **Scripting:** Vanilla JavaScript (ES6+).

---

## 🚀 Installation & Setup

1.  **Server Requirements:** Aapko ek local server (XAMPP/WAMP/Laragon) ya kisi PHP hosting ki zaroorat hogi.
2.  **File Setup:** `index.php` naam ki file banayein aur sara code usme paste kar dein.
3.  **Run:** Browser mein server URL open karein (e.g., `http://localhost/mines/index.php`).

---

## ⚙️ Admin Customization (How to Control)

Aap `index.php` ke JavaScript section mein in variables ko change karke game ko control kar sakte hain:

| Variable | Description | Default Value |
| :--- | :--- | :--- |
| `BAIT` | Shuruat ke kitne clicks 100% safe honge. | 2 |
| `LIMIT` | Kitne profit ke baad user ko harana hai. | 500 |
| `WIN_CHANCE` | Normal mode mein jeetne ki probability. | 30% |
| `MULTIPLIER` | Har diamond pe paisa kitna multiply hoga. | 2x |

---

## 📂 Project Structure

```text
/Mines-Pro
├── index.php         # Single file containing PHP, HTML, CSS, and JS logic
├── README.md         # Project documentation (This file)