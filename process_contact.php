<?php
// process_contact.php
require_once 'config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Ensure User is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to send a message.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$shop_name = $_POST['shop_name'] ?? '';
$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';
$message = trim($_POST['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a message.']);
    exit;
}

try {
    // 1. Ensure Table Exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        shop_name VARCHAR(150),
        name VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(150),
        message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 1.5 Ensure 'email' column exists (Left for backward compatibility, but not used now)
    // try { $pdo->query("SELECT email FROM contact_messages LIMIT 1"); } catch (Exception $e) { ... }

    // 2. Insert Message into Database
    $stmt = $pdo->prepare("INSERT INTO contact_messages (user_id, shop_name, name, phone, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $shop_name, $name, $phone, $message]);

    // 3. SMS API Integration (Placeholder)
    // To send SMS, you need an API Key from a provider like Fast2SMS, Twilio, or MSG91.
    // Example using Fast2SMS (Indian Provider):
    /*
    $apiKey = "YOUR_API_KEY_HERE";
    $numbers = array(9601838590, 9327277459); // Team numbers
    $sender = "TXTIND";
    $sms_message = urlencode("New Inquiry from $shop_name ($name): $message");
    
    foreach ($numbers as $number) {
        $url = "https://www.fast2sms.com/dev/bulkV2?authorization=$apiKey&route=v3&sender_id=$sender&message=$sms_message&language=english&flash=0&numbers=$number";
        // file_get_contents($url); // Uncomment to send
    }
    */

    // Attempt to Send Email (Internal Notification)
    $to = "dhruvkhirsariya@example.com, abikyada@example.com"; 
    $subject = "New Support Query from $shop_name";
    $email_body = "You have received a new message.\n\n" .
                  "Shop: $shop_name\n" .
                  "Name: $name\n" .
                  "Phone: $phone\n\n" .
                  "Message:\n$message\n\n" .
                  "Date: " . date('Y-m-d H:i:s');
    
    $headers = "From: no-reply@pos-system.com";

    // Suppress errors for mail() as local server might not have SMTP configured
    @mail($to, $subject, $email_body, $headers);

    echo json_encode(['success' => true, 'message' => 'Message sent successfully! Our team will contact you soon.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
