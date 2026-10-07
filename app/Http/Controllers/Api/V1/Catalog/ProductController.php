<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ListProductsRequest;
use App\Http\Resources\Catalog\ProductDetailResource;
use App\Http\Resources\Catalog\ProductListResource;
use App\Http\Responses\ApiResponse;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index(ListProductsRequest $request): JsonResponse
    {
        return ApiResponse::paginated($this->catalog->products($request->filter()), ProductListResource::class);
    }

    public function show(int $id): ProductDetailResource
    {
        return new ProductDetailResource($this->catalog->product($id));
    }
}
