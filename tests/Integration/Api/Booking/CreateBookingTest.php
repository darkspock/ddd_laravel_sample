<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Booking;

use Tests\Integration\IntegrationTestCase;

final class CreateBookingTest extends IntegrationTestCase
{
    private string $clientId;

    private string $restaurantId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a client for bookings
        $response = $this->postJson('/api/clients', [
            'name' => 'Booking Test Client',
            'email' => 'booking.test@example.com',
        ]);
        /** @var string $clientId */
        $clientId = $response->json('id');
        $this->clientId = $clientId;
        $this->trackClientId($this->clientId);

        // Use a fixed restaurant ID (restaurants are not managed via API)
        $this->restaurantId = $this->generateUlid();
    }

    public function test_can_create_booking_without_products(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-15',
            'time' => '19:00',
            'party_size' => 4,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id']);

        /** @var string $bookingId */
        $bookingId = $response->json('id');
        $this->assertNotEmpty($bookingId);
        $this->trackBookingId($bookingId);
    }

    public function test_can_create_booking_with_special_requests(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-16',
            'time' => '20:00',
            'party_size' => 2,
            'special_requests' => 'Window table please',
        ]);

        $response->assertStatus(201);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);
    }

    public function test_can_create_booking_with_products(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-17',
            'time' => '21:00',
            'party_size' => 6,
            'products' => [
                ['type' => 'menu', 'quantity' => 6],
                ['type' => 'bottle_of_wine', 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);
    }

    public function test_can_create_booking_with_table_reservation_product(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-18',
            'time' => '13:00',
            'party_size' => 3,
            'products' => [
                ['type' => 'table_reservation', 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);
    }

    public function test_can_create_booking_with_event_product(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-03-01',
            'time' => '18:00',
            'party_size' => 20,
            'products' => [
                ['type' => 'event', 'quantity' => 1],
                ['type' => 'menu', 'quantity' => 20],
            ],
        ]);

        $response->assertStatus(201);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);
    }

    public function test_fails_without_client_id(): void
    {
        $response = $this->postJson('/api/bookings', [
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-15',
            'time' => '19:00',
            'party_size' => 4,
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_fails_without_restaurant_id(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'date' => '2026-02-15',
            'time' => '19:00',
            'party_size' => 4,
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_fails_without_date(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'time' => '19:00',
            'party_size' => 4,
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_fails_without_time(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-15',
            'party_size' => 4,
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_fails_without_party_size(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-15',
            'time' => '19:00',
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_fails_with_invalid_product_type(): void
    {
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-02-15',
            'time' => '19:00',
            'party_size' => 4,
            'products' => [
                ['type' => 'invalid_type', 'quantity' => 1],
            ],
        ]);

        $this->assertContains($response->status(), [422, 500]);
    }
}
