<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        try {
            Log::info('Attempting to fetch comics in admin');
            $comics = Comic::with('user')
                         ->orderBy('created_at', 'desc')
                         ->get();
            Log::info('Successfully fetched comics', ['count' => $comics->count()]);
            return response()->json($comics);
        } catch (\Exception $e) {
            Log::error('Error fetching comics in admin: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error fetching comics', 'error' => $e->getMessage()], 500);
        }
    }

    public function stats()
    {
        try {
            Log::info('Attempting to fetch admin stats');
            $stats = DB::transaction(function () {
                $total_comics = Comic::count();
                $total_users = User::count();
                $published_comics = Comic::where('status', 'published')->count();

                Log::info('Stats counts:', [
                    'totalComics' => $total_comics,
                    'totalUsers' => $total_users,
                    'publishedComics' => $published_comics
                ]);

                return [
                    'totalComics' => $total_comics,
                    'totalUsers' => $total_users,
                    'publishedComics' => $published_comics
                ];
            });

            Log::info('Successfully fetched admin stats', $stats);
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching admin stats: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error fetching stats', 'error' => $e->getMessage()], 500);
        }
    }

    public function storeComic(Request $request)
    {
        try {
            Log::info('Attempting to create comic', $request->except('image'));
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'author' => 'required|string|max:255',
                'genre' => 'required|string|max:255',
                'category_id' => 'required|integer',
                'status' => 'required|in:draft,published',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'featured' => 'boolean',
                'price' => 'numeric|min:0'
            ]);

            $comic = DB::transaction(function () use ($request) {
                $comicData = [
                    'title' => $request->title,
                    'description' => $request->description,
                    'author' => $request->author,
                    'genre' => $request->genre,
                    'category_id' => $request->category_id,
                    'status' => $request->status,
                    'user_id' => auth()->id(),
                    'featured' => $request->featured ?? false,
                    'price' => $request->price ?? 0.00
                ];
                
                // Handle image upload if provided
                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    
                    // Make sure the public/images directory exists
                    if (!file_exists(public_path('images'))) {
                        mkdir(public_path('images'), 0755, true);
                    }
                    
                    // Move the image to the public/images directory
                    $image->move(public_path('images'), $imageName);
                    
                    // Store the relative path in the database
                    $comicData['image_url'] = '/images/' . $imageName;
                    
                    Log::info('Image uploaded successfully', ['path' => $comicData['image_url']]);
                }
                
                return Comic::create($comicData);
            });

            Log::info('Comic created by admin', [
                'comic_id' => $comic->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json($comic, 201);
        } catch (\Exception $e) {
            Log::error('Error creating comic in admin: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error creating comic',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateComic(Request $request, Comic $comic)
    {
        try {
            Log::info('Attempting to update comic', ['comic_id' => $comic->id, 'data' => $request->except('image')]);
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'author' => 'required|string|max:255',
                'genre' => 'required|string|max:255',
                'category_id' => 'required|integer',
                'status' => 'required|in:draft,published',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
                'featured' => 'boolean',
                'price' => 'numeric|min:0'
            ]);

            DB::transaction(function () use ($comic, $request) {
                $comic->title = $request->title;
                $comic->description = $request->description;
                $comic->author = $request->author;
                $comic->genre = $request->genre;
                $comic->category_id = $request->category_id;
                $comic->status = $request->status;
                $comic->featured = $request->featured ?? false;
                $comic->price = $request->price;

                // Handle image upload if provided
                if ($request->hasFile('image')) {
                    // Delete old image if exists
                    if ($comic->image_url && file_exists(public_path($comic->image_url))) {
                        unlink(public_path($comic->image_url));
                    }

                    $image = $request->file('image');
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    
                    // Make sure the public/images directory exists
                    if (!file_exists(public_path('images'))) {
                        mkdir(public_path('images'), 0755, true);
                    }
                    
                    $image->move(public_path('images'), $imageName);
                    $comic->image_url = '/images/' . $imageName;
                    
                    Log::info('Image updated successfully', ['path' => $comic->image_url]);
                }

                $comic->save();
            });

            Log::info('Comic updated by admin', [
                'comic_id' => $comic->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json($comic->fresh());
        } catch (\Exception $e) {
            Log::error('Error updating comic in admin: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error updating comic',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteComic(Comic $comic)
    {
        try {
            Log::info('Attempting to delete comic', ['comic_id' => $comic->id]);
            $comicId = $comic->id;

            DB::transaction(function () use ($comic) {
                // Delete the associated image if it exists
                if ($comic->image_url && file_exists(public_path($comic->image_url))) {
                    unlink(public_path($comic->image_url));
                    Log::info('Deleted comic image file', ['path' => $comic->image_url]);
                }
                
                $comic->delete();
            });

            Log::info('Comic deleted by admin', [
                'comic_id' => $comicId,
                'admin_id' => auth()->id()
            ]);

            return response()->json(['message' => 'Comic deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting comic in admin: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error deleting comic',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function users()
    {
        try {
            Log::info('Attempting to fetch users');
            $users = User::withCount('comics')
                        ->orderBy('created_at', 'desc')
                        ->get();
            Log::info('Successfully fetched users', ['count' => $users->count()]);
            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error fetching users in admin: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error fetching users'], 500);
        }
    }
}