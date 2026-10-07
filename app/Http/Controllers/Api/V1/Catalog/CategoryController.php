<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\CategoryResource;
use App\Services\Catalog\CatalogService;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index()
    {
        return CategoryResource::collection($this->catalog->categories());
    }
}
