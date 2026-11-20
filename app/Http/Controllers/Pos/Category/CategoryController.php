<?php

namespace App\Http\Controllers\Pos\Category;

use App\Exceptions\AuthorizeException;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Category\CategoryRequest;
use App\Models\Landlord\Category\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{

    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     * @throws AuthorizeException
     */

    public function index()
    {
        return Category::query()
            ->filters($this->filter)
            ->withCount('products')
            ->with('parent:id,name')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(CategoryRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        // The create form sends "" for "no parent selected" — an empty string isn't
        // a valid value for the parent_id foreign key column, so normalize it to null.
        $data['parent_id'] = $data['parent_id'] ?: null;
        $data['order'] = (int) Category::query()
            ->when(empty($data['parent_id']), fn($q) => $q->whereNull('parent_id'), fn($q) => $q->where('parent_id', $data['parent_id']))
            ->max('order') + 1;

    Category::query()->create($data);


        return successResponse('Category created successfully');
    }


    /**
     * Display the specified resource.
     */

    public function show(Category $category)
    {
        return $category;
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(CategoryRequest $request, Category $category): \Illuminate\Http\JsonResponse
    {
    
        $data = $request->validated();
        $data['parent_id'] = $data['parent_id'] ?: null;

        $category->update(array_merge($data, ['slug' => Str::slug($request->name)]));

        return successResponse('Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Category $category): \Illuminate\Http\JsonResponse
    {
        $category->delete();
        return successResponse('Category deleted successfully');
    }

    public function toggleStatus(Category $category): \Illuminate\Http\JsonResponse
    {
        $category->update([
            'status' => $category->status == '1' ? '0' : '1',
        ]);

        return successResponse('Category visibility updated successfully');
    }

    public function parentCategory(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Category::query()
            ->filter(new NameFilter())
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function categories(): \Illuminate\Database\Eloquent\Collection|array
    {
        return Category::query()
            ->filter(new NameFilter())
            ->whereNull('parent_id')
            ->with('subCategories')
            ->orderByDesc('id')
            ->get();
    }

    public function updateOrder(Request $request)
    {
        $categories = $request->input('categories');

        DB::beginTransaction();
        try {
            $this->applyCategoryOrder($categories);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update order'], 500);
        }
    }


    /**
     * Recurses through the tree so reordering at any depth — children,
     * grandchildren, and beyond — gets persisted, not just the top two levels.
     */
    private function applyCategoryOrder(array $categories): void
    {
        foreach ($categories as $category) {
            $cat = Category::find($category['id']);
            if ($cat) {
                $cat->order = $category['order'];
                $cat->save();
            }

            if (!empty($category['sub_categories'])) {
                $this->applyCategoryOrder($category['sub_categories']);
            }
        }
    }

}
