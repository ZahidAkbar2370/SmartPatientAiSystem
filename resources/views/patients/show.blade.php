@extends('layouts.app')

@section('title', 'Patient Details')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h1 class="h3 page-title mb-0">Patient Details</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('patients.edit', $patient) }}" class="btn btn-primary">Edit</a>
                <form method="POST" action="{{ route('patients.destroy', $patient) }}"
                      onsubmit="return confirm('Are you sure you want to delete this patient?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>

        <div class="card-soft p-4 mb-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Name</dt>
                <dd class="col-sm-8">{{ $patient->full_name }}</dd>

                <dt class="col-sm-4">Father/Husband Name</dt>
                <dd class="col-sm-8">{{ $patient->father_husband_name ?: '—' }}</dd>

                <dt class="col-sm-4">CNIC</dt>
                <dd class="col-sm-8">{{ $patient->cnic }}</dd>

                <dt class="col-sm-4">Date of Birth</dt>
                <dd class="col-sm-8">{{ $patient->date_of_birth?->format('d-m-Y') }}</dd>

                <dt class="col-sm-4">Gender</dt>
                <dd class="col-sm-8">{{ $patient->gender }}</dd>

                <dt class="col-sm-4">Phone</dt>
                <dd class="col-sm-8">{{ $patient->phone ?: '—' }}</dd>

                <dt class="col-sm-4">Address</dt>
                <dd class="col-sm-8">{{ $patient->address }}</dd>

                <dt class="col-sm-4">Document Type</dt>
                <dd class="col-sm-8">{{ $patient->document_type_label }}</dd>
            </dl>
        </div>

        @if ($patient->document_image)
            <div class="card-soft p-4 mb-4">
                <h2 class="h5 mb-3">Original Document</h2>
                <a href="{{ route('patients.document', $patient) }}" target="_blank" class="btn btn-outline-primary mb-3">View Image</a>
                <div>
                    <img src="{{ route('patients.document', $patient) }}" alt="Patient document" class="img-fluid rounded border" style="max-height: 420px;">
                </div>
            </div>
        @endif

        <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Back to Patient Records</a>
    </div>
</div>
@endsection
