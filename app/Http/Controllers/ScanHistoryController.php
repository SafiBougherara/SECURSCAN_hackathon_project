<?php

namespace App\Http\Controllers;

use App\Jobs\RunSecurityScanJob;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanHistoryController extends Controller
{
    /** GET /scans — List current user's scans (admin sees all) */
    public function index()
    {
        $user = Auth::user();

        $scans = $user->is_admin
            ? Scan::with('user')->latest()->paginate(20)
            : Scan::where('user_id', $user->id)->latest()->paginate(20);

        return view('scans.index', compact('scans'));
    }

    /** DELETE /scan/{scan} — Delete a scan and its vulnerabilities */
    public function destroy(Scan $scan)
    {
        $this->authorizeAccess($scan);

        $scan->vulnerabilities()->delete();
        $scan->delete();

        return redirect()->route('scans.index')->with('success', 'Scan deleted.');
    }

    /** POST /scan/{scan}/rerun — Re-run the same scan (overwrites) */
    public function rerun(Scan $scan)
    {
        $this->authorizeAccess($scan);

        // Delete old results
        $scan->vulnerabilities()->delete();
        $scan->update(['status' => 'pending', 'score' => null]);

        // Dispatch job again
        RunSecurityScanJob::dispatch($scan);

        return redirect()->route('scan.loading', $scan)->with('info', 'Re-scan started.');
    }

    /** POST /scan/{scan}/rescan — Create a new scan linked to this one as parent */
    public function rescan(Scan $scan)
    {
        $this->authorizeAccess($scan);

        $newScan = Scan::create([
            'user_id' => Auth::id(),
            'repo_url' => $scan->repo_url,
            'status' => 'pending',
            'parent_scan_id' => $scan->id,
        ]);

        RunSecurityScanJob::dispatch($newScan);

        return redirect()->route('scan.loading', $newScan)->with('info', 'Rescan started. Previous results are preserved.');
    }

    private function authorizeAccess(Scan $scan): void
    {
        $user = Auth::user();
        if (!$user->is_admin && $scan->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }
    }
}
