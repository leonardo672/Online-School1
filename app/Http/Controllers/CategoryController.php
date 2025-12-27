<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema; // Add this

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::when(method_exists(Category::class, 'courses'), function($query) {
                $query->withCount('courses');
            })
            ->when(request('search'), function ($query) {
                $query->where('name', 'like', '%' . request('search') . '%')
                      ->orWhere('description', 'like', '%' . request('search') . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        // Calculate statistics
        $categoriesWithCourses = 0;
        $emptyCategories = 0;
        if (method_exists(Category::class, 'courses')) {
            $categoriesWithCourses = Category::has('courses')->count();
            $emptyCategories = Category::doesntHave('courses')->count();
        }

        return view('categories.index', compact('categories', 'categoriesWithCourses', 'emptyCategories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        // Basic validation
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        // Prepare data with only existing columns
        $categoryData = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ];

        // Only add description if column exists
        if (Schema::hasColumn('categories', 'description')) {
            $request->validate(['description' => 'nullable|string']);
            $categoryData['description'] = $request->description;
        }

        // Only add icon if column exists
        if (Schema::hasColumn('categories', 'icon')) {
            $request->validate(['icon' => 'nullable|string']);
            $categoryData['icon'] = $request->icon;
        }

        // Only add color if column exists
        if (Schema::hasColumn('categories', 'color')) {
            $request->validate(['color' => 'nullable|string|max:7']);
            $categoryData['color'] = $request->color;
        }

        Category::create($categoryData);

        return redirect()->route('categories.index')->with('success', 'Category created successfully!');
    }

    /**
     * Display the specified category.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        
        // Add courses count if relationship exists
        if (method_exists($category, 'courses')) {
            $category->loadCount('courses');
        }
        
        return view('categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        
        // Add courses count if relationship exists
        if (method_exists($category, 'courses')) {
            $category->loadCount('courses');
        }
        
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        // Basic validation
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
        ]);

        // Prepare update data with only existing columns
        $updateData = [
            'name' => $request->name,
            'slug' => $request->slug,
        ];

        // Only update description if column exists
        if (Schema::hasColumn('categories', 'description')) {
            $request->validate(['description' => 'nullable|string']);
            $updateData['description'] = $request->description;
        }

        // Only update icon if column exists
        if (Schema::hasColumn('categories', 'icon')) {
            $request->validate(['icon' => 'nullable|string']);
            $updateData['icon'] = $request->icon;
        }

        // Only update color if column exists
        if (Schema::hasColumn('categories', 'color')) {
            $request->validate(['color' => 'nullable|string|max:7']);
            $updateData['color'] = $request->color;
        }

        $category->update($updateData);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        
        // Check if category has courses before deleting (if relationship exists)
        if (method_exists($category, 'courses') && $category->courses()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Cannot delete category that has courses. Please remove or reassign courses first.');
        }
        
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}