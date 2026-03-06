<?php
session_start();

// Initial Wallet
if (!isset($_SESSION['wallet'])) {
    $_SESSION['wallet'] = 1000;
}

// PHP API for Wallet Sync
if (isset($_GET['action']) && $_GET['action'] == 'sync_wallet') {
    header('Content-Type: application/json');
    $amount = (int) $_POST['amount'];
    $_SESSION['wallet'] += $amount;
    echo json_encode(['new_balance' => $_SESSION['wallet']]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mines Pro | Gaming App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #0b0e14;
            color: #fff;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            height: 100vh;
            user-select: none;
        }

        .tile-btn {
            background: #1c232d;
            border-radius: 12px;
            border-bottom: 4px solid #000;
            transition: 0.1s;
            cursor: pointer;
        }

        .tile-btn:active {
            transform: scale(0.92);
            border-bottom-width: 0;
        }

        .gem-active {
            background: linear-gradient(135deg, #10b981, #065f46) !important;
            border-bottom: 0 !important;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }

        .bomb-active {
            background: linear-gradient(135deg, #ef4444, #991b1b) !important;
            border-bottom: 0 !important;
        }

        .disabled-grid {
            pointer-events: none;
            opacity: 0.6;
        }

        .nav-blur {
            background: rgba(21, 26, 36, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #2d3748;
        }
    </style>
</head>

<body class="flex flex-col">

    <div class="p-4 flex justify-between items-center nav-blur fixed top-0 w-full z-10">
        <div class="text-xl font-black text-blue-500 tracking-tighter italic">MINES<span class="text-white">PRO</span>
        </div>
        <div class="bg-black/50 px-4 py-1.5 rounded-full border border-blue-500/30 flex items-center">
            <span class="text-blue-400 font-bold mr-2">₹</span>
            <span id="balance-ui" class="font-mono font-bold"><?php echo $_SESSION['wallet']; ?></span>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4 pt-20">



        <div id="grid-container" class="grid grid-cols-5 gap-2.5 w-full max-w-[380px] disabled-grid">
            <?php for ($i = 0; $i < 25; $i++): ?>
                <div onclick="handleTileClick(this)"
                    class="tile-btn aspect-square flex items-center justify-center text-2xl shadow-lg">
                    <div class="w-1.5 h-1.5 bg-blue-500/20 rounded-full"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="w-full max-w-[380px] mt-10 grid grid-cols-2 gap-4 px-2">
            <div class="bg-[#151a21] p-4 rounded-2xl border border-gray-800 text-center">
                <p class="text-[10px] text-gray-500 font-bold uppercase">Multiplier</p>
                <p id="mult-text" class="text-2xl font-black text-blue-400">1x</p>
            </div>
            <div class="bg-[#151a21] p-4 rounded-2xl border border-gray-800 text-center">
                <p class="text-[10px] text-gray-500 font-bold uppercase">Potential</p>
                <p id="win-text" class="text-2xl font-black text-white">₹0</p>
            </div>
        </div>
    </div>

    <div class="p-8 bg-[#151a21] rounded-t-[40px] shadow-[0_-15px_50px_rgba(0,0,0,0.8)] border-t border-gray-800">
        <div class="flex gap-3 mb-6">
            <div class="flex-1 bg-black/40 rounded-2xl border border-gray-700 p-3">
                <p class="text-[10px] text-gray-500 font-bold mb-1 ml-1">BET AMOUNT</p>
                <input type="number" id="bet-input" value="100"
                    class="bg-transparent w-full font-black outline-none text-blue-400 text-xl">
            </div>
            <button onclick="document.getElementById('bet-input').value *= 2"
                class="bg-gray-800 px-6 rounded-2xl font-bold border border-gray-700 active:scale-90 transition-all">2x</button>
        </div>

        <button id="main-btn" onclick="toggleGame()"
            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg shadow-lg shadow-blue-600/20 active:scale-95 transition-all">
            Start Game
        </button>
    </div>

    <script>
        // --- MASTER LOGIC CONFIG ---
        const config = {
            baitClicks: 2,        // Pehle 2 click 100% Win
            profitLimit: 500,     // ₹500 ke baad 95% chance Bomb ka
            normalWinRate: 35,    // Normal win chance 35%
            multiplier: 2         // Har click pe paisa double
        };

        let isRunning = false;
        let clickCount = 0;
        let currentBet = 0;
        let currentMult = 1;

        function toggleGame() {
            if (!isRunning) start(); else cashout();
        }

        function start() {
            const betVal = parseInt(document.getElementById('bet-input').value);
            const balance = parseInt(document.getElementById('balance-ui').innerText);

            if (betVal > balance || betVal <= 0) return alert("Insufficient balance!");

            isRunning = true;
            clickCount = 0;
            currentBet = betVal;
            currentMult = 1;

            document.getElementById('grid-container').classList.remove('disabled-grid');
            document.getElementById('main-btn').innerText = 'CASHOUT';
            document.getElementById('main-btn').classList.replace('bg-blue-600', 'bg-orange-500');

            // Reset Grid
            document.querySelectorAll('.tile-btn').forEach(tile => {
                tile.className = "tile-btn aspect-square flex items-center justify-center text-2xl shadow-lg";
                tile.innerHTML = '<div class="w-1.5 h-1.5 bg-blue-500/20 rounded-full"></div>';
            });
        }

        function handleTileClick(el) {
            if (!isRunning || el.innerHTML.includes('i')) return;

            clickCount++;
            let currentWin = currentBet * currentMult;
            let result = 'gem';

            // --- SMART RIGGED LOGIC ---
            if (clickCount <= config.baitClicks) {
                result = 'gem';
            } else if (currentWin >= config.profitLimit) {
                result = (Math.random() * 100 <= 5) ? 'gem' : 'bomb'; // Hard rigged
            } else {
                result = (Math.random() * 100 <= config.normalWinRate) ? 'gem' : 'bomb';
            }

            // --- UI UPDATE ---
            if (result === 'gem') {
                el.classList.add('gem-active');
                el.innerHTML = '<i class="fas fa-gem text-white animate-pulse"></i>';
                currentMult = currentMult * config.multiplier;
                updateStats();
            } else {
                el.classList.add('bomb-active');
                el.innerHTML = '<i class="fas fa-bomb text-white animate-bounce"></i>';
                end(false);
            }
        }

        function updateStats() {
            let win = currentBet * currentMult;
            document.getElementById('mult-text').innerText = currentMult + 'x';
            document.getElementById('win-text').innerText = '₹' + win;
            document.getElementById('main-btn').innerText = `CASHOUT ₹${win}`;
        }

        function cashout() {
            let winAmount = currentBet * currentMult;
            syncWallet(winAmount);
            alert("Jackpot! You won ₹" + winAmount);
            end(true);
        }

        function end(won) {
            isRunning = false;
            document.getElementById('grid-container').classList.add('disabled-grid');
            document.getElementById('main-btn').innerText = 'START GAME';
            document.getElementById('main-btn').classList.replace('bg-orange-500', 'bg-blue-600');

            if (!won) {
                syncWallet(-currentBet);
                alert("BOOM! Better luck next time.");
            }
            document.getElementById('mult-text').innerText = '1x';
            document.getElementById('win-text').innerText = '₹0';
        }

        function syncWallet(amount) {
            const formData = new FormData();
            formData.append('amount', amount);
            fetch('?action=sync_wallet', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('balance-ui').innerText = data.new_balance;
                }).catch(err => console.error("Sync Error:", err));
        }
    </script>
</body>

</html>