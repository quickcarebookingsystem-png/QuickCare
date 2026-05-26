<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once __DIR__ . '/db_connect.php'; 
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$PAGE_URLS = [
    'user' => [
        'dashboard' => 'user/user_dashboard.php', 'profile' => 'my_profile.php', 'services' => 'user/clinic_services.php',
        'doctors' => 'user/doctor_list.php', 'book' => 'user/book_appointment.php', 'appointments' => 'user/my_appointments.php',
        'payment' => 'user/payment.php', 'payment_history' => 'user/payment_history.php',
    ],
    'staff' => [
        'dashboard' => 'staff/staff_dashboard.php', 'profile' => 'my_profile.php', 'appointments' => 'staff/appointments.php',
        'schedule' => 'staff/daily_schedule.php', 'users' => 'user/user_list.php', 'payment' => 'staff/payment_management.php',
    ],
    'admin' => [
        'dashboard' => 'admin/admin_dashboard.php', 'profile' => 'my_profile.php', 'staff' => 'staff/manage_staff.php',
        'doctors' => 'admin/manage_doctors.php', 'services' => 'admin/manage_services.php', 'appointments' => 'admin/all_appointments.php',
        'payment' => 'admin/manage_payments.php', 'reports' => 'admin/reports.php',
    ],
];

$NAVS = [
    'user' => [
        ['section' => 'Account', 'items' => [['id' => 'dashboard', 'icon' => '📊', 'label' => 'Dashboard'], ['id' => 'profile', 'icon' => '👤', 'label' => 'My Profile']]],
        ['section' => 'Appointments', 'items' => [['id' => 'services', 'icon' => '🏥', 'label' => 'Clinic Services'], ['id' => 'doctors', 'icon' => '👨‍⚕️', 'label' => 'Doctor List'], ['id' => 'book', 'icon' => '📅', 'label' => 'Book Appointment'], ['id' => 'appointments', 'icon' => '📋', 'label' => 'My Appointments']]],
        ['section' => 'Payments', 'items' => [
            ['id' => 'payment', 'icon' => '💳', 'label' => 'Make Payment'],
            ['id' => 'payment_history', 'icon' => '📜', 'label' => 'Payment History']
        ]],
    ],
    'staff' => [
        ['section' => 'Account', 'items' => [['id' => 'dashboard', 'icon' => '📊', 'label' => 'Dashboard'], ['id' => 'profile', 'icon' => '👤', 'label' => 'My Profile']]],
        ['section' => 'Appointments', 'items' => [['id' => 'appointments', 'icon' => '📋', 'label' => 'Appointments'], ['id' => 'schedule', 'icon' => '🗓', 'label' => 'Daily Schedule']]],
        ['section' => 'Users', 'items' => [['id' => 'users', 'icon' => '👥', 'label' => 'User List']]],
        ['section' => 'Payments', 'items' => [['id' => 'payment', 'icon' => '💳', 'label' => 'Payment Management']]],
    ],
    'admin' => [
        ['section' => 'Account', 'items' => [['id' => 'dashboard', 'icon' => '📊', 'label' => 'Dashboard'], ['id' => 'profile', 'icon' => '👤', 'label' => 'My Profile']]],
        ['section' => 'Management', 'items' => [['id' => 'staff', 'icon' => '👥', 'label' => 'Manage Staff'], ['id' => 'doctors', 'icon' => '👨‍⚕️', 'label' => 'Manage Doctors'], ['id' => 'services', 'icon' => '🏥', 'label' => 'Manage Services']]],
        ['section' => 'Appointments', 'items' => [['id' => 'appointments', 'icon' => '📋', 'label' => 'All Appointments']]],
        ['section' => 'Payments & Reports', 'items' => [['id' => 'payment', 'icon' => '💳', 'label' => 'Manage Payments'], ['id' => 'reports', 'icon' => '📈', 'label' => 'Reports']]],
    ],
];

$PAGE_TITLES = [
    'dashboard' => 'Dashboard', 'profile' => 'My Profile', 'services' => 'Clinic Services',
    'doctors' => 'Doctor List', 'book' => 'Book Appointment', 'appointments' => 'Appointments',
    'payment' => 'Payment', 'payment_history' => 'Payment History', 'reports' => 'Reports', 
    'staff' => 'Manage Staff', 'schedule' => 'Daily Schedule', 'users' => 'User List',
];

if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_start();
}

//check either email exist or not （register - return true/false)
function email_exist($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

//insert user data
function create_user($conn, $name, $email, $password, $role) {
    $prefix = 'U';
    $stmt = $conn->prepare("
        SELECT MAX(CAST(SUBSTRING(user_code, 2) AS UNSIGNED)) AS max_id
        FROM users
        WHERE role = 'user'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $next_id = ((int)$row['max_id']) + 1;
    $user_code = $prefix . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    $stmt->close();

    $stmt = $conn->prepare(
        "INSERT INTO users (user_code, name, email, password, role) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $user_code, $name, $email, $password, $role);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

//check email exist or not (login - return user data or false)
function get_user_by_email($conn, $email) {
    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function app_base_url() {
    //calculate base URL dynamically
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $currentDir = str_replace('\\', '/', __DIR__);
    // calculate the relative path from document root to current directory
    $baseUrl = str_replace($docRoot, '', $currentDir);
    return '/' . ltrim($baseUrl, '/');
}

function app_url($path) {
    return rtrim(app_base_url(), '/') . '/' . ltrim($path, '/');
}

function absolute_app_url($path) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . app_url($path);
}

function page_url($page, $role = null) {
    global $PAGE_URLS;
    $role = $role ?: ($_SESSION['QuickCare_role'] ?? 'user');
    return app_url($PAGE_URLS[$role][$page] ?? 'user/user_dashboard.php');
}

function action_url($action, $params = []) {
    $params = array_merge(['action' => $action], $params);
    return app_url('action.php') . '?' . http_build_query($params);
}

function redirect_to($url) {
    header('Location: ' . $url);
    exit;
}

function protect_page() {
    global $conn;

    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['id'])) {
        redirect_to(app_url('login.php'));
    }

    if (isset($conn) && !current_user($conn)) {
        session_unset();
        session_destroy();
        redirect_to(app_url('login.php'));
    }
}

function guest_only() {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (isset($_SESSION['id'])) {

        redirect_to(
            page_url('dashboard', $_SESSION['QuickCare_role'])
        );

        exit();
    }

    echo'<script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>';
}

function current_user($conn) {
    if (!isset($_SESSION['id'])) {
        return null;
    }
    $id = $_SESSION['id'];
    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE user_id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function update_user_status($conn, $userId, $status) {
    if (!in_array($status, ['active', 'inactive'], true)) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE users SET user_status = ? WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("si", $status, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function reset_token($conn, $email){
    $token = bin2hex(random_bytes(32));
    $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));
    $stmt = $conn->prepare("
        UPDATE users 
        SET reset_token=?, reset_expiry=? 
        WHERE email=?
    ");
    $stmt->bind_param("sss", $token, $expiry, $email);
    $stmt->execute();
    return $token;
}

function verify_reset_token($conn, $token) {
    if (empty($token)) return null;
    $stmt = $conn->prepare("
        SELECT * FROM users 
        WHERE reset_token=? 
        AND reset_expiry > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function send_email($to, $subject, $body) {
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); //use smtp (email protocol)
        $mail->Host = 'smtp.gmail.com'; //connect to email server
        $mail->SMTPAuth = true;
        $mail->Username = 'nshuzheng@gmail.com'; //login gmail account
        $mail->Password = 'sepe adqn xfez jcdv'; //use google app password
        $mail->SMTPSecure = 'tls'; // password encryption
        $mail->Port = 587; //email server port (TLS587, SSL465)
        $mail->setFrom('nshuzheng@gmail.com', 'QuickCare'); //from QuickCare
        $mail->addAddress($to); //to user email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function update_password($conn, $new_password, $email) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $null = null;
    $stmt = $conn->prepare("
        UPDATE users 
        SET password=?, reset_token=?, reset_expiry=? 
        WHERE email=?
    ");
    $stmt->bind_param("ssss", $hashed_password, $null, $null, $email);
    return $stmt->execute();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function name_avatar($name) {
    $name = trim((string) $name);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }

    return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}

function format_phone_number($phone) {
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return '';
    }

    if (str_starts_with($digits, '60')) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    if (strlen($digits) <= 2) {
        return '+60 ' . $digits;
    }

    if (strlen($digits) <= 5) {
        return '+60 ' . substr($digits, 0, 2) . '-' . substr($digits, 2);
    }

    return '+60 ' . substr($digits, 0, 2) . '-' . substr($digits, 2, 3) . ' ' . substr($digits, 5);
}

function fetch_all_assoc($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function count_appointments($conn, $role = null, $conditions = [], $types = '', $params = []) {
    $where = [];

    if ($role === 'user') {
        $user = current_user($conn);
        if ($user) {
            $where[] = "name = ?";
            $types = 's' . $types;
            array_unshift($params, $user['name']);
        }
    }

    foreach ($conditions as $condition) {
        $where[] = $condition;
    }

    $sql = "SELECT COUNT(*) AS total FROM appointments";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $rows = fetch_all_assoc($conn, $sql, $types, $params);
    return (int) ($rows[0]['total'] ?? 0);
}

function get_services($conn) {
    return fetch_all_assoc(
        $conn,
        "SELECT service_id, service_icon, service_name, service_price, service_description
         FROM services
         ORDER BY service_id ASC"
    );
}

function get_doctors($conn) {
    return fetch_all_assoc(
        $conn,
        "SELECT d.doctor_id, d.doctor_icon, d.doctor_name, d.doctor_specialist,
                GROUP_CONCAT(DISTINCT ds.available_day ORDER BY FIELD(ds.available_day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') SEPARATOR ', ') AS available_days
         FROM doctors d
         LEFT JOIN doctor_schedule ds ON ds.doctor_id = d.doctor_id
         GROUP BY d.doctor_id, d.doctor_icon, d.doctor_name, d.doctor_specialist
         ORDER BY d.doctor_id ASC"
    );
}

function get_appointments($conn, $role = null, $appointmentDate = null, $limit = null, $orderByCreatedAt = false) {
    $sql = "SELECT appointment_id, appointment_code, name, doctor_name, service_name,
                   appointment_date, appointment_time, appointment_status, payment_status, amount, notes, created_at,
                   (
                       SELECT p.receipt_image
                       FROM payments p
                       WHERE p.appointment_code = appointments.appointment_code
                       ORDER BY p.payment_date DESC, p.payment_id DESC
                       LIMIT 1
                   ) AS payment_proof,
                   (
                       SELECT p.payment_id
                       FROM payments p
                       WHERE p.appointment_code = appointments.appointment_code
                         AND p.payment_status IN ('paid', 'approved')
                       ORDER BY p.approved_date DESC, p.payment_date DESC, p.payment_id DESC
                       LIMIT 1
                   ) AS receipt_payment_id
            FROM appointments";
    $types = '';
    $params = [];
    $where = [];

    if ($role === 'user') {
        $user = current_user($conn);
        if ($user) {
            $where[] = "name = ?";
            $types .= 's';
            $params[] = $user['name'];
        }
    }

    if (!empty($appointmentDate)) {
        $where[] = "appointment_date = ?";
        $types .= 's';
        $params[] = $appointmentDate;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    if ($orderByCreatedAt) {
        $sql .= " ORDER BY created_at DESC, appointment_id DESC";
    } else {
        $sql .= " ORDER BY appointment_date DESC, appointment_time DESC, appointment_id DESC";
    }
    if ($limit !== null) {
        $sql .= " LIMIT ?";
        $types .= 'i';
        $params[] = (int) $limit;
    }
    return fetch_all_assoc($conn, $sql, $types, $params);
}

function format_date_display($date) {
    if (empty($date)) {
        return '';
    }
    return date('d M Y', strtotime($date));
}

function format_time_display($time) {
    if (empty($time)) {
        return '';
    }
    return date('H:i', strtotime($time));
}

function appointment_time_has_passed($date, $time) {
    if (empty($date) || empty($time)) {
        return false;
    }

    $appointmentTimestamp = strtotime($date . ' ' . $time);
    return $appointmentTimestamp !== false && $appointmentTimestamp <= time();
}

function app_header($title) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . '</title>';
    echo '<base href="' . e(rtrim(app_base_url(), '/') . '/') . '">';
    echo '<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="style.css"></head>';
    if (isset($_SESSION['message'])) {
        echo "<script>alert('" . $_SESSION['message'] . "');</script>";
        unset($_SESSION['message']);
    }
}

function app_start($role, $page, $title = null) {
    global $PAGE_TITLES, $conn;
    $_SESSION['QuickCare_role'] = $role;
    $title = $title ?: ($PAGE_TITLES[$page] ?? 'Dashboard');
    echo '<body><div id="app" class="view active">';
    render_sidebar($conn, $role, $page);
    echo '<div class="main-content"><div class="topbar"><span class="topbar-title">' . e($title) . '</span><div class="topbar-actions">';
    echo '<span class="text-muted text-sm">' . date('l, F j, Y') . '</span></div></div><div class="page-content">';
    if (!empty($_SESSION['QuickCare_message'])) {
        $messageType = $_SESSION['QuickCare_message_type'] ?? 'success';
        echo '<div class="toast show ' . e($messageType) . '" style="position:static;margin-bottom:16px">' . e($_SESSION['QuickCare_message']) . '</div>';
        unset($_SESSION['QuickCare_message']);
        unset($_SESSION['QuickCare_message_type']);
    }
}

function app_end() {
    render_modals();

    echo '
    </div></div></div>
    <div class="toast" id="toast"></div>

    <script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>

    <script src="ui.js"></script>
    </body></html>';
}

function render_dashboard($role) {
    global $conn;

    $selectedDate = $_GET['view_date'] ?? null;
    $calendarDate = $selectedDate ?: date('Y-m-d');
    $viewMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m', strtotime($calendarDate));
    $viewYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y', strtotime($calendarDate));

    $appointments = get_appointments($conn, $role, $selectedDate, $selectedDate ? null : 10);

    $firstDayOfMonth = strtotime("$viewYear-$viewMonth-01");
    $daysInMonth = date('t', $firstDayOfMonth);
    $startOfWeek = date('w', $firstDayOfMonth);
    $monthName = date('F Y', $firstDayOfMonth);

    $monthStart = date('Y-m-01', $firstDayOfMonth);
    $monthEnd = date('Y-m-t', $firstDayOfMonth);

    $bookedSql = "SELECT DISTINCT appointment_date FROM appointments WHERE appointment_date BETWEEN ? AND ?";
    $bookedTypes = 'ss';
    $bookedParams = [$monthStart, $monthEnd];
    if ($role === 'user') {
        $currentUser = current_user($conn);
        if ($currentUser) {
            $bookedSql .= " AND name = ?";
            $bookedTypes .= 's';
            $bookedParams[] = $currentUser['name'];
        }
    }
    $bookedDateRows = fetch_all_assoc($conn, $bookedSql, $bookedTypes, $bookedParams);
    $bookedDates = [];
    foreach ($bookedDateRows as $row) {
        $bookedDates[] = $row['appointment_date'];
    }

    render_stats($role);

    $displayTitle = $selectedDate ? 'Appointments: ' . format_date_display($selectedDate) : 'Recent Appointments';
    echo '<div class="dashboard-grid"><div><div class="card mb-20"><div class="card-header"><span class="card-title">' . e($displayTitle) . '</span><a class="btn btn-sm btn-outline" href="' . e(page_url('appointments', $role)) . '">View All</a></div><div class="card-body"><div class="table-wrap"><table><thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>';
    if (empty($appointments)) {
        echo '<tr><td colspan="5" style="text-align:center">No appointments found.</td></tr>';
    } else {
        foreach ($appointments as $a) {
            $paymentProof = trim($a['payment_proof'] ?? '');
            $paymentProofLink = $paymentProof !== '' ? app_url('uploads/receipts/' . rawurlencode($paymentProof)) : page_url('payment_history', $role);
            $paymentProofExt = strtolower(pathinfo($paymentProof, PATHINFO_EXTENSION));
            $receiptPaymentId = (int)($a['receipt_payment_id'] ?? 0);
            $payUrl = page_url('payment', $role) . '?appointment=' . urlencode($a['appointment_code']);
            $actionData = $role === 'user'
                ? '" data-pay-url="' . e($payUrl) . '" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-receipt-id="' . e($receiptPaymentId)
                : '';
            echo '<tr><td>' . e($a['name']) . '</td><td>' . e($a['doctor_name']) . '</td><td>' . e(format_date_display($a['appointment_date'])) . '</td><td>' . appointment_badge($a['appointment_status'], $role) . '</td><td><button type="button" class="btn btn-sm btn-outline dashboard-view-btn" onclick="showAppointmentDetails(this)" data-code="' . e($a['appointment_code']) . '" data-patient="' . e($a['name']) . '" data-doctor="' . e($a['doctor_name']) . '" data-service="' . e($a['service_name']) . '" data-date="' . e(format_date_display($a['appointment_date'])) . '" data-time="' . e(format_time_display($a['appointment_time'])) . '" data-notes="' . e($a['notes'] ?? '') . '" data-status="' . e($a['appointment_status']) . '" data-payment="' . e($a['payment_status']) . '" data-amount="RM ' . e(number_format((float) $a['amount'], 2)) . $actionData . '">View</button></td></tr>';
        }
    }
    echo '</tbody></table></div></div></div></div><div><div class="card mb-20">';

    $prevMonth = $viewMonth - 1; $prevYear = $viewYear;
    if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
    $nextMonth = $viewMonth + 1; $nextYear = $viewYear;
    if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

    $baseUrl = page_url('dashboard', $role);
    $sep = str_contains($baseUrl, '?') ? '&' : '?';

    echo '<div class="card-header" style="padding-bottom:16px"><div class="cal-header" style="width:100%;margin-bottom:0">';
    echo '<a class="cal-nav" href="' . e($baseUrl . $sep . 'month=' . $prevMonth . '&year=' . $prevYear) . '" style="text-decoration:none;color:inherit">◀</a>';
    echo '<span class="card-title">' . e($monthName) . '</span>';
    echo '<a class="cal-nav" href="' . e($baseUrl . $sep . 'month=' . $nextMonth . '&year=' . $nextYear) . '" style="text-decoration:none;color:inherit">▶</a>';
    echo '</div></div>';

    echo '<div class="mini-calendar"><div class="cal-grid">';
    foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d) echo '<div class="cal-day-label">' . $d . '</div>';
    for ($i = 0; $i < $startOfWeek; $i++) echo '<div class="cal-day other-month"></div>';
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $currDate = sprintf('%04d-%02d-%02d', $viewYear, $viewMonth, $d);
        $classes = 'cal-day';
        if ($selectedDate && $currDate === $selectedDate) $classes .= ' selected';
        if ($currDate === date('Y-m-d')) $classes .= ' today';
        if (in_array($currDate, $bookedDates)) $classes .= ' has-appt';
        echo '<a href="' . e($baseUrl . $sep . 'view_date=' . $currDate . '&month=' . $viewMonth . '&year=' . $viewYear) . '" class="' . $classes . '" style="text-decoration:none">' . $d . '</a>';
    }
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Quick Actions</span></div><div class="card-body"><div class="quick-actions">';
    $actions = [
        'user' => [['Book New Appointment','book'], ['View My Appointments','appointments'], ['Make Payment','payment']],
        'staff' => [['View Daily Schedule','schedule'], ['Manage Appointments','appointments']],
        'admin' => [['Add Staff Member','staff'], ['Manage Doctors','doctors'], ['View Reports','reports']],
    ];
    foreach ($actions[$role] ?? [] as $a) echo '<a class="btn btn-outline w-full" href="' . e(page_url($a[1], $role)) . '">' . e($a[0]) . '</a>';
    echo '</div></div></div></div></div>';
    echo '<div class="modal-overlay" id="modal-appointment-details"><div class="modal appointment-details-modal"><div class="modal-header"><span class="modal-title">Appointment Details</span><button class="modal-close appointment-modal-close" onclick="closeModal(\'modal-appointment-details\')">×</button></div><div class="modal-body"><div class="appointment-detail-code"><span>Appointment ID</span><strong id="detailAppointmentCode"></strong></div><div class="appointment-detail-list"><div><span>Patient</span><strong id="detailPatient"></strong></div><div><span>Doctor</span><strong id="detailDoctor"></strong></div><div><span>Service</span><strong id="detailService"></strong></div><div><span>Date</span><strong id="detailDate"></strong></div><div><span>Time</span><strong id="detailTime"></strong></div><div><span>Status</span><strong id="detailStatus"></strong></div><div><span>Payment</span><strong id="detailPayment"></strong></div><div><span>Amount</span><strong class="detail-amount" id="detailAmount"></strong></div><div class="appointment-detail-notes"><span>Notes</span><strong id="detailNotes"></strong></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline" id="detailPaymentAction" style="display:none;width:auto"></button><button type="button" class="btn btn-primary appointment-detail-close" onclick="closeModal(\'modal-appointment-details\')">Close</button></div></div></div>';
    if ($role === 'user') {
        echo '<div class="modal-overlay" id="modal-dashboard-payment-proof"><div class="modal payment-proof-modal"><div class="modal-header"><span class="modal-title">Payment Proof</span><button class="modal-close" onclick="closeModal(\'modal-dashboard-payment-proof\')">×</button></div><div class="modal-body"><div class="payment-proof-card" id="dashboardPaymentProofContent"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" style="width:auto" onclick="closeModal(\'modal-dashboard-payment-proof\')">Close</button></div></div></div>';
        echo '<div class="modal-overlay" id="modal-dashboard-receipt"><div class="modal receipt-modal"><div class="modal-header"><span class="modal-title">Payment Receipt</span><button class="modal-close" onclick="closeModal(\'modal-dashboard-receipt\')">×</button></div><div class="modal-body" id="dashboardReceiptContent"></div><div class="modal-footer"><button class="btn btn-primary" style="width:auto" onclick="window.print()">Print</button><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-dashboard-receipt\')">Close</button></div></div></div>';
    }
    echo '<script>
    function formatStatusLabel(status) {
        const labels = { unpaid: "Unpaid", paid: "Paid" };
        return labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : "");
    }
    function setDetailBadge(id, status) {
        const target = document.getElementById(id);
        if (!target) return;
        target.innerHTML = "<span class=\"badge badge-" + status + "\">" + formatStatusLabel(status) + "</span>";
    }
    function trimDetailNotes(notes) {
        notes = (notes || "").trim();
        if (!notes) return "-";
        return notes.length > 120 ? notes.slice(0, 120) + "..." : notes;
    }
    function setDetailPaymentAction(button) {
        const action = document.getElementById("detailPaymentAction");
        if (!action) return;

        const status = button.dataset.status || "";
        const payment = button.dataset.payment || "";
        if (status === "cancelled") {
            action.style.display = "none";
            action.onclick = null;
            return;
        }

        action.style.display = "inline-flex";
        action.className = "btn btn-outline";
        action.style.width = "auto";

        if (payment === "pending") {
            action.textContent = "Pay";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (payment === "rejected") {
            action.textContent = "Retry Payment";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (payment === "verifying") {
            action.textContent = "View Payment Proof";
            action.onclick = function () {
                openDashboardPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "");
            };
        } else if (payment === "paid") {
            action.textContent = "View Receipt";
            action.onclick = function () {
                printDashboardReceipt(Number(button.dataset.receiptId || 0));
            };
        } else {
            action.style.display = "none";
            action.onclick = null;
        }
    }
    function showAppointmentDetails(button) {
        document.getElementById("detailAppointmentCode").textContent = button.dataset.code || "";
        document.getElementById("detailPatient").textContent = button.dataset.patient || "";
        document.getElementById("detailDoctor").textContent = button.dataset.doctor || "";
        document.getElementById("detailService").textContent = button.dataset.service || "";
        document.getElementById("detailDate").textContent = button.dataset.date || "";
        document.getElementById("detailTime").textContent = button.dataset.time || "";
        document.getElementById("detailNotes").textContent = trimDetailNotes(button.dataset.notes);
        document.getElementById("detailAmount").textContent = button.dataset.amount || "";
        setDetailBadge("detailStatus", button.dataset.status || "");
        setDetailBadge("detailPayment", button.dataset.payment || "");
        setDetailPaymentAction(button);
        openModal("modal-appointment-details");
    }
    function openDashboardPaymentProof(proofUrl, proofExt) {
        const content = document.getElementById("dashboardPaymentProofContent");
        if (!content) return;
        proofExt = (proofExt || "").toLowerCase();
        if (!proofUrl) {
            content.innerHTML = "<p class=\"text-muted text-center\">No payment proof uploaded.</p>";
        } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(proofExt)) {
            content.innerHTML = "<img src=\"" + proofUrl + "\" alt=\"Payment proof\">";
        } else {
            content.innerHTML = "<p class=\"text-muted text-center mb-16\">This payment proof file cannot be previewed here.</p><a class=\"btn btn-outline\" style=\"width:auto\" target=\"_blank\" rel=\"noopener\" href=\"" + proofUrl + "\">Open File</a>";
        }
        openModal("modal-dashboard-payment-proof");
    }
    async function printDashboardReceipt(paymentId) {
        if (!paymentId) {
            alert("Receipt not found.");
            return;
        }
        const response = await fetch("' . e(app_url('action.php')) . '", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=get_receipt&payment_id=" + encodeURIComponent(paymentId)
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById("dashboardReceiptContent").innerHTML = data.html;
            openModal("modal-dashboard-receipt");
        } else {
            alert("Error loading receipt");
        }
    }
    </script>';
}

function render_sidebar($conn, $role, $page) {
    global $NAVS;
    $user = current_user($conn);
    if (!$user) {
        redirect_to(app_url('login.php'));
    }
    echo '<aside class="sidebar" id="sidebar"><div class="sidebar-header"><div class="sidebar-logo"><div class="logo-icon">🏥</div><span>QuickCare</span></div>';
    echo '<div class="sidebar-role">' . e($user['role']) . ' Portal</div></div><nav class="sidebar-nav">';
    foreach ($NAVS[$role] ?? [] as $section) {
        echo '<div class="nav-section"><div class="nav-section-label">' . e($section['section']) . '</div>';
        foreach ($section['items'] as $item) {
            $active = $item['id'] === $page ? ' active' : '';
            echo '<a class="nav-item' . $active . '" href="' . e(page_url($item['id'], $role)) . '"><span class="nav-icon">' . $item['icon'] . '</span> ' . e($item['label']) . '</a>';
        }
        echo '</div>';
    }
    echo '</nav><div class="sidebar-footer"><div class="user-info"><div class="user-avatar">' . e(name_avatar($user['name'] ?? '')) . '</div><div>';
    echo '<div class="user-name">' . e($user['name']) . '</div><div class="user-email">' . e($user['email']) . '</div></div></div>';
    echo '<a class="btn-signout" href="' . e(action_url('logout')) . '" draggable = "false">🚪 Log Out</a></div></aside>';
}

function render_stats($role) {
    global $conn;

    $upcomingAppointments = count_appointments($conn, 'user', ["(appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME()))", "appointment_status <> ?"], 's', ['cancelled']);
    $completedAppointments = count_appointments($conn, 'user', ["appointment_status = ?"], 's', ['completed']);
    $pendingPayments = count_appointments($conn, 'user', ["payment_status = ?"], 's', ['pending']);

    $todayAppointments = count_appointments($conn, null, ["appointment_date = CURDATE()", "appointment_status <> ?"], 's', ['cancelled']);
    $pendingReview = count_appointments($conn, null, ["appointment_status = ?"], 's', ['pending']);
    $confirmed = count_appointments($conn, null, ["appointment_status = ?"], 's', ['confirmed']);
    $totalUsers = (int)(fetch_all_assoc($conn, "SELECT COUNT(*) AS total FROM users WHERE role = ?", 's', ['user'])[0]['total'] ?? 0);

    $totalAppointments = count_appointments($conn);
    $pendingApprovalRows = fetch_all_assoc($conn, "SELECT COUNT(*) AS total FROM payments WHERE payment_status IN ('verifying', 'pending')");
    $pendingApprovals = (int) ($pendingApprovalRows[0]['total'] ?? 0);
    $revenueRows = fetch_all_assoc($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_status = ?", 's', ['paid']);
    $totalRevenue = (float) ($revenueRows[0]['total'] ?? 0);
    $totalActiveUsers = (int)(fetch_all_assoc($conn, "SELECT COUNT(*) AS total FROM users WHERE role = ? AND user_status = ?", 'ss', ['user', 'active'])[0]['total'] ?? 0);

    $stats = [
        'user' => [['📅','primary',$upcomingAppointments,'Upcoming Appointments',''], ['✅','success',$completedAppointments,'Completed',''], ['⏳','warning',$pendingPayments,'Pending Payment','']],
        'staff' => [['📅','primary',$todayAppointments,"Today's Appointments",''], ['⏳','warning',$pendingReview,'Pending Review',''], ['✅','success',$confirmed,'Confirmed',''], ['👥','teal',$totalUsers,'Total Users','']],
        'admin' => [['📅','primary',$totalAppointments,'Total Appointments',''], ['💰','success','RM ' . number_format($totalRevenue, 2),'Revenue',''], ['👥','teal',$totalActiveUsers,'Active Users',''], ['⏳','warning',$pendingApprovals,'Pending Approvals','']],
    ];
    echo '<div class="stats-grid">';
    foreach ($stats[$role] ?? [] as $s) {
        echo '<div class="stat-card"><div class="stat-icon ' . e($s[1]) . '">' . $s[0] . '</div><div><div class="stat-value">' . e($s[2]) . '</div><div class="stat-label">' . e($s[3]) . '</div>';
        if ($s[4]) echo '<div class="stat-change up">' . e($s[4]) . '</div>';
        echo '</div></div>';
    }
    echo '</div>';
}

function badge($status) {
    $labels = [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'verifying' => 'Verifying',
    ];
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="badge badge-' . e($status) . '">' . e($label) . '</span>';
}

function appointment_badge($status, $role = null) {
    if (in_array($role, ['staff', 'admin'], true) && in_array($status, ['confirm', 'confirmed'], true)) {
        return badge('pending');
    }

    return badge($status);
}

function render_profile($role) {
    global $conn;
    $u = current_user($conn);
    if (!$u) {
        redirect_to(app_url('login.php'));
    }
    $accountStatus = strtolower((string)($u['user_status'] ?? 'inactive')) === 'active' ? 'active' : 'inactive';
    echo '<div class="profile-header"><div class="profile-avatar-lg">' . e(name_avatar($u['name'] ?? '')) . '</div><div><div class="profile-name">' . e($u['name']) . '</div><div class="profile-meta">' . e($u['role'] . ' · ID: ' . $u['user_code']) . '</div><div style="margin-top:8px"><span class="badge badge-' . e($accountStatus) . '">• ' . e(ucfirst($accountStatus)) . '</span></div></div><button class="btn btn-outline" style="margin-left:auto" onclick="openModal(\'modal-edit-profile\')">✏️ Edit Profile</button></div>';
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Personal Information</span></div><div class="card-body"><div style="display:flex;flex-direction:column;gap:12px">';
    foreach ([['Full Name',$u['name']], ['Email',$u['email']], ['Phone',format_phone_number($u['phone_number'])], ['Gender',$u['gender']], ['Date of Birth',$u['date_of_birth']], ['Blood Type',$u['blood_type']]] as $row) echo '<div class="flex-between"><span class="text-muted">' . e($row[0]) . '</span><span>' . e($row[1]) . '</span></div><div class="divider"></div>';
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Change Password</span></div><div class="card-body"><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="change_password"><div class="form-group"><label>Current Password</label><input class="form-control" type="password" name="current_password" required></div><div class="form-group"><label>New Password</label><input class="form-control" type="password" name="new_password" required></div><div class="form-group"><label>Confirm Password</label><input class="form-control" type="password" name="confirm_password" required></div><button class="btn btn-primary" style="width:auto">Update Password</button></form></div></div></div>';
}

function render_services($role) {
    global $conn;
    $services = get_services($conn);
    echo '<div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input class="form-control" type="text" placeholder="Search services…">
          </div>';
    if ($role === 'admin') {
        echo '<button class="btn btn-primary" style="width:auto" onclick="openModal(\'modal-add-service\')">
                + Add Service
              </button>';
    }
    echo '</div><div class="services-grid">';
    foreach ($services as $s) {
        echo '<div class="service-card">
                <span class="service-icon">' . e($s['service_icon']) . '</span>
                <div class="service-name">' . e($s['service_name']) . '</div>
                <div class="service-price">RM ' . e(number_format((float) $s['service_price'], 2)) . '</div>
                <div class="service-desc">' . e($s['service_description']) . '</div>
              </div>';
    }
    echo '</div>';
}

function render_doctors($role) {
    global $conn;
    $doctors = get_doctors($conn);
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search doctors…"></div>';
    if ($role === 'admin') echo '<button class="btn btn-primary" style="width:auto" onclick="openModal(\'modal-add-doctor\')">+ Add Doctor</button>';
    echo '</div><div class="doctor-grid">';
    foreach ($doctors as $d) {
        $available = $d['available_days'] ?: 'Not scheduled';
        echo '<div class="doctor-card"><div class="doctor-avatar">' . e($d['doctor_icon']) . '</div><div class="doctor-name">' . e($d['doctor_name']) . '</div><div class="doctor-spec">' . e($d['doctor_specialist']) . '</div><div class="doctor-avail">✅ Available ' . e($available) . '</div>';
        if ($role === 'admin') echo '<div style="margin-top:12px;display:flex;gap:6px;justify-content:center"><button class="btn btn-sm btn-outline" onclick="openModal(\'modal-add-doctor\')">✏️</button><a class="btn btn-sm btn-danger" href="' . e(action_url('delete', ['type' => 'doctor', 'id' => $d['doctor_id']])) . '">🗑</a></div>';
        echo '</div>';
    }
    echo '</div>';
}

function appointment_actions($role, $a) {
    $appointmentId = $a['appointment_code'] ?? $a['id'] ?? '';
    $appointmentStatus = $a['appointment_status'] ?? '';
    $paymentStatus = $a['payment_status'] ?? '';
    if ($role === 'user') {
        $paymentProof = trim($a['payment_proof'] ?? '');
        $paymentProofLink = $paymentProof !== '' ? app_url('uploads/receipts/' . rawurlencode($paymentProof)) : page_url('payment_history', $role);
        $paymentProofExt = strtolower(pathinfo($paymentProof, PATHINFO_EXTENSION));
        $receiptPaymentId = (int)($a['receipt_payment_id'] ?? 0);
        $payUrl = page_url('payment', $role) . '?appointment=' . urlencode($appointmentId);
        $detailsAttrs = ' data-code="' . e($appointmentId) . '" data-patient="' . e($a['name'] ?? '') . '" data-doctor="' . e($a['doctor_name'] ?? '') . '" data-service="' . e($a['service_name'] ?? '') . '" data-date="' . e(format_date_display($a['appointment_date'] ?? '')) . '" data-time="' . e(format_time_display($a['appointment_time'] ?? '')) . '" data-notes="' . e($a['notes'] ?? '') . '" data-status="' . e($appointmentStatus) . '" data-payment="' . e($paymentStatus) . '" data-amount="RM ' . e(number_format((float)($a['amount'] ?? 0), 2)) . '" data-pay-url="' . e($payUrl) . '" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-receipt-id="' . e($receiptPaymentId) . '"';
        $menuItems = [];

        if (in_array($appointmentStatus, ['completed', 'cancelled'], true)) {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'pending') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<a class="appt-menu-item" href="' . e($payUrl) . '">Pay</a>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<a class="appt-menu-item danger" href="' . e(action_url('cancel_appointment', ['id' => $appointmentId])) . '">Cancel</a>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'rejected') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<a class="appt-menu-item" href="' . e($payUrl) . '">Retry Payment</a>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<a class="appt-menu-item danger" href="' . e(action_url('cancel_appointment', ['id' => $appointmentId])) . '">Cancel</a>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'verifying') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '">View Payment Proof</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<a class="appt-menu-item danger" href="' . e(action_url('cancel_appointment', ['id' => $appointmentId])) . '">Cancel</a>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'paid') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="printAppointmentReceipt(' . e($receiptPaymentId) . ')">View Receipt</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<a class="appt-menu-item danger" href="' . e(action_url('cancel_appointment', ['id' => $appointmentId])) . '">Cancel</a>';
        } else {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
        }

        return '<div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleAppointmentMenu(event, this)" aria-label="Appointment actions">...</button><div class="appt-menu-list">' . implode('', $menuItems) . '</div></div>';
    }
    if (in_array($role, ['admin', 'staff'], true) && !in_array($appointmentStatus, ['completed', 'cancelled', 'rejected'], true)) {
        $canComplete = appointment_time_has_passed($a['appointment_date'] ?? '', $a['appointment_time'] ?? '');
        $completeAction = $canComplete
            ? '<a class="btn btn-sm btn-teal" href="' . e(action_url('update_status', ['id' => $appointmentId])) . '">Complete</a>'
            : '<span class="text-muted text-sm">-</span>';
        return $completeAction . ' <a class="btn btn-sm btn-danger" href="' . e(action_url('cancel_appointment', ['id' => $appointmentId])) . '">Cancel</a>';
    }
    return '<span class="text-muted text-sm">No actions</span>';
}

// ============================================
// RENDER APPOINTMENTS - UPDATED VERSION (KEMAS & RESPONSIVE)
// ============================================

function render_appointments($role) {
    global $conn;
    $appointments = get_appointments($conn, $role, null, null, true);

    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search appointments..." id="searchAppointment"></div><div class="filter-group"><input class="form-control" type="date" id="filterDate" style="width:160px"><select class="filter-select" id="filterStatus"><option value="">All Status</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option><option value="pending">Pending</option><option value="paid">Paid</option><option value="rejected">Rejected</option></select></div>';
    echo '</div><div class="card"><div class="card-body" style="padding:0; overflow-x:auto"><table class="appointments-table" style="width:100%; border-collapse:collapse; min-width:900px"><thead><tr><th>ID</th><th>User</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead><tbody id="appointmentsTableBody">';

    if (empty($appointments)) {
        echo '<tr><td colspan="9" style="text-align:center">No appointments found.</td></tr>';
    }

    foreach ($appointments as $a) {
        echo '<tr><td>' . e($a['appointment_code']) . '</td><td>' . e($a['name']) . '</td><td>' . e($a['doctor_name']) . '</td><td>' . e($a['service_name']) . '</td><td data-date="' . e($a['appointment_date']) . '">' . e(format_date_display($a['appointment_date'])) . '</td><td>' . e(format_time_display($a['appointment_time'])) . '</td><td>' . appointment_badge($a['appointment_status'], $role) . '</td><td>' . badge($a['payment_status']) . '</td><td class="appt-actions-cell">' . appointment_actions($role, $a) . '</td></tr>';
    }

    echo '</tbody></table></div></div>';
    if ($role === 'user') {
        echo '<div class="modal-overlay" id="modal-appointment-details"><div class="modal appointment-details-modal"><div class="modal-header"><span class="modal-title">Appointment Details</span><button class="modal-close appointment-modal-close" onclick="closeModal(\'modal-appointment-details\')">×</button></div><div class="modal-body"><div class="appointment-detail-code"><span>Appointment ID</span><strong id="detailAppointmentCode"></strong></div><div class="appointment-detail-list"><div><span>Patient</span><strong id="detailPatient"></strong></div><div><span>Doctor</span><strong id="detailDoctor"></strong></div><div><span>Service</span><strong id="detailService"></strong></div><div><span>Date</span><strong id="detailDate"></strong></div><div><span>Time</span><strong id="detailTime"></strong></div><div><span>Status</span><strong id="detailStatus"></strong></div><div><span>Payment</span><strong id="detailPayment"></strong></div><div><span>Amount</span><strong class="detail-amount" id="detailAmount"></strong></div><div class="appointment-detail-notes"><span>Notes</span><strong id="detailNotes"></strong></div></div></div><div class="modal-footer"><button type="button" class="btn btn-outline" id="detailPaymentAction" style="display:none;width:auto"></button><button type="button" class="btn btn-primary appointment-detail-close" onclick="closeModal(\'modal-appointment-details\')">Close</button></div></div></div>';
        echo '<div class="modal-overlay" id="modal-edit-notes"><div class="modal"><div class="modal-header"><span class="modal-title">Edit Notes</span><button class="modal-close" onclick="closeModal(\'modal-edit-notes\')">×</button></div><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="save_appointment_notes"><input type="hidden" name="appointment_code" id="editNotesAppointmentCode"><div class="modal-body"><div class="form-group"><label for="editAppointmentNotes">Notes</label><textarea class="form-control" id="editAppointmentNotes" name="notes" rows="7"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-edit-notes\')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>';
        echo '<div class="modal-overlay" id="modal-payment-proof"><div class="modal payment-proof-modal"><div class="modal-header"><span class="modal-title">Payment Proof</span><button class="modal-close" onclick="closeModal(\'modal-payment-proof\')">×</button></div><div class="modal-body"><div class="payment-proof-card" id="paymentProofContent"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" style="width:auto" onclick="closeModal(\'modal-payment-proof\')">Close</button></div></div></div>';
        echo '<div class="modal-overlay" id="modal-appointment-receipt"><div class="modal receipt-modal"><div class="modal-header"><span class="modal-title">Payment Receipt</span><button class="modal-close" onclick="closeModal(\'modal-appointment-receipt\')">×</button></div><div class="modal-body" id="appointmentReceiptContent"></div><div class="modal-footer"><button class="btn btn-primary" style="width:auto" onclick="window.print()">Print</button><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-appointment-receipt\')">Close</button></div></div></div>';
    }
    echo '<script>
    function formatStatusLabel(status) {
        const labels = { unpaid: "Unpaid", paid: "Paid" };
        return labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : "");
    }
    function setDetailBadge(id, status) {
        const target = document.getElementById(id);
        if (!target) return;
        target.innerHTML = "<span class=\"badge badge-" + status + "\">" + formatStatusLabel(status) + "</span>";
    }
    function trimDetailNotes(notes) {
        notes = (notes || "").trim();
        if (!notes) return "-";
        return notes.length > 120 ? notes.slice(0, 120) + "..." : notes;
    }
    function setDetailPaymentAction(button) {
        const action = document.getElementById("detailPaymentAction");
        if (!action) return;

        const status = button.dataset.status || "";
        const payment = button.dataset.payment || "";
        if (status === "cancelled") {
            action.style.display = "none";
            action.onclick = null;
            return;
        }

        action.style.display = "inline-flex";
        action.className = "btn btn-outline";
        action.style.width = "auto";

        if (payment === "pending") {
            action.textContent = "Pay";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (payment === "rejected") {
            action.textContent = "Retry Payment";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (payment === "verifying") {
            action.textContent = "View Payment Proof";
            action.onclick = function () {
                openPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "");
            };
        } else if (payment === "paid") {
            action.textContent = "View Receipt";
            action.onclick = function () {
                printAppointmentReceipt(Number(button.dataset.receiptId || 0));
            };
        } else {
            action.style.display = "none";
            action.onclick = null;
        }
    }
    function showAppointmentDetails(button) {
        document.getElementById("detailAppointmentCode").textContent = button.dataset.code || "";
        document.getElementById("detailPatient").textContent = button.dataset.patient || "";
        document.getElementById("detailDoctor").textContent = button.dataset.doctor || "";
        document.getElementById("detailService").textContent = button.dataset.service || "";
        document.getElementById("detailDate").textContent = button.dataset.date || "";
        document.getElementById("detailTime").textContent = button.dataset.time || "";
        document.getElementById("detailNotes").textContent = trimDetailNotes(button.dataset.notes);
        document.getElementById("detailAmount").textContent = button.dataset.amount || "";
        setDetailBadge("detailStatus", button.dataset.status || "");
        setDetailBadge("detailPayment", button.dataset.payment || "");
        setDetailPaymentAction(button);
        openModal("modal-appointment-details");
    }
    function openEditNotesModal(button) {
        document.getElementById("editNotesAppointmentCode").value = button.dataset.code || "";
        document.getElementById("editAppointmentNotes").value = button.dataset.notes || "";
        openModal("modal-edit-notes");
    }
    function openPaymentProof(proofUrl, proofExt) {
        proofExt = (proofExt || "").toLowerCase();
        const content = document.getElementById("paymentProofContent");
        if (!content) return;
        if (!proofUrl) {
            content.innerHTML = "<p class=\"text-muted text-center\">No payment proof uploaded.</p>";
        } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(proofExt)) {
            content.innerHTML = "<img src=\"" + proofUrl + "\" alt=\"Payment proof\">";
        } else {
            content.innerHTML = "<p class=\"text-muted text-center mb-16\">This payment proof file cannot be previewed here.</p><a class=\"btn btn-outline\" style=\"width:auto\" target=\"_blank\" rel=\"noopener\" href=\"" + proofUrl + "\">Open File</a>";
        }
        openModal("modal-payment-proof");
    }
    function openPaymentProofModal(button) {
        openPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "");
    }
    async function printAppointmentReceipt(paymentId) {
        if (!paymentId) {
            alert("Receipt not found.");
            return;
        }
        const response = await fetch("' . e(app_url('action.php')) . '", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=get_receipt&payment_id=" + encodeURIComponent(paymentId)
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById("appointmentReceiptContent").innerHTML = data.html;
            openModal("modal-appointment-receipt");
        } else {
            alert("Error loading receipt");
        }
    }
    function closeAppointmentMenus() {
        document.querySelectorAll(".appt-action-menu.open").forEach(menu => menu.classList.remove("open"));
    }
    function toggleAppointmentMenu(event, button) {
        event.stopPropagation();
        const menu = button.closest(".appt-action-menu");
        const menuList = menu.querySelector(".appt-menu-list");
        const wasOpen = menu.classList.contains("open");
        closeAppointmentMenus();
        menu.classList.toggle("open", !wasOpen);
        if (!wasOpen && menuList) {
            const rect = button.getBoundingClientRect();
            const menuWidth = menuList.offsetWidth || 148;
            const left = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, rect.right - menuWidth));
            menuList.style.top = (rect.bottom + 6) + "px";
            menuList.style.left = left + "px";
        }
    }
    document.addEventListener("click", closeAppointmentMenus);
    function filterAppointments() {
        const searchValue = document.getElementById("searchAppointment")?.value.toLowerCase() || "";
        const filterDate = document.getElementById("filterDate")?.value || "";
        const filterStatus = document.getElementById("filterStatus")?.value || "";
        const rows = document.querySelectorAll("#appointmentsTableBody tr");
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const dateCell = row.cells[4]?.dataset.date || "";
            const statusCell = ((row.cells[6]?.innerText || "") + " " + (row.cells[7]?.innerText || "")).toLowerCase();
            let show = true;
            if (searchValue && !text.includes(searchValue)) show = false;
            if (filterDate && dateCell !== filterDate) show = false;
            if (filterStatus && !statusCell.includes(filterStatus)) show = false;
            row.style.display = show ? "" : "none";
        });
    }
    document.getElementById("searchAppointment")?.addEventListener("keyup", filterAppointments);
    document.getElementById("filterDate")?.addEventListener("change", filterAppointments);
    document.getElementById("filterStatus")?.addEventListener("change", filterAppointments);
    </script>';
}

function render_book_legacy() {
    global $conn;
    $services = get_services($conn);
    $doctors = get_doctors($conn);
    echo '<form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="book_appointment"><div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Choose Service</span></div><div class="card-body"><div class="services-grid">';
    foreach ($services as $s) echo '<label class="service-card"><input type="checkbox" name="service" value="' . e($s['service_name']) . '" required> <span class="service-icon">' . e($s['service_icon']) . '</span><div class="service-name">' . e($s['service_name']) . '</div><div class="service-price">RM ' . e(number_format((float) $s['service_price'], 2)) . '</div><div class="service-desc">' . e($s['service_description']) . '</div></label>';
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Choose Doctor</span></div><div class="card-body"><div class="doctor-grid">';
    foreach ($doctors as $d) echo '<label class="doctor-card"><input type="radio" name="doctor" value="' . e($d['doctor_name']) . '" required><div class="doctor-avatar">' . e($d['doctor_icon']) . '</div><div class="doctor-name">' . e($d['doctor_name']) . '</div><div class="doctor-spec">' . e($d['doctor_specialist']) . '</div><div class="doctor-avail">Available ' . e($d['available_days'] ?: 'Not scheduled') . '</div></label>';
    echo '</div></div></div></div><div class="card mt-20"><div class="card-header"><span class="card-title">Date, Time & Notes</span></div><div class="card-body"><div class="grid-2"><div class="form-group"><label>Date</label><input class="form-control" type="date" name="date" required></div><div class="form-group"><label>Time</label><select class="form-control" name="time" required><option>09:00</option><option>09:30</option><option>10:00</option><option>10:30</option><option>11:00</option><option>14:00</option></select></div></div><div class="form-group"><label>Symptoms / Notes</label><textarea class="form-control" rows="5" name="notes" placeholder="e.g. Fever for 3 days, headache…"></textarea></div><button class="btn btn-primary" style="width:auto">Confirm Appointment</button></div></div></form>';
}

function render_book() {
    global $conn;
    $services = get_services($conn);
    $doctors = get_doctors($conn);
    $today = date('Y-m-d');
    $scheduleRows = fetch_all_assoc(
        $conn,
        "SELECT d.doctor_name, ds.available_day, ds.start_time, ds.end_time
         FROM doctor_schedule ds
         INNER JOIN doctors d ON d.doctor_id = ds.doctor_id
         ORDER BY d.doctor_id ASC, FIELD(ds.available_day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'), ds.start_time ASC"
    );
    $doctorSchedules = [];
    foreach ($scheduleRows as $row) {
        $doctorName = $row['doctor_name'] ?? '';
        $day = $row['available_day'] ?? '';
        if ($doctorName === '' || $day === '') {
            continue;
        }

        $start = DateTime::createFromFormat('H:i:s', $row['start_time']) ?: DateTime::createFromFormat('H:i', $row['start_time']);
        $end = DateTime::createFromFormat('H:i:s', $row['end_time']) ?: DateTime::createFromFormat('H:i', $row['end_time']);
        if (!$start || !$end || $start >= $end) {
            continue;
        }

        if (!isset($doctorSchedules[$doctorName])) {
            $doctorSchedules[$doctorName] = [];
        }
        if (!isset($doctorSchedules[$doctorName][$day])) {
            $doctorSchedules[$doctorName][$day] = [];
        }

        $slot = clone $start;
        while ($slot < $end) {
            $doctorSchedules[$doctorName][$day][] = $slot->format('H:i');
            $slot->modify('+30 minutes');
        }
        $doctorSchedules[$doctorName][$day] = array_values(array_unique($doctorSchedules[$doctorName][$day]));
    }
    $doctorSchedulesJson = json_encode($doctorSchedules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $bookedRows = fetch_all_assoc(
        $conn,
        "SELECT doctor_name, appointment_date, appointment_time
         FROM appointments
         WHERE appointment_status NOT IN ('rejected', 'cancelled')
         ORDER BY appointment_date ASC, appointment_time ASC"
    );
    $bookedSlots = [];
    foreach ($bookedRows as $row) {
        $doctorName = $row['doctor_name'] ?? '';
        $appointmentDate = $row['appointment_date'] ?? '';
        $appointmentTime = $row['appointment_time'] ?? '';
        if ($doctorName === '' || $appointmentDate === '' || $appointmentTime === '') {
            continue;
        }
        if (!isset($bookedSlots[$doctorName])) {
            $bookedSlots[$doctorName] = [];
        }
        if (!isset($bookedSlots[$doctorName][$appointmentDate])) {
            $bookedSlots[$doctorName][$appointmentDate] = [];
        }
        $bookedSlots[$doctorName][$appointmentDate][] = date('H:i', strtotime($appointmentTime));
    }
    $bookedSlotsJson = json_encode($bookedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    echo '<form id="bookingWizardForm" class="booking-wizard" method="post" action="' . e(app_url('action.php')) . '">';
    echo '<input type="hidden" name="action" value="book_appointment">';
    echo '<input type="hidden" name="service" id="selectedServiceInput" value="">';
    echo '<input type="hidden" name="doctor" id="selectedDoctorInput" value="">';
    echo '<input type="hidden" name="time" id="selectedTimeInput" value="">';

    echo '<div class="booking-steps">';
    echo '<div class="booking-step active" data-step="1"><span class="step-num">1</span><span class="step-label">Select Service</span></div>';
    echo '<div class="booking-step" data-step="2"><span class="step-num">2</span><span class="step-label">Choose Doctor</span></div>';
    echo '<div class="booking-step" data-step="3"><span class="step-num">3</span><span class="step-label">Date &amp; Time</span></div>';
    echo '<div class="booking-step" data-step="4"><span class="step-num">4</span><span class="step-label">Confirm</span></div>';
    echo '</div>';

    echo '<section class="booking-panel active" data-panel="1">';
    echo '<h2 class="booking-section-title">Select a Service</h2>';
    echo '<div class="book-service-grid">';
    foreach ($services as $s) {
        $price = (float) ($s['service_price'] ?? 0);
        $priceText = rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.');
        if ($priceText === '') {
            $priceText = '0';
        }
        echo '<button type="button" class="book-service-card js-select-service" data-service-name="' . e($s['service_name']) . '" data-service-price="' . e($priceText) . '" data-service-description="' . e($s['service_description']) . '">';
        echo '<span class="service-icon">' . e($s['service_icon']) . '</span>';
        echo '<div class="service-name">' . e($s['service_name']) . '</div>';
        echo '<div class="service-price">RM ' . e($priceText) . '</div>';
        echo '<div class="service-desc">' . e($s['service_description']) . '</div>';
        echo '</button>';
    }
    echo '</div>';
    echo '<div class="book-nav book-nav-right"><button type="button" class="btn btn-primary js-next-step" data-next-step="2">Next: Choose Doctor &rarr;</button></div>';
    echo '</section>';

    echo '<section class="booking-panel" data-panel="2">';
    echo '<h2 class="booking-section-title">Choose a Doctor</h2>';
    echo '<div class="book-doctor-grid">';
    foreach ($doctors as $d) {
        $availableDays = $d['available_days'] ?: 'Not scheduled';
        echo '<button type="button" class="book-doctor-card js-select-doctor" data-doctor-name="' . e($d['doctor_name']) . '" data-doctor-specialist="' . e($d['doctor_specialist']) . '">';
        echo '<div class="doctor-avatar">' . e($d['doctor_icon']) . '</div>';
        echo '<div class="doctor-name">' . e($d['doctor_name']) . '</div>';
        echo '<div class="doctor-spec">' . e($d['doctor_specialist']) . '</div>';
        echo '<div class="doctor-avail">&#9989; ' . e($availableDays) . '</div>';
        echo '</button>';
    }
    echo '</div>';
    echo '<div class="book-nav"><button type="button" class="btn btn-outline js-back-step" data-back-step="1">&larr; Back</button><button type="button" class="btn btn-primary js-next-step" data-next-step="3">Next: Date &amp; Time &rarr;</button></div>';
    echo '</section>';

    echo '<section class="booking-panel" data-panel="3">';
    echo '<div class="book-step3-grid">';
    echo '<div><h2 class="booking-section-title">Select Date</h2><div class="form-group"><input class="form-control booking-date-input" id="bookingDateInput" type="date" name="date" min="' . e($today) . '" value="' . e($today) . '" required></div>';
    echo '<h2 class="booking-section-title booking-subtitle">Available Time Slots</h2>';
    echo '<div class="book-time-grid" id="bookTimeGrid"><div class="book-empty-slots">Choose a doctor and date first.</div></div></div>';
    echo '<div class="book-summary-card"><h3>Your Selection</h3><div class="book-summary-list">';
    echo '<div class="book-summary-row"><span>Service</span><strong id="summaryServiceStep3">-</strong></div>';
    echo '<div class="book-summary-row"><span>Doctor</span><strong id="summaryDoctorStep3">-</strong></div>';
    echo '<div class="book-summary-row"><span>Date</span><strong id="summaryDateStep3">-</strong></div>';
    echo '<div class="book-summary-row"><span>Time</span><strong id="summaryTimeStep3">-</strong></div>';
    echo '<div class="book-summary-row"><span>Fee</span><strong class="book-fee" id="summaryFeeStep3">RM 0</strong></div>';
    echo '</div></div></div>';
    echo '<div class="book-nav"><button type="button" class="btn btn-outline js-back-step" data-back-step="2">&larr; Back</button><button type="button" class="btn btn-primary js-next-step" data-next-step="4">Next: Confirm &rarr;</button></div>';
    echo '</section>';

    echo '<section class="booking-panel" data-panel="4">';
    echo '<div class="book-step4-grid">';
    echo '<div class="book-summary-card"><h3>Appointment Summary</h3><div class="book-summary-list appointment-summary-list">';
    echo '<div class="appointment-summary-group summary-service-group"><div class="book-summary-row"><span>Service</span><strong id="summaryServiceStep4" class="summary-multi-service">-</strong></div></div>';
    echo '<div class="appointment-summary-group summary-doctor-group"><div class="book-summary-row"><span>Doctor</span><strong id="summaryDoctorStep4">-</strong></div><div class="book-summary-row"><span>Specialization</span><strong id="summarySpecialistStep4">-</strong></div></div>';
    echo '<div class="appointment-summary-group summary-datetime-group"><div class="book-summary-row"><span>Date</span><strong id="summaryDateStep4">-</strong></div><div class="book-summary-row"><span>Time</span><strong id="summaryTimeStep4">-</strong></div></div>';
    echo '<div class="appointment-summary-group summary-price-group"><div class="book-summary-row"><span>Estimated Fee</span><strong class="book-fee" id="summaryFeeStep4">RM 0</strong></div></div>';
    echo '</div></div>';
    echo '<div class="book-summary-card"><h3>Notes (Optional)</h3><div class="form-group"><label for="bookingNotesInput">Describe your symptoms or reason for visit</label><textarea id="bookingNotesInput" class="form-control booking-notes" name="notes" rows="7" placeholder="e.g. Fever for 3 days, headache..."></textarea></div></div>';
    echo '</div>';
    echo '<div class="book-nav"><button type="button" class="btn btn-outline js-back-step" data-back-step="3">&larr; Back</button><button type="submit" class="btn btn-primary">&#10004; Confirm Appointment</button></div>';
    echo '</section>';

    echo '</form>';

    echo '<script>
    (function () {
        var wizard = document.getElementById("bookingWizardForm");
        if (!wizard) return;

        var doctorSchedules = ' . ($doctorSchedulesJson ?: '{}') . ';
        var bookedSlots = ' . ($bookedSlotsJson ?: '{}') . ';
        var steps = wizard.querySelectorAll(".booking-step");
        var panels = wizard.querySelectorAll(".booking-panel");
        var timeGrid = document.getElementById("bookTimeGrid");
        var state = {
            services: [],
            servicePriceTotal: 0,
            doctor: "",
            doctorSpecialist: "",
            date: "",
            time: ""
        };

        var serviceInput = document.getElementById("selectedServiceInput");
        var doctorInput = document.getElementById("selectedDoctorInput");
        var timeInput = document.getElementById("selectedTimeInput");
        var dateInput = document.getElementById("bookingDateInput");
        if (dateInput) {
            state.date = dateInput.value || "";
        }

        function formatDate(value) {
            if (!value) return "-";
            var date = new Date(value + "T00:00:00");
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString("en-US", { year: "numeric", month: "2-digit", day: "2-digit" });
        }

        function selectedDayName() {
            if (!state.date) return "";
            var date = new Date(state.date + "T00:00:00");
            if (Number.isNaN(date.getTime())) return "";
            return ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"][date.getDay()];
        }

        function renderTimeSlots() {
            if (!timeGrid) return;
            timeGrid.innerHTML = "";
            state.time = "";
            timeInput.value = "";

            if (!state.doctor || !state.date) {
                timeGrid.innerHTML = "<div class=\"book-empty-slots\">Choose a doctor and date first.</div>";
                updateSummary();
                return;
            }

            var dayName = selectedDayName();
            var slots = ((doctorSchedules[state.doctor] || {})[dayName] || []);
            var booked = ((bookedSlots[state.doctor] || {})[state.date] || []);
            if (slots.length === 0) {
                timeGrid.innerHTML = "<div class=\"book-empty-slots\">No available slots for this date.</div>";
                updateSummary();
                return;
            }

            slots.forEach(function (slot) {
                var btn = document.createElement("button");
                var isBooked = booked.indexOf(slot) !== -1;
                btn.type = "button";
                btn.className = "book-time-slot js-time-slot" + (isBooked ? " unavailable" : "");
                btn.dataset.time = slot;
                btn.textContent = slot;
                btn.disabled = isBooked;
                if (isBooked) {
                    btn.title = "Already booked";
                    timeGrid.appendChild(btn);
                    return;
                }
                btn.addEventListener("click", function () {
                    timeGrid.querySelectorAll(".js-time-slot").forEach(function (item) {
                        item.classList.remove("selected");
                    });
                    btn.classList.add("selected");
                    state.time = slot;
                    timeInput.value = slot;
                    updateSummary();
                });
                timeGrid.appendChild(btn);
            });

            updateSummary();
        }

        function updateServiceState() {
            var selected = wizard.querySelectorAll(".js-select-service.selected");
            var names = [];
            var total = 0;
            selected.forEach(function (item) {
                names.push(item.getAttribute("data-service-name") || "");
                total += parseFloat(item.getAttribute("data-service-price") || "0") || 0;
            });
            state.services = names.filter(function (name) { return name !== ""; });
            state.servicePriceTotal = total;
            serviceInput.value = state.services.join(", ");
        }

        function updateSummary() {
            var feeText = "RM " + state.servicePriceTotal.toFixed(2);
            var serviceText = state.services.length ? state.services.join(", ") : "-";
            var serviceTextVertical = state.services.length ? state.services.join("\n") : "-";
            var summaryMap = {
                summaryServiceStep3: serviceText,
                summaryDoctorStep3: state.doctor || "-",
                summaryDateStep3: formatDate(state.date),
                summaryTimeStep3: state.time || "-",
                summaryFeeStep3: feeText,
                summaryDoctorStep4: state.doctor || "-",
                summarySpecialistStep4: state.doctorSpecialist || "-",
                summaryDateStep4: formatDate(state.date),
                summaryTimeStep4: state.time || "-",
                summaryFeeStep4: feeText
            };

            Object.keys(summaryMap).forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.textContent = summaryMap[id];
            });

            var summaryServiceStep4 = document.getElementById("summaryServiceStep4");
            if (summaryServiceStep4) {
                summaryServiceStep4.textContent = serviceTextVertical;
            }
        }

        function goToStep(stepNumber) {
            steps.forEach(function (stepEl, idx) {
                var current = idx + 1;
                stepEl.classList.toggle("active", current === stepNumber);
                stepEl.classList.toggle("done", current < stepNumber);
            });

            panels.forEach(function (panelEl) {
                panelEl.classList.toggle("active", panelEl.getAttribute("data-panel") === String(stepNumber));
            });

            updateSummary();
        }

        function validateStep(stepNumber) {
            if (stepNumber === 1 && state.services.length === 0) {
                alert("Please select at least one service first.");
                return false;
            }
            if (stepNumber === 2 && !state.doctor) {
                alert("Please choose a doctor first.");
                return false;
            }
            if (stepNumber === 3) {
                if (!state.date) {
                    alert("Please select a date first.");
                    return false;
                }
                if (!state.time) {
                    alert("Please select a time slot first.");
                    return false;
                }
            }
            return true;
        }

        wizard.querySelectorAll(".js-select-service").forEach(function (btn) {
            btn.addEventListener("click", function () {
                btn.classList.toggle("selected");
                updateServiceState();
                updateSummary();
            });
        });

        wizard.querySelectorAll(".js-select-doctor").forEach(function (btn) {
            btn.addEventListener("click", function () {
                wizard.querySelectorAll(".js-select-doctor").forEach(function (item) {
                    item.classList.remove("selected");
                });
                btn.classList.add("selected");
                state.doctor = btn.getAttribute("data-doctor-name") || "";
                state.doctorSpecialist = btn.getAttribute("data-doctor-specialist") || "";
                doctorInput.value = state.doctor;
                renderTimeSlots();
                updateSummary();
            });
        });

        if (dateInput) {
            dateInput.addEventListener("change", function () {
                state.date = dateInput.value || "";
                renderTimeSlots();
            });
        }

        wizard.querySelectorAll(".js-next-step").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var currentStep = Number(btn.getAttribute("data-next-step")) - 1;
                if (!validateStep(currentStep)) return;
                goToStep(Number(btn.getAttribute("data-next-step")));
            });
        });

        wizard.querySelectorAll(".js-back-step").forEach(function (btn) {
            btn.addEventListener("click", function () {
                goToStep(Number(btn.getAttribute("data-back-step")));
            });
        });

        wizard.addEventListener("submit", function (event) {
            if (state.services.length === 0 || !state.doctor || !state.date || !state.time) {
                event.preventDefault();
                alert("Please complete all booking steps before confirming.");
            }
        });

        updateServiceState();
        goToStep(1);
    })();
    </script>';
}

function render_payment($role) {
    global $conn;
    $appointments = get_appointments($conn, $role);
    $payAmount = $appointments[0]['amount'] ?? '0.00';
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">' . ($role === 'user' ? 'Make Payment' : 'Manage Payments') . '</span></div><div class="card-body"><form method="post" action="action.php"><input type="hidden" name="action" value="process_payment"><div class="form-group"><label>Select Appointment</label><select class="form-control" name="appointment">';
    foreach ($appointments as $a) {
        echo '<option value="' . e($a['appointment_code']) . '">' . e($a['appointment_code'] . ' - ' . $a['service_name'] . ' - RM ' . number_format((float) $a['amount'], 2)) . '</option>';
    }
    echo '</select></div><h3 class="mb-16">Payment Method</h3><div class="payment-methods"><label class="payment-method selected"><input type="radio" name="method" value="card" checked><span class="pm-icon">Card</span>Credit Card</label><label class="payment-method"><input type="radio" name="method" value="bank"><span class="pm-icon">Bank</span>Online Banking</label><label class="payment-method"><input type="radio" name="method" value="wallet"><span class="pm-icon">Wallet</span>e-Wallet</label></div><div class="form-group"><label>Card Number</label><input class="form-control" placeholder="1234 5678 9012 3456"></div><button class="btn btn-primary">Pay RM ' . e(number_format((float) $payAmount, 2)) . '</button></form></div></div><div class="card"><div class="card-header"><span class="card-title">Payment History</span></div><div class="card-body" style="padding:0"><table><thead><tr><th>Appointment</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ($appointments as $a) {
        echo '<tr><td>' . e($a['appointment_code']) . '</td><td>' . e(format_date_display($a['appointment_date'])) . '</td><td>RM ' . e(number_format((float) $a['amount'], 2)) . '</td><td>' . badge($a['payment_status']) . '</td><td><a class="btn btn-sm btn-outline" href="' . e(action_url('receipt', ['id' => $a['appointment_code']])) . '">Receipt</a></td></tr>';
    }
    echo '</tbody></table></div></div></div>';
}

function render_reports() {
    global $conn;

    $summary = fetch_all_assoc(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(appointment_status = 'completed') AS completed,
            SUM(appointment_status IN ('confirmed', 'confirm')) AS confirmed,
            SUM(appointment_status = 'cancelled') AS cancelled
         FROM appointments"
    )[0] ?? ['total' => 0, 'completed' => 0, 'confirmed' => 0, 'cancelled' => 0];

    $monthlyRows = fetch_all_assoc(
        $conn,
        "SELECT
            DATE_FORMAT(appointment_date, '%M %Y') AS month_label,
            COUNT(*) AS total,
            SUM(appointment_status = 'completed') AS completed
         FROM appointments
         GROUP BY YEAR(appointment_date), MONTH(appointment_date)
         ORDER BY YEAR(appointment_date) DESC, MONTH(appointment_date) DESC"
    );

    $paymentSummary = fetch_all_assoc(
        $conn,
        "SELECT
            COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'approved') THEN amount ELSE 0 END), 0) AS revenue,
            SUM(payment_status IN ('paid', 'approved')) AS paid_count,
            COALESCE(SUM(CASE WHEN payment_status IN ('pending', 'verifying') THEN amount ELSE 0 END), 0) AS pending_amount
         FROM payments"
    )[0] ?? ['revenue' => 0, 'paid_count' => 0, 'pending_amount' => 0];

    $paymentMonthlyRows = fetch_all_assoc(
        $conn,
        "SELECT
            DATE_FORMAT(payment_date, '%M %Y') AS month_label,
            COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'approved') THEN amount ELSE 0 END), 0) AS revenue,
            SUM(payment_status IN ('paid', 'approved')) AS invoices
         FROM payments
         GROUP BY YEAR(payment_date), MONTH(payment_date)
         ORDER BY YEAR(payment_date) DESC, MONTH(payment_date) DESC"
    );

    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Appointment Report</span><a class="btn btn-sm btn-outline" href="' . e(action_url('export_report')) . '">⬇ Export</a></div><div class="card-body"><div class="report-summary">';
    echo '<div class="report-item"><div class="val">' . e((int)($summary['total'] ?? 0)) . '</div><div class="lbl">Total</div></div>';
    echo '<div class="report-item"><div class="val">' . e((int)($summary['completed'] ?? 0)) . '</div><div class="lbl">Completed</div></div>';
    echo '<div class="report-item"><div class="val">' . e((int)($summary['confirmed'] ?? 0)) . '</div><div class="lbl">Pending</div></div>';
    echo '<div class="report-item"><div class="val">' . e((int)($summary['cancelled'] ?? 0)) . '</div><div class="lbl">Cancelled</div></div></div>';
    echo '<table><thead><tr><th>Month</th><th>Total</th><th>Completed</th><th>Rate</th></tr></thead><tbody>';
    if (empty($monthlyRows)) {
        echo '<tr><td colspan="4" style="text-align:center">No appointment data found.</td></tr>';
    }
    foreach ($monthlyRows as $row) {
        $total = (int)($row['total'] ?? 0);
        $completed = (int)($row['completed'] ?? 0);
        $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
        $rateClass = $rate >= 80 ? 'paid' : ($rate >= 50 ? 'pending' : 'rejected');
        echo '<tr><td>' . e($row['month_label']) . '</td><td>' . e($total) . '</td><td>' . e($completed) . '</td><td><span class="badge badge-' . e($rateClass) . '">' . e($rate) . '%</span></td></tr>';
    }
    echo '</tbody></table></div></div><div class="card"><div class="card-header"><span class="card-title">Payment Report</span><a class="btn btn-sm btn-outline" href="' . e(action_url('export_report')) . '">⬇ Export</a></div><div class="card-body"><div class="report-summary">';
    echo '<div class="report-item"><div class="val">RM ' . e(number_format((float)($paymentSummary['revenue'] ?? 0), 2)) . '</div><div class="lbl">Total Revenue</div></div>';
    echo '<div class="report-item"><div class="val">' . e((int)($paymentSummary['paid_count'] ?? 0)) . '</div><div class="lbl">Paid Invoices</div></div>';
    echo '<div class="report-item"><div class="val">RM ' . e(number_format((float)($paymentSummary['pending_amount'] ?? 0), 2)) . '</div><div class="lbl">Pending</div></div>';
    echo '</div><table><thead><tr><th>Month</th><th>Revenue</th><th>Invoices</th></tr></thead><tbody>';
    if (empty($paymentMonthlyRows)) {
        echo '<tr><td colspan="3" style="text-align:center">No payment data found.</td></tr>';
    }
    foreach ($paymentMonthlyRows as $row) {
        echo '<tr><td>' . e($row['month_label']) . '</td><td>RM ' . e(number_format((float)($row['revenue'] ?? 0), 2)) . '</td><td>' . e((int)($row['invoices'] ?? 0)) . '</td></tr>';
    }
    echo '</tbody></table></div></div></div>';
}

function render_staff() {
    global $conn;
    $staff = fetch_all_assoc(
        $conn,
        "SELECT user_id, user_code, name, email, phone_number, user_status
         FROM users
         WHERE role = ?
         ORDER BY user_code ASC",
        's',
        ['staff']
    );

    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search staff…" id="searchStaff"></div><button class="btn btn-primary" style="width:auto" onclick="openModal(\'modal-add-staff\')">+ Add Staff</button></div><div class="card"><div class="card-body" style="padding:0"><table><thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Email</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead><tbody id="staffTableBody">';
    if (empty($staff)) {
        echo '<tr><td colspan="7" style="text-align:center">No staff found.</td></tr>';
    }
    foreach ($staff as $s) {
        $status = strtolower($s['user_status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
        echo '<tr><td>' . e($s['user_code']) . '</td><td>' . e($s['name']) . '</td><td>Staff</td><td>' . e($s['email']) . '</td><td>' . e(format_phone_number($s['phone_number'] ?? '')) . '</td><td>' . badge($status) . '</td><td class="flex gap-8"><button type="button" class="btn btn-sm btn-outline" onclick="openEditStaffModal(this)" data-id="' . e($s['user_id']) . '" data-name="' . e($s['name']) . '" data-email="' . e($s['email']) . '" data-phone="' . e(format_phone_number($s['phone_number'] ?? '')) . '">✏️ Edit</button><a class="btn btn-sm btn-danger" href="' . e(action_url('delete', ['type' => 'staff', 'id' => $s['user_id']])) . '" onclick="return confirm(\'Delete this staff member?\')">🗑 Delete</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<script>
    document.getElementById("searchStaff")?.addEventListener("input", function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll("#staffTableBody tr").forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(query) ? "" : "none";
        });
    });
    function openEditStaffModal(button) {
        document.getElementById("editStaffId").value = button.dataset.id || "";
        document.getElementById("editStaffName").value = button.dataset.name || "";
        document.getElementById("editStaffEmail").value = button.dataset.email || "";
        document.getElementById("editStaffPhone").value = button.dataset.phone || "";
        openModal("modal-edit-staff");
    }
    </script>';
}

// ============================================
// RENDER SCHEDULE - UPDATED VERSION (KEMAS & RESPONSIVE)
// ============================================

function render_schedule() {
    global $conn;
    $today = date('Y-m-d');
    $doctors = get_doctors($conn);
    $appointments = fetch_all_assoc(
        $conn,
        "SELECT appointment_code, name, doctor_name, service_name, appointment_date, appointment_time, appointment_status
         FROM appointments
         WHERE appointment_date = ? AND appointment_status <> 'cancelled'
         ORDER BY appointment_time ASC",
        's',
        [$today]
    );

    echo '<div class="toolbar"><input class="form-control" type="date" id="scheduleDate" style="width:200px" value="' . e($today) . '"><select class="filter-select" id="scheduleDoctor"><option value="">All Doctors</option>';
    foreach ($doctors as $doctor) {
        echo '<option>' . e($doctor['doctor_name']) . '</option>';
    }
    echo '</select></div><div class="card"><div class="card-body" style="padding:0; overflow-x:auto"><table class="data-table" style="width:100%; border-collapse:collapse; min-width:700px"><thead><tr><th>Time</th><th>User</th><th>Doctor</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    if (empty($appointments)) {
        echo '<tr><td colspan="6" style="text-align:center">No appointments found.</td></tr>';
    }
    foreach ($appointments as $row) {
        $canComplete = appointment_time_has_passed($row['appointment_date'] ?? '', $row['appointment_time'] ?? '');
        $action = in_array($row['appointment_status'], ['completed', 'cancelled', 'rejected'], true) || !$canComplete
            ? '<span class="text-muted">-</span>'
            : '<a class="btn btn-sm btn-teal" href="' . e(action_url('update_status', ['id' => $row['appointment_code']])) . '">Complete</a>';
        echo '<tr><td>' . e(format_time_display($row['appointment_time'])) . '</td><td>' . e($row['name']) . '</td><td>' . e($row['doctor_name']) . '</td><td>' . e($row['service_name']) . '</td><td>' . appointment_badge($row['appointment_status'], 'staff') . '</td><td>' . $action . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<script>
    function filterSchedule() {
        const doctor = document.getElementById("scheduleDoctor")?.value.toLowerCase() || "";
        const rows = document.querySelectorAll(".data-table tbody tr");
        rows.forEach(row => {
            const doctorCell = row.cells[2]?.innerText.toLowerCase() || "";
            row.style.display = doctor && !doctorCell.includes(doctor) ? "none" : "";
        });
    }
    document.getElementById("scheduleDoctor")?.addEventListener("change", filterSchedule);
    </script>';
}

function render_users() {
    global $conn;

    $users = fetch_all_assoc(
        $conn,
        "SELECT u.user_code, u.name, u.email, u.phone_number, u.gender, u.date_of_birth, u.blood_type, u.user_status,
                MAX(CASE
                    WHEN a.appointment_status = 'completed'
                         AND (
                             a.appointment_date < CURDATE()
                             OR (a.appointment_date = CURDATE() AND a.appointment_time < CURTIME())
                         )
                    THEN CONCAT(a.appointment_date, ' ', a.appointment_time)
                    ELSE NULL
                END) AS last_visit
         FROM users u
         LEFT JOIN appointments a ON a.name = u.name
         WHERE u.role = 'user'
         GROUP BY u.user_id, u.user_code, u.name, u.email, u.phone_number, u.gender, u.date_of_birth, u.blood_type, u.user_status
         ORDER BY u.user_id ASC"
    );

    echo '<div class="toolbar">
            <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input class="form-control" type="text" placeholder="Search users..." id="searchUser">
            </div>
            <select class="filter-select" id="userStatus">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
          </div>';
    
    echo '<div class="card">
            <div class="card-body" style="padding:0; overflow-x: auto;">
                <table class="data-table" style="width:100%; border-collapse: collapse; min-width: 700px;">
                    <thead>
                        <tr style="background: var(--surface2); border-bottom: 2px solid var(--border);">
                            <th style="padding: 12px 8px; text-align: left;">User ID</th>
                            <th style="padding: 12px 8px; text-align: left;">Name</th>
                            <th style="padding: 12px 8px; text-align: left;">Email</th>
                            <th style="padding: 12px 8px; text-align: left;">Phone</th>
                            <th style="padding: 12px 8px; text-align: left;">Gender</th>
                            <th style="padding: 12px 8px; text-align: left;">Date of Birth</th>
                            <th style="padding: 12px 8px; text-align: left;">Blood Type</th>
                            <th style="padding: 12px 8px; text-align: left;">Last Visit</th>
                            <th style="padding: 12px 8px; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">';
    
    if (empty($users)) {
        echo '<tr><td colspan="9" style="text-align:center;padding:16px">No users found.</td></tr>';
    }

    foreach ($users as $u) {
        $status = strtolower($u['user_status'] ?? 'inactive');
        $lastVisit = !empty($u['last_visit']) ? date('d M Y, H:i', strtotime($u['last_visit'])) : '-';
        echo '<tr data-status="' . e($status) . '" style="border-bottom: 1px solid var(--border);">
                <td style="padding: 12px 8px;">' . e($u['user_code']) . '</td>
                <td style="padding: 12px 8px;">' . e($u['name']) . '</td>
                <td style="padding: 12px 8px;">' . e($u['email']) . '</td>
                <td style="padding: 12px 8px;">' . e(format_phone_number($u['phone_number'] ?? '')) . '</td>
                <td style="padding: 12px 8px;">' . e($u['gender'] ?: '-') . '</td>
                <td style="padding: 12px 8px;">' . e(!empty($u['date_of_birth']) ? format_date_display($u['date_of_birth']) : '-') . '</td>
                <td style="padding: 12px 8px;">' . e($u['blood_type'] ?: '-') . '</td>
                <td style="padding: 12px 8px;">' . e($lastVisit) . '</td>
                <td style="padding: 12px 8px;">' . badge($status) . '</td>
               </tr>';
    }
    
    echo '</tbody>
                </table>
            </div>
          </div>';
    
    // Add search filter script
    echo '
    <script>
    function filterUsers() {
        const searchValue = document.getElementById("searchUser")?.value.toLowerCase() || "";
        const statusFilter = document.getElementById("userStatus")?.value.toLowerCase() || "";
        const rows = document.querySelectorAll("#usersTableBody tr");
        
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const statusValue = row.dataset.status || "";
            let show = true;
            
            if (searchValue && !text.includes(searchValue)) show = false;
            if (statusFilter && statusValue !== statusFilter) show = false;
            
            row.style.display = show ? "" : "none";
        });
    }
    
    const searchInput = document.getElementById("searchUser");
    const statusFilter = document.getElementById("userStatus");
    
    if (searchInput) searchInput.addEventListener("keyup", filterUsers);
    if (statusFilter) statusFilter.addEventListener("change", filterUsers);
    </script>';
}

function render_modals() {
    global $conn;
    $user = current_user($conn) ?: [];
    $gender = $user['gender'] ?? '';
    $bloodType = $user['blood_type'] ?? '';

    echo <<<'HTML'
<div class="modal-overlay" id="modal-add-staff"><div class="modal"><div class="modal-header"><span class="modal-title">Add Staff Member</span><button class="modal-close" onclick="closeModal('modal-add-staff')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_staff"><input type="hidden" name="role" value="staff"><div class="modal-body"><div class="form-group"><label>Full Name</label><input class="form-control" name="name" placeholder="e.g. Nurul Ain binti Razak" required></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" placeholder="staff@QuickCare.my" required></div><div class="form-group"><label>Password</label><input class="form-control" type="password" name="password" required></div><div class="form-group"><label>Phone</label><input class="form-control" name="phone_number" data-phone-format placeholder="+60 12-345 6789"></div><div class="form-group"><label>Role</label><input class="form-control" value="Staff" readonly></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-staff')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<div class="modal-overlay" id="modal-edit-staff"><div class="modal"><div class="modal-header"><span class="modal-title">Edit Staff Member</span><button class="modal-close" onclick="closeModal('modal-edit-staff')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="update_staff"><input type="hidden" name="id" id="editStaffId"><div class="modal-body"><div class="form-group"><label>Full Name</label><input class="form-control" name="name" id="editStaffName" required></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" id="editStaffEmail" required></div><div class="form-group"><label>Phone</label><input class="form-control" name="phone_number" id="editStaffPhone" data-phone-format placeholder="+60 12-345 6789"></div><div class="form-group"><label>Role</label><input class="form-control" value="Staff" readonly></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-edit-staff')">Cancel</button><button class="btn btn-primary" style="width:auto">Save Changes</button></div></form></div></div>
<div class="modal-overlay" id="modal-add-doctor"><div class="modal"><div class="modal-header"><span class="modal-title">Add Doctor</span><button class="modal-close" onclick="closeModal('modal-add-doctor')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_doctor"><div class="modal-body"><div class="form-group"><label>Full Name</label><input class="form-control" name="name" placeholder="e.g. Dr. Ahmad Fauzi"></div><div class="form-group"><label>Specialization</label><input class="form-control" name="specialization"></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email"></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-doctor')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<div class="modal-overlay" id="modal-add-service"><div class="modal"><div class="modal-header"><span class="modal-title">Add Service</span><button class="modal-close" onclick="closeModal('modal-add-service')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_service"><div class="modal-body"><div class="form-group"><label>Service Name</label><input class="form-control" name="name"></div><div class="form-group"><label>Fee (RM)</label><input class="form-control" type="number" name="fee"></div><div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="3"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-service')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
HTML;
    echo '<div class="modal-overlay" id="modal-edit-profile" data-static-modal="true"><div class="modal"><div class="modal-header"><span class="modal-title">Edit Profile</span><button class="modal-close" onclick="closeModal(\'modal-edit-profile\')">✕</button></div><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="save_profile"><div class="modal-body">';
    echo '<div class="form-group"><label>Full Name</label><input class="form-control" name="name" value="' . e($user['name'] ?? '') . '" required></div>';
    echo '<div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="' . e($user['email'] ?? '') . '" required></div>';
    echo '<div class="form-group"><label>Phone</label><input class="form-control" type="tel" name="phone_number" data-phone-format pattern="^\+60\s[0-9]{2}-[0-9]{3}\s[0-9]{4,5}$" placeholder="+60 12-345 6789" value="' . e(format_phone_number($user['phone_number'] ?? '')) . '"></div>';
    echo '<div class="form-group"><label>Gender</label><select class="form-control" name="gender"><option value="">Select gender</option><option value="Male"' . ($gender === 'Male' ? ' selected' : '') . '>Male</option><option value="Female"' . ($gender === 'Female' ? ' selected' : '') . '>Female</option></select></div>';
    echo '<div class="form-group"><label>Date of Birth</label><input class="form-control" type="date" name="date_of_birth" value="' . e($user['date_of_birth'] ?? '') . '" max="' . date('Y-m-d') . '" min="' . date('Y-m-d', strtotime('-120 years')) . '"></div>';
    echo '<div class="form-group"><label>Blood Type</label><select class="form-control" name="blood_type"><option value="">Select blood type</option>';
    foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type) {
        echo '<option value="' . e($type) . '"' . ($bloodType === $type ? ' selected' : '') . '>' . e($type) . '</option>';
    }
    echo '</select></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-edit-profile\')">Cancel</button><button class="btn btn-primary" style="width:auto">Save Changes</button></div></form></div></div>';
}

// ============================================
// PAYMENT FUNCTIONS - Untuk QR Payment System
// ============================================

// Get user's pending payments (confirmed appointments with pending/rejected payment status)
function get_user_pending_payments($user_id) {
    global $conn;
    
    // First get user name from users table
    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $user_name = $user['name'] ?? '';
    
    $query = "SELECT a.*, 
              a.doctor_name, 
              a.service_name, 
              a.amount 
              FROM appointments a
              WHERE a.name = ?
              AND a.appointment_status IN ('confirm', 'confirmed')
              AND a.payment_status IN ('pending', 'rejected')
              AND NOT EXISTS (
                  SELECT 1 FROM payments p 
                  WHERE p.appointment_code = a.appointment_code 
                  AND p.payment_status IN ('paid', 'approved')
              )";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();
    
    return $payments;
}

// Get user's payment history
function get_user_payment_history($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $user_name = $user['name'] ?? '';
    
    $query = "SELECT p.*, 
              a.appointment_code, 
              a.appointment_date, 
              a.appointment_time,
              a.doctor_name, 
              a.service_name
              FROM payments p
              LEFT JOIN appointments a ON p.appointment_code = a.appointment_code
              WHERE p.user_id = ?
              ORDER BY p.payment_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
    
    return $history;
}

// Get all payments for staff/admin
function get_all_payments() {
    global $conn;
    
    $query = "SELECT p.*, 
              u.name as patient_name, 
              a.appointment_code, 
              a.appointment_date,
              a.doctor_name, 
              a.service_name
              FROM payments p
              LEFT JOIN users u ON p.user_id = u.user_id
              LEFT JOIN appointments a ON p.appointment_code = a.appointment_code
              ORDER BY p.payment_date DESC";
    
    $result = $conn->query($query);
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    return $payments;
}

// Get pending payments for approval (admin)
function get_pending_payments() {
    global $conn;
    
    $query = "SELECT p.*, 
              u.name as patient_name, 
              u.email as patient_email,
              a.appointment_code, 
              a.appointment_date, 
              a.appointment_time,
              a.doctor_name, 
              a.service_name
              FROM payments p
              LEFT JOIN users u ON p.user_id = u.user_id
              LEFT JOIN appointments a ON p.appointment_code = a.appointment_code
              WHERE p.payment_status IN ('verifying', 'pending')
              ORDER BY p.payment_date ASC";
    
    $result = $conn->query($query);
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    return $payments;
}

// Generate receipt number
function generate_receipt_number() {
    return 'RCPT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Generate payment code
function generate_payment_code() {
    return 'PAY-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Submit payment (user upload receipt)
function submit_payment($user_id, $appointment_code, $amount, $transaction_id, $remarks, $receipt_file, $payment_status = 'verifying') {
    global $conn;
    
    // Get appointment details
    $stmt = $conn->prepare("
        SELECT a.*, a.doctor_name, a.service_name, a.appointment_date, a.appointment_time
        FROM appointments a
        WHERE a.appointment_code = ?
    ");
    $stmt->bind_param("s", $appointment_code);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$appointment) {
        return false;
    }
    
    $payment_code = generate_payment_code();
    $receipt_number = generate_receipt_number();
    
    $stmt = $conn->prepare("
        INSERT INTO payments (payment_code, user_id, appointment_code, receipt_number, amount, 
                              payment_status, transaction_id, receipt_image, remarks, payment_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sissdssss", $payment_code, $user_id, $appointment_code, $receipt_number,
                      $amount, $payment_status, $transaction_id, $receipt_file, $remarks);
    
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        // Update appointment payment status to show payment verification is in progress
        $stmt = $conn->prepare("UPDATE appointments SET payment_status = ? WHERE appointment_code = ?");
        $stmt->bind_param("ss", $payment_status, $appointment_code);
        $stmt->execute();
        $stmt->close();
    }
    
    return $success;
}

// Approve payment (admin)
function approve_payment($payment_id, $admin_id) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Get payment details
        $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$payment) {
            throw new Exception('Payment not found');
        }
        
        // Update payment status
        $stmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'paid', approved_by = ?, approved_date = NOW()
            WHERE payment_id = ?
        ");
        $stmt->bind_param("ii", $admin_id, $payment_id);
        $stmt->execute();
        $stmt->close();
        
        // Update appointment payment status to paid
        if ($payment['appointment_code']) {
            $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'paid' WHERE appointment_code = ?");
            $stmt->bind_param("s", $payment['appointment_code']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Insert into receipts table
        $stmt = $conn->prepare("
            INSERT INTO receipts (receipt_number, payment_id, user_id, amount, issued_date)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("siid", $payment['receipt_number'], $payment_id, $payment['user_id'], 
                          $payment['amount']);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        // Send email notification to user
        send_payment_approved_email($payment['user_id'], $payment);
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Reject payment (admin)
function reject_payment($payment_id, $admin_id, $reason) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE payments 
        SET payment_status = 'rejected', approved_by = ?, approved_date = NOW(), remarks = CONCAT(remarks, '\nRejected: ', ?)
        WHERE payment_id = ?
    ");
    $stmt->bind_param("isi", $admin_id, $reason, $payment_id);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        // Get payment details
        $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        // Update appointment payment status so the patient can retry
        if ($payment && $payment['appointment_code']) {
            $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'rejected' WHERE appointment_code = ?");
            $stmt->bind_param("s", $payment['appointment_code']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Send email notification
        send_payment_rejected_email($payment['user_id'], $payment, $reason);
    }
    
    return $success;
}

// Send email for approved payment
function send_payment_approved_email($user_id, $payment) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) return;
    
    $subject = "Payment Approved - QuickCare";
    $body = "
        <h2>Payment Approved ✅</h2>
        <p>Dear {$user['name']},</p>
        <p>Your payment has been <strong>APPROVED</strong>.</p>
        <h3>Payment Details:</h3>
        <ul>
            <li><strong>Receipt Number:</strong> {$payment['receipt_number']}</li>
            <li><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</li>
            <li><strong>Appointment Code:</strong> {$payment['appointment_code']}</li>
        </ul>
        <p>You can view and print your receipt from your payment history page.</p>
        <br>
        <p>Thank you for using QuickCare!</p>
    ";
    
    send_email($user['email'], $subject, $body);
}

// Send email for rejected payment
function send_payment_rejected_email($user_id, $payment, $reason) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) return;
    
    $subject = "Payment Requires Attention - QuickCare";
    $body = "
        <h2>Payment Status: Rejected ❌</h2>
        <p>Dear {$user['name']},</p>
        <p>Your payment has been <strong>REJECTED</strong>.</p>
        <p><strong>Reason:</strong> {$reason}</p>
        <h3>Payment Details:</h3>
        <ul>
            <li><strong>Receipt Number:</strong> {$payment['receipt_number']}</li>
            <li><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</li>
        </ul>
        <p>Please make a new payment and upload a clear receipt.</p>
        <br>
        <p><a href='" . absolute_app_url('user/payment.php') . "'>Click here to retry payment</a></p>
    ";
    
    send_email($user['email'], $subject, $body);
}

// Get receipt HTML for printing
function get_receipt_html($payment_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT p.*, u.name as user_name, u.email as user_email
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.payment_id = ?
    ");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$payment) {
        return "<p>Receipt not found</p>";
    }
    
    $html = '
    <div class="receipt" style="max-width: 400px; margin: 0 auto; padding: 20px; font-family: monospace;">
        <div class="receipt-header" style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 48px;">🏥</div>
            <h2>QUICKCARE CLINIC</h2>
            <p>123 Jalan SS2, 47300 Petaling Jaya<br>Tel: 03-1234 5678</p>
            <hr>
            <h3>OFFICIAL RECEIPT</h3>
        </div>
        <div class="receipt-body">
            <table style="width: 100%; border-collapse: collapse;">
                <tr><td style="padding: 6px 0;"><strong>Receipt No:</strong></td><td>' . htmlspecialchars($payment['receipt_number']) . '</td></tr>
                <tr><td style="padding: 6px 0;"><strong>Date:</strong></td><td>' . date('d/m/Y h:i A', strtotime($payment['approved_date'])) . '</td></tr>
                <tr><td style="padding: 6px 0;"><strong>Patient Name:</strong></td><td>' . htmlspecialchars($payment['user_name']) . '</td></tr>
                <tr><td style="padding: 6px 0;"><strong>Payment Method:</strong></td><td>QR Code / Online Banking</td></tr>
                <tr><td style="padding: 6px 0;"><strong>Transaction ID:</strong></td><td>' . htmlspecialchars($payment['transaction_id']) . '</td></tr>
                <tr><td colspan="2"><hr></td></tr>
                <tr><td style="padding: 6px 0;"><strong>Appointment Code:</strong></td><td>' . htmlspecialchars($payment['appointment_code']) . '</td></tr>
                <tr><td colspan="2"><hr></td></tr>
                <tr><td style="padding: 6px 0;"><strong>Amount Paid:</strong></td><td><strong style="font-size: 18px;">RM ' . number_format($payment['amount'], 2) . '</strong></td></tr>
            </table>
        </div>
        <div class="receipt-footer" style="text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px dashed #ccc;">
            <p>Thank you for choosing QuickCare!</p>
            <p style="font-size: 10px;">This is a computer generated receipt. No signature required.</p>
        </div>
    </div>';
    
    return $html;
}

?>
