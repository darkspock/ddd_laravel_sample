<?php

declare(strict_types=1);

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Base class for integration tests.
 *
 * These tests run against the real database without transactions or refresh.
 * Data created during tests persists - tests should clean up after themselves.
 */
abstract class IntegrationTestCase extends TestCase
{
    /** @var array<string> IDs to clean up after test */
    protected array $createdClientIds = [];

    /** @var array<string> IDs to clean up after test */
    protected array $createdBookingIds = [];

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    protected function cleanupTestData(): void
    {
        if (! empty($this->createdBookingIds)) {
            DB::table('booking_products')
                ->whereIn('booking_id', $this->createdBookingIds)
                ->delete();

            DB::table('bookings')
                ->whereIn('id', $this->createdBookingIds)
                ->delete();
        }

        if (! empty($this->createdClientIds)) {
            DB::table('clients')
                ->whereIn('id', $this->createdClientIds)
                ->delete();
        }
    }

    protected function trackClientId(string $id): void
    {
        $this->createdClientIds[] = $id;
    }

    protected function trackBookingId(string $id): void
    {
        $this->createdBookingIds[] = $id;
    }

    protected function generateUlid(): string
    {
        return (string) \Illuminate\Support\Str::ulid();
    }
}
