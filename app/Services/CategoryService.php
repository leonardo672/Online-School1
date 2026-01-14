<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Traits\SupportsDynamicColumns;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    use SupportsDynamicColumns;

    protected string $table = 'categories';
    protected array $allowedFields = ['name', 'slug', 'description', 'icon', 'color'];

    /**
     * Create a new category.
     */
    public function create(array $data): Category
    {
        $payload = $this->filterExistingColumns($this->table, $data, $this->allowedFields);

        // Auto-generate slug if missing
        if (empty($payload['slug']) && isset($payload['name'])) {
            $payload['slug'] = Str::slug($payload['name']);
        }

        return Category::create($payload);
    }

    /**
     * Update an existing category.
     */
    public function update(Category $category, array $data): Category
    {
        $payload = $this->filterExistingColumns($this->table, $data, $this->allowedFields);

        // Auto-generate slug if missing
        if (empty($payload['slug']) && isset($payload['name'])) {
            $payload['slug'] = Str::slug($payload['name']);
        }

        $category->update($payload);

        return $category;
    }

    /**
     * Delete a category safely.
     * Throws exception if category has courses.
     */
    public function delete(Category $category): void
    {
        if ($category->courses()->exists()) {
            throw new \Exception("Cannot delete category with existing courses.");
        }

        $category->delete();
    }

    /**
     * Get category statistics.
     */
    public function statistics(): array
    {
        // Single query for efficiency using COUNT with conditions
        $total = Category::count();
        $withCourses = Category::has('courses')->count();
        $empty = $total - $withCourses;

        return [
            'total' => $total,
            'categoriesWithCourses' => $withCourses,
            'emptyCategories' => $empty,
        ];
    }

    /**
     * Search categories by name or description.
     */
    public function search(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Category::query()->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%") // Postgres-safe case-insensitive search
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        // Always include courses count for the table
        $query->withCount('courses');

        return $query->paginate($perPage);
    }
}
