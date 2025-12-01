<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" href="{{ Url::assets('img/logo.png') }}">
	<title>{{ $title }}</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
			overflow: hidden;
		}

		.wrapper {
			background: white;
			border-radius: 16px;
			box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
			max-width: 1100px;
			width: 100%;
			overflow: hidden;
		}

		.header {
			background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
			color: white;
			padding: 30px 40px;
			display: flex;
			align-items: center;
			gap: 25px;
		}

		.logo {
			width: 70px;
			height: 70px;
			background: white;
			border-radius: 12px;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
		}

		.logo img {
			max-width: 80%;
			max-height: 80%;
		}

		.header-text h1 {
			font-size: 32px;
			font-weight: 700;
			margin-bottom: 5px;
		}

		.header-text p {
			font-size: 15px;
			opacity: 0.95;
		}

		.content {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 30px;
			padding: 35px 40px;
		}

		.column h2 {
			font-size: 20px;
			font-weight: 700;
			margin-bottom: 20px;
			color: #2d3748;
		}

		.features {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.feature {
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 12px 15px;
			background: #f8f9fa;
			border-radius: 8px;
			border-left: 3px solid #667eea;
		}

		.feature-icon {
			font-size: 24px;
			flex-shrink: 0;
		}

		.feature-text h3 {
			font-size: 15px;
			font-weight: 600;
			margin-bottom: 2px;
			color: #2d3748;
		}

		.feature-text p {
			font-size: 13px;
			color: #6c757d;
			line-height: 1.4;
		}

		.steps {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.step {
			display: flex;
			gap: 12px;
			padding: 12px 15px;
			background: #f8f9fa;
			border-radius: 8px;
		}

		.step-num {
			width: 32px;
			height: 32px;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 700;
			font-size: 16px;
			flex-shrink: 0;
		}

		.step-text h3 {
			font-size: 14px;
			font-weight: 600;
			margin-bottom: 3px;
			color: #2d3748;
		}

		.step-text code {
			font-size: 12px;
			background: #2d3748;
			color: #4fd1c5;
			padding: 2px 6px;
			border-radius: 4px;
			font-family: 'Consolas', monospace;
		}

		.links {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
			margin-top: 20px;
		}

		.btn {
			padding: 12px 20px;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			text-decoration: none;
			border-radius: 8px;
			text-align: center;
			font-weight: 600;
			font-size: 14px;
			transition: all 0.3s ease;
			display: block;
		}

		.btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
		}

		.footer {
			padding: 18px 40px;
			background: #2d3748;
			color: white;
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-size: 13px;
		}

		.footer a {
			color: #667eea;
			text-decoration: none;
		}

		.footer-time {
			opacity: 0.7;
			font-family: 'Consolas', monospace;
			font-size: 12px;
		}

		@media (max-width: 900px) {
			.content {
				grid-template-columns: 1fr;
				gap: 25px;
				padding: 25px 30px;
			}

			.header {
				padding: 25px 30px;
			}

			.footer {
				flex-direction: column;
				gap: 8px;
				padding: 15px 30px;
			}
		}
	</style>
</head>
<body>
	@section('content'):
</body>
</html>
