<?php

namespace App\Http\Controllers\Pos\Product;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Product\Product;
use App\Models\Pos\ProductSpecification\ProductSpecification;
use App\Models\Pos\SpecificationGroup\SpecificationGroup;
use Illuminate\Http\Request;

class ProductSpecificationController extends Controller
{
    public function index(Product $product)
    {
        $specs = ProductSpecification::query()
            ->where('product_id', $product->id)
            ->with(['parent:id,name', 'sub:id,name'])
            ->orderBy('parent_id')
            ->get()
            ->map(fn($spec) => [
                'id'      => $spec->id,
                'parent'  => $spec->parent ? ['id' => $spec->parent->id, 'name' => $spec->parent->name] : null,
                'sub'     => $spec->sub    ? ['id' => $spec->sub->id,    'name' => $spec->sub->name]    : null,
                'details' => $spec->details,
            ]);

        $groups = SpecificationGroup::query()
            ->whereNull('parent_id')
            ->with('subSpecificationGroups:id,name,parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'specs'  => $specs,
            'groups' => $groups,
        ]);
    }

    public function store(Request $request, Product $product)
    {
        $request->validate([
            'parent_id' => 'required|integer|exists:specification_groups,id',
            'sub_id'    => 'nullable|integer|exists:specification_groups,id',
            'details'   => 'required|string|max:1000',
        ]);

        $spec = ProductSpecification::create([
            'product_id' => $product->id,
            'parent_id'  => $request->parent_id,
            'sub_id'     => $request->sub_id,
            'details'    => $request->details,
        ]);

        $spec->load(['parent:id,name', 'sub:id,name']);

        return response()->json([
            'message' => 'Specification added successfully',
            'spec'    => [
                'id'      => $spec->id,
                'parent'  => $spec->parent ? ['id' => $spec->parent->id, 'name' => $spec->parent->name] : null,
                'sub'     => $spec->sub    ? ['id' => $spec->sub->id,    'name' => $spec->sub->name]    : null,
                'details' => $spec->details,
            ],
        ], 201);
    }

    public function update(Request $request, Product $product, ProductSpecification $specification)
    {
        if ($specification->product_id !== $product->id) {
            throw new GeneralException('Specification does not belong to this product', 403);
        }

        $request->validate([
            'parent_id' => 'required|integer|exists:specification_groups,id',
            'sub_id'    => 'nullable|integer|exists:specification_groups,id',
            'details'   => 'required|string|max:1000',
        ]);

        $specification->update([
            'parent_id' => $request->parent_id,
            'sub_id'    => $request->sub_id,
            'details'   => $request->details,
        ]);

        $specification->load(['parent:id,name', 'sub:id,name']);

        return response()->json([
            'message' => 'Specification updated successfully',
            'spec'    => [
                'id'      => $specification->id,
                'parent'  => $specification->parent ? ['id' => $specification->parent->id, 'name' => $specification->parent->name] : null,
                'sub'     => $specification->sub    ? ['id' => $specification->sub->id,    'name' => $specification->sub->name]    : null,
                'details' => $specification->details,
            ],
        ]);
    }

    public function destroy(Product $product, ProductSpecification $specification)
    {
        if ($specification->product_id !== $product->id) {
            throw new GeneralException('Specification does not belong to this product', 403);
        }

        $specification->delete();

        return successResponse('Specification deleted successfully');
    }
}
