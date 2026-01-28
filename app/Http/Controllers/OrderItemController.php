<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderItemController extends Controller
{
    /**
     * បង្ហាញបញ្ជី Order Items ទាំងអស់
     */
    public function index()
    {
        try {
            $items = OrderItem::with(['product', 'order.user'])
                ->latest()
                ->get();

            return response()->json($items, 200);
        } catch (\Exception $e) {
            Log::error('OrderItem index error: ' . $e->getMessage());

            return response()->json([
                'message' => 'មានបញ្ហាក្នុងការទាញយកទិន្នន័យ'
            ], 500);
        }
    }

    /**
     * បង្កើត Order Item ថ្មី
     */
    public function store(Request $request)
    {
        try {
            $fields = $request->validate([
                'order_id'    => 'required|exists:orders,id',
                'product_id'  => 'required|exists:products,id',
                'quantity'    => 'required|integer|min:1',
                'unit_price'  => 'required|numeric|min:0',
            ]);

            $orderItem = OrderItem::create($fields);

            return response()->json([
                'message' => 'បានបន្ថែមមុខទំនិញទៅក្នុង Order រួចរាល់',
                'data'    => $orderItem
            ], 201);
        } catch (\Exception $e) {
            Log::error('OrderItem store error: ' . $e->getMessage());

            return response()->json([
                'message' => 'បរាជ័យក្នុងការបង្កើត Order Item'
            ], 500);
        }
    }

    /**
     * បង្ហាញ Order Item មួយ
     */
    public function show($id)
    {
        try {
            $orderItem = OrderItem::with(['product', 'order'])
                ->find($id);

            if (!$orderItem) {
                return response()->json([
                    'message' => 'រកមិនឃើញទិន្នន័យ'
                ], 404);
            }

            return response()->json($orderItem, 200);
        } catch (\Exception $e) {
            Log::error('OrderItem show error: ' . $e->getMessage());

            return response()->json([
                'message' => 'មានបញ្ហាក្នុងការបង្ហាញទិន្នន័យ'
            ], 500);
        }
    }

    /**
     * កែប្រែ Order Item
     */
    public function update(Request $request, $id)
    {
        try {
            $orderItem = OrderItem::find($id);

            if (!$orderItem) {
                return response()->json([
                    'message' => 'រកមិនឃើញទិន្នន័យដើម្បីកែប្រែ'
                ], 404);
            }

            $fields = $request->validate([
                'quantity'   => 'integer|min:1',
                'unit_price' => 'numeric|min:0',
            ]);

            $orderItem->update($fields);

            return response()->json([
                'message' => 'បានកែប្រែជោគជ័យ',
                'data'    => $orderItem
            ], 200);
        } catch (\Exception $e) {
            Log::error('OrderItem update error: ' . $e->getMessage());

            return response()->json([
                'message' => 'បរាជ័យក្នុងការកែប្រែ'
            ], 500);
        }
    }

    /**
     * លុប Order Item
     */
    public function destroy($id)
    {
        try {
            $orderItem = OrderItem::find($id);

            if (!$orderItem) {
                return response()->json([
                    'message' => 'រកមិនឃើញទិន្នន័យដើម្បីលុប'
                ], 404);
            }

            $orderItem->delete();

            return response()->json([
                'message' => 'បានលុបមុខទំនិញរួចរាល់'
            ], 200);
        } catch (\Exception $e) {
            Log::error('OrderItem delete error: ' . $e->getMessage());

            return response()->json([
                'message' => 'បរាជ័យក្នុងការលុប'
            ], 500);
        }
    }
}
