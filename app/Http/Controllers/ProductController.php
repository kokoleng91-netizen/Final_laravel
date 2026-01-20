<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * បង្ហាញបញ្ជីផលិតផលទាំងអស់ (សម្រាប់គ្រប់គ្នា)
     */
    public function index()
    {
        // ប្រើ with('category') ដើម្បីទាញយកឈ្មោះប្រភេទផលិតផលមកជាមួយ
        return response()->json(Product::with('category')->latest()->get(), 200);
    }

    /**
     * រក្សាទុកផលិតផលថ្មី (សម្រាប់តែ Admin)
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'product_name' => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric',
            'quantity'     => 'required|integer',
            'category_id'  => 'required|exists:categories,id',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // កំណត់ទំហំរូបភាព
        ]);

        // Logic សម្រាប់បង្ហោះរូបភាព
        if ($request->hasFile('image')) {
            $fields['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($fields);

        return response()->json([
            'message' => 'ផលិតផលត្រូវបានបង្កើតដោយជោគជ័យ',
            'product' => $product
        ], 201);
    }

    /**
     * បង្ហាញព័ត៌មានលម្អិតនៃផលិតផលមួយ (តាម ID)
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json(['message' => 'រកមិនឃើញផលិតផលនេះទេ'], 404);
        }

        return response()->json($product, 200);
    }

    /**
     * កែប្រែព័ត៌មានផលិតផល (សម្រាប់តែ Admin)
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'រកមិនឃើញផលិតផលដើម្បីកែប្រែ'], 404);
        }

        $fields = $request->validate([
            'product_name' => 'string|max:255',
            'price'        => 'numeric',
            'quantity'     => 'integer',
            'category_id'  => 'exists:categories,id',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // ប្រសិនបើមានការប្តូររូបភាពថ្មី ត្រូវលុបរូបភាពចាស់ចោល
        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $fields['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($fields);

        return response()->json([
            'message' => 'បានកែប្រែព័ត៌មានដោយជោគជ័យ',
            'product' => $product
        ], 200);
    }

    /**
     * លុបផលិតផល (សម្រាប់តែ Admin)
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'មិនអាចលុបបានទេ ព្រោះរកមិនឃើញផលិតផល'], 404);
        }

        // លុបរូបភាពចេញពី Storage មុននឹងលុបចេញពី Database
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'លុបផលិតផលរួចរាល់'], 200);
    }
}
