<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('staff', 'payment_management');

$payments = get_all_payments();
?>

<div class="staff-payment-container">
    <div class="page-header">
        <p>Staff View Only</p>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="tab-btn active" data-filter="all">All</button>
        <button class="tab-btn" data-filter="pending">Pending ⏳</button>
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
                        <th>Patient Name</th>
                        <th>Appointment</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Receipt</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="paymentsTableBody">
                    <?php foreach ($payments as $payment): ?>
                    <tr data-status="<?php echo $payment['status']; ?>">
                        <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                        <td><?php echo htmlspecialchars($payment['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($payment['appointment_details']); ?></td>
                        <td>RM <?php echo number_format($payment['amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-view" onclick="viewReceipt('<?php echo $payment['receipt_image']; ?>')">
                                📷 View
                            </button>
                        </td>
                        <td>
                            <button class="btn-view-details" onclick="viewDetails(<?php echo $payment['id']; ?>)">
                                👁️ View
                            </button>
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
            <span class="modal-title">📷 Payment Receipt</span>
            <button class="modal-close" onclick="closeReceiptViewModal()">✕</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <img id="receiptImage" src="" style="max-width: 100%; border-radius: 8px;">
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

<style>
.staff-payment-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 25px;
}

.page-header h1 {
    font-size: 24px;
    color: var(--text);
}

.header-icon {
    font-size: 28px;
    margin-right: 10px;
}

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

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
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

.btn-view, .btn-view-details {
    padding: 5px 12px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 12px;
    border: none;
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
    background: var(--teal-light);
}

.alert-info {
    background: #dbeafe;
    padding: 10px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    color: #1e40af;
    text-align: center;
    width: 100%;
}

.modal {
    max-width: 500px;
    background: var(--surface);
    border-radius: var(--radius);
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
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: var(--text-muted);
}

.detail-value {
    color: var(--text);
}

@media (max-width: 768px) {
    .payments-table th,
    .payments-table td {
        padding: 8px 10px;
        font-size: 12px;
    }
    
    .filter-tabs {
        justify-content: center;
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