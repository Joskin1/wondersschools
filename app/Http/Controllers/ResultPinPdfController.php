<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ResultPin;
use App\Models\ResultPinBatch;
use App\Services\FrontendLibrary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Facades\Impersonation;

class ResultPinPdfController extends Controller
{
    public function download(ResultPinBatch $batch)
    {
        $user = Auth::user();
        if (! $user || ! $user->isSudo() || Impersonation::isImpersonating()) {
            abort(403, 'Unauthorized access. Only sudo users can print result PINs.');
        }

        $schoolName = FrontendLibrary::getSetting('school_name', config('app.name', 'School'));

        $pins = ResultPin::where('batch_id', $batch->id)
            ->orderBy('id')
            ->get(['id', 'serial_number', 'pin'])
            ->toArray();

        $pages = array_chunk($pins, 40);

        $pdf = Pdf::loadView('pdf.result-pins-a4', [
            'batch'       => $batch,
            'schoolName'  => $schoolName,
            'sessionName' => $batch->session->name ?? 'Session',
            'termName'    => $batch->term->name ?? 'Term',
            'pages'       => $pages,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("{$batch->batch_code}_result_pins.pdf");
    }
}
