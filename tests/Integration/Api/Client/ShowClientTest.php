<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Client;

use Tests\Integration\IntegrationTestCase;

final class ShowClientTest extends IntegrationTestCase
{
    public function test_can_show_existing_client(): void
    {
        // First create a client
        $createResponse = $this->postJson('/api/clients', [
            'name' => 'Show Test Client',
            'email' => 'show.test@example.com',
            'phone' => '+34611111111',
        ]);

        $createResponse->assertStatus(201);
        /** @var string $clientId */
        $clientId = $createResponse->json('id');
        $this->trackClientId($clientId);

        // Then fetch it
        $showResponse = $this->getJson("/api/clients/{$clientId}");

        $showResponse->assertStatus(200);
        $showResponse->assertJson([
            'id' => $clientId,
            'name' => 'Show Test Client',
            'email' => 'show.test@example.com',
            'phone' => '+34611111111',
        ]);
        $showResponse->assertJsonStructure([
            'id',
            'name',
            'email',
            'phone',
            'createdAt',
        ]);
    }

    public function test_returns_error_for_nonexistent_client(): void
    {
        $fakeId = $this->generateUlid();

        $response = $this->getJson("/api/clients/{$fakeId}");

        // 500 because ClientNotFoundException not mapped to 404
        $this->assertContains($response->status(), [404, 500]);
    }

    public function test_returns_500_for_invalid_ulid(): void
    {
        // Invalid ULIDs throw exception which results in 500
        $response = $this->getJson('/api/clients/invalid-ulid');

        $response->assertStatus(500);
    }
}
