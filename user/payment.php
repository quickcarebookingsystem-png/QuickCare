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
            <span class="section-icon">💳</span> Payment
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

        <!-- Step 2: Payment Method -->
        <div class="step-box" id="methodStep" style="display: none;">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>Choose Payment Method</h3>
                <div class="payment-method-tabs" role="tablist" aria-label="Payment method">
                    <button type="button" class="payment-method-tab active" data-payment-method="fpx" role="tab" aria-selected="true">FPX</button>
                    <button type="button" class="payment-method-tab" data-payment-method="qr" role="tab" aria-selected="false">QR Pay</button>
                </div>
            </div>
        </div>

        <!-- Step 3: QR Payment Details -->
        <div class="step-box payment-fpx-active" id="qrStep" style="display: none;">
            <div class="step-number">3</div>
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
                        <div class="warning-note" id="paymentInstructionNote">
                            ⚠️ After payment, please upload the receipt below for verification
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Confirm Payment -->
        <div class="step-box" id="uploadStep" style="display: none;">
            <div class="step-number">4</div>
            <div class="step-content">
                <h3 id="paymentSubmitTitle">Continue to Payment</h3>
                <form id="paymentForm" enctype="multipart/form-data">
                    <input type="hidden" id="appointmentCode" name="appointment_code">
                    <input type="hidden" id="amount" name="amount">
                    <input type="hidden" id="paymentMethodLabel" name="payment_method_label">
                    <div class="profile-inline-notification error" id="receiptSizeError" hidden></div>

                    <div class="payment-summary-card">
                        <div class="payment-summary-top">
                            <div>
                                <div class="payment-summary-label">Appointment</div>
                                <div class="payment-summary-value" id="summaryAppointment">-</div>
                            </div>
                            <span class="payment-summary-method" id="summaryMethod">FPX</span>
                        </div>
                        <div class="payment-summary-row">
                            <span>Service</span>
                            <strong id="summaryService">-</strong>
                        </div>
                        <div class="payment-summary-total">
                            <span>Total Amount</span>
                            <strong id="summaryAmount">RM 0.00</strong>
                        </div>
                        <div class="payment-summary-note" id="summaryPaymentNote">
                            You will be redirected to ToyyibPay to complete your FPX payment.
                        </div>
                    </div>
                    
                    <div class="form-group" id="receiptUploadGroup">
                        <label>Upload Receipt/Screenshot</label>
                        <input type="file" class="form-control" id="receipt" name="receipt" 
                               accept="image/*,.pdf">
                        <small class="text-muted">Format: JPG, PNG, PDF (Max 2MB)</small>
                    </div>
                    
                    <div class="form-group" id="remarksGroup">
                        <label>Remarks (Optional)</label>
                        <textarea class="form-control" id="remarks" name="remarks" 
                                  rows="4" placeholder="Any notes for admin..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" id="paymentSubmitButton">Pay with ToyyibPay</button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
let currentAppointmentCode = '';
let selectedPaymentMethod = 'fpx';
let selectedQrType = 'tng';

function updatePaymentMethodLabel() {
    const labelInput = document.getElementById('paymentMethodLabel');
    if (!labelInput) return;

    if (selectedPaymentMethod === 'qr') {
        labelInput.value = selectedQrType === 'bank' ? 'QR Pay - Bank' : 'QR Pay - TNG';
    } else {
        labelInput.value = 'FPX / Bank Transfer';
    }
}

function updatePaymentSummary() {
    const select = document.getElementById('appointmentSelect');
    const selectedOption = select?.options[select.selectedIndex];
    const appointmentId = selectedOption?.value || '';
    const serviceName = selectedOption?.dataset.name || '-';
    const amount = selectedOption?.dataset.amount || '0';
    const amountText = appointmentId ? 'RM ' + parseFloat(amount).toFixed(2) : 'RM 0.00';
    const methodText = selectedPaymentMethod === 'qr'
        ? (selectedQrType === 'bank' ? 'QR Pay - Bank' : 'QR Pay - TNG')
        : 'FPX';

    const summaryAppointment = document.getElementById('summaryAppointment');
    const summaryService = document.getElementById('summaryService');
    const summaryAmount = document.getElementById('summaryAmount');
    const summaryMethod = document.getElementById('summaryMethod');
    const summaryPaymentNote = document.getElementById('summaryPaymentNote');

    if (summaryAppointment) summaryAppointment.textContent = appointmentId || '-';
    if (summaryService) summaryService.textContent = appointmentId ? serviceName : '-';
    if (summaryAmount) summaryAmount.textContent = amountText;
    if (summaryMethod) summaryMethod.textContent = methodText;
    if (summaryPaymentNote) {
        summaryPaymentNote.textContent = selectedPaymentMethod === 'qr'
            ? 'Scan the selected QR, then upload your receipt for admin verification.'
            : 'You will be redirected to ToyyibPay to complete your FPX payment.';
    }
}

function setPaymentMethod(method) {
    const isQr = method === 'qr';
    const detailsStep = document.getElementById('qrStep');
    const uploadStepNumber = document.querySelector('#uploadStep .step-number');
    const tabs = document.querySelector('.payment-method-tabs');
    const receiptUploadGroup = document.getElementById('receiptUploadGroup');
    const remarksGroup = document.getElementById('remarksGroup');
    const receiptInput = document.getElementById('receipt');
    const submitTitle = document.getElementById('paymentSubmitTitle');
    const submitButton = document.getElementById('paymentSubmitButton');
    const instructionNote = document.getElementById('paymentInstructionNote');

    selectedPaymentMethod = isQr ? 'qr' : 'fpx';
    detailsStep?.classList.toggle('payment-qr-active', isQr);
    detailsStep?.classList.toggle('payment-fpx-active', !isQr);
    if (detailsStep) detailsStep.style.display = currentAppointmentCode && isQr ? 'flex' : 'none';
    if (uploadStepNumber) uploadStepNumber.textContent = isQr ? '4' : '3';
    tabs?.classList.toggle('qr-active', isQr);
    if (receiptUploadGroup) receiptUploadGroup.style.display = isQr ? '' : 'none';
    if (remarksGroup) remarksGroup.style.display = isQr ? '' : 'none';
    if (receiptInput) receiptInput.required = isQr;
    if (submitTitle) submitTitle.textContent = isQr ? 'Upload Manual Payment Receipt' : 'Pay with FPX';
    if (submitButton) submitButton.textContent = isQr ? 'Submit Payment Verification' : 'Pay with ToyyibPay';
    if (instructionNote) {
        instructionNote.textContent = isQr
            ? 'After payment, please upload the receipt below for verification.'
            : 'You will be redirected to ToyyibPay to complete FPX payment.';
    }
    if (!isQr) {
        clearReceiptSizeError();
    }

    document.querySelectorAll('.payment-method-tab').forEach(tab => {
        const active = tab.dataset.paymentMethod === selectedPaymentMethod;
        tab.classList.toggle('active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    updatePaymentMethodLabel();
    updatePaymentSummary();
}

function resetPaymentMethod() {
    selectedPaymentMethod = 'fpx';
    selectedQrType = 'tng';
    setPaymentMethod('fpx');
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
    updatePaymentSummary();
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
        updatePaymentSummary();
        document.getElementById('methodStep').style.display = 'flex';
        document.getElementById('qrStep').style.display = selectedPaymentMethod === 'qr' ? 'flex' : 'none';
        document.getElementById('uploadStep').style.display = 'flex';
    } else {
        resetPaymentMethod();
        currentAppointmentCode = '';
        updatePaymentSummary();
        document.getElementById('methodStep').style.display = 'none';
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

function showReceiptSizeError(message) {
    const errorBox = document.getElementById('receiptSizeError');
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.hidden = false;
    errorBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function clearReceiptSizeError() {
    const errorBox = document.getElementById('receiptSizeError');
    if (!errorBox) return;
    errorBox.textContent = '';
    errorBox.hidden = true;
}

function validateReceiptSizeImmediately(clearWhenEmpty = true) {
    const receiptInput = document.getElementById('receipt');
    const receiptFile = receiptInput?.files?.[0];
    const maxReceiptSize = 2 * 1024 * 1024;

    if (!receiptInput || !receiptFile) {
        if (clearWhenEmpty) {
            clearReceiptSizeError();
        }
        return true;
    }

    if (receiptFile.size > maxReceiptSize) {
        receiptInput.value = '';
        showReceiptSizeError('Receipt file is too large. Please upload a file 2MB or smaller.');
        return false;
    }

    clearReceiptSizeError();
    return true;
}

// Handle form submission
document.getElementById('paymentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const receiptInput = document.getElementById('receipt');
    if (!validateReceiptSizeImmediately(false)) {
        receiptInput.focus();
        return;
    }

    const receiptFile = receiptInput.files[0];
    const methodLabel = document.getElementById('paymentMethodLabel').value;

    if (selectedPaymentMethod === 'fpx') {
        const formData = new FormData();
        formData.append('action', 'start_toyyibpay');
        formData.append('appointment_code', document.getElementById('appointmentCode').value);
        formData.append('amount', document.getElementById('amount').value);
        formData.append('remarks', '');

        const response = await fetch('../action.php', {
            method: 'POST',
            body: formData
        });
        const rawResult = await response.text();
        let result;
        try {
            result = JSON.parse(rawResult);
        } catch (error) {
            showPaymentNotice('Error: Server returned an invalid response. ' + rawResult.slice(0, 160), 'error');
            return;
        }

        if (result.success && result.payment_url) {
            if (result.bill_code) {
                sessionStorage.setItem('pendingToyyibPayBill', result.bill_code);
            }
            window.location.href = result.payment_url;
        } else {
            showPaymentNotice('Error: ' + (result.message || 'Failed to start ToyyibPay payment'), 'error');
        }
        return;
    }

    if (!receiptFile) {
        showPaymentNotice('Please upload payment receipt.', 'error');
        receiptInput.focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'submit_payment');
    formData.append('appointment_code', document.getElementById('appointmentCode').value);
    formData.append('amount', document.getElementById('amount').value);
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

function failPendingToyyibPayIfReturned() {
    const billCode = sessionStorage.getItem('pendingToyyibPayBill');
    if (!billCode) return;

    const navigationEntry = performance.getEntriesByType?.('navigation')?.[0];
    const cameBack = navigationEntry?.type === 'back_forward';
    if (!cameBack) return;

    const formData = new FormData();
    formData.append('action', 'fail_toyyibpay_pending');
    formData.append('bill_code', billCode);

    fetch('../action.php', {
        method: 'POST',
        body: formData,
        keepalive: true
    }).finally(() => {
        sessionStorage.removeItem('pendingToyyibPayBill');
        showPaymentNotice('FPX payment was not completed and has been marked as failed.', 'error');
        setTimeout(() => window.location.reload(), 1200);
    });
}

window.addEventListener('pageshow', failPendingToyyibPayIfReturned);

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.payment-method-tab').forEach(tab => {
        tab.addEventListener('click', () => setPaymentMethod(tab.dataset.paymentMethod || 'fpx'));
    });
    document.querySelectorAll('.qr-type-tab').forEach(tab => {
        tab.addEventListener('click', () => setQrType(tab.dataset.qrType || 'bank'));
    });
    document.getElementById('receipt')?.addEventListener('change', validateReceiptSizeImmediately);
    setPaymentMethod('fpx');
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
