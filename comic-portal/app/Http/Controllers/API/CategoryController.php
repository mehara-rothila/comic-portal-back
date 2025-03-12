<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            Log::info('Fetching all categories');
            $categories = Category::all();
            return response()->json(['categories' => $categories]);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching categories'], 500);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);
            Log::info('Fetching category details', ['category_id' => $category->id]);
            return response()->json($category);
        } catch (\Exception $e) {
            Log::error('Error fetching category details: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching category details'], 500);
        }
    }
}