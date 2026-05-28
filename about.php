<?php
// about.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch Current User Details for Auto-fill
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.username, p.full_name, p.shop_name, p.phone 
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$shop_name = $user['shop_name'] ?? '';
$full_name = $user['full_name'] ?? $user['username'];
$phone = $user['phone'] ?? '';

require_once 'includes/header.php';
?>

<style>
    /* Page Specific Styles */
    .hero-section {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: var(--radius);
        margin-bottom: 2rem;
        text-align: center;
        box-shadow: var(--shadow);
    }
    
    .hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        color: white;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .feature-card {
        background: white;
        padding: 1.5rem;
        border-radius: var(--radius);
        border: 1px solid var(--border-color);
        transition: transform 0.2s;
        text-align: center;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: #e0e7ff;
        color: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .team-card {
        background: #f8fafc;
        padding: 1rem;
        border-radius: var(--radius);
        border: 1px solid var(--border-color);
        text-align: center;
        transition: all 0.2s;
    }

    .team-card:hover {
        background: white;
        box-shadow: var(--shadow-sm);
        border-color: var(--primary-color);
    }

    .team-avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        margin: 0 auto 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.25rem;
        color: white;
    }

    .contact-form-container {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .contact-header {
        background: #f8fafc;
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .contact-body {
        padding: 1.5rem;
    }
</style>

<!-- Hero Section -->
<div class="hero-section">
    <h1 class="hero-title">About Provision POS</h1>
    <p class="hero-subtitle">Your comprehensive solution for billing, inventory, and customer management. Built for speed, security, and simplicity.</p>
</div>

<!-- Features Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="feature-card">
        <div class="feature-icon"><i class="fas fa-cash-register"></i></div>
        <h3>Smart Billing</h3>
        <p class="text-muted" style="font-size: 0.9rem;">Fast checkout with barcode scanning and thermal printing support.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon" style="background: #dcfce7; color: #166534;"><i class="fas fa-boxes"></i></div>
        <h3>Inventory</h3>
        <p class="text-muted" style="font-size: 0.9rem;">Real-time stock tracking with low inventory alerts.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon" style="background: #ffedd5; color: #9a3412;"><i class="fas fa-chart-pie"></i></div>
        <h3>Analytics</h3>
        <p class="text-muted" style="font-size: 0.9rem;">Detailed insights into sales trends and top-performing products.</p>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: 1.5fr 1fr; align-items: start;">

    <!-- Left Column: Team -->
    <div>
        <div class="card">
            <div class="d-flex justify-between items-center">
                <h3><i class="fas fa-users" style="color: var(--primary-color);"></i> Meet Our Team</h3>
                <span class="badge" style="background: #f1f5f9; color: #64748b;">Creators</span>
            </div>
            <p class="text-muted" style="font-size: 0.95rem;">The passionate minds behind this system.</p>

            <div class="team-grid">
                <!-- Dhruv -->
                <div class="team-card">
                    <div class="team-avatar" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">DK</div>
                    <div style="font-weight: 600; color: var(--text-main);">Dhruv Khirsariya</div>
                    <div style="font-size: 0.8rem; color: var(--secondary-color); margin-top: 4px;">9601838590</div>
                </div>

                <!-- Abhi -->
                <div class="team-card">
                    <div class="team-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">AK</div>
                    <div style="font-weight: 600; color: var(--text-main);">Abhi Kyada</div>
                    <div style="font-size: 0.8rem; color: var(--secondary-color); margin-top: 4px;">9327277459</div>
                </div>

                <!-- Aarav -->
                <div class="team-card">
                    <div class="team-avatar" style="background: linear-gradient(135deg, #f97316, #ea580c);">AH</div>
                    <div style="font-weight: 600; color: var(--text-main);">Aarav Haldariya</div>
                    <div style="font-size: 0.8rem; color: var(--secondary-color); margin-top: 4px;">Team Member</div>
                </div>

                <!-- Daksh -->
                <div class="team-card">
                    <div class="team-avatar" style="background: linear-gradient(135deg, #a855f7, #9333ea);">DD</div>
                    <div style="font-weight: 600; color: var(--text-main);">Daksh Digashia</div>
                    <div style="font-size: 0.8rem; color: var(--secondary-color); margin-top: 4px;">Team Member</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Contact Form -->
    <div class="contact-form-container">
        <div class="contact-header">
            <h3 style="margin: 0;"><i class="fas fa-paper-plane" style="color: var(--primary-color);"></i> Get in Touch</h3>
            <p class="text-muted" style="margin-top: 0.5rem; font-size: 0.9rem;">We'd love to hear from you. Send us a message.</p>
        </div>
        
        <div class="contact-body">
            <form id="contactForm">
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.85rem; text-transform: uppercase; color: #64748b;">Shop Name</label>
                    <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($shop_name) ?>" readonly style="background: #f8fafc; font-weight: 600; color: #334155;">
                </div>
                
                <div class="d-flex gap-2">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" style="font-size: 0.85rem; text-transform: uppercase; color: #64748b;">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($full_name) ?>" readonly style="background: #f8fafc; color: #334155;">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" style="font-size: 0.85rem; text-transform: uppercase; color: #64748b;">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" readonly style="background: #f8fafc; color: #334155;">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" placeholder="How can we help you?" required style="resize: none;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 1rem;">
                    Send Message
                </button>
            </form>
            <div id="responseMessage" style="margin-top: 1rem;"></div>
        </div>
    </div>

</div>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    btn.disabled = true;

    const formData = new FormData(this);

    fetch('process_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const msgDiv = document.getElementById('responseMessage');
        if (data.success) {
            msgDiv.innerHTML = `<div class="alert alert-success" style="background: #dcfce7; color: #166534; padding: 0.75rem; border-radius: var(--radius); border: 1px solid #bbf7d0; font-size: 0.9rem; text-align: center;"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
            this.reset();
        } else {
            msgDiv.innerHTML = `<div class="alert alert-danger" style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius); border: 1px solid #fecaca; font-size: 0.9rem; text-align: center;"><i class="fas fa-exclamation-circle"></i> ${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('responseMessage').innerHTML = `<div class="alert alert-danger">An error occurred. Please try again.</div>`;
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
