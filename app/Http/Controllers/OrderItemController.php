<?php

namespace App\Http\Controllers;

use App\Models\Order_item;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * បង្ហាញបញ្ជីមុខទំនិញដែលបានលក់ទាំងអស់ (របាយការណ៍លក់)
     */
    public function index()
    {
        // ទាញយក Order_item ទាំងអស់ រួមជាមួយព័ត៌មាន Product និង Order របស់វា
        $items = Order_item::with(['product', 'order.user'])->latest()->get();
        return response()->json($items, 200);
    }

    /**
     * បង្កើត Order Item ថ្មី (ជាទូទៅប្រើក្នុង OrderController តែបើចង់មានក្នុងនេះក៏បាន)
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'price'      => 'required|numeric',
        ]);

        $orderItem = Order_item::create($fields);

        return response()->json([
            'message' => 'បានបន្ថែមមុខទំនិញទៅក្នុង Order រួចរាល់',
            'data'    => $orderItem
        ], 201);
    }

    /**
     * បង្ហាញព័ត៌មានលម្អិតនៃ Item មួយ
     */
    public function show($id)
    {
        $orderItem = Order_item::with(['product', 'order'])->find($id);

        if (!$orderItem) {
            return response()->json(['message' => 'រកមិនឃើញទិន្នន័យ'], 404);
        }

        return response()->json($orderItem, 200);
    }

    /**
     * កែប្រែទិន្នន័យ Item (ឧទាហរណ៍៖ កែចំនួន Quantity ក្នុងករណីពិសេស)
     */
    public function update(Request $request, $id)
    {
        $orderItem = Order_item::find($id);

        if (!$orderItem) {
            return response()->json(['message' => 'រកមិនឃើញទិន្នន័យដើម្បីកែប្រែ'], 404);
        }

        $fields = $request->validate([
            'quantity' => 'integer|min:1',
            'price'    => 'numeric',
        ]);

        $orderItem->update($fields);

        return response()->json([
            'message' => 'បានកែប្រែជោគជ័យ',
            'data'    => $orderItem
        ], 200);
    }

    /**
     * លុបមុខទំនិញចេញពី Order (Admin Only)
     */
    public function destroy($id)
    {
        $orderItem = Order_item::find($id);

        if (!$orderItem) {
            return response()->json(['message' => 'រកមិនឃើញទិន្នន័យដើម្បីលុប'], 404);
        }

        $orderItem->delete();

        return response()->json(['message' => 'បានលុបមុខទំនិញរួចរាល់'], 200);
    }
}
