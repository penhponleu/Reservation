<?php

namespace App\Http\Controllers;

use App\Models\CheckinLog;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    // Staff scans QR → success / already_checked_in / invalid
    public function scan(Request $request)
    {
        $data  = $request->validate(['value' => 'required|string']);
        $value = mb_substr(trim($data['value']), 0, 255);

        // transaction + lock → same QR scanned twice at once can't both succeed
        return DB::transaction(function () use ($request, $value) {
            $registration = Registration::where('token', $value)->lockForUpdate()->first();

            if (! $registration) {
                $result  = 'invalid';
                $message = 'Not found';
            } elseif ($registration->checked_in_at) {
                $result  = 'already_checked_in';
                $message = 'Already checked in at '.$registration->checked_in_at->format('d M Y H:i');
            } else {
                $registration->update(['checked_in_at' => now()]);
                $result  = 'success';
                $message = 'Welcome!';
            }

            // save every scan
            CheckinLog::create([
                'registration_id' => $registration?->id,
                'user_id'         => auth('sanctum')->id(), // null = scanned without login
                'result'          => $result,
                'scanned_value'   => $value,
                'scanned_at'      => now(),
            ]);

            return response()->json([
                'result'       => $result,
                'message'      => $message,
                'registration' => $registration,
            ]);
        });
    }

    // Last 30 scans for admin
    public function index()
    {
        return CheckinLog::with(['registration:id,fullname,email', 'user:id,fullname'])
            ->orderByDesc('scanned_at')
            ->limit(30)
            ->get();
    }
}
