<?php
session_start();

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

require_once 'db_connect.php';

$entries = [];
try {
    $stmt = $pdo->query("SELECT * FROM contentguestbook ORDER BY time DESC");
    $entries = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');
    
    $nama = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $msg = htmlspecialchars($_POST['message']);
    date_default_timezone_set('Asia/Jakarta'); 
    $time = date('Y-m-d H:i:s');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO contentguestbook (nama, email, msg, time) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nama, $email, $msg, $time])) {
            $newEntry = [
                'timestamp' => $time,
                'name' => $nama,
                'email' => $email,
                'message' => $msg
            ];
            
            echo json_encode(['status' => 'success', 'entry' => $newEntry]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan entri ke database']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan database']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --aqua: #9CCBB5;
            --cream: #F7E8AC;
            --coral: #F97C7C;
            --gold: #F7C873;
            --text: #2C3E50;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--aqua) 0%, var(--cream) 100%);
            color: var(--text);
            transition: all 0.3s ease;
        }
        .entry-card {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            animation: slideFadeScaleIn 0.7s cubic-bezier(.23,1.02,.32,1) forwards;
        }
        @keyframes slideFadeScaleIn {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.98);
            }
            60% {
                opacity: 1;
                transform: translateY(-8px) scale(1.03);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .entry-card.instant {
            animation: none;
            opacity: 1;
            transform: none;
        }
        .toast {
            background: linear-gradient(90deg, var(--gold), var(--coral));
            color: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1rem 2rem;
            font-weight: 500;
            font-size: 1rem;
            min-width: 260px;
            max-width: 90vw;
            z-index: 50;
            pointer-events: none;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            position: fixed;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .toast.show {
            right: auto;
            top: 32px;
            opacity: 1;
        }
        .toast.hide {
            opacity: 0;
        }
        .gradient-text {
            background: linear-gradient(90deg, var(--coral), var(--gold));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .glass-nav {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background-color: rgba(247, 232, 172, 0.9);
            border-bottom: 1px solid rgba(224, 224, 224, 0.5);
        }
        .avatar {
            background: linear-gradient(135deg, var(--coral) 0%, var(--gold) 100%);
            color: #fff;
            font-weight: bold;
        }
        .form-input {
            background-color: var(--cream);
            border: 1px solid var(--aqua);
            color: var(--text);
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: var(--coral);
            box-shadow: 0 0 0 2px var(--aqua);
        }
        .card {
            background-color: #fffbe9;
            border: 2px solid var(--aqua);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .bg-main-btn { background: linear-gradient(90deg, var(--coral), var(--gold)); }
        .bg-accent-btn { background: var(--aqua); color: var(--text); }
        .text-coral { color: var(--coral); }
        .text-gold { color: var(--gold); font-weight: 500; }
        .text-aqua { color: var(--aqua); }
        .border-gold { border-color: var(--gold); }
        .border-coral { border-color: var(--coral); }
        .border-aqua { border-color: var(--aqua); }
        .email-link {
            color: var(--coral);
            font-weight: 500;
        }
        .entry-card .font-semibold {
            color: var(--text);
        }
        .entry-card .leading-relaxed {
            color: #444;
        }
        .entries-panel {
            display: flex;
            flex-direction: column;
            gap: 2.5rem;
        }
        @media (min-width: 1024px) {
            .main-flex {
                display: flex;
                gap: 2.5rem;
                align-items: flex-start;
            }
            .form-panel {
                flex: 0 0 340px;
                min-width: 320px;
            }
            .entries-panel {
                flex: 1 1 0%;
            }
        }
        .modern-card {
            display: flex;
            background: #fffbe9;
            border-radius: 1.5rem;
            box-shadow: 0 4px 24px 0 rgba(156,203,181,0.10), 0 1.5px 6px 0 rgba(249,124,124,0.07);
            border: none;
            position: relative;
            overflow: visible;
            min-height: 120px;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .modern-card:hover {
            box-shadow: 0 8px 32px 0 rgba(156,203,181,0.18), 0 3px 12px 0 rgba(249,124,124,0.12);
            transform: translateY(-4px) scale(1.01);
        }
        .accent-bar {
            width: 7px;
            border-radius: 1.5rem 0 0 1.5rem;
            background: linear-gradient(180deg, var(--coral), var(--gold));
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        .modern-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--coral), var(--gold));
            color: #fff;
            font-size: 1.7rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(249,124,124,0.10);
            border: 3px solid #fff;
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            z-index: 2;
        }
        .modern-card-content {
            flex: 1 1 0%;
            padding: 1.5rem 2rem 1.5rem 5.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .modern-header-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .modern-name {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text);
        }
        .modern-time {
            font-size: 0.95rem;
            color: #a0a0a0;
            font-weight: 400;
        }
        .modern-email-badge {
            display: inline-block;
            background: var(--aqua);
            color: var(--coral);
            font-size: 0.93rem;
            font-weight: 500;
            border-radius: 0.7rem;
            padding: 0.18rem 0.8rem 0.18rem 0.6rem;
            margin: 0.3rem 0 0.7rem 0;
            letter-spacing: 0.01em;
        }
        .modern-bubble {
            background: linear-gradient(90deg, #fff, var(--cream) 80%);
            border-radius: 1.1rem;
            padding: 1.1rem 1.3rem 1.1rem 1.1rem;
            margin-top: 0.2rem;
            font-size: 1.08rem;
            color: #444;
            box-shadow: 0 1px 4px rgba(156,203,181,0.07);
            position: relative;
        }
        .modern-bubble .fa-quote-left {
            font-size: 1.2em;
            color: var(--gold);
            margin-right: 0.5em;
            vertical-align: -0.2em;
        }
        @media (max-width: 1023px) {
            .main-flex {
                display: block;
            }
            .form-panel, .entries-panel {
                width: 100%;
                min-width: 0;
            }
            .modern-card-content {
                padding: 1.5rem 1.2rem 1.5rem 5.5rem;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#F7F9F9] to-[#F5E6D3]">
    <header class="fixed w-full top-0 z-50 glass-nav">
        <nav class="container mx-auto px-6 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="hamburger lg:hidden cursor-pointer" onclick="toggleMenu()">
                    <div class="w-6 h-0.5 bg-gray-600 my-1.5 transition-all duration-300"></div>
                    <div class="w-6 h-0.5 bg-gray-600 my-1.5 transition-all duration-300"></div>
                    <div class="w-6 h-0.5 bg-gray-600 my-1.5 transition-all duration-300"></div>
                </div>
                <h1 class="text-2xl font-bold gradient-text">Buku Tamu Digital</h1>
            </div>
            
            <div class="flex items-center space-x-6">
                <div class="hidden lg:flex items-center space-x-4">
                    <a href="logout.php" class="px-4 py-2 bg-gradient-to-r from-[#FF6B6B] to-[#FF8E8E] text-white rounded-lg hover:opacity-90 transition-all shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="lg:hidden menu-items bg-white overflow-hidden max-h-0 transition-all duration-300">
            <div class="container mx-auto px-6 py-2">
                <a href="logout.php" class="block py-3 text-[#FF6B6B] hover:text-[#FF8E8E] transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 pt-24 pb-8">
        <div class="main-flex">
            <div class="form-panel">
                <div class="sticky top-28 p-6 rounded-2xl card backdrop-blur-sm shadow-xl animate__animated animate__fadeIn">
                    <h2 class="text-xl font-semibold mb-6 text-gray-800 flex items-center">
                        <i class="fas fa-pen-fancy mr-3 gradient-text"></i>Tinggalkan Pesan
                    </h2>
                    <form id="guestbookForm" method="post" class="space-y-5">
                        <div class="relative">
                            <input type="text" name="name" required 
                                class="form-input w-full px-4 py-3 rounded-xl focus:ring-2 focus:ring-[#E2C4A6] transition-all shadow-sm"
                                placeholder="Nama Anda">
                            <i class="fas fa-user absolute right-4 top-3.5 text-gray-400"></i>
                        </div>
                        <div class="relative">
                            <input type="email" name="email" 
                                class="form-input w-full px-4 py-3 rounded-xl focus:ring-2 focus:ring-[#E2C4A6] transition-all shadow-sm"
                                placeholder="Email (opsional)">
                            <i class="fas fa-envelope absolute right-4 top-3.5 text-gray-400"></i>
                        </div>
                        <div class="relative">
                            <textarea name="message" required rows="4"
                                class="form-input w-full px-4 py-3 rounded-xl focus:ring-2 focus:ring-[#E2C4A6] transition-all shadow-sm"
                                placeholder="Pesan Anda"></textarea>
                            <i class="fas fa-comment-dots absolute right-4 top-3.5 text-gray-400"></i>
                        </div>
                        <button type="submit" 
                                class="w-full px-4 py-3 bg-main-btn hover:bg-accent-btn text-white rounded-xl hover:opacity-90 transition-all shadow-md hover:shadow-lg">
                            <i class="fas fa-paper-plane mr-2"></i>Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
            <div class="entries-panel" id="entriesContainer">
                <?php if(empty($entries)): ?>
                    <div class="card p-8 text-center rounded-2xl">
                        <i class="fas fa-book-open text-4xl mb-4 gradient-text"></i>
                        <h3 class="text-xl font-medium text-gray-600">Belum ada pesan</h3>
                        <p class="text-gray-500 mt-2">Jadilah yang pertama meninggalkan pesan!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <div class="modern-card entry-card">
                            <div class="accent-bar"></div>
                            <div class="modern-avatar">
                                <?= strtoupper(substr($entry['nama'], 0, 1)) ?>
                            </div>
                            <div class="modern-card-content">
                                <div class="modern-header-row">
                                    <span class="modern-name"><?= htmlspecialchars($entry['nama']) ?></span>
                                    <span class="modern-time"><?= htmlspecialchars($entry['time']) ?></span>
                                </div>
                                <?php if(!empty($entry['email'])): ?>
                                    <span class="modern-email-badge">
                                        <i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($entry['email']) ?>
                                    </span>
                                <?php endif; ?>
                                <div class="modern-bubble mt-2">
                                    <i class="fas fa-quote-left"></i>
                                    <?= nl2br(htmlspecialchars($entry['msg'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const hamburger = document.querySelector('.hamburger');
            const menu = document.querySelector('.menu-items');
            hamburger.classList.toggle('active');
            menu.style.maxHeight = menu.style.maxHeight ? null : menu.scrollHeight + 'px';
            
            const bars = hamburger.querySelectorAll('div');
            if (hamburger.classList.contains('active')) {
                bars[0].style.transform = 'translateY(6px) rotate(45deg)';
                bars[1].style.opacity = '0';
                bars[2].style.transform = 'translateY(-6px) rotate(-45deg)';
            } else {
                bars[0].style.transform = '';
                bars[1].style.opacity = '';
                bars[2].style.transform = '';
            }
        }

        function showToast(message, type = 'success') {
            let toast = document.getElementById('toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast';
                document.body.appendChild(toast);
            }
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle text-gold' : 'fa-exclamation-circle text-coral'} mr-3"></i>
                    <span>${message}</span>
                </div>
            `;
            toast.className = 'toast show';
            toast.style.display = 'block';
            setTimeout(() => {
                toast.classList.add('hide');
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 500);
            }, 3000);
        }

        document.getElementById('guestbookForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('guestbook.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    form.reset();
                    showToast('Pesan berhasil dikirim!', 'success');
                    
                    const entryHTML = `
                        <div class="modern-card entry-card">
                            <div class="accent-bar"></div>
                            <div class="modern-avatar">
                                ${result.entry.name.charAt(0).toUpperCase()}
                            </div>
                            <div class="modern-card-content">
                                <div class="modern-header-row">
                                    <span class="modern-name">${result.entry.name}</span>
                                    <span class="modern-time">${result.entry.timestamp}</span>
                                </div>
                                ${result.entry.email ? `
                                <span class="modern-email-badge">
                                    <i class="fas fa-envelope mr-1"></i>${result.entry.email}
                                </span>` : ''}
                                <div class="modern-bubble mt-2">
                                    <i class="fas fa-quote-left"></i>
                                    ${result.entry.message.replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    `;
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = entryHTML;
                    const newEntry = tempDiv.firstElementChild;
                    newEntry.classList.remove('instant');
                    const emptyState = document.querySelector('#entriesContainer > div:not(.entry-card)');
                    if (emptyState) emptyState.remove();
                    document.getElementById('entriesContainer').prepend(newEntry);
                    setTimeout(() => {
                        newEntry.style.opacity = '1';
                        newEntry.style.transform = 'translateY(0) scale(1)';
                    }, 50);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('Terjadi kesalahan saat mengirim pesan', 'error');
            } finally {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Kirim Pesan';
                submitBtn.disabled = false;
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const entries = document.querySelectorAll('.entry-card');
            entries.forEach((entry, index) => {
                setTimeout(() => {
                    entry.style.opacity = '1';
                    entry.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>