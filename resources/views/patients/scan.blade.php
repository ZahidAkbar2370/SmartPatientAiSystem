@extends('layouts.app')

@section('title', 'Scan Patient Document')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 page-title mb-4">Scan Patient Document</h1>

        <div class="card-soft p-4">
            <form id="scan-form" method="POST" action="{{ route('patients.scan.process') }}" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-semibold">Document Type</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="document_type" id="doc_cnic" value="cnic" checked>
                        <label class="form-check-label" for="doc_cnic">CNIC</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="document_type" id="doc_license" value="driving_license">
                        <label class="form-check-label" for="doc_license">Driving License</label>
                    </div>
                    @error('document_type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div id="camera-permission-alert" class="alert alert-warning d-none">
                    Camera access is required to scan the document.
                    Please allow camera access in your browser settings.
                </div>

                <div class="camera-frame mb-3">
                    <video id="camera-preview" autoplay playsinline muted></video>
                    <img id="captured-preview" class="d-none" alt="Captured document">
                    <canvas id="capture-canvas" class="d-none"></canvas>
                    <div id="camera-hint" class="camera-hint">Place document here</div>
                </div>

                <div id="status-text" class="text-muted small mb-3">Starting camera...</div>

                <input type="file" id="document_image" name="document_image" accept="image/jpeg,image/png,image/webp" class="d-none" required>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" id="btn-capture" class="btn btn-primary" disabled>Capture Image</button>
                    <button type="button" id="btn-retake" class="btn btn-outline-secondary d-none">Retake</button>
                    <button type="submit" id="btn-process" class="btn btn-success d-none" disabled>Process Document</button>
                    <a href="{{ route('patients.create') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>

                <hr>

                <div>
                    <p class="mb-2 text-muted">If camera scanning is unavailable:</p>
                    <label for="upload-image" class="btn btn-outline-primary">Upload Document Image</label>
                    <input type="file" id="upload-image" accept="image/jpeg,image/png,image/webp" class="d-none">
                    <div id="upload-name" class="small text-muted mt-2"></div>
                </div>

                @error('document_image')
                    <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                @enderror
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/scan.js') }}"></script>
@endpush
