@extends('home/layout')

@section('content')
<div class="wrapper">
	<div class="header">
		<div class="logo">
			<img src="{{ Url::assets('img/logo.png') }}" alt="Rachie">
		</div>
		<div class="header-text">
			<h1>Rachie Framework</h1>
			<p>Lightweight PHP MVC - Simple, Fast, and Elegant</p>
		</div>
	</div>

	<div class="content">
		<div class="column">
			<h2>Why Rachie?</h2>
			<div class="features">
				<div class="feature">
					<span class="feature-icon">⚡</span>
					<div class="feature-text">
						<h3>Lightning Fast</h3>
						<p>Tiny footprint with built-in caching for blazing performance</p>
					</div>
				</div>

				<div class="feature">
					<span class="feature-icon">🎯</span>
					<div class="feature-text">
						<h3>Simple Syntax</h3>
						<p>Clean code with zero learning curve - start building now</p>
					</div>
				</div>

				<div class="feature">
					<span class="feature-icon">📦</span>
					<div class="feature-text">
						<h3>Composer Ready</h3>
						<p>Modern package management made easy</p>
					</div>
				</div>

				<div class="feature">
					<span class="feature-icon">🔐</span>
					<div class="feature-text">
						<h3>Secure by Default</h3>
						<p>CSRF, XSS protection, and password hashing built-in</p>
					</div>
				</div>
			</div>
		</div>

		<div class="column">
			<h2>Getting Started</h2>
			<div class="steps">
				<div class="step">
					<div class="step-num">1</div>
					<div class="step-text">
						<h3>Edit this page</h3>
						<code>application/views/users/home.php</code>
					</div>
				</div>

				<div class="step">
					<div class="step-num">2</div>
					<div class="step-text">
						<h3>Check the controller</h3>
						<code>application/controllers/HomeController.php</code>
					</div>
				</div>

				<div class="step">
					<div class="step-num">3</div>
					<div class="step-text">
						<h3>Configure your app</h3>
						<code>config/settings.php</code>
					</div>
				</div>

				<div class="step">
					<div class="step-num">4</div>
					<div class="step-text">
						<h3>Read the documentation</h3>
						<code>README.md</code>
					</div>
				</div>
			</div>

			<div class="links">
				<a href="https://rachie.dev/docs" class="btn">📚 Documentation</a>
				<a href="https://github.com/glivers/rachie" class="btn">💻 GitHub</a>
			</div>
		</div>
	</div>

	<div class="footer">
		<div>
			&copy; 2015-2050 Geoffrey Okongo
		</div>
		@if(isset($request_time) && $request_time)
			<div class="footer-time">Request time: {{ $request_time }}</div>
		@endif
	</div>
</div>
@endsection
