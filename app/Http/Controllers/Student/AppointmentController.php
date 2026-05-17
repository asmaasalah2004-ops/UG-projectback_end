<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Timeslot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;

class AppointmentController extends Controller
{
   
    public function index(Request $request)
    {
        $student = Student::where('user_id', Auth::id())->first(); 

        $query = Appointment::with(['timeslot.advisor'])
            ->where('student_id', $student->id);

       
        if ($request->filled('status') && $request->status !== 'All Status') {
            $query->where('status', $request->status);
        }

        $appointments = $query
            ->join('time_slots', 'appointments.slot_id', '=', 'time_slots.id')
            ->orderByDesc('time_slots.slot_date')
            ->select('appointments.*')
            ->get()
            ->map(fn($a) => $this->formatAppointment($a));

       
        $base = Appointment::where('student_id', $student->id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'appointments' => $appointments,
                'counters'     => [
                    'upcoming'  => (clone $base)->where('status', 'Booked')->count(),
                    'attended'  => (clone $base)->where('status', 'Attended')->count(),
                    'cancelled' => (clone $base)->where('status', 'Cancelled')->count(),
                ],
            ],
        ]);
    }

  
    public function cancel($id)
    {
        $student = Student::where('user_id', Auth::id())->first();

        $appointment = Appointment::where('id', $id)
            ->where('student_id', $student->id)   
            ->where('status', 'Booked')                    
            ->first();

        if (!$appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment not found or cannot be cancelled.',
            ], 404);
        }

        $appointment->update(['status' => 'Cancelled']);

         $slot = $appointment->timeslot;

        if ($slot) {
        $slot->update([
            'status' => 'Available'
        ]);
       }



        
        $base = Appointment::where('student_id', $student->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Appointment cancelled successfully.',
            'data'    => [
                'appointment' => $this->formatAppointment($appointment->fresh(['timeslot.advisor'])),
                'counters'    => [
                    'upcoming'  => (clone $base)->where('status', 'Booked')->count(),
                    'attended'  => (clone $base)->where('status', 'Attended')->count(),
                    'cancelled' => (clone $base)->where('status', 'Cancelled')->count(),
                  
                ],
            ],
        ]);
    }

    

   
    private function formatAppointment(Appointment $appointment): array
    {
        $slot = $appointment->timeslot;

        return [
            'appointment_id' => $appointment->id,
            'status'         => $appointment->status,
            'booking_date'   => $appointment->booking_date,
            'notes'          => $appointment->notes,
            'slot'           => $slot ? [
                'slot_id'    => $slot->id,
                'date'       => $slot->slot_date,
                'start_time' => $slot->start_time,
                'end_time'   => $slot->end_time,
                'advisor'    => $slot->advisor ? [
                    'id'   => $slot->advisor->id,
                    'name' => $slot->advisor->name,
                ] : null,
            ] : null,
        ];
    }
}