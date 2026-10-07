<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OptionRequest;
use App\Http\Resources\Admin\OptionResource;
use App\Services\Admin\CustomizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OptionController extends Controller
{
    public function __construct(private readonly CustomizationService $customization)
    {
    }

    public function store(OptionRequest $request): JsonResponse
    {
        return (new OptionResource($this->customization->createOption($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function update(OptionRequest $request, int $id): OptionResource
    {
        return new OptionResource($this->customization->updateOption($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        $this->customization->deleteOption($id);

        return response()->noContent();
    }
}
