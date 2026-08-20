<?php namespace Rackage\Mail;

/**
 * SMTPMailer - Native SMTP Protocol Implementation
 *
 * Pure PHP implementation of SMTP (Simple Mail Transfer Protocol) for sending
 * emails through SMTP servers. Opens direct socket connection to mail server,
 * handles authentication, encryption, and MIME message delivery.
 *
 * How SMTP Works:
 *   Client connects to SMTP server on port 587/465/25, exchanges commands
 *   (EHLO, AUTH, MAIL FROM, RCPT TO, DATA, QUIT), server responds with codes
 *   (250 OK, 354 Start mail input, etc.). This is the standard email protocol
 *   used by all mail servers worldwide.
 *
 * Protocol Flow:
 *   1. Connect to server via socket
 *   2. Server sends: 220 smtp.example.com Ready
 *   3. Client sends: EHLO localhost
 *   4. Server sends: 250 OK
 *   5. Client sends: STARTTLS (if using TLS)
 *   6. Enable TLS encryption on socket
 *   7. Client sends: AUTH LOGIN
 *   8. Client sends: base64(username)
 *   9. Client sends: base64(password)
 *   10. Server sends: 235 Authentication successful
 *   11. Client sends: MAIL FROM:<sender@example.com>
 *   12. Client sends: RCPT TO:<recipient@example.com>
 *   13. Client sends: DATA
 *   14. Client sends: [email headers and body]
 *   15. Client sends: . (period on line by itself)
 *   16. Server sends: 250 Message accepted
 *   17. Client sends: QUIT
 *   18. Connection closed
 *
 * Supported Features:
 *   - TLS encryption (STARTTLS on port 587)
 *   - SSL encryption (direct SSL on port 465)
 *   - AUTH LOGIN authentication (most common)
 *   - AUTH PLAIN authentication (alternative)
 *   - Multiple recipients (To, CC, BCC)
 *   - HTML and plain text emails
 *   - MIME attachments (Base64 encoded)
 *   - Proper error handling with SMTP codes
 *
 * Supported Ports:
 *   587  - Submission port with STARTTLS (recommended, widely supported)
 *   465  - SMTPS (SSL from start, older but still used)
 *   25   - Traditional SMTP (often blocked, avoid)
 *   2525 - Alternative submission port (some providers like Mailgun)
 *
 * Advantages:
 *   - Works with any SMTP server (Gmail, SendGrid, Mailgun, AWS SES, etc.)
 *   - Full authentication support (use commercial mail services)
 *   - Encrypted connections (TLS/SSL)
 *   - Detailed error messages from server
 *   - No dependency on server configuration
 *   - No external libraries required (pure PHP)
 *
 * Requirements:
 *   - PHP sockets enabled (check with: function_exists('fsockopen'))
 *   - OpenSSL extension for TLS/SSL (check: extension_loaded('openssl'))
 *   - Outbound connections allowed on SMTP port
 *   - Valid SMTP credentials
 *
 * When to Use:
 *   - Production applications (high reliability needed)
 *   - When using Gmail, SendGrid, Mailgun, AWS SES, etc.
 *   - Shared hosting (doesn't depend on server MTA)
 *   - When you need delivery confirmation
 *   - When emails must not go to spam
 *
 * Popular SMTP Services:
 *
 *   Gmail:
 *     host: smtp.gmail.com, port: 587, encryption: tls
 *     Note: Requires App Password if 2FA enabled
 *
 *   SendGrid:
 *     host: smtp.sendgrid.net, port: 587, encryption: tls
 *     username: apikey, password: YOUR_API_KEY
 *
 *   Mailgun:
 *     host: smtp.mailgun.org, port: 587, encryption: tls
 *
 *   Amazon SES:
 *     host: email-smtp.us-east-1.amazonaws.com, port: 587, encryption: tls
 *
 *   Mailtrap (testing):
 *     host: smtp.mailtrap.io, port: 2525, encryption: tls
 *
 * Security Features:
 *   - TLS/SSL encryption for credentials and content
 *   - Secure authentication (base64 encoded credentials)
 *   - Header injection prevention
 *   - Email address validation
 *   - Timeout protection (prevents hanging connections)
 *
 * Usage Example:
 *
 *   $config = [
 *       'host' => 'smtp.gmail.com',
 *       'port' => 587,
 *       'encryption' => 'tls',
 *       'username' => 'your-email@gmail.com',
 *       'password' => 'your-app-password',
 *       'timeout' => 30,
 *       'auth' => true,
 *   ];
 *
 *   $mailer = new SMTPMailer($config);
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

class SMTPMailer {

	/**
	 * SMTP server configuration
	 * @var array
	 */
	private $config;

	/**
	 * Socket connection to SMTP server
	 * @var resource|null
	 */
	private $socket = null;

	/**
	 * Last error message from failed operation
	 * @var string|null
	 */
	private $lastError = null;

	/**
	 * Line ending for SMTP commands and email messages (CRLF per RFC 5321)
	 * @var string
	 */
	private $lineEnding = "\r\n";

	/**
	 * Debug mode (logs all SMTP communication)
	 * @var bool
	 */
	private $debug = false;

	/**
	 * Debug log output
	 * @var array
	 */
	private $debugLog = [];

	/**
	 * Constructor
	 *
	 * @param array $config SMTP configuration
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}

	// ===========================================================================
	// PUBLIC API
	// ===========================================================================

	/**
	 * Send email via SMTP
	 *
	 * Establishes connection to SMTP server, authenticates, sends email,
	 * and closes connection. This is the main public method for sending emails.
	 *
	 * Process:
	 * 1. Validate all email addresses
	 * 2. Connect to SMTP server via socket
	 * 3. Start TLS encryption (if configured)
	 * 4. Authenticate with username/password (if configured)
	 * 5. Send MAIL FROM command (sender)
	 * 6. Send RCPT TO commands (all recipients: to, cc, bcc)
	 * 7. Send DATA command and email content
	 * 8. Send QUIT command and close connection
	 *
	 * SMTP Response Codes:
	 *   220 - Service ready
	 *   250 - Requested action completed
	 *   354 - Start mail input (after DATA command)
	 *   235 - Authentication successful
	 *   535 - Authentication failed
	 *   550 - Mailbox unavailable
	 *   554 - Transaction failed
	 *
	 * Error Handling:
	 *   Any SMTP error stops the process and returns false.
	 *   Connection is closed even on error (via try/finally pattern).
	 *   Detailed error message available via getError().
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
	public function send($from, $to, $headers, $replyTo, $cc, $bcc, $subject, $body, $isHtml, $attachments)
	{
		// Clear previous error and debug log
		$this->lastError = null;
		$this->debugLog = [];

		try {
			// Validate from address
			if (!$this->validateEmail($from['address'])) {
				
				$this->lastError = 'Invalid from address: ' . $from['address'];
				return false;
			}
			
			// Validate replyTo address
			if($replyTo && !$this->validateEmail($replyTo['address'])) {
				 	
				$this->lastError = 'Invalid replyTo address: ' . $replyTo['address'];
				return false;
			}	 		

			// Validate all recipient addresses
			if (!$this->validateRecipients($to, 'to')) return false;
			if (!$this->validateRecipients($cc, 'cc')) return false;
			if (!$this->validateRecipients($bcc, 'bcc')) return false;

			// Step 1: Connect to SMTP server
			if (!$this->connect()) return false;

			// Step 2: Authenticate (if enabled)
			if (!empty($this->config['auth']) && $this->config['auth'] === true) {
				
				if (!$this->authenticate()) {
					$this->disconnect();
					return false;
				}
			}

			// Step 3: Send MAIL FROM command (envelope sender)
			if (!$this->sendCommand("MAIL FROM:<{$from['address']}>", 250)) {
				
				$this->disconnect();
				return false;
			}

			// Step 4: Send RCPT TO commands (all recipients)
			// SMTP requires separate RCPT TO command for each recipient
			$allRecipients = array_merge(
				array_keys($to),
				array_keys($cc),
				array_keys($bcc)
			);

			foreach ($allRecipients as $recipient) {
				
				if (!$this->sendCommand("RCPT TO:<{$recipient}>", 250)) {
					$this->disconnect();
					return false;
				}
			}

			// Step 5: Send DATA command to start message transmission
			if (!$this->sendCommand("DATA", 354)) {

				$this->disconnect();
				return false;
			}

			// Step 6: Build and send complete email message
			$message = $this->buildMessage($from, $to, $headers, $replyTo, $cc, $bcc, $subject, $body, $isHtml, $attachments);

			// Send message and end with CRLF.CRLF (period on line by itself)
			fwrite($this->socket, $message . $this->lineEnding);
			fwrite($this->socket, '.' . $this->lineEnding);

			// Wait for server confirmation (250 Message accepted)
			if (!$this->readResponse(250)) {
				$this->disconnect();
				return false;
			}

			// Step 7: Send QUIT command and close connection
			$this->disconnect();

			return true;

		} 
		catch (\Exception $e) {
			
			$this->lastError = 'SMTP Exception: ' . $e->getMessage();
			$this->disconnect();
			return false;
		}
	}

	/**
	 * Get last error message
	 *
	 * Returns the error message from the most recent failed operation.
	 * Includes SMTP server responses for debugging.
	 *
	 * @return string|null Error message or null
	 */
	public function getError()
	{
		return $this->lastError;
	}

	/**
	 * Enable debug mode
	 *
	 * When enabled, logs all SMTP communication for debugging.
	 * Access log via getDebugLog().
	 *
	 * @param bool $enabled Enable debug mode
	 * @return void
	 */
	public function setDebug($enabled)
	{
		$this->debug = $enabled;
	}

	/**
	 * Get debug log
	 *
	 * Returns array of all SMTP commands and responses for debugging.
	 *
	 * @return array Debug log entries
	 */
	public function getDebugLog()
	{
		return $this->debugLog;
	}

	// ===========================================================================
	// SMTP CONNECTION
	// ===========================================================================

	/**
	 * Connect to SMTP server
	 *
	 * Establishes socket connection to SMTP server and handles encryption.
	 * Supports both STARTTLS (port 587) and direct SSL (port 465).
	 *
	 * Connection Process:
	 * 1. Open socket connection to server
	 * 2. Read server greeting (220 code)
	 * 3. Send EHLO command (identify ourselves)
	 * 4. If TLS: Send STARTTLS, enable encryption, send EHLO again
	 * 5. If SSL: Encryption already active from connection
	 *
	 * TLS vs SSL:
	 *   TLS (port 587): Connect plaintext, then STARTTLS to upgrade to encrypted
	 *   SSL (port 465): Connect with encryption from the start
	 *
	 * EHLO vs HELO:
	 *   EHLO is extended SMTP, required for AUTH and modern features
	 *   HELO is legacy, still works but lacks modern capabilities
	 *
	 * @return bool True on success, false on failure
	 */
	private function connect()
	{
		$host = $this->config['host'];
		$port = $this->config['port'];
		$encryption = $this->config['encryption'] ?? '';
		$timeout = $this->config['timeout'] ?? 30;

		// Build connection string
		// For SSL (port 465), use ssl:// prefix
		// For TLS (port 587), connect plaintext then upgrade
		$connectionString = $host . ':' . $port;
		if ($encryption === 'ssl') {
			$connectionString = 'ssl://' . $connectionString;
		}

		// Open socket connection
		$this->socket = @stream_socket_client(
			$connectionString,
			$errno,
			$errstr,
			$timeout,
			STREAM_CLIENT_CONNECT
		);

		if (!$this->socket) {
			$this->lastError = "Could not connect to {$host}:{$port} - {$errstr} ({$errno})";
			return false;
		}

		// Set timeout for socket operations
		stream_set_timeout($this->socket, $timeout);

		// Read server greeting (220 Service ready)
		if (!$this->readResponse(220)) {
			return false;
		}

		// Send EHLO command to identify ourselves
		$clientName = $_SERVER['SERVER_NAME'] ?? 'localhost';
		if (!$this->sendCommand("EHLO {$clientName}", 250)) {
			return false;
		}

		// Start TLS encryption if configured (not needed for SSL - already encrypted)
		if ($encryption === 'tls') {
			// Send STARTTLS command
			if (!$this->sendCommand("STARTTLS", 220)) {
				return false;
			}

			// Enable TLS encryption on socket
			$crypto = stream_socket_enable_crypto(
				$this->socket,
				true,
				STREAM_CRYPTO_METHOD_TLS_CLIENT
			);

			if (!$crypto) {
				$this->lastError = 'Failed to enable TLS encryption';
				return false;
			}

			// Send EHLO again after encryption (required by RFC)
			if (!$this->sendCommand("EHLO {$clientName}", 250)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Authenticate with SMTP server
	 *
	 * Performs authentication using AUTH LOGIN or AUTH PLAIN.
	 * Credentials are base64 encoded before transmission.
	 *
	 * AUTH LOGIN Process:
	 * 1. Client sends: AUTH LOGIN
	 * 2. Server sends: 334 VXNlcm5hbWU6 (base64 "Username:")
	 * 3. Client sends: base64(username)
	 * 4. Server sends: 334 UGFzc3dvcmQ6 (base64 "Password:")
	 * 5. Client sends: base64(password)
	 * 6. Server sends: 235 Authentication successful
	 *
	 * AUTH PLAIN Process (alternative):
	 * 1. Client sends: AUTH PLAIN base64("\0username\0password")
	 * 2. Server sends: 235 Authentication successful
	 *
	 * Security:
	 *   Always use TLS/SSL encryption before authenticating.
	 *   Credentials are base64 encoded (not encrypted, just encoded).
	 *   Without TLS/SSL, credentials are visible to network sniffers.
	 *
	 * @return bool True on success, false on failure
	 */
	private function authenticate()
	{
		$username = $this->config['username'] ?? '';
		$password = $this->config['password'] ?? '';

		if (empty($username) || empty($password)) {
			$this->lastError = 'SMTP username and password required for authentication';
			return false;
		}

		// Try AUTH LOGIN first (most common)
		// Send AUTH LOGIN command
		if (!$this->sendCommand("AUTH LOGIN", 334)) {
			// If AUTH LOGIN not supported, try AUTH PLAIN
			return $this->authenticatePlain($username, $password);
		}

		// Send base64 encoded username
		if (!$this->sendCommand(base64_encode($username), 334)) {
			return false;
		}

		// Send base64 encoded password
		if (!$this->sendCommand(base64_encode($password), 235)) {
			$this->lastError = 'SMTP authentication failed. Check username and password.';
			return false;
		}

		return true;
	}

	/**
	 * Authenticate using AUTH PLAIN
	 *
	 * Alternative authentication method. Sends username and password
	 * in a single command as base64("\0username\0password").
	 *
	 * @param string $username SMTP username
	 * @param string $password SMTP password
	 * @return bool True on success, false on failure
	 */
	private function authenticatePlain($username, $password)
	{
		// AUTH PLAIN format: base64("\0username\0password")
		$auth = base64_encode("\0" . $username . "\0" . $password);

		if (!$this->sendCommand("AUTH PLAIN {$auth}", 235)) {
			$this->lastError = 'SMTP authentication failed. Check username and password.';
			return false;
		}

		return true;
	}

	/**
	 * Disconnect from SMTP server
	 *
	 * Sends QUIT command and closes socket connection.
	 * Safe to call even if connection already closed.
	 *
	 * @return void
	 */
	private function disconnect()
	{
		if ($this->socket) {
			// Send QUIT command (polite way to close connection)
			@fwrite($this->socket, "QUIT" . $this->lineEnding);

			// Close socket
			@fclose($this->socket);
			$this->socket = null;
		}
	}

	// ===========================================================================
	// SMTP COMMUNICATION
	// ===========================================================================

	/**
	 * Send SMTP command and verify response code
	 *
	 * Writes command to socket, reads server response, and checks for
	 * expected response code.
	 *
	 * SMTP Response Format:
	 *   250 OK
	 *   250-First line
	 *   250-Second line
	 *   250 Last line
	 *
	 * Response codes are 3 digits. Fourth character is space (last line)
	 * or hyphen (more lines follow).
	 *
	 * @param string $command SMTP command to send
	 * @param int $expectedCode Expected response code (250, 354, etc.)
	 * @return bool True if response matches expected code
	 */
	private function sendCommand($command, $expectedCode)
	{
		// Write command to socket
		fwrite($this->socket, $command . $this->lineEnding);

		// Log command if debug enabled
		if ($this->debug) {
			$this->debugLog[] = 'C: ' . $command;
		}

		// Read and verify response
		return $this->readResponse($expectedCode);
	}

	/**
	 * Read SMTP server response
	 *
	 * Reads one or more lines from SMTP server and checks response code.
	 * Handles multi-line responses (250-line1, 250-line2, 250 line3).
	 *
	 * Response Format:
	 *   Single line: "250 OK\r\n"
	 *   Multi-line:  "250-First\r\n250-Second\r\n250 Last\r\n"
	 *
	 * Reading Logic:
	 *   Read lines until we find one where character 4 is a space (not hyphen).
	 *   That indicates the last line of the response.
	 *
	 * @param int $expectedCode Expected response code
	 * @return bool True if response code matches expected
	 */
	private function readResponse($expectedCode)
	{
		$response = '';
		$code = 0;

		// Read response lines until we get the final line
		// Final line has space in position 3: "250 OK"
		// Continued lines have hyphen: "250-First line"
		while ($line = fgets($this->socket, 515)) {
			$response .= $line;

			// Extract response code from first 3 characters
			if ($code === 0) {
				$code = (int) substr($line, 0, 3);
			}

			// Check if this is the last line (space after code, not hyphen)
			if (isset($line[3]) && $line[3] === ' ') {
				break;
			}
		}

		// Log response if debug enabled
		if ($this->debug) {
			$this->debugLog[] = 'S: ' . trim($response);
		}

		// Check if response code matches expected
		if ($code !== $expectedCode) {
			$this->lastError = "SMTP Error: Expected {$expectedCode}, got {$code} - {$response}";
			return false;
		}

		return true;
	}

	// ===========================================================================
	// MESSAGE BUILDING
	// ===========================================================================

	/**
	 * Build complete email message
	 *
	 * Constructs raw email message following RFC 5322 (Internet Message Format).
	 * Same format as SendMailer - headers, blank line, body, attachments.
	 *
	 * Note: This is identical to SendMailer's buildMessage() method.
	 * SMTP and sendmail both use the same email message format.
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
	private function buildMessage($from, $to, $headers, $replyTo, $cc, $bcc, $subject, $body, $isHtml, $attachments)
	{
		$message = [];

		// Build required headers
		$message[] = 'From: ' . $this->formatAddress($from['address'], $from['name']);
		$message[] = 'To: ' . $this->formatRecipientList($to);

		// Add optional headers (only if recipients present)
		if (!empty($cc)) {
			$message[] = 'Cc: ' . $this->formatRecipientList($cc);
		}

		// Note: BCC recipients are NOT included in message headers
		// They're sent via RCPT TO but hidden from other recipients

		// Add standard headers
		$message[] = 'Subject: ' . $this->sanitizeHeader($subject);

		if($replyTo) $message[] = 'Reply-To: ' . $this->formatAddress($replyTo['address'], $replyTo['name']);
		else $message[] = 'Reply-To: ' . $this->formatAddress($from['address'], $from['name']);

		// Add custom headers if present
		if($headers) {
			foreach($headers as $name => $value){

				$value 	= trim($value);
				$value 	= trim($value, "<>");
				$message[] 	= $name . " : <" . $value . ">";
			}
		}
			
		// $message[] = 'X-Mailer: Rachie Framework';
		$message[] = 'MIME-Version: 1.0';
		$message[] = 'Date: ' . date('r'); // RFC 2822 date format

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
		} 
		else {
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
	 * @param string $email Email address
	 * @param string $name Display name (optional)
	 * @return string Formatted email address
	 */
	private function formatAddress($email, $name = '')
	{
		$email = $this->sanitizeHeader($email);
		$name = $this->sanitizeHeader($name);

		if (!empty($name)) {
			return '"' . $name . '" <' . $email . '>';
		}

		return $email;
	}

	/**
	 * Format recipient list as comma-separated string
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
	 * @return string Unique boundary string
	 */
	private function generateBoundary()
	{
		return '----=_Part_' . time() . '_' . mt_rand() . '.' . mt_rand();
	}

}
