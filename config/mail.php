<?php
/**
 * Mail Configuration
 * 
 * Configure email sending for your Rachie application.
 * Used for notifications, password resets, contact forms, etc.
 * 
 * Supported methods:
 *   - smtp:     Professional email via SMTP server (recommended)
 *   - sendmail: Unix/Linux sendmail (simple, works on most servers)
 *   - mail:     PHP's built-in mail() function (basic, unreliable)
 * 
 * Quick Start:
 *   For development: Use 'mail' method (no setup)
 *   For production:  Use 'smtp' with a proper email service
 * 
 * @category Configuration
 * @package  Rachie
 */

return array(

	// ===========================================================================
	// MAIL DRIVER
	// ===========================================================================
	
	/**
	 * Mail Driver/Method
	 * 
	 * How emails should be sent from your application.
	 * 
	 * Options:
	 *   'smtp'     - SMTP server (Gmail, SendGrid, Mailgun, etc.) - RECOMMENDED
	 *   'sendmail' - Server's sendmail program (Linux/Unix)
	 *   'mail'     - PHP mail() function (simple but unreliable)
	 * 
	 * Recommendation:
	 *   Development: 'mail' (quick testing)
	 *   Production:  'smtp' (reliable delivery)
	 */
	'driver' => 'smtp',

	// ===========================================================================
	// FROM ADDRESS
	// ===========================================================================
	
	/**
	 * Default "From" Email Address
	 * 
	 * The email address that appears as the sender.
	 * This should be a valid email address from your domain.
	 * 
	 * Examples:
	 *   'noreply@example.com'
	 *   'notifications@yourdomain.com'
	 *   'support@company.com'
	 * 
	 * Important: Use an address you control and can authenticate
	 */
	'from_email' => 'noreply@example.com',

	/**
	 * Default "From" Name
	 * 
	 * The name that appears as the sender.
	 * This is what users see in their inbox.
	 * 
	 * Examples:
	 *   'My Application'
	 *   'Company Name'
	 *   'Support Team'
	 */
	'from_name' => 'Rachie Application',

	/**
	 * Reply-To Email Address
	 * 
	 * Where replies to your emails should go.
	 * Can be different from the "from" address.
	 * 
	 * Leave empty to use from_email as reply-to.
	 * 
	 * Example: 'support@example.com'
	 */
	'reply_to' => '',

	// ===========================================================================
	// SMTP CONFIGURATION
	// ===========================================================================
	
	/**
	 * SMTP Server Settings
	 * 
	 * Configure SMTP server for reliable email delivery.
	 * Only used when driver is 'smtp'.
	 * 
	 * Popular SMTP Services:
	 * 
	 * Gmail:
	 *   host: smtp.gmail.com
	 *   port: 587 (TLS) or 465 (SSL)
	 *   encryption: tls or ssl
	 *   Note: Requires "App Password" if 2FA enabled
	 * 
	 * SendGrid:
	 *   host: smtp.sendgrid.net
	 *   port: 587
	 *   username: apikey
	 *   password: YOUR_SENDGRID_API_KEY
	 * 
	 * Mailgun:
	 *   host: smtp.mailgun.org
	 *   port: 587
	 *   username: YOUR_MAILGUN_SMTP_USERNAME
	 *   password: YOUR_MAILGUN_SMTP_PASSWORD
	 * 
	 * Amazon SES:
	 *   host: email-smtp.us-east-1.amazonaws.com (region-specific)
	 *   port: 587
	 *   username: YOUR_SES_SMTP_USERNAME
	 *   password: YOUR_SES_SMTP_PASSWORD
	 * 
	 * Mailtrap (Testing):
	 *   host: smtp.mailtrap.io
	 *   port: 2525
	 *   username: YOUR_MAILTRAP_USERNAME
	 *   password: YOUR_MAILTRAP_PASSWORD
	 */
	'smtp' => array(
		
		/**
		 * SMTP Host
		 * 
		 * The SMTP server address.
		 * 
		 * Examples:
		 *   'smtp.gmail.com'
		 *   'smtp.sendgrid.net'
		 *   'mail.yourdomain.com'
		 */
		'host' => 'smtp.gmail.com',

		/**
		 * SMTP Port
		 * 
		 * The port number for SMTP connection.
		 * 
		 * Common ports:
		 *   587 - STARTTLS (recommended, widely supported)
		 *   465 - SSL/TLS (older, but still used)
		 *   25  - Unencrypted (avoid, often blocked)
		 *   2525 - Alternative port (used by some providers)
		 */
		'port' => 587,

		/**
		 * SMTP Encryption
		 * 
		 * The encryption method to use.
		 * 
		 * Options:
		 *   'tls'  - STARTTLS (port 587) - RECOMMENDED
		 *   'ssl'  - SSL/TLS (port 465)
		 *   ''     - No encryption (NOT recommended)
		 * 
		 * Always use encryption in production!
		 */
		'encryption' => 'tls',

		/**
		 * SMTP Username
		 * 
		 * Your SMTP authentication username.
		 * Often your full email address.
		 * 
		 * Examples:
		 *   'your-email@gmail.com'
		 *   'apikey' (for SendGrid)
		 *   'your-smtp-username'
		 */
		'username' => 'your-email@gmail.com',

		/**
		 * SMTP Password
		 * 
		 * Your SMTP authentication password.
		 * 
		 * Security Notes:
		 *   - Never commit passwords to version control
		 *   - Use environment variables in production
		 *   - For Gmail: Use "App Password" if 2FA is enabled
		 *   - For APIs: Use API keys, not your account password
		 * 
		 * IMPORTANT: Add mail.php to .gitignore!
		 */
		'password' => 'your-password-here',

		/**
		 * SMTP Timeout (seconds)
		 * 
		 * How long to wait for SMTP server response.
		 * 
		 * Default: 30 seconds
		 * Increase if you have slow connections
		 */
		'timeout' => 30,

		/**
		 * SMTP Authentication
		 * 
		 * Whether to use SMTP authentication.
		 * 
		 * Set to FALSE only if your server doesn't require auth
		 * (rare, usually only for localhost testing)
		 */
		'auth' => true,

	),

	// ===========================================================================
	// SENDMAIL CONFIGURATION
	// ===========================================================================
	
	/**
	 * Sendmail Path
	 * 
	 * Path to the sendmail binary on your server.
	 * Only used when driver is 'sendmail'.
	 * 
	 * Common paths:
	 *   '/usr/sbin/sendmail -bs'
	 *   '/usr/lib/sendmail -bs'
	 * 
	 * Use 'which sendmail' in terminal to find your path.
	 */
	'sendmail_path' => '/usr/sbin/sendmail -bs',

	// ===========================================================================
	// EMAIL DEFAULTS
	// ===========================================================================
	
	/**
	 * Default Email Format
	 * 
	 * Whether to send HTML or plain text emails by default.
	 * Individual emails can override this.
	 * 
	 * Options:
	 *   'html' - HTML emails (styled, with images) - RECOMMENDED
	 *   'text' - Plain text emails (simple, always work)
	 */
	'format' => 'html',

	/**
	 * Character Set
	 * 
	 * Character encoding for emails.
	 * 
	 * Default: 'utf-8' (supports all languages, emojis)
	 * Keep as utf-8 unless you have a specific reason to change
	 */
	'charset' => 'utf-8',

	/**
	 * Email Priority
	 * 
	 * Default priority level for emails.
	 * 
	 * Options:
	 *   1 - High priority (appears urgent in inbox)
	 *   3 - Normal priority
	 *   5 - Low priority
	 */
	'priority' => 3,

	// ===========================================================================
	// TESTING & DEBUGGING
	// ===========================================================================
	
	/**
	 * Log Emails
	 * 
	 * Whether to log all sent emails (useful for debugging).
	 * 
	 * When TRUE:
	 *   - Email details are logged to vault/logs/email.log
	 *   - Helps troubleshoot delivery issues
	 *   - Track sent emails
	 * 
	 * When FALSE:
	 *   - No email logging
	 *   - Slightly better performance
	 * 
	 * Recommended: TRUE in development, FALSE in production (or use log rotation)
	 */
	'log_emails' => true,

	/**
	 * Test Mode
	 * 
	 * When enabled, emails are not actually sent.
	 * Instead, they're saved to vault/logs/email-test.log
	 * 
	 * Perfect for:
	 *   - Development/testing
	 *   - Preventing accidental emails
	 *   - Testing email templates
	 * 
	 * IMPORTANT: Set to FALSE in production!
	 */
	'test_mode' => false,

);

/**
 * GMAIL SETUP GUIDE:
 * 
 * If using Gmail SMTP:
 * 
 * 1. Enable 2-Factor Authentication on your Google account
 * 2. Generate an "App Password":
 *    - Go to: https://myaccount.google.com/apppasswords
 *    - Select "Mail" and your device
 *    - Copy the generated 16-character password
 * 3. Use in config:
 *    'username' => 'your-email@gmail.com'
 *    'password' => 'your-16-char-app-password'
 * 
 * Note: Gmail has sending limits (500 emails/day for free accounts)
 */

/**
 * PRODUCTION BEST PRACTICES:
 * 
 * 1. Use a dedicated email service (SendGrid, Mailgun, Amazon SES)
 *    - Better deliverability
 *    - Analytics and tracking
 *    - Higher sending limits
 *    - Professional features
 * 
 * 2. Never commit passwords to Git
 *    - Add mail.php to .gitignore
 *    - Use environment variables
 *    - Use mail.php.example for templates
 * 
 * 3. Set up SPF, DKIM, and DMARC records
 *    - Improves email deliverability
 *    - Prevents emails going to spam
 *    - Your email provider will guide you
 * 
 * 4. Monitor email sending
 *    - Track bounces and failures
 *    - Watch for spam complaints
 *    - Keep logs for troubleshooting
 * 
 * 5. Use meaningful from addresses
 *    - Not generic like admin@localhost
 *    - Use your actual domain
 *    - Consider dedicated addresses per email type
 *      (e.g., notifications@, support@, noreply@)
 */

/**
 * TROUBLESHOOTING:
 * 
 * Emails not sending?
 * 
 * 1. Check credentials are correct
 * 2. Verify SMTP host and port
 * 3. Ensure firewall allows outbound connections on SMTP port
 * 4. Check PHP has required extensions (openssl for TLS)
 * 5. Look at error logs (vault/logs/error.log)
 * 6. Try test_mode = true to see if email is generated correctly
 * 7. Use Mailtrap.io for safe testing
 * 
 * Emails going to spam?
 * 
 * 1. Set up SPF/DKIM/DMARC records
 * 2. Use a verified "from" address
 * 3. Avoid spam trigger words
 * 4. Include unsubscribe link
 * 5. Use a reputable SMTP service
 * 6. Warm up your sending domain gradually
 */