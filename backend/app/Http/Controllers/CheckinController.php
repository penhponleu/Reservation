<?php

namespace App\Http\Controllers;

use App\Models\CheckinLog;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    // Staff scans QR code
    public function scan(Request $request)
    {
        $data   = $request->validate(['value' => 'required|string']);
        $value  = mb_substr(trim($data['value']), 0, 255);
        $userId = $request->user()->id;

        // transaction + lock = the same QR scanned twice at once can't both succeed
        return DB::transaction(function () use ($value, $userId) {
            // SELECT registrations by token from QR
            $registration = Registration::where('token', $value)->lockForUpdate()->first();

            // Found? No → INSERT checkin_logs result = invalid → "Not found"
            if (! $registration) {
                $this->log(null, $userId, 'invalid', $value);

                return response()->json(['result' => 'invalid', 'message' => 'Not found']);
            }

            // checked_in_at empty? No → INSERT checkin_logs already_checked_in
            if ($registration->checked_in_at) {
                $this->log($registration->id, $userId, 'already_checked_in', $value);

                return response()->json([
                    'result'       => 'already_checked_in',
                    'message'      => 'Already checked in at '.$registration->checked_in_at->format('d M Y H:i'),
                    'registration' => $registration,
                ]);
            }

            // Yes → UPDATE registrations checked_in_at = now
            $registration->update(['checked_in_at' => now()]);

            // → INSERT checkin_logs result = success, user_id = staff → "Welcome!"
            $this->log($registration->id, $userId, 'success', $value);

            return response()->json([
                'result'       => 'success',
                'message'      => 'Welcome!',
                'registration' => $registration,
            ]);
        });
    }

    // Recent scans for admin
    public function index()
    {
        return CheckinLog::with(['registration:id,username,email', 'user:id,username'])
            ->orderByDesc('scanned_at')
            ->limit(30)
            ->get();
    }

    private function log(?int $registrationId, int $userId, string $result, string $value): void
    {
        CheckinLog::create([
            'registration_id' => $registrationId,
            'user_id'         => $userId,
            'result'          => $result,
            'scanned_value'   => $value,
            'scanned_at'      => now(),
        ]);
    }
}
