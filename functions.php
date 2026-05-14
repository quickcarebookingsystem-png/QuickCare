<?php
date_default_timezone_set('Asia/Kuala_Lumpur');

require_once __DIR__ . '/db_connect.php'; 
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$SERVICES = [
    ['icon' => '🩺', 'name' => 'General Check-up', 'price' => 'RM 50', 'desc' => 'Routine health screening and consultation'],
    ['icon' => '🦷', 'name' => 'Dental Care', 'price' => 'RM 80', 'desc' => 'Dental examination, scaling and oral care'],
    ['icon' => '👁', 'name' => 'Eye Examination', 'price' => 'RM 60', 'desc' => 'Vision testing and eye health check'],
    ['icon' => '💉', 'name' => 'Vaccination', 'price' => 'RM 45', 'desc' => 'All approved vaccines available'],
    ['icon' => '🧪', 'name' => 'Blood Test', 'price' => 'RM 50', 'desc' => 'Complete blood count and lab testing'],
    ['icon' => '❤️', 'name' => 'Cardiology', 'price' => 'RM 120', 'desc' => 'Heart health consultation and screening'],
];

$DOCTORS = [
    ['icon' => '👩‍⚕️', 'name' => 'Dr. Sarah Lim', 'spec' => 'General Practice', 'avail' => 'Mon-Fri'],
    ['icon' => '👨‍⚕️', 'name' => 'Dr. Amir Hamzah', 'spec' => 'Dental Specialist', 'avail' => 'Tue-Sat'],
    ['icon' => '👩‍⚕️', 'name' => 'Dr. Priya Menon', 'spec' => 'Eye Care', 'avail' => 'Mon-Thu'],
    ['icon' => '👨‍⚕️', 'name' => 'Dr. Fauzi Rahman', 'spec' => 'Cardiology', 'avail' => 'Wed-Fri'],
];

$APPOINTMENTS = [
    ['id' => 'APT-0145', 'user' => 'Ahmad Razif', 'doctor' => 'Dr. Sarah Lim', 'service' => 'General Check-up', 'date' => '05 May 2026', 'time' => '09:00', 'status' => 'approved', 'amount' => 'RM 52.00'],
    ['id' => 'APT-0144', 'user' => 'Farah Nadia', 'doctor' => 'Dr. Amir Hamzah', 'service' => 'Dental Care', 'date' => '05 May 2026', 'time' => '09:30', 'status' => 'completed', 'amount' => 'RM 82.00'],
    ['id' => 'APT-0143', 'user' => 'Raj Kumar', 'doctor' => 'Dr. Priya Menon', 'service' => 'Eye Examination', 'date' => '05 May 2026', 'time' => '10:00', 'status' => 'pending', 'amount' => 'RM 62.00'],
    ['id' => 'APT-0142', 'user' => 'Lim Wei Xian', 'doctor' => 'Dr. Sarah Lim', 'service' => 'Blood Test', 'date' => '04 May 2026', 'time' => '14:00', 'status' => 'approved', 'amount' => 'RM 52.00'],
    ['id' => 'APT-0141', 'user' => 'Nurul Syafiqah', 'doctor' => 'Dr. Fauzi Rahman', 'service' => 'Cardiology', 'date' => '03 May 2026', 'time' => '11:00', 'status' => 'rejected', 'amount' => 'RM 122.00'],
];

$PAGE_URLS = [
    'user' => [
        'dashboard' => 'user/user_dashboard.php', 'profile' => 'my_profile.php', 'services' => 'user/clinic_services.php',
        'doctors' => 'user/doctor_list.php', 'book' => 'user/book_appointment.php', 'appointments' => 'user/my_appointments.php',
        'payment' => 'user/payment.php',
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
        ['section' => 'Payments', 'items' => [['id' => 'payment', 'icon' => '💳', 'label' => 'Payment']]],
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
    'payment' => 'Payment', 'reports' => 'Reports', 'staff' => 'Manage Staff',
    'schedule' => 'Daily Schedule', 'users' => 'User List',
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
        SELECT MAX(CAST(SUBSTRING(user_id, 2) AS UNSIGNED)) AS max_id
        FROM users
        WHERE role = 'user'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $next_id = ((int)$row['max_id']) + 1;
    $user_id = $prefix . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    $stmt->close();

    $stmt = $conn->prepare(
        "INSERT INTO users (user_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssss", $user_id, $name, $email, $password, $role);
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

    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['id'])) {
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
        "SELECT * FROM users WHERE id = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
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

function app_header($title) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . '</title>';
    echo '<base href="' . e(rtrim(app_base_url(), '/') . '/') . '">';
    echo '<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">';
    // 既然有了 <base> 标签，这里使用相对路径 'style.css' 即可，浏览器会自动拼接
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
        echo '<div class="toast show success" style="position:static;margin-bottom:16px">' . e($_SESSION['QuickCare_message']) . '</div>';
        unset($_SESSION['QuickCare_message']);
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
    global $APPOINTMENTS;
    render_stats($role);
    echo '<div class="dashboard-grid"><div><div class="card mb-20"><div class="card-header"><span class="card-title">Recent Appointments</span><a class="btn btn-sm btn-outline" href="' . e(page_url('appointments', $role)) . '">View All</a></div><div class="card-body"><div class="table-wrap"><table><thead><tr><th>User</th><th>Doctor</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach (array_slice($APPOINTMENTS, 0, 5) as $a) {
        echo '<tr><td>' . e($a['user']) . '</td><td>' . e($a['doctor']) . '</td><td>' . e($a['date']) . '</td><td>' . badge($a['status']) . '</td><td><a class="btn btn-sm btn-outline" href="' . e(action_url('view_appointment', ['id' => $a['id']])) . '">View</a></td></tr>';
    }
    echo '</tbody></table></div></div></div></div><div><div class="card mb-20"><div class="card-header"><span class="card-title">May 2026</span></div><div class="mini-calendar"><div class="cal-grid">';
    foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $d) echo '<div class="cal-day-label">' . $d . '</div>';
    for ($i = 0; $i < 5; $i++) echo '<div class="cal-day other-month"></div>';
    for ($d = 1; $d <= 31; $d++) echo '<div class="cal-day' . ($d === 5 ? ' today' : '') . (in_array($d, [12,14,19,26]) ? ' has-appt' : '') . '">' . $d . '</div>';
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Quick Actions</span></div><div class="card-body"><div class="quick-actions">';
    $actions = [
        'user' => [['Book New Appointment','book'], ['View My Appointments','appointments'], ['Make Payment','payment']],
        'staff' => [['View Daily Schedule','schedule'], ['Manage Appointments','appointments']],
        'admin' => [['Add Staff Member','staff'], ['Manage Doctors','doctors'], ['View Reports','reports']],
    ];
    foreach ($actions[$role] ?? [] as $a) echo '<a class="btn btn-outline w-full" href="' . e(page_url($a[1], $role)) . '">' . e($a[0]) . '</a>';
    echo '</div></div></div></div></div>';
}

function render_sidebar($conn, $role, $page) {
    global $NAVS;
    $user = current_user($conn);
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
    echo '</nav><div class="sidebar-footer"><div class="user-info"><div class="user-avatar">' . 'hh' . '</div><div>';
    echo '<div class="user-name">' . e($user['name']) . '</div><div class="user-email">' . e($user['email']) . '</div></div></div>';
    echo '<a class="btn-signout" href="' . e(action_url('logout')) . '" draggable = "false">🚪 Log Out</a></div></aside>';
}

function render_stats($role) {
    $stats = [
        'user' => [['📅','primary','3','Upcoming Appts',''], ['✅','success','12','Completed','↑ 2 this month'], ['⏳','warning','1','Pending Payment','']],
        'staff' => [['📅','primary','24',"Today's Appointments",''], ['⏳','warning','8','Pending Review',''], ['✅','success','16','Confirmed','↑ 4 vs yesterday'], ['👥','teal','142','Total Users','']],
        'admin' => [['📅','primary','124','Total Appointments','↑ 12% this month'], ['💰','success','RM 6,240','Revenue','↑ 8% this month'], ['👥','teal','98','Active Users',''], ['⏳','warning','14','Pending Approvals','']],
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
    return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

function render_profile($role) {
    global $conn;
    $u = current_user($conn);
    echo '<div class="profile-header"><div class="profile-avatar-lg">👤</div><div><div class="profile-name">' . e($u['name']) . '</div><div class="profile-meta">' . e($u['role'] . ' · ID: ' . $u['user_id']) . '</div></div><button class="btn btn-outline" style="margin-left:auto" onclick="openModal(\'modal-edit-profile\')">✏️ Edit Profile</button></div>';
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Personal Information</span></div><div class="card-body"><div style="display:flex;flex-direction:column;gap:12px">';
    foreach ([['Full Name',$u['name']], ['Email',$u['email']], ['Phone','+60 12-345 6789'], ['Gender','Male'], ['Blood Type','A+']] as $row) echo '<div class="flex-between"><span class="text-muted">' . e($row[0]) . '</span><span class="font-600">' . e($row[1]) . '</span></div><div class="divider"></div>';
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Change Password</span></div><div class="card-body"><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="save_profile"><div class="form-group"><label>Current Password</label><input class="form-control" type="password"></div><div class="form-group"><label>New Password</label><input class="form-control" type="password"></div><div class="form-group"><label>Confirm Password</label><input class="form-control" type="password"></div><button class="btn btn-primary" style="width:auto">Update Password</button></form></div></div></div>';
}

function render_services($role) {
    global $SERVICES;
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search services…"></div>';
    if ($role === 'admin') echo '<button class="btn btn-primary" style="width:auto" onclick="openModal(\'modal-add-service\')">+ Add Service</button>';
    echo '</div><div class="services-grid">';
    foreach ($SERVICES as $s) echo '<div class="service-card"><span class="service-icon">' . $s['icon'] . '</span><div class="service-name">' . e($s['name']) . '</div><div class="service-price">' . e($s['price']) . '</div><div class="service-desc">' . e($s['desc']) . '</div></div>';
    echo '</div>';
}

function render_doctors($role) {
    global $DOCTORS;
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search doctors…"></div>';
    if ($role === 'admin') echo '<button class="btn btn-primary" style="width:auto" onclick="openModal(\'modal-add-doctor\')">+ Add Doctor</button>';
    echo '</div><div class="doctor-grid">';
    foreach ($DOCTORS as $d) {
        echo '<div class="doctor-card"><div class="doctor-avatar">' . $d['icon'] . '</div><div class="doctor-name">' . e($d['name']) . '</div><div class="doctor-spec">' . e($d['spec']) . '</div><div class="doctor-avail">✅ Available ' . e($d['avail']) . '</div>';
        if ($role === 'admin') echo '<div style="margin-top:12px;display:flex;gap:6px;justify-content:center"><button class="btn btn-sm btn-outline" onclick="openModal(\'modal-add-doctor\')">✏️</button><a class="btn btn-sm btn-danger" href="' . e(action_url('delete', ['type' => 'doctor'])) . '">🗑</a></div>';
        echo '</div>';
    }
    echo '</div>';
}

function appointment_actions($role, $a) {
    if ($role === 'user') {
        if ($a['status'] === 'approved') return '<a class="btn btn-sm btn-danger" href="' . e(action_url('cancel_appointment', ['id' => $a['id']])) . '">Cancel</a>';
        if ($a['status'] === 'completed') return '<a class="btn btn-sm btn-outline" href="' . e(page_url('payment', $role)) . '">Pay</a>';
        return '<a class="btn btn-sm btn-outline" href="' . e(action_url('view_appointment', ['id' => $a['id']])) . '">View</a>';
    }
    if ($role === 'admin' && $a['status'] === 'pending') {
        return '<a class="btn btn-sm btn-success" href="' . e(action_url('approve', ['id' => $a['id']])) . '">✓ Approve</a> <a class="btn btn-sm btn-danger" href="' . e(action_url('reject', ['id' => $a['id']])) . '">✗ Reject</a>';
    }
    return '<a class="btn btn-sm btn-teal" href="' . e(action_url('update_status', ['id' => $a['id']])) . '">Update</a>';
}

function render_appointments($role) {
    global $APPOINTMENTS;
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search appointments…"></div><div class="filter-group"><input class="form-control" type="date" style="width:160px"><select class="filter-select"><option>All Status</option><option>Pending</option><option>Approved</option><option>Completed</option><option>Rejected</option></select></div>';
    if ($role === 'admin') echo '<a class="btn btn-outline" href="' . e(action_url('export_report')) . '">⬇ Export</a>';
    echo '</div><div class="card"><div class="card-body" style="padding:0"><div class="table-wrap"><table><thead><tr><th>ID</th><th>User</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    foreach ($APPOINTMENTS as $a) {
        echo '<tr><td>' . e($a['id']) . '</td><td>' . e($a['user']) . '</td><td>' . e($a['doctor']) . '</td><td>' . e($a['service']) . '</td><td>' . e($a['date']) . '</td><td>' . e($a['time']) . '</td><td>' . badge($a['status']) . '</td><td class="flex gap-8">' . appointment_actions($role, $a) . '</td></tr>';
    }
    echo '</tbody></table></div></div></div>';
}

function render_book() {
    global $SERVICES, $DOCTORS;
    echo '<form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="book_appointment"><div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Choose Service</span></div><div class="card-body"><div class="services-grid">';
    foreach ($SERVICES as $s) echo '<label class="service-card"><input type="radio" name="service" value="' . e($s['name']) . '" required> <span class="service-icon">' . $s['icon'] . '</span><div class="service-name">' . e($s['name']) . '</div><div class="service-price">' . e($s['price']) . '</div><div class="service-desc">' . e($s['desc']) . '</div></label>';
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Choose Doctor</span></div><div class="card-body"><div class="doctor-grid">';
    foreach ($DOCTORS as $d) echo '<label class="doctor-card"><input type="radio" name="doctor" value="' . e($d['name']) . '" required><div class="doctor-avatar">' . $d['icon'] . '</div><div class="doctor-name">' . e($d['name']) . '</div><div class="doctor-spec">' . e($d['spec']) . '</div><div class="doctor-avail">Available ' . e($d['avail']) . '</div></label>';
    echo '</div></div></div></div><div class="card mt-20"><div class="card-header"><span class="card-title">Date, Time & Notes</span></div><div class="card-body"><div class="grid-2"><div class="form-group"><label>Date</label><input class="form-control" type="date" name="date" required></div><div class="form-group"><label>Time</label><select class="form-control" name="time" required><option>09:00</option><option>09:30</option><option>10:00</option><option>10:30</option><option>11:00</option><option>14:00</option></select></div></div><div class="form-group"><label>Symptoms / Notes</label><textarea class="form-control" rows="5" name="notes" placeholder="e.g. Fever for 3 days, headache…"></textarea></div><button class="btn btn-primary" style="width:auto">Confirm Appointment</button></div></div></form>';
}

function render_payment($role) {
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">' . ($role === 'user' ? 'Make Payment' : 'Manage Payments') . '</span></div><div class="card-body"><form method="post" action="action.php"><input type="hidden" name="action" value="process_payment"><div class="form-group"><label>Select Appointment</label><select class="form-control"><option>APT-0144 - Dental Care - RM 82.00</option><option>APT-0145 - General Check-up - RM 52.00</option></select></div><h3 class="mb-16">Payment Method</h3><div class="payment-methods"><label class="payment-method selected"><input type="radio" name="method" value="card" checked><span class="pm-icon">💳</span>Credit Card</label><label class="payment-method"><input type="radio" name="method" value="bank"><span class="pm-icon">🏦</span>Online Banking</label><label class="payment-method"><input type="radio" name="method" value="wallet"><span class="pm-icon">📱</span>e-Wallet</label></div><div class="form-group"><label>Card Number</label><input class="form-control" placeholder="1234 5678 9012 3456"></div><button class="btn btn-primary">Pay RM 52.00</button></form></div></div><div class="card"><div class="card-header"><span class="card-title">Payment History</span></div><div class="card-body" style="padding:0"><table><thead><tr><th>Invoice</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody><tr><td>INV-0042</td><td>28 Apr</td><td>RM 52.00</td><td>' . badge('approved') . '</td><td><a class="btn btn-sm btn-outline" href="' . e(action_url('receipt')) . '">Receipt</a></td></tr><tr><td>INV-0038</td><td>15 Apr</td><td>RM 35.00</td><td>' . badge('approved') . '</td><td><a class="btn btn-sm btn-outline" href="' . e(action_url('receipt')) . '">Receipt</a></td></tr><tr><td>INV-0031</td><td>2 Apr</td><td>RM 80.00</td><td>' . badge('pending') . '</td><td><a class="btn btn-sm btn-primary" href="' . e(action_url('process_payment')) . '">Pay Now</a></td></tr></tbody></table></div></div></div>';
}

function render_reports() {
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Appointment Report</span><a class="btn btn-sm btn-outline" href="' . e(action_url('export_report')) . '">⬇ Export</a></div><div class="card-body"><div class="report-summary"><div class="report-item"><div class="val">124</div><div class="lbl">Total</div></div><div class="report-item"><div class="val">98</div><div class="lbl">Completed</div></div><div class="report-item"><div class="val">14</div><div class="lbl">Pending</div></div><div class="report-item"><div class="val">12</div><div class="lbl">Cancelled</div></div></div><table><tr><th>Month</th><th>Total</th><th>Completed</th><th>Rate</th></tr><tr><td>May 2026</td><td>32</td><td>18</td><td>' . badge('approved') . '</td></tr><tr><td>Apr 2026</td><td>44</td><td>41</td><td>' . badge('approved') . '</td></tr></table></div></div><div class="card"><div class="card-header"><span class="card-title">Payment Report</span><a class="btn btn-sm btn-outline" href="' . e(action_url('export_report')) . '">⬇ Export</a></div><div class="card-body"><div class="report-summary"><div class="report-item"><div class="val">RM 6,240</div><div class="lbl">Total Revenue</div></div><div class="report-item"><div class="val">98</div><div class="lbl">Paid Invoices</div></div><div class="report-item"><div class="val">RM 260</div><div class="lbl">Pending</div></div></div></div></div></div>';
}

function render_staff() {
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search staff…"></div><button class="btn btn-primary" style="width:auto" onclick="openModal(\'modal-add-staff\')">+ Add Staff</button></div><div class="card"><div class="card-body" style="padding:0"><table><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    foreach ([['Nurul Ain binti Razak','Staff','nurul@QuickCare.my','active'], ['Hafizi bin Ahmad','Staff','hafizi@QuickCare.my','active'], ['Siti Rahimah','Staff','siti@QuickCare.my','pending']] as $s) {
        echo '<tr><td>' . e($s[0]) . '</td><td>' . e($s[1]) . '</td><td>' . e($s[2]) . '</td><td>' . badge($s[3]) . '</td><td class="flex gap-8"><button class="btn btn-sm btn-outline" onclick="openModal(\'modal-add-staff\')">✏️ Edit</button><a class="btn btn-sm btn-danger" href="' . e(action_url('delete', ['type' => 'staff'])) . '">🗑 Delete</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function render_schedule() {
    echo '<div class="toolbar"><input class="form-control" type="date" style="width:200px" value="2026-05-05"><select class="filter-select"><option>All Doctors</option><option>Dr. Sarah Lim</option><option>Dr. Amir Hamzah</option><option>Dr. Priya Menon</option></select></div><div class="card"><div class="card-header"><span class="card-title">Today\'s Schedule — 5 May 2026</span></div><div class="card-body" style="padding:0"><table><thead><tr><th>Time</th><th>User</th><th>Doctor</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    foreach ([['09:00','Ahmad Razif','Dr. Sarah Lim','General Check-up','approved'], ['09:30','Farah Nadia','Dr. Amir Hamzah','Dental','completed'], ['10:00','Raj Kumar','Dr. Priya Menon','Eye Check','pending'], ['10:30','Lim Wei Xian','Dr. Sarah Lim','General Check-up','approved']] as $row) {
        echo '<tr><td>' . e($row[0]) . '</td><td>' . e($row[1]) . '</td><td>' . e($row[2]) . '</td><td>' . e($row[3]) . '</td><td>' . badge($row[4]) . '</td><td><a class="btn btn-sm btn-teal" href="' . e(action_url('update_status')) . '">Update</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function render_users() {
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search users…"></div><select class="filter-select"><option>All Status</option><option>Active</option><option>Inactive</option></select></div><div class="card"><div class="card-header"><span class="card-title">User List</span></div><div class="card-body" style="padding:0"><table><thead><tr><th>User ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Last Visit</th><th>Status</th></tr></thead><tbody>';
    foreach ([['PT-2024-0042','Ahmad Razif bin Hassan','ahmad@email.com','+60 12-345 6789','28 Apr 2026','active'], ['PT-2024-0043','Farah Nadia','farah@email.com','+60 13-220 7811','05 May 2026','active'], ['PT-2024-0044','Raj Kumar','raj@email.com','+60 16-442 9021','05 May 2026','active'], ['PT-2024-0045','Lim Wei Xian','lim@email.com','+60 17-555 1900','04 May 2026','pending']] as $p) {
        echo '<tr><td>' . e($p[0]) . '</td><td>' . e($p[1]) . '</td><td>' . e($p[2]) . '</td><td>' . e($p[3]) . '</td><td>' . e($p[4]) . '</td><td>' . badge($p[5]) . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
}

function render_modals() {
    echo <<<'HTML'
<div class="modal-overlay" id="modal-add-staff"><div class="modal"><div class="modal-header"><span class="modal-title">Add Staff Member</span><button class="modal-close" onclick="closeModal('modal-add-staff')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_staff"><div class="modal-body"><div class="form-group"><label>Full Name</label><input class="form-control" name="name" placeholder="e.g. Nurul Ain binti Razak"></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" placeholder="staff@QuickCare.my"></div><div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div><div class="form-group"><label>Role</label><select class="form-control" name="role"><option>Staff</option><option>Admin</option></select></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-staff')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<div class="modal-overlay" id="modal-add-doctor"><div class="modal"><div class="modal-header"><span class="modal-title">Add Doctor</span><button class="modal-close" onclick="closeModal('modal-add-doctor')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_doctor"><div class="modal-body"><div class="form-group"><label>Full Name</label><input class="form-control" name="name" placeholder="e.g. Dr. Ahmad Fauzi"></div><div class="form-group"><label>Specialization</label><input class="form-control" name="specialization"></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email"></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-doctor')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<div class="modal-overlay" id="modal-add-service"><div class="modal"><div class="modal-header"><span class="modal-title">Add Service</span><button class="modal-close" onclick="closeModal('modal-add-service')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_service"><div class="modal-body"><div class="form-group"><label>Service Name</label><input class="form-control" name="name"></div><div class="form-group"><label>Fee (RM)</label><input class="form-control" type="number" name="fee"></div><div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="3"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-service')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<div class="modal-overlay" id="modal-edit-profile"><div class="modal"><div class="modal-header"><span class="modal-title">Edit Profile</span><button class="modal-close" onclick="closeModal('modal-edit-profile')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_profile"><div class="modal-body"><div class="form-group"><label>Full Name</label><input class="form-control" name="name" value="Ahmad Razif bin Hassan"></div><div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="ahmad@email.com"></div><div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="+60 12-345 6789"></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-edit-profile')">Cancel</button><button class="btn btn-primary" style="width:auto">Save Changes</button></div></form></div></div>
HTML;
}

?>
