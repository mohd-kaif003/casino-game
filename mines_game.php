<?php
session_start();

// --- 1. ADMIN CONFIGURATION ---
$total_tiles = 25;
$rigged_bomb_percent = 20; // 20% area rigged
$safe_clicks_limit = $total_tiles - floor(($total_tiles * $rigged_bomb_percent) / 100); 

if (!isset($_SESSION['wallet'])) {
    $_SESSION['wallet'] = 1000; 
}

// --- 2. BACKEND API LOGIC ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] == 'check_tile') {
        $user_clicks = (int)$_POST['click_count'];
        
        // RIGGED LOGIC
        if ($user_clicks >= $safe_clicks_limit) {
            $response = ['status' => 'bomb'];
        } else {
            // Yahan 2x logic hai
            $response = ['status' => 'gem', 'multiplier_factor' => 2];
        }
        echo json_encode($response);
        exit;
    }

    if ($_GET['action'] == 'sync_wallet') {
        $amount = (int)$_POST['amount'];
        $_SESSION['wallet'] += $amount;
        echo json_encode(['new_balance' => $_SESSION['wallet']]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mines 2x Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #0b0e11; color: #f8fafc; font-family: sans-serif; overflow: hidden; height: 100vh; }
        .tile-btn { background: #1c2128; border-bottom: 4px solid #000; transition: 0.1s; border-radius: 12px; }
        .tile-btn:active { transform: scale(0.95); border-bottom-width: 0; }
        .gem-bg { background: linear-gradient(135deg, #22c55e, #15803d) !important; border-color: #14532d !important; }
        .bomb-bg { background: linear-gradient(135deg, #ef4444, #b91c1c) !important; border-color: #7f1d1d !important; }
        .disabled { pointer-events: none; opacity: 0.6; }
    </style>
</head>
<body class="flex flex-col">

    <div class="p-4 flex justify-between items-center bg-[#151a21] border-b border-slate-800">
        <div class="text-xl font-black text-yellow-500 italic">MINES<span class="text-white">2X</span></div>
        <div class="bg-black px-4 py-1.5 rounded-full border border-slate-700">
            <span class="text-green-500 font-bold mr-1">₹</span>
            <span id="bal" class="font-bold"><?php echo $_SESSION['wallet']; ?></span>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4">
        <div id="grid" class="grid grid-cols-5 gap-2 w-full max-w-[360px] disabled">
            <?php for($i=0; $i<25; $i++): ?>
                <div onclick="clickTile(this)" class="tile-btn aspect-square flex items-center justify-center text-2xl">
                    <div class="w-1.5 h-1.5 bg-slate-700 rounded-full"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="w-full max-w-[360px] mt-8 flex justify-between bg-[#151a21] p-4 rounded-2xl border border-slate-800">
            <div class="text-center">
                <p class="text-[10px] text-slate-500 font-bold uppercase">Multiplier</p>
                <p id="txt-mult" class="text-xl font-black text-yellow-500">1x</p>
            </div>
            <div class="text-center border-l border-slate-700 pl-8">
                <p class="text-[10px] text-slate-500 font-bold uppercase">Potential Win</p>
                <p id="txt-win" class="text-xl font-black text-green-400">₹0</p>
            </div>
        </div>
    </div>

    <div class="p-6 bg-[#151a21] rounded-t-[30px] shadow-2xl">
        <div class="flex gap-2 mb-4">
            <input type="number" id="bet-val" value="100" class="flex-1 bg-black border border-slate-700 p-3 rounded-xl font-bold outline-none focus:border-yellow-500 text-center text-lg">
            <button onclick="document.getElementById('bet-val').value *= 2" class="bg-slate-800 px-4 rounded-xl font-bold border border-slate-700">2x</button>
        </div>
        <button id="btn-main" onclick="handleGame()" class="w-full bg-yellow-500 text-black font-black py-4 rounded-2xl uppercase tracking-widest text-lg shadow-lg shadow-yellow-500/10 active:scale-95 transition-all">
            Start Game
        </button>
    </div>

    <script>
        let playing = false;
        let count = 0;
        let bet = 0;
        let mult = 1;

        function handleGame() {
            if(!playing) start(); else cashout();
        }

        function start() {
            const b = parseInt(document.getElementById('bet-val').value);
            const bal = parseInt(document.getElementById('bal').innerText);
            if(b > bal || b <= 0) return alert("Balance kam hai!");

            playing = true;
            count = 0;
            bet = b;
            mult = 1;
            
            document.getElementById('grid').classList.remove('disabled');
            document.getElementById('btn-main').innerText = 'Cashout';
            document.getElementById('btn-main').className = 'w-full bg-green-600 text-white font-black py-4 rounded-2xl uppercase tracking-widest text-lg';
            
            document.querySelectorAll('.tile-btn').forEach(t => {
                t.className = "tile-btn aspect-square flex items-center justify-center text-2xl";
                t.innerHTML = '<div class="w-1.5 h-1.5 bg-slate-700 rounded-full"></div>';
            });
        }

        function clickTile(el) {
            if(!playing || el.classList.contains('gem-bg')) return;
            count++;
            
            let fd = new FormData();
            fd.append('click_count', count);

            fetch('?action=check_tile', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'gem') {
                    el.classList.add('gem-bg');
                    el.innerHTML = '<i class="fas fa-gem text-white"></i>';
                    mult = mult * data.multiplier_factor; // Yahan 2x ho raha hai
                    updateUI();
                } else {
                    el.classList.add('bomb-bg');
                    el.innerHTML = '<i class="fas fa-bomb text-white"></i>';
                    finish(false);
                }
            });
        }

        function updateUI() {
            let win = bet * mult;
            document.getElementById('txt-mult').innerText = mult + 'x';
            document.getElementById('txt-win').innerText = '₹' + win;
            document.getElementById('btn-main').innerText = `Cashout ₹${win}`;
        }

        function cashout() {
            updateWallet(bet * mult);
            alert("Winner! Paisa double ho gaya.");
            finish(true);
        }

        function finish(win) {
            playing = false;
            document.getElementById('grid').classList.add('disabled');
            document.getElementById('btn-main').innerText = 'Start Game';
            document.getElementById('btn-main').className = 'w-full bg-yellow-500 text-black font-black py-4 rounded-2xl uppercase tracking-widest text-lg';
            
            if(!win) {
                updateWallet(-bet);
                alert("Boom! Sab gaya.");
            }
            document.getElementById('txt-mult').innerText = '1x';
            document.getElementById('txt-win').innerText = '₹0';
        }

        function updateWallet(amt) {
            let fd = new FormData();
            fd.append('amount', amt);
            fetch('?action=sync_wallet', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => document.getElementById('bal').innerText = d.new_balance);
        }
    </script>
</body>
</html>