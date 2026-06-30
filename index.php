<?php
// ==================== KONFIGURASI DATABASE ====================
session_start();

// Konfigurasi database
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'todo_app';

// Koneksi database
$conn = new mysqli($host, $user, $password);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat database jika belum ada
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Buat tabel users (hapus dulu jika ada masalah)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Buat tabel tasks
$conn->query("CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==================== FUNGSI HELPER ====================
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ==================== PROSES LOGIN ====================
$login_error = '';
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $login_error = "Username/email dan password harus diisi!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ?");
                exit();
            } else {
                $login_error = "Password salah!";
            }
        } else {
            $login_error = "Username/email tidak ditemukan!";
        }
        $stmt->close();
    }
}

// ==================== PROSES REGISTER ====================
$register_error = '';
$register_success = '';

// Reset form jika perlu
if (isset($_GET['reset_form'])) {
    $register_error = '';
    $register_success = '';
}

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($username) || empty($email) || empty($password)) {
        $register_error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Email tidak valid! Contoh: nama@email.com";
    } elseif (strlen($password) < 6) {
        $register_error = "Password minimal 6 karakter!";
    } elseif ($password != $confirm_password) {
        $register_error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah username atau email sudah ada
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $register_error = "Username atau email sudah terdaftar! Silakan gunakan yang lain.";
        } else {
            // Hash password dan simpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $register_success = "Registrasi berhasil! Silakan login.";
                // Reset error
                $register_error = '';
            } else {
                $register_error = "Registrasi gagal: " . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

// ==================== PROSES LOGOUT ====================
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit();
}

// ==================== PROSES TO-DO LIST (hanya jika login) ====================
if (isLoggedIn()) {
    // Tambah tugas
    if (isset($_POST['add_task'])) {
        $task = trim($_POST['task']);
        $priority = $_POST['priority'] ?? 'medium';
        $user_id = $_SESSION['user_id'];
        if (!empty($task)) {
            $stmt = $conn->prepare("INSERT INTO tasks (user_id, task, priority) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $task, $priority);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: ?");
        exit();
    }

    // Hapus tugas
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        header("Location: ?");
        exit();
    }

    // Ubah status
    if (isset($_GET['toggle'])) {
        $id = intval($_GET['toggle']);
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("SELECT status FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $new_status = ($row['status'] == 'pending') ? 'completed' : 'pending';
            $update = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $update->bind_param("sii", $new_status, $id, $user_id);
            $update->execute();
            $update->close();
        }
        $stmt->close();
        header("Location: ?");
        exit();
    }
}

// ==================== AMBIL DATA TUGAS (jika login) ====================
if (isLoggedIn()) {
    $filter = $_GET['filter'] ?? 'all';
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM tasks WHERE user_id = ?";
    if ($filter != 'all') {
        $sql .= " AND priority = ?";
    }
    $sql .= " ORDER BY 
        CASE priority 
            WHEN 'high' THEN 1 
            WHEN 'medium' THEN 2 
            WHEN 'low' THEN 3 
        END,
        created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($filter != 'all') {
        $stmt->bind_param("is", $user_id, $filter);
    } else {
        $stmt->bind_param("i", $user_id);
    }
    $stmt->execute();
    $tasks = $stmt->get_result();
    
    // Statistik
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM tasks WHERE user_id = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("i", $user_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    if (!$stats) {
        $stats = ['total' => 0, 'completed' => 0, 'pending' => 0];
    }
}

// ==================== TAMPILAN HALAMAN ====================
$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskFlow - To-Do List</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Auth Container */
        .auth-container {
            max-width: 450px;
            margin: 0 auto;
        }

        .auth-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 40px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo p {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #cbd5e1;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 12px;
            font-size: 0.95rem;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.1);
        }

        .form-group input::placeholder {
            color: #64748b;
        }

        .btn-auth {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            color: #94a3b8;
            font-size: 0.875rem;
        }

        .auth-footer a {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .password-hint {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 5px;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .user-info {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .user-info span {
            color: #a5b4fc;
            font-weight: 500;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 6px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.4);
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #a5b4fc 0%, #c084fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .header p {
            color: #94a3b8;
            font-size: 1rem;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #a5b4fc;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Form Container */
        .form-container {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }

        .form-container:hover {
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .input-group {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .input-group input {
            flex: 1;
            padding: 14px 18px;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 16px;
            font-size: 1rem;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.1);
        }

        .input-group input::placeholder {
            color: #64748b;
        }

        .priority-select {
            padding: 14px 18px;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 16px;
            font-size: 0.9rem;
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .priority-select:focus {
            outline: none;
            border-color: #818cf8;
        }

        .btn-add {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
        }

        /* Filter Buttons */
        .filter-container {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 20px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid #334155;
            border-radius: 12px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-color: #818cf8;
            color: white;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            border-color: #818cf8;
        }

        /* Tasks List */
        .tasks-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .task-item {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .task-item:hover {
            transform: translateX(8px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .task-content {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .priority-badge {
            width: 8px;
            height: 40px;
            border-radius: 4px;
        }

        .priority-high .priority-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .priority-medium .priority-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .priority-low .priority-badge {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .task-text {
            flex: 1;
        }

        .task-title {
            font-size: 1rem;
            font-weight: 500;
            color: #f1f5f9;
            margin-bottom: 6px;
        }

        .task-title.completed {
            text-decoration: line-through;
            color: #64748b;
        }

        .task-meta {
            display: flex;
            gap: 12px;
            font-size: 0.7rem;
            color: #64748b;
        }

        .priority-text {
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .priority-high .priority-text {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .priority-medium .priority-text {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }

        .priority-low .priority-text {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .btn-toggle, .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Inter', sans-serif;
        }

        .btn-toggle {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
        }

        .btn-toggle:hover {
            background: rgba(99, 102, 241, 0.4);
            transform: scale(1.05);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.4);
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: rgba(30, 41, 59, 0.4);
            border-radius: 24px;
            backdrop-filter: blur(10px);
        }

        .empty-state i {
            font-size: 4rem;
            color: #475569;
            margin-bottom: 20px;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: 1rem;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            color: #64748b;
            font-size: 0.8rem;
        }

        @media (max-width: 640px) {
            body {
                padding: 20px 15px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .task-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .task-actions {
                align-self: flex-end;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .user-menu {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isLoggedIn()): ?>
            <!-- HALAMAN LOGIN/REGISTER -->
            <div class="auth-container">
                <div class="auth-card">
                    <div class="logo">
                        <h1><i class="fas fa-tasks"></i> TaskFlow</h1>
                        <p>Kelola tugas Anda dengan lebih produktif</p>
                    </div>

                    <?php if ($action == 'register'): ?>
                        <!-- FORM REGISTER -->
                        <?php if ($register_error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($register_error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($register_success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($register_success); ?>
                                <br><br>
                                <a href="?" style="color: #6ee7b7;">Klik disini untuk login</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Username</label>
                                <input type="text" name="username" placeholder="Masukkan username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" name="email" placeholder="Masukkan email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Password</label>
                                <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                                <div class="password-hint">Password minimal 6 karakter</div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                                <input type="password" name="confirm_password" placeholder="Ulangi password" required>
                            </div>
                            <button type="submit" name="register" class="btn-auth">
                                <i class="fas fa-user-plus"></i> Daftar
                            </button>
                        </form>
                        <div class="auth-footer">
                            Sudah punya akun? <a href="?">Masuk disini</a>
                        </div>

                    <?php else: ?>
                        <!-- FORM LOGIN -->
                        <?php if ($login_error): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($login_error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Username atau Email</label>
                                <input type="text" name="username" placeholder="Masukkan username atau email" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Password</label>
                                <input type="password" name="password" placeholder="Masukkan password" required>
                            </div>
                            <button type="submit" name="login" class="btn-auth">
                                <i class="fas fa-sign-in-alt"></i> Masuk
                            </button>
                        </form>
                        <div class="auth-footer">
                            Belum punya akun? <a href="?action=register">Daftar sekarang</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- HALAMAN TO-DO LIST (LOGIN) -->
            <div class="user-menu">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="?logout=1" class="logout-btn" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="header">
                <h1><i class="fas fa-tasks"></i> TaskFlow</h1>
                <p>Kelola tugas Anda dengan lebih produktif dan terorganisir</p>
            </div>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="stat-label">Total Tugas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Belum Selesai</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed'] ?? 0; ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>

            <div class="form-container">
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" name="task" placeholder="Tulis tugas baru..." required autocomplete="off">
                        <select name="priority" class="priority-select">
                            <option value="low">🟢 Low Priority</option>
                            <option value="medium" selected>🟡 Medium Priority</option>
                            <option value="high">🔴 High Priority</option>
                        </select>
                        <button type="submit" name="add_task" class="btn-add">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                    </div>
                </form>
            </div>

            <div class="filter-container">
                <a href="?" class="filter-btn <?php echo ($filter ?? 'all') == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Semua
                </a>
                <a href="?filter=high" class="filter-btn <?php echo ($filter ?? '') == 'high' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i> High
                </a>
                <a href="?filter=medium" class="filter-btn <?php echo ($filter ?? '') == 'medium' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Medium
                </a>
                <a href="?filter=low" class="filter-btn <?php echo ($filter ?? '') == 'low' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Low
                </a>
            </div>

            <div class="tasks-container">
                <?php if (isset($tasks) && $tasks && $tasks->num_rows > 0): ?>
                    <?php while($row = $tasks->fetch_assoc()): ?>
                        <div class="task-item priority-<?php echo $row['priority']; ?>">
                            <div class="task-content">
                                <div class="priority-badge"></div>
                                <div class="task-text">
                                    <div class="task-title <?php echo $row['status'] == 'completed' ? 'completed' : ''; ?>">
                                        <?php echo htmlspecialchars($row['task']); ?>
                                    </div>
                                    <div class="task-meta">
                                        <span class="priority-text">
                                            <?php 
                                                $priority_icons = [
                                                    'high' => '🔴',
                                                    'medium' => '🟡',
                                                    'low' => '🟢'
                                                ];
                                                $priority_labels = [
                                                    'high' => 'High Priority',
                                                    'medium' => 'Medium Priority',
                                                    'low' => 'Low Priority'
                                                ];
                                                echo $priority_icons[$row['priority']] . ' ' . $priority_labels[$row['priority']];
                                            ?>
                                        </span>
                                        <span>
                                            <i class="far fa-calendar-alt"></i> 
                                            <?php echo date('d M Y H:i', strtotime($row['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <?php if($row['status'] == 'pending'): ?>
                                        <span style="color: #fcd34d;">⚡ Belum</span>
                                    <?php else: ?>
                                        <span style="color: #6ee7b7;">✓ Selesai</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="task-actions">
                                <a href="?toggle=<?php echo $row['id']; ?>" class="btn-toggle">
                                    <i class="fas <?php echo $row['status'] == 'pending' ? 'fa-check-circle' : 'fa-undo-alt'; ?>"></i>
                                    <?php echo $row['status'] == 'pending' ? 'Selesai' : 'Batal'; ?>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus tugas ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>Belum ada tugas. Yuk tambah tugas pertama Anda!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="footer">
                <p><i class="fas fa-rocket"></i> TaskFlow - Stay Productive & Organized | © 2025</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>