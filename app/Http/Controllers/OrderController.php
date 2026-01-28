<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;


class OrderController extends Controller
{
    /**
     * Display order history
     * - Admin: can see all orders
     * - User: can see only their own orders
     */

    public function index()
    {
        try {
            $user = Auth::user();

            if ($user->role_id == 1) { // Admin
                $orders = Order::with(['user', 'orderItems.product'])
                    ->latest()
                    ->get();
            } else {
                $orders = Order::with(['orderItems.product'])
                    ->where('user_id', $user->id)
                    ->latest()
                    ->get();
            }

            return response()->json($orders, 200);
        } catch (\Exception $e) {
            Log::error('Order index error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve orders'
            ], 500);
        }
    }

    /**
     * Create a new order (Checkout)
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.stock_qty' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {

            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if (!$product || $product->stock_qty < $item['stock_qty']) {
                    throw new \Exception('Insufficient stock for ' . $product->product_name);
                }

                $totalAmount += $product->price * $item['stock_qty'];

                $orderItemsData[] = [
                    'product_id'    => $product->id,
                    'product_name'  => $product->product_name,  // snapshot
                    'product_image' => $product->image,         // snapshot
                    'quantity'      => $item['stock_qty'],
                    'unit_price'    => $product->price,
                ];

                $product->decrement('stock_qty', $item['stock_qty']);
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            return response()->json([
                'message' => 'Order placed successfully',
                'order'   => $order->load('orderItems') // no live product
            ], 201);
        });
    }


    /**
     * Display details of a single order
     */
    public function show($id)
    {
        try {
            $order = Order::with(['user', 'orderItems.product'])->find($id);

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found'
                ], 404);
            }

            // Prevent users from accessing other users' orders
            if (Auth::id() !== $order->user_id && Auth::user()->role_id !== 1) {
                return response()->json([
                    'message' => 'You are not authorized to view this order'
                ], 403);
            }

            return response()->json($order, 200);
        } catch (\Exception $e) {
            Log::error('Order show error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve order details'
            ], 500);
        }
    }

    /**
     * Display all orders (Admin only)
     */
    public function adminIndex()
    {
        try {
            $orders = Order::with(['user', 'orderItems.product'])
                ->latest()
                ->get();

            return response()->json($orders, 200);
        } catch (\Exception $e) {
            Log::error('Admin order index error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve orders'
            ], 500);
        }
    }

    /**
     * Update order status (Admin only)
     */
    public function update(Request $request, $id)
    {
        $user = request()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order->update(['status' => $request->status]);

        return response()->json(['message' => 'Order updated', 'order' => $order]);
    }

    /**
     * Delete an order (not recommended, usually use status = cancelled)
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Order cancelled']);
    }
}
