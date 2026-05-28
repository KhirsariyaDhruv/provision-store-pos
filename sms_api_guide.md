# How to Integate SMS API

To receive SMS notifications on your phone when someone contacts you, you need to sign up for an SMS Service Provider.

## Recommended Providers (India)
1. **Fast2SMS** (Easy, offers free credit for testing)
2. **MSG91** (Professional, widely used)
3. **TextLocal**

## Step-by-Step Process (Example: Fast2SMS)

1.  **Sign Up**: Go to [fast2sms.com](https://www.fast2sms.com) and create an account.
2.  **Get API Key**:
    -   Login to your dashboard.
    -   Go to **Dev API** section.
    -   Copy your **Authorization Key**.
3.  **Integrate**:
    -   Open `process_contact.php` in your code.
    -   Find the section `// 3. SMS API Integration`.
    -   Paste your key in `$apiKey = "YOUR_KEY_HERE";`.
    -   Uncomment the code (remove `/*` and `*/`).

## Cost
-   Most providers charge per SMS (approx ₹0.20 - ₹0.50).
-   You will need to recharge your SMS wallet on their website.
