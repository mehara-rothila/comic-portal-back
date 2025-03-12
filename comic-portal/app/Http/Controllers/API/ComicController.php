<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ComicController extends Controller
{
    public function index()
    {
        try {
            Log::info('Fetching all comics');
            $comics = Comic::with('user')->latest()->get();
            return response()->json($comics);
        } catch (\Exception $e) {
            Log::error('Error fetching comics: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching comics'], 500);
        }
    }

    public function show($id)
    {
        try {
            $comic = Comic::findOrFail($id);
            Log::info('Fetching comic details', ['comic_id' => $comic->id]);
            return response()->json($comic->load('user'));
        } catch (\Exception $e) {
            Log::error('Error fetching comic details: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching comic details'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->check()) {
                Log::warning('Unauthorized comic creation attempt');
                return response()->json(['message' => 'Unauthorized'], 401);
            }
    
            Log::info('Comic creation attempt', [
                'user_id' => auth()->id(),
                'request_data' => $request->except('image')
            ]);
    
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'author' => 'required|string|max:255',
                'genre' => 'required|string|max:255',
                'status' => 'required|in:draft,published',
                'category_id' => 'required|integer',
                'price' => 'required|numeric|min:0',
                'featured' => 'boolean'
            ]);
    
            // Handle optional image upload
            if ($request->hasFile('image')) {
                $request->validate([
                    'image' => 'image|mimes:jpeg,png,jpg|max:5120'
                ]);
    
                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                
                // Store image in public/images directory
                $image->move(public_path('images'), $imageName);
                $validatedData['image_url'] = '/images/' . $imageName;
            }
    
            // Format data
            $validatedData['featured'] = filter_var($request->featured, FILTER_VALIDATE_BOOLEAN);
            $validatedData['price'] = number_format((float)$validatedData['price'], 2, '.', '');
            $validatedData['category_id'] = (int)$validatedData['category_id'];
            $validatedData['user_id'] = auth()->id();
    
            Log::info('Validated data:', $validatedData);
    
            $comic = Comic::create($validatedData);
    
            Log::info('Comic created successfully', ['comic_id' => $comic->id]);
            return response()->json($comic, 201);
    
        } catch (\Exception $e) {
            Log::error('Error creating comic: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating comic',
                'error' => $e->getMessage()
            ], 500);
        }
    }
  
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $comic = Comic::findOrFail($id);
            
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'author' => 'required|string|max:255',
                'genre' => 'required|string|max:255',
                'status' => 'required|in:draft,published',
                'category_id' => 'required|integer',
                'price' => 'required|numeric|min:0',
                'featured' => 'required|in:0,1,true,false',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120' // Optional image
            ];

            $validatedData = $request->validate($rules);

            // Handle image upload if new image is provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($comic->image_url && file_exists(public_path($comic->image_url))) {
                    unlink(public_path($comic->image_url));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images'), $imageName);
                $validatedData['image_url'] = '/images/' . $imageName;
            }

            // Format data
            $validatedData['featured'] = filter_var($request->featured, FILTER_VALIDATE_BOOLEAN);
            $validatedData['price'] = number_format((float)$validatedData['price'], 2, '.', '');
            $validatedData['category_id'] = (int)$validatedData['category_id'];

            // Remove image field if it exists
            unset($validatedData['image']);
            
            $comic->update($validatedData);
            
            Log::info('Comic updated successfully', ['comic_id' => $comic->id]);
            return response()->json($comic->fresh());

        } catch (\Exception $e) {
            Log::error('Update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating comic',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $comic = Comic::findOrFail($id);
            if (!$user->is_admin && $comic->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Delete the image file if it exists
            if ($comic->image_url && file_exists(public_path($comic->image_url))) {
                unlink(public_path($comic->image_url));
            }

            $comic->delete();
            return response()->json(['message' => 'Comic deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting comic: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting comic'], 500);
        }
    }

    public function featured()
    {
        try {
            $comics = Comic::where('featured', true)
                         ->where('status', 'published')
                         ->latest()
                         ->get();
            
            return response()->json($comics);
        } catch (\Exception $e) {
            Log::error('Error fetching featured comics: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching featured comics'], 500);
        }
    }

    public function adminStats()
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->is_admin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $stats = [
                'totalComics' => Comic::count(),
                'totalUsers' => User::count(),
                'publishedComics' => Comic::where('status', 'published')->count(),
                'featuredComics' => Comic::where('featured', true)->count()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching admin stats: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching stats'], 500);
        }
    }

    public function userComics()
    {
        try {
            if (!auth()->check()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $comics = Comic::where('user_id', auth()->id())->latest()->get();
            return response()->json($comics);
        } catch (\Exception $e) {
            Log::error('Error fetching user comics: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching user comics'], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = Comic::query();

            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('author', 'like', "%{$searchTerm}%")
                      ->orWhere('genre', 'like', "%{$searchTerm}%");
                });
            }

            if ($request->has('category')) {
                $query->where('category_id', $request->category);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $comics = $query->with('user')
                          ->latest()
                          ->paginate(10);

            return response()->json($comics);
        } catch (\Exception $e) {
            Log::error('Error searching comics: ' . $e->getMessage());
            return response()->json(['message' => 'Error searching comics'], 500);
        }
    }

    public function toggleFeatured($id)
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->is_admin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $comic = Comic::findOrFail($id);
            $comic->featured = !$comic->featured;
            $comic->save();

            return response()->json([
                'message' => 'Featured status updated successfully',
                'featured' => $comic->featured
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling featured status: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating featured status'], 500);
        }
    }

    // New method to get comics by category
    public function getByCategory($id)
    {
        try {
            Log::info('Fetching comics by category', ['category_id' => $id]);
            
            $comics = Comic::where('category_id', $id)
                        ->where('status', 'published')
                        ->with('user')
                        ->latest()
                        ->get();
            
            return response()->json($comics);
        } catch (\Exception $e) {
            Log::error('Error fetching comics by category: ' . $e->getMessage(), [
                'category_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['message' => 'Error fetching comics by category'], 500);
        }
    }
}