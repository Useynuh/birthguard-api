<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Vaccination;
use Illuminate\Http\Request;

class VaccinationController extends Controller
{
    /**
     * Check whether the authenticated user owns the child.
     */
    private function authorizeChild(Child $child)
    {
        if ($child->parent_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this child.'
            ], 403);
        }

        return null;
    }

    /**
     * Get all vaccinations for a child.
     */
    public function index(Child $child)
    {
        if ($response = $this->authorizeChild($child)) {
            return $response;
        }

        $vaccinations = $child->vaccinations()
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        return response()->json([
            'success' => true,
            'vaccinations' => $vaccinations
        ]);
    }

    /**
     * Add a vaccination for a child.
     */
  public function store(Request $request, Child $child)
{
    $validated = $request->validate([
        'vaccine_name' => 'required|string|max:255',
        'date' => 'required|date',
        'time' => 'nullable|date_format:H:i',
        'is_administered' => 'nullable|boolean',
    ]);

    $vaccination = Vaccination::create([
        'child_id' => $child->id,
        'vaccine_name' => $validated['vaccine_name'],
        'date' => $validated['date'],
        'time' => $validated['time'] ?? null,
        'is_administered' => $validated['is_administered'] ?? false,
    ]);

    return response()->json([
        'message' => 'Vaccination created successfully.',
        'vaccination' => $vaccination,
    ], 201);
}
    /**
     * Show one vaccination.
     */
    public function show(Child $child, Vaccination $vaccination)
    {
        if ($response = $this->authorizeChild($child)) {
            return $response;
        }

        if ($vaccination->child_id !== $child->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vaccination does not belong to this child.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'vaccination' => $vaccination
        ]);
    }

    /**
     * Update a vaccination.
     */
    public function update(
        Request $request,
        Child $child,
        Vaccination $vaccination
    ) {
        if ($response = $this->authorizeChild($child)) {
            return $response;
        }

        if ($vaccination->child_id !== $child->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vaccination does not belong to this child.'
            ], 404);
        }

        $validated = $request->validate([
            'vaccine_name' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'time' => 'sometimes|date_format:H:i',
            'is_administered' => 'sometimes|boolean',
        ]);

        $vaccination->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vaccination updated successfully.',
            'vaccination' => $vaccination
        ]);
    }

    /**
     * Mark vaccination as administered.
     */
    public function markAdministered(
    Child $child,
    Vaccination $vaccination
) {
    if ($response = $this->authorizeChild($child)) {
        return $response;
    }

    if ($vaccination->child_id !== $child->id) {
        return response()->json([
            'success' => false,
            'message' => 'Vaccination does not belong to this child.'
        ], 404);
    }

    $vaccination->update([
        'is_administered' => true
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Vaccination marked as administered.',
        'vaccination' => $vaccination
    ]);
}

    /**
     * Delete a vaccination.
     */
    public function destroy(
        Child $child,
        Vaccination $vaccination
    ) {
        if ($response = $this->authorizeChild($child)) {
            return $response;
        }

        if ($vaccination->child_id !== $child->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vaccination does not belong to this child.'
            ], 404);
        }

        $vaccination->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vaccination deleted successfully.'
        ]);
    }

/**
 * Get upcoming/pending vaccinations for the authenticated health worker.
 *
 * Only vaccinations belonging to children registered by
 * the authenticated health worker are returned.
 */
public function healthWorkerUpcoming(Request $request)
{
    $user = $request->user();

    // Only health workers can use this endpoint.
    if ($user->role !== 'health_worker') {
        return response()->json([
            'success' => false,
            'message' => 'Only health workers can access this endpoint.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Identify the health worker
    |--------------------------------------------------------------------------
    |
    | When a child is registered, ChildController stores:
    |
    | registered_by = user's email OR phone
    |
    */

    $registeredBy = $user->email ?? $user->phone;

    /*
    |--------------------------------------------------------------------------
    | Get vaccinations belonging to children registered by this worker
    |--------------------------------------------------------------------------
    */

    $vaccinations = Vaccination::whereHas('child', function ($query) use ($registeredBy) {
        $query->where('registered_by', $registeredBy);
    })
        ->where('is_administered', false)
        ->whereDate('date', '>=', now()->toDateString())
        ->with('child')
        ->orderBy('date')
        ->orderBy('time')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Format response for Flutter
    |--------------------------------------------------------------------------
    */

    $result = $vaccinations->map(function ($vaccination) {
        return [
            'id' => $vaccination->id,
            'child_id' => $vaccination->child_id,
            'child_name' => $vaccination->child?->full_name,
            'vaccine_name' => $vaccination->vaccine_name,
            'date' => $vaccination->date,
            'time' => $vaccination->time,
            'is_administered' => $vaccination->is_administered,
        ];
    })->values();

    return response()->json([
        'success' => true,
        'count' => $result->count(),
        'vaccinations' => $result,
    ]);
}
}