<?php
session_start();

// --- 1. ADMIN SMART CONTROLS ---
$config = [
    "bait_clicks" => 2,        // Shuruat ke 2 clicks hamesha SAFE (Lalach ke liye)
    "profit_limit" => 500,     // Agar user ₹500+ profit mein jaye toh bomb ke chances 95%
    "normal_win_rate" => 30,   // Normal win chance 30%
    "multiplier" => 2          // Har click pe paisa double (2x)
];

// Wallet Initialization
if (!isset($_SESSION['wallet'])) {
    $_SESSION['wallet'] = 1000;
}

// --- 2. BACKEND API HANDLER ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // Logic to check tile
    if ($_GET['action'] == 'check') {
        $clicks = (int)$_POST['clicks'];
        $bet = (int)$_POST['bet'];
        $current_win = (int)$_POST['current_win'];

        $result = 'gem'; // Default

        // Smart Logic Application
        if ($clicks <= $config['bait_clicks']) {
            $result = 'gem'; // Always win for bait
        } elseif ($current_win >= $config['profit_limit']) {
            $result = (rand(1, 100) <= 5) ? 'gem' : 'bomb'; // Hard Rigged
        } else {
            $result = (rand(1, 100) <= $config['normal_win_rate']) ? 'gem' : 'bomb';
        }

        echo json_encode(['status' => $result, 'mult' => $config['multiplier']]);
        exit;
    }

    // Logic to update wallet
    if ($_GET['action'] == 'sync') {
        $amount = (int)$_POST['amount'];
        $_SESSION['wallet'] += $amount;
        echo json_encode(['bal' => $_SESSION['wallet']]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mines Pro | Smart Casino</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #0b0e14; color: #fff; font-family: 'Inter', sans-serif; overflow: hidden; }
        .grid-tile { background: #1c232d; border-radius: 12px; border-bottom: 4px solid #000; transition: 0.1s; cursor: pointer; }
        .grid-tile:active { transform: scale(0.95); border-bottom-width: 0; }
        .gem-reveal { background: linear-gradient(135deg, #10b981, #064e3b) !important; border-bottom-color: #064e3b !important; }
        .bomb-reveal { background: linear-gradient(135deg, #ef4444, #7f1d1d) !important; border-bottom-color: #450a0a !important; }
        .disabled { pointer-events: none; opacity: 0.5; }
    </style>
</head>
<body class="flex flex-col h-screen">

    <div class="p-4 flex justify-between items-center bg-[#151a24] border-b border-gray-800 shadow-xl">
        <div class="text-xl font-black text-emerald-500 tracking-tighter uppercase italic">Mines<span class="text-white">Pro</span></div>
        <div class="bg-black/40 px-5 py-1.5 rounded-full border border-emerald-900/30 flex items-center">
            <span class="text-emerald-400 font-bold mr-2 text-sm">₹</span>
            <span id="bal-display" class="font-mono font-bold"><?php echo $_SESSION['wallet']; ?></span>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4">
        
        
        
        <div id="game-grid" class="grid grid-cols-5 gap-2.5 w-full max-w-[360px] disabled">
            <?php for($i=0; $i<25; $i++): ?>
                <div onclick="onTileClick(this)" class="grid-tile aspect-square flex items-center justify-center text-2xl shadow-lg">
                    <div class="w-1.5 h-1.5 bg-emerald-500/20 rounded-full"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="w-full max-w-[360px] mt-10 grid grid-cols-2 gap-4">
            <div class="bg-[#151a24] p-4 rounded-2xl border border-gray-800 text-center">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-1">Multiplier</p>
                <p id="mult-ui" class="text-2xl font-black text-emerald-400">1x</p>
            </div>
            <div class="bg-[#151a24] p-4 rounded-2xl border border-gray-800 text-center">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-1">Profit</p>
                <p id="profit-ui" class="text-2xl font-black text-white">₹0</p>
            </div>
        </div>
    </div>

    <div class="p-8 bg-[#151a24] rounded-t-[40px] shadow-[0_-15px_40px_rgba(0,0,0,0.7)] border-t border-gray-800">
        <div class="flex gap-3 mb-6">
            <div class="flex-1 bg-black/40 rounded-2xl border border-gray-700 p-3">
                <p class="text-[10px] text-gray-500 font-bold mb-1 ml-1">BET AMOUNT</p>
                <input type="number" id="bet-input" value="100" class="bg-transparent w-full font-black outline-none text-emerald-400 text-xl">
            </div>
            <button onclick="document.getElementById('bet-input').value *= 2" class="bg-gray-800 px-6 rounded-2xl font-bold border border-gray-700 active:scale-90 transition-all">2x</button>
        </div>

        <button id="main-btn" onclick="handleMainAction()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg shadow-lg active:scale-95 transition-all">
            Bet
        </button>
    </div>

    <script>
        let isRunning = false;
        let clickCount = 0;
        let currentBet = 0;
        let currentMult = 1;

        function handleMainAction() {
            if(!isRunning) startGame(); else cashout();
        }

        function startGame() {
            const betVal = parseInt(document.getElementById('bet-input').value);
            const balance = parseInt(document.getElementById('bal-display').innerText);
            
            if(betVal > balance || betVal <= 0) return alert("Paisa kam hai!");

            isRunning = true;
            clickCount = 0;
            currentBet = betVal;
            currentMult = 1;

            document.getElementById('game-grid').classList.remove('disabled');
            document.getElementById('main-btn').innerText = 'Cashout';
            document.getElementById('main-btn').className = 'w-full bg-orange-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg';
            
            // Reset grid
            document.querySelectorAll('.grid-tile').forEach(tile => {
                tile.className = "grid-tile aspect-square flex items-center justify-center text-2xl shadow-lg";
                tile.innerHTML = '<div class="w-1.5 h-1.5 bg-emerald-500/20 rounded-full"></div>';
            });
        }

        function onTileClick(el) {
            if(!isRunning || el.innerHTML.includes('i')) return;
            
            clickCount++;
            let fd = new FormData();
            fd.append('clicks', clickCount);
            fd.append('bet', currentBet);
            fd.append('current_win', currentBet * currentMult);

            fetch('?action=check', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'gem') {
                    el.classList.add('gem-reveal');
                    el.innerHTML = '<i class="fas fa-gem text-white animate-pulse"></i>';
                    currentMult = currentMult * data.mult;
                    updateStats();
                } else {
                    el.classList.add('bomb-reveal');
                    el.innerHTML = '<i class="fas fa-bomb text-white animate-bounce"></i>';
                    endGame(false);
                }
            });
        }

        function updateStats() {
            let win = currentBet * currentMult;
            document.getElementById('mult-ui').innerText = currentMult + 'x';
            document.getElementById('profit-ui').innerText = '₹' + win;
            document.getElementById('main-btn').innerText = `Cashout ₹${win}`;
        }

        function cashout() {
            let win = currentBet * currentMult;
            updateWallet(win);
            alert("Congratulations! You won ₹" + win);
            endGame(true);
        }

        function endGame(won) {
            isRunning = false;
            document.getElementById('game-grid').classList.add('disabled');
            document.getElementById('main-btn').innerText = 'Bet';
            document.getElementById('main-btn').className = 'w-full bg-emerald-600 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg';
            
            if(!won) {
                updateWallet(-currentBet);
                alert("BOOM! Sab gaya.");
            }
            document.getElementById('mult-ui').innerText = '1x';
            document.getElementById('profit-ui').innerText = '₹0';
        }

        function updateWallet(amt) {
            let fd = new FormData();
            fd.append('amount', amt);
            fetch('?action=sync', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                document.getElementById('bal-display').innerText = d.bal;
            });
        }
    </script>
</body>
</html>