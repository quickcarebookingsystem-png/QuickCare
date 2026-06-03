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

    <?php if (empty($payments)): ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>No Payment History</h3>
            <p>You haven't made any payments yet.</p>
        </div>
    <?php else: ?>
        <div class="payments-list">
            <?php foreach ($payments as $payment): ?>
            <div class="payment-card">
                <div class="payment-header">
                    <div class="payment-info">
                        <span class="payment-receipt">Receipt #: <?php echo htmlspecialchars($payment['receipt_number']); ?></span>
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
                            <span class="detail-value"><?php echo htmlspecialchars(ucfirst($payment['appointment_status'] ?? '')); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value amount">RM <?php echo number_format($payment['amount'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Transaction ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                        </div>
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
                            <span class="detail-value" style="color: <?php echo (in_array($payment['payment_status'], ['refund_requested', 'refunded'], true)) ? 'var(--warning)' : 'var(--danger)'; ?>;"><?php echo nl2br(htmlspecialchars($paymentNote['text'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="payment-footer">
                    <?php if (in_array($payment['payment_status'], ['pending', 'verifying'], true)): ?>
                    <button class="btn-print" onclick='viewPaymentProof(<?php echo json_encode($payment['receipt_image'] ?? ''); ?>)'>
                        View Payment Proof
                    </button>
                    <?php endif; ?>

                    <?php if (($payment['appointment_status'] ?? '') === 'cancelled' && in_array($payment['payment_status'], ['approved', 'paid'], true)): ?>
                    <button class="btn-print btn-refund-request" onclick="showRefundRequestModal(<?php echo $payment['payment_id']; ?>)">
                        Request Refund
                    </button>
                    <?php endif; ?>

                    <?php if (in_array($payment['payment_status'], ['approved', 'paid'], true)): ?>
                    <button class="btn-print btn-view-receipt" onclick="printReceipt(<?php echo $payment['payment_id']; ?>)">
                        View Receipt
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($payment['payment_status'] == 'rejected'): ?>
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
<div id="refundRequestModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 420px;">
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
<div id="proofModal" class="modal-overlay" style="display: none;">
    <div class="modal proof-modal">
        <div class="modal-header">
            <span class="modal-title">Payment Proof</span>
            <button class="modal-close" onclick="closeProofModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="proof-content" id="proofContent"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeProofModal()">Close</button>
        </div>
    </div>
</div>

<!-- Print Receipt Modal -->
<div id="receiptModal" class="modal-overlay" style="display: none;">
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

<style>
.history-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    text-align: center;
}

.page-header h1 {
    font-size: 28px;
    color: var(--text);
}

.page-header p {
    color: var(--text-muted);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--surface);
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin-bottom: 10px;
    color: var(--text);
}

.empty-state p {
    color: var(--text-muted);
}

.payments-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.payment-card {
    background: var(--surface);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.payment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: var(--surface2);
    border-bottom: 1px solid var(--border);
}

.payment-info {
    display: flex;
    flex-direction: column;
}

.payment-receipt {
    font-weight: 600;
    color: var(--text);
}

.payment-date {
    font-size: 12px;
    color: var(--text-muted);
}

.payment-header .badge {
    min-width: 96px;
    justify-content: center;
    text-align: center;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: rgba(194,122,26,0.12);
    color: var(--warning);
}

.status-approved {
    background: rgba(42,127,90,0.12);
    color: var(--success);
}

.status-rejected {
    background: rgba(176,48,48,0.12);
    color: var(--danger);
}

.payment-body {
    padding: 20px;
}

.payment-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.detail-label {
    color: var(--text-muted);
    font-size: 14px;
}

.detail-value {
    color: var(--text);
    font-weight: 500;
}

.detail-value.amount {
    color: var(--primary);
    font-size: 18px;
}

.payment-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-print {
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    border: none;
}

.btn-print {
    background: var(--primary);
    color: white;
}

.btn-print:hover {
    background: var(--primary-dark);
}

.btn-refund-request {
    background: var(--warning);
    margin-right: auto;
}

.btn-refund-request:hover {
    background: #a16207;
}

.btn-view-receipt {
    margin-left: auto;
}

.receipt-modal {
    max-width: 500px;
}

.proof-modal {
    max-width: 680px;
}

.proof-content {
    min-height: 280px;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.proof-content img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: var(--radius-sm);
    object-fit: contain;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 15px 20px;
    border-top: 1px solid var(--border);
}

@media print {
    body * {
        visibility: hidden;
    }
    .receipt-modal, .receipt-modal * {
        visibility: visible;
    }
    .receipt-modal {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        margin: 0;
    }
    .modal-footer {
        display: none;
    }
}
</style>

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
        alert('Error loading receipt');
    }
}

function closeReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
}

function viewPaymentProof(receiptImage) {
    const modal = document.getElementById('proofModal');
    const content = document.getElementById('proofContent');
    if (!modal || !content) return;

    if (!receiptImage) {
        content.innerHTML = '<p class="text-muted">No payment proof uploaded.</p>';
        modal.style.display = 'flex';
        return;
    }

    const proofUrl = '../uploads/receipts/' + encodeURIComponent(receiptImage);
    const extension = receiptImage.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        content.innerHTML = `<img src="${proofUrl}" alt="Payment proof">`;
    } else {
        content.innerHTML = `<a class="btn btn-outline" target="_blank" rel="noopener" href="${proofUrl}">Open Payment Proof</a>`;
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

async function confirmRefundRequest() {
    const reason = document.getElementById('refundRequestReason').value;
    if (!reason.trim()) {
        alert('Please provide a reason for refund request');
        return;
    }

    const response = await fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=request_refund&payment_id=${refundRequestPaymentId}&reason=${encodeURIComponent(reason)}`
    });

    const data = await response.json();
    if (data.success) {
        alert('Refund request submitted. Please wait for admin approval.');
        location.reload();
    } else {
        alert('Error: ' + data.message);
    }
}
</script>

<?php
app_end();
?>
