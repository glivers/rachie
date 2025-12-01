<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo isset($title) ? htmlspecialchars($title) : 'Application Error'; ?></title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
			color: #333;
		}

		.error-container {
			background: white;
			border-radius: 12px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			max-width: 800px;
			width: 100%;
			overflow: hidden;
		}

		.error-header {
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			padding: 40px;
			text-align: center;
			color: white;
		}

		.error-icon {
			font-size: 64px;
			margin-bottom: 20px;
			display: block;
		}

		.error-header h1 {
			font-size: 32px;
			font-weight: 600;
			margin-bottom: 10px;
		}

		.error-header p {
			font-size: 18px;
			opacity: 0.9;
		}

		.error-body {
			padding: 40px;
		}

		.error-message {
			background: #f8f9fa;
			border-left: 4px solid #f5576c;
			padding: 20px;
			border-radius: 6px;
			margin-bottom: 30px;
			font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
			font-size: 14px;
			line-height: 1.6;
			color: #495057;
			overflow-x: auto;
		}

		.error-actions {
			display: flex;
			gap: 15px;
			flex-wrap: wrap;
		}

		.btn {
			padding: 12px 24px;
			border-radius: 6px;
			text-decoration: none;
			font-weight: 500;
			transition: all 0.3s ease;
			display: inline-block;
			border: none;
			cursor: pointer;
			font-size: 16px;
		}

		.btn-primary {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
		}

		.btn-primary:hover {
			transform: translateY(-2px);
			box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
		}

		.btn-secondary {
			background: #e9ecef;
			color: #495057;
		}

		.btn-secondary:hover {
			background: #dee2e6;
		}

		.error-footer {
			padding: 20px 40px;
			background: #f8f9fa;
			border-top: 1px solid #e9ecef;
			font-size: 14px;
			color: #6c757d;
			text-align: center;
		}

		.production-message {
			text-align: center;
			color: #6c757d;
		}

		.production-message h2 {
			font-size: 24px;
			color: #495057;
			margin-bottom: 10px;
		}

		@media (max-width: 600px) {
			.error-header {
				padding: 30px 20px;
			}

			.error-header h1 {
				font-size: 24px;
			}

			.error-header p {
				font-size: 16px;
			}

			.error-body {
				padding: 30px 20px;
			}

			.error-footer {
				padding: 15px 20px;
			}

			.btn {
				width: 100%;
				text-align: center;
			}
		}
	</style>
</head>
<body>
	<div class="error-container">
		<div class="error-header">
			<span class="error-icon">⚠️</span>
			<h1>Application Error</h1>
			<p><?php echo isset($title) ? htmlspecialchars($title) : 'Something went wrong'; ?></p>
		</div>

		<div class="error-body">
			<?php if (isset($hideError) && $hideError): ?>
				<!-- Production Mode: Hide error details -->
				<div class="production-message">
					<h2>We're sorry!</h2>
					<p>The application encountered an unexpected error. Our team has been notified and will investigate the issue.</p>
					<p style="margin-top: 20px;">Please try again later or contact support if the problem persists.</p>
				</div>
			<?php else: ?>
				<!-- Development Mode: Show error details -->
				<?php if (isset($error)): ?>
					<div class="error-message">
						<?php echo $error; ?>
					</div>
				<?php endif; ?>

				<div class="error-actions">
					<a href="/" class="btn btn-primary">Go to Homepage</a>
					<button onclick="history.back()" class="btn btn-secondary">Go Back</button>
				</div>
			<?php endif; ?>
		</div>

		<div class="error-footer">
			<?php if (isset($hideError) && $hideError): ?>
				Error logged and administrators notified
			<?php else: ?>
				Development Mode - Detailed errors shown
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
