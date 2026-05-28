<?php
// reports.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$owner_id = get_owner_id();

// Date Filters (Default to this month)
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Total Revenue & Count
$stmt = $pdo->prepare("SELECT SUM(total_amount) as total_rev, COUNT(*) as total_bills, 
                       SUM(CASE WHEN payment_type = 'khata' THEN total_amount ELSE 0 END) as total_khata 
                       FROM sales 
                       WHERE user_id = ? AND sale_time BETWEEN ? AND ?");
// Use full timestamp range for accuracy
$query_start = date('Y-m-d 00:00:00', strtotime($start_date));
$query_end = date('Y-m-d 23:59:59', strtotime($end_date));

$stmt->execute([$owner_id, $query_start, $query_end]);
$summary = $stmt->fetch();

// 2. Daily Sales (For Chart)
// Use to_char for reliable date formatting from timestamp
$stmt = $pdo->prepare("SELECT DATE_FORMAT(sale_time, '%Y-%m-%d') as sale_date, SUM(total_amount) as daily_total 
                       FROM sales 
                       WHERE user_id = ? AND sale_time BETWEEN ? AND ? 
                       GROUP BY sale_date ORDER BY sale_date");
$stmt->execute([$owner_id, $query_start, $query_end]);
$daily_sales = $stmt->fetchAll();

// 3. Product Performance (Top Selling)
$stmt = $pdo->prepare("SELECT p.name, SUM(si.quantity) as qty_sold, SUM(si.quantity * si.price_at_sale) as revenue 
                       FROM sale_items si 
                       JOIN products p ON si.product_id = p.id 
                       JOIN sales s ON si.sale_id = s.id 
                       WHERE s.user_id = ? AND s.sale_time BETWEEN ? AND ? 
                       GROUP BY p.id, p.name 
                       ORDER BY qty_sold DESC LIMIT 10");
$stmt->execute([$owner_id, $query_start, $query_end]);
$top_products = $stmt->fetchAll();

// 4. Total Profit Calculation
// Note: Uses current cost_price from products table. Ideally, store cost_at_sale in sale_items for historical accuracy.
$stmt = $pdo->prepare("SELECT SUM((si.price_at_sale - COALESCE(p.cost_price, 0)) * si.quantity) as total_profit 
                       FROM sale_items si 
                       JOIN sales s ON si.sale_id = s.id 
                       JOIN products p ON si.product_id = p.id 
                       WHERE s.user_id = ? AND s.sale_time BETWEEN ? AND ?");
$stmt->execute([$owner_id, $query_start, $query_end]);
$profit_data = $stmt->fetch();
$total_profit = $profit_data['total_profit'] ?? 0;

require_once 'includes/header.php';
?>

<div class="mb-5">
    <h2 style="font-weight: 700; font-size: 1.75rem; color: #1e293b; margin-bottom: 1.5rem;">Reports & Analytics</h2>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 1rem 1.5rem; border: none; box-shadow: var(--shadow-sm); margin-bottom: 2rem;">
    <form method="GET" class="d-flex gap-4 items-center">
        <div class="d-flex items-center gap-3">
            <label style="font-size: 0.85rem; font-weight: 500; color: #64748b;">From:</label>
            <input type="date" name="start_date" class="form-control" style="width: 160px; height: 40px; border-radius: 8px; border: 1px solid #e2e8f0;" value="<?= $start_date ?>">
        </div>
        <div class="d-flex items-center gap-3">
            <label style="font-size: 0.85rem; font-weight: 500; color: #64748b;">To:</label>
            <input type="date" name="end_date" class="form-control" style="width: 160px; height: 40px; border-radius: 8px; border: 1px solid #e2e8f0;" value="<?= $end_date ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 40px; border-radius: 8px; padding: 0 1.5rem; background: #6366f1;">
            Filter
        </button>
    </form>
</div>

<!-- Metric Cards -->
<div class="stats-grid mb-5" style="grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
    <div class="stat-card" style="padding: 1.75rem; border: none; box-shadow: var(--shadow-sm);">
        <div class="stat-icon" style="background: #eef2ff; color: #6366f1; width: 56px; height: 56px; border-radius: 12px; font-size: 1.5rem;"><i class="fas fa-chart-line"></i></div>
        <div style="margin-left: 1rem;">
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 800;">₹<?= number_format($summary['total_rev'] ?? 0, 2) ?></div>
            <div class="stat-label" style="font-size: 0.9rem; font-weight: 500; color: #94a3b8;">Total Revenue</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.75rem; border: none; box-shadow: var(--shadow-sm);">
        <div class="stat-icon" style="background: #f0fdf4; color: #22c55e; width: 56px; height: 56px; border-radius: 12px; font-size: 1.5rem;"><i class="fas fa-receipt"></i></div>
        <div style="margin-left: 1rem;">
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 800;"><?= number_format($summary['total_bills'] ?? 0) ?></div>
            <div class="stat-label" style="font-size: 0.9rem; font-weight: 500; color: #94a3b8;">Total Bills</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.75rem; border: none; box-shadow: var(--shadow-sm);">
        <div class="stat-icon" style="background: #fff7ed; color: #f59e0b; width: 56px; height: 56px; border-radius: 12px; font-size: 1.5rem;"><i class="fas fa-file-invoice-dollar"></i></div>
        <div style="margin-left: 1rem;">
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 800;">₹<?= number_format($summary['total_khata'] ?? 0, 2) ?></div>
            <div class="stat-label" style="font-size: 0.9rem; font-weight: 500; color: #94a3b8;">Credit Sales (Khata)</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 1.75rem; border: none; box-shadow: var(--shadow-sm);">
        <div class="stat-icon" style="background: #faf5ff; color: #a855f7; width: 56px; height: 56px; border-radius: 12px; font-size: 1.5rem;"><i class="fas fa-hand-holding-usd"></i></div>
        <div style="margin-left: 1rem;">
            <div class="stat-value" style="font-size: 1.75rem; font-weight: 800; color: #166534;">₹<?= number_format($total_profit, 2) ?></div>
            <div class="stat-label" style="font-size: 0.9rem; font-weight: 500; color: #94a3b8;">Total Profit</div>
        </div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
    
    <!-- Sales Trend Chart -->
    <div class="card" style="padding: 2rem; border: none; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 2rem;">Sales Trend</h3>
        <div style="position: relative; height: 400px; width: 100%;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="card" style="padding: 2rem; border: none; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 2rem;">Top Selling Products</h3>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #f1f5f9;">
                        <th class="table-head-alt" style="padding: 1rem 0;">Product</th>
                        <th class="table-head-alt" style="padding: 1rem 0; text-align: center;">Qty</th>
                        <th class="table-head-alt" style="padding: 1rem 0; text-align: right;">Rev</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($top_products) > 0): ?>
                        <?php foreach ($top_products as $p): ?>
                        <tr style="border-bottom: 1px solid #f8fafc; font-size: 0.95rem;">
                            <td style="padding: 1.25rem 0; color: #475569;"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="padding: 1.25rem 0; text-align: center; font-weight: 700; color: #1e293b;"><?= $p['qty_sold'] ?></td>
                            <td style="padding: 1.25rem 0; text-align: right; color: #475569;">₹<?= number_format($p['revenue'], 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="padding: 2rem; text-align: center; color: #94a3b8;">No sales in this period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesData = <?= json_encode($daily_sales) ?>;
    
    // Process Data
    const labels = salesData.map(d => d.sale_date);
    const data = salesData.map(d => d.daily_total);

    // Create Gradient (Purple Theme from Screenshot)
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(168, 85, 247, 0.4)'); // Purple Area
    gradient.addColorStop(1, 'rgba(168, 85, 247, 0.05)'); 

    new Chart(ctx, {
        type: 'line', 
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: data,
                borderColor: '#a855f7', // Purple line
                backgroundColor: gradient,
                borderWidth: 4,
                tension: 0.45,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#a855f7',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 9,
                pointHoverBackgroundColor: '#a855f7',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { 
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', borderDash: [5, 5] },
                    ticks: { 
                        font: { family: "'Inter', sans-serif", size: 12 },
                        color: '#94a3b8',
                        callback: function(value) { return '₹' + value; }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Inter', sans-serif", size: 12 }, color: '#94a3b8' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    displayColors: false,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 14 },
                    callbacks: {
                        label: function(context) { return 'Revenue: ₹' + context.parsed.y.toLocaleString(); }
                    }
                }
            }
        }
    });

    // Auto-Refresh
    let refreshTimer = setTimeout(() => window.location.reload(), 60000);
</script>

<?php require_once 'includes/footer.php'; ?>
