<?php namespace Rackage\Mail;

/**
 * PHPMailer - Native PHP mail() Function Wrapper
 *
 * Wraps PHP's native mail() function with security enhancements, proper header
 * formatting, and MIME support for attachments. This is the simplest mail
 * transport but has significant limitations in production environments.
 *
 * How mail() Works:
 *   PHP's mail() function passes emails to the server's Mail Transfer Agent (MTA),
 *   typically sendmail, postfix, or qmail. The MTA then handles actual delivery.
 *   This means mail() depends entirely on server configuration.
 *
 * Requirements:
 *   - Server must have an MTA installed and configured (sendmail, postfix, etc.)
 *   - php.ini must have correct sendmail_path setting
 *   - Server must allow outbound connections on port 25 (often blocked by ISPs)
 *   - Domain must have proper SPF/DKIM records for deliverability
 *
 * Advantages: 
 *   - Zero external dependencies (built into PHP)
 *   - Fast (minimal overhead, direct system call)
 *   - Simple (no authentication, no connection management)
 *   - Works immediately on properly configured servers
 *
 * Disadvantages:
 *   - Unreliable (depends on server MTA configuration)
 *   - No authentication (can't use Gmail, SendGrid, etc.)
 *   - No detailed error messages (mail() returns only true/false)
 *   - Port 25 often blocked by hosting providers
 *   - Emails often go to spam without proper server setup
 *   - No delivery confirmation or tracking
 *
 * When to Use:
 *   - Local development and testing
 *   - Simple internal notifications on dedicated servers
 *   - Legacy systems where SMTP isn't available
 *   - Low-volume applications on properly configured servers
 *
 * When NOT to Use:
 *   - Production applications with important emails
 *   - Shared hosting (often disabled or restricted)
 *   - When you need delivery confirmation
 *   - When emails must not go to spam
 *
 * Recommendation:
 *   Use SMTPMailer for production. PHPMailer is best for development/testing only.
 *
 * Security Features:
 *   - Header injection prevention (strips \r and \n from user input)
 *   - Email address validation
 *   - Safe MIME boundary generation
 *   - Proper content encoding
 *
 * Usage Example:
 *
 *   $mailer = new PHPMailer();
 *
 *   $result = $mailer->send(
 *       ['address' => 'from@example.com', 'name' => 'Sender'],
 *       ['user@example.com' => 'John Doe'],
 *       [],  // CC
 *       [],  // BCC
 *       'Welcome!',
 *       '<h1>Welcome</h1><p>Thanks for signing up.</p>',
 *       true,  // isHtml
 *       []     // attachments
 *   );
 *
 *   if (!$result) {
 *       echo "Failed: " . $mailer->getError();
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

class PHPMailer {

	/**
	 * Last error message from failed send attempt
	 * @var string|null
	 */
	private $lastError = null;

	/**
	 * Line ending for email headers (CRLF as per RFC 5322)
	 * @var string
	 */
	private $lineEnding = "\r\n";

	/**
	 * Maximum line length for email content (RFC 5322 recommends 78)
	 * @var int
	 */
	private $maxLineLength = 78;

	// ===========================================================================
	// PUBLIC API
	// ===========================================================================

	/**
	 * Send email via PHP's mail() function
	 *
	 * Composes and sends an email using PHP's native mail() function.
	 * Handles all recipient types (to, cc, bcc), HTML/plain text content,
	 * and MIME-encoded attachments.
	 *
	 * Process:
	 * 1. Validate all email addresses (prevent injection)
	 * 2. Build email headers (From, CC, BCC, MIME, etc.)
	 * 3. Prepare body content (plain text or HTML)
	 * 4. Encode attachments if present (Base64 MIME)
	 * 5. Call mail() function with prepared data
	 * 6. Return success/failure status
	 *
	 * Email Structure:
	 *   No attachments: Simple text or HTML message
	 *   With attachments: Multipart MIME message with boundaries
	 *
	 * Security:
	 *   All headers are sanitized to prevent injection attacks.
	 *   User-provided data is stripped of \r and \n characters.
	 *
	 * Parameters:
	 *   $from: ['address' => 'sender@example.com', 'name' => 'Sender Name']
	 *   $to: ['recipient@example.com' => 'Recipient Name', ...]
	 *   $cc: ['cc@example.com' => 'CC Name', ...] (optional)
	 *   $bcc: ['bcc@example.com' => 'BCC Name', ...] (optional)
	 *   $subject: Email subject line (string)
	 *   $body: Email body content (plain text or HTML)
	 *   $isHtml: Whether body is HTML (true) or plain text (false)
	 *   $attachments: [['path' => '/path/to/file', 'name' => 'filename.pdf'], ...]
	 *
	 * Return Value:
	 *   Returns true on success, false on failure.
	 *   On failure, call getError() to retrieve error message.
	 *
	 * mail() Function Signature:
	 *   bool mail(string $to, string $subject, string $message, string $headers)
	 *
	 * Example:
	 *   $mailer = new PHPMailer();
	 *   $sent = $mailer->send(
	 *       ['address' => 'from@example.com', 'name' => 'My App'],
	 *       ['user@example.com' => 'John Doe'],
	 *       [],
	 *       [],
	 *       'Welcome!',
	 *       'Thanks for joining us.',
	 *       false,
	 *       []
	 *   );
	 *
	 * @param array $from From address and name
	 * @param array $to To recipients (email => name)
	 * @param array $cc CC recipients (email => name)
	 * @param array $bcc BCC recipients (email => name)
	 * @param string $subject Email subject
	 * @param string $body Email body content
	 * @param bool $isHtml Whether body is HTML
	 * @param array $attachments File attachments
	 * @return bool True on success, false on failure
	 */
	public function send($from, $to, $replyTo, $cc, $bcc, $subject, $body, $isHtml, $attachments)
	{
		// Clear previous error
		$this->lastError = null;

		// Validate from address
		if (!$this->validateEmail($from['address'])) {
			$this->lastError = 'Invalid from address: ' . $from['address'];
			return false;
		}

		// Validate all recipient addresses
		foreach (array_keys($to) as $email) {
			if (!$this->validateEmail($email)) {
				$this->lastError = 'Invalid to address: ' . $email;
				return false;
			}
		}

		foreach (array_keys($cc) as $email) {
			if (!$this->validateEmail($email)) {
				$this->lastError = 'Invalid cc address: ' . $email;
				return false;
			}
		}

		foreach (array_keys($bcc) as $email) {
			if (!$this->validateEmail($email)) {
				$this->lastError = 'Invalid bcc address: ' . $email;
				return false;
			}
		}

		// Build recipient string for mail() function (To field only)
		$toAddresses = $this->formatRecipientList($to);

		// Sanitize subject (prevent header injection)
		$subject = $this->sanitizeHeader($subject);

		// Build email headers
		$headers = $this->buildHeaders($from, $cc, $bcc, $isHtml, $attachments);

		// Build email body (with attachments if present)
		$message = $this->buildBody($body, $isHtml, $attachments);

		// Send email via mail() function
		$sent = @mail($toAddresses, $subject, $message, $headers);

		if (!$sent) {
			$this->lastError = 'mail() function returned false. Check server MTA configuration.';
			return false;
		}

		return true;
	}

	/**
	 * Get last error message
	 *
	 * Returns the error message from the most recent failed send() attempt.
	 * Returns null if no error occurred.
	 *
	 * Usage:
	 *   if (!$mailer->send(...)) {
	 *       echo "Error: " . $mailer->getError();
	 *   }
	 *
	 * @return string|null Error message or null
	 */
	public function getError()
	{
		return $this->lastError;
	}

	// ===========================================================================
	// HEADER BUILDING
	// ===========================================================================

	/**
	 * Build email headers
	 *
	 * Constructs all email headers required for mail() function.
	 * Includes From, CC, BCC, Reply-To, MIME-Version, Content-Type, etc.
	 *
	 * Header Order (per RFC 5322):
	 * 1. From (required)
	 * 2. Reply-To (optional)
	 * 3. CC (optional)
	 * 4. BCC (optional)
	 * 5. MIME-Version (required for HTML/attachments)
	 * 6. Content-Type (determines plain text vs HTML vs multipart)
	 *
	 * Security:
	 *   All header values are sanitized to prevent injection attacks.
	 *   \r and \n characters are stripped from user input.
	 *
	 * MIME Multipart:
	 *   Used when email has attachments. Creates a unique boundary
	 *   to separate email body from attachments.
	 *
	 * @param array $from From address and name
	 * @param array $cc CC recipients
	 * @param array $bcc BCC recipients
	 * @param bool $isHtml Whether body is HTML
	 * @param array $attachments File attachments
	 * @return string Formatted headers for mail() function
	 */
	private function buildHeaders($from, $cc, $bcc, $isHtml, $attachments)
	{
		$headers = [];

		// From header (required)
		$headers[] = 'From: ' . $this->formatAddress($from['address'], $from['name']);

		// Reply-To header (same as From by default)
		$headers[] = 'Reply-To: ' . $this->formatAddress($from['address'], $from['name']);

		// CC header (if present)
		if (!empty($cc)) {
			$ccList = $this->formatRecipientList($cc);
			$headers[] = 'Cc: ' . $ccList;
		}

		// BCC header (if present)
		if (!empty($bcc)) {
			$bccList = $this->formatRecipientList($bcc);
			$headers[] = 'Bcc: ' . $bccList;
		}

		// X-Mailer header (identifies email client)
		$headers[] = 'X-Mailer: Rachie Framework';

		// MIME headers (required for HTML and attachments)
		$headers[] = 'MIME-Version: 1.0';

		// Content-Type depends on whether we have attachments
		if (!empty($attachments)) {
			// Multipart for attachments
			$boundary = $this->generateBoundary();
			$headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
		} else {
			// Simple text or HTML
			if ($isHtml) {
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
			} else {
				$headers[] = 'Content-Type: text/plain; charset=UTF-8';
			}
		}

		// Join headers with CRLF as per RFC 5322
		return implode($this->lineEnding, $headers);
	}

	// ===========================================================================
	// BODY BUILDING
	// ===========================================================================

	/**
	 * Build email body with optional attachments
	 *
	 * Constructs the email message body. For simple emails (no attachments),
	 * returns the body as-is. For emails with attachments, creates a
	 * MIME multipart message with proper boundaries.
	 *
	 * Simple Email Structure:
	 *   Just the body content (plain text or HTML)
	 *
	 * Multipart Email Structure (with attachments):
	 *   --boundary
	 *   Content-Type: text/html
	 *
	 *   [Body content here]
	 *
	 *   --boundary
	 *   Content-Type: application/octet-stream
	 *   Content-Transfer-Encoding: base64
	 *   Content-Disposition: attachment; filename="file.pdf"
	 *
	 *   [Base64 encoded file content]
	 *
	 *   --boundary--
	 *
	 * MIME Encoding:
	 *   Attachments are Base64 encoded and chunked into 76-character lines
	 *   as per RFC 2045. This ensures safe transmission through email systems.
	 *
	 * @param string $body Email body content
	 * @param bool $isHtml Whether body is HTML
	 * @param array $attachments File attachments
	 * @return string Complete email message body
	 */
	private function buildBody($body, $isHtml, $attachments)
	{
		// No attachments - return body as-is
		if (empty($attachments)) {
			return $body;
		}

		// Has attachments - build multipart MIME message
		$boundary = $this->generateBoundary();
		$message = [];

		// Part 1: Email body content
		$message[] = '--' . $boundary;
		$message[] = 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
		$message[] = 'Content-Transfer-Encoding: 8bit';
		$message[] = '';
		$message[] = $body;
		$message[] = '';

		// Part 2+: File attachments
		foreach ($attachments as $attachment) {
			$message[] = '--' . $boundary;
			$message[] = 'Content-Type: application/octet-stream; name="' . $attachment['name'] . '"';
			$message[] = 'Content-Transfer-Encoding: base64';
			$message[] = 'Content-Disposition: attachment; filename="' . $attachment['name'] . '"';
			$message[] = '';
			$message[] = chunk_split(base64_encode(file_get_contents($attachment['path'])), 76, $this->lineEnding);
			$message[] = '';
		}

		// Final boundary marker
		$message[] = '--' . $boundary . '--';

		return implode($this->lineEnding, $message);
	}

	// ===========================================================================
	// FORMATTING & VALIDATION
	// ===========================================================================

	/**
	 * Format email address with optional name
	 *
	 * Formats an email address in RFC 5322 format:
	 *   - With name: "John Doe" <john@example.com>
	 *   - Without name: john@example.com
	 *
	 * Name Encoding:
	 *   If name contains special characters, it's wrapped in quotes.
	 *   Email address is always in angle brackets when name is present.
	 *
	 * Security:
	 *   Both name and address are sanitized to prevent header injection.
	 *
	 * @param string $email Email address
	 * @param string $name Display name (optional)
	 * @return string Formatted email address
	 */
	private function formatAddress($email, $name = '')
	{
		// Sanitize both name and email
		$email = $this->sanitizeHeader($email);
		$name = $this->sanitizeHeader($name);

		// Return with or without name
		if (!empty($name)) {
			return '"' . $name . '" <' . $email . '>';
		}

		return $email;
	}

	/**
	 * Format recipient list as comma-separated string
	 *
	 * Converts an array of recipients into a comma-separated string
	 * suitable for mail() function or email headers.
	 *
	 * Input Format:
	 *   ['john@example.com' => 'John Doe', 'jane@example.com' => 'Jane Smith']
	 *
	 * Output Format:
	 *   "John Doe" <john@example.com>, "Jane Smith" <jane@example.com>
	 *
	 * @param array $recipients Array of email => name pairs
	 * @return string Comma-separated recipient list
	 */
	private function formatRecipientList($recipients)
	{
		$formatted = [];

		foreach ($recipients as $email => $name) {
			$formatted[] = $this->formatAddress($email, $name);
		}

		return implode(', ', $formatted);
	}

	/**
	 * Validate email address format
	 *
	 * Uses PHP's filter_var() with FILTER_VALIDATE_EMAIL to check
	 * if an email address is syntactically valid per RFC 5322.
	 *
	 * Validation Rules:
	 *   - Must have @ symbol
	 *   - Must have valid domain part
	 *   - Must not contain dangerous characters
	 *
	 * Security:
	 *   This prevents obviously malicious addresses from being used
	 *   in header injection attempts.
	 *
	 * Note:
	 *   This checks syntax only, not whether the address actually exists.
	 *
	 * @param string $email Email address to validate
	 * @return bool True if valid, false otherwise
	 */
	private function validateEmail($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * Sanitize header value to prevent injection attacks
	 *
	 * Strips carriage return (\r) and line feed (\n) characters from
	 * header values. This prevents header injection attacks where
	 * malicious users try to inject additional headers.
	 *
	 * Attack Example (prevented):
	 *   User input: "Subject\r\nBcc: attacker@evil.com"
	 *   After sanitization: "SubjectBcc: attacker@evil.com" (harmless)
	 *
	 * Security:
	 *   Email headers are separated by CRLF (\r\n). If user input contains
	 *   these characters, they could inject arbitrary headers (BCC to spam
	 *   recipients, change From address, etc.). This function prevents that.
	 *
	 * Characters Removed:
	 *   \r (carriage return, ASCII 13)
	 *   \n (line feed, ASCII 10)
	 *
	 * @param string $value Header value to sanitize
	 * @return string Sanitized value safe for use in headers
	 */
	private function sanitizeHeader($value)
	{
		return str_replace(["\r", "\n"], '', $value);
	}

	/**
	 * Generate unique MIME boundary string
	 *
	 * Creates a unique boundary string used to separate parts in
	 * multipart MIME messages (body vs attachments).
	 *
	 * Boundary Format:
	 *   ----=_Part_<timestamp>_<random>.<random>
	 *
	 * Requirements (per RFC 2046):
	 *   - Must be unique (won't appear in message content)
	 *   - Must be printable ASCII characters
	 *   - Should be difficult to guess
	 *   - Maximum 70 characters
	 *
	 * Example:
	 *   ----=_Part_1704067200_123456789.987654321
	 *
	 * Security:
	 *   Uses microtime and random values to ensure uniqueness.
	 *   Prevents boundary collision attacks.
	 *
	 * @return string Unique boundary string
	 */
	private function generateBoundary()
	{
		return '----=_Part_' . time() . '_' . mt_rand() . '.' . mt_rand();
	}

}
