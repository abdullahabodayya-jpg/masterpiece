<!doctype html>
<html lang="en">
<head>
    @include('admin.head')
</head>
<body class="sign-in" data-page="login" data-api="{{ url('/api/v1') }}"
      data-login="{{ route('login') }}" data-home="{{ route('admin.dashboard') }}">
    <div class="login-side">
        <span class="brand"><span class="brand-mark"><i class="bi bi-bounding-box" aria-hidden="true"></i></span>Frame</span>
        <p>Marketplace administration</p>
    </div>
    <main class="login-main">
        <div class="login-content">
            <h1>Admin sign in</h1>
            <p class="text-secondary mb-4">Use your Frame administrator account.</p>
            <div id="auth-message" class="alert alert-danger" role="alert" hidden></div>
            @if ($hasSession)
                <p>You’re signed in to a different Frame account.</p>
                <button id="switch-account" type="button" class="btn btn-primary">Sign out to switch accounts</button>
            @else
                <form id="login-form" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" class="form-control" autocomplete="username" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                            <button id="show-password" type="button" class="btn btn-outline-secondary" aria-controls="password" aria-pressed="false">Show</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" disabled>Sign in</button>
                </form>
                <noscript>Enable JavaScript to sign in.</noscript>
            @endif
        </div>
    </main>
    <script type="module" src="{{ asset('admin-assets/js/auth.js') }}"></script>
</body>
</html>
