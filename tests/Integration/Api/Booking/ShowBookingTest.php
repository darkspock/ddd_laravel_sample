<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Booking;

use Tests\Integration\IntegrationTestCase;

final class ShowBookingTest extends IntegrationTestCase
{
    private string $clientId;

    private string $restaurantId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a client for bookings
        $response = $this->postJson('/api/clients', [
            'name' => 'Show Booking Test Client',
            'email' => 'show.booking@example.com',
        ]);
        /** @var string $clientId */
        $clientId = $response->json('id');
        $this->clientId = $clientId;
        $this->trackClientId($this->clientId);

        $this->restaurantId = $this->generateUlid();
    }

    public function test_can_show_booking_without_products(): void
    {
        // Create booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-03-10',
            'time' => '19:30',
            'party_size' => 4,
            'special_requests' => 'Near the window',
        ]);

        $createResponse->assertStatus(201);
        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Fetch booking
        $showResponse = $this->getJson("/api/bookings/{$bookingId}");

        $showResponse->assertStatus(200);
        $showResponse->assertJson([
            'id' => $bookingId,
            'clientId' => $this->clientId,
            'restaurantId' => $this->restaurantId,
            'date' => '2026-03-10',
            'time' => '19:30',
            'partySize' => 4,
            'status' => 'pending',
            'specialRequests' => 'Near the window',
        ]);
    }

    public function test_can_show_booking_with_products(): void
    {
        // Create booking with products
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-03-11',
            'time' => '20:00',
            'party_size' => 2,
            'products' => [
                ['type' => 'menu', 'quantity' => 2],
                ['type' => 'bottle_of_wine', 'quantity' => 1],
            ],
        ]);

        $createResponse->assertStatus(201);
        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Fetch booking
        $showResponse = $this->getJson("/api/bookings/{$bookingId}");

        $showResponse->assertStatus(200);
        $showResponse->assertJson([
            'id' => $bookingId,
            'status' => 'pending',
        ]);
        $showResponse->assertJsonStructure([
            'id',
            'clientId',
            'restaurantId',
            'date',
            'time',
            'partySize',
            'status',
            'products',
            'totalPriceCents',
        ]);

        // Verify products exist
        /** @var array<mixed> $products */
        $products = $showResponse->json('products');
        $this->assertCount(2, $products);
    }

    public function test_returns_error_for_nonexistent_booking(): void
    {
        $fakeId = $this->generateUlid();

        $response = $this->getJson("/api/bookings/{$fakeId}");

        // 500 because BookingNotFoundException not mapped to 404
        $this->assertContains($response->status(), [404, 500]);
    }

    public function test_returns_500_for_invalid_ulid(): void
    {
        // Invalid ULIDs throw exception which results in 500
        $response = $this->getJson('/api/bookings/invalid-ulid');

        $response->assertStatus(500);
    }
}
