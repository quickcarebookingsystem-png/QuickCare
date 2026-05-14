<?php
require_once __DIR__ . '/functions.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'register') {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        //get all data from register.php
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // default role

        // check email exists
        if (email_exist($conn, $email)) {
            $_SESSION['message'] = "Email already registered";
            redirect_to('register.php');
            exit();
        } 

        //insert database
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
            // send reset link to email
            $token = reset_token($conn, $email);
            $link = absolute_app_url('reset_password.php?token=' . urlencode($token));
            $subject = "Password Reset Request";
            $to = $email;
            $body = 
                "<h2>Click the link below to reset your password:</h2>
                <a href='$link'>$link</a>
                <p>This link will expire in 15 minutes.</p>"
                ;
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

if ($action === 'logout') {
    session_unset();
    session_destroy();
    redirect_to('login.php');
}

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
    default => 'Action completed.',
};

$_SESSION['QuickCare_message'] = $message;
$back = $_SERVER['HTTP_REFERER'] ?? page_url('dashboard');
redirect_to($back);
?>
