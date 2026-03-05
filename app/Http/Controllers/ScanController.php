<?php

namespace App\Http\Controllers;

use App\Jobs\RunSecurityScanJob;
use App\Models\Scan;
use App\Services\AiFixService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    /** GET / — Home page with URL form */
    public function home()
    {
        return view('home');
    }

    /** POST /scan — Submit a repo URL, dispatch the job */
    public function store(Request $request)
    {
        $request->validate([
            'repo_url' => ['required', 'url', 'regex:/github\.com/'],
        ], [
            'repo_url.regex' => 'Only GitHub repositories are supported.',
        ]);

        $scan = Scan::create([
            'user_id' => auth()->id(), // null if guest
            'repo_url' => $request->input('repo_url'),
            'status' => 'pending',
        ]);

        RunSecurityScanJob::dispatch($scan);

        return redirect()->route('scan.loading', $scan);
    }

    /** GET /scan/{scan}/loading — Loading animation page */
    public function loading(Scan $scan)
    {
        return view('loading', compact('scan'));
    }

    /** GET /scan/{scan}/status — JSON polling endpoint */
    public function status(Scan $scan)
    {
        return response()->json([
            'status' => $scan->status,
            'redirect' => $scan->status === 'failed'
                ? route('home')
                : route('scan.dashboard', $scan),
        ]);
    }

    /** GET /scan/{scan}/dashboard — Full results dashboard */
    public function dashboard(Scan $scan)
    {
        if ($scan->status === 'failed') {
            return view('scan-failed', compact('scan'));
        }

        if ($scan->status !== 'done') {
            return redirect()->route('scan.loading', $scan);
        }

        $vulnerabilities = $scan->vulnerabilities()
            ->orderByRaw("CASE severity 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
                WHEN 'info' THEN 5 
                ELSE 6 
            END")
            ->get();

        // Stats for charts
        $bySeverity = $vulnerabilities->groupBy('severity')->map(fn($g) => $g->count());
        $byOwasp = $vulnerabilities->groupBy('owasp_category')->map(fn($g) => $g->count())->sortDesc()->take(10);

        // Parent scan for comparison
        $parentScan = $scan->parent_scan_id ? $scan->parent()->with('vulnerabilities')->first() : null;

        return view('dashboard', compact('scan', 'vulnerabilities', 'bySeverity', 'byOwasp', 'parentScan'));
    }

    /** GET /scan/{scan}/pdf — Export PDF report */
    public function exportPdf(Scan $scan)
    {
        if ($scan->status !== 'done') {
            abort(400, 'Scan not yet complete.');
        }

        $vulnerabilities = $scan->vulnerabilities()->orderByRaw("FIELD(severity,'critical','high','medium','low','info')")->get();

        // Use only cached AI fixes — do NOT call Gemini in mass here (rate limits)
        // Fixes are generated on-demand via the Detail modal (VulnerabilityController@fix)
        $vulnerabilities = $vulnerabilities->fresh(); // reload with ai_fix

        $bySeverity = $vulnerabilities->groupBy('severity')->map(fn($g) => $g->count());
        $byOwasp = $vulnerabilities->groupBy('owasp_category')->map(fn($g) => $g->count())->sortDesc()->take(10);

        $pdf = Pdf::loadView('pdf.report', compact('scan', 'vulnerabilities', 'bySeverity', 'byOwasp'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        $filename = 'SecureScan_' . preg_replace('/[^a-z0-9]/i', '_', $scan->repo_name ?? $scan->id) . '_' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}
