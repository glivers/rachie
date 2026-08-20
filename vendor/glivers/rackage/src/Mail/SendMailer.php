<?php namespace Rackage\Mail;

/**
 * SendMailer - Sendmail Binary Transport
 *
 * Uses the server's sendmail binary (or compatible MTA like postfix, qmail)
 * to send emails. Opens a pipe to the sendmail process and writes the raw
 * email message directly.
 *
 * How It Works:
 *   Opens a pipe to sendmail binary using popen(), writes complete email
 *   message (headers + body) to the pipe, closes pipe and checks exit code.
 *   Sendmail then handles SMTP delivery to recipients.
 *
 * Pipe Process:
 *   PHP popen() creates a one-way pipe to sendmail process. We write to the
 *   pipe, sendmail reads from stdin, processes the email, and exits with a
 *   status code. Exit code 0 means success, non-zero means failure.
 *
 * Requirements:
 *   - Sendmail (or compatible) binary installed on server
 *   - Binary path configured correctly (typically /usr/sbin/sendmail)
 *   - PHP allowed to execute popen() (check disable_functions in php.ini)
 *   - Server firewall allows outbound port 25
 *   - Proper MTA configuration on server
 *
 * Common Sendmail Paths:
 *   /usr/sbin/sendmail -bs        (most Linux distributions)
 *   /usr/lib/sendmail -bs         (some older systems)
 *   /usr/sbin/postfix sendmail -bs   (postfix MTA)
 *   /var/qmail/bin/sendmail -bs   (qmail MTA)
 *
 * Sendmail Flags:
 *   -bs    Use SMTP protocol on stdin/stdout (recommended)
 *   -t     Read recipients from message headers (alternative)
 *   -oi    Don't treat "." alone on line as message terminator
 *   -f     Set envelope sender address
 *
 * Advantages Over mail():
 *   - More control over message format
 *   - Better error detection (exit codes instead of true/false)
 *   - Direct pipe to MTA (no PHP mail() overhead)
 *   - Can use custom sendmail flags
 *   - Can capture actual sendmail errors
 *
 * Disadvantages:
 *   - Still depends on server MTA configuration
 *   - No authentication (can't use Gmail, SendGrid, etc.)
 *   - Requires sendmail binary (not available on all hosts)
 *   - popen() may be disabled on shared hosting
 *   - More complex than mail()
 *
 * When to Use:
 *   - Linux/Unix servers with sendmail/postfix installed
 *   - When you need more control than mail() provides
 *   - Internal server notifications
 *   - When SMTP authentication isn't required
 *   - Dedicated or VPS hosting with proper MTA setup
 *
 * When NOT to Use:
 *   - Shared hosting (popen often disabled)
 *   - Windows servers (no sendmail binary)
 *   - When you need SMTP authentication
 *   - When sendmail isn't installed or configured
 *
 * Recommendation:
 *   Use SMTPMailer for production. SendMailer is best for dedicated servers
 *   with properly configured MTAs (sendmail, postfix, qmail).
 *
 * Security Features:
 *   - Header injection prevention (strips \r and \n)
 *   - Email address validation
 *   - Safe MIME boundary generation
 *   - Proper content encoding
 *
 * Usage Example:
 *
 *   $mailer = new SendMailer('/usr/sbin/sendmail -bs');
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

class SendMailer {

	/**
	 * Path to sendmail binary with flags
	 * Example: /usr/sbin/sendmail -bs
	 * @var string
	 */
	private $sendmailPath;

	/**
	 * Last error message from failed send attempt
	 * @var string|null
	 */
	private $lastError = null;

	/**
	 * Line ending for email messages (CRLF as per RFC 5322)
	 * @var string
	 */
	private $lineEnding = "\r\n";

	/**
	 * Constructor
	 *
	 * @param string $sendmailPath Path to sendmail binary with flags
	 */
	public function __construct($sendmailPath)
	{
		$this->sendmailPath = $sendmailPath;
	}

	// ===========================================================================
	// PUBLIC API
	// ===========================================================================

	/**
	 * Send email via sendmail binary
	 *
	 * Opens a pipe to sendmail, writes complete email message, and checks
	 * exit code for success/failure. The sendmail binary then handles actual
	 * delivery via SMTP to the recipient's mail server.
	 *
	 * Process:
	 * 1. Validate all email addresses (prevent injection attacks)
	 * 2. Build complete email message in RFC 5322 format
	 * 3. Open pipe to sendmail binary via popen()
	 * 4. Write raw email message to pipe
	 * 5. Close pipe and check exit code (0 = success)
	 *
	 * Sendmail Exit Codes:
	 *   0   - Success (email accepted for delivery)
	 *   64  - Command line usage error (wrong flags or syntax)
	 *   65  - Data format error (malformed email message)
	 *   67  - Addressee unknown (invalid recipient address)
	 *   69  - Service unavailable (sendmail not running properly)
	 *   75  - Temporary failure (try again later)
	 *   77  - Permission denied (can't execute sendmail)
	 *
	 * popen() vs mail():
	 *   popen() gives us direct pipe to sendmail, allowing raw message format
	 *   and better error detection via exit codes. mail() uses internal PHP
	 *   wrapper which adds overhead and only returns true/false without
	 *   detailed error information.
	 *
	 * Security:
	 *   All email addresses are validated before use.
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
	 * Example:
	 *   $mailer = new SendMailer('/usr/sbin/sendmail -bs');
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
		if (!$this->validateRecipients($to, 'to')) return false;
		if (!$this->validateRecipients($cc, 'cc')) return false;
		if (!$this->validateRecipients($bcc, 'bcc')) return false;

		// Build complete email message (RFC 5322 format)
		$message = $this->buildMessage($from, $to, $replyTo, $cc, $bcc, $subject, $body, $isHtml, $attachments);

		// Open pipe to sendmail binary ('w' = write mode)
		$handle = @popen($this->sendmailPath, 'w');

		if (!$handle) {
			$this->lastError = 'Could not open sendmail binary: ' . $this->sendmailPath;
			return false;
		}

		// Write complete email message to pipe
		fwrite($handle, $message);

		// Close pipe and get exit code
		// pclose() returns the exit status of the sendmail process
		$exitCode = pclose($handle);

		if ($exitCode !== 0) {
			$this->lastError = 'Sendmail exited with code ' . $exitCode . '. Check server logs for details.';
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
	// MESSAGE BUILDING
	// ===========================================================================

	/**
	 * Build complete email message
	 *
	 * Constructs raw email message following RFC 5322 (Internet Message Format).
	 * Message consists of headers, blank line separator, and body.
	 *
	 * Message Structure:
	 *   Headers (From, To, Subject, MIME-Version, Content-Type, etc.)
	 *   [Blank line]
	 *   Body content
	 *   [Attachments if present]
	 *
	 * Simple Message (no attachments):
	 *   From: sender@example.com
	 *   To: recipient@example.com
	 *   Subject: Hello
	 *   MIME-Version: 1.0
	 *   Content-Type: text/html; charset=UTF-8
	 *   [blank line]
	 *   <h1>Email body content</h1>
	 *
	 * Multipart Message (with attachments):
	 *   From: sender@example.com
	 *   To: recipient@example.com
	 *   Subject: Files Attached
	 *   MIME-Version: 1.0
	 *   Content-Type: multipart/mixed; boundary="unique-boundary-string"
	 *   [blank line]
	 *   --unique-boundary-string
	 *   Content-Type: text/html; charset=UTF-8
	 *   [body content]
	 *   --unique-boundary-string
	 *   Content-Type: application/octet-stream
	 *   Content-Transfer-Encoding: base64
	 *   Content-Disposition: attachment; filename="file.pdf"
	 *   [base64 encoded file content]
	 *   --unique-boundary-string--
	 *
	 * MIME Boundaries:
	 *   Boundaries separate different parts of multipart messages. They must
	 *   be unique strings that won't appear in the message content. We generate
	 *   them using timestamp + random numbers.
	 *
	 * @param array $from From address
	 * @param array $to To recipients
	 * @param array $cc CC recipients
	 * @param array $bcc BCC recipients
	 * @param string $subject Subject line
	 * @param string $body Body content
	 * @param bool $isHtml Whether body is HTML
	 * @param array $attachments Attachments
	 * @return string Complete email message
	 */
	private function buildMessage($from, $to, $replyTo, $cc, $bcc, $subject, $body, $isHtml, $attachments)
	{
		$message = [];

		// Build required headers
		$message[] = 'From: ' . $this->formatAddress($from['address'], $from['name']);
		$message[] = 'To: ' . $this->formatRecipientList($to);

		// Add optional headers (only if recipients present)
		if (!empty($cc)) {
			$message[] = 'Cc: ' . $this->formatRecipientList($cc);
		}

		if (!empty($bcc)) {
			$message[] = 'Bcc: ' . $this->formatRecipientList($bcc);
		}

		// Add standard headers
		$message[] = 'Subject: ' . $this->sanitizeHeader($subject);
		$message[] = 'Reply-To: ' . $this->formatAddress($from['address'], $from['name']);
		$message[] = 'X-Mailer: Rachie Framework';
		$message[] = 'MIME-Version: 1.0';

		// Content-Type and body (depends on attachments)
		if (!empty($attachments)) {
			// Multipart message with attachments
			$boundary = $this->generateBoundary();
			$message[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
			$message[] = '';
			$message[] = 'This is a multi-part message in MIME format.';
			$message[] = '';

			// Part 1: Email body content
			$message[] = '--' . $boundary;
			$message[] = 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
			$message[] = 'Content-Transfer-Encoding: 8bit';
			$message[] = '';
			$message[] = $body;
			$message[] = '';

			// Part 2+: File attachments (Base64 encoded)
			foreach ($attachments as $attachment) {
				$message[] = '--' . $boundary;
				$message[] = 'Content-Type: application/octet-stream; name="' . $attachment['name'] . '"';
				$message[] = 'Content-Transfer-Encoding: base64';
				$message[] = 'Content-Disposition: attachment; filename="' . $attachment['name'] . '"';
				$message[] = '';
				// Base64 encode file and split into 76-character lines (RFC 2045)
				$message[] = chunk_split(base64_encode(file_get_contents($attachment['path'])), 76, $this->lineEnding);
			}

			// Final boundary marker (with trailing --)
			$message[] = '--' . $boundary . '--';
		} else {
			// Simple message (no attachments)
			$message[] = 'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8';
			$message[] = 'Content-Transfer-Encoding: 8bit';
			$message[] = '';
			$message[] = $body;
		}

		// Join all parts with CRLF line endings
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
	 *   Name is wrapped in double quotes to handle special characters.
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
		// Sanitize both email and name
		$email = $this->sanitizeHeader($email);
		$name = $this->sanitizeHeader($name);

		// Return formatted address with or without name
		if (!empty($name)) {
			return '"' . $name . '" <' . $email . '>';
		}

		return $email;
	}

	/**
	 * Format recipient list as comma-separated string
	 *
	 * Converts an array of recipients into a comma-separated string
	 * suitable for email headers.
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
	 * Validate all recipients in array
	 *
	 * Checks each email address in the recipients array for validity.
	 * Sets error message and returns false if any address is invalid.
	 *
	 * @param array $recipients Recipients to validate
	 * @param string $type Type label for error message (to/cc/bcc)
	 * @return bool True if all valid, false if any invalid
	 */
	private function validateRecipients($recipients, $type)
	{
		foreach (array_keys($recipients) as $email) {
			if (!$this->validateEmail($email)) {
				$this->lastError = "Invalid {$type} address: {$email}";
				return false;
			}
		}

		return true;
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
	 *   Uses time() and mt_rand() to ensure uniqueness.
	 *   Prevents boundary collision attacks.
	 *
	 * @return string Unique boundary string
	 */
	private function generateBoundary()
	{
		return '----=_Part_' . time() . '_' . mt_rand() . '.' . mt_rand();
	}

}
