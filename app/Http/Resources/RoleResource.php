<?php

namespace App\Http\Resources;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          'id' => $this->id,
          'name' => $this->name,
          'legacy_permissions' => $this->legacy_permissions,
          'default' => $this->default,
          'guests' => $this->guests,
          'type' => $this->type,
          // 'internal' => $this->internal,
          'description' => $this->description,
          // 'a'=> Auth::guard('api')->id(),

        ];
    }
}
