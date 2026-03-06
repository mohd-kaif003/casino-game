<?php
session_start();
// Starting balance
if (!isset($_SESSION['wallet'])) {
    $_SESSION['wallet'] = 1000;
}

// Wallet sync API
if (isset($_GET['action']) && $_GET['action'] == 'update') {
    header('Content-Type: application/json');
    $amt = (int) $_POST['amount'];
    $_SESSION['wallet'] += $amt;
    echo json_encode(['bal' => $_SESSION['wallet']]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mines Pro Fix</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #0b0e14;
            color: white;
            font-family: sans-serif;
            height: 100vh;
            overflow: hidden;
            margin: 0;
        }

        .box {
            background: #1c232d;
            border-radius: 12px;
            border-bottom: 4px solid #000;
            cursor: pointer;
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: 0.1s;
        }

        .box:active {
            transform: scale(0.9);
            border-bottom-width: 0;
        }

        .gem {
            background: linear-gradient(135deg, #10b981, #065f46) !important;
            border: none !important;
        }

        .bomb {
            background: linear-gradient(135deg, #ef4444, #991b1b) !important;
            border: none !important;
        }

        .active-grid {
            opacity: 1 !important;
            pointer-events: auto !important;
        }
    </style>
</head>

<body class="flex flex-col items-center">

    <div class="w-full p-4 flex justify-between items-center bg-[#151a21] border-b border-gray-800 shadow-lg z-20">
        <div class="text-xl font-bold text-blue-500 italic">ULTRA MINES</div>
        <div class="bg-black/50 px-4 py-1 rounded-full border border-blue-500/30">
            <span class="text-blue-400 font-bold mr-1">₹</span>
            <span id="balText" class="font-bold tracking-wider"><?php echo $_SESSION['wallet']; ?></span>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center w-full px-4">
        <div id="grid"
            class="grid grid-cols-5 gap-2 w-full max-w-[360px] p-2 bg-[#151a21] rounded-2xl border border-gray-800 shadow-2xl transition-opacity duration-300"
            style="opacity: 0.4; pointer-events: none;">
            <?php for ($i = 0; $i < 25; $i++): ?>
                <div onclick="boxClicked(this)" class="box">
                    <div class="w-1.5 h-1.5 bg-blue-500/30 rounded-full pointer-events-none"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="mt-8 flex gap-12">
            <div class="text-center">
                <p class="text-[10px] text-gray-500 font-bold tracking-widest">MULTIPLIER</p>
                <p id="mText" class="text-2xl font-black text-blue-400">1x</p>
            </div>
            <div class="text-center border-l border-gray-800 pl-12">
                <p class="text-[10px] text-gray-500 font-bold tracking-widest">WINNING</p>
                <p id="wText" class="text-2xl font-black text-white">₹0</p>
            </div>
        </div>
    </div>

    <div
        class="w-full bg-[#151a21] p-6 rounded-t-[35px] border-t border-gray-800 shadow-[0_-10px_30px_rgba(0,0,0,0.5)] z-20">
        <div class="flex gap-3 mb-4">
            <div class="flex-1 bg-black/40 p-2 rounded-xl border border-gray-700 flex flex-col">
                <span class="text-[9px] text-gray-500 font-bold ml-2">AMOUNT</span>
                <input type="number" id="betInput" value="100"
                    class="bg-transparent text-center font-bold text-lg outline-none text-blue-400">
            </div>
            <button onclick="doubleBet()"
                class="bg-gray-800 px-6 rounded-xl font-bold border border-gray-700 active:bg-gray-700 transition-colors">2x</button>
        </div>
        <button id="actionBtn" onclick="handleMainAction(event)"
            class="w-full bg-blue-600 py-4 rounded-2xl font-black text-lg shadow-lg shadow-blue-600/20 active:scale-95 transition-all uppercase tracking-widest">
            Start Game
        </button>
    </div>

    <script>
        let isPlaying = false;
        let clickCount = 0;
        let bet = 0;
        let mult = 1;

        // RIGGED CONFIG
        const BAIT = 2; // Pehle 2 click 100% Diamond
        const LIMIT = 500; // 500 profit ke baad 90% chance bomb
        const WIN_CHANCE = 30; // Normal chance 30%

        function doubleBet() {
            document.getElementById('betInput').value = parseInt(document.getElementById('betInput').value) * 2;
        }

        function handleMainAction(e) {
            e.preventDefault();
            if (!isPlaying) startGame(); else cashout();
        }

        function startGame() {
            const betVal = parseInt(document.getElementById('betInput').value);
            const bal = parseInt(document.getElementById('balText').innerText);

            if (betVal > bal || betVal <= 0) { alert("Balance low!"); return; }

            isPlaying = true;
            clickCount = 0;
            bet = betVal;
            mult = 1;

            // Activate Grid
            const grid = document.getElementById('grid');
            grid.classList.add('active-grid');

            // UI Changes
            const btn = document.getElementById('actionBtn');
            btn.innerText = "Cashout";
            btn.classList.replace('bg-blue-600', 'bg-green-600');

            // Reset Boxes
            document.querySelectorAll('.box').forEach(b => {
                b.className = "box";
                b.innerHTML = '<div class="w-1.5 h-1.5 bg-blue-500/30 rounded-full pointer-events-none"></div>';
            });
            updateUI();
        }

        function boxClicked(el) {
            if (!isPlaying || el.classList.contains('gem') || el.classList.contains('bomb')) return;

            clickCount++;
            let currentProfit = bet * mult;
            let result = 'gem';

            // --- SMART RIGGING LOGIC ---
            if (clickCount <= BAIT) {
                result = 'gem';
            } else if (currentProfit >= LIMIT) {
                result = (Math.random() * 100 <= 10) ? 'gem' : 'bomb'; // Hard Rigged
            } else {
                result = (Math.random() * 100 <= WIN_CHANCE) ? 'gem' : 'bomb';
            }

            if (result === 'gem') {
                el.classList.add('gem');
                el.innerHTML = '<i class="fas fa-gem text-white"></i>';
                mult = mult * 2; // 2x per diamond
                updateUI();
            } else {
                el.classList.add('bomb');
                el.innerHTML = '<i class="fas fa-bomb text-white"></i>';
                endGame(false);
            }
        }

        function updateUI() {
            let win = bet * mult;
            document.getElementById('mText').innerText = mult + "x";
            document.getElementById('wText').innerText = "₹" + win;
            if (isPlaying) {
                document.getElementById('actionBtn').innerText = "CASHOUT ₹" + win;
            }
        }

        function cashout() {
            let winTotal = bet * mult;
            syncWallet(winTotal);
            alert("Mubarak! ₹" + winTotal + " jeet gaye.");
            endGame(true);
        }

        function endGame(won) {
            isPlaying = false;
            const grid = document.getElementById('grid');
            grid.classList.remove('active-grid');

            const btn = document.getElementById('actionBtn');
            btn.innerText = "Start Game";
            btn.classList.replace('bg-green-600', 'bg-blue-600');

            if (!won) {
                syncWallet(-bet);
                alert("BOOM! Har gaye.");
            }

            document.getElementById('mText').innerText = "1x";
            document.getElementById('wText').innerText = "₹0";
        }

        function syncWallet(val) {
            let fd = new FormData();
            fd.append('amount', val);
            fetch('?action=update', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('balText').innerText = data.bal;
                });
        }
    </script>
</body>

</html>