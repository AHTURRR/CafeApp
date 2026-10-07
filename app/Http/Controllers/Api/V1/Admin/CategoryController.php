<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Services\Admin\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories)
    {
    }

    public function index()
    {
        return CategoryResource::collection($this->categories->list());
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        return (new CategoryResource($this->categories->create($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function update(CategoryRequest $request, int $id): CategoryResource
    {
        return new CategoryResource($this->categories->update($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        $this->categories->delete($id);

        return response()->noContent();
    }
}
