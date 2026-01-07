<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Booking;

use Tests\Integration\IntegrationTestCase;

final class IndexBookingsTest extends IntegrationTestCase
{
    private string $clientId;

    private string $restaurantId;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->postJson('/api/clients', [
            'name' => 'Index Bookings Test Client',
            'email' => 'index.bookings@example.com',
        ]);
        /** @var string $clientId */
        $clientId = $response->json('id');
        $this->clientId = $clientId;
        $this->trackClientId($this->clientId);

        $this->restaurantId = $this->generateUlid();
    }

    public function test_can_list_bookings(): void
    {
        // Create some bookings
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/bookings', [
                'client_id' => $this->clientId,
                'restaurant_id' => $this->restaurantId,
                'date' => "2026-06-0{$i}",
                'time' => '19:00',
                'party_size' => $i + 1,
            ]);
            /** @var string $id */
            $id = $response->json('id');
            $this->trackBookingId($id);
        }

        // List bookings filtered by restaurant
        $listResponse = $this->getJson("/api/bookings?restaurant_id={$this->restaurantId}");

        $listResponse->assertStatus(200);
        $listResponse->assertJsonStructure([
            'items' => [
                '*' => [
                    'id',
                    'clientId',
                    'clientName',
                    'restaurantId',
                    'date',
                    'time',
                    'partySize',
                    'status',
                    'totalPriceCents',
                ],
            ],
            'total',
            'pageSize',
            'page',
        ]);
    }

    public function test_can_filter_by_client_id(): void
    {
        // Create booking for our client
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-06-10',
            'time' => '20:00',
            'party_size' => 2,
        ]);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);

        // Filter by client_id
        $listResponse = $this->getJson("/api/bookings?client_id={$this->clientId}");

        $listResponse->assertStatus(200);

        /** @var array<array{clientId: string}> $items */
        $items = $listResponse->json('items');
        foreach ($items as $booking) {
            $this->assertEquals($this->clientId, $booking['clientId']);
        }
    }

    public function test_can_filter_by_restaurant_id(): void
    {
        // Create booking
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-06-11',
            'time' => '21:00',
            'party_size' => 4,
        ]);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);

        // Filter by restaurant_id
        $listResponse = $this->getJson("/api/bookings?restaurant_id={$this->restaurantId}");

        $listResponse->assertStatus(200);

        /** @var array<array{restaurantId: string}> $items */
        $items = $listResponse->json('items');
        foreach ($items as $booking) {
            $this->assertEquals($this->restaurantId, $booking['restaurantId']);
        }
    }

    public function test_can_filter_by_status(): void
    {
        // Create and confirm a booking
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-06-12',
            'time' => '18:00',
            'party_size' => 3,
        ]);
        /** @var string $bookingId */
        $bookingId = $response->json('id');
        $this->trackBookingId($bookingId);

        $this->postJson("/api/bookings/{$bookingId}/confirm");

        // Filter by confirmed status and restaurant
        $listResponse = $this->getJson("/api/bookings?status=confirmed&restaurant_id={$this->restaurantId}");

        $listResponse->assertStatus(200);

        /** @var array<array{status: string}> $items */
        $items = $listResponse->json('items');
        foreach ($items as $booking) {
            $this->assertEquals('confirmed', $booking['status']);
        }
    }

    public function test_can_filter_by_date_range(): void
    {
        // Create booking for specific date
        $response = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-07-15',
            'time' => '19:00',
            'party_size' => 2,
        ]);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackBookingId($id);

        // Filter by date range
        $listResponse = $this->getJson("/api/bookings?date_from=2026-07-01&date_to=2026-07-31&restaurant_id={$this->restaurantId}");

        $listResponse->assertStatus(200);

        /** @var array<array{date: string}> $items */
        $items = $listResponse->json('items');
        foreach ($items as $booking) {
            $this->assertGreaterThanOrEqual('2026-07-01', $booking['date']);
            $this->assertLessThanOrEqual('2026-07-31', $booking['date']);
        }
    }

    public function test_can_paginate_results(): void
    {
        // Create several bookings
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/bookings', [
                'client_id' => $this->clientId,
                'restaurant_id' => $this->restaurantId,
                'date' => "2026-08-0{$i}",
                'time' => '19:00',
                'party_size' => 2,
            ]);
            /** @var string $id */
            $id = $response->json('id');
            $this->trackBookingId($id);
        }

        // Get first page with limit 2
        $page1Response = $this->getJson("/api/bookings?limit=2&offset=0&restaurant_id={$this->restaurantId}");
        $page1Response->assertStatus(200);
        /** @var array<mixed> $page1Items */
        $page1Items = $page1Response->json('items');
        $this->assertLessThanOrEqual(2, count($page1Items));

        // Get second page
        $page2Response = $this->getJson("/api/bookings?limit=2&offset=2&restaurant_id={$this->restaurantId}");
        $page2Response->assertStatus(200);
    }

    public function test_returns_empty_list_when_no_bookings_match(): void
    {
        $nonExistentRestaurantId = $this->generateUlid();

        $response = $this->getJson("/api/bookings?restaurant_id={$nonExistentRestaurantId}");

        $response->assertStatus(200);
        $response->assertJson([
            'items' => [],
            'total' => 0,
        ]);
    }
}
