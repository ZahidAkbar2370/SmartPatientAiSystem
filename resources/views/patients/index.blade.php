@extends('layouts.app')

@section('title', 'Patient Records')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <h1 class="h3 page-title mb-0">Patient Records</h1>
    <a href="{{ route('patients.create') }}" class="btn btn-primary">+ Add Patient</a>
</div>

<div class="card-soft p-3 p-md-4">
    <form method="GET" action="{{ route('patients.index') }}" class="row g-2 mb-3">
        <div class="col-md-8">
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Search patient by name or CNIC..."
            >
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">Search</button>
            @if ($search !== '')
                <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Name</th>
                    <th>CNIC</th>
                    <th>DOB</th>
                    <th style="width: 160px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $index => $patient)
                    <tr>
                        <td>{{ $patients->firstItem() + $index }}</td>
                        <td>{{ $patient->full_name }}</td>
                        <td>{{ $patient->cnic }}</td>
                        <td>{{ $patient->date_of_birth?->format('d-m-Y') }}</td>
                        <td>
                            <a href="{{ route('patients.show', $patient) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('patients.edit', $patient) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            @if ($search !== '')
                                No patients matched your search.
                            @else
                                No patient records yet. Click Add Patient to create one.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($patients->hasPages())
        <div class="mt-3">
            {{ $patients->links() }}
        </div>
    @endif
</div>
@endsection
