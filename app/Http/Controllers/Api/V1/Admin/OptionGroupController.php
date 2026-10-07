<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OptionGroupRequest;
use App\Http\Resources\Admin\OptionGroupResource;
use App\Services\Admin\CustomizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OptionGroupController extends Controller
{
    public function __construct(private readonly CustomizationService $customization)
    {
    }

    public function index()
    {
        return OptionGroupResource::collection($this->customization->listGroups());
    }

    public function store(OptionGroupRequest $request): JsonResponse
    {
        return (new OptionGroupResource($this->customization->createGroup($request->validated())))
            ->response()->setStatusCode(201);
    }

    public function show(int $id): OptionGroupResource
    {
        return new OptionGroupResource($this->customization->group($id));
    }

    public function update(OptionGroupRequest $request, int $id): OptionGroupResource
    {
        return new OptionGroupResource($this->customization->updateGroup($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        $this->customization->deleteGroup($id);

        return response()->noContent();
    }
}
