<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          "title"=>$this->title,
          "body"=>$this->body,
          "slug"=>$this->slug,
          "type"=>$this->type,
          "meta"=>$this->meta,
        ];
    }
}
