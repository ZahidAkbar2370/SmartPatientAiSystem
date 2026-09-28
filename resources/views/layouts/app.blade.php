<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Patient Records')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --spr-primary: #0b5f6b;
            --spr-primary-dark: #084851;
            --spr-bg: #f3f6f7;
            --spr-border: #d7e2e5;
        }

        body {
            background: linear-gradient(180deg, #e8f2f4 0%, var(--spr-bg) 40%, #f7f9fa 100%);
            min-height: 100vh;
            color: #1f2a2e;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-spr {
            background: var(--spr-primary);
        }

        .navbar-spr .navbar-brand,
        .navbar-spr .nav-link,
        .navbar-spr .navbar-text {
            color: #fff !important;
        }

        .navbar-spr .nav-link:hover,
        .navbar-spr .nav-link.active {
            color: #d7f3f7 !important;
        }

        .btn-primary {
            background-color: var(--spr-primary);
            border-color: var(--spr-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--spr-primary-dark);
            border-color: var(--spr-primary-dark);
        }

        .card-soft {
            border: 1px solid var(--spr-border);
            border-radius: 0.75rem;
            box-shadow: 0 8px 24px rgba(11, 95, 107, 0.06);
            background: #fff;
        }

        .page-title {
            font-weight: 700;
            color: var(--spr-primary-dark);
        }

        .table thead th {
            background: #eef6f7;
            color: #35555b;
            font-weight: 600;
        }

        .camera-frame {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            background: #111;
            border-radius: 0.75rem;
            overflow: hidden;
            aspect-ratio: 4 / 3;
        }

        .camera-frame video,
        .camera-frame img,
        .camera-frame canvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .camera-hint {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.75);
            pointer-events: none;
            text-align: center;
            padding: 1rem;
        }

        .required-mark {
            color: #c0392b;
        }
    </style>
    @stack('styles')
</head>
<body>
    @auth
        <nav class="navbar navbar-expand-lg navbar-spr mb-4">
            <div class="container">
                <a class="navbar-brand fw-semibold" href="{{ route('patients.index') }}">Smart Patient Records</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                    <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patients.index') ? 'active' : '' }}"
                               href="{{ route('patients.index') }}">Patient Records</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patients.create*') || request()->routeIs('patients.scan*') ? 'active' : '' }}"
                               href="{{ route('patients.create') }}">Add Patient</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-3">
                        <span class="navbar-text">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    <main class="container pb-5">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
