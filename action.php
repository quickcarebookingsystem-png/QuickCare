<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db_connect.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================
// AUTH ACTIONS
// ============================================

if ($action === 'register') {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = 'user';

        if (email_exist($conn, $email)) {
            $_SESSION['message'] = "Email already registered";
            redirect_to('register.php');
            exit();
        } 

        if (create_user($conn, $name, $email, $password, $role)) {
            $_SESSION['message'] = "Registration successful";
            redirect_to('login.php');
            exit();
        }
    }
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        $user = get_user_by_email($conn, $email);
        if ($user) {
            if (password_verify($password, $user['password'])) {
                update_user_status($conn, (int)$user['user_id'], 'active');
                $_SESSION['id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['QuickCare_role'] = $user['role'];
                redirect_to(page_url('dashboard', $user['role']));
                exit();
            } else {
                $_SESSION['message'] = "Wrong password";
                redirect_to('login.php');
                exit();
            }
        } else {
            $_SESSION['message'] = "User not found";
            redirect_to('login.php');
            exit();
        }
    }
}

if ($action === 'forgot_password') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
        if (get_user_by_email($conn, $email)) {
            $token = reset_token($conn, $email);
            $link = absolute_app_url('reset_password.php?token=' . urlencode($token));
            $subject = "Password Reset Request";
            $to = $email;
            $body = 
                "<h2>Click the link below to reset your password:</h2>
                <a href='$link'>$link</a>
                <p>This link will expire in 15 minutes.</p>";
            send_email($to, $subject, $body);
            $_SESSION['message'] = "Reset link sent to your email.";
            redirect_to('login.php');
            exit();
        } else {
            $_SESSION['message'] = "Email not found.";
            redirect_to('forgot_password.php');
            exit();
        }
    }
}

if ($action === 'reset_password') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        $token = $_POST['token'] ?? $_GET['token'] ?? '';

        $user = verify_reset_token($conn, $token);

        if (!$user) {
            $_SESSION['message'] = "Invalid or expired reset link.";
            redirect_to('forgot_password.php');
            exit();
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['message'] = "Passwords do not match.";
            redirect_to('reset_password.php?token=' . $token);
            exit();
        }

        update_password($conn, $new_password, $user['email']);
        $_SESSION['message'] = "Password reset successfully.";
        redirect_to('login.php');
        exit();
    }
}

// ============================================
// LOGOUT
// ============================================

if ($action === 'logout') {
    if (isset($_SESSION['id'])) {
        update_user_status($conn, (int)$_SESSION['id'], 'inactive');
    }
    session_unset();
    session_destroy();
    redirect_to('login.php');
    exit();
}

// ============================================
// PAYMENT ACTIONS (AJAX)
// ============================================

// Handle submit payment (user upload receipt) - AJAX request
if ($action === 'submit_payment') {
    // Check if user is logged in
    if (!isset($_SESSION['id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        exit;
    }
    
    $user_id = $_SESSION['id'];
    $appointment_code = $_POST['appointment_code'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';
    
    // Validate input (transaction_id no longer required)
    if (empty($appointment_code) || empty($amount)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Handle file upload
    $receipt_image = '';
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === 0) {
        $upload_dir = __DIR__ . '/uploads/receipts/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $receipt_image = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $upload_path = $upload_dir . $receipt_image;
        
        if ($_FILES['receipt']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File too large. Max 2MB']);
            exit;
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($_FILES['receipt']['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. JPG, PNG, PDF only']);
            exit;
        }
        
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload receipt']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please upload payment receipt']);
        exit;
    }
    
    // Generate random transaction ID for internal use
    $transaction_id = 'TXN' . time() . rand(1000, 9999);

    // Explicitly set uploaded payment status
    $payment_status = 'verifying';

    $result = submit_payment($user_id, $appointment_code, $amount, $transaction_id, $remarks, $receipt_image, $payment_status);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payment submitted. Waiting for admin approval.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit payment']);
    }
    exit;
}

// Handle approve payment (admin only) - AJAX request
if ($action === 'approve_payment') {
    // Check if user is admin
    if (!isset($_SESSION['id']) || $_SESSION['QuickCare_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $payment_id = $_POST['payment_id'] ?? 0;
    $admin_id = $_SESSION['id'];
    
    if (empty($payment_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }
    
    $result = approve_payment($payment_id, $admin_id);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payment approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve payment']);
    }
    exit;
}

// Handle reject payment (admin only) - AJAX request
if ($action === 'reject_payment') {
    if (!isset($_SESSION['id']) || $_SESSION['QuickCare_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $payment_id = $_POST['payment_id'] ?? 0;
    $reason = $_POST['reason'] ?? 'No reason provided';
    $admin_id = $_SESSION['id'];
    
    if (empty($payment_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }
    
    $result = reject_payment($payment_id, $admin_id, $reason);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payment rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject payment']);
    }
    exit;
}

// Handle get payment details (for staff/admin) - AJAX request
if ($action === 'get_payment_details') {
    global $conn;
    
    $payment_id = $_POST['payment_id'] ?? 0;
    
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
    
    if ($payment) {
        $html = '
        <div class="payment-details-modal">
            <div class="detail-row"><strong>Receipt #:</strong> ' . htmlspecialchars($payment['receipt_number']) . '</div>
            <div class="detail-row"><strong>Patient:</strong> ' . htmlspecialchars($payment['user_name']) . '</div>
            <div class="detail-row"><strong>Email:</strong> ' . htmlspecialchars($payment['user_email']) . '</div>
            <div class="detail-row"><strong>Amount:</strong> RM ' . number_format($payment['amount'], 2) . '</div>
            <div class="detail-row"><strong>Status:</strong> ' . htmlspecialchars(ucfirst($payment['payment_status'])) . '</div>
            <div class="detail-row"><strong>Transaction ID:</strong> ' . htmlspecialchars($payment['transaction_id']) . '</div>
            <div class="detail-row"><strong>Appointment Code:</strong> ' . htmlspecialchars($payment['appointment_code']) . '</div>
            <div class="detail-row"><strong>Remarks:</strong> ' . nl2br(htmlspecialchars($payment['remarks'])) . '</div>
            <div class="detail-row"><strong>Submitted:</strong> ' . date('d/m/Y h:i A', strtotime($payment['payment_date'])) . '</div>
        </div>';
        
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
    }
    exit;
}

// Handle get receipt for printing - AJAX request
if ($action === 'get_receipt') {
    $payment_id = $_POST['payment_id'] ?? 0;
    
    $html = get_receipt_html($payment_id);
    
    if ($html) {
        echo json_encode(['success' => true, 'html' => $html]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
    }
    exit;
}

// Handle get pending payments count - AJAX request
if ($action === 'get_pending_payments_count') {
    global $conn;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status IN ('verifying', 'pending')");
    $row = $result->fetch_assoc();
    
    echo json_encode(['count' => $row['count']]);
    exit;
}

// ===========================================
if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = current_user($conn);
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $back = $_SERVER['HTTP_REFERER'] ?? page_url('profile');

    if (!$user) {
        $_SESSION['QuickCare_message'] = "Please login again.";
        $_SESSION['QuickCare_message_type'] = "error";
        redirect_to(app_url('login.php'));
    }

    if (!password_verify($currentPassword, $user['password'])) {
        $_SESSION['QuickCare_message'] = "Current password is incorrect.";
        $_SESSION['QuickCare_message_type'] = "error";
        redirect_to($back);
    }

    if ($currentPassword === $newPassword) {
        $_SESSION['QuickCare_message'] = "New password cannot be the same as your current password.";
        $_SESSION['QuickCare_message_type'] = "error";
        redirect_to($back);
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['QuickCare_message'] = "New password and confirm password do not match.";
        $_SESSION['QuickCare_message_type'] = "error";
        redirect_to($back);
    }

    update_password($conn, $newPassword, $user['email']);
    $_SESSION['QuickCare_message'] = "Password updated successfully.";
    $_SESSION['QuickCare_message_type'] = "success";
    redirect_to($back);
}

if ($action === 'save_profile' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id'], $_POST['name'], $_POST['email'])) {
    $id = (int) $_SESSION['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phoneNumber = format_phone_number($_POST['phone_number'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $bloodType = trim($_POST['blood_type'] ?? '');

    if ($dateOfBirth !== '') {
        $dob = new DateTime($dateOfBirth);
        $today = new DateTime('today');
        $age = $today->diff($dob)->y;

        if ($dob > $today || $age > 120) {
            $_SESSION['QuickCare_message'] = "Date of birth must be between 0 and 120 years old.";
            $_SESSION['QuickCare_message_type'] = "error";
            redirect_to($_SERVER['HTTP_REFERER'] ?? page_url('profile'));
        }
    }

    $gender = $gender === '' ? null : $gender;
    $dateOfBirth = $dateOfBirth === '' ? null : $dateOfBirth;
    $bloodType = $bloodType === '' ? null : $bloodType;

    $stmt = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, phone_number = ?, gender = ?, date_of_birth = ?, blood_type = ?
        WHERE user_id = ?
    ");
    $stmt->bind_param("ssssssi", $name, $email, $phoneNumber, $gender, $dateOfBirth, $bloodType, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['name'] = $name;
}

if ($action === 'save_staff' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phoneNumber = format_phone_number($_POST['phone_number'] ?? '');
    $role = 'staff';
    $back = $_SERVER['HTTP_REFERER'] ?? page_url('staff', 'admin');

    if ($name === '' || $email === '' || $password === '') {
        $_SESSION['QuickCare_message'] = 'Please fill in staff name, email, and password.';
        $_SESSION['QuickCare_message_type'] = 'error';
        redirect_to($back);
    }

    if (email_exist($conn, $email)) {
        $_SESSION['QuickCare_message'] = 'Email already registered.';
        $_SESSION['QuickCare_message_type'] = 'error';
        redirect_to($back);
    }

    $stmt = $conn->prepare("
        SELECT MAX(CAST(SUBSTRING(user_code, 2) AS UNSIGNED)) AS max_id
        FROM users
        WHERE role = ?
    ");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $nextId = ((int)($row['max_id'] ?? 0)) + 1;
    $userCode = 'S' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $userStatus = 'inactive';

    $stmt = $conn->prepare("
        INSERT INTO users (user_code, name, email, password, role, phone_number, user_status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssss", $userCode, $name, $email, $hashedPassword, $role, $phoneNumber, $userStatus);
    $stmt->execute();
    $stmt->close();
}

if ($action === 'update_staff' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = format_phone_number($_POST['phone_number'] ?? '');
    $back = $_SERVER['HTTP_REFERER'] ?? page_url('staff', 'admin');

    if ($id <= 0 || $name === '' || $email === '') {
        $_SESSION['QuickCare_message'] = 'Please fill in staff name and email.';
        $_SESSION['QuickCare_message_type'] = 'error';
        redirect_to($back);
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $_SESSION['QuickCare_message'] = 'Email already registered.';
        $_SESSION['QuickCare_message_type'] = 'error';
        redirect_to($back);
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, phone_number = ?
        WHERE user_id = ? AND role = 'staff'
    ");
    $stmt->bind_param("sssi", $name, $email, $phoneNumber, $id);
    $stmt->execute();
    $stmt->close();
}

if ($action === 'book_appointment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = current_user($conn);
    $serviceInput = $_POST['service'] ?? '';
    $selectedServices = [];
    if (is_array($serviceInput)) {
        foreach ($serviceInput as $serviceName) {
            $serviceName = trim((string) $serviceName);
            if ($serviceName !== '') {
                $selectedServices[] = $serviceName;
            }
        }
    } else {
        $serviceParts = explode(',', (string) $serviceInput);
        foreach ($serviceParts as $serviceName) {
            $serviceName = trim($serviceName);
            if ($serviceName !== '') {
                $selectedServices[] = $serviceName;
            }
        }
    }
    $selectedServices = array_values(array_unique($selectedServices));

    $doctor = trim($_POST['doctor'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($selectedServices)) {
        $_SESSION['QuickCare_message'] = "Please select at least one service.";
        redirect_to(page_url('book', $_SESSION['QuickCare_role'] ?? 'user'));
    }

    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if ($doctor === '' || !$dateObj || $time === '') {
        $_SESSION['QuickCare_message'] = "Please choose a doctor, date and time.";
        $_SESSION['QuickCare_message_type'] = "error";
        redirect_to(page_url('book', $_SESSION['QuickCare_role'] ?? 'user'));
    }

    $today = new DateTime('today');
    if ($dateObj < $today) {
        $_SESSION['QuickCare_message'] = "Please choose today or a future appointment date.";
        $_SESSION['QuickCare_message_type'] = "error";
        redirect_to(page_url('book', $_SESSION['QuickCare_role'] ?? 'user'));
    }

    $availableDay = $dateObj->format('D');
    $appointmentTime = strlen($time) === 5 ? $time . ':00' : $time;
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM doctor_schedule ds
        INNER JOIN doctors d ON d.doctor_id = ds.doctor_id
        WHERE d.doctor_name = ?
          AND ds.available_day = ?
          AND ds.start_time <= ?
          AND ds.end_time > ?
    ");
    $stmt->bind_param("ssss", $doctor, $availableDay, $appointmentTime, $appointmentTime);
    $stmt->execute();
    $scheduleRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)($scheduleRow['total'] ?? 0) === 0) {
        $_SESSION['QuickCare_message'] = "Selected time is not available for this doctor.";
        redirect_to(page_url('book', $_SESSION['QuickCare_role'] ?? 'user'));
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM appointments
        WHERE doctor_name = ?
          AND appointment_date = ?
          AND appointment_time = ?
          AND appointment_status NOT IN ('rejected', 'cancelled')
    ");
    $stmt->bind_param("sss", $doctor, $date, $appointmentTime);
    $stmt->execute();
    $bookingRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)($bookingRow['total'] ?? 0) > 0) {
        $_SESSION['QuickCare_message'] = "Selected time slot is already booked.";
        redirect_to(page_url('book', $_SESSION['QuickCare_role'] ?? 'user'));
    }

    $amount = 0.0;
    $stmt = $conn->prepare("SELECT service_price FROM services WHERE service_name = ?");
    foreach ($selectedServices as $serviceName) {
        $stmt->bind_param("s", $serviceName);
        $stmt->execute();
        $serviceRow = $stmt->get_result()->fetch_assoc();
        $amount += (float) ($serviceRow['service_price'] ?? 0);
    }
    $stmt->close();

    $service = implode(', ', $selectedServices);

    $result = $conn->query("
        SELECT MAX(CAST(SUBSTRING(appointment_code, 5) AS UNSIGNED)) AS max_code
        FROM appointments
        WHERE appointment_code LIKE 'APT-%'
    ");
    $row = $result ? $result->fetch_assoc() : null;
    $nextCode = ((int) ($row['max_code'] ?? 0)) + 1;
    $appointmentCode = 'APT-' . str_pad($nextCode, 4, '0', STR_PAD_LEFT);
    $name = $user['name'] ?? ($_SESSION['name'] ?? '');
    $userId = (int) ($user['user_id'] ?? ($_SESSION['id'] ?? 0));
    $appointment_status = 'confirmed';
    $payment_status = 'pending';

    $hasUserIdColumn = false;
    $columnCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'user_id'");
    if ($columnCheck && $columnCheck->num_rows > 0) {
        $hasUserIdColumn = true;
    }

    if ($hasUserIdColumn) {
        $stmt = $conn->prepare("
            INSERT INTO appointments (appointment_code, user_id, name, doctor_name, service_name, appointment_date, appointment_time, appointment_status, payment_status, amount, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sisssssssds", $appointmentCode, $userId, $name, $doctor, $service, $date, $time, $appointment_status, $payment_status, $amount, $notes);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO appointments (appointment_code, name, doctor_name, service_name, appointment_date, appointment_time, appointment_status, payment_status, amount, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssssds", $appointmentCode, $name, $doctor, $service, $date, $time, $appointment_status, $payment_status, $amount, $notes);
    }
    $stmt->execute();
    $stmt->close();

    $_SESSION['QuickCare_message'] = "Appointment booked successfully.";
    redirect_to(page_url('appointments', $_SESSION['QuickCare_role'] ?? 'user'));
}

if ($action === 'save_appointment_notes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = current_user($conn);
    $appointmentCode = trim($_POST['appointment_code'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($user && $appointmentCode !== '') {
        $stmt = $conn->prepare("UPDATE appointments SET notes = ? WHERE appointment_code = ? AND name = ?");
        $stmt->bind_param("sss", $notes, $appointmentCode, $user['name']);
        $stmt->execute();
        $stmt->close();
    }
}

if (in_array($action, ['cancel_appointment', 'approve', 'reject', 'update_status'], true)) {
    $appointmentCode = $_GET['id'] ?? $_POST['id'] ?? '';
    $appointment_status = match ($action) {
        'cancel_appointment' => 'cancelled',
        'approve' => 'approved',
        'reject' => 'rejected',
        'update_status' => 'completed',
    };

    if ($appointmentCode !== '') {
        if ($appointment_status === 'completed') {
            $stmt = $conn->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE appointment_code = ?");
            $stmt->bind_param("s", $appointmentCode);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$appointment || !appointment_time_has_passed($appointment['appointment_date'] ?? '', $appointment['appointment_time'] ?? '')) {
                $_SESSION['QuickCare_message'] = 'Appointment can only be completed after the appointment date and time has passed.';
                $_SESSION['QuickCare_message_type'] = 'error';
                $back = $_SERVER['HTTP_REFERER'] ?? page_url('dashboard');
                redirect_to($back);
            }
        }

        if ($appointment_status === 'completed') {
            $stmt = $conn->prepare("UPDATE appointments SET appointment_status = ?, payment_status = 'paid' WHERE appointment_code = ?");
        } elseif ($appointment_status === 'cancelled') {
            $stmt = $conn->prepare("UPDATE appointments SET appointment_status = ?, payment_status = CASE WHEN payment_status = 'pending' THEN 'unpaid' ELSE payment_status END WHERE appointment_code = ?");
        } else {
            $stmt = $conn->prepare("UPDATE appointments SET appointment_status = ? WHERE appointment_code = ?");
        }
        $stmt->bind_param("ss", $appointment_status, $appointmentCode);
        $stmt->execute();
        $stmt->close();
    }
}

if ($action === 'save_service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $icon = trim($_POST['icon'] ?? '🏥');
    $name = trim($_POST['name'] ?? '');
    $fee = (float) ($_POST['fee'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO services (service_icon, service_name, service_price, service_description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $icon, $name, $fee, $description);
        $stmt->execute();
        $stmt->close();
    }
}

if ($action === 'export_report') {
    if (!isset($_SESSION['id']) || ($_SESSION['QuickCare_role'] ?? '') !== 'admin') {
        redirect_to(app_url('login.php'));
    }

    $selectedMonth = (int)($_GET['month'] ?? 0);
    $selectedYear = (int)($_GET['year'] ?? 0);
    $appointmentWhere = '';
    $paymentWhere = '';
    $filterTypes = '';
    $appointmentParams = [];
    $paymentParams = [];
    $reportPeriod = 'All Time';

    if ($selectedMonth >= 1 && $selectedMonth <= 12 && $selectedYear > 0) {
        $appointmentWhere = ' WHERE MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?';
        $paymentWhere = ' WHERE MONTH(payment_date) = ? AND YEAR(payment_date) = ?';
        $filterTypes = 'ii';
        $appointmentParams = [$selectedMonth, $selectedYear];
        $paymentParams = [$selectedMonth, $selectedYear];
        $reportPeriod = date('F', mktime(0, 0, 0, $selectedMonth, 1)) . ' ' . $selectedYear;
    }

    $appointments = fetch_all_assoc(
        $conn,
        "SELECT appointment_id, appointment_code, name, doctor_name, service_name,
                appointment_date, appointment_time, appointment_status, payment_status, amount, created_at
         FROM appointments" . $appointmentWhere . "
         ORDER BY created_at DESC, appointment_id DESC",
        $filterTypes,
        $appointmentParams
    );
    $appointmentSummary = fetch_all_assoc(
        $conn,
        "SELECT
            COUNT(*) AS total,
            SUM(appointment_status = 'completed') AS completed,
            SUM(appointment_status IN ('confirmed', 'confirm')) AS pending,
            SUM(appointment_status = 'cancelled') AS cancelled
         FROM appointments" . $appointmentWhere,
        $filterTypes,
        $appointmentParams
    )[0] ?? ['total' => 0, 'completed' => 0, 'pending' => 0, 'cancelled' => 0];
    $appointmentMonthlyRows = fetch_all_assoc(
        $conn,
        "SELECT
            DATE_FORMAT(appointment_date, '%M %Y') AS month_label,
            COUNT(*) AS total,
            SUM(appointment_status = 'completed') AS completed
         FROM appointments" . $appointmentWhere . "
         GROUP BY YEAR(appointment_date), MONTH(appointment_date)
         ORDER BY YEAR(appointment_date) DESC, MONTH(appointment_date) DESC",
        $filterTypes,
        $appointmentParams
    );
    $paymentSummary = fetch_all_assoc(
        $conn,
        "SELECT
            COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'approved') THEN amount ELSE 0 END), 0) AS revenue,
            SUM(payment_status IN ('paid', 'approved')) AS paid_count,
            COALESCE(SUM(CASE WHEN payment_status IN ('pending', 'verifying') THEN amount ELSE 0 END), 0) AS pending_amount
         FROM payments" . $paymentWhere,
        $filterTypes,
        $paymentParams
    )[0] ?? ['revenue' => 0, 'paid_count' => 0, 'pending_amount' => 0];
    $paymentMonthlyRows = fetch_all_assoc(
        $conn,
        "SELECT
            DATE_FORMAT(payment_date, '%M %Y') AS month_label,
            COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'approved') THEN amount ELSE 0 END), 0) AS revenue,
            SUM(payment_status IN ('paid', 'approved')) AS invoices
         FROM payments" . $paymentWhere . "
         GROUP BY YEAR(payment_date), MONTH(payment_date)
         ORDER BY YEAR(payment_date) DESC, MONTH(payment_date) DESC",
        $filterTypes,
        $paymentParams
    );

    $escapePdf = function ($text) {
        $text = preg_replace('/[^\x20-\x7E]/', '', (string) $text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
    };
    $pdfText = function ($x, $y, $text, $size = 10) use ($escapePdf) {
        return "BT /F1 {$size} Tf {$x} {$y} Td (" . $escapePdf($text) . ") Tj ET\n";
    };

    $pages = [];
    $content = '';
    $y = 800;
    $content .= $pdfText(50, $y, 'QuickCare Report', 18);
    $y -= 24;
    $content .= $pdfText(50, $y, 'Generated: ' . date('d M Y, H:i'), 10);
    $y -= 16;
    $content .= $pdfText(50, $y, 'Period: ' . $reportPeriod, 10);
    $y -= 34;
    $content .= $pdfText(50, $y, 'Appointment Report', 14);
    $y -= 22;
    $content .= $pdfText(60, $y, 'Total: ' . (int)($appointmentSummary['total'] ?? 0), 10);
    $content .= $pdfText(180, $y, 'Completed: ' . (int)($appointmentSummary['completed'] ?? 0), 10);
    $content .= $pdfText(330, $y, 'Pending: ' . (int)($appointmentSummary['pending'] ?? 0), 10);
    $content .= $pdfText(450, $y, 'Cancelled: ' . (int)($appointmentSummary['cancelled'] ?? 0), 10);
    $y -= 30;
    $content .= $pdfText(60, $y, 'Month', 10);
    $content .= $pdfText(230, $y, 'Total', 10);
    $content .= $pdfText(310, $y, 'Completed', 10);
    $content .= $pdfText(430, $y, 'Rate', 10);
    $y -= 18;
    foreach ($appointmentMonthlyRows as $row) {
        $total = (int)($row['total'] ?? 0);
        $completed = (int)($row['completed'] ?? 0);
        $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
        $content .= $pdfText(60, $y, $row['month_label'] ?? '', 10);
        $content .= $pdfText(230, $y, $total, 10);
        $content .= $pdfText(310, $y, $completed, 10);
        $content .= $pdfText(430, $y, $rate . '%', 10);
        $y -= 16;
    }

    $y -= 24;
    $content .= $pdfText(50, $y, 'Payment Report', 14);
    $y -= 22;
    $content .= $pdfText(60, $y, 'Total Revenue: RM ' . number_format((float)($paymentSummary['revenue'] ?? 0), 2), 10);
    $content .= $pdfText(250, $y, 'Paid Invoices: ' . (int)($paymentSummary['paid_count'] ?? 0), 10);
    $content .= $pdfText(410, $y, 'Pending: RM ' . number_format((float)($paymentSummary['pending_amount'] ?? 0), 2), 10);
    $y -= 30;
    $content .= $pdfText(60, $y, 'Month', 10);
    $content .= $pdfText(230, $y, 'Revenue', 10);
    $content .= $pdfText(360, $y, 'Invoices', 10);
    $y -= 18;
    foreach ($paymentMonthlyRows as $row) {
        $content .= $pdfText(60, $y, $row['month_label'] ?? '', 10);
        $content .= $pdfText(230, $y, 'RM ' . number_format((float)($row['revenue'] ?? 0), 2), 10);
        $content .= $pdfText(360, $y, (int)($row['invoices'] ?? 0), 10);
        $y -= 16;
    }
    $pages[] = $content;

    $content = '';
    $y = 800;
    $content .= $pdfText(50, $y, 'All Appointments', 16);
    $y -= 28;
    $content .= $pdfText(40, $y, 'Code', 8);
    $content .= $pdfText(105, $y, 'Patient', 8);
    $content .= $pdfText(210, $y, 'Doctor', 8);
    $content .= $pdfText(325, $y, 'Date', 8);
    $content .= $pdfText(390, $y, 'Time', 8);
    $content .= $pdfText(440, $y, 'Status', 8);
    $content .= $pdfText(500, $y, 'Payment', 8);
    $y -= 16;
    foreach ($appointments as $appointment) {
        if ($y < 50) {
            $pages[] = $content;
            $content = '';
            $y = 800;
            $content .= $pdfText(50, $y, 'All Appointments', 16);
            $y -= 28;
        }
        $content .= $pdfText(40, $y, $appointment['appointment_code'] ?? '', 8);
        $content .= $pdfText(105, $y, substr($appointment['name'] ?? '', 0, 18), 8);
        $content .= $pdfText(210, $y, substr($appointment['doctor_name'] ?? '', 0, 18), 8);
        $content .= $pdfText(325, $y, $appointment['appointment_date'] ?? '', 8);
        $content .= $pdfText(390, $y, format_time_display($appointment['appointment_time'] ?? ''), 8);
        $content .= $pdfText(440, $y, $appointment['appointment_status'] ?? '', 8);
        $content .= $pdfText(500, $y, $appointment['payment_status'] ?? '', 8);
        $y -= 14;
    }
    $pages[] = $content;

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $pageKids = [];
    $pageCount = count($pages);
    $fontObjectNumber = 3 + ($pageCount * 2);
    foreach ($pages as $index => $pageContent) {
        $pageObjectNumber = 3 + ($index * 2);
        $contentObjectNumber = $pageObjectNumber + 1;
        $pageKids[] = $pageObjectNumber . ' 0 R';
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontObjectNumber} 0 R >> >> /Contents {$contentObjectNumber} 0 R >>";
        $objects[] = "<< /Length " . strlen($pageContent) . " >>\nstream\n{$pageContent}endstream";
    }
    array_splice($objects, 1, 0, "<< /Type /Pages /Kids [" . implode(' ', $pageKids) . "] /Count {$pageCount} >>");
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $offsets[] = strlen($pdf);
        $objectNumber = $i + 1;
        $pdf .= "{$objectNumber} 0 obj\n{$object}\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

    $filename = 'quickcare_report_' . date('Ymd_His') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

// Handle refund request (user only) - AJAX request
if ($action === 'request_refund') {
    if (!isset($_SESSION['id']) || ($_SESSION['QuickCare_role'] ?? '') !== 'user') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $payment_id = $_POST['payment_id'] ?? 0;
    $reason = $_POST['reason'] ?? 'No reason provided';
    $user_id = $_SESSION['id'];

    if (empty($payment_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }

    $result = request_refund($user_id, $payment_id, $reason);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Refund request submitted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to request refund']);
    }
    exit;
}

// Handle refund payment (admin only) - AJAX request
if ($action === 'refund_payment') {
    if (!isset($_SESSION['id']) || $_SESSION['QuickCare_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $payment_id = $_POST['payment_id'] ?? 0;
    $reason = $_POST['reason'] ?? 'No reason provided';
    $admin_id = $_SESSION['id'];

    if (empty($payment_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }

    $result = refund_payment($payment_id, $admin_id, $reason);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Payment refunded']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to refund payment']);
    }
    exit;
}

// Handle reject refund request (admin only) - AJAX request
if ($action === 'reject_refund') {
    if (!isset($_SESSION['id']) || $_SESSION['QuickCare_role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $payment_id = $_POST['payment_id'] ?? 0;
    $reason = $_POST['reason'] ?? 'No reason provided';
    $admin_id = $_SESSION['id'];

    if (empty($payment_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
        exit;
    }

    $result = reject_refund_request($payment_id, $admin_id, $reason);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Refund request rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject refund request']);
    }
    exit;
}

if ($action === 'save_doctor' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $icon = trim($_POST['icon'] ?? '👨‍⚕️');
    $name = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO doctors (doctor_icon, doctor_name, doctor_specialist) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $icon, $name, $specialization);
        $stmt->execute();
        $stmt->close();
    }
}

if ($action === 'delete') {
    $type = $_GET['type'] ?? '';
    $id = (int) ($_GET['id'] ?? 0);
    if ($type === 'doctor' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM doctors WHERE doctor_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($type === 'staff' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'staff'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

$message = match ($action) {
    'save_profile' => 'Profile updated successfully.',
    'save_staff' => 'Staff member saved successfully.',
    'update_staff' => 'Staff member updated successfully.',
    'save_doctor' => 'Doctor saved successfully.',
    'save_service' => 'Service saved successfully.',
    'save_appointment_notes' => 'Appointment notes updated.',
    'cancel_appointment' => 'Appointment cancelled.',
    'approve' => 'Appointment approved.',
    'reject' => 'Appointment rejected.',
    'update_status' => 'Status updated.',
    'process_payment' => 'Payment processed successfully.',
    'export_report' => 'Report exported.',
    'delete' => 'Deleted successfully.',
    'submit_payment' => 'Payment submitted successfully. Please wait for admin approval.',
    'approve_payment' => 'Payment has been approved. Email sent to patient.',
    'reject_payment' => 'Payment has been rejected. Email sent to patient.',
    'request_refund' => 'Refund request submitted. Please wait for admin approval.',
    'refund_payment' => 'Payment has been refunded. Email sent to patient.',
    'reject_refund' => 'Refund request has been rejected. Email sent to patient.',
    default => 'Action completed.',
};

// ============================================

if ($action !== 'logout' && 
    $action !== 'submit_payment' && 
    $action !== 'approve_payment' && 
    $action !== 'reject_payment' && 
    $action !== 'request_refund' && 
    $action !== 'refund_payment' && 
    $action !== 'reject_refund' && 
    $action !== 'get_payment_details' && 
    $action !== 'get_receipt' && 
    $action !== 'get_pending_payments_count') {
    
    $_SESSION['QuickCare_message'] = $message;
    $back = $_SERVER['HTTP_REFERER'] ?? page_url('dashboard');
    redirect_to($back);
}
?>
