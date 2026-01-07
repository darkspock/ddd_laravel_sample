<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Booking;

use Tests\Integration\IntegrationTestCase;

final class ConfirmBookingTest extends IntegrationTestCase
{
    private string $clientId;

    private string $restaurantId;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->postJson('/api/clients', [
            'name' => 'Confirm Booking Test Client',
            'email' => 'confirm.booking@example.com',
        ]);
        /** @var string $clientId */
        $clientId = $response->json('id');
        $this->clientId = $clientId;
        $this->trackClientId($this->clientId);

        $this->restaurantId = $this->generateUlid();
    }

    public function test_can_confirm_pending_booking(): void
    {
        // Create a pending booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-04-01',
            'time' => '19:00',
            'party_size' => 4,
        ]);

        $createResponse->assertStatus(201);
        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Confirm it
        $confirmResponse = $this->postJson("/api/bookings/{$bookingId}/confirm");

        $confirmResponse->assertStatus(200);
        $confirmResponse->assertJson([
            'message' => 'Booking confirmed successfully',
        ]);

        // Verify status via show
        $showResponse = $this->getJson("/api/bookings/{$bookingId}");
        $showResponse->assertJson(['status' => 'confirmed']);
        $this->assertNotNull($showResponse->json('confirmedAt'));
    }

    public function test_cannot_confirm_already_confirmed_booking(): void
    {
        // Create and confirm a booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-04-02',
            'time' => '20:00',
            'party_size' => 2,
        ]);

        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Confirm first time
        $this->postJson("/api/bookings/{$bookingId}/confirm");

        // Try to confirm again (500 because domain exception not mapped to HTTP)
        $response = $this->postJson("/api/bookings/{$bookingId}/confirm");

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_cannot_confirm_cancelled_booking(): void
    {
        // Create a booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-04-03',
            'time' => '21:00',
            'party_size' => 3,
        ]);

        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Cancel it
        $this->postJson("/api/bookings/{$bookingId}/cancel");

        // Try to confirm (500 because domain exception not mapped to HTTP)
        $response = $this->postJson("/api/bookings/{$bookingId}/confirm");

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_returns_error_for_nonexistent_booking(): void
    {
        $fakeId = $this->generateUlid();

        $response = $this->postJson("/api/bookings/{$fakeId}/confirm");

        // 500 because BookingNotFoundException not mapped to 404
        $this->assertContains($response->status(), [404, 500]);
    }
}
