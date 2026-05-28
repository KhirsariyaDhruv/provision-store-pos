<?php
// index.php
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
require_once 'config/db.php'; // Ensure DB is also there if needed by functions
require_once 'includes/header.php';

// Check role for specific content if needed
$is_admin = ($_SESSION['role'] == 'admin');

// Fetch Last Login
$stmt = $pdo->prepare("SELECT login_time FROM login_history WHERE user_id = ? ORDER BY login_time DESC LIMIT 1 OFFSET 1");
$stmt->execute([$_SESSION['user_id']]);
$last_login = $stmt->fetchColumn(); 
$last_login_display = $last_login ? date('d M Y, h:i A', strtotime($last_login)) : 'First Login';

// Dashboard Stats
$today = date('Y-m-d');
// Use get_owner_id() to handle both Admin (uses own ID) and Staff (uses Admin's ID)
$user_id = get_owner_id();

// 1. Today's Revenue & Sales Count
$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d 23:59:59');

$stmt = $pdo->prepare("SELECT SUM(total_amount) as revenue, COUNT(*) as sales_count FROM sales WHERE user_id = ? AND sale_time BETWEEN ? AND ?");
$stmt->execute([$user_id, $start_date, $end_date]);
$daily_stats = $stmt->fetch();

// 2. Low Stock Items (< 5)
$stmt = $pdo->prepare("SELECT name, stock FROM products WHERE user_id = ? AND stock < 5 AND barcode_active = TRUE ORDER BY stock ASC");
$stmt->execute([$user_id]);
$low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$low_stock_count = count($low_stock_items);

// 3. Pending Khata (Total Due)
$stmt = $pdo->prepare("SELECT SUM(total_due) FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$pending_khata = $stmt->fetchColumn();

// 4. Recent Sales
$stmt = $pdo->prepare("SELECT s.id, s.bill_number, s.total_amount, s.payment_type, s.sale_time, c.name as customer_name 
                       FROM sales s 
                       LEFT JOIN customers c ON s.customer_id = c.id 
                       WHERE s.user_id = ? 
                       ORDER BY s.sale_time DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_sales = $stmt->fetchAll();

// 5. AI Sales Forecasting Logic
// Fetch last 30 days of daily sales
$forecast_days = 30;
$forecast_start = date('Y-m-d', strtotime("-$forecast_days days"));
$stmt = $pdo->prepare("SELECT DATE(sale_time) as sdate, SUM(total_amount) as total FROM sales WHERE user_id = ? AND sale_time >= ? GROUP BY DATE(sale_time) ORDER BY sdate ASC");
$stmt->execute([$user_id, $forecast_start]);
$history_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['2023-10-01' => 1200, ...]

// Prepare arrays for Chart.js
$chart_labels = [];
$chart_history = [];
$chart_forecast = [];

// Fill in missing days with 0 and build coordinate arrays for regression
$x_values = [];
$y_values = [];
$day_index = 0;

for ($i = $forecast_days; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $val = $history_data[$d] ?? 0;
    
    $chart_labels[] = date('d M', strtotime($d));
    $chart_history[] = $val;
    $chart_forecast[] = null; // No forecast for past
    
    $x_values[] = $day_index;
    $y_values[] = $val;
    $day_index++;
}

// Linear Regression Calculation (Least Squares)
$n = count($x_values);
if ($n > 1) {
    $sum_x = array_sum($x_values);
    $sum_y = array_sum($y_values);
    $sum_xx = 0;
    $sum_xy = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sum_xx += $x_values[$i] * $x_values[$i];
        $sum_xy += $x_values[$i] * $y_values[$i];
    }
    
    $denominator = ($n * $sum_xx) - ($sum_x * $sum_x);
    if ($denominator != 0) {
        $slope = (($n * $sum_xy) - ($sum_x * $sum_y)) / $denominator;
        $intercept = ($sum_y - ($slope * $sum_x)) / $n;
        
        // Connect last history point
        $last_hist_val = end($chart_history);
        $chart_forecast[count($chart_forecast)-1] = $last_hist_val;

        // Predict next 7 days
        for ($j = 1; $j <= 7; $j++) {
            $next_day_idx = $day_index + $j;
            $predicted_val = ($slope * $next_day_idx) + $intercept;
            $predicted_val = max(0, $predicted_val); // No negative sales
            
            $chart_labels[] = date('d M', strtotime("+$j days"));
            $chart_history[] = null;
            $chart_forecast[] = round($predicted_val, 2);
        }
    }
}
?>

<div class="dashboard-header d-flex justify-between items-center mb-3 mobile-stack">
    <div>
        <h2>Dashboard</h2>
        <p class="text-muted">
            Shop: <strong><?= htmlspecialchars($_SESSION['shop_name'] ?? 'My Shop') ?></strong> 
            <span class="last-login-meta" style="font-size: 0.85rem; margin-left: 10px; padding-left: 10px; border-left: 1px solid #ccc;">
                <i class="far fa-clock"></i> Last Login: <?= $last_login_display ?>
            </span>
        </p>
    </div>
    <div class="header-actions">
        <a href="pos.php" class="btn btn-primary" style="width: 100%;"><i class="fas fa-cash-register" style="margin-right: 8px;"></i> New Bill</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card" style="border-bottom: 4px solid #4f46e5;">
        <div class="stat-icon" style="background: #e0e7ff; color: #4f46e5;"><i class="fas fa-rupee-sign"></i></div>
        <div>
            <div class="stat-value">₹<?= number_format($daily_stats['revenue'] ?? 0, 2) ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
    </div>
    <div class="stat-card" style="border-bottom: 4px solid #10b981;">
        <div class="stat-icon" style="background: #dcfce7; color: #166534;"><i class="fas fa-shopping-cart"></i></div>
        <div>
            <div class="stat-value"><?= number_format($daily_stats['sales_count'] ?? 0) ?></div>
            <div class="stat-label">Total Sales Today</div>
        </div>
    </div>
    <div class="stat-card" onclick="openLowStockModal()" style="cursor: pointer; border-bottom: 4px solid #ef4444;">
        <div class="stat-icon" style="background: #fee2e2; color: #991b1b;"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="stat-value"><?= number_format($low_stock_count) ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
    </div>
    <div class="stat-card" style="border-bottom: 4px solid #f59e0b;">
        <div class="stat-icon" style="background: #ffedd5; color: #9a3412;"><i class="fas fa-book"></i></div>
        <div>
            <div class="stat-value">₹<?= number_format($pending_khata ?? 0, 2) ?></div>
            <div class="stat-label">Total Pending Khata</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="d-flex justify-between items-center mb-3">
        <h3>Recent Sales Activity</h3>
        <div class="d-flex gap-2">
            <a href="export_sales.php" class="btn" style="background: #10b981; color: white; padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="reports.php" style="font-size: 0.9rem; color: var(--primary); text-decoration: none; padding: 0.4rem 0.8rem; display: flex; align-items: center;">
                View Reports <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
            </a>
        </div>
    </div>
    
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Sale ID</th>
                    <th>Customer</th>
                    <th>Time</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_sales): ?>
                    <?php foreach ($recent_sales as $sale): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($sale['bill_number'] ?? $sale['id']) ?></td>
                        <td><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></td>
                        <td style="color: var(--text-muted); font-size: 0.9rem;">
                            <?= date('h:i A', strtotime($sale['sale_time'])) ?>
                        </td>
                        <td>
                            <?php if($sale['payment_type'] == 'khata'): ?>
                                <span class="badge" style="background: #ffedd5; color: #9a3412;">Khata</span>
                            <?php else: ?>
                                <span class="badge" style="background: #dcfce7; color: #166534;">Paid</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right" style="font-weight: 600;">₹<?= number_format($sale['total_amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center" style="padding: 2rem; color: var(--text-muted);">No sales recorded today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Auto-Refresh Dashboard every 30 seconds to show live data
    setTimeout(function() {
        window.location.reload();
    }, 30000);

    // Low Stock Modal Logic
    function openLowStockModal() {
        document.getElementById('lowStockModal').style.display = 'block';
    }
    
    function closeLowStockModal() {
        document.getElementById('lowStockModal').style.display = 'none';
    }
    
    // Close modal if clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('lowStockModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

<!-- Low Stock Details Modal -->
<div id="lowStockModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: var(--card-bg); margin: 10% auto; padding: 20px; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <div class="d-flex justify-between items-center mb-3">
            <h3 style="margin: 0; color: #991b1b;"><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h3>
            <span onclick="closeLowStockModal()" style="cursor: pointer; font-size: 1.5rem; color: var(--text-muted);">&times;</span>
        </div>
        
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="table" style="margin-top: 0;">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th class="text-right">Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($low_stock_items)): ?>
                        <?php foreach ($low_stock_items as $item): ?>
                        <tr>
                            <td style="color: var(--text-main); font-weight: 500;"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="text-right" style="color: #dc2626; font-weight: 700;">
                                <?= number_format($item['stock']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center text-muted">No items are low on stock.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-right mt-3" style="margin-top: 1rem;">
            <a href="inventory.php" class="btn btn-primary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">Go to Inventory</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
