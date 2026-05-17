<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Advisor;
use Illuminate\Support\Facades\Auth;
use App\Models\Timeslot;

class AppointmentsController extends Controller
{
     public function advisor_appointments(Request $request)
    {

     $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
     $advisorId = $advisor->id;
     
        $query = Appointment::with([
            'student:id,name',
            'timeslot:id,slot_date,start_time,end_time',
            'semester:id,semester_name,academic_year',
            'student.riskEvaluations',
        ])
        ->whereHas('timeslot', fn($q) => $q->where('advisor_id', $advisorId));

       
        if ($request->filled('search')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

          $appointments = $query
            ->orderBy('booking_date', 'desc')
            ->get()
            ->map(function ($appt) {
                return [
                    'id'             => $appt->id,
                    'student'        => [
                        'id'         => $appt->student->id,
                        'name'       => $appt->student->name,
                        'initials'   => $this->get_initials($appt->student->name),
                        'risk_level' => $appt->student->riskEvaluations->last()?->risk_level ?? 'low'
                    ],
                    'date'           => optional($appt->timeslot)->slot_date,
                    'time'           => optional($appt->timeslot)->start_time . ' - ' . optional($appt->timeslot)->end_time,
                    'booking_date'   => $appt->booking_date,
                    'status'         => $appt->status,
                    'notes'          => $appt->notes,
                ];
            });
 
        return response()->json($appointments);
 
    }

     public function advisor_stats()
    {

     $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
     $advisorId = $advisor->id;

        $base = Appointment::whereHas('timeslot', fn($q) => $q->where('advisor_id', $advisorId));
 
        $stats = [
            'upcoming'  => (clone $base)->where('status', 'Booked')->count(),
            'attended'  => (clone $base)->where('status', 'Attended')->count(),
            'cancelled' => (clone $base)->where('status', 'Cancelled')->count(),
        ];
 
        return response()->json($stats);
    }

     public function update_status(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Attended,Cancelled',
        ]);
 
        $appointment = Appointment::findOrFail($id);
 
        
        if ($appointment->status !== 'Booked') {
            return response()->json([
                'message' => 'only booked appointments can be updated',
            ], 422);
        }
 
    $newStatus = match ($request->status) {
    'Attended' => 'Attended',
    'Cancelled'=> 'Cancelled',
     };

    $appointment->update([
    'status' => $newStatus,
   ]);
 
    
        if (in_array($request->status, ['Cancelled'])) {
            $slot = Timeslot::find($appointment->slot_id);
            if ($slot && $slot->status === 'Full') {
                $slot->update(['status' => 'Available']);
            }
        }
 
        return response()->json([
            'message'     => 'status updated successfully',
            'appointment' => $appointment,
        ]);
    }
 




     private function get_initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper($part[0]);
        }
        return $initials;
    }
 
}
