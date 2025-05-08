<?php
session_start();
$isLoginPage = strpos($_SERVER['REQUEST_URI'], 'login.php') !== false;

require_once 'db_connect.php';

if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $response = array('success' => false, 'message' => '');
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && $_POST['action'] == 'register') {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            
            if (strlen($username) < 3) {
                $response['message'] = "Username minimal 3 karakter!";
            } elseif (strlen($password) < 6) {
                $response['message'] = "Password minimal 6 karakter!";
            } else {
                $result = registerUser($username, $password);
                if ($result === true) {
                    $response['success'] = true;
                    $response['message'] = "Registrasi berhasil! Silakan login.";
                } else {
                    $response['message'] = $result;
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] == 'login') {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $result = loginUser($username, $password);
            
            if ($result === true) {
                $response['success'] = true;
                $response['redirect'] = "guestbook.php";
            } else {
                $response['message'] = $result;
            }
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("
        CREATE TABLE IF NOT EXISTS contentguestbook (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            msg TEXT NOT NULL,
            time DATETIME NOT NULL
        )
    ");
    $stmt->execute();
} catch (PDOException $e) {
}

function registerUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id FROM list_user WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() > 0) {
        return "Username sudah digunakan!";
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO list_user (username, password) VALUES (?, ?)");
    
    if ($stmt->execute([$username, $hash])) {
        return true;
    } else {
        return "Gagal mendaftarkan pengguna!";
    }
}

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, username, password FROM list_user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    
    return "Username atau password salah!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register - Buku Tamu Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --aqua: #9CCBB5;
            --cream: #F7E8AC;
            --coral: #F97C7C;
            --gold: #F7C873;
            --text: #2C3E50;
        }
        .gradient-bg {
            background: linear-gradient(135deg, var(--aqua) 0%, var(--cream) 100%);
            animation: gradientShift 15s ease infinite;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .form-container {
            position: absolute;
            width: 100%;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .form-container.active {
            position: relative;
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .forms-wrapper {
            position: relative;
            min-height: 280px; 
            padding: 0.5rem 0;
        }
        .card-shadow {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: floatIn 0.8s ease-out forwards;
        }
        @keyframes floatIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .input-focus:focus {
            box-shadow: 0 0 0 3px var(--aqua);
            transition: all 0.2s ease;
        }
        .tab-btn {
            transition: all 0.3s ease;
            position: relative;
        }
        .tab-btn::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background-color: var(--gold);
            transition: all 0.3s ease;
        }
        .tab-btn:hover::after {
            width: 70%;
            left: 15%;
        }
        .tab-active::after {
            width: 70%;
            left: 15%;
        }
        .tab-active {
            border-bottom-color: var(--gold);
            color: var(--gold);
        }
        .btn-hover {
            transition: all 0.3s ease;
        }
        .btn-hover:hover {
            transform: translateY(-2px);
        }
        .btn-hover:active {
            transform: translateY(1px);
        }
        .input-animated {
            transition: all 0.3s ease;
        }
        .input-animated:focus {
            transform: scale(1.01);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s ease;
        }
        .modal.show {
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            animation: modalFadeIn 0.4s ease-out;
            transform-origin: center;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s ease;
        }
        .success-modal.show {
            background-color: rgba(0,0,0,0.5);
        }
        .success-modal-content {
            animation: modalFadeIn 0.4s ease-out;
        }
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .notification {
            animation: slideIn 0.4s ease-out forwards;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Custom overrides for palette */
        .bg-main-btn { background: linear-gradient(90deg, var(--coral), var(--gold)); }
        .bg-accent-btn { background: var(--aqua); color: var(--text); }
        .bg-form { background: var(--cream); }
        .text-coral { color: var(--coral); }
        .text-gold { color: var(--gold); }
        .text-aqua { color: var(--aqua); }
        .border-gold { border-color: var(--gold); }
        .border-coral { border-color: var(--coral); }
        .border-aqua { border-color: var(--aqua); }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div id="errorModal" class="modal">
        <div class="modal-content bg-white rounded-xl p-6 max-w-sm w-full mx-4 card-shadow">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-[#FF6B6B]"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Login Gagal</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-600" id="modalErrorMessage">Username atau password salah. Silakan coba lagi atau daftar jika belum memiliki akun.</p>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" onclick="closeModal('errorModal')" class="px-4 py-2 bg-[#FF6B6B] text-white rounded-md hover:bg-[#FF8E8E] focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="successModal" class="success-modal">
        <div class="success-modal-content bg-white rounded-xl p-6 max-w-sm w-full mx-4 card-shadow">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-[#F5E6D3] flex items-center justify-center">
                        <i class="fas fa-check-circle text-[#E2C4A6]"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">Congratulations!</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-600" id="modalSuccessMessage">Registrasi berhasil! Silakan login.</p>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" onclick="closeModal('successModal')" class="px-4 py-2 bg-[#E2C4A6] text-white rounded-md hover:bg-[#D4B08C] focus:outline-none focus:ring-2 focus:ring-[#E2C4A6]">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl card-shadow overflow-hidden">
            <div class="flex border-b relative">
                <button onclick="showForm('login')" class="tab-btn flex-1 py-4 px-6 text-center font-medium text-gray-700 border-b-2 border-transparent hover:text-[#E2C4A6] tab-active" id="loginTab">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
                <button onclick="showForm('register')" class="tab-btn flex-1 py-4 px-6 text-center font-medium text-gray-700 border-b-2 border-transparent hover:text-[#E2C4A6]" id="registerTab">
                    <i class="fas fa-user-plus mr-2"></i>Daftar
                </button>
            </div>
            
            <div class="p-6">
                <div id="successMessage" class="hidden notification bg-[#F5E6D3] border border-[#E2C4A6] text-[#8B7355] px-4 py-3 rounded mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span id="successText"></span>
                </div>

                <div id="errorMessage" class="hidden notification bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="errorText"></span>
                </div>

                <div id="loadingIndicator" class="hidden notification bg-[#F5E6D3] border border-[#E2C4A6] text-[#8B7355] px-4 py-3 rounded mb-4 flex items-center">
                    <i class="fas fa-circle-notch fa-spin mr-2"></i>
                    <span>Memproses...</span>
                </div>

                <div class="forms-wrapper">
                    <form id="loginForm" class="form-container active">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-1">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" name="username" id="loginUsername" required autofocus
                                    class="input-animated w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg input-focus focus:border-[#E2C4A6] focus:outline-none transition duration-150">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" name="password" id="loginPassword" required 
                                    class="input-animated w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg input-focus focus:border-[#E2C4A6] focus:outline-none transition duration-150">
                                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 cursor-pointer" data-target="loginPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" id="loginButton"
                                class="btn-hover w-full bg-main-btn hover:bg-accent-btn text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                        </button>
                    </form>
                    <form id="registerForm" class="form-container">
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-1">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" name="username" id="registerUsername" required autofocus
                                    class="input-animated w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg input-focus focus:border-[#E2C4A6] focus:outline-none transition duration-150">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Minimal 3 karakter</p>
                        </div>
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" name="password" id="registerPassword" required 
                                    class="input-animated w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg input-focus focus:border-[#E2C4A6] focus:outline-none transition duration-150">
                                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 cursor-pointer" data-target="registerPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                        </div>
                        
                        <button type="submit" id="registerButton"
                                class="btn-hover w-full bg-main-btn hover:bg-accent-btn text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                document.querySelector('.card-shadow').style.opacity = 1;
            }, 100);
            
            document.getElementById('loginTab').classList.add('tab-active', 'text-[#E2C4A6]');
            document.getElementById('loginTab').classList.remove('border-transparent');
            
            // Handle form submissions
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const username = document.getElementById('loginUsername').value.trim();
                const password = document.getElementById('loginPassword').value.trim();
                
                if (!username || !password) {
                    showMessage('error', 'Mohon isi semua field');
                    return;
                }
                
                showLoading(true);
                
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('username', username);
                formData.append('password', password);
                
                fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    
                    if (data.success) {
                        showMessage('success', 'Login berhasil! Mengalihkan...');
                        
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 800);
                    } else {
                        animateError('loginForm');
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showMessage('error', 'Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Error:', error);
                });
            });
            
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const username = document.getElementById('registerUsername').value.trim();
                const password = document.getElementById('registerPassword').value.trim();
                
                if (!username || !password) {
                    showMessage('error', 'Mohon isi semua field');
                    return;
                }
                
                if (username.length < 3) {
                    showMessage('error', 'Username minimal 3 karakter');
                    return;
                }
                
                if (password.length < 6) {
                    showMessage('error', 'Password minimal 6 karakter');
                    return;
                }
                
                showLoading(true);
                const formData = new FormData();
                formData.append('action', 'register');
                formData.append('username', username);
                formData.append('password', password);
                
                fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showLoading(false);
                    
                    if (data.success) {
                        document.getElementById('registerForm').reset();
                        showModal('successModal', data.message);
                        
                        setTimeout(() => {
                            showForm('login');
                        }, 1500);
                    } else {
                        animateError('registerForm');
                        showMessage('error', data.message);
                    }
                })
                .catch(error => {
                    showLoading(false);
                    showMessage('error', 'Terjadi kesalahan. Silakan coba lagi.');
                    console.error('Error:', error);
                });
            });
            
            const buttons = document.querySelectorAll('button[type="submit"]');
            buttons.forEach(btn => {
                btn.addEventListener('mousedown', function() {
                    this.classList.add('scale-95');
                    setTimeout(() => {
                        this.classList.remove('scale-95');
                    }, 200);
                });
            });
            
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('scale-101');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('scale-101');
                });
            });

            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (targetInput.type === 'password') {
                        targetInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        targetInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });

        function showForm(formType) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            
            document.getElementById('successMessage').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
            
            if (formType === 'login') {
                loginTab.classList.add('tab-active', 'text-[#E2C4A6]');
                loginTab.classList.remove('border-transparent');
                registerTab.classList.remove('tab-active', 'text-[#E2C4A6]');
                registerTab.classList.add('border-transparent', 'text-gray-700');
                
                registerForm.classList.remove('active');
                setTimeout(() => {
                    loginForm.classList.add('active');
                }, 300);
            } else {
                registerTab.classList.add('tab-active', 'text-[#E2C4A6]');
                registerTab.classList.remove('border-transparent');
                loginTab.classList.remove('tab-active', 'text-[#E2C4A6]');
                loginTab.classList.add('border-transparent', 'text-gray-700');
                
                loginForm.classList.remove('active');
                setTimeout(() => {
                    registerForm.classList.add('active');
                }, 300);
            }
        }
        
        function showMessage(type, message) {
[
            document.getElementById('successMessage').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
            
            if (type === 'success') {
                document.getElementById('successText').textContent = message;
                const successMsg = document.getElementById('successMessage');
                successMsg.classList.remove('hidden');
                successMsg.classList.add('fade-in');
            } else if (type === 'error') {
                document.getElementById('errorText').textContent = message;
                const errorMsg = document.getElementById('errorMessage');
                errorMsg.classList.remove('hidden');
                errorMsg.classList.add('fade-in');
            }
        }
        
        function showModal(modalId, message) {
            if (modalId === 'successModal') {
                document.getElementById('modalSuccessMessage').textContent = message;
            } else {
                document.getElementById('modalErrorMessage').textContent = message;
            }
            
            const modal = document.getElementById(modalId);
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        function showLoading(show) {
            const loadingIndicator = document.getElementById('loadingIndicator');
            if (show) {
                loadingIndicator.classList.remove('hidden');
                loadingIndicator.classList.add('fade-in');
            } else {
                loadingIndicator.classList.add('hidden');
                loadingIndicator.classList.remove('fade-in');
            }
        }
        
        function animateError(formId) {
            const form = document.getElementById(formId);
            form.classList.add('animate-shake');
            setTimeout(() => {
                form.classList.remove('animate-shake');
            }, 500);
        }
        
        document.querySelectorAll('.btn-hover').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.add('scale-95');
                setTimeout(() => this.classList.remove('scale-95'), 200);
            });
        });
    </script>
</body>
</html>
