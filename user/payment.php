<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('user', 'payment');

$user_id = $_SESSION['id'];
$pending_payments = get_user_pending_payments($user_id);
$selected_appointment = $_GET['appointment'] ?? '';
?>

<div class="checkout-container">
    <div class="qr-payment-section">
        <div class="section-title">
            <span class="section-icon">📱</span> QR Code Payment
        </div>

        <!-- Step 1: Select Appointment -->
        <div class="step-box">
            <div class="step-number">1</div>
            <div class="step-content">
                <h3>Select Appointment</h3>
                <?php if (empty($pending_payments)): ?>
                    <div class="alert-info">
                        <p>No pending payments. You don't have any confirmed appointments that need payment.</p>
                        <a href="<?php echo e(page_url('book', 'user')); ?>" class="btn btn-primary" style="margin-top: 10px;">Book New Appointment</a>
                    </div>
                <?php else: ?>
                    <select id="appointmentSelect" class="form-control" onchange="updateAmount()">
                        <option value="">-- Select appointment --</option>
                        <?php foreach ($pending_payments as $payment): ?>
                        <option value="<?php echo htmlspecialchars($payment['appointment_code']); ?>" 
                                data-amount="<?php echo $payment['amount']; ?>"
                                data-name="<?php echo htmlspecialchars($payment['service_name']); ?>"
                                <?php echo $payment['appointment_code'] === $selected_appointment ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($payment['appointment_code']); ?> -
                            <?php echo htmlspecialchars($payment['service_name']); ?> -
                            RM <?php echo number_format($payment['amount'], 2); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: Payment Details -->
        <div class="step-box payment-qr-active" id="qrStep" style="display: none;">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>Payment Details</h3>
                <div class="qr-container">
                    <div class="qr-stack">
                        <div class="qr-code">
                            <div class="qr-type-tabs" role="tablist" aria-label="QR type">
                                <button type="button" class="qr-type-tab active" data-qr-type="tng" role="tab" aria-selected="true">TNG</button>
                                <button type="button" class="qr-type-tab" data-qr-type="bank" role="tab" aria-selected="false">Bank</button>
                            </div>
                            <img src="<?php echo e(app_url('uploads/receipts/tng_qr.JPG')); ?>"
                                 alt="Touch n Go payment QR Code"
                                 id="paymentQrImage"
                                 data-bank-src="<?php echo e(app_url('uploads/receipts/bank_qr.JPG')); ?>"
                                 data-tng-src="<?php echo e(app_url('uploads/receipts/tng_qr.JPG')); ?>">
                            <div class="qr-caption" id="paymentQrCaption">Touch 'n Go eWallet</div>
                        </div>
                    </div>
                        <div class="payment-details">
                            <div class="amount-display">
                                Amount: <strong id="payAmount">RM 0.00</strong>
                            </div>
                            <div class="bank-details">
                                <div class="bank-details-title">Payment Details</div>
                            <div class="bank-detail-row">
                                <span class="bank-detail-label">DuitNow ID</span>
                                <span class="bank-detail-value">150598893567</span>
                            </div>
                            <div class="bank-detail-row">
                                <span class="bank-detail-label">Touch 'n Go</span>
                                <span class="bank-detail-value">011-10807180</span>
                            </div>
                            <div class="bank-detail-row">
                                <span class="bank-detail-label">Bank Transfer</span>
                                <span class="bank-detail-value">RHB Bank<br>1-51414-0007092-2</span>
                            </div>
                            <div class="bank-detail-row">
                                <span class="bank-detail-label">Account Name</span>
                                <span class="bank-detail-value">NG SHUZHENG<br><small>Admin QuickCare Clinic</small></span>
                            </div>
                        </div>
                        <div class="warning-note">
                            ⚠️ After payment, please upload the receipt below for verification
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Upload Receipt -->
        <div class="step-box" id="uploadStep" style="display: none;">
            <div class="step-number">3</div>
            <div class="step-content">
                <h3>Upload Payment Receipt</h3>
                <form id="paymentForm" enctype="multipart/form-data">
                    <input type="hidden" id="appointmentCode" name="appointment_code">
                    <input type="hidden" id="amount" name="amount">
                    <input type="hidden" id="paymentMethodLabel" name="payment_method_label">
                    
                    <div class="form-group">
                        <label>Upload Receipt/Screenshot</label>
                        <input type="file" class="form-control" id="receipt" name="receipt" 
                               accept="image/*,.pdf" required>
                        <small class="text-muted">Format: JPG, PNG, PDF (Max 2MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks (Optional)</label>
                        <textarea class="form-control" id="remarks" name="remarks" 
                                  rows="4" placeholder="Any notes for admin..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit Payment Verification</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

#remarks {
    min-height: 120px;
    resize: none;
}

.qr-payment-section {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text);
}

.section-icon {
    font-size: 24px;
}

/* Step Box - Horizontal layout (nombor di kiri, content di kanan) */
.step-box {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: var(--surface2);
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content h3 {
    margin-bottom: 15px;
    color: var(--text);
    font-size: 18px;
    margin-top: 0;
}

.qr-container {
    display: flex;
    gap: 30px;
    align-items: center;
    flex-wrap: nowrap;
}

.qr-stack {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 232px;
    flex-shrink: 0;
}

.qr-code {
    background: white;
    padding: 14px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid var(--border);
}

.qr-type-tabs {
    position: relative;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    padding: 4px;
    margin-bottom: 12px;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}

.qr-type-tabs::before {
    content: "";
    position: absolute;
    top: 4px;
    bottom: 4px;
    left: 4px;
    width: calc((100% - 8px) / 2);
    background: var(--primary);
    border-radius: 6px;
    transition: transform 0.24s ease;
}

.qr-type-tabs.bank-active::before {
    transform: translateX(100%);
}

.qr-type-tab {
    position: relative;
    z-index: 1;
    min-height: 30px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--text-muted);
    font: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: color 0.2s ease;
}

.qr-type-tab.active {
    color: white;
}

.qr-title {
    color: var(--text);
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 10px;
}

.qr-code img {
    width: 200px;
    height: 200px;
    display: block;
}

.qr-web-link {
    width: 200px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px dashed var(--border);
    border-radius: 8px;
    color: var(--primary);
    font-weight: 700;
    text-decoration: none;
    background: var(--surface2);
}

.qr-web-link:hover {
    border-color: var(--primary);
    background: rgba(124,51,73,0.08);
}

.qr-caption {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    margin-top: 10px;
}

.payment-details {
    flex: 1;
    min-width: 260px;
}

.amount-display {
    font-size: 20px;
    margin-bottom: 15px;
    padding: 10px;
    background: rgba(124,51,73,0.1);
    border-radius: 8px;
    text-align: center;
    color: var(--primary);
}

.bank-details {
    background: var(--surface);
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid var(--border);
}

.bank-details-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
}

.bank-detail-row {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 12px;
    align-items: start;
    padding: 10px 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.25);
}

.bank-detail-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.bank-detail-label {
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 600;
}

.bank-detail-value {
    color: var(--text);
    font-size: 14px;
    font-weight: 600;
    line-height: 1.45;
    text-align: right;
    word-break: break-word;
}

.bank-detail-value small {
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 500;
}

.warning-note {
    background: #fef3c7;
    padding: 10px;
    border-radius: 8px;
    font-size: 13px;
    color: #92400e;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text);
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
    box-shadow: 0 0 0 3px rgba(124,51,73,0.1);
}

.btn-primary {
    background: var(--primary);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-weight: 600;
    width: 100%;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.alert-info {
    background: #dbeafe;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    color: #1e40af;
}

.text-muted {
    color: var(--text-muted);
    font-size: 12px;
}

/* Responsive: pada mobile, tukar kepada column */
@media (max-width: 768px) {
    .step-box {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .qr-container {
        flex-direction: column;
        align-items: stretch;
    }

    .qr-stack {
        width: 100%;
    }

    .qr-code img {
        margin: 0 auto;
    }

    .bank-detail-row {
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .bank-detail-value {
        text-align: left;
    }
}
</style>

<script>
let currentAppointmentCode = '';
let selectedQrType = 'tng';

function updatePaymentMethodLabel() {
    const labelInput = document.getElementById('paymentMethodLabel');
    if (!labelInput) return;

    labelInput.value = selectedQrType === 'bank' ? 'QR Pay - Bank' : 'QR Pay - TNG';
}

function resetPaymentMethod() {
    selectedQrType = 'tng';
    setQrType('tng');
    updatePaymentMethodLabel();
}

function setQrType(type) {
    const qrTabs = document.querySelector('.qr-type-tabs');
    const qrImage = document.getElementById('paymentQrImage');
    const qrCaption = document.getElementById('paymentQrCaption');
    const isBank = type === 'bank';
    selectedQrType = isBank ? 'bank' : 'tng';

    qrTabs?.classList.toggle('bank-active', isBank);
    if (qrImage) {
        qrImage.src = isBank ? qrImage.dataset.bankSrc : qrImage.dataset.tngSrc;
        qrImage.alt = isBank ? 'Bank payment QR Code' : 'Touch n Go payment QR Code';
    }
    if (qrCaption) {
        qrCaption.textContent = isBank ? 'RHB Bank' : "Touch 'n Go eWallet";
    }

    document.querySelectorAll('.qr-type-tab').forEach(tab => {
        const active = tab.dataset.qrType === type;
        tab.classList.toggle('active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    updatePaymentMethodLabel();
}

function updateAmount() {
    const select = document.getElementById('appointmentSelect');
    const selectedOption = select.options[select.selectedIndex];
    const amount = selectedOption.dataset.amount;
    const appointmentId = selectedOption.value;
    
    if (appointmentId) {
        if (appointmentId !== currentAppointmentCode) {
            resetPaymentMethod();
            currentAppointmentCode = appointmentId;
        }
        document.getElementById('payAmount').innerHTML = 'RM ' + parseFloat(amount).toFixed(2);
        document.getElementById('appointmentCode').value = appointmentId;
        document.getElementById('amount').value = amount;
        document.getElementById('qrStep').style.display = 'flex';
        document.getElementById('uploadStep').style.display = 'flex';
    } else {
        resetPaymentMethod();
        currentAppointmentCode = '';
        document.getElementById('qrStep').style.display = 'none';
        document.getElementById('uploadStep').style.display = 'none';
    }
}

function showPaymentNotice(message, type = 'success', redirectUrl = '') {
    const form = document.getElementById('paymentForm');
    const anchor = form?.closest('.card') || form || document.body;
    document.querySelectorAll('.payment-flash-message').forEach(messageBox => messageBox.remove());

    const notice = document.createElement('div');
    notice.className = `toast flash-message show ${type} payment-flash-message`;
    notice.textContent = message;
    anchor.parentNode.insertBefore(notice, anchor);
    notice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

    if (redirectUrl) {
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 5000);
        return;
    }

    setTimeout(() => {
        notice.classList.add('hiding');
        setTimeout(() => notice.remove(), 350);
    }, 5000);
}

// Handle form submission
document.getElementById('paymentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const receiptInput = document.getElementById('receipt');
    const receiptFile = receiptInput.files[0];
    const maxReceiptSize = 2 * 1024 * 1024;

    if (receiptFile && receiptFile.size > maxReceiptSize) {
        showPaymentNotice('Receipt file is too large. Please upload a file 2MB or smaller.', 'error');
        receiptInput.focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'submit_payment');
    formData.append('appointment_code', document.getElementById('appointmentCode').value);
    formData.append('amount', document.getElementById('amount').value);
    const methodLabel = document.getElementById('paymentMethodLabel').value;
    formData.append('payment_method', methodLabel);
    formData.append('remarks', document.getElementById('remarks').value.trim());
    formData.append('receipt', receiptFile);
    
    const response = await fetch('../action.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        showPaymentNotice('Payment submitted! Waiting for admin approval.', 'success', '<?php echo e(page_url('payment_history', 'user')); ?>');
    } else {
        showPaymentNotice('Error: ' + result.message, 'error');
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.qr-type-tab').forEach(tab => {
        tab.addEventListener('click', () => setQrType(tab.dataset.qrType || 'bank'));
    });
    setQrType('tng');

    const select = document.getElementById('appointmentSelect');
    if (select && select.value) {
        updateAmount();
    }
});
</script>

<?php
app_end();
?>
