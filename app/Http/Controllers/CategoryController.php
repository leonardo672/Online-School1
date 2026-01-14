<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\CategoryService;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        // Paginated categories for table
        $categories = $this->categoryService->search($request->get('search'));

        // Statistics
        $stats = $this->categoryService->statistics();

        // Latest added category
        $latestCategory = Category::orderBy('created_at', 'desc')->first();

        return view('categories.index', [
            'categories' => $categories,
            'categoriesWithCourses' => $stats['categoriesWithCourses'],
            'emptyCategories' => $stats['emptyCategories'],
            'latestCategory' => $latestCategory,
        ]);
    }


    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('categories.index')
                         ->with('success', 'Category created successfully!');
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        if (method_exists($category, 'courses')) {
            $category->loadCount('courses');
        }

        return view('categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        if (method_exists($category, 'courses')) {
            $category->loadCount('courses');
        }

        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('categories.index')
                         ->with('success', 'Category updated successfully!');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        try {
            $this->categoryService->delete($category);
            return redirect()->route('categories.index')
                             ->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('categories.index')
                             ->with('error', $e->getMessage());
        }
    }
}
