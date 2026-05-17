<?php
require_once dirname(__DIR__) . '/functions.php';
protect_page();
app_header('QuickCare');
app_start('user', 'payment');
?>

<div class="checkout-container">
    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step" id="step1">
            <div class="step-circle">1</div>
            <div class="step-title">Select Payment</div>
        </div>
        <div class="step" id="step2">
            <div class="step-circle">2</div>
            <div class="step-title">Choose Method</div>
        </div>
        <div class="step" id="step3">
            <div class="step-circle">3</div>
            <div class="step-title">Payment Details</div>
        </div>
        <div class="step" id="step4">
            <div class="step-circle">4</div>
            <div class="step-title">Confirmation</div>
        </div>
    </div>

    <div class="checkout-layout">
        <!-- Left Column - Payment Steps -->
        <div class="payment-section">
            <!-- Step 1: Select Payment -->
            <div id="step1Content">
                <div class="section-title">
                    <span class="section-icon">💰</span> Select Payment to Settle
                </div>
                <div class="payment-items" id="paymentItems">
                    <div class="payment-item" data-id="1" data-amount="52.00" data-name="General Check-up">
                        <div class="payment-item-info">
                            <h4>APT-0145 - General Check-up</h4>
                            <p>Date: May 5, 2026 | Dr. Sarah Lim | 09:00 AM</p>
                        </div>
                        <div class="payment-item-amount">RM 52.00</div>
                    </div>
                    <div class="payment-item" data-id="2" data-amount="52.00" data-name="Blood Test">
                        <div class="payment-item-info">
                            <h4>APT-0142 - Blood Test</h4>
                            <p>Date: May 4, 2026 | Dr. Sarah Lim | 02:00 PM</p>
                        </div>
                        <div class="payment-item-amount">RM 52.00</div>
                    </div>
                    <div class="payment-item" data-id="3" data-amount="82.00" data-name="Dental Care">
                        <div class="payment-item-info">
                            <h4>APT-0144 - Dental Care</h4>
                            <p>Date: May 5, 2026 | Dr. Amir Hamzah | 09:30 AM</p>
                        </div>
                        <div class="payment-item-amount">RM 82.00</div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Choose Payment Method -->
            <div id="step2Content" style="display: none;">
                <div class="section-title">
                    <span class="section-icon">💳</span> Choose Payment Method
                </div>
                
                <!-- Card Payments -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 12px; color: #64748b; font-size: 13px; letter-spacing: 0.5px;">💳 CARD</h4>
                    <div class="payment-methods-grid">
                        <div class="method-card" data-method="credit_card" data-type="card">
                            <div class="radio-indicator"></div>
                            <span class="method-icon">💳</span>
                            <h4>Credit / Debit Card</h4>
                            <p>Visa, Mastercard</p>
                        </div>
                    </div>
                </div>

                <!-- Online Banking -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 12px; color: #64748b; font-size: 13px; letter-spacing: 0.5px;">🏦 ONLINE BANKING</h4>
                    <div class="payment-methods-grid">
                        <div class="method-card" data-method="fpx" data-type="banking">
                            <div class="radio-indicator"></div>
                            <span class="method-icon">🏦</span>
                            <h4>FPX</h4>
                            <p>11 Malaysian Banks</p>
                        </div>
                    </div>
                </div>

                <!-- E-Wallet Category -->
                <div style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 12px; color: #64748b; font-size: 13px; letter-spacing: 0.5px;">📱 E-WALLET</h4>
                    <div class="payment-methods-grid">
                        <div class="method-card" data-method="ewallet" data-type="ewallet_category">
                            <div class="radio-indicator"></div>
                            <span class="method-icon">📱</span>
                            <h4>E-Wallet</h4>
                            <p>GrabPay, ShopeePay, Touch 'n Go</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Payment Details -->
            <div id="step3Content" style="display: none;">
                <div class="section-title">
                    <span class="section-icon">✏️</span> Enter Payment Details
                </div>
                <div id="paymentDetailsForm"></div>
            </div>

            <!-- Step 4: Confirmation -->
            <div id="step4Content" style="display: none;">
                <div class="section-title">
                    <span class="section-icon">✅</span> Confirm Payment
                </div>
                <div id="confirmationDetails"></div>
                <button class="btn btn-primary" id="confirmPayBtn" style="margin-top: 20px; width: 100%;">Confirm & Pay</button>
            </div>

            <!-- Navigation Buttons -->
            <div class="navigation-buttons">
                <button class="btn btn-outline" id="prevBtn" onclick="goToPrevStep()" style="display: none;">← Back</button>
                <button class="btn btn-primary" id="nextBtn" onclick="goToNextStep()">Next →</button>
            </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="order-summary">
            <div class="section-title">
                <span class="section-icon">📋</span> Order Summary
            </div>
            <div class="summary-content">
                <div class="summary-row">
                    <span>Selected Payment:</span>
                    <span id="selectedPaymentName" class="summary-value">-</span>
                </div>
                <div class="summary-row">
                    <span>Payment Method:</span>
                    <span id="selectedMethodName" class="summary-value">-</span>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-total">
                    <span>Total Amount:</span>
                    <span id="totalAmount" class="total-value">RM 0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- E-Wallet Selection Modal -->
<div id="ewalletModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <span class="modal-title">📱 Select E-Wallet</span>
            <button class="modal-close" onclick="closeEwalletModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="ewallet-options">
                <div class="ewallet-option" data-ewallet="grabpay">
                    <span class="ewallet-icon">🛵</span>
                    <div class="ewallet-info">
                        <h4>GrabPay</h4>
                        <p>Pay with GrabPay eWallet</p>
                    </div>
                </div>
                <div class="ewallet-option" data-ewallet="shopeepay">
                    <span class="ewallet-icon">🛒</span>
                    <div class="ewallet-info">
                        <h4>ShopeePay</h4>
                        <p>Pay with ShopeePay eWallet</p>
                    </div>
                </div>
                <div class="ewallet-option" data-ewallet="tng">
                    <span class="ewallet-icon">📲</span>
                    <div class="ewallet-info">
                        <h4>Touch 'n Go</h4>
                        <p>Touch 'n Go eWallet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FPX Bank Selection Modal -->
<div id="fpxModal" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 500px;">
        <div class="modal-header">
            <span class="modal-title">🏦 Select Your Bank</span>
            <button class="modal-close" onclick="closeFpxModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="bank-list" id="fpxBankList"></div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">✅ Payment Successful!</span>
            <button class="modal-close" onclick="closeSuccessModal()">✕</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <div style="font-size: 64px; margin-bottom: 20px;">✅</div>
            <p><strong>Transaction ID:</strong> <span id="transactionId"></span></p>
            <p><strong>Amount Paid:</strong> <span id="paidAmount"></span></p>
            <p><strong>Payment Method:</strong> <span id="paymentMethodDisplay"></span></p>
            <br>
            <p>Thank you for your payment!</p>
            <button class="btn btn-primary" onclick="location.reload()" style="margin-top: 20px;">Make Another Payment</button>
        </div>
    </div>
</div>

<style>
.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Progress Steps */
.progress-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    position: relative;
}

.progress-steps::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 80px;
    right: 80px;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
}

.step {
    position: relative;
    z-index: 2;
    background: white;
    text-align: center;
    flex: 1;
}

.step-circle {
    width: 36px;
    height: 36px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-weight: 600;
    color: #64748b;
    transition: all 0.3s;
}

.step.active .step-circle {
    background: #0d9488;
    color: white;
    box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.2);
}

.step.completed .step-circle {
    background: #10b981;
    color: white;
}

.step-title {
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
}

.step.active .step-title {
    color: #0d9488;
    font-weight: 600;
}

.step.completed .step-title {
    color: #10b981;
}

/* Layout */
.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
}

.payment-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.order-summary {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-icon {
    font-size: 20px;
}

/* Payment Items */
.payment-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.payment-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-item:hover {
    border-color: #0d9488;
    background: #f0fdfa;
}

.payment-item.selected {
    border-color: #0d9488;
    background: #f0fdfa;
}

.payment-item-info h4 {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #1e293b;
}

.payment-item-info p {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 0;
}

.payment-item-amount {
    font-size: 16px;
    font-weight: 700;
    color: #0d9488;
}

/* Payment Methods Grid */
.payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.method-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.method-card:hover {
    border-color: #0d9488;
    background: #f0fdfa;
}

.method-card.selected {
    border-color: #0d9488;
    background: #f0fdfa;
}

.method-icon {
    font-size: 28px;
    display: block;
    margin-bottom: 8px;
}

.method-card h4 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #1e293b;
}

.method-card p {
    font-size: 11px;
    color: #64748b;
}

.radio-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 16px;
    height: 16px;
    border: 2px solid #cbd5e1;
    border-radius: 50%;
}

.method-card.selected .radio-indicator {
    border-color: #0d9488;
    background: #0d9488;
    box-shadow: inset 0 0 0 3px white;
}

/* E-Wallet Options */
.ewallet-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ewallet-option {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.ewallet-option:hover {
    border-color: #0d9488;
    background: #f0fdfa;
}

.ewallet-icon {
    font-size: 32px;
}

.ewallet-info h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #1e293b;
}

.ewallet-info p {
    font-size: 12px;
    color: #64748b;
}

/* Bank List */
.bank-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
}

.bank-option {
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    font-size: 14px;
    transition: all 0.2s;
}

.bank-option:hover {
    border-color: #0d9488;
    background: #f0fdfa;
}

.bank-option.selected {
    background: #0d9488;
    color: white;
    border-color: #0d9488;
}

/* Forms */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #334155;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Navigation Buttons */
.navigation-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
}

.btn {
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.btn-primary {
    background: #0d9488;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #0f766e;
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    border: 1px solid #e2e8f0;
    color: #64748b;
}

.btn-outline:hover {
    border-color: #0d9488;
    color: #0d9488;
}

/* Order Summary */
.summary-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #64748b;
}

.summary-value {
    color: #1e293b;
    font-weight: 500;
}

.summary-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 8px 0;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.total-value {
    color: #0d9488;
    font-size: 18px;
}

/* Confirmation Box */
.confirmation-box {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
}

.confirmation-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
}

.confirmation-label {
    color: #64748b;
}

.confirmation-value {
    font-weight: 500;
    color: #1e293b;
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
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideIn 0.3s;
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-title {
    font-size: 18px;
    font-weight: 600;
}

.modal-close {
    font-size: 24px;
    cursor: pointer;
    background: none;
    border: none;
}

.modal-body {
    padding: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }
    
    .progress-steps::before {
        left: 40px;
        right: 40px;
    }
    
    .payment-methods-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// State variables
let currentStep = 1;
let selectedPaymentId = null;
let selectedPaymentAmount = null;
let selectedPaymentName = null;
let selectedMethod = null;
let selectedEwallet = null;
let selectedBank = null;

// Method names mapping
const methodNames = {
    'credit_card': 'Credit/Debit Card',
    'fpx': 'FPX - Online Banking',
    'grabpay': 'GrabPay',
    'shopeepay': 'ShopeePay',
    'tng': 'Touch n Go'
};

// Method icons
const methodIcons = {
    'credit_card': '💳',
    'fpx': '🏦',
    'grabpay': '🛵',
    'shopeepay': '🛒',
    'tng': '📲'
};

// FPX Banks
const fpxBanks = ['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank', 'AmBank', 'Bank Islam', 'Bank Rakyat', 'Affin Bank', 'UOB Bank', 'OCBC Bank'];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    const firstPayment = document.querySelector('.payment-item');
    if (firstPayment) {
        firstPayment.click();
    }
});

// Step 1: Select Payment
document.querySelectorAll('.payment-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.payment-item').forEach(i => i.classList.remove('selected'));
        this.classList.add('selected');
        selectedPaymentId = this.dataset.id;
        selectedPaymentAmount = parseFloat(this.dataset.amount);
        selectedPaymentName = this.querySelector('h4').innerText;
        updateOrderSummary();
    });
});

// Step 2: Select Payment Method
function attachMethodListeners() {
    document.querySelectorAll('.method-card').forEach(card => {
        card.addEventListener('click', function() {
            const method = this.dataset.method;
            const type = this.dataset.type;
            
            if (type === 'ewallet_category') {
                // Show e-wallet selection modal
                showEwalletModal();
            } else if (method === 'fpx') {
                // Show FPX bank selection modal
                showFpxModal();
            } else {
                // Regular method selection
                document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedMethod = method;
                selectedEwallet = null;
                selectedBank = null;
                updateOrderSummary();
            }
        });
    });
}

// Show E-Wallet Modal
function showEwalletModal() {
    const modal = document.getElementById('ewalletModal');
    modal.style.display = 'flex';
    
    document.querySelectorAll('.ewallet-option').forEach(option => {
        option.addEventListener('click', function() {
            const ewallet = this.dataset.ewallet;
            selectedMethod = ewallet;
            selectedEwallet = ewallet;
            
            // Update the selected method card
            document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
            const ewalletCard = document.querySelector('.method-card[data-method="ewallet"]');
            if (ewalletCard) {
                ewalletCard.classList.add('selected');
                // Update the card text to show selected e-wallet
                const cardTitle = ewalletCard.querySelector('h4');
                if (cardTitle) {
                    cardTitle.innerHTML = `${methodIcons[ewallet]} ${methodNames[ewallet]}`;
                }
            }
            
            updateOrderSummary();
            closeEwalletModal();
        });
    });
}

function closeEwalletModal() {
    document.getElementById('ewalletModal').style.display = 'none';
}

// Show FPX Modal
function showFpxModal() {
    const modal = document.getElementById('fpxModal');
    const bankList = document.getElementById('fpxBankList');
    
    // Populate banks
    bankList.innerHTML = fpxBanks.map(bank => `
        <div class="bank-option" data-bank="${bank}">${bank}</div>
    `).join('');
    
    modal.style.display = 'flex';
    
    document.querySelectorAll('.bank-option').forEach(option => {
        option.addEventListener('click', function() {
            selectedBank = this.dataset.bank;
            selectedMethod = 'fpx';
            
            // Update the selected method card
            document.querySelectorAll('.method-card').forEach(c => c.classList.remove('selected'));
            const fpxCard = document.querySelector('.method-card[data-method="fpx"]');
            if (fpxCard) {
                fpxCard.classList.add('selected');
                // Update the card text to show selected bank
                const cardTitle = fpxCard.querySelector('h4');
                if (cardTitle) {
                    cardTitle.innerHTML = `🏦 ${selectedBank}`;
                }
                const cardDesc = fpxCard.querySelector('p');
                if (cardDesc) {
                    cardDesc.innerHTML = 'Online Banking';
                }
            }
            
            updateOrderSummary();
            closeFpxModal();
        });
    });
}

function closeFpxModal() {
    document.getElementById('fpxModal').style.display = 'none';
}

// Step 3: Load payment details form
function loadPaymentDetailsForm() {
    const container = document.getElementById('paymentDetailsForm');
    let formHtml = '';
    
    switch(selectedMethod) {
        case 'credit_card':
            formHtml = `
                <form id="detailsForm">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" class="form-control" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" class="form-control" id="expiry" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" class="form-control" id="cvv" placeholder="123" maxlength="4" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" class="form-control" id="cardholderName" placeholder="Name on card" required>
                    </div>
                </form>
            `;
            break;
            
        case 'fpx':
            formHtml = `
                <form id="detailsForm">
                    <div class="form-group">
                        <label>Bank</label>
                        <input type="text" class="form-control" value="${selectedBank}" disabled style="background: #f8fafc;">
                    </div>
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" class="form-control" placeholder="Enter your account number" required>
                    </div>
                    <div class="form-group">
                        <label>ID Number (IC/Passport)</label>
                        <input type="text" class="form-control" placeholder="Enter your IC number" required>
                    </div>
                </form>
            `;
            break;
            
        case 'grabpay':
            formHtml = `
                <form id="detailsForm">
                    <div class="form-group">
                        <label>GrabPay Registered Mobile Number</label>
                        <input type="tel" class="form-control" placeholder="0123456789" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" placeholder="your@email.com" required>
                    </div>
                </form>
            `;
            break;
            
        case 'shopeepay':
            formHtml = `
                <form id="detailsForm">
                    <div class="form-group">
                        <label>ShopeePay Mobile Number</label>
                        <input type="tel" class="form-control" placeholder="0123456789" required>
                    </div>
                    <div class="form-group">
                        <label>Shopee Username</label>
                        <input type="text" class="form-control" placeholder="Your Shopee username" required>
                    </div>
                </form>
            `;
            break;
            
        case 'tng':
            formHtml = `
                <form id="detailsForm">
                    <div class="form-group">
                        <label>Touch 'n Go Mobile Number</label>
                        <input type="tel" class="form-control" placeholder="0123456789" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" placeholder="your@email.com" required>
                    </div>
                </form>
            `;
            break;
    }
    
    container.innerHTML = formHtml;
}

// Load confirmation details
function loadConfirmationDetails() {
    const container = document.getElementById('confirmationDetails');
    let paymentDetailText = '';
    let methodDisplayName = '';
    
    if (selectedMethod === 'fpx') {
        methodDisplayName = `FPX - ${selectedBank}`;
        paymentDetailText = `Bank: ${selectedBank}`;
    } else if (selectedMethod === 'grabpay') {
        methodDisplayName = 'GrabPay';
        paymentDetailText = `E-Wallet: GrabPay`;
    } else if (selectedMethod === 'shopeepay') {
        methodDisplayName = 'ShopeePay';
        paymentDetailText = `E-Wallet: ShopeePay`;
    } else if (selectedMethod === 'tng') {
        methodDisplayName = 'Touch n Go';
        paymentDetailText = `E-Wallet: Touch n Go`;
    } else {
        methodDisplayName = methodNames[selectedMethod];
        paymentDetailText = 'Card payment';
    }
    
    container.innerHTML = `
        <div class="confirmation-box">
            <div class="confirmation-row">
                <span class="confirmation-label">Appointment:</span>
                <span class="confirmation-value">${selectedPaymentName}</span>
            </div>
            <div class="confirmation-row">
                <span class="confirmation-label">Payment Method:</span>
                <span class="confirmation-value">${methodDisplayName}</span>
            </div>
            <div class="confirmation-row">
                <span class="confirmation-label">Payment Details:</span>
                <span class="confirmation-value">${paymentDetailText}</span>
            </div>
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                <div class="confirmation-row">
                    <span class="confirmation-label"><strong>Total Amount:</strong></span>
                    <span class="confirmation-value" style="color: #0d9488; font-size: 18px; font-weight: 700;">RM ${selectedPaymentAmount.toFixed(2)}</span>
                </div>
            </div>
        </div>
    `;
}

// Update order summary
function updateOrderSummary() {
    if (selectedPaymentName) {
        document.getElementById('selectedPaymentName').innerHTML = selectedPaymentName;
    }
    
    let methodDisplay = '';
    if (selectedMethod === 'fpx' && selectedBank) {
        methodDisplay = `🏦 FPX - ${selectedBank}`;
    } else if (selectedMethod === 'grabpay') {
        methodDisplay = `🛵 GrabPay`;
    } else if (selectedMethod === 'shopeepay') {
        methodDisplay = `🛒 ShopeePay`;
    } else if (selectedMethod === 'tng') {
        methodDisplay = `📲 Touch n Go`;
    } else if (selectedMethod === 'credit_card') {
        methodDisplay = `💳 Credit/Debit Card`;
    }
    
    if (methodDisplay) {
        document.getElementById('selectedMethodName').innerHTML = methodDisplay;
    }
    
    if (selectedPaymentAmount) {
        document.getElementById('totalAmount').innerHTML = `RM ${selectedPaymentAmount.toFixed(2)}`;
    }
}

// Navigation functions
function goToNextStep() {
    if (currentStep === 1 && !selectedPaymentId) {
        alert('Please select a payment to proceed');
        return;
    }
    if (currentStep === 2 && !selectedMethod) {
        alert('Please select a payment method');
        return;
    }
    if (currentStep === 3) {
        const form = document.getElementById('detailsForm');
        if (form && !form.checkValidity()) {
            form.reportValidity();
            return;
        }
    }
    
    document.getElementById(`step${currentStep}Content`).style.display = 'none';
    currentStep++;
    document.getElementById(`step${currentStep}Content`).style.display = 'block';
    updateProgressSteps();
    
    document.getElementById('prevBtn').style.display = currentStep > 1 ? 'inline-flex' : 'none';
    document.getElementById('nextBtn').innerHTML = currentStep === 4 ? 'Review Payment' : 'Next →';
    
    if (currentStep === 2) attachMethodListeners();
    if (currentStep === 3) loadPaymentDetailsForm();
    if (currentStep === 4) {
        loadConfirmationDetails();
        document.getElementById('nextBtn').style.display = 'none';
        document.getElementById('confirmPayBtn').style.display = 'block';
    }
}

function goToPrevStep() {
    document.getElementById(`step${currentStep}Content`).style.display = 'none';
    currentStep--;
    document.getElementById(`step${currentStep}Content`).style.display = 'block';
    updateProgressSteps();
    
    document.getElementById('prevBtn').style.display = currentStep > 1 ? 'inline-flex' : 'none';
    document.getElementById('nextBtn').style.display = 'inline-flex';
    document.getElementById('confirmPayBtn').style.display = 'none';
    document.getElementById('nextBtn').innerHTML = 'Next →';
}

function updateProgressSteps() {
    for (let i = 1; i <= 4; i++) {
        const step = document.getElementById(`step${i}`);
        step.classList.remove('active', 'completed');
        if (i < currentStep) {
            step.classList.add('completed');
            step.querySelector('.step-circle').innerHTML = '✓';
        } else if (i === currentStep) {
            step.classList.add('active');
            step.querySelector('.step-circle').innerHTML = i;
        } else {
            step.querySelector('.step-circle').innerHTML = i;
        }
    }
}

// Process payment
function processPayment() {
    const transactionId = 'TXN' + Date.now() + Math.floor(Math.random() * 1000);
    
    let methodDisplay = '';
    if (selectedMethod === 'fpx' && selectedBank) {
        methodDisplay = `FPX - ${selectedBank}`;
    } else if (selectedMethod === 'grabpay') {
        methodDisplay = 'GrabPay';
    } else if (selectedMethod === 'shopeepay') {
        methodDisplay = 'ShopeePay';
    } else if (selectedMethod === 'tng') {
        methodDisplay = 'Touch n Go';
    } else {
        methodDisplay = methodNames[selectedMethod];
    }
    
    document.getElementById('transactionId').innerText = transactionId;
    document.getElementById('paidAmount').innerText = `RM ${selectedPaymentAmount.toFixed(2)}`;
    document.getElementById('paymentMethodDisplay').innerHTML = methodDisplay;
    document.getElementById('successModal').style.display = 'flex';
}

function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
    location.reload();
}

// Attach confirm button
document.getElementById('confirmPayBtn')?.addEventListener('click', processPayment);

// Format card number
function formatCardNumber(input) {
    let value = input.value.replace(/\s/g, '');
    if (value.length > 16) value = value.slice(0, 16);
    let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
    input.value = formatted;
}

function formatExpiry(input) {
    let value = input.value.replace('/', '');
    if (value.length >= 2) {
        value = value.slice(0,2) + '/' + value.slice(2);
    }
    input.value = value;
}

document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'cardNumber') formatCardNumber(e.target);
    if (e.target && e.target.id === 'expiry') formatExpiry(e.target);
});
</script>

<?php
app_end();
?>
