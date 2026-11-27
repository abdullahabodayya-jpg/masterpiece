<!doctype html>
<html lang="en">
<head>
    @include('admin.head')
</head>
<body data-page="{{ $page }}" data-api="{{ url('/api/v1') }}"
      data-login="{{ route('login') }}" data-home="{{ route('admin.dashboard') }}">
    <a class="visually-hidden-focusable skip-link" href="#main">Skip to content</a>
    <aside class="offcanvas-lg offcanvas-start sidebar" id="sidebar" tabindex="-1" aria-labelledby="sidebar-label">
        <div class="sidebar-head">
            <a class="brand" href="{{ route('admin.dashboard') }}" id="sidebar-label">
                <span class="brand-mark"><i class="bi bi-bounding-box" aria-hidden="true"></i></span>Frame
            </a>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close navigation"></button>
        </div>
        <nav class="sidebar-nav" aria-label="Administration">
            @foreach ($navigation as $section => $links)
                <div class="nav-group">
                    <div class="nav-label">{{ $section }}</div>
                    @foreach ($links as $key => [$label, $icon])
                        <a href="{{ route('admin.'.$key) }}" @class(['nav-item-link', 'current' => $key === $page])
                           @if ($key === $page) aria-current="page" @endif>
                            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $label }}
                        </a>
                    @endforeach
                </div>
            @endforeach
            <div class="nav-group">
                <div class="nav-label">Account</div>
                <button type="button" class="nav-item-link" data-logout><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Sign out</button>
            </div>
        </nav>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <div class="topbar-context">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <span>{{ $group }}</span><span aria-hidden="true" class="text-secondary">/</span><strong>{{ $title }}</strong>
            </div>
            <div class="dropdown">
                <button type="button" class="btn account-toggle dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="account-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
                    <span class="account-name">{{ request()->user()->name }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <span class="dropdown-header text-wrap">{{ request()->user()->name }}</span>
                    <button type="button" class="dropdown-item" data-logout>Sign out</button>
                </div>
            </div>
        </header>
        <main id="main" class="page-content" tabindex="-1">
            <div class="page-heading">
                <div>
                    <h1>{{ $title }}</h1>
                    <p id="page-description" class="text-secondary mb-0" hidden></p>
                </div>
                <button type="button" class="btn btn-primary" id="create-record" hidden></button>
            </div>
            <div id="notice" role="status" aria-live="polite"></div>
            @yield('content')
        </main>
    </div>
    @include('admin.dialog')
    <script src="{{ asset('admin-assets/vendor/bootstrap.bundle.min.js') }}" defer></script>
    @stack('scripts')
    <script type="module" src="{{ asset('admin-assets/js/auth.js') }}"></script>
</body>
</html>
