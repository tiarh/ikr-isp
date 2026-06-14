<?php

namespace App\Http\Controllers\Psb;

use App\Enums\PsbStatus;
use App\Http\Controllers\Controller;
use App\Models\PsbHiosoChecklist;
use App\Models\PsbOrder;
use App\Services\PsbStateMachine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PsbDocumentController extends Controller
{
    public function __construct(private PsbStateMachine $sm) {}

    public function index(PsbOrder $psbOrder): Response
    {
        $psbOrder->load(['hiosoChecklist.checkedBy', 'teknisi']);
        return Inertia::render('Psb/PsbDocuments', [
            'order' => $psbOrder,
        ]);
    }

    public function uploadPhoto(Request $request, PsbOrder $psbOrder, string $type): RedirectResponse
    {
        $allowed = [
            'rumah'    => 'foto_rumah_path',
            'modem'    => 'foto_modem_path',
            'ktp'      => 'foto_ktp_path',
            'odp'      => 'foto_odp_path',
            'odp_dalam'=> 'foto_odp_dalam_path',
            'router'   => 'foto_router_path',
        ];
        if (! isset($allowed[$type])) {
            return back()->with('error', 'Invalid photo type');
        }

        $request->validate([
            'photo' => 'required|image|max:8192', // 8MB
        ]);
        $path = $request->file('photo')->store("photos/{$psbOrder->id}", 'public');
        $psbOrder->update([$allowed[$type] => $path]);

        // Auto-transition ke photos setelah semua foto uploaded
        if ($psbOrder->status === PsbStatus::Provisioning && $psbOrder->isAllPhotosUploaded()) {
            $this->sm->transition($psbOrder, PsbStatus::Photos, $request->user(), 'All photos uploaded');
        }

        return back()->with('success', "Foto {$type} uploaded");
    }

    /**
     * API version — return JSON, dipanggil via fetch dari React (PsbDocuments.tsx).
     */
    public function uploadPhotoApi(Request $request, PsbOrder $psbOrder, string $type): JsonResponse
    {
        $allowed = [
            'rumah'    => 'foto_rumah_path',
            'modem'    => 'foto_modem_path',
            'ktp'      => 'foto_ktp_path',
            'odp'      => 'foto_odp_path',
            'odp_dalam'=> 'foto_odp_dalam_path',
            'router'   => 'foto_router_path',
        ];
        if (! isset($allowed[$type])) {
            return response()->json(['success' => false, 'error' => 'Invalid photo type'], 400);
        }
        $request->validate([
            'photo' => 'required|image|max:8192',
        ]);
        $path = $request->file('photo')->store("photos/{$psbOrder->id}", 'public');
        $psbOrder->update([$allowed[$type] => $path]);
        if ($psbOrder->status === PsbStatus::Provisioning && $psbOrder->isAllPhotosUploaded()) {
            $this->sm->transition($psbOrder, PsbStatus::Photos, $request->user(), 'All photos uploaded');
        }
        return response()->json([
            'success'      => true,
            'path'         => $path,
            'url'          => Storage::disk('public')->url($path),
            'order_status' => $psbOrder->status->value,
        ]);
    }

    public function updateMeasurements(Request $request, PsbOrder $psbOrder): RedirectResponse
    {
        $data = $request->validate([
            'redaman_odp'    => 'required|numeric',
            'redaman_router' => 'required|numeric',
            'gps_lat'        => 'required|numeric',
            'gps_long'       => 'required|numeric',
        ]);
        $psbOrder->update($data);
        return back()->with('success', 'Measurements updated');
    }

    public function toggleHiOSChecklist(Request $request, PsbHiosoChecklist $checklist): JsonResponse
    {
        $data = $request->validate([
            'is_checked' => 'required|boolean',
            'notes'      => 'nullable|string',
        ]);
        $checklist->update([
            'is_checked'  => $data['is_checked'],
            'notes'       => $data['notes'] ?? null,
            'checked_by'  => $data['is_checked'] ? $request->user()->id : null,
            'checked_at'  => $data['is_checked'] ? now() : null,
        ]);
        return response()->json(['success' => true, 'checklist' => $checklist]);
    }

    public function uploadBai(Request $request, PsbOrder $psbOrder): RedirectResponse
    {
        $request->validate([
            'signature' => 'required|string', // base64 canvas image
        ]);

        // Decode base64 → save image
        $signatureData = $request->input('signature');
        if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $m)) {
            $signatureData = substr($signatureData, strpos($signatureData, ',') + 1);
            $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        } else {
            $ext = 'png';
        }
        $signatureImage = base64_decode($signatureData);
        $sigPath = "bai/{$psbOrder->id}/signature.{$ext}";
        Storage::disk('public')->put($sigPath, $signatureImage);

        // Generate BAI PDF
        $psbOrder->load('teknisi');
        $pdf = Pdf::loadView('pdf.bai', [
            'order'      => $psbOrder,
            'signature'  => Storage::disk('public')->url($sigPath),
        ]);
        $pdfPath = "bai/{$psbOrder->id}/bai.pdf";
        Storage::disk('public')->put($pdfPath, $pdf->output());

        $psbOrder->update([
            'bai_pdf_path'  => $pdfPath,
            'bai_signed_at' => now(),
        ]);

        return back()->with('success', 'BAI PDF generated');
    }
}
