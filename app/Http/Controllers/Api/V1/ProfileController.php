<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Account\ProfileService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile)
    {
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        return new UserResource($this->profile->update($request->user(), $request->validated()));
    }
}
