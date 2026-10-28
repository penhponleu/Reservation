<?php

namespace App\Http\Controllers;

use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Ticket types page: General, VIP... with quota and on/off
class TicketTypeController extends Controller
{
    // list + how many guests have each type
    public function index()
    {
        return TicketType::withCount('registrations')->orderBy('id')->get();
    }

    public function store(Request $request)
    {
        $type = TicketType::create($this->validated($request));

        return response()->json(['message' => 'Ticket type '.$type->name.' added.', 'data' => $type], 201);
    }

    public function update(Request $request, TicketType $ticketType)
    {
        $ticketType->update($this->validated($request, $ticketType));

        return response()->json(['message' => 'Ticket type '.$ticketType->name.' saved.', 'data' => $ticketType]);
    }

    // only delete a type nobody has (otherwise switch it off)
    public function destroy(TicketType $ticketType)
    {
        if ($ticketType->registrations()->exists()) {
            return response()->json(['message' => "Guests have {$ticketType->name} tickets. Switch it off instead of deleting."], 422);
        }
        $ticketType->delete();

        return response()->json(['message' => 'Ticket type '.$ticketType->name.' deleted.']);
    }

    private function validated(Request $request, ?TicketType $type = null): array
    {
        return $request->validate([
            'name'      => ['required', 'string', 'max:50', Rule::unique('ticket_types', 'name')->ignore($type?->id)],
            'quota'     => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);
    }
}
