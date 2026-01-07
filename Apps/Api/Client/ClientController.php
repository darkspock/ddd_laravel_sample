<?php

declare(strict_types=1);

namespace Apps\Api\Client;

use Apps\Api\Client\Create\CreateClientAction;
use Apps\Api\Client\Create\CreateClientRequest;
use Apps\Api\Client\Show\ShowClientAction;
use Apps\Api\Client\Show\ShowClientRequest;
use Illuminate\Http\JsonResponse;

final class ClientController
{
    public function create(
        CreateClientRequest $request,
        CreateClientAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource, 201);
    }

    public function show(
        ShowClientRequest $request,
        ShowClientAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource);
    }
}
