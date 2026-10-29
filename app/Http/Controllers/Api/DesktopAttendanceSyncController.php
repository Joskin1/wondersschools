<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceRecord;
use App\Models\DesktopDevice;
use App\Models\Session;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesktopAttendanceSyncController extends Controller
{
    /**
     * Ingest batch attendance logs from offline desktop kiosk.
     */
    public function syncBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
            'records' => ['required', 'array'],
            'records.*.central_id' => ['required', 'integer'],
            'records.*.user_type' => ['required', 'string', 'in:student,staff'],
            'records.*.date' => ['required', 'date_format:Y-m-d'],
            'records.*.arrival_time' => ['nullable', 'string'],
            'records.*.departure_time' => ['nullable', 'string'],
        ]);

        /** @var DesktopDevice|null $device */
        $device = $request->attributes->get('desktop_device');

        $sessionId = $validated['session_id'] ?? Session::where('is_active', true)->value('id');
        $termId = $validated['term_id'] ?? Term::where('is_active', true)->value('id');

        if (! $sessionId || ! $termId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Active academic session and term must be defined or supplied in request.',
            ], 422);
        }

        $records = $validated['records'];
        $syncedCount = 0;

        DB::transaction(function () use ($records, $sessionId, $termId, $device, &$syncedCount) {
            // Pre-load student IDs in this batch
            $studentIds = collect($records)
                ->where('user_type', 'student')
                ->pluck('central_id')
                ->unique()
                ->all();

            $students = Student::query()
                ->whereIn('id', $studentIds)
                ->with([
                    'enrollments' => fn ($q) => $q->where('session_id', $sessionId),
                    'enrollments.classroom',
                ])
                ->get()
                ->keyBy('id');

            foreach ($records as $item) {
                if ($item['user_type'] === 'student') {
                    $student = $students->get($item['central_id']);
                    if (! $student) {
                        continue;
                    }

                    $enrollment = $student->enrollments->first() ?? $student->currentEnrollment();
                    if (! $enrollment || ! $enrollment->classroom_id) {
                        // If no enrollment exists for session, cannot assign to class roll call
                        continue;
                    }

                    $attendance = Attendance::firstOrCreate(
                        [
                            'session_id' => $sessionId,
                            'term_id' => $termId,
                            'classroom_id' => $enrollment->classroom_id,
                            'class_arm_id' => $enrollment->class_arm_id,
                            'department_id' => $enrollment->department_id,
                            'date' => $item['date'],
                        ],
                        [
                            'recorded_by' => $device?->paired_by,
                        ]
                    );

                    $recordRow = AttendanceRecord::firstOrNew([
                        'attendance_id' => $attendance->id,
                        'student_id' => $student->id,
                    ]);

                    $recordRow->status = 'present';
                    if (! empty($item['arrival_time'])) {
                        $recordRow->arrival_time = $item['arrival_time'];
                    }
                    if (! empty($item['departure_time'])) {
                        $recordRow->departure_time = $item['departure_time'];
                    }
                    $recordRow->save();

                    $syncedCount++;
                } elseif ($item['user_type'] === 'staff') {
                    $staffRecord = StaffAttendance::firstOrNew([
                        'user_id' => $item['central_id'],
                        'date' => $item['date'],
                    ]);

                    $staffRecord->session_id = $sessionId;
                    $staffRecord->term_id = $termId;
                    $staffRecord->status = 'present';
                    if (! empty($item['arrival_time'])) {
                        $staffRecord->arrival_time = $item['arrival_time'];
                    }
                    if (! empty($item['departure_time'])) {
                        $staffRecord->departure_time = $item['departure_time'];
                    }
                    if ($device) {
                        $staffRecord->recorded_by_device_id = $device->id;
                    }
                    $staffRecord->save();

                    $syncedCount++;
                }
            }
        });

        if ($device) {
            $device->recordSync($request->ip());
        }

        return response()->json([
            'status' => 'success',
            'synced_count' => $syncedCount,
            'synced_at' => now()->toDateTimeString(),
        ]);
    }
}
