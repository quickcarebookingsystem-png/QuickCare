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
                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                        <?php echo ucfirst($payment['status']); ?>
                    </span>
                </div>
                <div class="payment-body">
                    <div class="payment-details">
                        <div class="detail-row">
                            <span class="detail-label">Appointment:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['appointment_details']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Doctor:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['doctor_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value amount">RM <?php echo number_format($payment['amount'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Transaction ID:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                        </div>
                        <?php if ($payment['status'] == 'approved'): ?>
                        <div class="detail-row">
                            <span class="detail-label">Approved By:</span>
                            <span class="detail-value">Admin on <?php echo date('d M Y', strtotime($payment['approved_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($payment['status'] == 'rejected' && !empty($payment['remarks'])): ?>
                        <div class="detail-row">
                            <span class="detail-label">Remarks:</span>
                            <span class="detail-value" style="color: var(--danger);"><?php echo nl2br(htmlspecialchars($payment['remarks'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="payment-footer">
                    <?php if ($payment['status'] == 'approved'): ?>
                    <button class="btn-print" onclick="printReceipt(<?php echo $payment['id']; ?>)">
                        Print Receipt
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($payment['status'] == 'rejected'): ?>
                    <a href="payment.php" class="btn-retry">
                        Retry Payment
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

.btn-print, .btn-retry {
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

.btn-retry {
    background: var(--warning);
    color: white;
}

.btn-retry:hover {
    background: #b85c00;
}

.receipt-modal {
    max-width: 500px;
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
</script>

<?php
app_end();
?>