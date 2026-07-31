<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * 🧾 Display all orders
     */
    public function index()
    {
        $orders = Order::with(['user', 'items.product', 'items.variant', 'shipping.shippingMethod', 'payment'])->latest()->get();

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $orders
        ], 200);
    }

    /**
     * 🆕 Store a new order in MySQL database with items
     */
    public function store(Request $request)
    {
        $userId = $request->user() ? $request->user()->id : $request->input('user_id');

        // Fallback user if non-logged in patron completes checkout
        if (!$userId) {
            $user = User::where('role', 'customer')->first() ?? User::first();
            $userId = $user ? $user->id : 1;
        }

        $order = Order::create([
            'user_id' => $userId,
            'total_amount' => $request->input('total_amount', 0),
            'discount_amount' => $request->input('discount_amount', 0),
            'shipping_fee' => $request->input('shipping_fee', 0),
            'final_amount' => $request->input('final_amount', 0),
            'status' => 'pending',
        ]);

        // Attach order items
        $items = $request->input('items', []);
        foreach ($items as $item) {
            $productId = isset($item['product_id']) ? $item['product_id'] : (isset($item['product']) ? $item['product']['id'] : null);
            if ($productId) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'] ?? 1,
                    'price_at_purchase' => $item['price'] ?? 0,
                    'discount_applied' => 0,
                ]);
            }
        }

        $order->load(['items.product', 'user']);

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Order created successfully in database',
            'data' => $order
        ], 200);
    }

    /**
     * Fetch orders for current customer / user
     */
    public function userOrders(Request $request)
    {
        $user = $request->user() ?? auth('sanctum')->user();
        $userId = $user ? $user->id : $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $orders = Order::where('user_id', $userId)
            ->with(['items.product.images', 'user'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * 📦 Show a single order
     */
    public function show($id)
    {
        $order = Order::with(['user', 'items.product', 'items.variant', 'shipping.shippingMethod', 'payment'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ], 200);
    }

    /**
     * ✏️ Update order
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'total_amount' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'final_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order
        ], 200);
    }

    /**
     * 🗑️ Soft Delete order
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully (soft deleted)',
        ], 200);
    }

    /**
     * 🔁 Restore a soft-deleted order
     */
    public function restore($id)
    {
        $order = Order::withTrashed()->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->restore();

        return response()->json([
            'success' => true,
            'message' => 'Order restored successfully',
            'data' => $order
        ], 200);
    }

    /**
     * 🚮 Permanently delete a soft-deleted order
     */
    public function forceDelete($id)
    {
        $order = Order::withTrashed()->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Order permanently deleted',
        ], 200);
    }
}
