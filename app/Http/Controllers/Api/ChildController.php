<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    /**
     * Register a new child.
     *
     * Parent:
     * - Registers their own child.
     *
     * Health Worker:
     * - Registers a child on behalf of a parent.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'gender' => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'weight_at_birth' => 'required|numeric|min:0',
            'height_at_birth' => 'required|numeric|min:0',

            // Required when a health worker registers a baby.
            'parent_id' => 'nullable|integer|exists:users,id',
        ]);

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Determine parent
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'parent') {

            // Parent is registering their own child.
            $parentId = $user->id;

        } elseif ($user->role === 'health_worker') {

            // Health worker must provide the parent ID.
            if (empty($validated['parent_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent ID is required when a health worker registers a child.',
                ], 422);
            }

            $parentId = $validated['parent_id'];

        } else {

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to register a child.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Create child
        |--------------------------------------------------------------------------
        */

        $child = Child::create([
            'parent_id' => $parentId,

            'full_name' => $validated['full_name'],
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'place_of_birth' => $validated['place_of_birth'],
            'weight_at_birth' => $validated['weight_at_birth'],
            'height_at_birth' => $validated['height_at_birth'],

            // Always record who actually registered the birth.
            'registered_by' => $user->email ?? $user->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Child birth registered successfully.',
            'child' => $child,
        ], 201);
    }


    /**
     * Get children relevant to the authenticated user.
     *
     * Parent:
     *     Returns their own children.
     *
     * Health Worker:
     *     Returns children registered by them.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Parent
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'parent') {

            $children = Child::where(
                'parent_id',
                $user->id
            )
                ->orderByDesc('id')
                ->get();

        }

        /*
        |--------------------------------------------------------------------------
        | Health Worker
        |--------------------------------------------------------------------------
        */

        elseif ($user->role === 'health_worker') {

            $registeredBy = $user->email ?? $user->phone;

            $children = Child::where(
                'registered_by',
                $registeredBy
            )
                ->orderByDesc('id')
                ->get();

        }

        /*
        |--------------------------------------------------------------------------
        | Unauthorized role
        |--------------------------------------------------------------------------
        */

        else {

            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access children.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'children' => $children,
        ]);
    }


    /**
     * Get one child.
     *
     * Parent:
     *     Can access their own child.
     *
     * Health Worker:
     *     Can access a child they registered.
     */
    public function show(Request $request, Child $child)
    {
        $user = $request->user();

        $authorized = false;

        /*
        |--------------------------------------------------------------------------
        | Parent authorization
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'parent') {

            $authorized =
                $child->parent_id === $user->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Health Worker authorization
        |--------------------------------------------------------------------------
        */

        elseif ($user->role === 'health_worker') {

            $registeredBy =
                $user->email ?? $user->phone;

            $authorized =
                $child->registered_by === $registeredBy;
        }

        if (!$authorized) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to access this child.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'child' => $child,
        ]);
    }


    /**
     * Update a child.
     *
     * Parents can update their own children.
     * Health workers can update children they registered.
     */
    public function update(Request $request, Child $child)
    {
        $user = $request->user();

        $authorized = false;

        /*
        |--------------------------------------------------------------------------
        | Parent
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'parent') {

            $authorized =
                $child->parent_id === $user->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Health Worker
        |--------------------------------------------------------------------------
        */

        elseif ($user->role === 'health_worker') {

            $registeredBy =
                $user->email ?? $user->phone;

            $authorized =
                $child->registered_by === $registeredBy;
        }

        if (!$authorized) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to update this child.',
            ], 403);
        }

        $validated = $request->validate([
            'full_name' =>
                'sometimes|required|string|max:255',

            'gender' =>
                'sometimes|required|string|max:50',

            'date_of_birth' =>
                'sometimes|required|date',

            'place_of_birth' =>
                'sometimes|required|string|max:255',

            'weight_at_birth' =>
                'sometimes|required|numeric|min:0',

            'height_at_birth' =>
                'sometimes|required|numeric|min:0',
        ]);

        $child->update($validated);

        return response()->json([
            'success' => true,
            'message' =>
                'Child information updated successfully.',
            'child' => $child->fresh(),
        ]);
    }


    /**
     * Delete a child.
     *
     * Parents can delete their own children.
     * Health workers can delete children they registered.
     */
    public function destroy(Request $request, Child $child)
    {
        $user = $request->user();

        $authorized = false;

        /*
        |--------------------------------------------------------------------------
        | Parent
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'parent') {

            $authorized =
                $child->parent_id === $user->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Health Worker
        |--------------------------------------------------------------------------
        */

        elseif ($user->role === 'health_worker') {

            $registeredBy =
                $user->email ?? $user->phone;

            $authorized =
                $child->registered_by === $registeredBy;
        }

        if (!$authorized) {

            return response()->json([
                'success' => false,
                'message' =>
                    'You are not authorized to delete this child.',
            ], 403);
        }

        $child->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Child deleted successfully.',
        ]);
    }
}
