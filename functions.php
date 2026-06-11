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
        'availability' => 'admin/manage_time_slots.php', 'payment' => 'admin/manage_payments.php', 'reports' => 'admin/reports.php',
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
        ['section' => 'Availability', 'items' => [['id' => 'availability', 'icon' => '&#128274;', 'label' => 'Manage Time Slots']]],
        ['section' => 'Payments & Reports', 'items' => [['id' => 'payment', 'icon' => '💳', 'label' => 'Manage Payments'], ['id' => 'reports', 'icon' => '📈', 'label' => 'Reports']]],
    ],
];

$PAGE_TITLES = [
    'dashboard' => 'Dashboard', 'profile' => 'My Profile', 'services' => 'Clinic Services',
    'doctors' => 'Doctor List', 'book' => 'Book Appointment', 'appointments' => 'Appointments',
    'payment' => 'Payment', 'payment_history' => 'Payment History', 'reports' => 'Reports', 
    'staff' => 'Manage Staff', 'schedule' => 'Daily Schedule', 'users' => 'User List', 'availability' => 'Manage Time Slots',
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

function get_service_icon($serviceName) {
    $name = strtolower(trim((string)$serviceName));

    // 自动关键词映射逻辑 (不需要数据库)
    if (str_contains($name, 'dental') || str_contains($name, 'tooth') || str_contains($name, 'teeth')) return '🦷';
    if (str_contains($name, 'eye') || str_contains($name, 'vision') || str_contains($name, 'optomet')) return '👁️';
    if (str_contains($name, 'vaccin') || str_contains($name, 'injection') || str_contains($name, 'flu')) return '💉';
    if (str_contains($name, 'blood')) return '🩸';
    if (str_contains($name, 'heart') || str_contains($name, 'cardio')) return '🫀';
    if (str_contains($name, 'child') || str_contains($name, 'pediat')) return '👶';
    if (str_contains($name, 'physio') || str_contains($name, 'therapy') || str_contains($name, 'rehab')) return '🧘';
    if (str_contains($name, 'check') || str_contains($name, 'consult') || str_contains($name, 'general')) return '🩺';
    if (str_contains($name, 'emergency') || str_contains($name, 'ambu')) return '🚑';
    if (str_contains($name, 'pharmacy') || str_contains($name, 'medicin') || str_contains($name, 'pill')) return '💊';
    if (str_contains($name, 'lab') || str_contains($name, 'test')) return '🧪';
    if (str_contains($name, 'bone') || str_contains($name, 'ortho')) return '🦴';

    return '🏥'; // 默认图标
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

function user_avatar_html($user, $class = 'user-avatar') {
    $image = trim((string)($user['profile_image'] ?? ''));
    if ($image !== '') {
        return '<div class="' . e($class) . ' profile-avatar-image"><img src="' . e(app_url('uploads/avatars/' . rawurlencode($image))) . '" alt="Profile avatar"></div>';
    }

    return '<div class="' . e($class) . '">' . e(name_avatar($user['name'] ?? '')) . '</div>';
}

function ensure_profile_image_column($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL");
}

function ensure_doctor_image_column($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM doctors LIKE 'doctor_image'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE doctors ADD COLUMN doctor_image VARCHAR(255) NULL");
}

function ensure_doctor_description_column($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM doctors LIKE 'doctor_description'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE doctors ADD COLUMN doctor_description TEXT NULL");
}

function ensure_doctor_schedule_break_columns($conn) {
    $startCheck = $conn->query("SHOW COLUMNS FROM doctor_schedule LIKE 'break_start_time'");
    if (!$startCheck || $startCheck->num_rows === 0) {
        $conn->query("ALTER TABLE doctor_schedule ADD COLUMN break_start_time TIME NULL AFTER end_time");
    }

    $endCheck = $conn->query("SHOW COLUMNS FROM doctor_schedule LIKE 'break_end_time'");
    if (!$endCheck || $endCheck->num_rows === 0) {
        $conn->query("ALTER TABLE doctor_schedule ADD COLUMN break_end_time TIME NULL AFTER break_start_time");
    }

    return true;
}

function ensure_doctor_services_table($conn) {
    return (bool) $conn->query("
        CREATE TABLE IF NOT EXISTS doctor_services (
            doctor_id INT NOT NULL,
            service_id INT NOT NULL,
            PRIMARY KEY (doctor_id, service_id)
        )
    ");
}

function ensure_doctor_time_locks_table($conn) {
    return (bool) $conn->query("
        CREATE TABLE IF NOT EXISTS doctor_time_locks (
            lock_id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            lock_date DATE NOT NULL,
            start_time TIME NULL,
            end_time TIME NULL,
            reason VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_doctor_lock_date (doctor_id, lock_date),
            CONSTRAINT fk_doctor_time_locks_doctor
                FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
                ON DELETE CASCADE
        )
    ");
}

function doctor_time_slot_is_locked($conn, $doctorName, $date, $time) {
    ensure_doctor_time_locks_table($conn);
    $appointmentTime = strlen((string)$time) === 5 ? $time . ':00' : $time;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM doctor_time_locks dtl
        INNER JOIN doctors d ON d.doctor_id = dtl.doctor_id
        WHERE d.doctor_name = ?
          AND dtl.lock_date = ?
          AND (
              dtl.start_time IS NULL
              OR dtl.end_time IS NULL
              OR (dtl.start_time < ADDTIME(?, '00:30:00') AND dtl.end_time > ?)
          )
    ");
    $stmt->bind_param("ssss", $doctorName, $date, $appointmentTime, $appointmentTime);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0) > 0;
}

function validate_doctor_time_lock_schedule($conn, $doctorId, $lockDate, $startTime = null, $endTime = null) {
    ensure_doctor_schedule_break_columns($conn);
    $dateObj = DateTime::createFromFormat('Y-m-d', (string)$lockDate);
    if (!$dateObj) {
        return 'Please choose a valid date.';
    }

    $availableDay = $dateObj->format('D');
    $stmt = $conn->prepare("
        SELECT start_time, end_time, break_start_time, break_end_time
        FROM doctor_schedule
        WHERE doctor_id = ?
          AND available_day = ?
        ORDER BY start_time ASC
    ");
    $stmt->bind_param("is", $doctorId, $availableDay);
    $stmt->execute();
    $scheduleRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($scheduleRows)) {
        return 'Selected doctor is not working on this day.';
    }

    if ($startTime === null || $endTime === null) {
        return '';
    }

    foreach ($scheduleRows as $row) {
        $workStart = (string)($row['start_time'] ?? '');
        $workEnd = (string)($row['end_time'] ?? '');
        if ($workStart === '' || $workEnd === '' || $workStart > $startTime || $workEnd < $endTime) {
            continue;
        }

        $breakStart = $row['break_start_time'] ?? null;
        $breakEnd = $row['break_end_time'] ?? null;
        $insideBreakOnly = $breakStart && $breakEnd && $breakStart < $breakEnd && $breakStart <= $startTime && $breakEnd >= $endTime;
        if (!$insideBreakOnly) {
            return '';
        }
    }

    return 'Selected time is outside this doctor\'s working hours.';
}

function ensure_service_overview_column($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM services LIKE 'service_overview'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE services ADD COLUMN service_overview TEXT NULL");
}

function doctor_avatar_html($doctor, $class = 'doctor-avatar') {
    $image = trim((string)($doctor['doctor_image'] ?? ''));
    if ($image !== '') {
        return '<div class="' . e($class) . ' doctor-avatar-image"><img src="' . e(app_url('uploads/doctors/' . rawurlencode($image))) . '" alt="' . e($doctor['doctor_name'] ?? 'Doctor') . '"></div>';
    }

    return '<div class="' . e($class) . '">' . e(name_avatar($doctor['doctor_name'] ?? 'Doctor')) . '</div>';
}

function appointment_cancel_reason($notes) {
    $notes = (string) $notes;
    if (preg_match('/Cancellation reason:\s*(.+)$/is', $notes, $matches)) {
        return trim($matches[1]);
    }

    return '';
}

function appointment_booking_notes($notes) {
    $notes = (string) $notes;
    $parts = preg_split('/\R*\s*Cancellation reason:\s*/i', $notes, 2);
    return trim($parts[0] ?? '');
}

function appointment_payment_notes($appointment) {
    $paymentNote = payment_note_display(
        $appointment['latest_payment_status'] ?? $appointment['payment_status'] ?? '',
        $appointment['latest_payment_remarks'] ?? ''
    );

    return trim((string)($paymentNote['text'] ?? ''));
}

function appointment_refund_receipt_file($appointment) {
    return payment_refund_receipt_file($appointment['latest_payment_remarks'] ?? '');
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
    ensure_service_overview_column($conn);
    return fetch_all_assoc(
        $conn,
        "SELECT service_id, service_name, service_price, service_description, service_overview
         FROM services
         ORDER BY service_id ASC"
    );
}

function service_required_specialist_keywords($serviceName, $serviceDescription = '') {
    $text = strtolower((string) $serviceName . ' ' . (string) $serviceDescription);

    $aliases = [
        'dental' => ['dental', 'dentist', 'tooth', 'teeth', 'oral'],
        'eye' => ['eye', 'ophthalmology', 'ophthalmologist', 'optometry', 'optometrist', 'vision', 'sight'],
        'cardio' => ['cardio', 'cardiology', 'cardiologist', 'heart', 'cardiac'],
        'physio' => ['physio', 'physiotherapy', 'physiotherapist', 'physical', 'rehab', 'rehabilitation'],
        'pediatric' => ['pediatric', 'paediatric', 'pediatrician', 'paediatrician', 'child', 'children', 'kids'],
        'dermatology' => ['dermatology', 'dermatologist', 'skin', 'acne', 'rash'],
        'orthopedic' => ['orthopedic', 'orthopaedic', 'orthopedist', 'bone', 'joint', 'fracture'],
        'neurology' => ['neurology', 'neurologist', 'brain', 'nerve', 'migraine'],
        'ent' => ['ent', 'ear', 'nose', 'throat'],
        'gynecology' => ['gynecology', 'gynaecology', 'gynecologist', 'gynaecologist', 'women', 'pregnancy'],
    ];

    $keywords = [];
    foreach ($aliases as $canonical => $words) {
        foreach ($words as $word) {
            if (str_contains($text, $word)) {
                $keywords = array_merge($keywords, $words, [$canonical]);
                break;
            }
        }
    }

    if (empty($keywords)) {
        foreach (['blood test', 'blood screening', 'vaccination', 'vaccine', 'general check-up', 'general checkup', 'health screening'] as $generalService) {
            if (str_contains($text, $generalService)) {
                return [];
            }
        }
    }

    if (empty($keywords)) {
        $stopWords = [
            'service', 'services', 'care', 'clinic', 'clinical', 'consultation', 'consult',
            'check', 'checkup', 'examination', 'exam', 'screening', 'test', 'treatment',
            'health', 'medical', 'routine', 'complete', 'professional', 'general',
            'available', 'appointment', 'therapy'
        ];
        preg_match_all('/[a-z0-9]+/', $text, $matches);
        foreach ($matches[0] ?? [] as $word) {
            if (strlen($word) < 4 || in_array($word, $stopWords, true)) {
                continue;
            }
            $keywords[] = $word;
        }
    }

    return array_values(array_unique($keywords));
}

function doctor_can_provide_service($serviceName, $doctorSpecialist, $serviceDescription = '') {
    $requiredKeywords = service_required_specialist_keywords($serviceName, $serviceDescription);
    if (empty($requiredKeywords)) {
        return true;
    }

    $specialist = strtolower((string) $doctorSpecialist);
    foreach ($requiredKeywords as $keyword) {
        if (str_contains($specialist, $keyword)) {
            return true;
        }
    }

    return false;
}

function service_default_overview($serviceName, $fallback = '') {
    $name = strtolower((string) $serviceName);

    if (str_contains($name, 'general check-up')) {
        return "Our comprehensive General Check-up is designed for total health awareness and proactive wellness management. This service includes a full physical examination, vital signs monitoring (blood pressure, heart rate, BMI), and a personalized consultation with our experienced medical practitioners. We focus on early detection of potential health risks and chronic conditions, providing you with professional guidance on maintaining a healthy lifestyle through preventive care tailored to your specific age, gender, and medical history. Regular check-ups are the cornerstone of long-term health, helping you stay ahead of any issues before they become serious.";
    }

    if (str_contains($name, 'dental care')) {
        return "Maintain a bright, confident, and healthy smile with our professional Dental Care services. This comprehensive oral health package covers routine clinical examinations, professional scaling and polishing to remove stubborn plaque and tartar, and detailed screening for gum disease, cavities, or other oral issues. Our dental experts utilize state-of-the-art equipment and gentle techniques to ensure your comfort while keeping your oral hygiene at its peak. Beyond treatment, we provide personalized advice on effective brushing, flossing, and long-term dental health maintenance to prevent future complications and ensure your smile lasts a lifetime.";
    }

    if (str_contains($name, 'eye examination')) {
        return "Vision is vital to your daily quality of life. Our professional Eye Examination service provides a thorough assessment of your visual acuity and overall ocular health using specialized diagnostic equipment. We screen for common vision problems such as refractive errors (nearsightedness, farsightedness, astigmatism) as well as more serious conditions like glaucoma, cataracts, and diabetic retinopathy. Whether you require a new prescription for corrective lenses or a routine preventative health check for your eyes, our specialists ensure precise testing and professional care. We prioritize preserving your sight and helping you see the world clearly at every stage of life.";
    }

    if (str_contains($name, 'vaccination')) {
        return "Protect yourself and your loved ones from preventable diseases with our professional Vaccination services. We offer a wide range of essential immunizations, including seasonal flu shots, travel vaccines for international trips, and standard boosters for adults and children. Our clinical team strictly adheres to national health guidelines for vaccine administration and temperature-controlled storage, ensuring maximum efficacy and safety. We provide a clean, safe environment for your injections and provide accurate, detailed documentation for your permanent medical immunization records. Stay protected and contribute to community health by keeping your vaccines up to date with QuickCare.";
    }

    if (str_contains($name, 'blood test')) {
        return "Gain deep, data-driven insights into your internal health with our diagnostic Blood Test services. We provide accurate laboratory analysis for a wide array of critical health markers, including full blood counts, lipid profiles (cholesterol/triglycerides), blood glucose levels for diabetes screening, and specialized kidney or liver function tests. Our collection process is quick, professional, and designed to be as painless as possible. Once processed, the results are interpreted by our medical staff, who will walk you through the findings to help you monitor existing conditions or detect silent health issues early. Regular blood screening is an essential tool for effective health management.";
    }

    if (str_contains($name, 'cardiology')) {
        return "Our specialized Cardiology screening focuses on the most important muscle in your body-your heart. This comprehensive service includes thorough cardiovascular risk assessments, blood pressure management, and in-depth consultations regarding your heart health history and lifestyle. We utilize modern diagnostic tools to evaluate cardiac function and identify potential issues such as arrhythmias, hypertension, or coronary artery disease. By catching early warning signs of cardiovascular stress, we help you take proactive, life-saving steps toward a heart-healthy future. Our specialists provide you with a clear roadmap for heart health, including diet and exercise recommendations tailored to your cardiac profile.";
    }

    return (string) $fallback;
}

function get_doctors($conn) {
    ensure_doctor_image_column($conn);
    ensure_doctor_description_column($conn);
    ensure_doctor_schedule_break_columns($conn);
    ensure_doctor_services_table($conn);
    return fetch_all_assoc(
        $conn,
        "SELECT d.doctor_id, d.doctor_image, d.doctor_name, d.doctor_specialist, d.doctor_description,
                GROUP_CONCAT(DISTINCT ds.available_day ORDER BY FIELD(ds.available_day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') SEPARATOR ', ') AS available_days,
                GROUP_CONCAT(DISTINCT dsvc.service_id ORDER BY dsvc.service_id ASC SEPARATOR ',') AS service_ids,
                GROUP_CONCAT(DISTINCT
                    CONCAT(
                        ds.available_day, '|',
                        TIME_FORMAT(ds.start_time, '%H:%i'), '|',
                        TIME_FORMAT(ds.end_time, '%H:%i'), '|',
                        COALESCE(TIME_FORMAT(ds.break_start_time, '%H:%i'), ''), '|',
                        COALESCE(TIME_FORMAT(ds.break_end_time, '%H:%i'), '')
                    )
                    ORDER BY FIELD(ds.available_day, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'), ds.start_time ASC
                    SEPARATOR ';;'
                ) AS schedule_data
         FROM doctors d
         LEFT JOIN doctor_schedule ds ON ds.doctor_id = d.doctor_id
         LEFT JOIN doctor_services dsvc ON dsvc.doctor_id = d.doctor_id
         GROUP BY d.doctor_id, d.doctor_image, d.doctor_name, d.doctor_specialist, d.doctor_description
         ORDER BY d.doctor_id ASC"
    );
}

function doctor_default_description($specialization) {
    $spec = strtolower((string)$specialization);

    if (str_contains($spec, 'cardiology')) {
        return "Specializes in diagnosing and treating diseases of the cardiovascular system. This includes managing heart conditions such as coronary artery disease, heart failure, and heart rhythm disorders through advanced diagnostic tools and therapeutic interventions.";
    }

    if (str_contains($spec, 'dental')) {
        return "Expert in oral health care, focusing on the diagnosis, prevention, and treatment of conditions affecting the teeth, gums, and mouth. Provides comprehensive services ranging from preventive care and restorative treatments to complex dental surgeries and cosmetic procedures.";
    }

    if (str_contains($spec, 'eye') || str_contains($spec, 'ophthalmology')) {
        return "Dedicated to comprehensive eye health and vision care. Expertise includes conducting detailed eye examinations, diagnosing ocular diseases, and performing specialized treatments or surgeries to preserve and enhance patient eyesight.";
    }

    return "Highly qualified medical professional dedicated to providing expert care and specialized treatment within their field of expertise.";
}

function get_appointments($conn, $role = null, $appointmentDate = null, $limit = null, $orderByCreatedAt = false) {
    ensure_payment_method_column($conn);
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
                         AND p.payment_status IN ('paid', 'approved', 'refund_requested', 'refund_rejected')
                       ORDER BY p.approved_date DESC, p.payment_date DESC, p.payment_id DESC
                       LIMIT 1
                   ) AS receipt_payment_id,
                   (
                       SELECT p.payment_status
                       FROM payments p
                       WHERE p.appointment_code = appointments.appointment_code
                       ORDER BY p.payment_date DESC, p.payment_id DESC
                       LIMIT 1
                   ) AS latest_payment_status,
                   (
                       SELECT p.remarks
                       FROM payments p
                       WHERE p.appointment_code = appointments.appointment_code
                       ORDER BY p.payment_date DESC, p.payment_id DESC
                       LIMIT 1
                   ) AS latest_payment_remarks,
                   (
                       SELECT p.payment_method
                       FROM payments p
                       WHERE p.appointment_code = appointments.appointment_code
                       ORDER BY p.payment_date DESC, p.payment_id DESC
                       LIMIT 1
                   ) AS latest_payment_method
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
    $appointments = fetch_all_assoc($conn, $sql, $types, $params);
    foreach ($appointments as &$appointment) {
        if (($appointment['payment_status'] ?? '') === 'failed') {
            $appointment['payment_status'] = 'pending';
        }
        $latestPaymentStatus = trim((string)($appointment['latest_payment_status'] ?? ''));
        if ($latestPaymentStatus !== '' && $latestPaymentStatus !== 'failed') {
            $appointment['payment_status'] = $latestPaymentStatus;
        }
    }
    unset($appointment);

    return $appointments;
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
    $styleVersion = @filemtime(__DIR__ . '/style.css') ?: time();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . '</title>';
    echo '<base href="' . e(rtrim(app_base_url(), '/') . '/') . '">';
    echo '<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="style.css?v=' . e($styleVersion) . '"></head>';
    if (isset($_SESSION['message'])) {
        echo "<script>alert('" . $_SESSION['message'] . "');</script>";
        unset($_SESSION['message']);
    }
}

function render_notification($placement = 'toast') {
    if (empty($_SESSION['QuickCare_message'])) {
        return;
    }

    $messageType = $_SESSION['QuickCare_message_type'] ?? 'success';
    if ($placement === 'auth') {
        echo '<div class="auth-notification ' . e($messageType) . '">' . e($_SESSION['QuickCare_message']) . '</div>';
    } else {
        echo '<div class="toast show ' . e($messageType) . '" id="pageNotification">' . e($_SESSION['QuickCare_message']) . '</div>';
        echo '<script>
        setTimeout(function () {
            const notification = document.getElementById("pageNotification");
            if (!notification) return;
            notification.classList.remove("show");
        }, 5000);
        </script>';
    }
    unset($_SESSION['QuickCare_message']);
    unset($_SESSION['QuickCare_message_type']);
}

function app_start($role, $page, $title = null) {
    global $PAGE_TITLES, $conn;
    ensure_failed_payment_status($conn);
    $_SESSION['QuickCare_role'] = $role;
    $title = $title ?: ($PAGE_TITLES[$page] ?? 'Dashboard');
    echo '<body><div id="app" class="view active">';
    render_sidebar($conn, $role, $page);
    echo '<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>';
    echo '<div class="main-content"><div class="topbar"><button class="sidebar-toggle" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false"><span></span><span></span><span></span></button><span class="topbar-title">' . e($title) . '</span><div class="topbar-actions">';
    echo '<span class="text-muted text-sm">' . date('l, F j, Y') . '</span></div></div><div class="page-content">';
    if (!empty($_SESSION['QuickCare_message'])) {
        $messageType = $_SESSION['QuickCare_message_type'] ?? 'success';
        echo '<div class="toast flash-message show ' . e($messageType) . '">' . e($_SESSION['QuickCare_message']) . '</div>';
        unset($_SESSION['QuickCare_message']);
        unset($_SESSION['QuickCare_message_type']);
    }
}

function render_payment_history_scripts() {
    echo <<<'HTML'
<script>
async function printReceipt(paymentId) {
    const response = await fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_receipt&payment_id=${paymentId}`
    });
    
    const data = await response.json();
    
    if (data.success) {
        document.getElementById('receiptContent').innerHTML = data.html;
        document.getElementById('receiptModal').style.display = 'flex';
    } else {
        showPaymentHistoryNotice('Error loading receipt.', 'error');
    }
}

function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

function viewPaymentProof(receiptImage, title = 'Payment Proof') {
    const modal = document.getElementById('proofModal');
    const content = document.getElementById('proofContent');
    const modalTitle = document.querySelector('#proofModal .modal-title');
    if (!modal || !content) return;
    if (modalTitle) modalTitle.textContent = title;

    if (!receiptImage) {
        content.innerHTML = '<p class="text-muted">No proof uploaded.</p>';
        modal.style.display = 'flex';
        return;
    }

    const proofUrl = '../uploads/receipts/' + encodeURIComponent(receiptImage);
    const extension = receiptImage.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        content.innerHTML = `<img src="${proofUrl}" alt="${title}">`;
    } else {
        content.innerHTML = `<div class="file-open-fallback"><p>This payment proof file cannot be previewed here.</p><a class="btn btn-outline" target="_blank" rel="noopener" href="${proofUrl}">Open File</a></div>`;
    }
    modal.style.display = 'flex';
}

function closeProofModal() {
    document.getElementById('proofModal').style.display = 'none';
}

let refundRequestPaymentId = null;

function showRefundRequestModal(paymentId) {
    refundRequestPaymentId = paymentId;
    document.getElementById('refundRequestPaymentId').value = paymentId;
    document.getElementById('refundRequestReason').value = '';
    document.getElementById('refundRequestModal').style.display = 'flex';
}

function closeRefundRequestModal() {
    document.getElementById('refundRequestModal').style.display = 'none';
    refundRequestPaymentId = null;
}

function showPaymentHistoryNotice(message, type = 'success', reload = false) {
    const anchor = document.querySelector('.history-container') || document.body;
    document.querySelectorAll('.payment-history-flash-message').forEach(messageBox => messageBox.remove());

    const notice = document.createElement('div');
    notice.className = `toast flash-message show ${type} payment-history-flash-message`;
    notice.textContent = message;
    anchor.insertBefore(notice, anchor.firstChild);
    notice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

    setTimeout(() => {
        notice.classList.add('hiding');
        setTimeout(() => {
            notice.remove();
            if (reload) {
                location.reload();
            }
        }, 350);
    }, 5000);
}

async function confirmRefundRequest() {
    const reason = document.getElementById('refundRequestReason').value;
    if (!reason.trim()) {
        showPaymentHistoryNotice('Please provide a reason for refund request.', 'error');
        document.getElementById('refundRequestReason').focus();
        return;
    }

    const response = await fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=request_refund&payment_id=${refundRequestPaymentId}&reason=${encodeURIComponent(reason)}`
    });

    const data = await response.json();
    if (data.success) {
        closeRefundRequestModal();
        showPaymentHistoryNotice('Refund request submitted. Please wait for admin approval.', 'success', true);
    } else {
        showPaymentHistoryNotice('Error: ' + data.message, 'error');
    }
}
</script>
HTML;
}

function app_end() {
    $scriptVersion = @filemtime(__DIR__ . '/ui.js') ?: time();
    render_modals();
    $customerServiceButton = '';
    if (($_SESSION['QuickCare_role'] ?? '') === 'user') {
        $customerServiceButton = '
    <a class="portal-customer-service-btn" href="https://wa.me/601110807180" target="_blank" rel="noopener" aria-label="Chat with customer service on WhatsApp" title="Customer Service WhatsApp" draggable="false">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M12 3a8 8 0 0 0-8 8v3a3 3 0 0 0 3 3h1v-6H6a6 6 0 0 1 12 0h-2v6h1a3 3 0 0 0 3-3v-3a8 8 0 0 0-8-8Z"/>
        <path d="M9 18h2.2c.3.9 1.1 1.5 2.1 1.5H15a1 1 0 1 0 0-2h-1.7a.5.5 0 0 1-.5-.5v-.2H9V18Z"/>
      </svg>
    </a>';
    }

    echo '
    </div></div></div>
    ' . $customerServiceButton . '
    <div class="toast" id="toast"></div>

    <script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".flash-message").forEach(function (message) {
            window.setTimeout(function () {
                message.classList.add("hiding");
                window.setTimeout(function () {
                    message.remove();
                }, 350);
            }, 5000);
        });
    });
    </script>

    <script src="ui.js?v=' . e($scriptVersion) . '"></script>
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
            $refundReceipt = appointment_refund_receipt_file($a);
            $refundProofLink = $refundReceipt !== '' ? app_url('uploads/receipts/' . rawurlencode($refundReceipt)) : '';
            $refundProofExt = strtolower(pathinfo($refundReceipt, PATHINFO_EXTENSION));
            $payUrl = page_url('payment', $role) . '?appointment=' . urlencode($a['appointment_code']);
            $actionData = '';
            if ($role === 'user') {
                $actionData = '" data-pay-url="' . e($payUrl) . '" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-receipt-id="' . e($receiptPaymentId) . '" data-refund-proof-url="' . e($refundProofLink) . '" data-refund-proof-ext="' . e($refundProofExt);
            } elseif (in_array($role, ['admin', 'staff'], true)) {
                $canComplete = in_array($a['appointment_status'] ?? '', ['confirm', 'confirmed'], true);
                if ($canComplete) {
                    $actionData = '" data-complete-url="' . e(action_url('update_status', ['id' => $a['appointment_code']]));
                }
            }
            echo '<tr><td>' . e($a['name']) . '</td><td>' . e($a['doctor_name']) . '</td><td>' . e(format_date_display($a['appointment_date'])) . '</td><td>' . appointment_badge($a['appointment_status'], $role) . '</td><td><button type="button" class="btn btn-sm btn-outline dashboard-view-btn" onclick="showAppointmentDetails(this)" data-code="' . e($a['appointment_code']) . '" data-patient="' . e($a['name']) . '" data-doctor="' . e($a['doctor_name']) . '" data-service="' . e($a['service_name']) . '" data-date="' . e(format_date_display($a['appointment_date'])) . '" data-time="' . e(format_time_display($a['appointment_time'])) . '" data-notes="' . e(appointment_booking_notes($a['notes'] ?? '')) . '" data-cancel-reason="' . e(appointment_cancel_reason($a['notes'] ?? '')) . '" data-payment-notes="' . e(appointment_payment_notes($a)) . '" data-payment-method="' . e(payment_method_from_payment(['payment_method' => $a['latest_payment_method'] ?? '', 'remarks' => $a['latest_payment_remarks'] ?? ''])) . '" data-status="' . e($a['appointment_status']) . '" data-payment="' . e($a['payment_status']) . '" data-amount="RM ' . e(number_format((float) $a['amount'], 2)) . $actionData . '">View</button></td></tr>';
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
    echo '<div class="modal-overlay" id="modal-appointment-details"><div class="modal appointment-details-modal"><div class="modal-header"><span class="modal-title">Appointment Details</span><button class="modal-close appointment-modal-close" onclick="closeModal(\'modal-appointment-details\')">×</button></div><div class="modal-body"><div class="appointment-detail-code"><span>Appointment ID</span><strong id="detailAppointmentCode"></strong></div><div class="appointment-detail-list"><div><span>Patient</span><strong id="detailPatient"></strong></div><div><span>Doctor</span><strong id="detailDoctor"></strong></div><div><span>Service</span><strong id="detailService"></strong></div><div><span>Date</span><strong id="detailDate"></strong></div><div><span>Time</span><strong id="detailTime"></strong></div><div><span>Status</span><strong id="detailStatus"></strong></div><div><span>Payment</span><strong id="detailPayment"></strong></div><div><span>Payment Method</span><strong id="detailPaymentMethod"></strong></div><div><span>Amount</span><strong class="detail-amount" id="detailAmount"></strong></div><div class="appointment-detail-notes"><span>Notes</span><strong id="detailNotes"></strong></div></div><div class="appointment-detail-actions"><button type="button" class="btn btn-danger" id="detailCancelAction" style="display:none;width:auto">Cancel Appointment</button><button type="button" class="btn btn-outline" id="detailPaymentAction" style="display:none;width:auto"></button></div></div></div></div>';
    if ($role === 'user') {
        echo '<div class="modal-overlay" id="modal-dashboard-payment-proof"><div class="modal payment-proof-modal"><div class="modal-header"><span class="modal-title">Payment Proof</span><button class="modal-close" onclick="closeModal(\'modal-dashboard-payment-proof\')">×</button></div><div class="modal-body"><div class="payment-proof-card" id="dashboardPaymentProofContent"></div></div></div></div>';
        echo '<div class="modal-overlay" id="modal-dashboard-receipt"><div class="modal receipt-modal"><div class="modal-header"><span class="modal-title">Payment Receipt</span><button class="modal-close" onclick="closeModal(\'modal-dashboard-receipt\')">×</button></div><div class="modal-body" id="dashboardReceiptContent"></div><div class="modal-footer"><button class="btn btn-primary" style="width:auto" onclick="window.print()">Print</button><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-dashboard-receipt\')">Close</button></div></div></div>';
    } elseif (in_array($role, ['admin', 'staff'], true)) {
        echo '<div class="modal-overlay" id="modal-complete-appointment"><div class="modal"><div class="modal-header"><span class="modal-title">Complete Appointment</span><button class="modal-close" onclick="closeModal(\'modal-complete-appointment\')">×</button></div><div class="modal-body"><p class="text-muted mb-16">Are you sure this appointment has been completed?</p></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-complete-appointment\')">Cancel</button><a class="btn btn-teal" id="confirmCompleteAppointmentAction" style="width:auto" href="#">Complete</a></div></div></div>';
    }
    echo '<script>
    function formatStatusLabel(status) {
        const labels = { unpaid: "Unpaid", paid: "Paid" };
        return labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : "");
    }
    function setDetailBadge(id, status) {
        const target = document.getElementById(id);
        if (!target) return;
        const badgeClass = status.replace(/_/g, "-");
        target.innerHTML = "<span class=\"badge badge-" + badgeClass + "\">" + formatStatusLabel(status) + "</span>";
    }
    function trimDetailNotes(notes) {
        notes = (notes || "").trim();
        if (!notes) return "-";
        return notes.length > 120 ? notes.slice(0, 120) + "..." : notes;
    }
    function setDetailPaymentAction(button) {
        const action = document.getElementById("detailPaymentAction");
        const cancelAction = document.getElementById("detailCancelAction");
        let refundAction = document.getElementById("detailRefundAction");
        if (!refundAction && action) {
            refundAction = document.createElement("button");
            refundAction.type = "button";
            refundAction.id = "detailRefundAction";
            refundAction.textContent = "Request Refund";
            action.parentNode.insertBefore(refundAction, action);
        }
        if (!action) return;
        const status = button.dataset.status || "";
        const payment = button.dataset.payment || "";
        const isCancelled = status === "cancelled";
        if (refundAction) {
            refundAction.style.display = "none";
            refundAction.onclick = null;
            refundAction.className = "btn btn-outline";
            refundAction.style.width = "auto";
        }
        if (cancelAction) {
            cancelAction.style.display = "none";
            cancelAction.onclick = null;
            if (button.dataset.cancelCode) {
                cancelAction.onclick = function () {
                    closeModal("modal-appointment-details");
                    openCancelAppointmentModal({ dataset: { code: button.dataset.cancelCode } });
                };
                cancelAction.style.display = "inline-flex";
            }
        }
        if (!button.dataset.payUrl && !button.dataset.proofUrl && !button.dataset.receiptId && !button.dataset.refundProofUrl && !button.dataset.completeUrl) {
            action.style.display = "none";
            action.onclick = null;
            return;
        }

        action.style.display = "none";
        action.onclick = null;
        action.className = "btn btn-primary";
        action.style.width = "auto";

        if (button.dataset.completeUrl) {
            action.textContent = "Complete";
            action.className = "btn btn-teal";
            action.onclick = function () { openCompleteAppointmentModal(button.dataset.completeUrl); };
        } else if (!isCancelled && payment === "pending") {
            action.textContent = "Pay";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (!isCancelled && payment === "rejected") {
            action.textContent = "Retry Payment";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (payment === "verifying") {
            action.textContent = "View Payment Proof";
            action.onclick = function () {
                openDashboardPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "");
            };
        } else if (["paid", "refund_requested", "refund_rejected"].includes(payment)) {
            action.textContent = "View Receipt";
            action.onclick = function () {
                printDashboardReceipt(Number(button.dataset.receiptId || 0));
            };
        } else if (payment === "refunded") {
            action.textContent = "View Refund Proof";
            action.onclick = function () {
                openDashboardPaymentProof(button.dataset.refundProofUrl || "", button.dataset.refundProofExt || "", "Refund Proof");
            };
        } else {
            action.style.display = "none";
            action.onclick = null;
        }
        if (action.onclick) action.style.display = "inline-flex";
        if (
            refundAction
            && "' . e($role) . '" === "user"
            && ["paid", "approved"].includes(payment)
            && ["confirm", "confirmed", "cancelled"].includes(status)
            && Number(button.dataset.receiptId || 0) > 0
        ) {
            refundAction.onclick = function () {
                closeModal("modal-appointment-details");
                requestAppointmentRefund(Number(button.dataset.receiptId || 0));
            };
            refundAction.style.display = "inline-flex";
        }
    }
    function showAppointmentDetails(button) {
        document.getElementById("detailAppointmentCode").textContent = button.dataset.code || "";
        document.getElementById("detailPatient").textContent = button.dataset.patient || "";
        document.getElementById("detailDoctor").textContent = button.dataset.doctor || "";
        document.getElementById("detailService").textContent = button.dataset.service || "";
        document.getElementById("detailDate").textContent = button.dataset.date || "";
        document.getElementById("detailTime").textContent = button.dataset.time || "";
        document.getElementById("detailPaymentMethod").textContent = button.dataset.paymentMethod || "-";
        document.getElementById("detailNotes").textContent = trimDetailNotes(button.dataset.notes);
        const cancelReason = (button.dataset.cancelReason || "").trim();
        let cancelWrap = document.getElementById("detailCancelReasonWrap");
        if (!cancelWrap) {
            cancelWrap = document.createElement("div");
            cancelWrap.className = "appointment-detail-notes";
            cancelWrap.id = "detailCancelReasonWrap";
            cancelWrap.innerHTML = "<span>Cancel Reason</span><strong id=\"detailCancelReason\"></strong>";
            document.getElementById("detailNotes").closest(".appointment-detail-notes").after(cancelWrap);
        }
        document.getElementById("detailCancelReason").textContent = cancelReason;
        cancelWrap.style.display = cancelReason ? "" : "none";
        const paymentNotes = (button.dataset.paymentNotes || "").trim();
        let paymentNotesWrap = document.getElementById("detailPaymentNotesWrap");
        if (!paymentNotesWrap) {
            paymentNotesWrap = document.createElement("div");
            paymentNotesWrap.className = "appointment-detail-notes";
            paymentNotesWrap.id = "detailPaymentNotesWrap";
            paymentNotesWrap.innerHTML = "<span>Payment Remarks</span><strong id=\"detailPaymentNotes\"></strong>";
            cancelWrap.after(paymentNotesWrap);
        }
        document.getElementById("detailPaymentNotes").textContent = paymentNotes;
        paymentNotesWrap.style.display = paymentNotes ? "" : "none";
        document.getElementById("detailAmount").textContent = button.dataset.amount || "";
        setDetailBadge("detailStatus", button.dataset.status || "");
        setDetailBadge("detailPayment", button.dataset.payment || "");
        setDetailPaymentAction(button);
        openModal("modal-appointment-details");
    }
    function openCompleteAppointmentModal(url) {
        const action = document.getElementById("confirmCompleteAppointmentAction");
        if (!action || !url) return;
        action.href = url;
        openModal("modal-complete-appointment");
    }
    function openDashboardPaymentProof(proofUrl, proofExt, title) {
        const content = document.getElementById("dashboardPaymentProofContent");
        if (!content) return;
        const modalTitle = document.querySelector("#modal-dashboard-payment-proof .modal-title");
        if (modalTitle) modalTitle.textContent = title || "Payment Proof";
        proofExt = (proofExt || "").toLowerCase();
        if (!proofUrl) {
            content.innerHTML = "<p class=\"text-muted text-center\">No payment proof uploaded.</p>";
        } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(proofExt)) {
            content.innerHTML = "<img src=\"" + proofUrl + "\" alt=\"Payment proof\">";
        } else {
            content.innerHTML = "<div class=\"file-open-fallback\"><p>This payment proof file cannot be previewed here.</p><a class=\"btn btn-outline\" target=\"_blank\" rel=\"noopener\" href=\"" + proofUrl + "\">Open File</a></div>";
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
    echo '</nav><div class="sidebar-footer"><div class="user-info">' . user_avatar_html($user, 'user-avatar') . '<div>';
    echo '<div class="user-name">' . e($user['name']) . '</div><div class="user-email">' . e($user['email']) . '</div></div></div>';
    echo '<a class="btn-signout" href="' . e(action_url('logout')) . '" draggable = "false">🚪 Log Out</a></div></aside>';
}

function render_stats($role) {
    global $conn;

    $upcomingAppointments = count_appointments($conn, 'user', ["(appointment_date > CURDATE() OR (appointment_date = CURDATE() AND appointment_time >= CURTIME()))", "appointment_status <> ?"], 's', ['cancelled']);
    $completedAppointments = count_appointments($conn, 'user', ["appointment_status = ?"], 's', ['completed']);
    $pendingPayments = count_appointments($conn, 'user', ["payment_status = ?"], 's', ['pending']);

    $todayAppointments = count_appointments($conn, null, ["appointment_date = CURDATE()", "appointment_status <> ?"], 's', ['cancelled']);
    $confirmed = count_appointments($conn, null, ["appointment_status = ?"], 's', ['confirmed']);
    $totalUsers = (int)(fetch_all_assoc($conn, "SELECT COUNT(*) AS total FROM users WHERE role = ?", 's', ['user'])[0]['total'] ?? 0);

    $totalAppointments = count_appointments($conn);
    $verifyingPaymentRows = fetch_all_assoc($conn, "SELECT COUNT(*) AS total FROM payments WHERE payment_status = ?", 's', ['verifying']);
    $verifyingPayments = (int) ($verifyingPaymentRows[0]['total'] ?? 0);
    $revenueRows = fetch_all_assoc($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_status = ?", 's', ['paid']);
    $totalRevenue = (float) ($revenueRows[0]['total'] ?? 0);
    $totalActiveUsers = (int)(fetch_all_assoc($conn, "SELECT COUNT(*) AS total FROM users WHERE role = ? AND user_status = ?", 'ss', ['user', 'active'])[0]['total'] ?? 0);

    $stats = [
        'user' => [['📅','primary',$upcomingAppointments,'Upcoming Appointments',''], ['✅','success',$completedAppointments,'Completed',''], ['⏳','warning',$pendingPayments,'Pending Payment','']],
        'staff' => [['📅','primary',$todayAppointments,"Today's Appointments",''], ['✅','success',$confirmed,'Confirmed',''], ['👥','teal',$totalUsers,'Total Users','']],
        'admin' => [['📅','primary',$totalAppointments,'Total Appointments',''], ['💰','success','RM ' . number_format($totalRevenue, 2),'Revenue',''], ['👥','teal',$totalActiveUsers,'Active Users',''], ['⏳','warning',$verifyingPayments,'Payment Verifying','']],
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
        'refunded' => 'Refunded',
        'refund_requested' => 'Refund Requested',
        'refund-requested' => 'Refund Requested',
        'refund_rejected' => 'Refund Rejected',
        'refund-rejected' => 'Refund Rejected',
    ];
    $label = $labels[$status] ?? ucfirst($status);
    $badgeClass = str_replace('_', '-', $status);
    return '<span class="badge badge-' . e($badgeClass) . '">' . e($label) . '</span>';
}

function payment_note_display($status, $remarks) {
    $status = strtolower((string)$status);
    $remarks = trim((string)$remarks);
    if ($remarks === '') {
        return ['label' => '', 'text' => '', 'is_refund' => false];
    }

    $lines = preg_split('/\R+/', $remarks);
    $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== '' && stripos($line, 'Payment method:') !== 0));
    if (empty($lines)) {
        return ['label' => '', 'text' => '', 'is_refund' => false];
    }

    if (in_array($status, ['refund_requested', 'refunded', 'refund_rejected'], true)) {
        $refundReason = '';
        $rejectReason = '';

        foreach ($lines as $line) {
            if (stripos($line, 'Refund requested:') === 0) {
                $refundReason = trim(substr($line, strlen('Refund requested:')));
            } elseif (stripos($line, 'Refund request rejected:') === 0) {
                $rejectReason = trim(substr($line, strlen('Refund request rejected:')));
            }
        }

        $refundLines = [];
        if ($refundReason !== '') {
            $refundLines[] = 'Request Reason: ' . $refundReason;
        }
        if ($status === 'refund_rejected' && $rejectReason !== '') {
            $refundLines[] = 'Reject Reason: ' . $rejectReason;
        }

        return [
            'label' => 'Refund Details:',
            'text' => implode("\n", $refundLines),
            'is_refund' => true,
        ];
    }

    if ($status === 'rejected') {
        $userRemarkLines = [];
        $rejectReason = '';

        foreach ($lines as $line) {
            if (stripos($line, 'Rejected:') === 0) {
                $rejectReason = trim(substr($line, strlen('Rejected:')));
                continue;
            }
            $userRemarkLines[] = $line;
        }

        $displayLines = [];
        if (!empty($userRemarkLines)) {
            $displayLines[] = 'User Remarks: ' . implode("\n", $userRemarkLines);
        }
        if ($rejectReason !== '') {
            $displayLines[] = 'Reject Reason: ' . $rejectReason;
        }

        return ['label' => 'Remarks:', 'text' => implode("\n", $displayLines), 'is_refund' => false];
    }

    $userRemarkLines = array_values(array_filter($lines, function ($line) {
        return stripos($line, 'Rejected:') !== 0
            && stripos($line, 'Refund requested:') !== 0
            && stripos($line, 'Refund request rejected:') !== 0
            && stripos($line, 'Refund receipt:') !== 0;
    }));

    if (empty($userRemarkLines)) {
        return ['label' => '', 'text' => '', 'is_refund' => false];
    }

    return ['label' => 'Remarks:', 'text' => implode("\n", $userRemarkLines), 'is_refund' => false];
}

function payment_method_display($remarks) {
    $lines = preg_split('/\R+/', (string)$remarks);
    foreach ($lines as $line) {
        $line = trim($line);
        if (stripos($line, 'Payment method:') === 0) {
            return trim(substr($line, strlen('Payment method:')));
        }
    }

    return '';
}

function payment_method_from_payment($payment) {
    $method = trim((string)($payment['payment_method'] ?? ''));
    if ($method !== '') {
        return $method;
    }

    return payment_method_display($payment['remarks'] ?? '');
}

function ensure_payment_method_column($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM payments LIKE 'payment_method'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        return true;
    }

    return (bool) $conn->query("ALTER TABLE payments ADD COLUMN payment_method VARCHAR(80) NULL AFTER payment_status");
}

function ensure_failed_payment_status($conn) {
    foreach (['appointments', 'payments'] as $table) {
        $columnCheck = $conn->query("SHOW COLUMNS FROM {$table} LIKE 'payment_status'");
        if (!$columnCheck || $columnCheck->num_rows === 0) {
            continue;
        }

        $column = $columnCheck->fetch_assoc();
        $type = (string)($column['Type'] ?? '');
        if (stripos($type, 'enum(') !== 0 || str_contains($type, "'failed'")) {
            continue;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
        $values = array_map(function ($value) {
            return str_replace("\\'", "'", $value);
        }, $matches[1] ?? []);
        $values[] = 'failed';
        $enumValues = implode(',', array_map(function ($value) use ($conn) {
            return "'" . $conn->real_escape_string($value) . "'";
        }, array_unique($values)));
        $nullSql = strtoupper((string)($column['Null'] ?? '')) === 'NO' ? 'NOT NULL' : 'NULL';
        $default = $column['Default'] ?? null;
        $defaultSql = $default !== null ? " DEFAULT '" . $conn->real_escape_string((string)$default) . "'" : '';
        $conn->query("ALTER TABLE {$table} MODIFY payment_status ENUM({$enumValues}) {$nullSql}{$defaultSql}");
    }

    return true;
}

function clean_payment_remarks($remarks) {
    $lines = preg_split('/\R+/', (string)$remarks);
    $lines = array_values(array_filter(array_map('trim', $lines), function ($line) {
        return $line !== '' && stripos($line, 'Payment method:') !== 0;
    }));

    return implode("\n", $lines);
}

function toyyibpay_config() {
    $configPath = __DIR__ . '/toyyibpay_config.php';
    $config = is_file($configPath) ? require $configPath : [];

    return [
        'secret_key' => trim((string)(getenv('TOYYIBPAY_SECRET_KEY') ?: ($config['secret_key'] ?? ''))),
        'category_code' => trim((string)(getenv('TOYYIBPAY_CATEGORY_CODE') ?: ($config['category_code'] ?? ''))),
        'base_url' => rtrim((string)($config['base_url'] ?? 'https://toyyibpay.com'), '/'),
    ];
}

function create_toyyibpay_bill($appointment, $user) {
    $config = toyyibpay_config();
    if ($config['secret_key'] === '' || $config['category_code'] === '') {
        return ['success' => false, 'message' => 'ToyyibPay secret key or category code is not configured.'];
    }

    $amount = (float)($appointment['amount'] ?? 0);
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Invalid payment amount.'];
    }

    $billName = 'QuickCare ' . ($appointment['appointment_code'] ?? 'Appointment');
    $billDescription = trim(($appointment['service_name'] ?? 'Clinic appointment') . ' - ' . ($appointment['doctor_name'] ?? ''));
    $payload = [
        'userSecretKey' => $config['secret_key'],
        'categoryCode' => $config['category_code'],
        'billName' => $billName,
        'billDescription' => $billDescription !== '' ? $billDescription : 'QuickCare appointment payment',
        'billPriceSetting' => 1,
        'billPayorInfo' => 1,
        'billAmount' => (int)round($amount * 100),
        'billReturnUrl' => absolute_app_url('action.php?action=toyyibpay_return'),
        'billCallbackUrl' => absolute_app_url('action.php?action=toyyibpay_callback'),
        'billExternalReferenceNo' => (string)($appointment['appointment_code'] ?? ''),
        'billTo' => (string)($user['name'] ?? $appointment['name'] ?? 'QuickCare Patient'),
        'billEmail' => (string)($user['email'] ?? ''),
        'billPhone' => (string)($user['phone_number'] ?? ''),
        'billPaymentChannel' => 0,
        'billContentEmail' => 'Thank you for your QuickCare payment.',
        'billChargeToCustomer' => 1,
    ];

    $endpoint = $config['base_url'] . '/index.php/api/createBill';
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL is required for ToyyibPay integration.'];
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false || $curlError !== '') {
        return ['success' => false, 'message' => 'ToyyibPay request failed: ' . $curlError];
    }

    $response = json_decode($rawResponse, true);
    $billCode = $response[0]['BillCode'] ?? $response['BillCode'] ?? '';
    if ($billCode === '') {
        return ['success' => false, 'message' => 'ToyyibPay did not return a bill code.'];
    }

    return [
        'success' => true,
        'bill_code' => $billCode,
        'payment_url' => $config['base_url'] . '/' . rawurlencode($billCode),
    ];
}

function mark_toyyibpay_payment_paid($billCode) {
    global $conn;
    ensure_payment_method_column($conn);

    $billCode = trim((string)$billCode);
    if ($billCode === '') {
        return false;
    }

    $stmt = $conn->prepare("SELECT * FROM payments WHERE transaction_id = ? LIMIT 1");
    $stmt->bind_param("s", $billCode);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        return false;
    }

    $wasAlreadyPaid = in_array(strtolower((string)($payment['payment_status'] ?? '')), ['paid', 'approved'], true);

    $conn->begin_transaction();
    try {
        $receiptNumber = payment_receipt_number($payment);
        $payment['receipt_number'] = $receiptNumber;

        $stmt = $conn->prepare("UPDATE payments SET payment_status = 'paid', receipt_number = ?, approved_date = COALESCE(approved_date, NOW()) WHERE payment_id = ?");
        $paymentId = (int)$payment['payment_id'];
        $stmt->bind_param("si", $receiptNumber, $paymentId);
        $stmt->execute();
        $stmt->close();

        if (!empty($payment['appointment_code'])) {
            $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'paid' WHERE appointment_code = ?");
            $stmt->bind_param("s", $payment['appointment_code']);
            $stmt->execute();
            $stmt->close();
        }

        if (!$wasAlreadyPaid) {
            $stmt = $conn->prepare("
                INSERT INTO receipts (receipt_number, payment_id, user_id, amount, issued_date)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("siid", $receiptNumber, $paymentId, $payment['user_id'], $payment['amount']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        if (!$wasAlreadyPaid) {
            $payment['payment_status'] = 'paid';
            send_payment_approved_email((int)$payment['user_id'], $payment);
        }
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function payment_refund_receipt_file($remarks) {
    $lines = preg_split('/\R+/', (string)$remarks);
    foreach ($lines as $line) {
        $line = trim($line);
        if (stripos($line, 'Refund receipt:') === 0) {
            return trim(substr($line, strlen('Refund receipt:')));
        }
    }
    return '';
}

function appointment_badge($status, $role = null) {
    return badge($status);
}

function render_profile($role) {
    global $conn;
    $u = current_user($conn);
    if (!$u) {
        redirect_to(app_url('login.php'));
    }
    $accountStatus = strtolower((string)($u['user_status'] ?? 'inactive')) === 'active' ? 'active' : 'inactive';
    echo '<div class="profile-header">' . user_avatar_html($u, 'profile-avatar-lg') . '<div><div class="profile-name">' . e($u['name']) . '</div><div class="profile-meta">' . e($u['role'] . ' · ID: ' . $u['user_code']) . '</div><div style="margin-top:8px"><span class="badge badge-' . e($accountStatus) . '">• ' . e(ucfirst($accountStatus)) . '</span></div></div><button class="btn btn-outline" style="margin-left:auto" onclick="openModal(\'modal-edit-profile\')">✏️ Edit Profile</button></div>';
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Personal Information</span></div><div class="card-body"><div style="display:flex;flex-direction:column;gap:12px">';
    foreach ([['Full Name',$u['name']], ['Email',$u['email']], ['Phone',format_phone_number($u['phone_number'])], ['Gender',$u['gender']], ['Date of Birth',$u['date_of_birth']], ['Blood Type',$u['blood_type']]] as $row) echo '<div class="flex-between"><span class="text-muted">' . e($row[0]) . '</span><span>' . e($row[1]) . '</span></div><div class="divider"></div>';
    echo '</div></div></div><div class="card"><div class="card-header"><span class="card-title">Change Password</span></div><div class="card-body"><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="change_password"><div class="form-group"><label>Current Password</label><input class="form-control" type="password" name="current_password" required></div><div class="form-group"><label>New Password</label><input class="form-control" type="password" name="new_password" required></div><div class="form-group"><label>Confirm Password</label><input class="form-control" type="password" name="confirm_password" required></div><button class="btn btn-primary" style="width:auto">Update Password</button></form></div></div></div>';
}

function render_services($role, $showToolbar = true) {
    global $conn;
    $services = get_services($conn);
    if ($showToolbar) {
        echo '<div class="toolbar">
                <div class="search-input-wrap">
                    <span class="search-icon">🔍</span>
                    <input class="form-control" type="text" placeholder="Search services…" id="searchServiceInput">
              </div>';
        if ($role === 'admin') {
            echo '<button class="btn btn-primary" style="width:auto" onclick="openAddServiceModal()">
                    + Add Service
                  </button>';
        }
        echo '</div>';
    }
    echo '<div class="services-grid" id="servicesGrid">';
    foreach ($services as $s) {
        $savedOverview = trim((string)($s['service_overview'] ?? ''));
        $longDesc = $savedOverview !== '' ? $savedOverview : service_default_overview($s['service_name'], 'No service overview added yet.');
        $name = strtolower($s['service_name']);
        $icon = get_service_icon($s['service_name']);

        if (str_contains($name, 'general check-up')) {
            $longDesc = "Our comprehensive General Check-up is designed for total health awareness and proactive wellness management. This service includes a full physical examination, vital signs monitoring (blood pressure, heart rate, BMI), and a personalized consultation with our experienced medical practitioners. We focus on early detection of potential health risks and chronic conditions, providing you with professional guidance on maintaining a healthy lifestyle through preventive care tailored to your specific age, gender, and medical history. Regular check-ups are the cornerstone of long-term health, helping you stay ahead of any issues before they become serious.";
        } elseif (str_contains($name, 'dental care')) {
            $longDesc = "Maintain a bright, confident, and healthy smile with our professional Dental Care services. This comprehensive oral health package covers routine clinical examinations, professional scaling and polishing to remove stubborn plaque and tartar, and detailed screening for gum disease, cavities, or other oral issues. Our dental experts utilize state-of-the-art equipment and gentle techniques to ensure your comfort while keeping your oral hygiene at its peak. Beyond treatment, we provide personalized advice on effective brushing, flossing, and long-term dental health maintenance to prevent future complications and ensure your smile lasts a lifetime.";
        } elseif (str_contains($name, 'eye examination')) {
            $longDesc = "Vision is vital to your daily quality of life. Our professional Eye Examination service provides a thorough assessment of your visual acuity and overall ocular health using specialized diagnostic equipment. We screen for common vision problems such as refractive errors (nearsightedness, farsightedness, astigmatism) as well as more serious conditions like glaucoma, cataracts, and diabetic retinopathy. Whether you require a new prescription for corrective lenses or a routine preventative health check for your eyes, our specialists ensure precise testing and professional care. We prioritize preserving your sight and helping you see the world clearly at every stage of life.";
        } elseif (str_contains($name, 'vaccination')) {
            $longDesc = "Protect yourself and your loved ones from preventable diseases with our professional Vaccination services. We offer a wide range of essential immunizations, including seasonal flu shots, travel vaccines for international trips, and standard boosters for adults and children. Our clinical team strictly adheres to national health guidelines for vaccine administration and temperature-controlled storage, ensuring maximum efficacy and safety. We provide a clean, safe environment for your injections and provide accurate, detailed documentation for your permanent medical immunization records. Stay protected and contribute to community health by keeping your vaccines up to date with QuickCare.";
        } elseif (str_contains($name, 'blood test')) {
            $longDesc = "Gain deep, data-driven insights into your internal health with our diagnostic Blood Test services. We provide accurate laboratory analysis for a wide array of critical health markers, including full blood counts, lipid profiles (cholesterol/triglycerides), blood glucose levels for diabetes screening, and specialized kidney or liver function tests. Our collection process is quick, professional, and designed to be as painless as possible. Once processed, the results are interpreted by our medical staff, who will walk you through the findings to help you monitor existing conditions or detect silent health issues early. Regular blood screening is an essential tool for effective health management.";
        } elseif (str_contains($name, 'cardiology')) {
            $longDesc = "Our specialized Cardiology screening focuses on the most important muscle in your body—your heart. This comprehensive service includes thorough cardiovascular risk assessments, blood pressure management, and in-depth consultations regarding your heart health history and lifestyle. We utilize modern diagnostic tools to evaluate cardiac function and identify potential issues such as arrhythmias, hypertension, or coronary artery disease. By catching early warning signs of cardiovascular stress, we help you take proactive, life-saving steps toward a heart-healthy future. Our specialists provide you with a clear roadmap for heart health, including diet and exercise recommendations tailored to your cardiac profile.";
        }

        if ($savedOverview !== '') {
            $longDesc = $savedOverview;
        }

        echo '<div class="service-card" style="cursor:pointer" onclick="showServiceDetails(this)" 
                   data-id="' . e($s['service_id']) . '"
                   data-name="' . e($s['service_name']) . '" 
                   data-icon="' . e($icon) . '" 
                   data-fee="' . e(number_format((float) $s['service_price'], 2, '.', '')) . '"
                   data-price="RM ' . e(number_format((float) $s['service_price'], 2)) . '" 
                   data-short-desc="' . e($s['service_description']) . '"
                   data-desc="' . e($longDesc) . '">
                <span class="service-icon">' . e($icon) . '</span>
                <div class="service-name">' . e($s['service_name']) . '</div>
                <div class="service-price">RM ' . e(number_format((float) $s['service_price'], 2)) . '</div>
                <div class="service-desc">' . e($s['service_description']) . '</div>';
        if ($role === 'admin') {
            echo '<div class="service-actions" onclick="event.stopPropagation()" onmouseenter="this.closest(\'.service-card\')?.classList.add(\'service-actions-hover\')" onmouseleave="this.closest(\'.service-card\')?.classList.remove(\'service-actions-hover\')">
                    <button class="btn btn-sm btn-outline" type="button" onclick="openEditServiceModal(event, this)" data-id="' . e($s['service_id']) . '" data-name="' . e($s['service_name']) . '" data-fee="' . e(number_format((float) $s['service_price'], 2, '.', '')) . '" data-description="' . e($s['service_description']) . '" data-overview="' . e($longDesc) . '">✏️</button>
                    <a class="btn btn-sm btn-danger" href="' . e(action_url('delete', ['type' => 'service', 'id' => (int)$s['service_id']])) . '" onclick="openDeleteServiceModal(event, this)">🗑</a>
                  </div>';
        }
        echo '</div>';
    }
    $adminOverviewControls = '';
    if ($role === 'admin') {
        $adminOverviewControls = '<button class="btn btn-outline" type="button" id="serviceOverviewEditBtn" style="width:auto; padding: 6px 12px; margin-left:auto;" onclick="toggleServiceOverviewEdit(true)">Edit Overview</button>';
    }
    $deleteServiceModal = '';
    if ($role === 'admin') {
        $deleteServiceModal = '<div class="modal-overlay" id="modal-delete-service"><div class="modal"><div class="modal-header"><span class="modal-title">Delete Service</span><button class="modal-close" onclick="closeModal(\'modal-delete-service\')">×</button></div><div class="modal-body"><p class="text-muted mb-16">Are you sure you want to delete this clinic service?</p><div class="appointment-detail-code"><span>Service</span><strong id="deleteServiceName">-</strong></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-delete-service\')">Cancel</button><a class="btn btn-danger" id="confirmDeleteServiceAction" style="width:auto" href="#">Delete</a></div></div></div>';
    }
    echo '</div>
    <div class="modal-overlay" id="modal-service-details">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">Service Details</span>
                <button class="modal-close" onclick="closeModal(\'modal-service-details\')">×</button>
            </div>
            <div class="modal-body" style="text-align:center">
                <div id="serviceDetailIcon" style="font-size: 3.5rem; margin-bottom: 12px;"></div>
                <h2 id="serviceDetailName" style="margin-bottom: 4px;"></h2>
                <div id="serviceDetailPrice" style="color: var(--primary); font-weight: 600; font-size: 1.2rem; margin-bottom: 20px;"></div>
                <div class="divider" style="margin-bottom: 24px;"></div>
                <div style="text-align: left; background: var(--surface2); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                    <h4 style="margin-top: 0; margin-bottom: 12px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        <span>📋</span> Service Overview
                        ' . $adminOverviewControls . '
                    </h4>
                    <p id="serviceDetailDesc" style="line-height: 1.7; color: var(--text-muted); white-space: pre-wrap; font-size: 0.95rem; margin: 0;"></p>
                    <form id="serviceOverviewForm" method="post" action="' . e(app_url('action.php')) . '" style="display:none; margin: 0;">
                        <input type="hidden" name="action" value="update_service_overview">
                        <input type="hidden" name="service_id" id="serviceOverviewId">
                        <textarea class="form-control" name="service_overview" id="serviceOverviewTextarea" rows="9" required style="resize: vertical;"></textarea>
                        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                            <button class="btn btn-outline" type="button" style="width:auto" onclick="toggleServiceOverviewEdit(false)">Cancel</button>
                            <button class="btn btn-primary" type="submit" style="width:auto">Save Overview</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    ' . $deleteServiceModal . '
    <script>
    if (typeof openModal !== "function") { window.openModal = function(id) { document.getElementById(id).classList.add("active"); }; }
    if (typeof closeModal !== "function") { window.closeModal = function(id) { document.getElementById(id).classList.remove("active"); }; }
    function openDeleteServiceModal(event, link) {
        event.preventDefault();
        event.stopPropagation();
        const modalAction = document.getElementById("confirmDeleteServiceAction");
        const modalName = document.getElementById("deleteServiceName");
        const card = link.closest(".service-card");
        if (modalAction) modalAction.href = link.href || "#";
        if (modalName) modalName.textContent = card?.dataset.name || "Selected service";
        openModal("modal-delete-service");
    }
    function showServiceDetails(el) {
        window.currentServiceCard = el;
        document.getElementById("serviceDetailIcon").textContent = el.dataset.icon;
        document.getElementById("serviceDetailName").textContent = el.dataset.name;
        document.getElementById("serviceDetailPrice").textContent = el.dataset.price;
        document.getElementById("serviceDetailDesc").textContent = el.dataset.desc;
        document.getElementById("serviceOverviewId").value = el.dataset.id || "";
        document.getElementById("serviceOverviewTextarea").value = el.dataset.desc || "";
        toggleServiceOverviewEdit(false);
        openModal("modal-service-details");
    }
    function toggleServiceOverviewEdit(isEditing) {
        const desc = document.getElementById("serviceDetailDesc");
        const form = document.getElementById("serviceOverviewForm");
        const editBtn = document.getElementById("serviceOverviewEditBtn");
        const textarea = document.getElementById("serviceOverviewTextarea");
        if (!desc || !form) return;

        desc.style.display = isEditing ? "none" : "";
        form.style.display = isEditing ? "" : "none";
        if (editBtn) editBtn.style.display = isEditing ? "none" : "";
        if (textarea && window.currentServiceCard) {
            textarea.value = window.currentServiceCard.dataset.desc || "";
        }
        if (isEditing && textarea) textarea.focus();
    }
    document.getElementById("searchServiceInput")?.addEventListener("input", function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll("#servicesGrid .service-card").forEach(c => {
            c.style.display = c.innerText.toLowerCase().includes(q) ? "" : "none";
        });
    });
    </script>';
}

function render_doctors($role, $showToolbar = true) {
    global $conn;
    $doctors = get_doctors($conn);
    if ($showToolbar) {
        echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search doctors…" id="searchDoctorInput"></div>';
        if ($role === 'admin') echo '<button class="btn btn-primary" style="width:auto" onclick="openAddDoctorModal()">+ Add Doctor</button>';
        echo '</div>';
    }
    echo '<div class="doctor-grid" id="doctorsGrid">';
    foreach ($doctors as $d) {
        $available = $d['available_days'] ?: 'Not scheduled';

        $savedDescription = trim((string)($d['doctor_description'] ?? ''));
        $description = $savedDescription !== '' ? $savedDescription : doctor_default_description($d['doctor_specialist']);

        echo '<div class="doctor-card" style="cursor:pointer" onclick="showDoctorDetails(this)" 
                   data-id="' . e($d['doctor_id']) . '"
                   data-name="' . e($d['doctor_name']) . '" 
                   data-initials="' . e(name_avatar($d['doctor_name'] ?? 'Doctor')) . '" 
                   data-image="' . e($d['doctor_image'] ?? '') . '" 
                   data-spec="' . e($d['doctor_specialist']) . '" 
                   data-avail="Available ' . e($available) . '" 
                   data-desc="' . e($description) . '"
                   data-schedule="' . e($d['schedule_data'] ?? '') . '">
                ' . doctor_avatar_html($d) . '
                <div class="doctor-name">' . e($d['doctor_name']) . '</div>
                <div class="doctor-spec">' . e($d['doctor_specialist']) . '</div>
                <div class="doctor-avail">✅ Available ' . e($available) . '</div>';
        if ($role === 'admin') echo '<div class="doctor-actions" onclick="event.stopPropagation()" onmouseenter="this.closest(\'.doctor-card\')?.classList.add(\'doctor-actions-hover\')" onmouseleave="this.closest(\'.doctor-card\')?.classList.remove(\'doctor-actions-hover\')"><button class="btn btn-sm btn-outline" onclick="openEditDoctorModal(event, this)" data-id="' . e($d['doctor_id']) . '" data-name="' . e($d['doctor_name']) . '" data-initials="' . e(name_avatar($d['doctor_name'] ?? 'Doctor')) . '" data-image="' . e($d['doctor_image'] ?? '') . '" data-spec="' . e($d['doctor_specialist']) . '" data-days="' . e($d['available_days']) . '" data-service-ids="' . e($d['service_ids'] ?? '') . '">✏️</button><a class="btn btn-sm btn-danger" href="' . e(action_url('delete', ['type' => 'doctor', 'id' => (int)$d['doctor_id']])) . '" onclick="openDeleteDoctorModal(event, this)">🗑</a></div>';
        echo '</div>';
    }
    $adminAboutControls = '';
    if ($role === 'admin') {
        $adminAboutControls = '<button class="btn btn-outline" type="button" id="doctorAboutEditBtn" style="width:auto; padding: 6px 12px; margin-left:auto;" onclick="toggleDoctorAboutEdit(true)">Edit About</button>';
    }
    $deleteDoctorModal = '';
    $avatarPreviewModal = '';
    if ($role === 'guest') {
        $avatarPreviewModal = '<div class="modal-overlay" id="modal-avatar-preview"><div class="modal avatar-preview-modal"><div class="modal-header"><span class="modal-title" id="avatarPreviewTitle">Avatar</span><button class="modal-close" onclick="closeModal(\'modal-avatar-preview\')">&times;</button></div><div class="modal-body"><div class="avatar-preview-content" id="avatarPreviewContent"></div></div></div></div>';
    }
    if ($role === 'admin') {
        $deleteDoctorModal = '<div class="modal-overlay" id="modal-delete-doctor"><div class="modal"><div class="modal-header"><span class="modal-title">Delete Doctor</span><button class="modal-close" onclick="closeModal(\'modal-delete-doctor\')">×</button></div><div class="modal-body"><p class="text-muted mb-16">Are you sure you want to delete this doctor profile?</p><div class="appointment-detail-code"><span>Doctor</span><strong id="deleteDoctorName">-</strong></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-delete-doctor\')">Cancel</button><a class="btn btn-danger" id="confirmDeleteDoctorAction" style="width:auto" href="#">Delete</a></div></div></div>';
    }
    echo '</div>
    <div class="modal-overlay" id="modal-doctor-details">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">Doctor Profile</span>
                <button class="modal-close" onclick="closeModal(\'modal-doctor-details\')">×</button>
            </div>
            <div class="modal-body" style="text-align:center">
                <div id="doctorDetailAvatar" class="doctor-avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 16px;"></div>
                <h2 id="doctorDetailName" style="margin-bottom: 4px;"></h2>
                <div id="doctorDetailSpec" style="color: var(--primary); font-weight: 500; font-size: 1.1rem; margin-bottom: 8px;"></div>
                <div id="doctorDetailAvail" style="font-size: 0.9rem; color: var(--success); margin-bottom: 24px;"></div>
                <div class="divider" style="margin-bottom: 24px;"></div>
                <div style="text-align: left; background: var(--surface2); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                    <h4 style="margin-top: 0; margin-bottom: 12px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        <span>🩺</span> About the Specialist
                        ' . $adminAboutControls . '
                    </h4>
                    <p id="doctorDetailDesc" style="line-height: 1.7; color: var(--text-muted); white-space: pre-wrap; font-size: 0.95rem; margin: 0;"></p>
                    <form id="doctorAboutForm" method="post" action="' . e(app_url('action.php')) . '" style="display:none; margin: 0;">
                        <input type="hidden" name="action" value="update_doctor_description">
                        <input type="hidden" name="doctor_id" id="doctorAboutId">
                        <textarea class="form-control" name="doctor_description" id="doctorAboutTextarea" rows="8" required style="resize: vertical;"></textarea>
                        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                            <button class="btn btn-outline" type="button" style="width:auto" onclick="toggleDoctorAboutEdit(false)">Cancel</button>
                            <button class="btn btn-primary" type="submit" style="width:auto">Save About</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    ' . $deleteDoctorModal . '
    ' . $avatarPreviewModal . '
    <script>
    if (typeof openModal !== "function") { window.openModal = function(id) { document.getElementById(id).classList.add("active"); }; }
    if (typeof closeModal !== "function") { window.closeModal = function(id) { document.getElementById(id).classList.remove("active"); }; }
    if (typeof window.openAvatarPreview !== "function") {
        window.openAvatarPreview = function(url, initials, title) {
            const titleEl = document.getElementById("avatarPreviewTitle");
            const content = document.getElementById("avatarPreviewContent");
            if (!content) return;
            content.innerHTML = "";
            if (titleEl) titleEl.textContent = title || "Avatar";

            if (url) {
                const img = document.createElement("img");
                img.className = "avatar-preview-image";
                img.src = url;
                img.alt = title || "Avatar preview";
                content.appendChild(img);
            } else {
                const fallback = document.createElement("div");
                fallback.className = "avatar-preview-initials";
                fallback.textContent = initials || "?";
                content.appendChild(fallback);
            }
            openModal("modal-avatar-preview");
        };
    }
    if (typeof window.openAvatarPreviewFromTrigger !== "function") {
        window.openAvatarPreviewFromTrigger = function(trigger) {
            openAvatarPreview(
                trigger?.dataset?.avatarUrl || "",
                trigger?.dataset?.avatarInitials || trigger?.textContent?.trim() || "",
                trigger?.dataset?.avatarTitle || "Avatar"
            );
        };
    }
    function openDeleteDoctorModal(event, link) {
        event.preventDefault();
        event.stopPropagation();
        const modalAction = document.getElementById("confirmDeleteDoctorAction");
        const modalName = document.getElementById("deleteDoctorName");
        const card = link.closest(".doctor-card");
        if (modalAction) modalAction.href = link.href || "#";
        if (modalName) modalName.textContent = card?.dataset.name || "Selected doctor";
        openModal("modal-delete-doctor");
    }
    function showDoctorDetails(el) {
        const avatar = document.getElementById("doctorDetailAvatar");
        if (el.dataset.image) {
            avatar.classList.add("doctor-avatar-image");
            avatar.innerHTML = "<img src=\"' . e(app_url('uploads/doctors/')) . '" + encodeURIComponent(el.dataset.image) + "\" alt=\"Doctor photo\">";
            avatar.dataset.avatarUrl = "' . e(app_url('uploads/doctors/')) . '" + encodeURIComponent(el.dataset.image);
        } else {
            avatar.classList.remove("doctor-avatar-image");
            avatar.textContent = el.dataset.initials;
            avatar.dataset.avatarUrl = "";
        }
        avatar.dataset.avatarInitials = el.dataset.initials || "";
        avatar.dataset.avatarTitle = el.dataset.name || "Doctor Photo";
        avatar.setAttribute("role", "button");
        avatar.setAttribute("tabindex", "0");
        avatar.onclick = function(event) {
            event.stopPropagation();
            openAvatarPreviewFromTrigger(avatar);
        };
        avatar.onkeydown = function(event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                openAvatarPreviewFromTrigger(avatar);
            }
        };
        document.getElementById("doctorDetailName").textContent = el.dataset.name;
        document.getElementById("doctorDetailSpec").textContent = el.dataset.spec;
        document.getElementById("doctorDetailAvail").textContent = el.dataset.avail;
        document.getElementById("doctorDetailDesc").textContent = el.dataset.desc;
        document.getElementById("doctorAboutId").value = el.dataset.id || "";
        document.getElementById("doctorAboutTextarea").value = el.dataset.desc || "";
        toggleDoctorAboutEdit(false);
        openModal("modal-doctor-details");
    }
    function toggleDoctorAboutEdit(isEditing) {
        const desc = document.getElementById("doctorDetailDesc");
        const form = document.getElementById("doctorAboutForm");
        const editBtn = document.getElementById("doctorAboutEditBtn");
        const textarea = document.getElementById("doctorAboutTextarea");
        if (!desc || !form) return;

        desc.style.display = isEditing ? "none" : "";
        form.style.display = isEditing ? "" : "none";
        if (editBtn) editBtn.style.display = isEditing ? "none" : "";
        if (isEditing && textarea) textarea.focus();
    }
    document.getElementById("searchDoctorInput")?.addEventListener("input", function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll("#doctorsGrid .doctor-card").forEach(c => {
            c.style.display = c.innerText.toLowerCase().includes(q) ? "" : "none";
        });
    });
    </script>';
}

function render_features() {
    $features = [
        [
            'name' => 'Patient Registration',
            'icon' => '👤',
            'desc' => 'Create a profile so your clinic details are ready when needed.',
            'long' => 'Create your QuickCare profile in minutes. Securely store your personal details, emergency contacts, and medical preferences so they are ready whenever you book a visit. Our streamlined registration process ensures your information is accurately captured for better healthcare delivery.'
        ],
        [
            'name' => 'Appointment Booking',
            'icon' => '📅',
            'desc' => 'Request visits and keep appointment information organized.',
            'long' => 'Skip the phone calls and book your appointments online 24/7. Browse through our list of specialists, check their real-time availability, and select a time slot that fits your schedule. You can manage, reschedule, or cancel your appointments directly from your patient dashboard.'
        ],
        [
            'name' => 'Clinic Access',
            'icon' => '🏥',
            'desc' => 'Use one website to start and continue your healthcare journey.',
            'long' => 'Gain immediate access to our full suite of specialized healthcare services. Whether you need a routine check-up or specialized care in cardiology or dentistry, our integrated system connects you with the right professionals and resources to support your health journey.'
        ]
    ];

    echo '<div class="services-grid landing-services">';
    foreach ($features as $f) {
        echo '<article class="service-card" style="cursor:pointer" onclick="showFeatureDetails(this)" 
                       data-name="' . e($f['name']) . '" 
                       data-icon="' . e($f['icon']) . '" 
                       data-desc="' . e($f['long']) . '">
                <span class="service-icon">' . e($f['icon']) . '</span>
                <div class="service-name">' . e($f['name']) . '</div>
                <p class="service-desc">' . e($f['desc']) . '</p>
              </article>';
    }
    echo '</div>
    <div class="modal-overlay" id="modal-feature-details">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">Feature Details</span>
                <button class="modal-close" onclick="closeModal(\'modal-feature-details\')">×</button>
            </div>
            <div class="modal-body" style="text-align:center">
                <div id="featureDetailIcon" style="font-size: 3.5rem; margin-bottom: 12px;"></div>
                <h2 id="featureDetailName" style="margin-bottom: 24px;"></h2>
                <div class="divider" style="margin-bottom: 24px;"></div>
                <div style="text-align: left; background: var(--surface2); padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                    <h4 style="margin-top: 0; margin-bottom: 12px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                        <span>✨</span> Feature Overview
                    </h4>
                    <p id="featureDetailDesc" style="line-height: 1.7; color: var(--text-muted); white-space: pre-wrap; font-size: 0.95rem; margin: 0;"></p>
                </div>
            </div>
        </div>
    </div>
    <script>
    if (typeof openModal !== "function") { window.openModal = function(id) { document.getElementById(id).classList.add("active"); }; }
    if (typeof closeModal !== "function") { window.closeModal = function(id) { document.getElementById(id).classList.remove("active"); }; }
    function showFeatureDetails(el) {
        document.getElementById("featureDetailIcon").textContent = el.dataset.icon;
        document.getElementById("featureDetailName").textContent = el.dataset.name;
        document.getElementById("featureDetailDesc").textContent = el.dataset.desc;
        openModal("modal-feature-details");
    }
    </script>';
}

function appointment_actions($role, $a) {
    $appointmentId = $a['appointment_code'] ?? $a['id'] ?? '';
    $appointmentStatus = $a['appointment_status'] ?? '';
    $paymentStatus = $a['payment_status'] ?? '';
    $paymentProof = trim($a['payment_proof'] ?? '');
    $paymentProofLink = $paymentProof !== '' ? app_url('uploads/receipts/' . rawurlencode($paymentProof)) : '';
    $paymentProofExt = strtolower(pathinfo($paymentProof, PATHINFO_EXTENSION));
    $refundReceipt = appointment_refund_receipt_file($a);
    $refundProofUrl = $refundReceipt !== '' ? app_url('uploads/receipts/' . rawurlencode($refundReceipt)) : '';
    $refundProofExt = strtolower(pathinfo($refundReceipt, PATHINFO_EXTENSION));
    $detailsAttrs = ' data-code="' . e($appointmentId) . '" data-patient="' . e($a['name'] ?? '') . '" data-doctor="' . e($a['doctor_name'] ?? '') . '" data-service="' . e($a['service_name'] ?? '') . '" data-date="' . e(format_date_display($a['appointment_date'] ?? '')) . '" data-time="' . e(format_time_display($a['appointment_time'] ?? '')) . '" data-notes="' . e(appointment_booking_notes($a['notes'] ?? '')) . '" data-cancel-reason="' . e(appointment_cancel_reason($a['notes'] ?? '')) . '" data-payment-notes="' . e(appointment_payment_notes($a)) . '" data-payment-method="' . e(payment_method_from_payment(['payment_method' => $a['latest_payment_method'] ?? '', 'remarks' => $a['latest_payment_remarks'] ?? ''])) . '" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-refund-proof-url="' . e($refundProofUrl) . '" data-refund-proof-ext="' . e($refundProofExt) . '" data-status="' . e($appointmentStatus) . '" data-payment="' . e($paymentStatus) . '" data-amount="RM ' . e(number_format((float)($a['amount'] ?? 0), 2)) . '"';

    if (in_array($role, ['admin', 'staff'], true)) {
        $canComplete = in_array($appointmentStatus, ['confirm', 'confirmed'], true);
        if (!in_array($appointmentStatus, ['completed', 'cancelled', 'rejected'], true)) {
            $detailsAttrs .= ' data-cancel-code="' . e($appointmentId) . '"';
        }
        if ($canComplete) {
            $detailsAttrs .= ' data-complete-url="' . e(action_url('update_status', ['id' => $appointmentId])) . '"';
        }
    }

    if ($role === 'user') {
        $paymentProofLink = $paymentProofLink !== '' ? $paymentProofLink : page_url('payment_history', $role);
        $receiptPaymentId = (int)($a['receipt_payment_id'] ?? 0);
        $payUrl = page_url('payment', $role) . '?appointment=' . urlencode($appointmentId);
        $detailsAttrs .= ' data-pay-url="' . e($payUrl) . '" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-receipt-id="' . e($receiptPaymentId) . '"';
        $menuItems = [];

        if (in_array($appointmentStatus, ['completed', 'cancelled'], true)) {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            if (in_array($paymentStatus, ['paid', 'approved', 'refund_requested', 'refund_rejected'], true) && $receiptPaymentId > 0) {
                $menuItems[] = '<button type="button" class="appt-menu-item" onclick="printAppointmentReceipt(' . e($receiptPaymentId) . ')">View Receipt</button>';
            }
            if ($paymentStatus === 'refunded') {
                $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($refundProofUrl) . '" data-proof-ext="' . e($refundProofExt) . '" data-proof-title="Refund Proof">View Refund Proof</button>';
            }
            if ($appointmentStatus === 'cancelled' && in_array($paymentStatus, ['paid', 'approved'], true) && $receiptPaymentId > 0) {
                $menuItems[] = '<button type="button" class="appt-menu-item" onclick="requestAppointmentRefund(' . e($receiptPaymentId) . ')">Request Refund</button>';
            }
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'pending') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<a class="appt-menu-item" href="' . e($payUrl) . '">Pay</a>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'rejected') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<a class="appt-menu-item" href="' . e($payUrl) . '">Retry Payment</a>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'verifying') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '">View Payment Proof</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'paid') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="printAppointmentReceipt(' . e($receiptPaymentId) . ')">View Receipt</button>';
            if ($receiptPaymentId > 0) {
                $menuItems[] = '<button type="button" class="appt-menu-item" onclick="requestAppointmentRefund(' . e($receiptPaymentId) . ')">Request Refund</button>';
            }
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && in_array($paymentStatus, ['refund_requested', 'refund_rejected'], true)) {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            if ($receiptPaymentId > 0) {
                $menuItems[] = '<button type="button" class="appt-menu-item" onclick="printAppointmentReceipt(' . e($receiptPaymentId) . ')">View Receipt</button>';
            }
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        } elseif (in_array($appointmentStatus, ['confirm', 'confirmed'], true) && $paymentStatus === 'refunded') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($refundProofUrl) . '" data-proof-ext="' . e($refundProofExt) . '" data-proof-title="Refund Proof">View Refund Proof</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openEditNotesModal(this)" data-code="' . e($appointmentId) . '" data-notes="' . e($a['notes'] ?? '') . '">Edit Notes</button>';
            $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        } else {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>';
        }

        return '<div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleAppointmentMenu(event, this)" aria-label="Appointment actions">...</button><div class="appt-menu-list">' . implode('', $menuItems) . '</div></div>';
    }
    if (in_array($role, ['admin', 'staff'], true) && !in_array($appointmentStatus, ['completed', 'cancelled', 'rejected'], true)) {
        $canComplete = in_array($appointmentStatus, ['confirm', 'confirmed'], true);
        $menuItems = ['<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>'];
        if ($paymentProofLink !== '' && in_array($paymentStatus, ['verifying', 'paid', 'approved', 'refund_requested', 'refund_rejected'], true)) {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-proof-title="Payment Proof">View Payment Proof</button>';
        }
        if ($refundProofUrl !== '' && $paymentStatus === 'refunded') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($refundProofUrl) . '" data-proof-ext="' . e($refundProofExt) . '" data-proof-title="Refund Proof">View Refund Proof</button>';
        }
        if ($canComplete) {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openCompleteAppointmentModal(this.dataset.completeUrl)" data-complete-url="' . e(action_url('update_status', ['id' => $appointmentId])) . '">Complete</button>';
        }
        $menuItems[] = '<button type="button" class="appt-menu-item danger" onclick="openCancelAppointmentModal(this)" data-code="' . e($appointmentId) . '">Cancel</button>';
        return '<div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleAppointmentMenu(event, this)" aria-label="Appointment actions">...</button><div class="appt-menu-list">' . implode('', $menuItems) . '</div></div>';
    }
    if (in_array($role, ['admin', 'staff'], true)) {
        $menuItems = ['<button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button>'];
        if ($paymentProofLink !== '' && in_array($paymentStatus, ['verifying', 'paid', 'approved', 'refund_requested', 'refund_rejected'], true)) {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($paymentProofLink) . '" data-proof-ext="' . e($paymentProofExt) . '" data-proof-title="Payment Proof">View Payment Proof</button>';
        }
        if ($refundProofUrl !== '' && $paymentStatus === 'refunded') {
            $menuItems[] = '<button type="button" class="appt-menu-item" onclick="openPaymentProofModal(this)" data-proof-url="' . e($refundProofUrl) . '" data-proof-ext="' . e($refundProofExt) . '" data-proof-title="Refund Proof">View Refund Proof</button>';
        }
        return '<div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleAppointmentMenu(event, this)" aria-label="Appointment actions">...</button><div class="appt-menu-list">' . implode('', $menuItems) . '</div></div>';
    }
    return '<div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleAppointmentMenu(event, this)" aria-label="Appointment actions">...</button><div class="appt-menu-list"><button type="button" class="appt-menu-item" onclick="showAppointmentDetails(this)"' . $detailsAttrs . '>View Details</button></div></div>';
}

// ============================================
// RENDER APPOINTMENTS - UPDATED VERSION (KEMAS & RESPONSIVE)
// ============================================

function render_appointments($role) {
    global $conn;
    $appointments = get_appointments($conn, $role, null, null, true);

    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control" type="text" placeholder="Search appointments..." id="searchAppointment"></div><div class="filter-group"><input class="form-control" type="date" id="filterDate" style="width:160px"><select class="filter-select" id="filterStatus"><option value="">All Status</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option><option value="pending">Pending</option><option value="unpaid">Unpaid</option><option value="verifying">Verifying</option><option value="paid">Paid</option><option value="rejected">Rejected</option><option value="refund_requested">Refund Requested</option><option value="refunded">Refunded</option><option value="refund_rejected">Refund Rejected</option></select></div>';
    if ($role === 'user') {
        echo '</div><div class="card"><div class="card-body" style="padding:0; overflow-x:auto"><table class="appointments-table" style="width:100%; border-collapse:collapse; min-width:900px"><thead><tr><th>ID</th><th>User</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead><tbody id="appointmentsTableBody">';
    } else {
        echo '</div><div class="card"><div class="card-body" style="padding:0; overflow-x:auto"><table class="appointments-table" style="width:100%; border-collapse:collapse; min-width:860px"><thead><tr><th>ID</th><th>User</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Payment</th><th>Actions</th></tr></thead><tbody id="appointmentsTableBody">';
    }

    if (empty($appointments)) {
        echo '<tr><td colspan="' . ($role === 'user' ? '9' : '8') . '" style="text-align:center">No appointments found.</td></tr>';
    }

    foreach ($appointments as $a) {
        if ($role === 'user') {
            echo '<tr data-appointment-status="' . e(strtolower((string)$a['appointment_status'])) . '" data-payment-status="' . e(strtolower((string)$a['payment_status'])) . '"><td>' . e($a['appointment_code']) . '</td><td>' . e($a['name']) . '</td><td>' . e($a['doctor_name']) . '</td><td>' . e($a['service_name']) . '</td><td data-date="' . e($a['appointment_date']) . '">' . e(format_date_display($a['appointment_date'])) . '</td><td>' . e(format_time_display($a['appointment_time'])) . '</td><td>' . appointment_badge($a['appointment_status'], $role) . '</td><td class="appt-payment-status-cell">' . badge($a['payment_status']) . '</td><td class="appt-actions-cell">' . appointment_actions($role, $a) . '</td></tr>';
        } else {
            echo '<tr data-appointment-status="' . e(strtolower((string)$a['appointment_status'])) . '" data-payment-status="' . e(strtolower((string)$a['payment_status'])) . '"><td>' . e($a['appointment_code']) . '</td><td>' . e($a['name']) . '</td><td>' . e($a['doctor_name']) . '</td><td data-date="' . e($a['appointment_date']) . '">' . e(format_date_display($a['appointment_date'])) . '</td><td>' . e(format_time_display($a['appointment_time'])) . '</td><td>' . appointment_badge($a['appointment_status'], $role) . '</td><td class="appt-payment-status-cell">' . badge($a['payment_status']) . '</td><td class="appt-actions-cell">' . appointment_actions($role, $a) . '</td></tr>';
        }
    }

    echo '</tbody></table></div></div>';
    echo '<div class="modal-overlay" id="modal-appointment-details"><div class="modal appointment-details-modal"><div class="modal-header"><span class="modal-title">Appointment Details</span><button class="modal-close appointment-modal-close" onclick="closeModal(\'modal-appointment-details\')">×</button></div><div class="modal-body"><div class="appointment-detail-code"><span>Appointment ID</span><strong id="detailAppointmentCode"></strong></div><div class="appointment-detail-list"><div><span>Patient</span><strong id="detailPatient"></strong></div><div><span>Doctor</span><strong id="detailDoctor"></strong></div><div><span>Service</span><strong id="detailService"></strong></div><div><span>Date</span><strong id="detailDate"></strong></div><div><span>Time</span><strong id="detailTime"></strong></div><div><span>Status</span><strong id="detailStatus"></strong></div><div><span>Payment</span><strong id="detailPayment"></strong></div><div><span>Payment Method</span><strong id="detailPaymentMethod"></strong></div><div><span>Amount</span><strong class="detail-amount" id="detailAmount"></strong></div><div class="appointment-detail-notes"><span>Notes</span><strong id="detailNotes"></strong></div></div><div class="appointment-detail-actions"><button type="button" class="btn btn-danger" id="detailCancelAction" style="display:none;width:auto">Cancel</button><button type="button" class="btn btn-outline" id="detailPaymentAction" style="display:none;width:auto"></button></div></div></div></div>';
    if ($role === 'user') {
        echo '<div class="modal-overlay" id="modal-edit-notes"><div class="modal"><div class="modal-header"><span class="modal-title">Edit Notes</span><button class="modal-close" onclick="closeModal(\'modal-edit-notes\')">×</button></div><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="save_appointment_notes"><input type="hidden" name="appointment_code" id="editNotesAppointmentCode"><div class="modal-body"><div class="form-group"><label for="editAppointmentNotes">Notes</label><textarea class="form-control" id="editAppointmentNotes" name="notes" rows="7"></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-edit-notes\')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>';
        echo '<div class="modal-overlay" id="modal-cancel-appointment"><div class="modal"><div class="modal-header"><span class="modal-title">Cancel Appointment</span><button class="modal-close" onclick="closeModal(\'modal-cancel-appointment\')">×</button></div><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="cancel_appointment"><input type="hidden" name="id" id="cancelAppointmentCode"><div class="modal-body"><p class="text-muted mb-16">Are you sure you want to cancel this appointment?</p><div class="form-group"><label for="cancelAppointmentReason">Reason</label><textarea class="form-control" id="cancelAppointmentReason" name="reason" rows="4" placeholder="Please tell us why you are cancelling..." required></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-cancel-appointment\')">Keep Appointment</button><button class="btn btn-danger" style="width:auto">Cancel Appointment</button></div></form></div></div>';
        echo '<div class="modal-overlay" id="modal-request-refund"><div class="modal"><div class="modal-header"><span class="modal-title">Request Refund</span><button class="modal-close" onclick="closeModal(\'modal-request-refund\')">×</button></div><form onsubmit="submitAppointmentRefund(event)"><input type="hidden" id="refundPaymentId"><div class="modal-body"><p class="text-muted mb-16">Please provide a reason for your refund request.</p><div class="form-group"><label for="refundAppointmentReason">Reason</label><textarea class="form-control" id="refundAppointmentReason" rows="4" placeholder="Please tell us why you are requesting a refund..." required></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-request-refund\')">Cancel</button><button class="btn btn-primary" style="width:auto">Request Refund</button></div></form></div></div>';
        echo '<div class="modal-overlay" id="modal-payment-proof"><div class="modal payment-proof-modal"><div class="modal-header"><span class="modal-title">Payment Proof</span><button class="modal-close" onclick="closeModal(\'modal-payment-proof\')">×</button></div><div class="modal-body"><div class="payment-proof-card" id="paymentProofContent"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" style="width:auto" onclick="closeModal(\'modal-payment-proof\')">Close</button></div></div></div>';
        echo '<div class="modal-overlay" id="modal-appointment-receipt"><div class="modal receipt-modal"><div class="modal-header"><span class="modal-title">Payment Receipt</span><button class="modal-close" onclick="closeModal(\'modal-appointment-receipt\')">×</button></div><div class="modal-body" id="appointmentReceiptContent"></div><div class="modal-footer"><button class="btn btn-primary" style="width:auto" onclick="window.print()">Print</button><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-appointment-receipt\')">Close</button></div></div></div>';
    }
    if ($role !== 'user') {
        echo '<div class="modal-overlay" id="modal-cancel-appointment"><div class="modal"><div class="modal-header"><span class="modal-title">Cancel Appointment</span><button class="modal-close" onclick="closeModal(\'modal-cancel-appointment\')">×</button></div><form method="post" action="' . e(app_url('action.php')) . '"><input type="hidden" name="action" value="cancel_appointment"><input type="hidden" name="id" id="cancelAppointmentCode"><div class="modal-body"><p class="text-muted mb-16">Are you sure you want to cancel this appointment?</p><div class="form-group"><label for="cancelAppointmentReason">Reason</label><textarea class="form-control" id="cancelAppointmentReason" name="reason" rows="4" placeholder="Please tell us why you are cancelling..." required></textarea></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-cancel-appointment\')">Keep Appointment</button><button class="btn btn-danger" style="width:auto">Cancel Appointment</button></div></form></div></div>';
        echo '<div class="modal-overlay" id="modal-payment-proof"><div class="modal payment-proof-modal"><div class="modal-header"><span class="modal-title">Payment Proof</span><button class="modal-close" onclick="closeModal(\'modal-payment-proof\')">×</button></div><div class="modal-body"><div class="payment-proof-card" id="paymentProofContent"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary" style="width:auto" onclick="closeModal(\'modal-payment-proof\')">Close</button></div></div></div>';
        echo '<div class="modal-overlay" id="modal-complete-appointment"><div class="modal"><div class="modal-header"><span class="modal-title">Complete Appointment</span><button class="modal-close" onclick="closeModal(\'modal-complete-appointment\')">×</button></div><div class="modal-body"><p class="text-muted mb-16">Are you sure this appointment has been completed?</p></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-complete-appointment\')">Cancel</button><a class="btn btn-teal" id="confirmCompleteAppointmentAction" style="width:auto" href="#">Complete</a></div></div></div>';
    }
    echo '<script>
    function formatStatusLabel(status) {
        const labels = { unpaid: "Unpaid", paid: "Paid" };
        if (labels[status]) return labels[status];
        return status ? status.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase()) : "";
    }
    function setDetailBadge(id, status) {
        const target = document.getElementById(id);
        if (!target) return;
        const badgeClass = status.replace(/_/g, "-");
        target.innerHTML = "<span class=\"badge badge-" + badgeClass + "\">" + formatStatusLabel(status) + "</span>";
    }
    function trimDetailNotes(notes) {
        notes = (notes || "").trim();
        if (!notes) return "-";
        return notes.length > 120 ? notes.slice(0, 120) + "..." : notes;
    }
    function setDetailPaymentAction(button) {
        const action = document.getElementById("detailPaymentAction");
        const cancelAction = document.getElementById("detailCancelAction");
        let refundAction = document.getElementById("detailRefundAction");
        if (!refundAction && action) {
            refundAction = document.createElement("button");
            refundAction.type = "button";
            refundAction.id = "detailRefundAction";
            refundAction.textContent = "Request Refund";
            action.parentNode.insertBefore(refundAction, action);
        }
        if (!action) return;
        const status = button.dataset.status || "";
        const payment = button.dataset.payment || "";
        const isCancelled = status === "cancelled";
        if (refundAction) {
            refundAction.style.display = "none";
            refundAction.onclick = null;
            refundAction.className = "btn btn-outline";
            refundAction.style.width = "auto";
        }
        if (cancelAction) {
            cancelAction.style.display = "none";
            cancelAction.onclick = null;
            if (button.dataset.cancelCode) {
                cancelAction.onclick = function () {
                    closeModal("modal-appointment-details");
                    openCancelAppointmentModal({ dataset: { code: button.dataset.cancelCode } });
                };
                cancelAction.style.display = "inline-flex";
            }
        }
        if (!button.dataset.payUrl && !button.dataset.proofUrl && !button.dataset.receiptId && !button.dataset.refundProofUrl && !button.dataset.completeUrl) {
            action.style.display = "none";
            action.onclick = null;
            return;
        }

        action.style.display = "none";
        action.onclick = null;
        action.className = "btn btn-primary";
        action.style.width = "auto";

        if (button.dataset.completeUrl) {
            action.textContent = "Complete";
            action.className = "btn btn-teal";
            action.onclick = function () { openCompleteAppointmentModal(button.dataset.completeUrl); };
        } else if (!isCancelled && payment === "pending") {
            action.textContent = "Pay";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (!isCancelled && payment === "rejected") {
            action.textContent = "Retry Payment";
            action.onclick = function () { window.location.href = button.dataset.payUrl || ""; };
        } else if (payment === "verifying") {
            action.textContent = "View Payment Proof";
            action.onclick = function () {
                openPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "");
            };
        } else if (["paid", "refund_requested", "refund_rejected"].includes(payment)) {
            if ("' . e($role) . '" === "staff") {
                action.textContent = "View Payment Proof";
                action.onclick = function () {
                    openPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "");
                };
            } else {
                action.textContent = "View Receipt";
                action.onclick = function () {
                    printAppointmentReceipt(Number(button.dataset.receiptId || 0));
                };
            }
        } else if (payment === "refunded") {
            action.textContent = "View Refund Proof";
            action.onclick = function () {
                openPaymentProof(button.dataset.refundProofUrl || "", button.dataset.refundProofExt || "", "Refund Proof");
            };
        } else {
            action.style.display = "none";
            action.onclick = null;
        }
        if (action.onclick) action.style.display = "inline-flex";
        if (
            refundAction
            && "' . e($role) . '" === "user"
            && ["paid", "approved"].includes(payment)
            && ["confirm", "confirmed", "cancelled"].includes(status)
            && Number(button.dataset.receiptId || 0) > 0
        ) {
            refundAction.onclick = function () {
                closeModal("modal-appointment-details");
                requestAppointmentRefund(Number(button.dataset.receiptId || 0));
            };
            refundAction.style.display = "inline-flex";
        }
    }
    function showAppointmentDetails(button) {
        document.getElementById("detailAppointmentCode").textContent = button.dataset.code || "";
        document.getElementById("detailPatient").textContent = button.dataset.patient || "";
        document.getElementById("detailDoctor").textContent = button.dataset.doctor || "";
        document.getElementById("detailService").textContent = button.dataset.service || "";
        document.getElementById("detailDate").textContent = button.dataset.date || "";
        document.getElementById("detailTime").textContent = button.dataset.time || "";
        document.getElementById("detailPaymentMethod").textContent = button.dataset.paymentMethod || "-";
        document.getElementById("detailNotes").textContent = trimDetailNotes(button.dataset.notes);
        const cancelReason = (button.dataset.cancelReason || "").trim();
        let cancelWrap = document.getElementById("detailCancelReasonWrap");
        if (!cancelWrap) {
            cancelWrap = document.createElement("div");
            cancelWrap.className = "appointment-detail-notes";
            cancelWrap.id = "detailCancelReasonWrap";
            cancelWrap.innerHTML = "<span>Cancel Reason</span><strong id=\"detailCancelReason\"></strong>";
            document.getElementById("detailNotes").closest(".appointment-detail-notes").after(cancelWrap);
        }
        document.getElementById("detailCancelReason").textContent = cancelReason;
        cancelWrap.style.display = cancelReason ? "" : "none";
        const paymentNotes = (button.dataset.paymentNotes || "").trim();
        let paymentNotesWrap = document.getElementById("detailPaymentNotesWrap");
        if (!paymentNotesWrap) {
            paymentNotesWrap = document.createElement("div");
            paymentNotesWrap.className = "appointment-detail-notes";
            paymentNotesWrap.id = "detailPaymentNotesWrap";
            paymentNotesWrap.innerHTML = "<span>Payment Remarks</span><strong id=\"detailPaymentNotes\"></strong>";
            cancelWrap.after(paymentNotesWrap);
        }
        document.getElementById("detailPaymentNotes").textContent = paymentNotes;
        paymentNotesWrap.style.display = paymentNotes ? "" : "none";
        document.getElementById("detailAmount").textContent = button.dataset.amount || "";
        setDetailBadge("detailStatus", button.dataset.status || "");
        setDetailBadge("detailPayment", button.dataset.payment || "");
        setDetailPaymentAction(button);
        openModal("modal-appointment-details");
    }
    function openCompleteAppointmentModal(url) {
        const action = document.getElementById("confirmCompleteAppointmentAction");
        if (!action || !url) return;
        action.href = url;
        openModal("modal-complete-appointment");
    }
    function openEditNotesModal(button) {
        document.getElementById("editNotesAppointmentCode").value = button.dataset.code || "";
        document.getElementById("editAppointmentNotes").value = button.dataset.notes || "";
        openModal("modal-edit-notes");
    }
    function openCancelAppointmentModal(button) {
        document.getElementById("cancelAppointmentCode").value = button.dataset.code || "";
        document.getElementById("cancelAppointmentReason").value = "";
        openModal("modal-cancel-appointment");
    }
    function openPaymentProof(proofUrl, proofExt, title) {
        proofExt = (proofExt || "").toLowerCase();
        const content = document.getElementById("paymentProofContent");
        if (!content) return;
        const modalTitle = document.querySelector("#modal-payment-proof .modal-title");
        if (modalTitle) modalTitle.textContent = title || "Payment Proof";
        if (!proofUrl) {
            content.innerHTML = "<p class=\"text-muted text-center\">No payment proof uploaded.</p>";
        } else if (["jpg", "jpeg", "png", "gif", "webp"].includes(proofExt)) {
            content.innerHTML = "<img src=\"" + proofUrl + "\" alt=\"Payment proof\">";
        } else {
            content.innerHTML = "<div class=\"file-open-fallback\"><p>This payment proof file cannot be previewed here.</p><a class=\"btn btn-outline\" target=\"_blank\" rel=\"noopener\" href=\"" + proofUrl + "\">Open File</a></div>";
        }
        openModal("modal-payment-proof");
    }
    function openPaymentProofModal(button) {
        openPaymentProof(button.dataset.proofUrl || "", button.dataset.proofExt || "", button.dataset.proofTitle || "");
    }
    function showAppointmentNotification(message, type = "success", reload = false) {
        const table = document.getElementById("appointmentsTableBody");
        const container = table?.closest(".card")?.parentNode || document.querySelector(".page-content") || document.body;
        document.querySelectorAll(".appointment-flash-message").forEach(messageBox => messageBox.remove());

        const notice = document.createElement("div");
        notice.className = "toast flash-message show " + type + " appointment-flash-message";
        notice.textContent = message;
        container.prepend(notice);
        notice.scrollIntoView({ block: "nearest", behavior: "smooth" });

        if (reload) {
            setTimeout(function () {
                window.location.reload();
            }, 5000);
            return;
        }

        setTimeout(function () {
            notice.classList.add("hiding");
            setTimeout(function () {
                notice.remove();
            }, 350);
        }, 5000);
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
    async function requestAppointmentRefund(paymentId) {
        if (!paymentId) {
            alert("Payment record not found.");
            return;
        }
        document.getElementById("refundPaymentId").value = paymentId;
        document.getElementById("refundAppointmentReason").value = "";
        openModal("modal-request-refund");
    }
    async function submitAppointmentRefund(event) {
        event.preventDefault();
        const paymentId = document.getElementById("refundPaymentId").value;
        const reason = document.getElementById("refundAppointmentReason").value;
        if (!reason.trim()) {
            showAppointmentNotification("Please provide a reason for refund request.", "error");
            return;
        }
        const response = await fetch("' . e(app_url('action.php')) . '", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=request_refund&payment_id=" + encodeURIComponent(paymentId) + "&reason=" + encodeURIComponent(reason)
        });
        const data = await response.json();
        if (data.success) {
            closeModal("modal-request-refund");
            showAppointmentNotification("Refund request submitted. Waiting for admin approval.", "success", true);
        } else {
            showAppointmentNotification("Error: " + data.message, "error");
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
            const dateCell = row.querySelector("td[data-date]")?.dataset.date || "";
            const appointmentStatus = row.dataset.appointmentStatus || "";
            const paymentStatus = row.dataset.paymentStatus || "";
            let show = true;
            if (searchValue && !text.includes(searchValue)) show = false;
            if (filterDate && dateCell !== filterDate) show = false;
            if (filterStatus && appointmentStatus !== filterStatus && paymentStatus !== filterStatus) show = false;
            row.style.display = show ? "" : "none";
        });
    }
    document.getElementById("searchAppointment")?.addEventListener("keyup", filterAppointments);
    document.getElementById("filterDate")?.addEventListener("change", filterAppointments);
    document.getElementById("filterStatus")?.addEventListener("change", filterAppointments);
    </script>';
}

function render_book() {
    global $conn;
    $services = get_services($conn);
    $doctors = get_doctors($conn);
    $today = date('Y-m-d');
    ensure_doctor_schedule_break_columns($conn);
    $scheduleRows = fetch_all_assoc(
        $conn,
        "SELECT d.doctor_name, ds.available_day, ds.start_time, ds.end_time, ds.break_start_time, ds.break_end_time
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
        $breakStart = !empty($row['break_start_time']) ? (DateTime::createFromFormat('H:i:s', $row['break_start_time']) ?: DateTime::createFromFormat('H:i', $row['break_start_time'])) : null;
        $breakEnd = !empty($row['break_end_time']) ? (DateTime::createFromFormat('H:i:s', $row['break_end_time']) ?: DateTime::createFromFormat('H:i', $row['break_end_time'])) : null;
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
            $isBreak = $breakStart && $breakEnd && $breakStart < $breakEnd && $slot >= $breakStart && $slot < $breakEnd;
            if (!$isBreak) {
                $doctorSchedules[$doctorName][$day][] = $slot->format('H:i');
            }
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
    ensure_doctor_time_locks_table($conn);
    $lockedRows = fetch_all_assoc(
        $conn,
        "SELECT d.doctor_name, dtl.lock_date, dtl.start_time, dtl.end_time, dtl.reason
         FROM doctor_time_locks dtl
         INNER JOIN doctors d ON d.doctor_id = dtl.doctor_id
         WHERE dtl.lock_date >= CURDATE()
         ORDER BY dtl.lock_date ASC, dtl.start_time ASC"
    );
    $lockedSlots = [];
    foreach ($lockedRows as $row) {
        $doctorName = $row['doctor_name'] ?? '';
        $lockDate = $row['lock_date'] ?? '';
        if ($doctorName === '' || $lockDate === '') {
            continue;
        }
        if (!isset($lockedSlots[$doctorName])) {
            $lockedSlots[$doctorName] = [];
        }
        if (!isset($lockedSlots[$doctorName][$lockDate])) {
            $lockedSlots[$doctorName][$lockDate] = [];
        }
        $lockedSlots[$doctorName][$lockDate][] = [
            'start' => !empty($row['start_time']) ? date('H:i', strtotime($row['start_time'])) : null,
            'end' => !empty($row['end_time']) ? date('H:i', strtotime($row['end_time'])) : null,
            'reason' => $row['reason'] ?? '',
        ];
    }
    $lockedSlotsJson = json_encode($lockedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    echo '<form id="bookingWizardForm" class="booking-wizard" method="post" action="' . e(app_url('action.php')) . '">';
    echo '<input type="hidden" name="action" value="book_appointment">';
    echo '<input type="hidden" name="service" id="selectedServiceInput" value="">';
    echo '<input type="hidden" name="doctor" id="selectedDoctorInput" value="">';
    echo '<input type="hidden" name="time" id="selectedTimeInput" value="">';
    echo '<div class="toast booking-notice" id="bookingNotice" aria-live="polite"></div>';

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
        $icon = get_service_icon($s['service_name']);
        echo '<button type="button" class="book-service-card js-select-service" data-service-id="' . e((int)($s['service_id'] ?? 0)) . '" data-service-name="' . e($s['service_name']) . '" data-service-price="' . e($priceText) . '" data-service-description="' . e($s['service_description']) . '">';
        echo '<span class="service-icon">' . e($icon) . '</span>';
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
        echo '<button type="button" class="book-doctor-card js-select-doctor" data-doctor-name="' . e($d['doctor_name']) . '" data-doctor-specialist="' . e($d['doctor_specialist']) . '" data-service-ids="' . e($d['service_ids'] ?? '') . '">';
        echo doctor_avatar_html($d);
        echo '<div class="doctor-name">' . e($d['doctor_name']) . '</div>';
        echo '<div class="doctor-spec">' . e($d['doctor_specialist']) . '</div>';
        echo '<div class="doctor-avail">&#9989; ' . e($availableDays) . '</div>';
        echo '</button>';
    }
    echo '</div><div class="book-empty-slots" id="bookDoctorEmpty" style="display:none">No doctors are available for this service.</div>';
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
        var lockedSlots = ' . ($lockedSlotsJson ?: '{}') . ';
        var minDate = ' . json_encode($today) . ';
        var steps = wizard.querySelectorAll(".booking-step");
        var panels = wizard.querySelectorAll(".booking-panel");
        var timeGrid = document.getElementById("bookTimeGrid");
        var bookingNotice = document.getElementById("bookingNotice");
        var bookingNoticeTimer = null;
        var state = {
            services: [],
            servicePriceTotal: 0,
            selectedServiceId: "",
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

        function showBookingNotice(message, type) {
            if (!bookingNotice) return;
            bookingNotice.textContent = message;
            bookingNotice.className = "toast booking-notice show " + (type || "success");
            if (bookingNoticeTimer) {
                clearTimeout(bookingNoticeTimer);
            }
            bookingNoticeTimer = setTimeout(function () {
                bookingNotice.classList.remove("show");
            }, 5000);
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

        function lockedReasonForSlot(slot) {
            var locks = ((lockedSlots[state.doctor] || {})[state.date] || []);
            var slotParts = slot.split(":");
            var slotEndMinutes = (Number(slotParts[0]) * 60) + Number(slotParts[1]) + 30;
            for (var i = 0; i < locks.length; i++) {
                var item = locks[i] || {};
                if (!item.start || !item.end) {
                    return item.reason || "Doctor unavailable";
                }
                var startParts = item.start.split(":");
                var endParts = item.end.split(":");
                var lockStartMinutes = (Number(startParts[0]) * 60) + Number(startParts[1]);
                var lockEndMinutes = (Number(endParts[0]) * 60) + Number(endParts[1]);
                if (lockStartMinutes < slotEndMinutes && lockEndMinutes > ((Number(slotParts[0]) * 60) + Number(slotParts[1]))) {
                    return item.reason || "Doctor unavailable";
                }
            }
            return "";
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
                var lockReason = lockedReasonForSlot(slot);
                var isLocked = lockReason !== "";
                btn.type = "button";
                btn.className = "book-time-slot js-time-slot" + ((isBooked || isLocked) ? " unavailable" : "");
                btn.dataset.time = slot;
                btn.textContent = slot;
                btn.disabled = isBooked || isLocked;
                if (isBooked || isLocked) {
                    btn.title = isBooked ? "Already booked" : lockReason;
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
            var selectedServiceId = "";
            selected.forEach(function (item) {
                names.push(item.getAttribute("data-service-name") || "");
                total += parseFloat(item.getAttribute("data-service-price") || "0") || 0;
                selectedServiceId = item.getAttribute("data-service-id") || "";
            });
            state.services = names.filter(function (name) { return name !== ""; });
            state.servicePriceTotal = total;
            state.selectedServiceId = selectedServiceId;
            serviceInput.value = state.services.join(", ");
        }

        function doctorMatchesSelectedService(doctorCard) {
            if (!state.selectedServiceId) return true;
            var serviceIds = (doctorCard.getAttribute("data-service-ids") || "").split(",").filter(Boolean);
            return serviceIds.includes(state.selectedServiceId);
        }

        function filterDoctorsForService() {
            var hasSelectedDoctor = false;
            var visibleDoctorCount = 0;
            wizard.querySelectorAll(".js-select-doctor").forEach(function (btn) {
                var matches = doctorMatchesSelectedService(btn);
                btn.style.display = matches ? "" : "none";
                if (matches) {
                    visibleDoctorCount++;
                }
                if (!matches && btn.classList.contains("selected")) {
                    btn.classList.remove("selected");
                    state.doctor = "";
                    state.doctorSpecialist = "";
                    doctorInput.value = "";
                    renderTimeSlots();
                }
                if (matches && btn.classList.contains("selected")) {
                    hasSelectedDoctor = true;
                }
            });
            if (!hasSelectedDoctor && state.doctor) {
                state.doctor = "";
                state.doctorSpecialist = "";
                doctorInput.value = "";
                renderTimeSlots();
            }
            var doctorEmpty = document.getElementById("bookDoctorEmpty");
            if (doctorEmpty) {
                doctorEmpty.style.display = visibleDoctorCount === 0 ? "" : "none";
            }
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
                alert("Please select a service first.");
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
                if (minDate && state.date < minDate) {
                    showBookingNotice("Please choose today or a future appointment date.", "error");
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
                wizard.querySelectorAll(".js-select-service").forEach(function (item) {
                    item.classList.remove("selected");
                });
                btn.classList.add("selected");
                updateServiceState();
                filterDoctorsForService();
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
                return;
            }
            if (minDate && state.date < minDate) {
                event.preventDefault();
                showBookingNotice("Please choose today or a future appointment date.", "error");
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
    $currentMonth = (int)date('n');
    $currentYear = (int)date('Y');
    $yearRows = fetch_all_assoc(
        $conn,
        "SELECT DISTINCT YEAR(report_date) AS report_year
         FROM (
            SELECT appointment_date AS report_date FROM appointments
            UNION ALL
            SELECT payment_date AS report_date FROM payments
         ) report_dates
         WHERE report_date IS NOT NULL
         ORDER BY report_year DESC"
    );

    $summary = fetch_all_assoc(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(appointment_status = 'completed') AS completed,
            SUM(appointment_status IN ('confirmed', 'confirm')) AS confirmed,
            SUM(appointment_status = 'cancelled') AS cancelled,
            COALESCE(SUM(amount), 0) AS appointment_value
         FROM appointments"
    )[0] ?? ['total' => 0, 'completed' => 0, 'confirmed' => 0, 'cancelled' => 0, 'appointment_value' => 0];

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

    $appointmentRows = fetch_all_assoc(
        $conn,
        "SELECT appointment_code, name, doctor_name, service_name, appointment_date, appointment_time,
                appointment_status, payment_status, amount
         FROM appointments
         ORDER BY appointment_date DESC, appointment_time DESC, appointment_id DESC"
    );

    $paymentSummary = fetch_all_assoc(
        $conn,
        "SELECT
            COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'approved') THEN amount ELSE 0 END), 0) AS revenue,
            SUM(payment_status IN ('paid', 'approved')) AS paid_count,
            COALESCE(SUM(CASE WHEN payment_status IN ('pending', 'verifying') THEN amount ELSE 0 END), 0) AS pending_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'refunded' THEN amount ELSE 0 END), 0) AS refunded_amount,
            SUM(payment_status = 'refunded') AS refunded_count
         FROM payments"
    )[0] ?? ['revenue' => 0, 'paid_count' => 0, 'pending_amount' => 0, 'refunded_amount' => 0, 'refunded_count' => 0];

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
    $paymentRows = get_all_payments();
    $completionRate = (int)($summary['total'] ?? 0) > 0 ? round(((int)($summary['completed'] ?? 0) / (int)$summary['total']) * 100) : 0;

    echo '<style>
    .report-page { display: flex; flex-direction: column; gap: 18px; }
    .report-tabs { position: relative; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: min(100%, 390px); padding: 6px; background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
    .report-tabs::before { content: ""; position: absolute; top: 6px; bottom: 6px; left: 6px; width: calc((100% - 12px) / 2); background: var(--primary); border-radius: 7px; transition: transform 0.28s ease; }
    .report-tabs.payments-active::before { transform: translateX(100%); }
    .report-tab { position: relative; z-index: 1; min-height: 42px; border: 0; background: transparent; color: var(--text-muted); padding: 8px 14px; border-radius: 7px; font-weight: 700; font-size: 0.95rem; line-height: 1.15; text-align: center; white-space: nowrap; cursor: pointer; transition: color 0.2s ease; }
    .report-tab.active { color: #fff; }
    .report-panel { display: none; }
    .report-panel.active { display: block; }
    .report-hero { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .report-metric { background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: 18px; box-shadow: var(--shadow); }
    .report-metric .metric-label { color: var(--text-muted); font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .report-metric .metric-value { color: var(--primary); font-size: 26px; font-weight: 800; margin-top: 8px; }
    .report-section-title { font-size: 18px; font-weight: 800; margin-bottom: 12px; }
    .report-table-wrap { overflow-x: auto; }
    .report-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    @media (max-width: 900px) { .report-hero { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .report-hero { grid-template-columns: 1fr; } .report-tabs { width: 100%; } .report-tab { padding: 8px 10px; font-size: 0.88rem; } }
    </style>';

    echo '<div class="report-page">';
    echo '<div class="toolbar"><div class="report-tabs" role="tablist"><button type="button" class="report-tab active" data-report-tab="appointments">Appointment Report</button><button type="button" class="report-tab" data-report-tab="payments">Payment Report</button></div><div class="report-actions"><select class="filter-select" id="reportPeriod"><option value="monthly" selected>Monthly</option><option value="yearly">Yearly</option></select><select class="filter-select" id="reportMonth">';
    for ($month = 1; $month <= 12; $month++) {
        echo '<option value="' . e($month) . '"' . ($month === $currentMonth ? ' selected' : '') . '>' . e(date('F', mktime(0, 0, 0, $month, 1))) . '</option>';
    }
    echo '</select><select class="filter-select" id="reportYear">';
    if (empty($yearRows)) {
        echo '<option value="' . e($currentYear) . '">' . e($currentYear) . '</option>';
    }
    foreach ($yearRows as $row) {
        $year = (int)($row['report_year'] ?? $currentYear);
        echo '<option value="' . e($year) . '"' . ($year === $currentYear ? ' selected' : '') . '>' . e($year) . '</option>';
    }
    echo '</select></div></div>';

    echo '<section class="report-panel active" id="report-panel-appointments">';
    echo '<div class="report-hero">';
    echo '<div class="report-metric"><div class="metric-label">Total Appointments</div><div class="metric-value">' . e((int)($summary['total'] ?? 0)) . '</div></div>';
    echo '<div class="report-metric"><div class="metric-label">Completed</div><div class="metric-value">' . e((int)($summary['completed'] ?? 0)) . '</div></div>';
    echo '<div class="report-metric"><div class="metric-label">Completion Rate</div><div class="metric-value">' . e($completionRate) . '%</div></div>';
    echo '<div class="report-metric"><div class="metric-label">Service Value</div><div class="metric-value">RM ' . e(number_format((float)($summary['appointment_value'] ?? 0), 2)) . '</div></div>';
    echo '</div>';
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control report-search" data-target="appointmentReportRows" type="text" placeholder="Search appointments..."></div><div class="filter-group"><select class="filter-select report-status-filter" data-target="appointmentReportRows"><option value="all">All Status</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select><a class="btn btn-sm btn-outline report-export-link" data-report-type="appointments" href="' . e(action_url('export_report', ['report_type' => 'appointments'])) . '">⬇ Export Appointment Report</a></div></div>';
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Monthly Appointment Performance</span></div><div class="card-body report-table-wrap"><table><thead><tr><th>Month</th><th>Total</th><th>Completed</th><th>Rate</th></tr></thead><tbody>';
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
    echo '</tbody></table></div></div>';
    echo '<div class="card"><div class="card-header"><span class="card-title">Appointment Details</span></div><div class="card-body report-table-wrap" style="padding:0"><table><thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Service</th><th>Date</th><th>Status</th><th>Payment</th><th>Amount</th></tr></thead><tbody id="appointmentReportRows">';
    if (empty($appointmentRows)) {
        echo '<tr><td colspan="8" style="text-align:center">No appointments found.</td></tr>';
    }
    foreach ($appointmentRows as $row) {
        $status = strtolower($row['appointment_status'] ?? '');
        echo '<tr data-status="' . e($status) . '"><td>' . e($row['appointment_code'] ?? '-') . '</td><td>' . e($row['name'] ?? '-') . '</td><td>' . e($row['doctor_name'] ?? '-') . '</td><td>' . e($row['service_name'] ?? '-') . '</td><td>' . e(format_date_display($row['appointment_date'] ?? '')) . '</td><td>' . badge($status) . '</td><td>' . badge($row['payment_status'] ?? 'pending') . '</td><td>RM ' . e(number_format((float)($row['amount'] ?? 0), 2)) . '</td></tr>';
    }
    echo '</tbody></table></div></div></div></section>';

    echo '<section class="report-panel" id="report-panel-payments">';
    echo '<div class="report-hero">';
    echo '<div class="report-metric"><div class="metric-label">Total Revenue</div><div class="metric-value">RM ' . e(number_format((float)($paymentSummary['revenue'] ?? 0), 2)) . '</div></div>';
    echo '<div class="report-metric"><div class="metric-label">Paid Receipts</div><div class="metric-value">' . e((int)($paymentSummary['paid_count'] ?? 0)) . '</div></div>';
    echo '<div class="report-metric"><div class="metric-label">Pending Amount</div><div class="metric-value">RM ' . e(number_format((float)($paymentSummary['pending_amount'] ?? 0), 2)) . '</div></div>';
    echo '<div class="report-metric"><div class="metric-label">Refunded</div><div class="metric-value">RM ' . e(number_format((float)($paymentSummary['refunded_amount'] ?? 0), 2)) . '</div></div>';
    echo '</div>';
    echo '<div class="toolbar"><div class="search-input-wrap"><span class="search-icon">🔍</span><input class="form-control report-search" data-target="paymentReportRows" type="text" placeholder="Search payments..."></div><div class="filter-group"><select class="filter-select report-status-filter" data-target="paymentReportRows"><option value="all">All Status</option><option value="pending">Pending</option><option value="verifying">Verifying</option><option value="approved">Paid</option><option value="rejected">Rejected</option><option value="refund_requested">Refund Requested</option><option value="refunded">Refunded</option><option value="refund_rejected">Refund Rejected</option></select><a class="btn btn-sm btn-outline report-export-link" data-report-type="payments" href="' . e(action_url('export_report', ['report_type' => 'payments'])) . '">⬇ Export Payment Report</a></div></div>';
    echo '<div class="grid-2"><div class="card"><div class="card-header"><span class="card-title">Monthly Revenue Performance</span></div><div class="card-body report-table-wrap"><table><thead><tr><th>Month</th><th>Revenue</th><th>Paid Receipts</th></tr></thead><tbody>';
    if (empty($paymentMonthlyRows)) {
        echo '<tr><td colspan="3" style="text-align:center">No payment data found.</td></tr>';
    }
    foreach ($paymentMonthlyRows as $row) {
        echo '<tr><td>' . e($row['month_label']) . '</td><td>RM ' . e(number_format((float)($row['revenue'] ?? 0), 2)) . '</td><td>' . e((int)($row['invoices'] ?? 0)) . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<div class="card"><div class="card-header"><span class="card-title">Payment Details</span></div><div class="card-body report-table-wrap" style="padding:0"><table><thead><tr><th>Date</th><th>Receipt #</th><th>Patient</th><th>Appointment</th><th>Amount</th><th>Transaction ID</th><th>Status</th></tr></thead><tbody id="paymentReportRows">';
    if (empty($paymentRows)) {
        echo '<tr><td colspan="7" style="text-align:center">No payments found.</td></tr>';
    }
    foreach ($paymentRows as $row) {
        $status = strtolower($row['payment_status'] ?? 'pending');
        echo '<tr data-status="' . e($status) . '"><td>' . e(format_date_display($row['payment_date'] ?? '')) . '</td><td>' . e($row['receipt_number'] ?? '-') . '</td><td>' . e($row['patient_name'] ?? '-') . '</td><td>' . e($row['appointment_code'] ?? '-') . '</td><td>RM ' . e(number_format((float)($row['amount'] ?? 0), 2)) . '</td><td>' . e($row['transaction_id'] ?? '-') . '</td><td>' . badge($status) . '</td></tr>';
    }
    echo '</tbody></table></div></div></div></section></div>';
    echo '<script>
    (function () {
        const monthSelect = document.getElementById("reportMonth");
        const yearSelect = document.getElementById("reportYear");
        const periodSelect = document.getElementById("reportPeriod");
        const exportLinks = document.querySelectorAll(".report-export-link");
        const tabList = document.querySelector(".report-tabs");
        const tabs = document.querySelectorAll(".report-tab");
        const panels = document.querySelectorAll(".report-panel");
        const baseUrl = ' . json_encode(action_url('export_report')) . ';
        function updateReportExportLinks() {
            exportLinks.forEach(link => {
                const params = new URLSearchParams();
                params.set("action", "export_report");
                params.set("report_type", link.dataset.reportType || "appointments");
                params.set("period", periodSelect?.value || "monthly");
                if ((periodSelect?.value || "monthly") === "monthly") {
                    params.set("month", monthSelect?.value || "");
                }
                params.set("year", yearSelect?.value || "");
                link.href = baseUrl.split("?")[0] + "?" + params.toString();
            });
        }
        function updatePeriodControls() {
            if (monthSelect) {
                monthSelect.style.display = (periodSelect?.value || "monthly") === "yearly" ? "none" : "";
            }
            updateReportExportLinks();
        }
        function filterReportRows(targetId) {
            const search = document.querySelector(`.report-search[data-target="${targetId}"]`)?.value.toLowerCase() || "";
            const status = document.querySelector(`.report-status-filter[data-target="${targetId}"]`)?.value || "all";
            document.querySelectorAll(`#${targetId} tr`).forEach(row => {
                const matchesSearch = !search || row.innerText.toLowerCase().includes(search);
                const matchesStatus = status === "all" || row.dataset.status === status || (status === "confirmed" && row.dataset.status === "confirm");
                row.style.display = matchesSearch && matchesStatus ? "" : "none";
            });
        }
        tabs.forEach(tab => {
            tab.addEventListener("click", function () {
                const target = this.dataset.reportTab || "appointments";
                tabs.forEach(item => item.classList.toggle("active", item === this));
                panels.forEach(panel => panel.classList.toggle("active", panel.id === `report-panel-${target}`));
                tabList?.classList.toggle("payments-active", target === "payments");
            });
        });
        document.querySelectorAll(".report-search, .report-status-filter").forEach(control => {
            control.addEventListener("input", () => filterReportRows(control.dataset.target));
            control.addEventListener("change", () => filterReportRows(control.dataset.target));
        });
        monthSelect?.addEventListener("change", updateReportExportLinks);
        yearSelect?.addEventListener("change", updateReportExportLinks);
        periodSelect?.addEventListener("change", updatePeriodControls);
        updatePeriodControls();
    })();
    </script>';
}

function render_time_slots() {
    global $conn;
    ensure_doctor_time_locks_table($conn);
    $doctors = get_doctors($conn);
    $doctorScheduleMap = [];
    foreach ($doctors as $doctor) {
        $doctorId = (string)(int)($doctor['doctor_id'] ?? 0);
        $doctorScheduleMap[$doctorId] = [];
        $scheduleData = trim((string)($doctor['schedule_data'] ?? ''));
        if ($scheduleData === '') {
            continue;
        }
        foreach (explode(';;', $scheduleData) as $scheduleItem) {
            $parts = explode('|', $scheduleItem);
            if (count($parts) < 3) {
                continue;
            }
            $day = $parts[0] ?? '';
            if ($day === '') {
                continue;
            }
            if (!isset($doctorScheduleMap[$doctorId][$day])) {
                $doctorScheduleMap[$doctorId][$day] = [];
            }
            $doctorScheduleMap[$doctorId][$day][] = [
                'start' => $parts[1] ?? '',
                'end' => $parts[2] ?? '',
                'breakStart' => $parts[3] ?? '',
                'breakEnd' => $parts[4] ?? '',
            ];
        }
    }
    $doctorScheduleMapJson = json_encode($doctorScheduleMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $locks = fetch_all_assoc(
        $conn,
        "SELECT dtl.lock_id, dtl.lock_date, dtl.start_time, dtl.end_time, dtl.reason,
                d.doctor_id, d.doctor_name, d.doctor_specialist
         FROM doctor_time_locks dtl
         INNER JOIN doctors d ON d.doctor_id = dtl.doctor_id
         ORDER BY dtl.lock_date DESC, dtl.start_time ASC, dtl.lock_id DESC"
    );
    $doctorLockMap = [];
    foreach ($locks as $lock) {
        $doctorId = (string)(int)($lock['doctor_id'] ?? 0);
        $lockDate = (string)($lock['lock_date'] ?? '');
        if ($doctorId === '0' || $lockDate === '') {
            continue;
        }
        if (!isset($doctorLockMap[$doctorId])) {
            $doctorLockMap[$doctorId] = [];
        }
        if (!isset($doctorLockMap[$doctorId][$lockDate])) {
            $doctorLockMap[$doctorId][$lockDate] = [];
        }
        $doctorLockMap[$doctorId][$lockDate][] = [
            'start' => !empty($lock['start_time']) ? date('H:i', strtotime($lock['start_time'])) : null,
            'end' => !empty($lock['end_time']) ? date('H:i', strtotime($lock['end_time'])) : null,
        ];
    }
    $doctorLockMapJson = json_encode($doctorLockMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

    echo '<div class="time-lock-grid">';
    echo '<div class="card"><div class="card-header"><span class="card-title">Lock Doctor Availability</span></div><div class="card-body">';
    echo '<form method="post" action="' . e(app_url('action.php')) . '" id="timeLockForm">';
    echo '<input type="hidden" name="action" value="save_time_lock">';
    echo '<div class="grid-2">';
    echo '<div class="form-group"><label>Doctor</label><select class="form-control" name="doctor_id" required><option value="">Select doctor</option>';
    foreach ($doctors as $doctor) {
        echo '<option value="' . e((int)($doctor['doctor_id'] ?? 0)) . '">' . e($doctor['doctor_name']) . ' - ' . e($doctor['doctor_specialist']) . '</option>';
    }
    echo '</select></div>';
    echo '<div class="form-group"><label>Date</label><input class="form-control" type="date" name="lock_date" min="' . e(date('Y-m-d')) . '" required></div>';
    echo '</div>';
    echo '<label class="time-lock-all-day"><input type="checkbox" name="all_day" value="1" id="timeLockAllDay" checked> Lock whole day</label>';
    echo '<div class="grid-2 time-lock-range" id="timeLockRange">';
    echo '<div class="form-group"><label>Start Time</label><input class="form-control" type="time" name="start_time" value="09:00" step="1800"></div>';
    echo '<div class="form-group"><label>End Time</label><input class="form-control" type="time" name="end_time" value="18:00" step="1800"></div>';
    echo '</div>';
    echo '<div class="form-group"><label>Reason</label><input class="form-control" name="reason" maxlength="255" placeholder="e.g. Doctor leave, meeting, emergency"></div>';
    echo '<button class="btn btn-primary" type="submit" style="width:auto">Lock Time Slot</button>';
    echo '</form></div></div>';

    echo '<div class="card"><div class="card-header"><span class="card-title">Locked Slots</span></div><div class="card-body time-lock-help">Locked slots will be disabled on the booking page and blocked again when the appointment is submitted.</div></div>';
    echo '</div>';

    echo '<div class="card mt-20"><div class="card-body" style="padding:0"><table><thead><tr><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Action</th></tr></thead><tbody>';
    if (empty($locks)) {
        echo '<tr><td colspan="5" style="text-align:center">No locked slots found.</td></tr>';
    }
    foreach ($locks as $lock) {
        $timeText = empty($lock['start_time']) || empty($lock['end_time'])
            ? 'Whole day'
            : format_time_display($lock['start_time']) . ' - ' . format_time_display($lock['end_time']);
        echo '<tr>';
        echo '<td><strong>' . e($lock['doctor_name']) . '</strong><div class="text-muted text-sm">' . e($lock['doctor_specialist']) . '</div></td>';
        echo '<td>' . e(format_date_display($lock['lock_date'])) . '</td>';
        echo '<td>' . e($timeText) . '</td>';
        echo '<td>' . e($lock['reason'] ?: '-') . '</td>';
        echo '<td><button class="btn btn-sm btn-danger" style="width:auto" type="button" onclick="openUnlockTimeLockModal(this)" data-unlock-url="' . e(action_url('delete_time_lock', ['id' => (int)$lock['lock_id']])) . '" data-doctor="' . e($lock['doctor_name']) . '" data-date="' . e(format_date_display($lock['lock_date'])) . '" data-time="' . e($timeText) . '">Unlock</button></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<div class="modal-overlay" id="modal-unlock-time-lock"><div class="modal"><div class="modal-header"><span class="modal-title">Unlock Time Slot</span><button class="modal-close" onclick="closeModal(\'modal-unlock-time-lock\')">×</button></div><div class="modal-body"><p class="text-muted mb-16">Are you sure you want to unlock this time slot?</p><div class="appointment-detail-code"><span>Doctor</span><strong id="unlockTimeLockDoctor">-</strong></div><div class="appointment-detail-list" style="margin-top:14px"><div><span>Date</span><strong id="unlockTimeLockDate">-</strong></div><div><span>Time</span><strong id="unlockTimeLockTime">-</strong></div></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-unlock-time-lock\')">Cancel</button><a class="btn btn-danger" id="confirmUnlockTimeLockAction" style="width:auto" href="#">Unlock</a></div></div></div>';

    echo '<script>
    (function () {
        var doctorSchedules = ' . ($doctorScheduleMapJson ?: '{}') . ';
        var existingLocks = ' . ($doctorLockMapJson ?: '{}') . ';
        var form = document.getElementById("timeLockForm");
        var allDay = document.getElementById("timeLockAllDay");
        var range = document.getElementById("timeLockRange");
        function selectedDayName(dateValue) {
            var date = new Date(dateValue + "T00:00:00");
            if (Number.isNaN(date.getTime())) return "";
            return ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"][date.getDay()];
        }
        function rangeIsInsideSchedule(rows, start, end) {
            return rows.some(function (row) {
                if (!row.start || !row.end || row.start > start || row.end < end) return false;
                var hasBreak = row.breakStart && row.breakEnd && row.breakStart < row.breakEnd;
                var insideBreakOnly = hasBreak && row.breakStart <= start && row.breakEnd >= end;
                return !insideBreakOnly;
            });
        }
        function lockOverlapsExisting(doctorId, lockDate, allDaySelected, start, end) {
            var locks = ((existingLocks[doctorId] || {})[lockDate] || []);
            return locks.some(function (lock) {
                if (!lock.start || !lock.end || allDaySelected) return true;
                return lock.start < end && lock.end > start;
            });
        }
        function showTimeLockNotification(message, type) {
            var pageContent = document.querySelector(".page-content");
            var notice = document.getElementById("timeLockNotification");
            if (!notice) {
                notice = document.createElement("div");
                notice.id = "timeLockNotification";
                if (pageContent) {
                    pageContent.insertBefore(notice, pageContent.firstChild);
                } else {
                    document.body.appendChild(notice);
                }
            }
            notice.textContent = message;
            notice.className = "toast flash-message show " + (type || "error");
            window.clearTimeout(notice.dataset.timer || 0);
            notice.dataset.timer = window.setTimeout(function () {
                notice.classList.add("hiding");
                window.setTimeout(function () {
                    notice.remove();
                }, 350);
            }, 5000);
        }
        window.openUnlockTimeLockModal = function (button) {
            document.getElementById("unlockTimeLockDoctor").textContent = button.dataset.doctor || "-";
            document.getElementById("unlockTimeLockDate").textContent = button.dataset.date || "-";
            document.getElementById("unlockTimeLockTime").textContent = button.dataset.time || "-";
            document.getElementById("confirmUnlockTimeLockAction").href = button.dataset.unlockUrl || "#";
            openModal("modal-unlock-time-lock");
        };
        function syncRange() {
            if (!range || !allDay) return;
            range.style.display = allDay.checked ? "none" : "grid";
            range.querySelectorAll("input").forEach(function (input) {
                input.required = !allDay.checked;
            });
        }
        allDay?.addEventListener("change", syncRange);
        form?.addEventListener("submit", function (event) {
            var doctorId = form.elements.doctor_id?.value || "";
            var lockDate = form.elements.lock_date?.value || "";
            var dayName = selectedDayName(lockDate);
            var daySchedules = ((doctorSchedules[doctorId] || {})[dayName] || []);
            var isAllDay = !!allDay?.checked;
            if (!doctorId || !lockDate) return;
            if (daySchedules.length === 0) {
                event.preventDefault();
                showTimeLockNotification("Selected doctor is not working on this day.", "error");
                return;
            }
            if (isAllDay) {
                if (lockOverlapsExisting(doctorId, lockDate, true, "", "")) {
                    event.preventDefault();
                    showTimeLockNotification("This doctor already has a locked slot on this date.", "error");
                }
                return;
            }
            var start = form.elements.start_time?.value || "";
            var end = form.elements.end_time?.value || "";
            if (!start || !end || end <= start || !rangeIsInsideSchedule(daySchedules, start, end)) {
                event.preventDefault();
                showTimeLockNotification("Selected time is outside this doctor\'s working hours.", "error");
                return;
            }
            if (lockOverlapsExisting(doctorId, lockDate, false, start, end)) {
                event.preventDefault();
                showTimeLockNotification("This time slot overlaps with an existing locked slot.", "error");
            }
        });
        syncRange();
    })();
    </script>';
}

function render_staff() {
    global $conn;
    ensure_profile_image_column($conn);
    $staff = fetch_all_assoc(
        $conn,
        "SELECT user_id, user_code, name, email, phone_number, gender, date_of_birth, blood_type, user_status, profile_image
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
        $phone = format_phone_number($s['phone_number'] ?? '');
        $dateOfBirth = !empty($s['date_of_birth']) ? format_date_display($s['date_of_birth']) : '-';
        $detailsAttrs = ' data-id="' . e($s['user_code']) . '" data-name="' . e($s['name']) . '" data-initials="' . e(name_avatar($s['name'] ?? 'Staff')) . '" data-image="' . e($s['profile_image'] ?? '') . '" data-email="' . e($s['email']) . '" data-phone="' . e($phone !== '' ? $phone : '-') . '" data-gender="' . e($s['gender'] ?: '-') . '" data-dob="' . e($dateOfBirth) . '" data-blood="' . e($s['blood_type'] ?: '-') . '" data-status="' . e($status) . '"';
        $editAttrs = ' data-id="' . e($s['user_id']) . '" data-name="' . e($s['name']) . '" data-email="' . e($s['email']) . '" data-phone="' . e($phone) . '" data-gender="' . e($s['gender'] ?? '') . '" data-dob="' . e($s['date_of_birth'] ?? '') . '" data-blood="' . e($s['blood_type'] ?? '') . '"';
        echo '<tr><td>' . e($s['user_code']) . '</td><td>' . e($s['name']) . '</td><td>Staff</td><td>' . e($s['email']) . '</td><td>' . e($phone) . '</td><td>' . badge($status) . '</td><td class="appt-actions-cell" style="padding: 12px 8px;"><div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleStaffMenu(event, this)" aria-label="Staff actions">...</button><div class="appt-menu-list"><button type="button" class="appt-menu-item" onclick="showStaffDetails(this)"' . $detailsAttrs . '>View Details</button><button type="button" class="appt-menu-item" onclick="openEditStaffModal(this)"' . $editAttrs . '>Edit</button><a class="appt-menu-item danger" href="' . e(action_url('delete', ['type' => 'staff', 'id' => $s['user_id']])) . '" onclick="return confirm(\'Delete this staff member?\')">Delete</a></div></div></td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<div class="modal-overlay" id="modal-staff-details"><div class="modal appointment-details-modal"><div class="modal-header"><span class="modal-title">Staff Details</span><button class="modal-close appointment-modal-close" onclick="closeModal(\'modal-staff-details\')">×</button></div><div class="modal-body"><div class="detail-avatar-wrap"><button type="button" class="detail-avatar avatar-preview-clickable" id="staffDetailAvatar" aria-label="Zoom staff avatar"></button></div><div class="appointment-detail-code"><span>Staff ID</span><strong id="staffDetailId"></strong></div><div class="appointment-detail-list"><div><span>Name</span><strong id="staffDetailName"></strong></div><div><span>Role</span><strong>Staff</strong></div><div><span>Email</span><strong id="staffDetailEmail"></strong></div><div><span>Phone</span><strong id="staffDetailPhone"></strong></div><div><span>Status</span><strong id="staffDetailStatus"></strong></div></div></div></div></div><div class="modal-overlay" id="modal-staff-avatar-preview"><div class="modal avatar-preview-modal"><div class="modal-header"><span class="modal-title" id="staffAvatarPreviewTitle">Avatar</span><button class="modal-close" onclick="closeModal(\'modal-staff-avatar-preview\')">&times;</button></div><div class="modal-body"><div class="avatar-preview-content" id="staffAvatarPreviewContent"></div></div></div></div>';
    echo '<script>
    document.getElementById("searchStaff")?.addEventListener("input", function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll("#staffTableBody tr").forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(query) ? "" : "none";
        });
    });
    function formatStaffStatusLabel(status) {
        return status ? status.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase()) : "-";
    }
    function ensureStaffDetailFields() {
        if (document.getElementById("staffDetailGender")) return;
        const statusRow = document.getElementById("staffDetailStatus")?.closest("div");
        if (!statusRow) return;
        ["Gender", "Date of Birth", "Blood Type"].forEach((label, index) => {
            const row = document.createElement("div");
            const id = ["staffDetailGender", "staffDetailDob", "staffDetailBlood"][index];
            row.innerHTML = "<span>" + label + "</span><strong id=\"" + id + "\"></strong>";
            statusRow.parentElement.insertBefore(row, statusRow);
        });
    }
    function ensureStaffEditFields() {
        if (document.getElementById("editStaffGender")) return;
        const phoneGroup = document.getElementById("editStaffPhone")?.closest(".form-group");
        if (!phoneGroup) return;
        const wrapper = document.createElement("div");
        wrapper.innerHTML = "<div class=\"form-group\"><label>Gender</label><select class=\"form-control\" name=\"gender\" id=\"editStaffGender\"><option value=\"\">Select gender</option><option value=\"Male\">Male</option><option value=\"Female\">Female</option></select></div><div class=\"form-group\"><label>Date of Birth</label><input class=\"form-control\" type=\"date\" name=\"date_of_birth\" id=\"editStaffDob\" max=\"' . date('Y-m-d') . '\" min=\"' . date('Y-m-d', strtotime('-120 years')) . '\"></div><div class=\"form-group\"><label>Blood Type</label><select class=\"form-control\" name=\"blood_type\" id=\"editStaffBlood\"><option value=\"\">Select blood type</option><option value=\"A+\">A+</option><option value=\"A-\">A-</option><option value=\"B+\">B+</option><option value=\"B-\">B-</option><option value=\"AB+\">AB+</option><option value=\"AB-\">AB-</option><option value=\"O+\">O+</option><option value=\"O-\">O-</option></select></div>";
        phoneGroup.after(...wrapper.children);
    }
    function renderDetailAvatar(targetId, imageName, initials, name) {
        const target = document.getElementById(targetId);
        if (!target) return;
        target.dataset.image = imageName || "";
        target.dataset.initials = initials || "?";
        target.dataset.title = name || "Avatar";
        if (imageName) {
            target.innerHTML = "<img src=\"' . e(app_url('uploads/avatars/')) . '" + encodeURIComponent(imageName) + "\" alt=\"\">";
        } else {
            target.textContent = initials || "?";
        }
    }
    function openStaffAvatarPreview() {
        const avatar = document.getElementById("staffDetailAvatar");
        const content = document.getElementById("staffAvatarPreviewContent");
        if (!avatar || !content) return;
        document.getElementById("staffAvatarPreviewTitle").textContent = avatar.dataset.title || "Avatar";
        content.innerHTML = "";
        if (avatar.dataset.image) {
            const img = document.createElement("img");
            img.className = "avatar-preview-image";
            img.src = "' . e(app_url('uploads/avatars/')) . '" + encodeURIComponent(avatar.dataset.image);
            img.alt = avatar.dataset.title || "Avatar";
            content.appendChild(img);
        } else {
            const fallback = document.createElement("div");
            fallback.className = "avatar-preview-initials";
            fallback.textContent = avatar.dataset.initials || "?";
            content.appendChild(fallback);
        }
        openModal("modal-staff-avatar-preview");
    }
    document.getElementById("staffDetailAvatar")?.addEventListener("click", openStaffAvatarPreview);
    function showStaffDetails(button) {
        ensureStaffDetailFields();
        renderDetailAvatar("staffDetailAvatar", button.dataset.image || "", button.dataset.initials || "", button.dataset.name || "Staff");
        document.getElementById("staffDetailId").textContent = button.dataset.id || "-";
        document.getElementById("staffDetailName").textContent = button.dataset.name || "-";
        document.getElementById("staffDetailEmail").textContent = button.dataset.email || "-";
        document.getElementById("staffDetailPhone").textContent = button.dataset.phone || "-";
        document.getElementById("staffDetailGender").textContent = button.dataset.gender || "-";
        document.getElementById("staffDetailDob").textContent = button.dataset.dob || "-";
        document.getElementById("staffDetailBlood").textContent = button.dataset.blood || "-";
        const status = button.dataset.status || "";
        document.getElementById("staffDetailStatus").innerHTML = "<span class=\"badge badge-" + status.replace(/_/g, "-") + "\">" + formatStaffStatusLabel(status) + "</span>";
        closeStaffMenus();
        openModal("modal-staff-details");
    }
    function openEditStaffModal(button) {
        ensureStaffEditFields();
        document.getElementById("editStaffId").value = button.dataset.id || "";
        document.getElementById("editStaffName").value = button.dataset.name || "";
        document.getElementById("editStaffEmail").value = button.dataset.email || "";
        document.getElementById("editStaffPhone").value = button.dataset.phone || "";
        document.getElementById("editStaffGender").value = button.dataset.gender || "";
        document.getElementById("editStaffDob").value = button.dataset.dob || "";
        document.getElementById("editStaffBlood").value = button.dataset.blood || "";
        closeStaffMenus();
        openModal("modal-edit-staff");
    }
    function closeStaffMenus() {
        document.querySelectorAll("#staffTableBody .appt-action-menu.open").forEach(menu => menu.classList.remove("open"));
    }
    function toggleStaffMenu(event, button) {
        event.stopPropagation();
        const menu = button.closest(".appt-action-menu");
        const menuList = menu.querySelector(".appt-menu-list");
        const wasOpen = menu.classList.contains("open");
        closeStaffMenus();
        menu.classList.toggle("open", !wasOpen);
        if (!wasOpen && menuList) {
            const rect = button.getBoundingClientRect();
            const menuWidth = menuList.offsetWidth || 148;
            const left = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, rect.right - menuWidth));
            menuList.style.top = (rect.bottom + 6) + "px";
            menuList.style.left = left + "px";
        }
    }
    document.addEventListener("click", closeStaffMenus);
    </script>';
}

// ============================================
// RENDER SCHEDULE - UPDATED VERSION (KEMAS & RESPONSIVE)
// ============================================

function render_schedule() {
    global $conn;
    $today = date('Y-m-d');
    $selectedDate = $_GET['date'] ?? $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
        $selectedDate = $today;
    }
    $doctors = get_doctors($conn);
    $appointments = fetch_all_assoc(
        $conn,
        "SELECT appointment_code, name, doctor_name, service_name, appointment_date, appointment_time, appointment_status
         FROM appointments
         WHERE appointment_date = ? AND appointment_status <> 'cancelled'
         ORDER BY appointment_time ASC",
        's',
        [$selectedDate]
    );

    echo '<div class="toolbar"><input class="form-control" type="date" id="scheduleDate" style="width:200px" value="' . e($selectedDate) . '" required><select class="filter-select" id="scheduleDoctor"><option value="">All Doctors</option>';
    foreach ($doctors as $doctor) {
        echo '<option>' . e($doctor['doctor_name']) . '</option>';
    }
    echo '</select></div><div class="card"><div class="card-body" style="padding:0; overflow-x:auto"><table class="data-table" style="width:100%; border-collapse:collapse; min-width:700px"><thead><tr><th>Time</th><th>User</th><th>Doctor</th><th>Service</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    if (empty($appointments)) {
        echo '<tr><td colspan="6" style="text-align:center">No appointments found.</td></tr>';
    }
    foreach ($appointments as $row) {
        $canComplete = in_array($row['appointment_status'] ?? '', ['confirm', 'confirmed'], true);
        $action = !$canComplete
            ? '<span class="text-muted">-</span>'
            : '<button type="button" class="btn btn-sm btn-teal" onclick="openCompleteAppointmentModal(this.dataset.completeUrl)" data-complete-url="' . e(action_url('update_status', ['id' => $row['appointment_code']])) . '">Complete</button>';
        echo '<tr><td>' . e(format_time_display($row['appointment_time'])) . '</td><td>' . e($row['name']) . '</td><td>' . e($row['doctor_name']) . '</td><td>' . e($row['service_name']) . '</td><td>' . appointment_badge($row['appointment_status'], 'staff') . '</td><td>' . $action . '</td></tr>';
    }
    echo '</tbody></table></div></div>';
    echo '<div class="modal-overlay" id="modal-complete-appointment"><div class="modal"><div class="modal-header"><span class="modal-title">Complete Appointment</span><button class="modal-close" onclick="closeModal(\'modal-complete-appointment\')">×</button></div><div class="modal-body"><p class="text-muted mb-16">Are you sure this appointment has been completed?</p></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal(\'modal-complete-appointment\')">Cancel</button><a class="btn btn-teal" id="confirmCompleteAppointmentAction" style="width:auto" href="#">Complete</a></div></div></div>';
    echo '<script>
    function openCompleteAppointmentModal(url) {
        const action = document.getElementById("confirmCompleteAppointmentAction");
        if (!action || !url) return;
        action.href = url;
        openModal("modal-complete-appointment");
    }
    function filterSchedule() {
        const doctor = document.getElementById("scheduleDoctor")?.value.toLowerCase() || "";
        const rows = document.querySelectorAll(".data-table tbody tr");
        rows.forEach(row => {
            const doctorCell = row.cells[2]?.innerText.toLowerCase() || "";
            row.style.display = doctor && !doctorCell.includes(doctor) ? "none" : "";
        });
    }
    document.getElementById("scheduleDate")?.addEventListener("change", function () {
        const url = new URL(window.location.href);
        url.searchParams.set("date", this.value);
        window.location.href = url.toString();
    });
    document.getElementById("scheduleDoctor")?.addEventListener("change", filterSchedule);
    </script>';
}

function render_users() {
    global $conn;
    ensure_profile_image_column($conn);

    $users = fetch_all_assoc(
        $conn,
        "SELECT u.user_code, u.name, u.email, u.phone_number, u.gender, u.date_of_birth, u.blood_type, u.user_status, u.profile_image,
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
         GROUP BY u.user_id, u.user_code, u.name, u.email, u.phone_number, u.gender, u.date_of_birth, u.blood_type, u.user_status, u.profile_image
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
                <table class="data-table" style="width:100%; border-collapse: collapse; min-width: 640px;">
                    <thead>
                        <tr style="background: var(--surface2); border-bottom: 2px solid var(--border);">
                            <th style="padding: 12px 8px; text-align: left;">User ID</th>
                            <th style="padding: 12px 8px; text-align: left;">Name</th>
                            <th style="padding: 12px 8px; text-align: left;">Email</th>
                            <th style="padding: 12px 8px; text-align: left;">Status</th>
                            <th style="padding: 12px 8px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">';
    
    if (empty($users)) {
        echo '<tr><td colspan="5" style="text-align:center;padding:16px">No users found.</td></tr>';
    }

    foreach ($users as $u) {
        $status = strtolower($u['user_status'] ?? 'inactive');
        $lastVisit = !empty($u['last_visit']) ? date('d M Y, H:i', strtotime($u['last_visit'])) : '-';
        $phone = format_phone_number($u['phone_number'] ?? '');
        $dateOfBirth = !empty($u['date_of_birth']) ? format_date_display($u['date_of_birth']) : '-';
        $detailsAttrs = ' data-id="' . e($u['user_code']) . '" data-name="' . e($u['name']) . '" data-initials="' . e(name_avatar($u['name'] ?? 'User')) . '" data-image="' . e($u['profile_image'] ?? '') . '" data-email="' . e($u['email']) . '" data-phone="' . e($phone !== '' ? $phone : '-') . '" data-gender="' . e($u['gender'] ?: '-') . '" data-dob="' . e($dateOfBirth) . '" data-blood="' . e($u['blood_type'] ?: '-') . '" data-last-visit="' . e($lastVisit) . '" data-status="' . e($status) . '"';
        $searchText = implode(' ', [$u['user_code'], $u['name'], $u['email'], $phone, $u['gender'], $dateOfBirth, $u['blood_type'], $lastVisit, $status]);
        echo '<tr data-status="' . e($status) . '" data-search="' . e($searchText) . '" style="border-bottom: 1px solid var(--border);">
                <td style="padding: 12px 8px;">' . e($u['user_code']) . '</td>
                <td style="padding: 12px 8px;">' . e($u['name']) . '</td>
                <td style="padding: 12px 8px;">' . e($u['email']) . '</td>
                <td style="padding: 12px 8px;">' . badge($status) . '</td>
                <td class="appt-actions-cell" style="padding: 12px 8px;"><div class="appt-action-menu"><button type="button" class="appt-menu-trigger" onclick="toggleUserMenu(event, this)" aria-label="User actions">...</button><div class="appt-menu-list"><button type="button" class="appt-menu-item" onclick="showUserDetails(this)"' . $detailsAttrs . '>View Details</button></div></div></td>
               </tr>';
    }
    
    echo '</tbody>
                </table>
            </div>
          </div>';
    echo '<div class="modal-overlay" id="modal-user-details"><div class="modal appointment-details-modal"><div class="modal-header"><span class="modal-title">User Details</span><button class="modal-close appointment-modal-close" onclick="closeModal(\'modal-user-details\')">×</button></div><div class="modal-body"><div class="detail-avatar-wrap"><button type="button" class="detail-avatar avatar-preview-clickable" id="userDetailAvatar" aria-label="Zoom user avatar"></button></div><div class="appointment-detail-code"><span>User ID</span><strong id="userDetailId"></strong></div><div class="appointment-detail-list"><div><span>Name</span><strong id="userDetailName"></strong></div><div><span>Email</span><strong id="userDetailEmail"></strong></div><div><span>Phone</span><strong id="userDetailPhone"></strong></div><div><span>Gender</span><strong id="userDetailGender"></strong></div><div><span>Date of Birth</span><strong id="userDetailDob"></strong></div><div><span>Blood Type</span><strong id="userDetailBlood"></strong></div><div><span>Last Visit</span><strong id="userDetailLastVisit"></strong></div><div><span>Status</span><strong id="userDetailStatus"></strong></div></div></div></div></div><div class="modal-overlay" id="modal-user-avatar-preview"><div class="modal avatar-preview-modal"><div class="modal-header"><span class="modal-title" id="userAvatarPreviewTitle">Avatar</span><button class="modal-close" onclick="closeModal(\'modal-user-avatar-preview\')">&times;</button></div><div class="modal-body"><div class="avatar-preview-content" id="userAvatarPreviewContent"></div></div></div></div>';
    
    // Add search filter script
    echo '
    <script>
    function filterUsers() {
        const searchValue = document.getElementById("searchUser")?.value.toLowerCase() || "";
        const statusFilter = document.getElementById("userStatus")?.value.toLowerCase() || "";
        const rows = document.querySelectorAll("#usersTableBody tr");
        
        rows.forEach(row => {
            const text = (row.dataset.search || row.innerText).toLowerCase();
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
    function formatUserStatusLabel(status) {
        return status ? status.replace(/_/g, " ").replace(/\b\w/g, char => char.toUpperCase()) : "-";
    }
    function renderUserDetailAvatar(imageName, initials, name) {
        const target = document.getElementById("userDetailAvatar");
        if (!target) return;
        target.dataset.image = imageName || "";
        target.dataset.initials = initials || "?";
        target.dataset.title = name || "Avatar";
        if (imageName) {
            target.innerHTML = "<img src=\"' . e(app_url('uploads/avatars/')) . '" + encodeURIComponent(imageName) + "\" alt=\"\">";
        } else {
            target.textContent = initials || "?";
        }
    }
    function openUserAvatarPreview() {
        const avatar = document.getElementById("userDetailAvatar");
        const content = document.getElementById("userAvatarPreviewContent");
        if (!avatar || !content) return;
        document.getElementById("userAvatarPreviewTitle").textContent = avatar.dataset.title || "Avatar";
        content.innerHTML = "";
        if (avatar.dataset.image) {
            const img = document.createElement("img");
            img.className = "avatar-preview-image";
            img.src = "' . e(app_url('uploads/avatars/')) . '" + encodeURIComponent(avatar.dataset.image);
            img.alt = avatar.dataset.title || "Avatar";
            content.appendChild(img);
        } else {
            const fallback = document.createElement("div");
            fallback.className = "avatar-preview-initials";
            fallback.textContent = avatar.dataset.initials || "?";
            content.appendChild(fallback);
        }
        openModal("modal-user-avatar-preview");
    }
    document.getElementById("userDetailAvatar")?.addEventListener("click", openUserAvatarPreview);
    function showUserDetails(button) {
        renderUserDetailAvatar(button.dataset.image || "", button.dataset.initials || "", button.dataset.name || "User");
        document.getElementById("userDetailId").textContent = button.dataset.id || "-";
        document.getElementById("userDetailName").textContent = button.dataset.name || "-";
        document.getElementById("userDetailEmail").textContent = button.dataset.email || "-";
        document.getElementById("userDetailPhone").textContent = button.dataset.phone || "-";
        document.getElementById("userDetailGender").textContent = button.dataset.gender || "-";
        document.getElementById("userDetailDob").textContent = button.dataset.dob || "-";
        document.getElementById("userDetailBlood").textContent = button.dataset.blood || "-";
        document.getElementById("userDetailLastVisit").textContent = button.dataset.lastVisit || "-";
        const status = button.dataset.status || "";
        document.getElementById("userDetailStatus").innerHTML = "<span class=\"badge badge-" + status.replace(/_/g, "-") + "\">" + formatUserStatusLabel(status) + "</span>";
        openModal("modal-user-details");
    }
    function closeUserMenus() {
        document.querySelectorAll(".appt-action-menu.open").forEach(menu => menu.classList.remove("open"));
    }
    function toggleUserMenu(event, button) {
        event.stopPropagation();
        const menu = button.closest(".appt-action-menu");
        const menuList = menu.querySelector(".appt-menu-list");
        const wasOpen = menu.classList.contains("open");
        closeUserMenus();
        menu.classList.toggle("open", !wasOpen);
        if (!wasOpen && menuList) {
            const rect = button.getBoundingClientRect();
            const menuWidth = menuList.offsetWidth || 148;
            const left = Math.max(8, Math.min(window.innerWidth - menuWidth - 8, rect.right - menuWidth));
            menuList.style.top = (rect.bottom + 6) + "px";
            menuList.style.left = left + "px";
        }
    }
    document.addEventListener("click", closeUserMenus);
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
<div class="modal-overlay" id="modal-add-doctor"><div class="modal"><div class="modal-header"><span class="modal-title" id="doctorModalTitle">Add Doctor</span><button class="modal-close" onclick="closeModal('modal-add-doctor')">✕</button></div><form method="post" action="action.php" enctype="multipart/form-data"><input type="hidden" name="action" value="save_doctor"><input type="hidden" name="id" id="editDoctorId" value=""><div class="modal-body"><div class="profile-upload-area"><label class="profile-upload-avatar" for="editDoctorImage"><div class="profile-avatar-lg" id="doctorImagePreview">D</div><span>Change</span></label><input class="profile-file-input" type="file" name="doctor_image" id="editDoctorImage" accept=".jpg,.jpeg,.png,.webp"><p class="text-muted profile-upload-note">Upload a square JPG, PNG, or WEBP image. Maximum file size is 2MB.</p></div><div class="form-group"><label>Full Name</label><input class="form-control" name="name" id="editDoctorName" placeholder="e.g. Dr. Ahmad Fauzi" required></div><div class="form-group"><label>Specialization</label><input class="form-control" name="specialization" id="editDoctorSpec" required></div><div class="form-group"><label>Services Provided</label><div id="doctorServicesContainer" style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:8px; margin-top:8px;"></div></div><div class="form-group"><label>Available Days</label><div id="doctorDaysContainer" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 8px;"><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Mon"> Mon</label><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Tue"> Tue</label><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Wed"> Wed</label><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Thu"> Thu</label><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Fri"> Fri</label><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Sat"> Sat</label><label style="font-weight: 400; font-size: 0.85rem;"><input type="checkbox" name="available_days[]" value="Sun"> Sun</label></div></div></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-doctor')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<script>
function setupDoctorScheduleInputs() {
    const container = document.getElementById('doctorDaysContainer');
    if (!container || document.getElementById('doctorScheduleTimeGroup')) return;
    container.style.gridTemplateColumns = 'repeat(4, 1fr)';
    container.closest('.form-group').insertAdjacentHTML('afterend',
        '<div class="form-group" id="doctorScheduleTimeGroup">' +
            '<label>Available Time</label>' +
            '<div class="grid-2">' +
                '<input class="form-control" type="time" name="schedule_start" id="doctorScheduleStart" value="09:00" step="1800" required>' +
                '<input class="form-control" type="time" name="schedule_end" id="doctorScheduleEnd" value="18:00" step="1800" required>' +
            '</div>' +
        '</div>' +
        '<div class="form-group" id="doctorBreakTimeGroup">' +
            '<label>Break Time</label>' +
            '<div class="grid-2">' +
                '<input class="form-control" type="time" name="break_start" id="doctorBreakStart" value="13:00" step="1800" required>' +
                '<input class="form-control" type="time" name="break_end" id="doctorBreakEnd" value="14:00" step="1800" required>' +
            '</div>' +
        '</div>'
    );
}
function setDoctorImagePreview(image, initials) {
    const preview = document.getElementById('doctorImagePreview');
    if (!preview) return;
    preview.classList.remove('doctor-avatar-image');
    preview.innerHTML = '';
    if (image) {
        preview.classList.add('doctor-avatar-image');
        preview.innerHTML = '<img src="' + image + '" alt="Doctor photo">';
    } else {
        preview.textContent = initials || 'D';
    }
}
function resetDoctorScheduleInputs() {
    setupDoctorScheduleInputs();
    document.querySelectorAll('#doctorDaysContainer input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('doctorScheduleStart').value = '09:00';
    document.getElementById('doctorScheduleEnd').value = '18:00';
    document.getElementById('doctorBreakStart').value = '13:00';
    document.getElementById('doctorBreakEnd').value = '14:00';
}
function applyDoctorScheduleData(scheduleData) {
    resetDoctorScheduleInputs();
    const rows = (scheduleData || '').split(';;').filter(Boolean);
    let firstStart = '';
    let firstEnd = '';
    let firstBreakStart = '';
    let firstBreakEnd = '';
    rows.forEach(row => {
        const parts = row.split('|');
        const day = parts[0] || '';
        const start = parts[1] || '09:00';
        const end = parts[2] || '18:00';
        const breakStart = parts[3] || '13:00';
        const breakEnd = parts[4] || '14:00';
        const checkbox = document.querySelector('#doctorDaysContainer input[type="checkbox"][value="' + day + '"]');
        if (checkbox) checkbox.checked = true;
        if (!firstStart) firstStart = start;
        if (!firstEnd) firstEnd = end;
        if (!firstBreakStart) firstBreakStart = breakStart;
        if (!firstBreakEnd) firstBreakEnd = breakEnd;
    });
    document.getElementById('doctorScheduleStart').value = firstStart || '09:00';
    document.getElementById('doctorScheduleEnd').value = firstEnd || '18:00';
    document.getElementById('doctorBreakStart').value = firstBreakStart || '13:00';
    document.getElementById('doctorBreakEnd').value = firstBreakEnd || '14:00';
}
function openAddDoctorModal() {
    document.getElementById('doctorModalTitle').textContent = 'Add Doctor';
    document.getElementById('editDoctorId').value = '';
    document.getElementById('editDoctorName').value = '';
    document.getElementById('editDoctorSpec').value = '';
    document.getElementById('editDoctorImage').value = '';
    setDoctorImagePreview('', 'D');
    resetDoctorServiceInputs();
    resetDoctorScheduleInputs();
    openModal('modal-add-doctor');
}
function openEditDoctorModal(event, btn) {
    event.stopPropagation();
    document.getElementById('doctorModalTitle').textContent = 'Edit Doctor';
    document.getElementById('editDoctorId').value = btn.dataset.id;
    document.getElementById('editDoctorName').value = btn.dataset.name;
    document.getElementById('editDoctorSpec').value = btn.dataset.spec;
    document.getElementById('editDoctorImage').value = '';
    setDoctorImagePreview(
        btn.dataset.image ? 'uploads/doctors/' + encodeURIComponent(btn.dataset.image) : '',
        btn.dataset.initials || 'D'
    );
    applyDoctorServiceData(btn.dataset.serviceIds || '');
    applyDoctorScheduleData(btn.closest('.doctor-card')?.dataset.schedule || '');
    openModal('modal-add-doctor');
}
document.getElementById('editDoctorImage')?.addEventListener('change', function () {
    const file = this.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (event) {
        setDoctorImagePreview(event.target.result, 'D');
    };
    reader.readAsDataURL(file);
});
setupDoctorScheduleInputs();
</script>
<div class="modal-overlay" id="modal-add-service"><div class="modal"><div class="modal-header"><span class="modal-title" id="serviceModalTitle">Add Service</span><button class="modal-close" onclick="closeModal('modal-add-service')">✕</button></div><form method="post" action="action.php"><input type="hidden" name="action" value="save_service"><input type="hidden" name="id" id="editServiceId" value=""><div class="modal-body"><div class="form-group"><label>Service Name</label><input class="form-control" name="name" id="editServiceName" placeholder="e.g. Dental Cleaning" required></div><div class="form-group"><label>Fee</label><div style="display:flex; align-items:center; border:1px solid var(--border); border-radius:8px; background:var(--surface); overflow:hidden;"><span style="padding:0 14px; color:var(--text-muted); font-weight:700;">RM</span><input class="form-control" style="border:0; border-left:1px solid var(--border); border-radius:0;" type="text" inputmode="numeric" name="fee" id="editServiceFee" placeholder="0.00" required></div></div><div class="form-group"><label>Card Description</label><textarea class="form-control" name="description" id="editServiceDescription" rows="3" required></textarea></div><p class="text-muted" style="font-size:0.75rem; margin-top:8px">Note: The system will automatically assign an icon based on the service name.</p></div><div class="modal-footer"><button class="btn btn-outline" type="button" onclick="closeModal('modal-add-service')">Cancel</button><button class="btn btn-primary" style="width:auto">Save</button></div></form></div></div>
<script>
function openAddServiceModal() {
    document.getElementById('serviceModalTitle').textContent = 'Add Service';
    document.getElementById('editServiceId').value = '';
    document.getElementById('editServiceName').value = '';
    document.getElementById('editServiceFee').value = '';
    document.getElementById('editServiceDescription').value = '';
    openModal('modal-add-service');
}
function serviceFeeToCents(value) {
    const amount = Number.parseFloat(value || '0');
    if (Number.isNaN(amount)) return '';
    return (Math.round(amount * 100) / 100).toFixed(2);
}
function formatServiceFeeInput(input) {
    const digits = input.value.replace(/\D+/g, '');
    input.value = digits ? (Number.parseInt(digits, 10) / 100).toFixed(2) : '';
}
function openEditServiceModal(event, btn) {
    event.stopPropagation();
    document.getElementById('serviceModalTitle').textContent = 'Edit Service';
    document.getElementById('editServiceId').value = btn.dataset.id || '';
    document.getElementById('editServiceName').value = btn.dataset.name || '';
    document.getElementById('editServiceFee').value = serviceFeeToCents(btn.dataset.fee);
    document.getElementById('editServiceDescription').value = btn.dataset.description || btn.dataset.shortDesc || '';
    openModal('modal-add-service');
}
document.getElementById('editServiceFee')?.addEventListener('input', function () {
    formatServiceFeeInput(this);
});
</script>
HTML;
    $doctorServiceOptions = array_map(
        fn($service) => ['id' => (int)($service['service_id'] ?? 0), 'name' => (string)($service['service_name'] ?? '')],
        get_services($conn)
    );
    echo '<script>
    window.quickcareDoctorServices = ' . json_encode($doctorServiceOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';
    function renderDoctorServiceOptions() {
        const container = document.getElementById("doctorServicesContainer");
        if (!container || container.dataset.ready === "1") return;
        container.dataset.ready = "1";
        container.innerHTML = "";
        (window.quickcareDoctorServices || []).forEach(function (service) {
            const label = document.createElement("label");
            label.style.fontWeight = "400";
            label.style.fontSize = "0.85rem";
            label.style.display = "flex";
            label.style.alignItems = "center";
            label.style.gap = "8px";
            label.innerHTML = "<input type=\"checkbox\" name=\"service_ids[]\" value=\"" + service.id + "\"> <span></span>";
            label.querySelector("span").textContent = service.name;
            container.appendChild(label);
        });
    }
    function resetDoctorServiceInputs() {
        renderDoctorServiceOptions();
        document.querySelectorAll("#doctorServicesContainer input[type=\"checkbox\"]").forEach(function (cb) {
            cb.checked = false;
        });
    }
    function applyDoctorServiceData(serviceIds) {
        resetDoctorServiceInputs();
        const selected = (serviceIds || "").split(",").filter(Boolean);
        document.querySelectorAll("#doctorServicesContainer input[type=\"checkbox\"]").forEach(function (cb) {
            cb.checked = selected.includes(cb.value);
        });
    }
    renderDoctorServiceOptions();
    </script>';
    echo '<div class="modal-overlay" id="modal-avatar-preview"><div class="modal avatar-preview-modal"><div class="modal-header"><span class="modal-title" id="avatarPreviewTitle">Avatar</span><button class="modal-close" onclick="closeModal(\'modal-avatar-preview\')">&times;</button></div><div class="modal-body"><div class="avatar-preview-content" id="avatarPreviewContent"></div></div></div></div>';
    echo '<script>
    function openAvatarPreview(url, initials, title) {
        const titleEl = document.getElementById("avatarPreviewTitle");
        const content = document.getElementById("avatarPreviewContent");
        if (!content) return;
        content.innerHTML = "";
        if (titleEl) titleEl.textContent = title || "Avatar";

        if (url) {
            const img = document.createElement("img");
            img.className = "avatar-preview-image";
            img.src = url;
            img.alt = title || "Avatar preview";
            content.appendChild(img);
        } else {
            const fallback = document.createElement("div");
            fallback.className = "avatar-preview-initials";
            fallback.textContent = initials || "?";
            content.appendChild(fallback);
        }
        openModal("modal-avatar-preview");
    }
    function openAvatarPreviewFromTrigger(trigger) {
        openAvatarPreview(
            trigger?.dataset?.avatarUrl || "",
            trigger?.dataset?.avatarInitials || trigger?.textContent?.trim() || "",
            trigger?.dataset?.avatarTitle || "Avatar"
        );
    }
    document.addEventListener("DOMContentLoaded", function () {
        const profileAvatar = document.querySelector(".profile-header .profile-avatar-lg");
        if (!profileAvatar) return;
        profileAvatar.classList.add("avatar-preview-clickable");
        profileAvatar.setAttribute("role", "button");
        profileAvatar.setAttribute("tabindex", "0");
        const profileImg = profileAvatar.querySelector("img");
        profileAvatar.dataset.avatarUrl = profileImg ? profileImg.src : "";
        profileAvatar.dataset.avatarInitials = profileAvatar.textContent.trim();
        profileAvatar.dataset.avatarTitle = "Profile Avatar";
        profileAvatar.addEventListener("click", function () {
            openAvatarPreviewFromTrigger(profileAvatar);
        });
        profileAvatar.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                openAvatarPreviewFromTrigger(profileAvatar);
            }
        });
    });
    </script>';
    echo '<div class="modal-overlay" id="modal-edit-profile" data-static-modal="true"><div class="modal"><div class="modal-header"><span class="modal-title">Edit Profile</span><button class="modal-close" onclick="closeModal(\'modal-edit-profile\')">✕</button></div><form method="post" action="' . e(app_url('action.php')) . '" enctype="multipart/form-data"><input type="hidden" name="action" value="save_profile"><div class="modal-body">';
    $hasProfileImage = !empty($user['profile_image']);
    echo '<div class="profile-upload-area"><label class="profile-upload-avatar" for="profileImage">' . str_replace('class="profile-avatar-lg', 'id="profileImagePreview" class="profile-avatar-lg', user_avatar_html($user, 'profile-avatar-lg')) . '<span>Change</span></label><input class="profile-file-input" id="profileImage" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp"><p class="text-muted profile-upload-note">Upload a square JPG, PNG, or WEBP image. Maximum file size is 2MB.</p>';
    echo '<input type="hidden" name="delete_profile_image" id="deleteProfileImage" value="0"><button class="profile-delete-photo" type="button" id="deleteProfileImageButton" aria-pressed="false"' . ($hasProfileImage ? '' : ' hidden') . '>Delete current photo</button>';
    echo '</div>';
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
    $profileInitials = name_avatar($user['name'] ?? 'User');
    echo '<script>
    function selectServiceEmoji(btn, emoji) {
        const container = btn.closest(".emoji-picker");
        container.querySelectorAll(".emoji-btn").forEach(b => { b.classList.remove("btn-primary"); b.classList.add("btn-outline"); });
        btn.classList.remove("btn-outline");
        btn.classList.add("btn-primary");
        document.getElementById("serviceIconInput").value = emoji;
    }
    document.getElementById("profileImage")?.addEventListener("change", function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const deleteInput = document.getElementById("deleteProfileImage");
        const deleteButton = document.getElementById("deleteProfileImageButton");
        if (deleteInput) {
            deleteInput.value = "0";
        }
        if (deleteButton) {
            deleteButton.hidden = false;
            deleteButton.classList.remove("active");
            deleteButton.setAttribute("aria-pressed", "false");
        }
        const preview = document.getElementById("profileImagePreview");
        if (!preview) return;
        const reader = new FileReader();
        reader.onload = function (event) {
            preview.classList.add("profile-avatar-image");
            preview.innerHTML = "<img src=\"" + event.target.result + "\" alt=\"Profile avatar preview\">";
        };
        reader.readAsDataURL(file);
    });
    document.getElementById("deleteProfileImageButton")?.addEventListener("click", function () {
        const deleteInput = document.getElementById("deleteProfileImage");
        if (!deleteInput) return;
        deleteInput.value = "1";
        this.classList.remove("active");
        this.setAttribute("aria-pressed", "false");
        const fileInput = document.getElementById("profileImage");
        const preview = document.getElementById("profileImagePreview");
        if (fileInput) fileInput.value = "";
        this.hidden = true;
        if (!preview) return;
        preview.classList.remove("profile-avatar-image");
        preview.innerHTML = "";
        preview.textContent = "' . e($profileInitials) . '";
    });
    </script>';
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
              AND a.payment_status IN ('pending', 'rejected', 'failed')
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
    ensure_payment_method_column($conn);
    ensure_failed_payment_status($conn);
    
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
              a.appointment_status,
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
    ensure_payment_method_column($conn);
    ensure_failed_payment_status($conn);
    
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

    $pendingRows = fetch_all_assoc(
        $conn,
        "SELECT
             0 AS payment_id,
             '' AS payment_code,
             a.user_id,
             a.appointment_code,
             '-' AS receipt_number,
             a.amount,
              'pending' AS payment_status,
              '' AS payment_method,
              '-' AS transaction_id,
             '' AS receipt_image,
             '' AS remarks,
             a.created_at AS payment_date,
             NULL AS approved_by,
             NULL AS approved_date,
             COALESCE(u.name, a.name) AS patient_name,
             a.appointment_date,
             a.doctor_name,
             a.service_name
         FROM appointments a
         LEFT JOIN users u ON a.user_id = u.user_id
         WHERE a.payment_status = 'pending'
           AND NOT EXISTS (
               SELECT 1
               FROM payments p
               WHERE p.appointment_code = a.appointment_code
                 AND p.payment_status <> 'failed'
           )"
    );

    $payments = array_merge($payments, $pendingRows);
    usort($payments, function($a, $b) {
        return strtotime($b['payment_date'] ?? '') <=> strtotime($a['payment_date'] ?? '');
    });
    
    return $payments;
}

// Get pending payments for approval (admin)
function get_pending_payments() {
    return array_values(array_filter(get_all_payments(), function ($payment) {
        return in_array($payment['payment_status'] ?? '', ['verifying', 'pending'], true);
    }));
}

// Generate receipt number
function generate_receipt_number() {
    return 'RCPT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Generate payment code
function generate_payment_code() {
    return 'PAY-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function payment_receipt_number($payment) {
    global $conn;

    $paymentId = (int)($payment['payment_id'] ?? 0);
    $receiptNumber = trim((string)($payment['receipt_number'] ?? ''));
    if ($receiptNumber !== '' && $receiptNumber !== '-') {
        return $receiptNumber;
    }

    if ($paymentId <= 0) {
        return generate_receipt_number();
    }

    $receiptNumber = generate_receipt_number();
    $stmt = $conn->prepare("UPDATE payments SET receipt_number = ? WHERE payment_id = ?");
    $stmt->bind_param("si", $receiptNumber, $paymentId);
    $stmt->execute();
    $stmt->close();

    return $receiptNumber;
}

// Submit payment (user upload receipt)
function submit_payment($user_id, $appointment_code, $amount, $transaction_id, $remarks, $receipt_file, $payment_status = 'verifying', $payment_method = '') {
    global $conn;
    ensure_payment_method_column($conn);
    ensure_failed_payment_status($conn);
    
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
    $receipt_number = in_array($payment_status, ['paid', 'approved'], true) ? generate_receipt_number() : null;
    
    $remarks = clean_payment_remarks($remarks);
    $payment_method = trim((string)$payment_method);

    $stmt = $conn->prepare("
        INSERT INTO payments (payment_code, user_id, appointment_code, receipt_number, amount, 
                              payment_status, payment_method, transaction_id, receipt_image, remarks, payment_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sissdsssss", $payment_code, $user_id, $appointment_code, $receipt_number,
                      $amount, $payment_status, $payment_method, $transaction_id, $receipt_file, $remarks);
    
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
        
        $receiptNumber = payment_receipt_number($payment);
        $payment['receipt_number'] = $receiptNumber;

        // Update payment status
        $stmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'paid', receipt_number = ?, approved_by = ?, approved_date = NOW()
            WHERE payment_id = ?
        ");
        $stmt->bind_param("sii", $receiptNumber, $admin_id, $payment_id);
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
        $stmt->bind_param("siid", $receiptNumber, $payment_id, $payment['user_id'], 
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

// Refund payment (admin)
function refund_payment($payment_id, $admin_id, $reason, $refund_receipt = '') {
    global $conn;

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        if ($payment['payment_status'] !== 'refund_requested') {
            throw new Exception('Only refund requests can be approved');
        }

        $refund_receipt = trim((string)$refund_receipt);
        if ($refund_receipt !== '') {
            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = 'refunded',
                    approved_by = ?,
                    approved_date = NOW(),
                    remarks = CONCAT(COALESCE(remarks, ''), '\nRefund receipt: ', ?)
                WHERE payment_id = ?
            ");
            $stmt->bind_param("isi", $admin_id, $refund_receipt, $payment_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE payments
                SET payment_status = 'refunded',
                    approved_by = ?,
                    approved_date = NOW()
                WHERE payment_id = ?
            ");
            $stmt->bind_param("ii", $admin_id, $payment_id);
        }
        $stmt->execute();
        $stmt->close();

        if ($payment['appointment_code']) {
            $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'refunded' WHERE appointment_code = ?");
            $stmt->bind_param("s", $payment['appointment_code']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();

        $payment['payment_status'] = 'refunded';
        send_payment_refunded_email($payment['user_id'], $payment, '');

        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Reject refund request (admin)
function reject_refund_request($payment_id, $admin_id, $reason) {
    global $conn;

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        if ($payment['payment_status'] !== 'refund_requested') {
            throw new Exception('Only refund requests can be rejected');
        }

        $rejectNote = trim($reason) !== '' ? trim($reason) : 'No reason provided';
        $stmt = $conn->prepare("
            UPDATE payments
            SET payment_status = 'refund_rejected',
                approved_by = ?,
                approved_date = NOW(),
                remarks = CONCAT(COALESCE(remarks, ''), '\nRefund request rejected: ', ?)
            WHERE payment_id = ?
        ");
        $stmt->bind_param("isi", $admin_id, $rejectNote, $payment_id);
        $stmt->execute();
        $stmt->close();

        if ($payment['appointment_code']) {
            $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'refund_rejected' WHERE appointment_code = ?");
            $stmt->bind_param("s", $payment['appointment_code']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        send_refund_rejected_email($payment['user_id'], $payment, $rejectNote);

        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Request refund (user)
function request_refund($user_id, $payment_id, $reason) {
    global $conn;

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT p.*, a.appointment_status
            FROM payments p
            LEFT JOIN appointments a ON a.appointment_code = p.appointment_code
            WHERE p.payment_id = ? AND p.user_id = ?
        ");
        $stmt->bind_param("ii", $payment_id, $user_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            throw new Exception('Payment not found');
        }

        if (!in_array($payment['appointment_status'] ?? '', ['cancelled', 'confirm', 'confirmed'], true)) {
            throw new Exception('Only cancelled or confirmed appointments can request a refund');
        }

        if (!in_array($payment['payment_status'], ['paid', 'approved'], true)) {
            throw new Exception('Only paid payments can request a refund');
        }

        $refundNote = trim($reason) !== '' ? trim($reason) : 'No reason provided';
        $stmt = $conn->prepare("
            UPDATE payments
            SET payment_status = 'refund_requested',
                remarks = CONCAT(COALESCE(remarks, ''), '\nRefund requested: ', ?)
            WHERE payment_id = ? AND user_id = ?
        ");
        $stmt->bind_param("sii", $refundNote, $payment_id, $user_id);
        $stmt->execute();
        $stmt->close();

        if ($payment['appointment_code']) {
            $stmt = $conn->prepare("UPDATE appointments SET payment_status = 'refund_requested' WHERE appointment_code = ?");
            $stmt->bind_param("s", $payment['appointment_code']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
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

function send_appointment_cancelled_email($appointment, $reason, $cancelledBy = 'QuickCare team') {
    $to = trim((string)($appointment['email'] ?? ''));
    if ($to === '') {
        return false;
    }

    $patientName = htmlspecialchars($appointment['name'] ?? 'Patient');
    $appointmentCode = htmlspecialchars($appointment['appointment_code'] ?? '');
    $doctorName = htmlspecialchars($appointment['doctor_name'] ?? '');
    $serviceName = htmlspecialchars($appointment['service_name'] ?? '');
    $appointmentDate = htmlspecialchars(format_date_display($appointment['appointment_date'] ?? ''));
    $appointmentTime = htmlspecialchars(format_time_display($appointment['appointment_time'] ?? ''));
    $cancelReason = nl2br(htmlspecialchars(trim((string)$reason)));
    $cancelledBy = htmlspecialchars($cancelledBy);

    $subject = "Appointment Cancelled - QuickCare";
    $body = "
        <h2>Appointment Cancelled ❌</h2>
        <p>Dear {$patientName},</p>
        <p>Your appointment has been cancelled by {$cancelledBy}.</p>
        <h3>Appointment Details:</h3>
        <ul>
            <li><strong>Appointment ID:</strong> {$appointmentCode}</li>
            <li><strong>Doctor:</strong> {$doctorName}</li>
            <li><strong>Service:</strong> {$serviceName}</li>
            <li><strong>Date:</strong> {$appointmentDate}</li>
            <li><strong>Time:</strong> {$appointmentTime}</li>
        </ul>
        <p><strong>Cancel Reason:</strong><br>{$cancelReason}</p>
        <p>Please contact QuickCare if you have any questions.</p>
    ";

    return send_email($to, $subject, $body);
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

// Send email for refunded payment
function send_payment_refunded_email($user_id, $payment, $reason) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) return;

    $subject = "Payment Refunded - QuickCare";
    $body = "
        <h2>Payment Refunded ✅</h2>
        <p>Dear {$user['name']},</p>
        <p>Your payment has been <strong>REFUNDED</strong>.</p>
        " . (trim((string)$reason) !== '' ? "<p><strong>Reason:</strong> {$reason}</p>" : "") . "
        <h3>Payment Details:</h3>
        <ul>
            <li><strong>Receipt Number:</strong> {$payment['receipt_number']}</li>
            <li><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</li>
            <li><strong>Appointment Code:</strong> {$payment['appointment_code']}</li>
        </ul>
        <p>Please contact QuickCare if you have any questions about this refund.</p>
        <br>
        <p>Thank you for using QuickCare.</p>
    ";

    send_email($user['email'], $subject, $body);
}

// Send email for rejected refund request
function send_refund_rejected_email($user_id, $payment, $reason) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) return;

    $subject = "Refund Request Rejected - QuickCare";
    $body = "
        <h2>Refund Request Rejected ❌</h2>
        <p>Dear {$user['name']},</p>
        <p>Your refund request has been <strong>REJECTED</strong>.</p>
        <p><strong>Reason:</strong> {$reason}</p>
        <h3>Payment Details:</h3>
        <ul>
            <li><strong>Receipt Number:</strong> {$payment['receipt_number']}</li>
            <li><strong>Amount:</strong> RM " . number_format($payment['amount'], 2) . "</li>
            <li><strong>Appointment Code:</strong> {$payment['appointment_code']}</li>
        </ul>
        <p>Please contact QuickCare if you have any questions.</p>
    ";

    send_email($user['email'], $subject, $body);
}

// Get receipt HTML for printing
function get_receipt_html($payment_id) {
    global $conn;
    ensure_payment_method_column($conn);
    
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

    if (!in_array($payment['payment_status'] ?? '', ['paid', 'approved', 'refund_requested', 'refund_rejected', 'refunded'], true)) {
        return "<p>Official receipt is available only after payment is successful.</p>";
    }

    $payment['receipt_number'] = payment_receipt_number($payment);
    
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
                <tr><td style="padding: 6px 0;"><strong>Payment Method:</strong></td><td>' . htmlspecialchars(payment_method_from_payment($payment) ?: 'QR Code / Online Banking') . '</td></tr>
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

