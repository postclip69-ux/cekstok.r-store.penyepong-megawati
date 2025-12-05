<?php
// ==========================================
// KONFIGURASI & API HANDLER (BACKEND)
// ==========================================
$api_url = 'https://panel.khfy-store.com/api_v3/cek_stock_akrab';

// Fungsi cURL untuk mengambil data server-to-server
function fetch_stock_data($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Abaikan SSL jika sertifikat bermasalah
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout cepat agar tidak loading lama
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result === false) return null;
    return json_decode($result, true);
}

// --- AJAX HANDLER ---
// Bagian ini menangani request dari Javascript tanpa me-load ulang halaman
if (isset($_GET['ajax_update'])) {
    header('Content-Type: application/json');
    $data = fetch_stock_data($api_url);
    echo json_encode($data);
    exit; // Stop script disini agar tidak merender HTML
}

// --- INITIAL LOAD ---
// Ambil data pertama kali saat halaman dibuka
$initial_data = fetch_stock_data($api_url);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Stock Monitor | KHFY</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #020617;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(76, 29, 149, 0.1) 0%, transparent 50%), 
                radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.1) 0%, transparent 50%);
            min-height: 100vh;
        }

        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        /* Loading Bar Animation */
        .progress-bar {
            width: 100%;
            height: 2px;
            background-color: rgba(255,255,255,0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 50;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            width: 0%;
            transition: width 3s linear;
        }
    </style>
</head>
<body class="text-slate-200 antialiased overflow-x-hidden">

    <div class="progress-bar"><div id="timer-bar" class="progress-fill"></div></div>

    <div class="max-w-7xl mx-auto px-4 py-12 md:py-20">
        
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-xs font-bold text-blue-400 uppercase tracking-widest mb-6">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                Auto Refresh: 3s
            </div>
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-2" style="font-family: 'Space Grotesk'">
                KHFY <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">Monitor</span>
            </h1>
            <p class="text-slate-500" id="last-updated">Menunggu update...</p>
        </div>

        <div id="stock-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            if (isset($initial_data['data'])) {
                foreach ($initial_data['data'] as $item) {
                    // Kita render dummy structure disini, nanti JS akan langsung menimpa
                    // Agar user melihat layout langsung tanpa menunggu JS load
                    include_card_template($item);
                }
            } else {
                echo '<div class="col-span-3 text-center text-red-400">Gagal memuat data awal.</div>';
            }

            // Fungsi helper PHP untuk render awal (biar kodenya sama dengan JS logic di bawah)
            function include_card_template($item) {
                $stock = intval($item['sisa_slot']);
                $avail = $stock > 0;
                $color = $avail ? 'text-emerald-400' : 'text-rose-500';
                $border = $avail ? 'border-emerald-500/30' : 'border-rose-500/20';
                $bg_badge = $avail ? 'bg-emerald-500/10' : 'bg-rose-500/10';
                $status = $avail ? 'TERSEDIA' : 'HABIS';
                $glow = $avail ? 'shadow-[0_0_15px_rgba(16,185,129,0.15)]' : '';
                
                echo "
                <div class='glass-card rounded-2xl p-6 relative group overflow-hidden'>
                    <div class='flex justify-between items-start mb-6'>
                        <div>
                            <p class='text-xs font-mono text-slate-500 mb-1 uppercase tracking-wider'>{$item['type']}</p>
                            <h3 class='text-2xl font-bold text-white'>{$item['nama']}</h3>
                        </div>
                        <div class='h-10 w-10 rounded-lg {$bg_badge} flex items-center justify-center border border-white/5'>
                            <i class='fa-solid " . ($avail ? 'fa-box-open text-emerald-400' : 'fa-lock text-rose-500') . "'></i>
                        </div>
                    </div>
                    <div class='flex items-end justify-between'>
                        <div>
                            <p class='text-xs text-slate-400 mb-1'>Stok Saat Ini</p>
                            <span class='text-4xl font-bold {$color}' style='font-family: \"Space Grotesk\"'>{$stock}</span>
                        </div>
                        <span class='px-3 py-1 rounded-md text-[10px] font-bold border {$bg_badge} {$border} {$color} {$glow}'>
                            {$status}
                        </span>
                    </div>
                </div>";
            }
            ?>
        </div>
    </div>

    <script>
        const grid = document.getElementById('stock-grid');
        const timerBar = document.getElementById('timer-bar');
        const updateLabel = document.getElementById('last-updated');

        function startTimer() {
            // Reset dan jalankan animasi progress bar
            timerBar.style.transition = 'none';
            timerBar.style.width = '0%';
            setTimeout(() => {
                timerBar.style.transition = 'width 3s linear';
                timerBar.style.width = '100%';
            }, 50);
        }

        async function updateStock() {
            try {
                // Fetch ke file ini sendiri dengan parameter ?ajax_update=1
                const response = await fetch('?ajax_update=1');
                const json = await response.json();

                if (json && json.ok && json.data) {
                    renderCards(json.data);
                    updateLabel.innerHTML = `<i class="fa-solid fa-check-circle text-emerald-500 mr-1"></i> Data Updated: ${new Date().toLocaleTimeString()}`;
                }
            } catch (error) {
                console.error("Gagal update:", error);
                updateLabel.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-rose-500 mr-1"></i> Koneksi Error...`;
            }
            
            // Ulangi timer
            startTimer();
        }

        function renderCards(items) {
            let html = '';
            items.forEach(item => {
                const stock = parseInt(item.sisa_slot);
                const isAvail = stock > 0;
                
                // Logic Styles (Sama dengan PHP di atas)
                const colorClass = isAvail ? 'text-emerald-400' : 'text-rose-500';
                const iconClass = isAvail ? 'fa-box-open text-emerald-400' : 'fa-lock text-rose-500';
                const badgeBg = isAvail ? 'bg-emerald-500/10' : 'bg-rose-500/10';
                const badgeBorder = isAvail ? 'border-emerald-500/30' : 'border-rose-500/20';
                const statusText = isAvail ? 'TERSEDIA' : 'HABIS';
                const glow = isAvail ? 'shadow-[0_0_15px_rgba(16,185,129,0.15)]' : '';

                html += `
                <div class='glass-card rounded-2xl p-6 relative group overflow-hidden animate-fade'>
                    <div class='flex justify-between items-start mb-6'>
                        <div>
                            <p class='text-xs font-mono text-slate-500 mb-1 uppercase tracking-wider'>${item.type}</p>
                            <h3 class='text-2xl font-bold text-white'>${item.nama}</h3>
                        </div>
                        <div class='h-10 w-10 rounded-lg ${badgeBg} flex items-center justify-center border border-white/5'>
                            <i class='fa-solid ${iconClass}'></i>
                        </div>
                    </div>
                    <div class='flex items-end justify-between'>
                        <div>
                            <p class='text-xs text-slate-400 mb-1'>Stok Saat Ini</p>
                            <span class='text-4xl font-bold ${colorClass}' style='font-family: "Space Grotesk"'>${stock}</span>
                        </div>
                        <span class='px-3 py-1 rounded-md text-[10px] font-bold border ${badgeBg} ${badgeBorder} ${colorClass} ${glow}'>
                            ${statusText}
                        </span>
                    </div>
                </div>`;
            });
            grid.innerHTML = html;
        }

        // Mulai sistem
        startTimer();
        setInterval(updateStock, 3000); // Jalan setiap 3000ms (3 detik)

    </script>
</body>
</html>

