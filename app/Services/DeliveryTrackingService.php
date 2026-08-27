<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryLocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class DeliveryTrackingService
{
    public function getLatestLocation(int $deliveryId): ?DeliveryLocation
    {
        return DeliveryLocation::query()
            ->where('delivery_id', $deliveryId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();
    }

    public function getLocationHistory(int $deliveryId): Collection
    {
        return DeliveryLocation::query()
            ->where('delivery_id', $deliveryId)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();
    }

    public function saveLocation(int $deliveryId, int $deliveryPersonId, float $latitude, float $longitude): DeliveryLocation
    {
        $delivery = Delivery::query()->where('id', $deliveryId)->first();

        if (!$delivery) {
            throw new RuntimeException('Delivery not found.');
        }

        if ($delivery->delivery_person_id != $deliveryPersonId) {
            throw new RuntimeException('Delivery person does not belong to this delivery.');
        }

        $this->validateCoordinates($latitude, $longitude);

        return DeliveryLocation::create([
            'delivery_id' => $deliveryId,
            'delivery_person_id' => $deliveryPersonId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => Carbon::now(),
            'created_by_id' => $deliveryPersonId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function getDeliveryTrackingInfo(int $deliveryId): ?array
    {
        $delivery = Delivery::query()
            ->with(['order.customer', 'deliveryPerson'])
            ->where('id', $deliveryId)
            ->first();

        if (!$delivery) {
            return null;
        }

        $latestLocation = $this->getLatestLocation($deliveryId);
        $locationCount = DeliveryLocation::query()->where('delivery_id', $deliveryId)->count();

        return [
            'delivery' => $delivery,
            'latest_location' => $latestLocation,
            'location_count' => $locationCount,
        ];
    }

    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new RuntimeException('Invalid latitude value. Must be between -90 and 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new RuntimeException('Invalid longitude value. Must be between -180 and 180.');
        }
    }
}
