<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('user', 'payment_history');

$user_id = $_SESSION['id'];
$payments = get_user_payment_history($user_id);
?>

<div class="history-container">
    <div class="page-header">
        <h1>Payment History</h1>
        <p>View all your past payments and receipts</p>
    </div>
    <?php if (($_GET['refund_requested'] ?? '') === '1'): ?>
        <div class="toast flash-message show success payment-history-flash-message">Refund request submitted. Please wait for admin approval.</div>
    <?php endif; ?>

    <?php if (empty($payments)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No Payment History</h3>
            <p>You haven't made any payments yet.</p>
        </div>
    <?php else: ?>
        <div class="payments-list">
            <?php foreach ($payments as $payment): ?>
            <?php
                $refundReceiptFile = payment_refund_receipt_file($payment['remarks'] ?? '');
                $hasOfficialReceipt = in_array($payment['payment_status'], ['approved', 'paid', 'refund_requested', 'refund_rejected', 'refunded'], true);
                $referenceLabel = $hasOfficialReceipt ? 'Receipt #' : 'Payment #';
                $referenceValue = $hasOfficialReceipt && trim((string)($payment['receipt_number'] ?? '')) !== ''
                    ? $payment['receipt_number']
                    : ($payment['payment_code'] ?? $payment['transaction_id'] ?? '-');
                $showReference = $payment['payment_status'] !== 'failed';
            ?>
            <div class="payment-card">
                <div class="payment-header">
                    <div class="payment-info">
                        <?php if ($showReference): ?>
                        <span class="payment-receipt"><?php echo htmlspecialchars($referenceLabel); ?>: <?php echo htmlspecialchars($referenceValue); ?></span>
                        <?php else: ?>
                        <span class="payment-receipt">Payment Failed</span>
                        <?php endif; ?>
                        <span class="payment-date"><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></span>
                    </div>
                    <?php
                    $badgeStatus = $payment['payment_status'];
                    $badgeText = ucfirst($payment['payment_status']);
                    if (in_array($payment['payment_status'], ['approved', 'paid'], true)) {
                        $badgeStatus = 'paid';
                        $badgeText = 'Paid';
                    } elseif (in_array($payment['payment_status'], ['pending', 'verifying'], true)) {
                        $badgeStatus = 'verifying';
                        $badgeText = 'Verifying';
                    } elseif ($payment['payment_status'] === 'refund_requested') {
                        $badgeStatus = 'refund-requested';
                        $badgeText = 'Refund Requested';
                    } elseif ($payment['payment_status'] === 'refunded') {
                        $badgeStatus = 'refunded';
                        $badgeText = 'Refunded';
                    } elseif ($payment['payment_status'] === 'refund_rejected') {
                        $badgeStatus = 'refund-rejected';
                        $badgeText = 'Refund Rejected';
                    } elseif ($payment['payment_status'] === 'failed') {
                        $badgeStatus = 'failed';
                        $badgeText = 'Failed';
                    }
                    ?>
                    <span class="badge badge-<?php echo htmlspecialchars($badgeStatus); ?>">
                        <?php echo $badgeText; ?>
                    </span>
                </div>
                <div class="payment-body">
                    <div class="payment-details">
                        <div class="detail-row">
                            <span class="detail-label">Appointment:</span>
                            <span class="detail-value"><?php echo htmlspecialchars(($payment['appointment_code'] ?? '')); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Doctor:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['doctor_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Appointment Status:</span>
                            <span class="detail-value"><?php echo appointment_badge($payment['appointment_status'] ?? '', 'user'); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value amount">RM <?php echo number_format($payment['amount'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Transaction ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                        </div>
                        <?php $paymentMethodText = payment_method_from_payment($payment); ?>
                        <?php if ($paymentMethodText !== ''): ?>
                        <div class="detail-row">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($paymentMethodText); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (in_array($payment['payment_status'], ['approved', 'paid'], true)): ?>
                        <div class="detail-row">
                            <span class="detail-label">Approved By:</span>
                            <span class="detail-value">Admin on <?php echo date('d M Y', strtotime($payment['approved_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payment['payment_status'] === 'refunded' && !empty($payment['approved_date'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Refunded On:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($payment['approved_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php
                            $paymentNote = payment_note_display($payment['payment_status'], $payment['remarks'] ?? '');
                            $showPaymentNote = $paymentNote['text'] !== '';
                        ?>
                        <?php if ($showPaymentNote): ?>
                        <div class="detail-row">
                            <span class="detail-label"><?php echo htmlspecialchars($paymentNote['label']); ?></span>
                            <span class="detail-value payment-note-<?php echo (in_array($payment['payment_status'], ['refund_requested', 'refunded'], true)) ? 'warning' : 'danger'; ?>"><?php echo nl2br(htmlspecialchars($paymentNote['text'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="payment-footer">
                    <?php if (in_array($payment['payment_status'], ['pending', 'verifying'], true)): ?>
                    <button class="btn-print" onclick='viewPaymentProof(<?php echo json_encode($payment['receipt_image'] ?? ''); ?>, "Payment Proof")'>
                        View Payment Proof
                    </button>
                    <?php endif; ?>

                    <?php if ($payment['payment_status'] === 'refunded'): ?>
                    <button class="btn-print" onclick='viewPaymentProof(<?php echo json_encode($refundReceiptFile); ?>, "Refund Proof")'>
                        View Refund Proof
                    </button>
                    <?php endif; ?>

                    <?php if (in_array($payment['payment_status'], ['approved', 'paid'], true) && in_array($payment['appointment_status'] ?? '', ['cancelled', 'confirm', 'confirmed'], true)): ?>
                    <button class="btn-print btn-refund-request" onclick="showRefundRequestModal(<?php echo $payment['payment_id']; ?>)">
                        Request Refund
                    </button>
                    <?php endif; ?>

                    <?php if (in_array($payment['payment_status'], ['approved', 'paid', 'refund_requested', 'refund_rejected'], true)): ?>
                    <button class="btn-print btn-view-receipt" onclick="printReceipt(<?php echo $payment['payment_id']; ?>)">
                        View Receipt
                    </button>
                    <?php endif; ?>
                    
                    <?php if (in_array($payment['payment_status'], ['rejected', 'failed'], true)): ?>
                    <a href="<?php echo e(page_url('payment', 'user') . '?appointment=' . urlencode($payment['appointment_code'] ?? '')); ?>" class="btn-print">
                        Retry Payment
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Refund Request Modal -->
<div id="refundRequestModal" class="modal-overlay">
    <div class="modal refund-request-modal">
        <div class="modal-header">
            <span class="modal-title">Request Refund</span>
            <button class="modal-close" onclick="closeRefundRequestModal()">X</button>
        </div>
        <div class="modal-body">
            <p class="text-muted">Please provide a reason for your refund request.</p>
            <textarea id="refundRequestReason" class="form-control" rows="3" placeholder="e.g., Appointment cancelled"></textarea>
            <input type="hidden" id="refundRequestPaymentId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeRefundRequestModal()">Cancel</button>
            <button class="btn btn-primary" onclick="confirmRefundRequest()">Submit Request</button>
        </div>
    </div>
</div>

<!-- Payment Proof Modal -->
<div id="proofModal" class="modal-overlay">
    <div class="modal proof-modal">
        <div class="modal-header">
            <span class="modal-title">Payment Proof</span>
            <button class="modal-close" onclick="closeProofModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="proof-content" id="proofContent"></div>
        </div>
    </div>
</div>

<!-- Print Receipt Modal -->
<div id="receiptModal" class="modal-overlay">
    <div class="modal receipt-modal">
        <div class="modal-header">
            <span class="modal-title">Payment Receipt</span>
            <button class="modal-close" onclick="closeReceiptModal()">✕</button>
        </div>
        <div class="modal-body" id="receiptContent">
            <!-- Receipt content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="window.print()">Print</button>
            <button class="btn btn-outline" onclick="closeReceiptModal()">Close</button>
        </div>
    </div>
</div>

<?php 
render_payment_history_scripts();
app_end();
?>
