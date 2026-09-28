@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $fromScan = $fromScan ?? false;
    $isEdit = ($mode ?? 'create') === 'edit';
    $tempDocument = old('temp_document', $tempDocument ?? null);
    $documentType = old('document_type', $patient->document_type);
    if (! empty($tempDocument)) {
        $fromScan = true;
        if (empty($previewImage ?? null)) {
            $previewImage = route('patients.temp-document', ['path' => encrypt($tempDocument)]);
        }
    }
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">
        <h1 class="h3 page-title mb-3">{{ $title }}</h1>

        @if (!empty($ocrWarning))
            <div class="alert alert-warning">
                {{ $ocrWarning }}
            </div>
        @endif

        @if ($errors->has('cnic') && str_contains($errors->first('cnic'), 'already exists'))
            @php
                $existing = \App\Models\Patient::where('cnic', old('cnic'))->first();
            @endphp
            @if ($existing)
                <div class="alert alert-danger">
                    A patient with this CNIC already exists.
                    <a href="{{ route('patients.show', $existing) }}" class="alert-link">Open existing patient</a>
                </div>
            @endif
        @endif

        @if (!empty($previewImage))
            <div class="card-soft p-3 mb-4">
                <h2 class="h6 mb-3">Scanned Document</h2>
                <img src="{{ $previewImage }}" alt="Scanned document" class="img-fluid rounded border">
            </div>
        @endif

        <div class="card-soft p-4">
            @if ($fromScan)
                <h2 class="h5 mb-3">Extracted Patient Information</h2>
                <p class="text-muted small">Review and correct the extracted information before saving. OCR is not always 100% accurate.</p>
            @endif

            <form
                method="POST"
                action="{{ $isEdit ? route('patients.update', $patient) : route('patients.store') }}"
                novalidate
            >
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                @if (!empty($tempDocument))
                    <input type="hidden" name="temp_document" value="{{ $tempDocument }}">
                @endif

                @if ($fromScan || $documentType)
                    <input type="hidden" name="document_type" value="{{ $documentType }}">
                @endif

                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name <span class="required-mark">*</span></label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="{{ old('full_name', $patient->full_name) }}"
                        class="form-control @error('full_name') is-invalid @enderror"
                        required
                        maxlength="150"
                    >
                    @error('full_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="father_husband_name" class="form-label">Father/Husband Name</label>
                    <input
                        type="text"
                        id="father_husband_name"
                        name="father_husband_name"
                        value="{{ old('father_husband_name', $patient->father_husband_name) }}"
                        class="form-control @error('father_husband_name') is-invalid @enderror"
                        maxlength="150"
                    >
                    @error('father_husband_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="cnic" class="form-label">CNIC Number <span class="required-mark">*</span></label>
                    <input
                        type="text"
                        id="cnic"
                        name="cnic"
                        value="{{ old('cnic', $patient->cnic) }}"
                        class="form-control @error('cnic') is-invalid @enderror"
                        required
                        placeholder="35202-1234567-1"
                        maxlength="15"
                    >
                    @error('cnic')
                        @unless(str_contains($message, 'already exists'))
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endunless
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth <span class="required-mark">*</span></label>
                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        value="{{ old('date_of_birth', optional($patient->date_of_birth)->format('Y-m-d')) }}"
                        class="form-control @error('date_of_birth') is-invalid @enderror"
                        required
                    >
                    @error('date_of_birth')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Gender <span class="required-mark">*</span></label>
                    @php $gender = old('gender', $patient->gender); @endphp
                    <div class="form-check form-check-inline">
                        <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender" id="gender_male" value="Male" {{ $gender === 'Male' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="gender_male">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender" id="gender_female" value="Female" {{ $gender === 'Female' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="gender_female">Female</label>
                    </div>
                    @error('gender')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $patient->phone) }}"
                        class="form-control @error('phone') is-invalid @enderror"
                        maxlength="20"
                        placeholder="0300-1234567"
                    >
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="address" class="form-label">Address <span class="required-mark">*</span></label>
                    <textarea
                        id="address"
                        name="address"
                        rows="3"
                        class="form-control @error('address') is-invalid @enderror"
                        required
                        maxlength="500"
                    >{{ old('address', $patient->address) }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Save Patient</button>

                    @if ($fromScan)
                        <a href="{{ route('patients.scan') }}" class="btn btn-outline-secondary">Scan Again</a>
                    @endif

                    <a href="{{ $isEdit ? route('patients.show', $patient) : route('patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
