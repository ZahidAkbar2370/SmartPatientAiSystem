@extends('layouts.app')

@section('title', 'Add Patient')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h1 class="h3 page-title mb-4">Add Patient</h1>

        <div class="row g-3">
            <div class="col-md-6">
                <a href="{{ route('patients.create.manual') }}" class="text-decoration-none">
                    <div class="card-soft p-4 h-100 text-center">
                        <h2 class="h5 mb-2 text-dark">Add Manually</h2>
                        <p class="text-muted mb-0">Enter patient information using a form.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('patients.scan') }}" class="text-decoration-none">
                    <div class="card-soft p-4 h-100 text-center">
                        <h2 class="h5 mb-2 text-dark">Scan CNIC / License</h2>
                        <p class="text-muted mb-0">Capture a document and auto-fill patient fields using OCR.</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Back to Patient Records</a>
        </div>
    </div>
</div>
@endsection
