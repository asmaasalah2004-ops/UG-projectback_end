<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timeslot;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;


class Bookingcontroller extends Controller
{

    public function getAvailableDays(int $month, int $year)
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (!$student || !$student->advisor_id) {
            return response()->json([]);
        }

        $days = Timeslot::where('advisor_id', $student->advisor_id)
            ->whereNotIn('status', ['Full', 'Expired', 'Cancelled'])
            ->whereMonth('slot_date', $month)
            ->whereYear('slot_date', $year)
            ->distinct()
            ->pluck('slot_date');

        return response()->json($days);
    }

    public function getSlotsByDate(string $date)
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (!$student || !$student->advisor_id) {
            return response()->json([]);
        }

        $slots = Timeslot::where('advisor_id', $student->advisor_id)
            ->whereDate('slot_date', $date)
            ->whereNotIn('status', ['Full', 'Expired', 'Cancelled'])
            ->get();

        return response()->json($slots);
    }

    public function book(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->first();

        $slot = Timeslot::find($request->slot_id);

        if (!$slot) {
            return response()->json(['message' => 'Slot not found'], 404);
        }


        if (in_array($slot->status, ['Full', 'Expired'])) {
            return response()->json(['message' => 'Already booked'], 400);
        }

       
        $exists = Appointment::where('student_id', $student->id)
            ->where('slot_id', $slot->id)
            ->where('status', '!=', 'Cancelled')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already booked'], 400);
        }

       
        Appointment::create([
            'student_id' => $student->id,
            'slot_id' => $slot->id,
            'semester_id' => $slot->semester_id,
            'booking_date' => now(),
            'department_id' =>$student->department_id,
            'notes' => $request->notes,
            'status' => 'Booked'
        ]);

        
        $count = Appointment::where('slot_id', $slot->id)
            ->where('status', '!=', 'Cancelled')
            ->count();

        
        if ($count >= $slot->max_students) {
            $slot->update(['status' => 'Full']);
        }

        return response()->json([
            'message' => 'Booked successfully'
        ]);
    }
    
}
