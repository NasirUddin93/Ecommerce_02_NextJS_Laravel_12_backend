<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
     /**
     * Display all coupons
     */
    public function index()
    {
        $coupons = Coupon::with('assignedUser')->get();

        return response()->json([
            'status' => 200,
            'data' => $coupons,
        ]);
    }

    /**
     * Store a new coupon
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:coupons,code',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'visibility' => 'required|in:public,private,activity',
            'assigned_user_id' => 'required_if:visibility,private|nullable|exists:users,id',
            'activity_type' => 'required_if:visibility,activity|nullable|string|max:100',
            'activity_threshold' => 'required_if:visibility,activity|nullable|integer|min:1',
            'activity_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if ($validated['visibility'] === 'public') {
            $validated['assigned_user_id'] = null;
            $validated['activity_type'] = null;
            $validated['activity_threshold'] = 1;
            $validated['activity_description'] = null;
        } elseif ($validated['visibility'] === 'private') {
            $validated['activity_type'] = null;
            $validated['activity_threshold'] = 1;
            $validated['activity_description'] = null;
        } elseif ($validated['visibility'] === 'activity') {
            $validated['assigned_user_id'] = null;
        }

        $coupon = Coupon::create($validated);
        $coupon->load('assignedUser');

        return response()->json([
            'status' => 201,
            'message' => 'Coupon created successfully',
            'data' => $coupon,
        ]);
    }

    /**
     * Show a single coupon
     */
    public function show($id)
    {
        $coupon = Coupon::with('assignedUser')->find($id);

        if (!$coupon) {
            return response()->json([
                'status' => 404,
                'message' => 'Coupon not found',
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $coupon,
        ]);
    }

    /**
     * Update an existing coupon
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'status' => 404,
                'message' => 'Coupon not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:50|unique:coupons,code,' . $coupon->id,
            'description' => 'nullable|string',
            'discount_type' => 'sometimes|required|in:percentage,fixed',
            'discount_value' => 'sometimes|required|numeric|min:0',
            'min_purchase_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'usage_limit' => 'nullable|integer|min:0',
            'status' => 'sometimes|required|in:active,inactive',
            'visibility' => 'sometimes|required|in:public,private,activity',
            'assigned_user_id' => 'required_if:visibility,private|nullable|exists:users,id',
            'activity_type' => 'required_if:visibility,activity|nullable|string|max:100',
            'activity_threshold' => 'required_if:visibility,activity|nullable|integer|min:1',
            'activity_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if (isset($validated['visibility'])) {
            if ($validated['visibility'] === 'public') {
                $validated['assigned_user_id'] = null;
                $validated['activity_type'] = null;
                $validated['activity_threshold'] = 1;
                $validated['activity_description'] = null;
            } elseif ($validated['visibility'] === 'private') {
                $validated['activity_type'] = null;
                $validated['activity_threshold'] = 1;
                $validated['activity_description'] = null;
            } elseif ($validated['visibility'] === 'activity') {
                $validated['assigned_user_id'] = null;
            }
        }

        $coupon->update($validated);
        $coupon->load('assignedUser');

        return response()->json([
            'status' => 200,
            'message' => 'Coupon updated successfully',
            'data' => $coupon,
        ]);
    }

    /**
     * Delete a coupon
     */
    public function destroy($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'status' => 404,
                'message' => 'Coupon not found',
            ], 404);
        }

        $coupon->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Coupon deleted successfully',
        ], 200);
    }
}
