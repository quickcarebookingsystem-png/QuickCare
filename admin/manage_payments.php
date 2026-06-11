<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('admin', 'payment');

$pending_payments = get_pending_payments();
$all_payments = get_all_payments();
$admin_queue_count = count(array_filter($all_payments, function($p) {
    return in_array($p['payment_status'], ['verifying', 'refund_requested'], true);
}));
?>

<div class="admin-payment-container">
    <!-- Stats Summary -->
    <div class="stats-summary">
        <div class="stat-box">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <span class="stat-value" id="pendingCount"><?php echo $admin_queue_count; ?></span>
                <span class="stat-label">Admin Queue</span>
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
        <div class="stat-box">
            <div class="stat-icon">↩️</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo count(array_filter($all_payments, function($p) { return $p['payment_status'] == 'refunded'; })); ?></span>
                <span class="stat-label">Refunded</span>
            </div>
        </div>
    </div>

    <div class="toolbar">
        <div class="search-input-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" class="form-control" id="searchPayment" placeholder="Search payments...">
        </div>
        <div class="filter-group">
            <select class="filter-select payment-filter-select" id="paymentStatusFilter">
                <option value="queue" selected>Action Required</option>
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="verifying">Verifying</option>
                <option value="approved">Paid</option>
                <option value="failed">Failed</option>
                <option value="rejected">Rejected</option>
                <option value="refund_requested">Refund Requests</option>
                <option value="refunded">Refunded</option>
                <option value="refund_rejected">Refund Rejected</option>
            </select>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="payments-table-container">
        <div class="table-wrap">
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Patient Name</th>
                        <th>Appointment</th>
                        <th>Amount</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="paymentsTableBody">
                    <?php foreach ($all_payments as $payment): ?>
                    <?php
                        $paymentStatus = strtolower((string) $payment['payment_status']);
                        $paymentId = (int)($payment['payment_id'] ?? 0);
                        $paymentGroup = $paymentStatus;
                        if ($paymentStatus === 'pending') {
                            $paymentGroup = 'pending';
                        } elseif ($paymentStatus === 'verifying') {
                            $paymentGroup = 'verifying';
                        } elseif (in_array($paymentStatus, ['paid', 'approved'], true)) {
                            $paymentGroup = 'approved';
                        } elseif ($paymentStatus === 'refund_requested') {
                            $paymentGroup = 'refund_requested';
                        } elseif ($paymentStatus === 'refund_rejected') {
                            $paymentGroup = 'refund_rejected';
                        }
                        $badgeStatus = $paymentStatus === 'approved' ? 'paid' : str_replace('_', '-', $paymentStatus);
                        $refundReceiptFile = payment_refund_receipt_file($payment['remarks'] ?? '');
                        $receiptToView = ($paymentStatus === 'refunded' && $refundReceiptFile !== '')
                            ? $refundReceiptFile
                            : ($payment['receipt_image'] ?? '');
                        $refundNote = payment_note_display($paymentStatus, $payment['remarks'] ?? '');
                        $refundNoteText = $refundNote['text'] ?? '';
                        $paymentMethodText = payment_method_from_payment($payment);
                        $hasOfficialReceipt = in_array($paymentStatus, ['paid', 'approved', 'refund_requested', 'refund_rejected', 'refunded'], true);
                        $referenceValue = $hasOfficialReceipt && trim((string)($payment['receipt_number'] ?? '')) !== ''
                            ? $payment['receipt_number']
                            : ($payment['payment_code'] ?? '-');
                    ?>
                    <tr data-status="<?php echo htmlspecialchars($paymentGroup); ?>" data-id="<?php echo $paymentId; ?>">
                        <td><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($referenceValue); ?></td>
                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($payment['appointment_code']); ?></td>
                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['transaction_id'] ?? '-'); ?></td>
                        <td><?php echo badge($badgeStatus); ?></td>
                        <td>
                            <?php if (!empty($receiptToView)): ?>
                                <button class="btn-view" onclick='viewReceipt(<?php echo json_encode($receiptToView); ?>, <?php echo json_encode($refundNoteText); ?>, <?php echo json_encode($paymentMethodText); ?>)'>
                                    View
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($paymentId > 0): ?>
                                <button class="btn-view-details" onclick="viewPaymentDetails(<?php echo $paymentId; ?>)">
                                    View
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <?php if ($paymentStatus === 'verifying' && $paymentId > 0): ?>
                                <button class="btn-approve" onclick="approvePayment(<?php echo $paymentId; ?>)">
                                    Approve
                                </button>
                                <button class="btn-reject" onclick="showRejectModal(<?php echo $paymentId; ?>)">
                                    Reject
                                </button>
                            <?php elseif ($paymentStatus === 'refund_requested' && $paymentId > 0): ?>
                                <button class="btn-approve" onclick='showRefundModal(<?php echo $paymentId; ?>, <?php echo json_encode($refundNoteText); ?>)'>
                                    Approve
                                </button>
                                <button class="btn-reject" onclick='showRejectRefundModal(<?php echo $paymentId; ?>, <?php echo json_encode($refundNoteText); ?>)'>
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

<!-- Payment Details Modal -->
<div id="paymentDetailsModal" class="modal-overlay" style="display: none;">
    <div class="modal payment-details-view-modal">
        <div class="modal-header">
            <span class="modal-title">Payment Details</span>
            <button class="modal-close" onclick="closePaymentDetailsModal()">✕</button>
        </div>
        <div class="modal-body" id="paymentDetailsContent">
            <p class="text-muted">Loading...</p>
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
            <div class="payment-method-proof" id="receiptPaymentMethodBox"></div>
            <div class="receipt-preview-card" id="receiptPreviewContent"></div>
            <div class="refund-reason-box receipt-refund-reason" id="receiptRefundReasonBox"></div>
        </div>
    </div>
</div>

<!-- Approve Payment Modal -->
<div id="approveModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">Approve Payment</span>
            <button class="modal-close" onclick="closeApproveModal()">X</button>
        </div>
        <div class="modal-body">
            <p>Approve this payment? The user will receive an email notification.</p>
            <input type="hidden" id="approvePaymentId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeApproveModal()">Cancel</button>
            <button class="btn btn-success" onclick="confirmApprovePayment()">Approve Payment</button>
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

<!-- Refund Modal -->
<div id="refundModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">Approve Refund</span>
            <button class="modal-close" onclick="closeRefundModal()">X</button>
        </div>
        <div class="modal-body">
            <p>Approve this refund request? The user will receive an email notification.</p>
            <div class="form-group refund-upload-group">
                <label for="refundReceipt">Upload Receipt/Screenshot</label>
                <input type="file" id="refundReceipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                <p class="text-muted refund-upload-help">Format: JPG, PNG, PDF (Max 2MB)</p>
            </div>
            <input type="hidden" id="refundPaymentId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeRefundModal()">Cancel</button>
            <button class="btn btn-success" onclick="confirmRefund()">Approve Refund</button>
        </div>
    </div>
</div>

<!-- Reject Refund Modal -->
<div id="rejectRefundModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">Reject Refund Request</span>
            <button class="modal-close" onclick="closeRejectRefundModal()">X</button>
        </div>
        <div class="modal-body">
            <div class="refund-reason-box" id="rejectRefundRequestReasonBox"></div>
            <p>Please provide a reason for rejecting this refund request:</p>
            <textarea id="rejectRefundReason" class="form-control" rows="3" placeholder="e.g., Refund conditions not met"></textarea>
            <input type="hidden" id="rejectRefundPaymentId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeRejectRefundModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmRejectRefund()">Reject Refund</button>
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

.payment-filter-select {
    width: 220px;
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
    overflow: hidden;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.admin-payment-container .table-wrap {
    overflow-x: visible;
}

.payments-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    table-layout: auto;
}

.payments-table th,
.payments-table td {
    padding: 12px 10px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    box-sizing: border-box;
    overflow-wrap: break-word;
    word-break: normal;
}

.payments-table th {
    background: var(--surface2);
    font-weight: 600;
    color: var(--text);
    line-height: 1.25;
    white-space: nowrap;
}

.payments-table td {
    vertical-align: middle;
}

.payments-table th:nth-child(1),
.payments-table td:nth-child(1) {
    width: 13%;
}

.payments-table th:nth-child(3),
.payments-table td:nth-child(3),
.payments-table th:nth-child(6),
.payments-table td:nth-child(6) {
    width: 13%;
}

.payments-table th:nth-child(5),
.payments-table td:nth-child(5),
.payments-table th:nth-child(8),
.payments-table td:nth-child(8),
.payments-table th:nth-child(9),
.payments-table td:nth-child(9) {
    width: 1%;
    white-space: nowrap;
}

.payments-table th:nth-child(10),
.payments-table td:nth-child(10) {
    width: 90px;
    white-space: nowrap;
}

.payments-table td:nth-child(2),
.payments-table td:nth-child(4),
.payments-table td:nth-child(6) {
    overflow-wrap: anywhere;
}

.payments-table tr:hover {
    background: var(--surface2);
}

/* Buttons */
.btn-view,
.btn-view-details {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
    transition: background 0.15s, color 0.15s;
}

.btn-view {
    background: var(--primary);
    color: white;
}

.btn-view:hover {
    background: var(--primary-dark);
}

.btn-view-details {
    background: var(--teal);
    color: white;
}

.btn-view-details:hover {
    background: rgba(42,127,127,0.85);
}

.btn-approve {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
    background: var(--success);
    color: white;
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

.btn-refund {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
    background: var(--warning);
    color: white;
}

.btn-refund:hover {
    background: #a16207;
}

.btn-refund-confirm {
    background: var(--warning);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    cursor: pointer;
}

.btn-refund-confirm:hover {
    background: #a16207;
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
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    white-space: normal;
}

.actions-cell .btn-approve,
.actions-cell .btn-reject {
    width: 100%;
    min-width: 74px;
    min-height: 28px;
    padding-left: 6px;
    padding-right: 6px;
    text-align: center;
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

.payment-details-view-modal {
    max-width: 500px;
}

.payment-details-modal .detail-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.payment-details-modal .detail-row:last-child {
    border-bottom: none;
}

.payment-details-modal .detail-row strong {
    color: var(--text-muted);
    font-weight: 600;
}

.payment-details-modal .detail-row {
    color: var(--text);
    overflow-wrap: anywhere;
}

.payment-details-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--border);
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

#receiptViewModal .modal-body {
    padding-top: 10px;
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

.refund-upload-group {
    margin-top: 16px;
}

.refund-upload-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}

.refund-upload-group input[type="file"] {
    padding: 12px;
}

.refund-upload-help {
    margin-top: 8px;
}

.refund-reason-box {
    margin: 12px 0 16px;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface2);
    color: var(--text);
    line-height: 1.45;
    white-space: pre-wrap;
}

.receipt-refund-reason {
    margin-bottom: 0;
    text-align: left;
}

.payment-method-proof {
    display: none;
    width: fit-content;
    max-width: 520px;
    margin: 0 0 12px;
    padding: 10px 14px;
    border-radius: 8px;
    background: rgba(124, 51, 73, 0.1);
    color: var(--primary);
    font-size: 16px;
    font-weight: 700;
    text-align: left;
}

.receipt-preview-card {
    min-height: 180px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.receipt-preview-card img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: 8px;
    object-fit: contain;
}

.receipt-preview-card .btn {
    width: auto;
}

.file-open-fallback {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    width: 100%;
    min-height: 160px;
    text-align: center;
}

.file-open-fallback p {
    margin: 0;
    color: var(--text-muted);
}

.refund-reason-box:empty {
    display: none;
}

@media (max-width: 768px) {
    .stats-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .payments-table th,
    .payments-table td {
        padding: 7px 5px;
        font-size: 11px;
    }
    
    .actions-cell {
        gap: 5px;
    }
}
</style>

<script>
function showPaymentNotification(message, type = 'success', reload = false) {
    const container = document.querySelector('.admin-payment-container') || document.body;
    document.querySelectorAll('.payment-flash-message').forEach(messageBox => messageBox.remove());

    const notice = document.createElement('div');
    notice.className = `toast flash-message show ${type} payment-flash-message`;
    notice.textContent = message;
    container.prepend(notice);

    if (container !== document.body) {
        notice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    if (reload) {
        setTimeout(() => window.location.reload(), 5000);
        return;
    }

    setTimeout(() => {
        notice.classList.add('hiding');
        setTimeout(() => notice.remove(), 350);
    }, 5000);
}

function filterPayments(status) {
    const searchValue = document.getElementById('searchPayment')?.value.toLowerCase() || '';
    const rows = document.querySelectorAll('#paymentsTableBody tr');
    rows.forEach(row => {
        const matchesSearch = !searchValue || row.innerText.toLowerCase().includes(searchValue);
        let matchesStatus = false;

        if (status === 'queue') {
            matchesStatus = ['verifying', 'refund_requested'].includes(row.dataset.status);
        } else if (status === 'all' || row.dataset.status === status) {
            matchesStatus = true;
        }

        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}

document.getElementById('paymentStatusFilter')?.addEventListener('change', function() {
    filterPayments(this.value);
});

document.getElementById('searchPayment')?.addEventListener('keyup', function() {
    filterPayments(document.getElementById('paymentStatusFilter')?.value || 'queue');
});

function initPaymentFilter() {
    const filter = document.getElementById('paymentStatusFilter');
    if (!filter) return;

    const hasActionRequired = Array.from(document.querySelectorAll('#paymentsTableBody tr'))
        .some(row => ['verifying', 'refund_requested'].includes(row.dataset.status));

    if (!hasActionRequired && filter.value === 'queue') {
        filter.value = 'all';
    }
    filterPayments(filter.value || 'queue');
}

initPaymentFilter();

async function viewPaymentDetails(paymentId) {
    const content = document.getElementById('paymentDetailsContent');
    const modal = document.getElementById('paymentDetailsModal');

    if (!content || !modal) return;

    content.innerHTML = '<p class="text-muted">Loading...</p>';
    modal.style.display = 'flex';

    try {
        const response = await fetch('../action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get_payment_details&payment_id=${paymentId}`
        });
        const data = await response.json();
        content.innerHTML = data.success ? data.html : '<p class="text-muted">Error loading details.</p>';
    } catch (error) {
        content.innerHTML = '<p class="text-muted">Error loading details.</p>';
    }
}

function closePaymentDetailsModal() {
    document.getElementById('paymentDetailsModal').style.display = 'none';
}

function viewReceipt(receiptImage, reason = '', paymentMethod = '') {
    if (!receiptImage) {
        alert('No receipt image available');
        return;
    }
    const modal = document.getElementById('receiptViewModal');
    const content = document.getElementById('receiptPreviewContent');
    const reasonBox = document.getElementById('receiptRefundReasonBox');
    const paymentMethodBox = document.getElementById('receiptPaymentMethodBox');
    const receiptUrl = '../uploads/receipts/' + encodeURIComponent(receiptImage);
    const extension = receiptImage.split('.').pop().toLowerCase();
    if (content) {
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            content.innerHTML = `<img src="${receiptUrl}" alt="Payment receipt">`;
        } else {
            content.innerHTML = `<div class="file-open-fallback"><p>This payment proof file cannot be previewed here.</p><a class="btn btn-outline" target="_blank" rel="noopener" href="${receiptUrl}">Open File</a></div>`;
        }
    }
    if (paymentMethodBox) {
        paymentMethodBox.textContent = paymentMethod ? `Paid via: ${paymentMethod}` : '';
        paymentMethodBox.style.display = paymentMethod ? 'block' : 'none';
    }
    if (reasonBox) reasonBox.textContent = formatRefundRequestReason(reason);
    modal.style.display = 'flex';
}

function closeReceiptViewModal() {
    document.getElementById('receiptViewModal').style.display = 'none';
}

let approvePaymentId = null;

function approvePayment(paymentId) {
    approvePaymentId = paymentId;
    document.getElementById('approvePaymentId').value = paymentId;
    document.getElementById('approveModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
    approvePaymentId = null;
}

function confirmApprovePayment() {
    fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=approve_payment&payment_id=${approvePaymentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeApproveModal();
            showPaymentNotification('Payment approved successfully! Email sent to patient.', 'success', true);
        } else {
            showPaymentNotification('Error: ' + data.message, 'error');
        }
    });
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
            closeRejectModal();
            showPaymentNotification('Payment rejected. Email sent to patient.', 'success', true);
        } else {
            showPaymentNotification('Error: ' + data.message, 'error');
        }
    });
}

let refundPaymentId = null;

function formatRefundRequestReason(reason) {
    return reason ? `Refund Details:\n${reason}` : '';
}

function showRefundModal(paymentId, reason = '') {
    refundPaymentId = paymentId;
    document.getElementById('refundReceipt').value = '';
    document.getElementById('refundModal').style.display = 'flex';
}

function closeRefundModal() {
    document.getElementById('refundModal').style.display = 'none';
    refundPaymentId = null;
}

function confirmRefund() {
    const reason = '';
    const receiptInput = document.getElementById('refundReceipt');
    const receiptFile = receiptInput.files[0];

    if (!receiptFile) {
        alert('Please upload refund receipt or screenshot');
        return;
    }

    if (receiptFile.size > 2 * 1024 * 1024) {
        alert('File too large. Max 2MB');
        return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!allowedTypes.includes(receiptFile.type)) {
        alert('Invalid file type. JPG, PNG, PDF only');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'refund_payment');
    formData.append('payment_id', refundPaymentId);
    formData.append('reason', reason);
    formData.append('refund_receipt', receiptFile);
    
    fetch('../action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRefundModal();
            showPaymentNotification('Refund approved. Email sent to patient.', 'success', true);
        } else {
            showPaymentNotification('Error: ' + data.message, 'error');
        }
    });
}

let rejectRefundPaymentId = null;

function showRejectRefundModal(paymentId, reason = '') {
    rejectRefundPaymentId = paymentId;
    document.getElementById('rejectRefundReason').value = '';
    document.getElementById('rejectRefundRequestReasonBox').textContent = formatRefundRequestReason(reason);
    document.getElementById('rejectRefundModal').style.display = 'flex';
}

function closeRejectRefundModal() {
    document.getElementById('rejectRefundModal').style.display = 'none';
    rejectRefundPaymentId = null;
}

function confirmRejectRefund() {
    const reason = document.getElementById('rejectRefundReason').value;
    if (!reason.trim()) {
        alert('Please provide a reason for rejecting the refund');
        return;
    }

    fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=reject_refund&payment_id=${rejectRefundPaymentId}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRejectRefundModal();
            showPaymentNotification('Refund request rejected. Email sent to patient.', 'success', true);
        } else {
            showPaymentNotification('Error: ' + data.message, 'error');
        }
    });
}

</script>

<?php
app_end();
?>
