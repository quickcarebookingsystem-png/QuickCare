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
                <colgroup>
                    <col class="payment-col-date">
                    <col class="payment-col-reference">
                    <col class="payment-col-patient">
                    <col class="payment-col-appointment">
                    <col class="payment-col-amount">
                    <col class="payment-col-status">
                    <col class="payment-col-proof">
                    <col class="payment-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Patient Name</th>
                        <th>Appointment</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Proof</th>
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
                        <td>
                            <span class="payment-date-day"><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></span>
                            <span class="payment-date-time"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($referenceValue); ?></td>
                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($payment['appointment_code']); ?></td>
                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
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
                        <td class="actions-cell">
                            <?php if ($paymentId > 0): ?>
                                <button class="btn-view-details" onclick="viewPaymentDetails(<?php echo $paymentId; ?>)">
                                    Details
                                </button>
                            <?php endif; ?>
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
                            <?php elseif ($paymentId <= 0): ?>
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
