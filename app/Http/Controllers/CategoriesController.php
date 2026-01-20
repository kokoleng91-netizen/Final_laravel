<?php

namespace App\Http\Controllers;

use App\Models\Categories; // ពិនិត្យមើលបើ Model ឈ្មោះ Category ត្រូវដូរមក Category វិញ
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    /**
     * បង្ហាញប្រភេទផលិតផលទាំងអស់
     */
    public function index()
    {
        $categories = Categories::all();
        return response()->json($categories, 200);
    }

    /**
     * បង្កើតប្រភេទផលិតផលថ្មី (Admin Only)
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'category_name' => 'required|string|unique:categories,category_name|max:255',
        ]);

        $category = Categories::create($fields);

        return response()->json([
            'message' => 'ប្រភេទផលិតផលត្រូវបានបង្កើត',
            'category' => $category
        ], 201);
    }

    /**
     * បង្ហាញព័ត៌មាន Category មួយ និងផលិតផលដែលមានក្នុងនោះ
     */
    public function show($id)
    {
        // ទាញយក Category ព្រមទាំងបញ្ជី Products ដែលនៅក្នុង Category នោះ
        $category = Categories::with('products')->find($id);

        if (!$category) {
            return response()->json(['message' => 'រកមិនឃើញប្រភេទផលិតផលនេះទេ'], 404);
        }

        return response()->json($category, 200);
    }

    /**
     * កែប្រែឈ្មោះប្រភេទផលិតផល
     */
    public function update(Request $request, $id)
    {
        $category = Categories::find($id);

        if (!$category) {
            return response()->json(['message' => 'រកមិនឃើញទិន្នន័យដើម្បីកែប្រែ'], 404);
        }

        $fields = $request->validate([
            'category_name' => 'required|string|unique:categories,category_name,' . $id . '|max:255',
        ]);

        $category->update($fields);

        return response()->json([
            'message' => 'បានកែប្រែដោយជោគជ័យ',
            'category' => $category
        ], 200);
    }

    /**
     * លុបប្រភេទផលិតផល
     */
    public function destroy($id)
    {
        $category = Categories::find($id);

        if (!$category) {
            return response()->json(['message' => 'រកមិនឃើញទិន្នន័យដើម្បីលុប'], 404);
        }

        // បញ្ជាក់៖ បើលុប Category ផលិតផលក្នុងនោះក៏អាចនឹងប៉ះពាល់ (អាស្រ័យលើ On Delete Cascade ក្នុង Migration)
        $category->delete();

        return response()->json(['message' => 'លុបប្រភេទផលិតផលរួចរាល់'], 200);
    }
}
