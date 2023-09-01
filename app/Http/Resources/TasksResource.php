<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TasksResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => (string)$this->id,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->description,
                'image' => $this->image,
                'priority' => $this->priority,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ],
            'relationships' => [
                'id'=> (string)Auth::user()->id,
                'user_name' => Auth::user()->name,
                'user_email' => Auth::user()->email
            ]
        ];
    }
}
