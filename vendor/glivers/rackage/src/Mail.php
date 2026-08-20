<?php namespace Rackage;

use Rackage\Mail\PHPMailer;
use Rackage\Mail\SendMailer;
use Rackage\Mail\SMTPMailer;

/**
 * Mail Helper
 *
 * Provides email sending functionality for Rachie applications with support for
 * multiple drivers (SMTP, Sendmail, PHP mail()). Uses a chainable builder pattern
 * for composing emails and supports both plain text and HTML messages.
 *
 * This is part of the Rackage helper library and provides native SMTP, Sendmail,
 * and PHP mail() implementations. Configuration is loaded from config/mail.php.
 *
 * Supported Drivers:
 *   - smtp:     Native SMTP implementation with TLS/SSL and authentication
 *   - sendmail: Uses server's sendmail binary (good for Linux servers)
 *   - mail:     PHP's native mail() function (simple but limited)
 *
 * Common Use Cases:
 *   - User registration confirmation emails
 *   - Password reset links
 *   - Order confirmations and receipts
 *   - Notification emails
 *   - Newsletter sending
 *   - Contact form submissions
 *
 * Chainable Builder Pattern:
 *   Methods return $this for chaining, making email composition fluent and readable.
 *   Always call send() as the final method to actually send the email.
 *
 * Email Template Support (Rachie View Templates):
 *   Use template() to render email content from Rachie view templates with dynamic
 *   merge fields. Templates are standard Rachie view files (application/views/emails/)
 *   that support the full Rachie template syntax with {{ }} merge fields for
 *   personalizing email content. All Rachie view helpers (Url, Html, Date, etc.)
 *   are available in email templates for generating dynamic links, formatting dates,
 *   and escaping content.
 *
 * Static vs Instance:
 *   Unlike other Rackage helpers, Mail uses instances (not pure static) because each
 *   email is a separate object with its own recipients, subject, body, etc.
 *   Use Mail::to() to start building a new email.
 *
 * Configuration (config/mail.php):
 *   return [
 *       'driver' => 'smtp',
 *       'from' => ['address' => 'noreply@example.com', 'name' => 'My App'],
 *       'smtp' => [
 *           'host' => 'smtp.mailtrap.io',
 *           'port' => 2525,
 *           'username' => 'your-username',
 *           'password' => 'your-password',
 *           'encryption' => 'tls',  // tls, ssl, or null
 *       ],
 *   ];
 *
 * Usage Examples:
 *
 *   // Simple text email
 *   Mail::to('user@example.com')
 *       ->subject('Welcome!')
 *       ->body('Thanks for signing up.')
 *       ->send();
 *
 *   // HTML email with Rachie template (dynamic merge fields)
 *   Mail::to($user['email'])
 *       ->subject('Password Reset Request')
 *       ->template('emails/password-reset', ['token' => $token, 'user' => $user])
 *       ->send();
 *
 *   // Email with attachments
 *   Mail::to('admin@example.com')
 *       ->subject('Monthly Report')
 *       ->attach(Path::vault() . 'reports/january.pdf')
 *       ->attach(Path::vault() . 'reports/summary.xlsx')
 *       ->template('emails/report', ['month' => 'January'])
 *       ->send();
 *
 *   // Multiple recipients with CC/BCC
 *   Mail::to(['user1@example.com', 'user2@example.com'])
 *       ->cc('manager@example.com')
 *       ->bcc('archive@example.com')
 *       ->subject('Team Update')
 *       ->body('Here is the latest team update...')
 *       ->send();
 *
 *   // Custom from address
 *   Mail::from('support@example.com', 'Support Team')
 *       ->to('user@example.com')
 *       ->subject('Your Support Ticket')
 *       ->template('emails/support-ticket', ['ticket' => $ticket])
 *       ->send();
 *
 *   // Check if sent successfully
 *   $sent = Mail::to('user@example.com')
 *       ->subject('Test')
 *       ->body('Test message')
 *       ->send();
 *
 *   if ($sent) {
 *       Session::flash('success', 'Email sent successfully!');
 *   } else {
 *       Session::flash('error', 'Failed to send email: ' . Mail::error());
 *   }
 *
 * @author Geoffrey Okongo <code@rachie.dev>
 * @copyright 2015 - 2030 Geoffrey Okongo
 * @category Rackage
 * @package Rackage\Mail
 * @link https://github.com/glivers/rackage
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 2.0.1
 */

class Mail {

	/**
	 * Mail configuration from config/mail.php
	 * @var array
	 */
	private static $config = [];

	/**
	 * Last error message from failed send attempt
	 * @var string|null
	 */
	private static $lastError = null;

	/**
	 * Recipient email addresses
	 * Format: ['email@example.com' => 'Name', 'another@example.com' => '']
	 * @var array
	 */
	private $to = [];

	/**
	 * CC (Carbon Copy) email addresses
	 * Format: ['email@example.com' => 'Name', ...]
	 * @var array
	 */
	private $cc = [];

	/**
	 * BCC (Blind Carbon Copy) email addresses
	 * Format: ['email@example.com' => 'Name', ...]
	 * @var array
	 */
	private $bcc = [];

	/**
	 * Sender email address and name
	 * Format: ['address' => 'from@example.com', 'name' => 'Sender Name']
	 * @var array
	 */
	private $from = [];

	/**
	 * Custom headers to add to the email
	 * Format: 'Message-ID', '<unique-id@yourdomain.com>'
	 * @var array
	 */
	private $headers = [];

	/**
	 * Reply-To email address and name
	 * Format: ['address' => 'reply@example.com', 'name' => 'Reply Name']
	 * @var array
	 */
	private $replyTo = [];

	/**
	 * Email subject line
	 * @var string
	 */
	private $subject = '';

	/**
	 * Email body content (plain text or HTML)
	 * @var string
	 */
	private $body = '';

	/**
	 * Whether body content is HTML (true) or plain text (false)
	 * @var bool
	 */
	private $isHtml = false;

	/**
	 * File attachments
	 * Format: [['path' => '/path/to/file.pdf', 'name' => 'file.pdf'], ...]
	 * @var array
	 */
	private $attachments = [];

	/**
	 * Initialize mail configuration
	 *
	 * Loads mail configuration from Registry on first use.
	 * Called automatically by static methods.
	 *
	 * @return void
	 */
	public static function config($config = null)
	{
		$instance 	= isset($this) ? $this : new self();

		if($config) self::$config 	= $config;
		if (empty(self::$config)) self::$config = Registry::mailConfig();

		return $instance;
	}

	// ===========================================================================
	// STATIC FACTORY METHODS (Start Email Composition)
	// ===========================================================================

	/**
	 * Create new mail instance with recipient
	 *
	 * This is the primary way to start composing an email. Returns a new
	 * Mail instance with the recipient(s) already set.
	 *
	 * Process:
	 * 1. Initialize configuration if needed
	 * 2. Create new Mail instance
	 * 3. Set default from address from config
	 * 4. Add recipient(s)
	 * 5. Return instance for chaining
	 *
	 * Recipient Format:
	 *   String: 'user@example.com' or 'User Name <user@example.com>'
	 *   Array: ['user1@example.com', 'user2@example.com']
	 *   Associative: ['user@example.com' => 'User Name']
	 *
	 * Usage:
	 *   Mail::to('user@example.com')->subject('Hi')->body('Hello')->send();
	 *   Mail::to(['user1@example.com', 'user2@example.com'])->subject('Update')->send();
	 *   Mail::to(['user@example.com' => 'John Doe'])->subject('Welcome')->send();
	 *
	 * @param string|array $address Email address(es)
	 * @param string $name Recipient name (only if $address is string)
	 * @return Mail Mail instance for chaining
	 */
	public static function to($address, $name = '')
	{
		$instance 	= isset($this) ? $this : self::config();

		// Set default 'from' address from config
		if (!empty(self::$config['from_email'])) {
			$instance->from = [
				'address' => self::$config['from_email'],
				'name' => self::$config['from_name'] ?? ''
			];
		}

		// Add recipient(s)
		$instance->addRecipient($address, $name, 'to');

		return $instance;
	}

	/**
	 * Create new mail instance with custom from address
	 *
	 * Use this when you need to override the default from address from config.
	 * Common use case: department-specific emails (support@, sales@, etc.)
	 *
	 * Usage:
	 *   Mail::from('support@example.com', 'Support Team')
	 *       ->to('user@example.com')
	 *       ->subject('Your Ticket')
	 *       ->send();
	 *
	 * @param string $address From email address
	 * @param string $name From name (optional)
	 * @return Mail Mail instance for chaining
	 */
	public static function from($address, $name = '')
	{
		$instance 	= isset($this) ? $this : self::config();

		$instance->from = ['address' => $address, 'name' => $name];

		return $instance;
	}

	/**
	 * Add custom headers to the email body for tracking purposes
	 * 
	 * @param string $name The name of the header, e.g. Message-ID
	 * @param string $value The value of the header, e.g. <123344545@domain.com>
	 */
	public function header($name, $value = true)
	{
		$this->headers[$name]	= $value;

		return $this;
	}

	/**
	 * Add a custom message ID to the body of the email
	 * 
	 * @param string $value
	 */
	public function messageId($value)
	{
		if(!$value) return $this;

		$this->headers['Message-ID'] = $value;
	}

	/**
	 * Get last error message
	 *
	 * Returns the error message from the most recent failed send() attempt.
	 * Useful for debugging or displaying user-friendly error messages.
	 *
	 * Usage:
	 *   if (!$sent) {
	 *       echo "Error: " . Mail::error();
	 *       Log::error('Mail failed: ' . Mail::error());
	 *   }
	 *
	 * @return string|null Error message or null if no error
	 */
	public static function error()
	{
		return self::$lastError;
	}

	// ===========================================================================
	// RECIPIENT METHODS (Chainable)
	// ===========================================================================

	/**
	 * Add CC (Carbon Copy) recipient
	 *
	 * CC recipients receive a copy of the email and can see each other's addresses.
	 * Use for keeping stakeholders informed.
	 *
	 * Recipient Format:
	 *   String: 'user@example.com' or 'User Name <user@example.com>'
	 *   Array: ['user1@example.com', 'user2@example.com']
	 *   Associative: ['user@example.com' => 'User Name']
	 *
	 * Usage:
	 *   Mail::to('customer@example.com')
	 *       ->cc('manager@example.com')
	 *       ->cc(['sales@example.com', 'support@example.com'])
	 *       ->subject('Order Confirmation')
	 *       ->send();
	 *
	 * @param string|array $address Email address(es)
	 * @param string $name Recipient name (only if $address is string)
	 * @return $this For method chaining
	 */
	public function cc($address, $name = '')
	{
		$this->addRecipient($address, $name, 'cc');

		return $this;
	}

	/**
	 * Add BCC (Blind Carbon Copy) recipient
	 *
	 * BCC recipients receive a copy but are hidden from other recipients.
	 * Use for privacy or archiving emails without revealing recipient list.
	 *
	 * Recipient Format:
	 *   String: 'user@example.com' or 'User Name <user@example.com>'
	 *   Array: ['user1@example.com', 'user2@example.com']
	 *   Associative: ['user@example.com' => 'User Name']
	 *
	 * Usage:
	 *   Mail::to('customer@example.com')
	 *       ->bcc('archive@example.com')  // Keep copy without customer knowing
	 *       ->subject('Receipt')
	 *       ->send();
	 *
	 * @param string|array $address Email address(es)
	 * @param string $name Recipient name (only if $address is string)
	 * @return $this For method chaining
	 */
	public function bcc($address, $name = '')
	{
		$this->addRecipient($address, $name, 'bcc');

		return $this;
	}
 
	/**
	 * Set reply-to address
	 *
	 * When recipients click "Reply", their email client will use this address
	 * instead of the from address. Useful when from is noreply@.
	 *
	 * Usage:
	 *   Mail::to('user@example.com')
	 *       ->from('noreply@example.com', 'My App')
	 *       ->replyTo('support@example.com', 'Support Team')
	 *       ->subject('Welcome')
	 *       ->send();
	 *
	 * @param string $address Reply-to email address
	 * @param string $name Reply-to name (optional)
	 * @return $this For method chaining
	 */
	public function replyTo($address, $name = '')
	{
		$this->replyTo = ['address' => $address, 'name' => $name];

		return $this;
	}

	// ===========================================================================
	// CONTENT METHODS (Chainable)
	// ===========================================================================

	/**
	 * Set email subject
	 *
	 * Sets the subject line for the email.
	 *
	 * Subject Line Best Practices:
	 *   - Keep under 50 characters for mobile
	 *   - Be specific and actionable
	 *   - Avoid spam trigger words (FREE, !!!, URGENT)
	 *   - Personalize when possible
	 *
	 * Usage:
	 *   Mail::to('user@example.com')
	 *       ->subject('Welcome to ' . Registry::settings()['title'])
	 *       ->body('Thanks for joining!')
	 *       ->send();
	 *
	 * @param string $subject Email subject line
	 * @return $this For method chaining
	 */
	public function subject($subject)
	{
		$this->subject = $subject;

		return $this;
	}

	/**
	 * Set email body (plain text or HTML)
	 *
	 * Sets the email body content. Can be plain text or HTML.
	 * For HTML, this method automatically detects HTML tags and sets isHtml flag.
	 *
	 * Process:
	 * 1. Store body content
	 * 2. Auto-detect HTML (checks for <html>, <body>, <p>, <br> tags)
	 * 3. Set isHtml flag accordingly
	 *
	 * Usage:
	 *   // Plain text
	 *   Mail::to('user@example.com')
	 *       ->subject('Alert')
	 *       ->body('Your password was changed.')
	 *       ->send();
	 *
	 *   // HTML
	 *   Mail::to('user@example.com')
	 *       ->subject('Welcome')
	 *       ->body('<h1>Welcome!</h1><p>Thanks for signing up.</p>')
	 *       ->send();
	 *
	 * Note: For complex HTML emails, use view() instead.
	 *
	 * @param string $body Email body content
	 * @return $this For method chaining
	 */
	public function body($body)
	{
		$this->body = $body;

		// Auto-detect HTML content
		$this->isHtml = $this->isHtmlContent($body);

		return $this;
	}

	/**
	 * Set email body from template
	 *
	 * Renders a view template and uses it as the email body.
	 * All view helpers (Url, Html, Date, etc.) are available in templates.
	 * Automatically detects if rendered content is HTML.
	 *
	 * Process:
	 * 1. Render view template with provided data
	 * 2. Capture rendered output as body
	 * 3. Auto-detect HTML from rendered content
	 *
	 * Template Example (application/views/emails/welcome.php):
	 *   <h1>Welcome, {{ $user['name'] }}!</h1>
	 *   <p>Thanks for joining {{ Registry::settings()['title'] }}.</p>
	 *   <p><a href="{{ Url::link('verify', $token) }}">Verify Email</a></p>
	 *
	 * Usage:
	 *   Mail::to($user['email'])
	 *       ->subject('Welcome!')
	 *       ->template('emails/welcome', ['user' => $user])
	 *       ->send();
	 *
	 *   Mail::to($user['email'])
	 *       ->subject('Password Reset')
	 *       ->template('emails/password-reset', [
	 *           'token' => $token,
	 *           'expiry' => Date::add(Date::now(), '1 hour', 'H:i')
	 *       ])
	 *       ->send();
	 *
	 * @param string $template Template name (e.g., 'emails/welcome')
	 * @param array $data Data to pass to template
	 * @return $this For method chaining
	 */
	public function template($template, $data = [])
	{
		// Render view template
		$rendered = View::get($template, $data);

		// Set as body and auto-detect HTML
		$this->body = $rendered;
		$this->isHtml = $this->isHtmlContent($rendered);

		return $this;
	}

	// ===========================================================================
	// ATTACHMENT METHODS (Chainable)
	// ===========================================================================

	/**
	 * Attach file to email
	 *
	 * Adds a file attachment to the email. Can be called multiple times
	 * to attach multiple files.
	 *
	 * Process:
	 * 1. Validate file exists
	 * 2. Extract filename from path (or use custom name)
	 * 3. Add to attachments array
	 *
	 * Attachment Name:
	 *   If $name is provided, file will appear with that name in email.
	 *   Otherwise, uses the filename from the path.
	 *
	 * Usage:
	 *   // Single attachment
	 *   Mail::to('user@example.com')
	 *       ->subject('Invoice')
	 *       ->attach(Path::vault() . 'invoices/invoice-123.pdf')
	 *       ->send();
	 *
	 *   // Multiple attachments
	 *   Mail::to('admin@example.com')
	 *       ->subject('Reports')
	 *       ->attach(Path::vault() . 'reports/sales.pdf')
	 *       ->attach(Path::vault() . 'reports/revenue.xlsx')
	 *       ->attach(Path::vault() . 'reports/summary.docx')
	 *       ->send();
	 *
	 *   // Custom attachment name
	 *   Mail::to('user@example.com')
	 *       ->attach('/var/reports/jan.pdf', 'January Report.pdf')
	 *       ->send();
	 *
	 * @param string $path Absolute path to file
	 * @param string $name Custom filename (optional, defaults to basename)
	 * @return $this For method chaining
	 * @throws \InvalidArgumentException If file doesn't exist
	 */
	public function attach($path, $name = '')
	{
		// Validate file exists
		if (!file_exists($path)) {
			throw new \InvalidArgumentException("Attachment file not found: {$path}");
		}

		// Use basename if no custom name provided
		if (empty($name)) {
			$name = basename($path);
		}

		// Add to attachments array
		$this->attachments[] = [
			'path' => $path,
			'name' => $name,
		];

		return $this;
	}

	// ===========================================================================
	// SEND METHOD (Execute Email)
	// ===========================================================================

	/**
	 * Send the email
	 *
	 * Validates email parameters and sends using the configured driver.
	 * Returns true on success, false on failure. Error details available
	 * via Mail::error().
	 *
	 * Process:
	 * 1. Validate required fields (to, from, subject, body)
	 * 2. Determine driver from config
	 * 3. Call appropriate send method (SMTP, sendmail, or mail)
	 * 4. Return success/failure status
	 *
	 * Validation Rules:
	 *   - At least one recipient (to, cc, or bcc)
	 *   - From address set
	 *   - Subject not empty
	 *   - Body not empty
	 *
	 * Usage:
	 *   $sent = Mail::to('user@example.com')
	 *       ->subject('Test')
	 *       ->body('Test message')
	 *       ->send();
	 *
	 *   if ($sent) {
	 *       Session::flash('success', 'Email sent!');
	 *   } else {
	 *       Session::flash('error', 'Failed: ' . Mail::error());
	 *       Log::error('Mail error: ' . Mail::error());
	 *   }
	 *
	 * @return bool True on success, false on failure
	 */
	public function send()
	{
		// Clear previous error
		self::$lastError = null;

		// Validate required fields
		if (!$this->validate()) {
			return false;
		}

		// Get driver from config
		$driver = self::$config['driver'] ?? 'mail';

		// Send using appropriate driver
		try {
			switch ($driver) {
				case 'smtp':
					return $this->sendViaSmtp();

				case 'sendmail':
					return $this->sendViaSendmail();

				case 'mail':
				default:
					return $this->sendViaMail();
			}
		} catch (\Throwable $e) {

			self::$lastError = $e->getMessage();
			return false;
		}
	}

	// ===========================================================================
	// INTERNAL HELPERS
	// ===========================================================================

	/**
	 * Add recipient to appropriate list (internal helper)
	 *
	 * Normalizes various recipient input formats into a consistent
	 * internal format: ['email@example.com' => 'Name', ...]
	 *
	 * Supported Formats:
	 *   String: 'user@example.com'
	 *   String with name: 'User Name <user@example.com>'
	 *   Array: ['user1@example.com', 'user2@example.com']
	 *   Associative: ['user@example.com' => 'User Name']
	 *
	 * @param string|array $address Email address(es)
	 * @param string $name Recipient name (only for string addresses)
	 * @param string $type Recipient type ('to', 'cc', 'bcc')
	 * @return void
	 */
	private function addRecipient($address, $name, $type)
	{
		$recipients = [];

		if (is_array($address)) {
			// Array of addresses
			foreach ($address as $key => $value) {
				if (is_numeric($key)) {
					// Numeric key: ['user@example.com']
					$email = $this->extractEmail($value);
					$recipientName = $this->extractName($value);
					$recipients[$email] = $recipientName;
				} else {
					// Associative: ['user@example.com' => 'Name']
					$recipients[$key] = $value;
				}
			}
		} else {
			// Single string address
			$email = $this->extractEmail($address);
			$recipientName = !empty($name) ? $name : $this->extractName($address);
			$recipients[$email] = $recipientName;
		}

		// Add to appropriate recipient list
		$this->{$type} = array_merge($this->{$type}, $recipients);
	}

	/**
	 * Extract email address from string
	 *
	 * Handles both plain addresses and "Name <email@example.com>" format.
	 *
	 * Examples:
	 *   'user@example.com' → 'user@example.com'
	 *   'John Doe <john@example.com>' → 'john@example.com'
	 *
	 * @param string $address Email string
	 * @return string Email address
	 */
	private function extractEmail($address)
	{
		if (preg_match('/<(.+?)>/', $address, $matches)) {
			return trim($matches[1]);
		}

		return trim($address);
	}

	/**
	 * Extract name from email string
	 *
	 * Handles "Name <email@example.com>" format.
	 *
	 * Examples:
	 *   'user@example.com' → ''
	 *   'John Doe <john@example.com>' → 'John Doe'
	 *
	 * @param string $address Email string
	 * @return string Name or empty string
	 */
	private function extractName($address)
	{
		if (preg_match('/^(.+?)\s*</', $address, $matches)) {
			return trim($matches[1]);
		}

		return '';
	}

	/**
	 * Check if content is HTML
	 *
	 * Detects common HTML tags to determine if content should be
	 * sent as HTML or plain text.
	 *
	 * @param string $content Content to check
	 * @return bool True if content appears to be HTML
	 */
	private function isHtmlContent($content)
	{
		return preg_match('/<(html|body|p|br|div|h[1-6]|table|ul|ol|li|a|img|strong|em|span)[\s>]/i', $content) === 1;
	}

	/**
	 * Validate email parameters
	 *
	 * Ensures all required fields are set before sending.
	 * Sets error message if validation fails.
	 *
	 * @return bool True if valid, false otherwise
	 */
	private function validate()
	{
		// Check recipients
		if (empty($this->to) && empty($this->cc) && empty($this->bcc)) {
			self::$lastError = 'At least one recipient (to, cc, or bcc) is required';
			return false;
		}

		// Check from address
		if (empty($this->from['address'])) {
			self::$lastError = 'From address is required';
			return false;
		}

		// Check subject
		if (empty($this->subject)) {
			self::$lastError = 'Subject is required';
			return false;
		}

		// Check body
		if (empty($this->body)) {
			self::$lastError = 'Body is required';
			return false;
		}

		return true;
	}

	/**
	 * Send email via PHP's mail() function
	 *
	 * Uses native PHP mail() function. Simple but limited.
	 * Requires server to have sendmail or equivalent configured.
	 *
	 * @return bool True on success, false on failure
	 */
	private function sendViaMail()
	{
		$mailer = new PHPMailer();

		$sent = $mailer->send(
			$this->from,
			$this->to,
			$this->replyTo,
			$this->cc,
			$this->bcc,
			$this->subject,
			$this->body,
			$this->isHtml,
			$this->attachments
		);

		if (!$sent) {
			self::$lastError = $mailer->getError();
		}

		return $sent;
	}

	/**
	 * Send email via Sendmail
	 *
	 * Uses server's sendmail binary directly.
	 * Good for Linux servers with sendmail installed.
	 *
	 * @return bool True on success, false on failure
	 */
	private function sendViaSendmail()
	{
		$sendmailPath = self::$config['sendmail_path'] ?? '/usr/sbin/sendmail -bs';
		$mailer = new SendMailer($sendmailPath);

		$sent = $mailer->send(
			$this->from,
			$this->to,
			$this->replyTo,
			$this->cc,
			$this->bcc,
			$this->subject,
			$this->body,
			$this->isHtml,
			$this->attachments
		);

		if (!$sent) {
			self::$lastError = $mailer->getError();
		}

		return $sent;
	}

	/**
	 * Send email via SMTP
	 *
	 * Uses native SMTP implementation with authentication.
	 * Supports TLS/SSL encryption and multiple authentication methods.
	 *
	 * @return bool True on success, false on failure
	 */
	private function sendViaSmtp()
	{
		$mailer = new SMTPMailer(self::$config['smtp'] ?? []);

		$sent = $mailer->send(
			$this->from,
			$this->to,
			$this->headers,
			$this->replyTo,
			$this->cc,
			$this->bcc,
			$this->subject,
			$this->body,
			$this->isHtml,
			$this->attachments 
		);

		if (!$sent) {
			self::$lastError = $mailer->getError();
		}

		return $sent;
	}

	/**
	 * Format email address with optional name
	 *
	 * Formats email address in "Name <email@example.com>" format.
	 * If no name provided, returns just the email address.
	 *
	 * @param string $email Email address
	 * @param string $name Name (optional)
	 * @return string Formatted address
	 */
	private function formatAddress($email, $name = '')
	{
		if (!empty($name)) {
			return "{$name} <{$email}>";
		}

		return $email;
	}

}
