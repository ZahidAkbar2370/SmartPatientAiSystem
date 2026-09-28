<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\OcrService;
use App\Services\PatientDocumentParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('cnic', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function createManual(): View
    {
        return view('patients.form', [
            'patient' => new Patient,
            'mode' => 'create',
            'title' => 'Add Patient',
            'ocrWarning' => null,
            'previewImage' => null,
            'tempDocument' => null,
        ]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        if (! empty($data['temp_document'])) {
            $tempPath = $data['temp_document'];
            if (Storage::disk('local')->exists($tempPath) && str_starts_with($tempPath, 'temp-scans/')) {
                $extension = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'jpg';
                $finalPath = 'patient-documents/'.Str::uuid().'.'.$extension;
                Storage::disk('local')->move($tempPath, $finalPath);
                $data['document_image'] = $finalPath;
            }
        }

        unset($data['temp_document']);

        Patient::create($data);

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient record created successfully.');
    }

    public function show(Patient $patient): View
    {
        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        return view('patients.form', [
            'patient' => $patient,
            'mode' => 'edit',
            'title' => 'Edit Patient',
            'ocrWarning' => null,
            'previewImage' => null,
            'tempDocument' => null,
        ]);
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return redirect()
            ->route('patients.show', $patient)
            ->with('success', 'Patient record updated successfully.');
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        if ($patient->document_image) {
            Storage::disk('local')->delete($patient->document_image);
        }

        $patient->delete();

        return redirect()
            ->route('patients.index')
            ->with('success', 'Patient record deleted successfully.');
    }

    public function scan(): View
    {
        return view('patients.scan');
    }

    public function processScan(
        Request $request,
        OcrService $ocrService,
        PatientDocumentParser $parser
    ): View|RedirectResponse {
        $maxKb = config('ocr.max_image_kb', 5120);

        $request->validate([
            'document_type' => ['required', 'in:cnic,driving_license'],
            'document_image' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.$maxKb,
            ],
        ], [
            'document_image.required' => 'Please capture or upload a document image.',
            'document_image.mimes' => 'Please upload a valid image (JPG, PNG, or WEBP).',
            'document_image.max' => 'The selected file is too large.',
            'document_type.required' => 'Please select a document type.',
        ]);

        $file = $request->file('document_image');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $tempRelative = 'temp-scans/'.Str::uuid().'.'.$extension;
        Storage::disk('local')->put($tempRelative, file_get_contents($file->getRealPath()));

        $absolutePath = Storage::disk('local')->path($tempRelative);

        $ocrResult = $ocrService->extractText($absolutePath);
        $extracted = $parser->parse($ocrResult['text'] ?? '', $request->input('document_type'));

        $ocrWarning = null;
        if (! $ocrResult['success'] || ($extracted['extracted_count'] ?? 0) === 0) {
            $ocrWarning = $ocrResult['message']
                ?? 'We could not extract all information from the document. Please check the image quality or enter the missing information manually.';
        } elseif (($extracted['extracted_count'] ?? 0) < 3) {
            $ocrWarning = 'We could not extract all information from the document. Please check the image quality or enter the missing information manually.';
        }

        $patient = new Patient([
            'full_name' => $extracted['full_name'] ?? '',
            'father_husband_name' => $extracted['father_husband_name'] ?? '',
            'cnic' => $extracted['cnic'] ?? '',
            'date_of_birth' => $extracted['date_of_birth'] ?? null,
            'gender' => $extracted['gender'] ?? '',
            'phone' => '',
            'address' => $extracted['address'] ?? '',
            'document_type' => $request->input('document_type'),
        ]);

        return view('patients.form', [
            'patient' => $patient,
            'mode' => 'create',
            'title' => 'Document Scan Result',
            'ocrWarning' => $ocrWarning,
            'previewImage' => route('patients.temp-document', ['path' => encrypt($tempRelative)]),
            'tempDocument' => $tempRelative,
            'fromScan' => true,
        ]);
    }

    public function document(Patient $patient): StreamedResponse|Response
    {
        abort_unless($patient->document_image, 404);
        abort_unless(Storage::disk('local')->exists($patient->document_image), 404);

        return Storage::disk('local')->response($patient->document_image);
    }

    public function tempDocument(Request $request): StreamedResponse|Response|RedirectResponse
    {
        try {
            $path = decrypt($request->query('path'));
        } catch (\Throwable) {
            abort(404);
        }

        if (! is_string($path) || ! str_starts_with($path, 'temp-scans/') || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }
}
