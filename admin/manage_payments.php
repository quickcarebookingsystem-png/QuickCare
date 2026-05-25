<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('admin', 'payment');

$pending_payments = get_pending_payments();
$all_payments = get_all_payments();
?>

<div class="admin-payment-container">
    <!-- Stats Summary -->
    <div class="stats-summary">
        <div class="stat-box">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <span class="stat-value" id="pendingCount"><?php echo count($pending_payments); ?></span>
                <span class="stat-label">Pending Approval</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count(array_filter($all_payments, function($p) { return in_array($p['payment_status'], ['approved', 'paid'], true); })); ?></span>
                <span class="stat-label">Approved</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count(array_filter($all_payments, function($p) { return $p['payment_status'] == 'rejected'; })); ?></span>
                <span class="stat-label">Rejected</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <span class="stat-value">RM <?php 
                    $total = array_sum(array_column(array_filter($all_payments, function($p) { 
                        return in_array($p['payment_status'], ['approved', 'paid'], true);
                    }), 'amount'));
                    echo number_format($total, 2);
                ?></span>
                <span class="stat-label">Total Revenue</span>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="tab-btn active" data-filter="all">All</button>
        <button class="tab-btn" data-filter="verifying">Verifying ⏳</button>
        <button class="tab-btn" data-filter="approved">Approved ✅</button>
        <button class="tab-btn" data-filter="rejected">Rejected ❌</button>
    </div>

    <!-- Payments Table -->
    <div class="payments-table-container">
        <div class="table-wrap">
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt #</th>
                        <th>Patient</th>
                        <th>Appointment</th>
                        <th>Amount</th>
                        <th>Transaction ID</th>
                        <th>Receipt</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="paymentsTableBody">
                    <?php foreach ($all_payments as $payment): ?>
                    <?php
                        $paymentStatus = strtolower((string) $payment['payment_status']);
                        $paymentGroup = $paymentStatus;
                        if (in_array($paymentStatus, ['pending', 'verifying'], true)) {
                            $paymentGroup = 'verifying';
                        } elseif (in_array($paymentStatus, ['paid', 'approved'], true)) {
                            $paymentGroup = 'approved';
                        }
                    ?>
                    <tr data-status="<?php echo htmlspecialchars($paymentGroup); ?>" data-id="<?php echo $payment['payment_id']; ?>">
                        <td><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($payment['appointment_code']); ?></td>
                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_id']); ?></td>
                        <td>
                            <button class="btn-view" onclick="viewReceipt('<?php echo $payment['receipt_image']; ?>')">
                                View
                            </button>
                        </td>
                        <td class="actions-cell">
                            <?php if (in_array($payment['payment_status'], ['verifying', 'pending'], true)): ?>
                                <button class="btn-approve" onclick="approvePayment(<?php echo $payment['payment_id']; ?>)">
                                    Approve
                                </button>
                                <button class="btn-reject" onclick="showRejectModal(<?php echo $payment['payment_id']; ?>)">
                                    Reject
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Receipt Modal -->
<div id="receiptViewModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Payment Receipt</span>
            <button class="modal-close" onclick="closeReceiptViewModal()">✕</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <img id="receiptImage" src="" style="max-width: 100%; border-radius: 8px;">
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">Reject Payment</span>
            <button class="modal-close" onclick="closeRejectModal()">✕</button>
        </div>
        <div class="modal-body">
            <p>Please provide a reason for rejecting this payment:</p>
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="e.g., Receipt unclear, Wrong amount, etc."></textarea>
            <input type="hidden" id="rejectPaymentId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmReject()">Confirm Reject</button>
        </div>
    </div>
</div>

<style>
.admin-payment-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Stats Summary */
.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: rgba(124,51,73,0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
    display: block;
}

.stat-label {
    font-size: 13px;
    color: var(--text-muted);
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 8px 20px;
    border: 1px solid var(--border);
    background: var(--surface);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text);
}

.tab-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.tab-btn:hover:not(.active) {
    border-color: var(--primary);
    color: var(--primary);
}

/* Table */
.payments-table-container {
    background: var(--surface);
    border-radius: var(--radius);
    overflow-x: auto;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.payments-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.payments-table th,
.payments-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.payments-table th {
    background: var(--surface2);
    font-weight: 600;
    color: var(--text);
}

.payments-table tr:hover {
    background: var(--surface2);
}

/* Buttons */
.btn-view {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
    background: var(--primary);
    color: white;
}

.btn-view:hover {
    background: var(--primary-dark);
}

.btn-approve {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
    background: var(--success);
    color: white;
    margin-right: 5px;
}

.btn-approve:hover {
    background: #1e6b4a;
}

.btn-reject {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
    background: var(--danger);
    color: white;
}

.btn-reject:hover {
    background: #8a2020;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    color: var(--text);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.btn-danger {
    background: var(--danger);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    cursor: pointer;
}

.btn-danger:hover {
    background: #8a2020;
}

.text-muted {
    color: var(--text-muted);
}

.actions-cell {
    white-space: nowrap;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal {
    background: var(--surface);
    border-radius: var(--radius);
    width: 90%;
    max-width: 500px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-muted);
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 14px;
    background: var(--surface);
    color: var(--text);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
}

@media (max-width: 768px) {
    .stats-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .payments-table th,
    .payments-table td {
        padding: 8px 10px;
        font-size: 12px;
    }
    
    .actions-cell {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
}
</style>

<script>
function filterPayments(status) {
    const rows = document.querySelectorAll('#paymentsTableBody tr');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filterPayments(this.dataset.filter);
    });
});

function viewReceipt(receiptImage) {
    if (!receiptImage) {
        alert('No receipt image available');
        return;
    }
    const modal = document.getElementById('receiptViewModal');
    const img = document.getElementById('receiptImage');
    img.src = '../uploads/receipts/' + receiptImage;
    modal.style.display = 'flex';
}

function closeReceiptViewModal() {
    document.getElementById('receiptViewModal').style.display = 'none';
}

function approvePayment(paymentId) {
    if (confirm('Are you sure you want to APPROVE this payment? The user will receive an email notification.')) {
        fetch('../action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=approve_payment&payment_id=${paymentId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment approved successfully! Email sent to patient.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

let rejectPaymentId = null;

function showRejectModal(paymentId) {
    rejectPaymentId = paymentId;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    rejectPaymentId = null;
}

function confirmReject() {
    const reason = document.getElementById('rejectReason').value;
    if (!reason.trim()) {
        alert('Please provide a reason for rejection');
        return;
    }
    
    fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reject_payment&payment_id=${rejectPaymentId}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Payment rejected. Email sent to patient.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php
app_end();
?>
