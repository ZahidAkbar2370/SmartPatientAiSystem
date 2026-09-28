@extends('layouts.app')

@section('title', 'Login — Smart Patient Records')

@section('content')
<div class="row justify-content-center" style="min-height: 80vh; align-items: center;">
    <div class="col-md-5 col-lg-4">
        <div class="card-soft p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h3 page-title mb-1">Smart Patient Records</h1>
                <p class="text-muted mb-0">Sign in to continue</p>
            </div>

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
            </form>
        </div>
    </div>
</div>
@endsection
