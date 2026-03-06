<?php
session_start();

// Wallet Setup
if (!isset($_SESSION['wallet'])) {
    $_SESSION['wallet'] = 1000;
}

// PHP API to update wallet balance via AJAX
if (isset($_GET['action']) && $_GET['action'] == 'update_balance') {
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
    <title>Mines Pro - Gaming</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #0b0e14;
            color: white;
            font-family: sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .grid-box {
            background: #1c232d;
            border-radius: 12px;
            border-bottom: 4px solid #000;
            transition: 0.1s;
            cursor: pointer;
        }

        .grid-box:active {
            transform: scale(0.92);
            border-bottom-width: 0;
        }

        .gem-win {
            background: linear-gradient(135deg, #10b981, #065f46) !important;
            border-bottom: 0 !important;
        }

        .bomb-loss {
            background: linear-gradient(135deg, #ef4444, #991b1b) !important;
            border-bottom: 0 !important;
        }

        .disabled {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
</head>

<body class="flex flex-col">

    <div class="p-4 flex justify-between items-center bg-[#151a21] border-b border-gray-800 fixed top-0 w-full z-10">
        <div class="text-xl font-black text-blue-500 italic">MINES<span class="text-white">PRO</span></div>
        <div class="bg-black/50 px-4 py-1.5 rounded-full border border-blue-500/30">
            <span class="text-blue-400 font-bold mr-2">₹</span>
            <span id="balance-display" class="font-mono font-bold"><?php echo $_SESSION['wallet']; ?></span>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4 pt-20">



        <div id="game-grid" class="grid grid-cols-5 gap-2 w-full max-w-[380px] disabled">
            <?php for ($i = 0; $i < 25; $i++): ?>
                <div onclick="tileClick(this)" class="grid-box aspect-square flex items-center justify-center text-2xl">
                    <div class="w-1.5 h-1.5 bg-blue-500/20 rounded-full"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="w-full max-w-[380px] mt-10 grid grid-cols-2 gap-4">
            <div class="bg-[#151a21] p-4 rounded-2xl border border-gray-800 text-center">
                <p class="text-[10px] text-gray-500 font-bold uppercase">Multiplier</p>
                <p id="mult-display" class="text-2xl font-black text-blue-400">1x</p>
            </div>
            <div class="text-center bg-[#151a21] p-4 rounded-2xl border border-gray-800">
                <p class="text-[10px] text-gray-500 font-bold uppercase">Current Profit</p>
                <p id="profit-display" class="text-2xl font-black text-white">₹0</p>
            </div>
        </div>
    </div>

    <div class="p-8 bg-[#151a21] rounded-t-[40px] shadow-2xl border-t border-gray-800">
        <div class="flex gap-3 mb-6">
            <div class="flex-1 bg-black/40 rounded-2xl border border-gray-700 p-3">
                <p class="text-[10px] text-gray-500 font-bold mb-1 ml-1 uppercase">Bet Amount</p>
                <input type="number" id="bet-amount" value="100"
                    class="bg-transparent w-full font-black outline-none text-blue-400 text-xl">
            </div>
            <button onclick="document.getElementById('bet-amount').value *= 2"
                class="bg-gray-800 px-6 rounded-2xl font-bold border border-gray-700 active:scale-90">2x</button>
        </div>

        <button id="main-btn" onclick="btnAction()"
            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg shadow-lg active:scale-95 transition-all">
            Start Game
        </button>
    </div>

    <script>
        // --- ADMIN CONTROLS ---
        const BAIT_CLICKS = 2;     // Shuruat ke 2 click 100% win
        const PROFIT_LIMIT = 500;  // ₹500 ke upar profit hua toh haarna shuru
        const WIN_CHANCE = 30;     // Normal win chance 30%
        const MULTIPLIER = 2;      // Har diamond pe 2x paisa

        let isPlaying = false;
        let clicks = 0;
        let currentBet = 0;
        let currentMult = 1;

        function btnAction() {
            if (!isPlaying) startGame(); else cashout();
        }

        function startGame() {
            const betInput = document.getElementById('bet-amount');
            const balance = parseInt(document.getElementById('balance-display').innerText);
            currentBet = parseInt(betInput.value);

            if (currentBet > balance || currentBet <= 0) {
                alert("Insufficient Balance!");
                return;
            }

            // Game State Start
            isPlaying = true;
            clicks = 0;
            currentMult = 1;

            document.getElementById('game-grid').classList.remove('disabled');
            document.getElementById('main-btn').innerText = 'Cashout';
            document.getElementById('main-btn').classList.replace('bg-blue-600', 'bg-orange-500');

            // Reset Grid UI
            document.querySelectorAll('.grid-box').forEach(tile => {
                tile.className = "grid-box aspect-square flex items-center justify-center text-2xl";
                tile.innerHTML = '<div class="w-1.5 h-1.5 bg-blue-500/20 rounded-full"></div>';
            });
            updateUI();
        }

        function tileClick(el) {
            if (!isPlaying || el.innerHTML.includes('i')) return;

            clicks++;
            let potentialProfit = currentBet * currentMult;
            let result = 'gem';

            // --- SMART RIGGING LOGIC ---
            if (clicks <= BAIT_CLICKS) {
                result = 'gem'; // Always win
            } else if (potentialProfit >= PROFIT_LIMIT) {
                // If user is earning too much, 95% chance to lose
                result = (Math.random() * 100 <= 5) ? 'gem' : 'bomb';
            } else {
                // Normal random chance
                result = (Math.random() * 100 <= WIN_CHANCE) ? 'gem' : 'bomb';
            }

            if (result === 'gem') {
                el.classList.add('gem-win');
                el.innerHTML = '<i class="fas fa-gem text-white"></i>';
                currentMult = currentMult * MULTIPLIER;
                updateUI();
            } else {
                el.classList.add('bomb-loss');
                el.innerHTML = '<i class="fas fa-bomb text-white animate-bounce"></i>';
                endGame(false);
            }
        }

        function updateUI() {
            let win = currentBet * currentMult;
            document.getElementById('mult-display').innerText = currentMult + 'x';
            document.getElementById('profit-display').innerText = '₹' + win;
            if (isPlaying) {
                document.getElementById('main-btn').innerText = `Cashout ₹${win}`;
            }
        }

        function cashout() {
            let winAmount = currentBet * currentMult;
            syncWallet(winAmount);
            alert("Winner! ₹" + winAmount + " added to wallet.");
            endGame(true);
        }

        function endGame(won) {
            isPlaying = false;
            document.getElementById('game-grid').classList.add('disabled');
            document.getElementById('main-btn').innerText = 'Start Game';
            document.getElementById('main-btn').classList.replace('bg-orange-500', 'bg-blue-600');

            if (!won) {
                syncWallet(-currentBet);
                alert("BOOM! You lost.");
            }

            document.getElementById('mult-display').innerText = '1x';
            document.getElementById('profit-display').innerText = '₹0';
        }

        function syncWallet(amount) {
            const formData = new FormData();
            formData.append('amount', amount);

            fetch('?action=update_balance', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('balance-display').innerText = data.new_balance;
                });
        }
    </script>
</body>

</html>