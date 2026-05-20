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
                $_SESSION['id'] = $user['id'];
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
    $appointment_id = $_POST['appointment_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $remarks = $_POST['remarks'] ?? '';
    
    // Validate input (transaction_id no longer required)
    if (empty($appointment_id) || empty($amount)) {
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
    
    $result = submit_payment($user_id, $appointment_id, $amount, $transaction_id, $remarks, $receipt_image);
    
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
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
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
            <div class="detail-row"><strong>Transaction ID:</strong> ' . htmlspecialchars($payment['transaction_id']) . '</div>
            <div class="detail-row"><strong>Appointment:</strong> ' . htmlspecialchars($payment['appointment_details']) . '</div>
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
    
    $result = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'");
    $row = $result->fetch_assoc();
    
    echo json_encode(['count' => $row['count']]);
    exit;
}

// ===========================================

$message = match ($action) {
    'save_profile' => 'Profile updated successfully.',
    'save_staff' => 'Staff member saved successfully.',
    'save_doctor' => 'Doctor saved successfully.',
    'save_service' => 'Service saved successfully.',
    'cancel_appointment' => 'Appointment cancelled.',
    'approve' => 'Appointment approved.',
    'reject' => 'Appointment rejected.',
    'update_status' => 'Status updated.',
    'process_payment' => 'Payment processed successfully.',
    'export_report' => 'Report exported.',
    'submit_payment' => 'Payment submitted successfully. Please wait for admin approval.',
    'approve_payment' => 'Payment has been approved. Email sent to patient.',
    'reject_payment' => 'Payment has been rejected. Email sent to patient.',
    default => 'Action completed.',
};

// ============================================

if ($action !== 'logout' && 
    $action !== 'submit_payment' && 
    $action !== 'approve_payment' && 
    $action !== 'reject_payment' && 
    $action !== 'get_payment_details' && 
    $action !== 'get_receipt' && 
    $action !== 'get_pending_payments_count') {
    
    $_SESSION['QuickCare_message'] = $message;
    $back = $_SERVER['HTTP_REFERER'] ?? page_url('dashboard');
    redirect_to($back);
}
?>