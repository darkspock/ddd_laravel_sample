<?php

declare(strict_types=1);

namespace Apps\Api\Client\Create;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Client\Domain\ValueObjects\ClientId;

final class CreateClientRequest extends AbstractFormRequest
{
    public function getDto(): CreateClientDto
    {
        return new CreateClientDto(
            id: ClientId::random(),
            name: $this->getHelper()->getString('name'),
            email: $this->getHelper()->getStringOrNull('email'),
            phone: $this->getHelper()->getStringOrNull('phone'),
        );
    }
}
