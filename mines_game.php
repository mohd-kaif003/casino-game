<?php
session_start();

// --- SMART PROFIT CONFIGURATION ---
$target_loss_limit = 500;  // Agar user ₹500 se zyada jeet gaya, toh use harana shuru karo
$global_win_chance = 35;   // Normal win chance 35%
$bait_clicks = 2;          // Shuruat ke 2 clicks hamesha Diamond (Lalach ke liye)
// ----------------------------------

if (!isset($_SESSION['wallet'])) { $_SESSION['wallet'] = 1000; }

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] == 'check_tile') {
        $clicks = (int)$_POST['click_count'];
        $current_win = (int)$_POST['current_win'];
        $bet = (int)$_POST['bet_amount'];

        // 1. BAIT LOGIC: Pehle 2 clicks hamesha Diamond
        if ($clicks <= $bait_clicks) {
            $status = 'gem';
        } 
        // 2. PROFIT CONTROL: Agar user ₹500 se zyada ka profit bana raha hai
        elseif ($current_win >= $target_loss_limit) {
            $status = (rand(1, 100) <= 5) ? 'gem' : 'bomb'; // Sirf 5% chance jeetne ka
        }
        // 3. NORMAL RIGGED LOGIC
        else {
            $status = (rand(1, 100) <= $global_win_chance) ? 'gem' : 'bomb';
        }

        echo json_encode(['status' => $status, 'factor' => 2]);
        exit;
    }

    if ($_GET['action'] == 'sync') {
        $_SESSION['wallet'] += (int)$_POST['amount'];
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
    <title>Casino Master Mines</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #05070a; color: #fff; font-family: 'Inter', sans-serif; overflow: hidden; height: 100vh; }
        .tile { background: #111827; border-radius: 16px; transition: 0.1s; border-bottom: 5px solid #000; }
        .tile:active { transform: translateY(2px); border-bottom-width: 2px; }
        .gem-active { background: linear-gradient(135deg, #059669, #064e3b) !important; border-color: #064e3b !important; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }
        .bomb-active { background: linear-gradient(135deg, #dc2626, #7f1d1d) !important; border-color: #450a0a !important; }
        .disabled { pointer-events: none; opacity: 0.4; }
        .nav-glass { background: rgba(17, 24, 39, 0.8); backdrop-filter: blur(12px); border-bottom: 1px solid #1f2937; }
    </style>
</head>
<body class="flex flex-col">

    <div class="p-4 flex justify-between items-center nav-glass fixed top-0 w-full z-10">
        <div class="text-xl font-black tracking-tighter text-emerald-500 italic">SMART<span class="text-white">MINES</span></div>
        <div class="flex items-center gap-3">
            <div class="bg-black/60 px-4 py-1.5 rounded-full border border-emerald-500/30 flex items-center shadow-inner">
                <span class="text-emerald-400 font-bold mr-2">₹</span>
                <span id="bal-val" class="font-mono font-bold text-sm tracking-widest"><?php echo $_SESSION['wallet']; ?></span>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4 pt-20">
        
        

        <div id="grid" class="grid grid-cols-5 gap-2.5 w-full max-w-[380px] disabled">
            <?php for($i=0; $i<25; $i++): ?>
                <div onclick="tileTap(this)" class="tile aspect-square flex items-center justify-center text-2xl">
                    <div class="w-1.5 h-1.5 bg-emerald-500/10 rounded-full"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="w-full max-w-[380px] mt-10 grid grid-cols-2 gap-4">
            <div class="bg-gray-900/50 p-4 rounded-2xl border border-gray-800">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Multiplier</p>
                <p id="mult-ui" class="text-2xl font-black text-emerald-400">1x</p>
            </div>
            <div class="bg-gray-900/50 p-4 rounded-2xl border border-gray-800 text-right">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Potential Win</p>
                <p id="win-ui" class="text-2xl font-black text-white">₹0</p>
            </div>
        </div>
    </div>

    <div class="p-8 bg-[#0b0f1a] rounded-t-[40px] shadow-[0_-20px_50px_rgba(0,0,0,0.8)] border-t border-gray-800">
        <div class="flex gap-3 mb-6">
            <div class="flex-1 bg-black/40 rounded-2xl border border-gray-700 p-3">
                <p class="text-[9px] text-gray-500 font-bold mb-1 ml-1">BET AMOUNT</p>
                <input type="number" id="bet-field" value="100" class="bg-transparent w-full font-black outline-none text-emerald-500 text-xl">
            </div>
            <button onclick="document.getElementById('bet-field').value *= 2" class="bg-gray-800 px-6 rounded-2xl font-bold border border-gray-700 active:scale-90 transition-all">2x</button>
        </div>

        <button id="action-btn" onclick="handleMainAction()" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg shadow-lg shadow-emerald-600/20 active:scale-95 transition-all">
            Place Bet
        </button>
    </div>

    <script>
        let running = false;
        let count = 0;
        let betAmt = 0;
        let multVal = 1;

        function handleMainAction() {
            if(!running) start(); else cashout();
        }

        function start() {
            const b = parseInt(document.getElementById('bet-field').value);
            const bal = parseInt(document.getElementById('bal-val').innerText);
            if(b > bal || b <= 0) return alert("Paisa nahi hai!");

            running = true;
            count = 0;
            betAmt = b;
            multVal = 1;

            document.getElementById('grid').classList.remove('disabled');
            document.getElementById('action-btn').innerText = 'Cashout';
            document.getElementById('action-btn').className = 'w-full bg-orange-500 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg';
            
            document.querySelectorAll('.tile').forEach(t => {
                t.className = "tile aspect-square flex items-center justify-center text-2xl";
                t.innerHTML = '<div class="w-1.5 h-1.5 bg-emerald-500/10 rounded-full"></div>';
            });
        }

        function tileTap(el) {
            if(!running || el.innerHTML.includes('i')) return;
            count++;

            const fd = new FormData();
            fd.append('click_count', count);
            fd.append('current_win', betAmt * multVal);
            fd.append('bet_amount', betAmt);

            fetch('?action=check_tile', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'gem') {
                    el.classList.add('gem-active');
                    el.innerHTML = '<i class="fas fa-gem text-white animate-pulse"></i>';
                    multVal = multVal * data.factor;
                    updateUI();
                } else {
                    el.classList.add('bomb-active');
                    el.innerHTML = '<i class="fas fa-bomb text-white"></i>';
                    stopGame(false);
                }
            });
        }

        function updateUI() {
            document.getElementById('mult-ui').innerText = multVal + 'x';
            document.getElementById('win-ui').innerText = '₹' + (betAmt * multVal);
            document.getElementById('action-btn').innerText = `Cashout ₹${betAmt * multVal}`;
        }

        function cashout() {
            updateWallet(betAmt * multVal);
            stopGame(true);
        }

        function stopGame(win) {
            running = false;
            document.getElementById('grid').classList.add('disabled');
            document.getElementById('action-btn').innerText = 'Place Bet';
            document.getElementById('action-btn').className = 'w-full bg-emerald-600 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg';
            
            if(!win) {
                updateWallet(-betAmt);
                alert("BOOM! Try again.");
            }
            document.getElementById('mult-ui').innerText = '1x';
            document.getElementById('win-ui').innerText = '₹0';
        }

        function updateWallet(val) {
            const fd = new FormData();
            fd.append('amount', val);
            fetch('?action=sync', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => document.getElementById('bal-val').innerText = d.bal);
        }
    </script>
</body>
</html>