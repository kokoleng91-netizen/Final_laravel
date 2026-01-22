<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * បង្ហាញបញ្ជីផលិតផលទាំងអស់ (សម្រាប់គ្រប់គ្នា)
     */
    public function index()
    {
        try {
            $products = Product::with('Categories')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'All students with courses retrieved successfully',
                'data' => $products,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * រក្សាទុកផលិតផលថ្មី (សម្រាប់តែ Admin)
     */
    public function store(Request $request)
    {
        try {
            // 1. Validation
            $fields = $request->validate([
                'product_name' => 'required|string|max:255',
                'description'  => 'nullable|string',
                'price'        => 'required|numeric',
                'quantity'     => 'required|integer',
                'category_id'  => 'required|exists:categories,id',
                'image'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // 2. Handle Image Upload
            if ($request->hasFile('image')) {
                $fields['image'] = $request->file('image')->store('products', 'public');
            }

            // 3. Create Product
            $product = Product::create($fields);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data'    => $product
            ], 201);
        } catch (\Throwable $th) {
            // Cleanup: Delete uploaded image if database insert fails
            if (isset($fields['image']) && Storage::disk('public')->exists($fields['image'])) {
                Storage::disk('public')->delete($fields['image']);
            }

            // Log the error for debugging
            Log::error("Product Store Error: " . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    /**
     * បង្ហាញព័ត៌មានលម្អិតនៃផលិតផលមួយ (តាម ID)
     */
    public function show($id)
    {
        try {
            // Find product with its category
            $product = Product::with('category')->find($id);

            // Check if product exists
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product retrieved successfully',
                'data'    => $product
            ], 200);
        } catch (\Throwable $th) {
            // Log error for internal debugging
            Log::error("Product Show Error: " . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching product details',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    /**
     * កែប្រែព័ត៌មានផលិតផល (សម្រាប់តែ Admin)
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found for update'
                ], 404);
            }

            $fields = $request->validate([
                'product_name' => 'string|max:255',
                'description'  => 'nullable|string',
                'price'        => 'numeric',
                'quantity'     => 'integer',
                'category_id'  => 'exists:categories,id',
                'image'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Logic for handling image update
            if ($request->hasFile('image')) {
                // 1. Delete the old image from storage if it exists
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }

                // 2. Store the new image
                $fields['image'] = $request->file('image')->store('products', 'public');
            }

            // 3. Update the database
            $product->update($fields);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data'    => $product
            ], 200);
        } catch (\Throwable $th) {
            // Log the error
            Log::error("Product Update Error: " . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    /**
     * លុបផលិតផល (សម្រាប់តែ Admin)
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found. Deletion failed.'
                ], 404);
            }

            // 1. Delete the image from Storage if it exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            // 2. Delete the record from Database
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            // Log the error for internal tracking
            Log::error("Product Delete Error: " . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while trying to delete the product',
                'error'   => $th->getMessage()
            ], 500);
        }
    }
}
