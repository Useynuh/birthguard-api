<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Vaccination;
use Illuminate\Http\Request;

class HealthWorkerDashboardController extends Controller
{
    /**
     * Health Worker Dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get children registered by this health worker
        $children = Child::where('registered_by', $user->email ?? $user->phone)
            ->orderByDesc('id')
            ->get();

        $childIds = $children->pluck('id');

        // Upcoming / pending vaccinations
        $upcomingVaccinations = Vaccination::whereIn('child_id', $childIds)
            ->where('is_administered', false)
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        // Number of children
        $registeredBabies = $children->count();

        // For now, growth checks will use 0
        // until we connect the GrowthCheck model/table.
        $growthChecks = 0;

        // Number of different communities
        // We will adjust this once we confirm the exact
        // community field in your Child table.
        $communities = $children
            ->pluck('place_of_birth')
            ->filter()
            ->unique()
            ->count();

        return response()->json([
            'success' => true,

            'summary' => [
                'registered_babies' => $registeredBabies,
                'upcoming_vaccinations' => $upcomingVaccinations->count(),
                'growth_checks' => $growthChecks,
                'communities' => $communities,
            ],

            'registered_births' => $children,

            'upcoming_vaccinations_list' => $upcomingVaccinations,
        ]);
    }
}