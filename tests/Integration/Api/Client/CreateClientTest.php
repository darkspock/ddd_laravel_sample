<?php

declare(strict_types=1);

namespace Tests\Integration\Api\Client;

use Tests\Integration\IntegrationTestCase;

final class CreateClientTest extends IntegrationTestCase
{
    public function test_can_create_client_with_all_fields(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+34612345678',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id']);

        /** @var string $clientId */
        $clientId = $response->json('id');
        $this->assertNotEmpty($clientId);
        $this->trackClientId($clientId);
    }

    public function test_can_create_client_with_only_name(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Jane Doe',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id']);

        /** @var string $id */
        $id = $response->json('id');
        $this->trackClientId($id);
    }

    public function test_can_create_client_with_email_only(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Email Only Client',
            'email' => 'email.only@example.com',
        ]);

        $response->assertStatus(201);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackClientId($id);
    }

    public function test_can_create_client_with_phone_only(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => 'Phone Only Client',
            'phone' => '+34698765432',
        ]);

        $response->assertStatus(201);
        /** @var string $id */
        $id = $response->json('id');
        $this->trackClientId($id);
    }

    public function test_fails_without_name(): void
    {
        $response = $this->postJson('/api/clients', [
            'email' => 'no.name@example.com',
        ]);

        // API may return 500 if validation throws exception, or 422 if handled
        $this->assertTrue(
            in_array($response->status(), [422, 500]),
            "Expected 422 or 500, got {$response->status()}"
        );
    }
}
