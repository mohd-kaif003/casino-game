<?php
session_start();

// --- ADMIN MASTER CONTROLS ---
$max_safe_multiplier = 4; // User ko maximum 4x tak hi jeetne dena hai (uske baad bomb)
$win_probability = 30;    // Har click pe 30% chance hai ki Diamond nikle (baaki chance Bomb ka)
// -----------------------------

if (!isset($_SESSION['wallet'])) { $_SESSION['wallet'] = 1000; }

// API Logic
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] == 'check_tile') {
        $current_mult = (float)$_POST['current_mult'];
        
        // MASTER LOGIC: 
        // 1. Agar user ka multiplier admin limit se upar gaya toh BOMB.
        // 2. Ya fir random probability ke hisab se BOMB.
        
        $random_chance = rand(1, 100);
        
        if ($current_mult >= $max_safe_multiplier || $random_chance > $win_probability) {
            $response = ['status' => 'bomb'];
        } else {
            $response = ['status' => 'gem', 'factor' => 2]; // Paisa double (2x)
        }
        echo json_encode($response);
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
    <title>Mines Master Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #080a0d; color: #fff; font-family: 'Inter', sans-serif; overflow: hidden; height: 100vh; }
        .tile { background: #1a1f26; border-radius: 12px; transition: 0.1s; border-bottom: 4px solid #000; }
        .tile:active { transform: scale(0.95); border-bottom-width: 0; }
        .gem-bg { background: linear-gradient(135deg, #10b981, #064e3b) !important; border-color: #064e3b !important; }
        .bomb-bg { background: linear-gradient(135deg, #ef4444, #7f1d1d) !important; border-color: #450a0a !important; }
        .disabled { pointer-events: none; opacity: 0.5; }
        .glass { background: rgba(21, 26, 33, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="flex flex-col">

    <div class="p-4 flex justify-between items-center border-b border-gray-800 glass">
        <div class="text-xl font-black text-blue-500 italic">ULTRA<span class="text-white">MINES</span></div>
        <div class="bg-black/40 px-4 py-1 rounded-full border border-blue-900/30">
            <span class="text-blue-400 font-bold mr-1">₹</span>
            <span id="bal-val" class="font-mono"><?php echo $_SESSION['wallet']; ?></span>
        </div>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4">
        
        

        <div id="grid" class="grid grid-cols-5 gap-2 w-full max-w-[360px] disabled">
            <?php for($i=0; $i<25; $i++): ?>
                <div onclick="tapTile(this)" class="tile aspect-square flex items-center justify-center text-xl">
                    <div class="w-1.5 h-1.5 bg-blue-500/20 rounded-full"></div>
                </div>
            <?php endfor; ?>
        </div>

        <div class="w-full max-w-[360px] mt-8 flex justify-between px-2">
            <div class="text-center">
                <p class="text-[10px] text-gray-500 font-bold">MULTIPLIER</p>
                <p id="m-text" class="text-2xl font-black text-blue-400">1x</p>
            </div>
            <div class="text-center">
                <p class="text-[10px] text-gray-500 font-bold">PROFIT</p>
                <p id="p-text" class="text-2xl font-black text-green-400">₹0</p>
            </div>
        </div>
    </div>

    <div class="p-6 glass rounded-t-[35px] shadow-2xl">
        <div class="flex gap-2 mb-4">
            <input type="number" id="bet-amt" value="100" class="flex-1 bg-black/50 border border-gray-700 p-4 rounded-2xl font-bold outline-none focus:border-blue-500 text-center text-lg">
            <button onclick="document.getElementById('bet-amt').value *= 2" class="bg-gray-800 px-6 rounded-2xl font-bold border border-gray-700">2x</button>
        </div>
        <button id="main-btn" onclick="action()" class="w-full bg-blue-600 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg shadow-lg shadow-blue-600/20 active:scale-95 transition-all">
            Bet
        </button>
    </div>

    <script>
        let playing = false;
        let bet = 0;
        let mult = 1;

        function action() {
            if(!playing) start(); else cashout();
        }

        function start() {
            const b = parseInt(document.getElementById('bet-amt').value);
            const bal = parseInt(document.getElementById('bal-val').innerText);
            if(b > bal || b <= 0) return alert("Paisa kam hai bhai!");

            playing = true;
            bet = b;
            mult = 1;
            
            document.getElementById('grid').classList.remove('disabled');
            document.getElementById('main-btn').innerText = 'Cashout';
            document.getElementById('main-btn').className = 'w-full bg-green-600 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg';
            
            document.querySelectorAll('.tile').forEach(t => {
                t.className = "tile aspect-square flex items-center justify-center text-xl";
                t.innerHTML = '<div class="w-1.5 h-1.5 bg-blue-500/20 rounded-full"></div>';
            });
        }

        function tapTile(el) {
            if(!playing || el.classList.contains('gem-bg')) return;
            
            const fd = new FormData();
            fd.append('current_mult', mult);

            fetch('?action=check_tile', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'gem') {
                    el.classList.add('gem-bg');
                    el.innerHTML = '<i class="fas fa-gem text-white"></i>';
                    mult = mult * data.factor;
                    updateUI();
                } else {
                    el.classList.add('bomb-bg');
                    el.innerHTML = '<i class="fas fa-bomb text-white"></i>';
                    finish(false);
                }
            });
        }

        function updateUI() {
            document.getElementById('m-text').innerText = mult + 'x';
            document.getElementById('p-text').innerText = '₹' + (bet * mult);
            document.getElementById('main-btn').innerText = `Cashout ₹${bet * mult}`;
        }

        function cashout() {
            updateWallet(bet * mult);
            alert("Jeet gaye bhai! ₹" + (bet * mult));
            finish(true);
        }

        function finish(win) {
            playing = false;
            document.getElementById('grid').classList.add('disabled');
            document.getElementById('main-btn').innerText = 'Bet';
            document.getElementById('main-btn').className = 'w-full bg-blue-600 text-white font-black py-5 rounded-2xl uppercase tracking-widest text-lg';
            
            if(!win) {
                updateWallet(-bet);
                alert("Bomb phat gaya!");
            }
            document.getElementById('m-text').innerText = '1x';
            document.getElementById('p-text').innerText = '₹0';
        }

        function updateWallet(amt) {
            const fd = new FormData();
            fd.append('amount', amt);
            fetch('?action=sync', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => document.getElementById('bal-val').innerText = d.bal);
        }
    </script>
</body>
</html>