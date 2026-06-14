<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('staff', 'payment');

$payments = get_all_payments();
?>

<div class="staff-payment-container">
    <div class="page-header">
        <p>Staff View Only</p>
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
                        <th>Status</th>
                        <th>Proof</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="paymentsTableBody">
                    <?php foreach ($payments as $payment): ?>
                    <?php
                        $paymentStatus = strtolower((string) $payment['payment_status']);
                        $paymentId = (int)($payment['payment_id'] ?? 0);
                        $badgeStatus = $paymentStatus === 'approved' ? 'paid' : str_replace('_', '-', $paymentStatus);
                        $paymentGroup = $badgeStatus;
                        if ($paymentStatus === 'pending') {
                            $paymentGroup = 'pending';
                        } elseif ($paymentStatus === 'verifying') {
                            $paymentGroup = 'verifying';
                        } elseif (in_array($paymentStatus, ['paid', 'approved'], true)) {
                            $paymentGroup = 'approved';
                        } elseif ($paymentStatus === 'refund_requested') {
                            $paymentGroup = 'refund_requested';
                        } elseif ($paymentStatus === 'refunded') {
                            $paymentGroup = 'refunded';
                        } elseif ($paymentStatus === 'refund_rejected') {
                            $paymentGroup = 'refund_rejected';
                        }
                        $refundReceiptFile = payment_refund_receipt_file($payment['remarks'] ?? '');
                        $receiptToView = ($paymentStatus === 'refunded' && $refundReceiptFile !== '')
                            ? $refundReceiptFile
                            : ($payment['receipt_image'] ?? '');
                        $hasOfficialReceipt = in_array($paymentStatus, ['paid', 'approved', 'refund_requested', 'refund_rejected', 'refunded'], true);
                        $referenceValue = $hasOfficialReceipt && trim((string)($payment['receipt_number'] ?? '')) !== ''
                            ? $payment['receipt_number']
                            : ($payment['payment_code'] ?? '-');
                    ?>
                    <tr data-status="<?php echo htmlspecialchars($paymentGroup); ?>">
                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($referenceValue); ?></td>
                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($payment['appointment_code']); ?></td>
                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                        <td>
                            <?php echo badge($badgeStatus); ?>
                        </td>
                        <td>
                            <?php if (!empty($receiptToView)): ?>
                                <button class="btn-view" onclick='viewReceipt(<?php echo json_encode($receiptToView); ?>)'>
                                    📷 View
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($paymentId > 0): ?>
                                <button class="btn-view-details" onclick="viewDetails(<?php echo $paymentId; ?>)">
                                    👁️ View
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
            <span class="modal-title">📷 Payment Proof</span>
            <button class="modal-close" onclick="closeReceiptViewModal()">✕</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <div class="receipt-preview-card" id="receiptPreviewContent"></div>
        </div>
    </div>
</div>

<!-- View Details Modal (Staff only view, cannot approve) -->
<div id="detailsModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <span class="modal-title">📋 Payment Details</span>
            <button class="modal-close" onclick="closeDetailsModal()">✕</button>
        </div>
        <div class="modal-body" id="detailsContent">
            <!-- Details loaded here -->
        </div>
        <div class="modal-footer">
            <div class="alert-info">
                ⚠️ Staff cannot approve/reject payments. Only Admin can approve/reject.
            </div>
        </div>
    </div>
</div>


<script>
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

function viewReceipt(receiptImage) {
    if (!receiptImage) {
        alert('No receipt image available');
        return;
    }
    const modal = document.getElementById('receiptViewModal');
    const content = document.getElementById('receiptPreviewContent');
    const receiptUrl = '../uploads/receipts/' + encodeURIComponent(receiptImage);
    const extension = receiptImage.split('.').pop().toLowerCase();
    if (content) {
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            content.innerHTML = `<img src="${receiptUrl}" alt="Payment receipt">`;
        } else {
            content.innerHTML = `<div class="file-open-fallback"><p>This payment proof file cannot be previewed here.</p><a class="btn btn-outline" target="_blank" rel="noopener" href="${receiptUrl}">Open File</a></div>`;
        }
    }
    modal.style.display = 'flex';
}

function closeReceiptViewModal() {
    document.getElementById('receiptViewModal').style.display = 'none';
}

async function viewDetails(paymentId) {
    const response = await fetch('../action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_payment_details&payment_id=${paymentId}`
    });
    
    const data = await response.json();
    
    if (data.success) {
        document.getElementById('detailsContent').innerHTML = data.html;
        document.getElementById('detailsModal').style.display = 'flex';
    } else {
        alert('Error loading details');
    }
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

</script>

<?php
app_end();
?>
