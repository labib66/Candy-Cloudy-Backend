<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          "id"=>$this->id,
          'user_email' => $this->user->email,
          'user_name' => $this->user->username,
          "plan_id"=>$this->plan_id,
          'status' => $this->isValid(),
          // "gateway_name"=>$this->gateway_name,
          // "gateway_id"=>$this->gateway_id,
          // "quantity"=>$this->quantity,
          "description"=>$this->description,
          // "trial_ends_at"=>$this->trial_ends_at,
          "created_at"=>$this->created_at,
          "ends_at"=>$this->ends_at,
          "renews_at"=>$this->renews_at,

        ];
    }
}
