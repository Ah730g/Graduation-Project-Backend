<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "Title" => $this->Title,
            "Price" => $this->Price,
            "Address" => $this->Address,
            "Description" => $this->Description,
            "City" => $this->City,
            "Bedrooms" => $this->Bedrooms,
            "Bathrooms" => $this->Bathrooms,
            "latitude" => $this->Latitude,
            "longitude" => $this->Longitude,
            "type" => $this->Type,
            "porperty_id" => $this->porperty_id,
            "property" => new PropertyResource($this->porperty),
            "utilities_policy" => $this->Utilities_Policy,
            "pet_policy" => $this->Pet_Policy,
            "income_policy" => $this->Income_Policy,
            "total_size" => $this->Total_Size,
            "bus" => $this->Bus,
            "resturant" => $this->Resturant,
            "school" => $this->School,
            "images" => $this->postimage,
            "duration_prices" => $this->whenLoaded('durationPrices', function() {
                return $this->durationPrices->map(function($dp) {
                    return [
                        'duration_type' => $dp->duration_type,
                        'price' => $dp->price,
                    ];
                });
            })
        ];
    }
}
