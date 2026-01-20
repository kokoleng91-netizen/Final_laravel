<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\OrderItemController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * បង្ហាញប្រវត្តិបញ្ជាទិញ (សម្រាប់ Admin ឃើញទាំងអស់ សម្រាប់ User ឃើញតែរបស់ខ្លួនឯង)
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role_id == 1) { // បើជា Admin
            $orders = Order::with(['user', 'orderItems.product'])->latest()->get();
        } else {
            $orders = Order::with('orderItems.product')
                ->where('user_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json($orders, 200);
    }

    /**
     * បង្កើតការបញ្ជាទិញថ្មី (Checkout)
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // ប្រើ DB Transaction ដើម្បីធានាថា បើការងារណាមួយ error វានឹង cancel ទាំងអស់វិញ (Data Integrity)
        return DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $orderItemsData = [];

            // ១. គណនាតម្លៃសរុប និងឆែកស្តុកទំនិញ
            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if ($product->quantity < $item['quantity']) {
                    throw new \Exception("ទំនិញ " . $product->product_name . " មិនមានស្តុកគ្រប់គ្រាន់ទេ");
                }

                $totalAmount += $product->price * $item['quantity'];

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price, // រក្សាទុកតម្លៃពេលទិញ
                ];

                // ២. កាត់ស្តុកទំនិញ
                $product->decrement('quantity', $item['quantity']);
            }

            // ៣. បង្កើត Order
            $order = Order::create([
                'user_id' => Auth::id(),
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            // ៤. បញ្ចូលមុខទំនិញទៅក្នុង order_items
            foreach ($orderItemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            return response()->json([
                'message' => 'ការបញ្ជាទិញបានជោគជ័យ',
                'order' => $order->load('orderItems')
            ], 201);
        });
    }

    /**
     * មើលព័ត៌មានលម្អិតនៃ Order មួយ
     */
    public function show($id)
    {
        $order = Order::with(['user', 'orderItems.product'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'រកមិនឃើញ Order នេះទេ'], 404);
        }

        // ការពារកុំឱ្យ User ម្នាក់ ទៅមើល Order របស់ User ម្នាក់ទៀត
        if (Auth::id() !== $order->user_id && Auth::user()->role_id !== 1) {
            return response()->json(['message' => 'អ្នកគ្មានសិទ្ធិមើលទិន្នន័យនេះទេ'], 403);
        }

        return response()->json($order, 200);
    }

    /**
     * កែប្រែស្ថានភាព Order (ឧទាហរណ៍៖ ពី pending ទៅ completed) - Admin Only
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'រកមិនឃើញ Order'], 404);
        }

        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        $order->update(['status' => $request->status]);

        return response()->json(['message' => 'បានកែប្រែស្ថានភាពជោគជ័យ', 'order' => $order]);
    }

    /**
     * លុប Order (ជាទូទៅគេមិនសូវលុបទេ គេប្រើ status = cancelled វិញ)
     */
    public function destroy($id)
    {
        $order = Order::find($id);
        if ($order) {
            $order->delete();
            return response()->json(['message' => 'បានលុប Order រួចរាល់']);
        }
        return response()->json(['message' => 'រកមិនឃើញទិន្នន័យ'], 404);
    }
}
