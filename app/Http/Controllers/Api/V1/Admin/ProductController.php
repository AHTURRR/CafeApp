<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminProductsRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\SyncOptionGroupsRequest;
use App\Http\Requests\Admin\UpdateProductStatusRequest;
use App\Http\Requests\Admin\UploadProductImageRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Services\Admin\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function index(ListAdminProductsRequest $request): JsonResponse
    {
        return ApiResponse::paginated($this->products->paginate($request->filter()), ProductResource::class);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        return (new ProductResource($this->products->create($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function show(int $id): ProductResource
    {
        return new ProductResource($this->products->find($id));
    }

    public function update(ProductRequest $request, int $id): ProductResource
    {
        return new ProductResource($this->products->update($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        $this->products->delete($id);

        return response()->noContent();
    }

    public function updateStatus(UpdateProductStatusRequest $request, int $id): ProductResource
    {
        return new ProductResource($this->products->updateStatus($id, $request->status()));
    }

    public function syncOptionGroups(SyncOptionGroupsRequest $request, int $id): ProductResource
    {
        return new ProductResource($this->products->syncOptionGroups($id, $request->validated('option_groups')));
    }

    public function uploadImage(UploadProductImageRequest $request, int $id): ProductResource
    {
        return new ProductResource($this->products->uploadImage($id, $request->file('image')));
    }

    public function deleteImage(int $id): Response
    {
        $this->products->deleteImage($id);

        return response()->noContent();
    }
}
