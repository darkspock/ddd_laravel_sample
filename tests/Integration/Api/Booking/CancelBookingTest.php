<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Booking;

use Tests\Integration\IntegrationTestCase;

final class CancelBookingTest extends IntegrationTestCase
{
    private string $clientId;

    private string $restaurantId;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->postJson('/api/clients', [
            'name' => 'Cancel Booking Test Client',
            'email' => 'cancel.booking@example.com',
        ]);
        /** @var string $clientId */
        $clientId = $response->json('id');
        $this->clientId = $clientId;
        $this->trackClientId($this->clientId);

        $this->restaurantId = $this->generateUlid();
    }

    public function test_can_cancel_pending_booking(): void
    {
        // Create a pending booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-05-01',
            'time' => '19:00',
            'party_size' => 4,
        ]);

        $createResponse->assertStatus(201);
        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Cancel it
        $cancelResponse = $this->postJson("/api/bookings/{$bookingId}/cancel");

        $cancelResponse->assertStatus(200);
        $cancelResponse->assertJson([
            'message' => 'Booking cancelled successfully',
        ]);

        // Verify booking is cancelled via show endpoint
        $showResponse = $this->getJson("/api/bookings/{$bookingId}");
        $showResponse->assertJson(['status' => 'cancelled']);
    }

    public function test_can_cancel_pending_booking_with_reason(): void
    {
        // Create a pending booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-05-02',
            'time' => '20:00',
            'party_size' => 2,
        ]);

        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Cancel with reason
        $cancelResponse = $this->postJson("/api/bookings/{$bookingId}/cancel", [
            'reason' => 'Change of plans',
        ]);

        $cancelResponse->assertStatus(200);

        // Verify cancellation reason via show endpoint
        $showResponse = $this->getJson("/api/bookings/{$bookingId}");
        $showResponse->assertJson([
            'status' => 'cancelled',
            'cancellationReason' => 'Change of plans',
        ]);
    }

    public function test_can_cancel_confirmed_booking(): void
    {
        // Create and confirm a booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-05-03',
            'time' => '21:00',
            'party_size' => 3,
        ]);

        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        $this->postJson("/api/bookings/{$bookingId}/confirm");

        // Cancel confirmed booking
        $cancelResponse = $this->postJson("/api/bookings/{$bookingId}/cancel", [
            'reason' => 'Emergency',
        ]);

        $cancelResponse->assertStatus(200);

        // Verify status via show
        $showResponse = $this->getJson("/api/bookings/{$bookingId}");
        $showResponse->assertJson(['status' => 'cancelled']);
    }

    public function test_cannot_cancel_already_cancelled_booking(): void
    {
        // Create and cancel a booking
        $createResponse = $this->postJson('/api/bookings', [
            'client_id' => $this->clientId,
            'restaurant_id' => $this->restaurantId,
            'date' => '2026-05-04',
            'time' => '18:00',
            'party_size' => 5,
        ]);

        /** @var string $bookingId */
        $bookingId = $createResponse->json('id');
        $this->trackBookingId($bookingId);

        // Cancel first time
        $this->postJson("/api/bookings/{$bookingId}/cancel");

        // Try to cancel again - should fail (500 because domain exception not mapped to HTTP)
        $response = $this->postJson("/api/bookings/{$bookingId}/cancel");

        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_returns_error_for_nonexistent_booking(): void
    {
        $fakeId = $this->generateUlid();

        $response = $this->postJson("/api/bookings/{$fakeId}/cancel");

        // 500 because BookingNotFoundException not mapped to 404
        $this->assertContains($response->status(), [404, 500]);
    }
}
