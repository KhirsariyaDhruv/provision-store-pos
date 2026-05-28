// assets/js/pos.js

let cart = [];
const barcodeInput = document.getElementById('barcodeInput');
const cartTableBody = document.getElementById('cartTableBody');
const emptyCartMsg = document.getElementById('emptyCartMsg');
const grandTotalEl = document.getElementById('grandTotal');

// Auto-focus & Load Draft on page load
window.onload = () => {
    loadCartFromLocal();
    barcodeInput.focus();
};

// --- LocalStorage Draft Logic ---
function saveCartToLocal() {
    localStorage.setItem('pos_cart_draft', JSON.stringify(cart));
}

function loadCartFromLocal() {
    const saved = localStorage.getItem('pos_cart_draft');
    if (saved) {
        try {
            cart = JSON.parse(saved);
            renderCart(false); // Pass false to avoid recursive saving if needed, but renderCart calls save, which is fine (idempotent).
        } catch (e) {
            console.error('Error loading draft', e);
        }
    }
}

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        barcodeInput.focus();
    }
});

// Input Handler
// Input Handler & Autocomplete
let debounceTimer;
const autoCompleteList = document.createElement('div');
autoCompleteList.id = 'autocomplete-list';
autoCompleteList.style.cssText = `
    position: absolute; 
    top: 100%; 
    left: 0; 
    right: 0; 
    background: white; 
    border: 1px solid #e2e8f0; 
    border-radius: 0 0 8px 8px; 
    z-index: 1000; 
    max-height: 200px; 
    overflow-y: auto; 
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: none;
`;
if (barcodeInput && barcodeInput.parentNode) {
    barcodeInput.parentNode.appendChild(autoCompleteList);
}

barcodeInput.addEventListener('input', (e) => {
    const query = e.target.value.trim();
    clearTimeout(debounceTimer);

    // Clear list if empty
    if (query.length < 2) {
        autoCompleteList.style.display = 'none';
        return;
    }

    // Debounce AJAX Call
    debounceTimer = setTimeout(async () => {
        try {
            const res = await fetch(`ajax/search_products.php?query=${encodeURIComponent(query)}`);
            const products = await res.json();

            autoCompleteList.innerHTML = '';

            if (products.length > 0) {
                products.forEach(p => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding: 10px; cursor: pointer; border-bottom: 1px solid #f1f5f9;';
                    item.innerHTML = `<strong>${p.name}</strong> <span style="color:#64748b; font-size:0.9em;">(₹${p.price})</span>`;

                    item.addEventListener('mouseenter', () => item.style.background = '#f8fafc');
                    item.addEventListener('mouseleave', () => item.style.background = 'white');

                    item.addEventListener('click', () => {
                        addToCart(p);
                        barcodeInput.value = ''; // Clear input
                        autoCompleteList.style.display = 'none';
                        barcodeInput.focus();
                    });

                    autoCompleteList.appendChild(item);
                });
                autoCompleteList.style.display = 'block';
            } else {
                autoCompleteList.style.display = 'none';
            }
        } catch (err) {
            console.error('Search error:', err);
        }
    }, 300);
});

// Close list when clicking outside
document.addEventListener('click', (e) => {
    if (e.target !== barcodeInput && e.target !== autoCompleteList) {
        autoCompleteList.style.display = 'none';
    }
});

barcodeInput.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        autoCompleteList.style.display = 'none';
        handleInput();
    }
});

const searchBtn = document.getElementById('searchBtn');
if (searchBtn) {
    searchBtn.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent form submit if any
        handleInput();
        barcodeInput.focus(); // Keep focus
    });
}

async function handleInput() {
    const query = barcodeInput.value.trim();
    if (query) {
        await fetchProduct(query);
        barcodeInput.value = ''; // Clear input for next scan
    }
}

async function fetchProduct(query) {
    try {
        const response = await fetch(`ajax/get_product.php?query=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.success) {
            addToCart(data.product);
            playBeep();
            // showFlash('Product added: ' + data.product.name, 'success');
        } else {
            playErrorSound();
            alert(data.message); // Use alert for error to grab attention
        }
    } catch (error) {
        playErrorSound();
        console.error('Error:', error);
    }
}

function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);

    if (existingItem) {
        if (existingItem.qty + 1 > existingItem.max_stock) {
            alert('Cannot add more! Stock limit reached: ' + existingItem.max_stock);
            playErrorSound();
            return;
        }
        existingItem.qty++;
    } else {
        if (product.stock <= 0) {
            alert('Out of Stock!');
            playErrorSound();
            return;
        }
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            qty: 1,
            max_stock: product.stock
        });
    }
    renderCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, newQty) {
    newQty = parseInt(newQty);
    const item = cart[index];

    if (newQty > item.max_stock) {
        alert('Stock limit reached! Max available: ' + item.max_stock);
        item.qty = item.max_stock; // Reset to max
    } else if (newQty > 0) {
        item.qty = newQty;
    } else {
        item.qty = 1;
    }
    renderCart();
}

function renderCart() {
    cartTableBody.innerHTML = '';
    let total = 0;

    if (cart.length === 0) {
        emptyCartMsg.style.display = 'block';
        grandTotalEl.innerText = '0.00';
        saveCartToLocal(); // Auto-save empty state
        return;
    }

    emptyCartMsg.style.display = 'none';

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        total += itemTotal;

        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid #f1f5f9';
        row.innerHTML = `
            <td style="padding: 0.75rem;">
                <div style="font-weight: 500;">${item.name}</div>
            </td>
            <td style="padding: 0.75rem;">₹${item.price.toFixed(2)}</td>
            <td style="padding: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button onclick="updateQty(${index}, ${item.qty - 1})" 
                        style="width: 28px; height: 28px; padding: 0; border: 1px solid #cbd5e1; border-radius: 4px; background: white; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-minus" style="font-size: 0.7rem;"></i>
                    </button>
                    <input type="text" readonly value="${item.qty}" 
                        style="width: 36px; padding: 4px; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; font-weight: 600; background: #f8fafc; font-size: 0.95rem;">
                    <button onclick="updateQty(${index}, ${item.qty + 1})" 
                        style="width: 28px; height: 28px; padding: 0; border: 1px solid #cbd5e1; border-radius: 4px; background: white; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
                    </button>
                </div>
            </td>
            <td style="padding: 0.75rem; font-weight: bold;">₹${itemTotal.toFixed(2)}</td>
            <td style="padding: 0.75rem; text-align: center;">
                <button onclick="removeFromCart(${index})" style="background: none; border: none; color: #ef4444; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        `;
        cartTableBody.appendChild(row);
    });

    grandTotalEl.innerText = total.toFixed(2);
    saveCartToLocal(); // Auto-save on every render
}

function clearCart() {
    if (confirm('Clear current bill?')) {
        cart = [];
        renderCart();
    }
}

function processCheckout(method) {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }

    const modal = document.getElementById('checkoutModal');
    const title = document.getElementById('checkoutTitle');
    const amountEl = document.getElementById('checkoutAmount');
    const khataDiv = document.getElementById('khataSelector');
    const confirmBtn = document.getElementById('confirmPaymentBtn');

    modal.style.display = 'block';
    amountEl.innerText = 'Total: ₹' + grandTotalEl.innerText;

    if (method === 'khata') {
        title.innerText = 'Add to Khata';
        khataDiv.style.display = 'block';
        confirmBtn.onclick = () => submitBill('khata');
    }
    else if (method === 'wallet') {
        title.innerText = 'Pay using Wallet';
        khataDiv.style.display = 'block'; // Customer required
        confirmBtn.onclick = () => submitBill('wallet');
        confirmBtn.style.background = '#2563eb'; // Blue for Wallet
    }
    else {
        title.innerText = 'Cash Payment';
        khataDiv.style.display = 'none';
        confirmBtn.onclick = () => submitBill('cash');
        confirmBtn.style.background = '#166534'; // Green for Cash
    }
}

function closeCheckout() {
    document.getElementById('checkoutModal').style.display = 'none';
}

async function submitBill(method) {
    const customerId = document.getElementById('checkoutCustomer').value;

    if ((method === 'khata' || method === 'wallet') && !customerId) {
        alert('Please select a customer for this payment method.');
        return;
    }

    const payload = {
        cart: cart,
        payment_method: method,
        customer_id: customerId || null
    };

    const confirmBtn = document.getElementById('confirmPaymentBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerText = 'Processing...';

    try {
        const response = await fetch('ajax/save_bill.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

        if (data.success) {
            cart = [];
            renderCart();
            closeCheckout();

            // Show non-blocking success message
            const msg = document.createElement('div');
            msg.innerText = 'Bill Saved! ID: ' + data.bill_id;
            msg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #166534; color: white; padding: 15px 25px; border-radius: 8px; font-weight: bold; z-index: 2000; box-shadow: 0 4px 6px rgba(0,0,0,0.2); animation: fadeOut 3s forwards;';
            document.body.appendChild(msg);
            setTimeout(() => msg.remove(), 3000);

            barcodeInput.focus();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Connection Error');
        console.error(error);
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerText = 'Confirm Pay';
    }
}

function showFlash(message, type) {
    // Basic alert for now
    // alert(message); 
}

// --- Audio Feedback Functions ---

function playBeep() {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;

    const ctx = new AudioContext();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(1000, ctx.currentTime); // Standard beep pitch
    gain.gain.setValueAtTime(0.5, ctx.currentTime); // Much louder (was 0.1)

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start();
    osc.stop(ctx.currentTime + 0.15); // Slightly longer
}

function playErrorSound() {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;

    const ctx = new AudioContext();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sawtooth'; // Buzzer sound
    osc.frequency.setValueAtTime(200, ctx.currentTime); // Low pitch
    gain.gain.setValueAtTime(0.1, ctx.currentTime);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start();
    osc.stop(ctx.currentTime + 0.3); // Longer 300ms buzz
}

// --- Camera Scanner Logic ---
let html5QrcodeScanner = null;

function startCameraScan() {
    const modal = document.getElementById('cameraModal');
    modal.style.display = 'block';

    if (html5QrcodeScanner === null) {
        html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: 250 }
        );
    }
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
}

function onScanSuccess(decodedText, decodedResult) {
    stopCameraScan();
    document.getElementById('barcodeInput').value = decodedText;
    handleInput();
}

function onScanFailure(error) {
    // console.warn(`Code scan error = ${error}`);
}

function stopCameraScan() {
    const modal = document.getElementById('cameraModal');
    modal.style.display = 'none';
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear().catch(err => {
            console.error("Failed to clear html5QrcodeScanner. ", err);
        });
    }
}
// --- Inline Customer Creation ---

function toggleNewCustomer() {
    const form = document.getElementById('newCustomerForm');
    const selectGroup = document.getElementById('customerSelectGroup');
    const isHidden = form.style.display === 'none';

    if (isHidden) {
        form.style.display = 'block';
        selectGroup.style.display = 'none';
        document.getElementById('newCustName').focus();
    } else {
        form.style.display = 'none';
        selectGroup.style.display = 'block';
    }
}

async function saveNewCustomer() {
    const name = document.getElementById('newCustName').value.trim();
    const phone = document.getElementById('newCustPhone').value.trim();

    if (!name) {
        alert('Customer name is required');
        return;
    }

    try {
        const response = await fetch('ajax/add_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, phone })
        });
        const data = await response.json();

        if (data.success) {
            // Add to dropdown
            const select = document.getElementById('checkoutCustomer');
            const opt = document.createElement('option');
            opt.value = data.customer.id;
            opt.innerText = data.customer.name + (data.customer.phone ? ` (${data.customer.phone})` : '');
            select.appendChild(opt);
            select.value = data.customer.id; // Auto-select

            // Reset UI
            document.getElementById('newCustName').value = '';
            document.getElementById('newCustPhone').value = '';
            toggleNewCustomer(); // Switch back to select view
            // alert('Customer added successfully'); // Optional feedback
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error(error);
        alert('Failed to add customer');
    }
}
