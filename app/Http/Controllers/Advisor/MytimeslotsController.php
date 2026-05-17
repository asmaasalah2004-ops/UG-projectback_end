<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSlotRequest;
use App\Http\Requests\UpdateSlotRequest;
use Illuminate\Http\Request;
use App\Models\Timeslot;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Advisor;
use App\Models\Appointment;


class MytimeslotsController extends Controller
{
    public function index(Request $request)
    {
        $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
        $advisorId = $advisor->id;

        $semester = Semester::where('is_active', '1')->firstOrFail();

         if (! $semester) {
            return $this->error('No active semester found.', 404);
        }

        $statusFilter = $request->query('status');

        $slotsQuery = Timeslot::with(['appointments'])
            ->where('advisor_id', $advisorId)
            ->where('semester_id', $semester->id)
            ->orderBy('slot_date')
            ->orderBy('start_time');

        if ($statusFilter) {
            $slotsQuery->where('status', $statusFilter);
        }

        $slots = $slotsQuery->get()->map(function (Timeslot $slot) {
            $booked = $slot->appointments
                ->whereNotIn('status', ['Cancelled'])
                ->count();
        $data = [
            'id' => $slot->id,
            'slot_date' => $slot->slot_date,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'status' => $slot->status,
            'booked' => $booked,
            'max' => $slot->max_students,
            'max_students' => $slot->max_students,
        ];

        $data['time_range'] = $data['start_time'] . ' - ' . $data['end_time'];

        unset($data['start_time'], $data['end_time']);

        return $data;
    });
               


        return response()->json([
            'success' => true,
            'semester' => [
                'semester_id' => $semester->id,
                'name' => $semester->semester_name,
                'academic_year' => $semester->academic_year,
            ],
            'filters' => [
                'status' => $statusFilter,
            ],
            'total' => $slots->count(),
            'data' => $slots,
        ]);
    }

    public function store(StoreSlotRequest $request)
    {
        $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
        $advisorId = $advisor->id;

        $semester = Semester::where('is_active', '1')->first();
 
        if (! $semester) {
            return $this->error('No active semester found.', 404);
        }
 
        $validated = $request->validated();
            
 
        if ($this->hasOverlap( $validated)) {
            return $this->error('You already have an overlapping slot on this date and time.', 422);
        }
 
        $slot = Timeslot::create([
            'advisor_id'   => $advisorId,
            'semester_id'  => $semester->id,
            'slot_date'    => $validated['slot_date'],
            'start_time'   => $validated['start_time'],
            'end_time'     => $validated['end_time'],
            'max_students' => $validated['max_students'],
            'status'       => 'Available',
        ]);
 
        $slot->load('appointments');
 
        return response()->json([
            'success' => true,
            'message' => 'Time slot created successfully.',
            'data'    => $this->formatSlot($slot),
        ], 201);
    }

     public function update(UpdateSlotRequest $request, Timeslot $slot)
    {

    
        if (! $this->ownsSlot($slot)) {
            return $this->error('You are not authorized to manage this slot.', 403);
        }
 
       
 
        $slot->load('appointments');
 
        $booked = $slot->appointments
            ->whereNotIn('status', ['Cancelled'])
            ->count();
 
        $validated = $request->validate([
            'start_time'   => ['sometimes', 'date_format:H:i'],
            'end_time'     => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'max_students' => ['sometimes', 'integer', 'min:' . $booked, 'max:20'],
        ], [
            'max_students.min' => "max_students cannot be less than the number already booked ({$booked}).",
        ]);
 
        $merged = array_merge([
            'start_time'   => Carbon::parse($slot->start_time)->format('H:i'),
            'end_time'     => Carbon::parse($slot->end_time)->format('H:i'),
            'max_students' => $slot->max_students,
        ], $validated);
 
        
      $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
    
 
        if ($this->hasOverlap($merged,$slot->id)) {
            return $this->error('This change would create an overlapping slot.', 422);
        }
 
        $newMax = $merged['max_students'];
 
        $slot->update([
            'start_time'   => $merged['start_time'],
            'end_time'     => $merged['end_time'],
            'max_students' => $newMax,
            'status'       => ($booked >= $newMax) ? 'Full' : 'Available',
        ]);
        
        $slot->refresh();

        $slot->load('appointments');

         

 
        return response()->json([
            'success' => true,
            'message' => 'Time slot updated successfully.',
            'data'    => $this->formatSlot($slot),
        ]);
    }

     

 
     private function formatSlot(Timeslot $slot): array
    {
        $booked = $slot->appointments
            ->whereNotIn('status', ['Cancelled'])
            ->count();
 
        return [
            'slot_id'         => $slot->id,
            'date'            => Carbon::parse($slot->slot_date)->format('D, M j'), 
            'date_iso'        => Carbon::parse($slot->slot_date)->toDateString(),   
            'start_time'      => Carbon::parse($slot->start_time)->format('H:i'),  
            'end_time'        => Carbon::parse($slot->end_time)->format('H:i'),    
            'max_students'    => $slot->max_students,
            'booked'          => $booked,
            'available_seats' => max(0, $slot->max_students - $booked),
            'status'          => $slot->status,
        ];
    }

    private function ownsSlot(Timeslot $slot): bool
   {
     $advisor =Advisor::where('user_id', Auth::id())->first();
        $advisorId = $advisor->id;

        if (! $advisor) {
        return false;
    }
 
    return (int)$slot->advisor_id ===(int) $advisorId;
  
  }
  
    

     private function hasOverlap(array $data, ?int $excludeId = null): bool
    {
        $advisor = Advisor::where('user_id', Auth::id())->firstOrFail();
        $advisorId = $advisor->id;

         $start = Carbon::parse($data['start_time'])->format('H:i:s');
         $end= Carbon::parse($data['end_time'])->format('H:i:s');


        $query = Timeslot::where('advisor_id', $advisorId)
        ->whereNotIn('status', ['Cancelled', 'Expired'])
        ->where(function ($q) use ($start, $end) {
            $q->where('start_time', '<', $end)
              ->where('end_time',   '>', $start);
        });
            
 
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
 
        return $query->exists();
    }

     private function error(string $message, int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
 
 

    
}
