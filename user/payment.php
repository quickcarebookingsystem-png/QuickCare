<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('user', 'payment');

$user_id = $_SESSION['id'];
$pending_payments = get_user_pending_payments($user_id);
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
                        <p>No pending payments. You don't have any approved appointments that need payment.</p>
                        <a href="<?php echo e(page_url('book', 'user')); ?>" class="btn btn-primary" style="margin-top: 10px;">Book New Appointment</a>
                    </div>
                <?php else: ?>
                    <select id="appointmentSelect" class="form-control" onchange="updateAmount()">
                        <option value="">-- Select appointment --</option>
                        <?php foreach ($pending_payments as $payment): ?>
                        <option value="<?php echo htmlspecialchars($payment['appointment_code']); ?>" 
                                data-amount="<?php echo $payment['amount']; ?>"
                                data-name="<?php echo htmlspecialchars($payment['service_name']); ?>">
                            <?php echo htmlspecialchars($payment['service_name']); ?> - 
                            with Dr. <?php echo htmlspecialchars($payment['doctor_name']); ?> -
                            RM <?php echo number_format($payment['amount'], 2); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step 2: QR Code -->
        <div class="step-box" id="qrStep" style="display: none;">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3>Scan QR Code to Pay</h3>
                <div class="qr-container">
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=QUICKCARE-PAYMENT" 
                             alt="QR Code" id="qrImage">
                    </div>
                    <div class="payment-details">
                        <div class="amount-display">
                            Amount: <strong id="payAmount">RM 0.00</strong>
                        </div>
                        <div class="bank-details">
                            <p><strong>📱 DuitNow ID:</strong> 1234567890</p>
                            <p><strong>📱 Touch 'n Go:</strong> 012-3456789</p>
                            <p><strong>🏦 Bank Transfer:</strong> Maybank 1234-5678-9012</p>
                            <p><strong>🏦 Account Name:</strong> QuickCare Clinic Sdn Bhd</p>
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
                    
                    <div class="form-group">
                        <label>Upload Receipt/Screenshot</label>
                        <input type="file" class="form-control" id="receipt" name="receipt" 
                               accept="image/*,.pdf" required>
                        <small class="text-muted">Format: JPG, PNG, PDF (Max 2MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks (Optional)</label>
                        <textarea class="form-control" id="remarks" name="remarks" 
                                  rows="2" placeholder="Any notes for admin..."></textarea>
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
    flex-wrap: wrap;
}

.qr-code {
    background: white;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid var(--border);
}

.qr-code img {
    width: 200px;
    height: 200px;
}

.payment-details {
    flex: 1;
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
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid var(--border);
}

.bank-details p {
    margin: 8px 0;
    font-size: 14px;
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
    }
}
</style>

<script>
function updateAmount() {
    const select = document.getElementById('appointmentSelect');
    const selectedOption = select.options[select.selectedIndex];
    const amount = selectedOption.dataset.amount;
    const appointmentId = selectedOption.value;
    
    if (appointmentId) {
        document.getElementById('payAmount').innerHTML = 'RM ' + parseFloat(amount).toFixed(2);
        document.getElementById('appointmentCode').value = appointmentId;
        document.getElementById('amount').value = amount;
        
        const qrData = `QUICKCARE-PAYMENT-${appointmentId}-RM${amount}`;
        document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`;
        
        document.getElementById('qrStep').style.display = 'flex';
        document.getElementById('uploadStep').style.display = 'flex';
    } else {
        document.getElementById('qrStep').style.display = 'none';
        document.getElementById('uploadStep').style.display = 'none';
    }
}

// Handle form submission
document.getElementById('paymentForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'submit_payment');
    formData.append('appointment_code', document.getElementById('appointmentCode').value);
    formData.append('amount', document.getElementById('amount').value);
    formData.append('remarks', document.getElementById('remarks').value);
    formData.append('receipt', document.getElementById('receipt').files[0]);
    
    const response = await fetch('../action.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Payment submitted! Waiting for admin approval.');
        window.location.reload();
    } else {
        alert('Error: ' + result.message);
    }
});
</script>

<?php
app_end();
?>
