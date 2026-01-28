<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class CategoriesController extends Controller
{
    /**
     * Display all categories
     */
    public function index()
    {
        try {
            $categories = Category::all();

            return response()->json($categories, 200);
        } catch (Exception $e) {
            Log::error('Category index error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve categories'
            ], 500);
        }
    }

    /**
     * Create a new category
     */
    public function store(Request $request)
    {
        try {
            $fields = $request->validate([
                'category_name' => 'required|string|unique:categories,category_name|max:255',
                'description'   => 'nullable|string',
            ]);

            $category = Category::create($fields);

            return response()->json([
                'message'  => 'Category created successfully',
                'category' => $category
            ], 201);
        } catch (Exception $e) {
            Log::error('Category store error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create category'
            ], 500);
        }
    }

    /**
     * Display a single category with its products
     */
    public function show($id)
    {
        try {
            $category = Category::with('products')->find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            return response()->json($category, 200);
        } catch (Exception $e) {
            Log::error('Category show error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to retrieve category'
            ], 500);
        }
    }

    /**
     * Update a category
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $fields = $request->validate([
                'category_name' => 'required|string|unique:categories,category_name,' . $id . '|max:255',
                'description'   => 'nullable|string',
            ]);

            $category->update($fields);

            return response()->json([
                'message'  => 'Category updated successfully',
                'category' => $category
            ], 200);
        } catch (Exception $e) {
            Log::error('Category update error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update category'
            ], 500);
        }
    }

    /**
     * Delete a category
     */
    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error('Category delete error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to delete category'
            ], 500);
        }
    }
}
