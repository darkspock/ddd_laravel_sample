<?php

declare(strict_types=1);

namespace Apps\Api\Client\Show;

use Apps\Shared\Http\AbstractFormRequest;
use Src\Client\Domain\ValueObjects\ClientId;

final class ShowClientRequest extends AbstractFormRequest
{
    public function getDto(): ShowClientDto
    {
        return new ShowClientDto(
            clientId: ClientId::fromString($this->route('id')),
        );
    }
}
