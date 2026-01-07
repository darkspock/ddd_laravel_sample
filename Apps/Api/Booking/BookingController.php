<?php

declare(strict_types=1);

namespace Apps\Api\Booking;

use Apps\Api\Booking\Cancel\CancelBookingAction;
use Apps\Api\Booking\Cancel\CancelBookingRequest;
use Apps\Api\Booking\Confirm\ConfirmBookingAction;
use Apps\Api\Booking\Confirm\ConfirmBookingRequest;
use Apps\Api\Booking\Create\CreateBookingAction;
use Apps\Api\Booking\Create\CreateBookingRequest;
use Apps\Api\Booking\Index\IndexBookingsAction;
use Apps\Api\Booking\Index\IndexBookingsRequest;
use Apps\Api\Booking\Show\ShowBookingAction;
use Apps\Api\Booking\Show\ShowBookingRequest;
use Illuminate\Http\JsonResponse;

final class BookingController
{
    public function index(
        IndexBookingsRequest $request,
        IndexBookingsAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource);
    }

    public function create(
        CreateBookingRequest $request,
        CreateBookingAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource, 201);
    }

    public function show(
        ShowBookingRequest $request,
        ShowBookingAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource);
    }

    public function confirm(
        ConfirmBookingRequest $request,
        ConfirmBookingAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource);
    }

    public function cancel(
        CancelBookingRequest $request,
        CancelBookingAction $action
    ): JsonResponse {
        $resource = $action($request->getDto());

        return response()->json($resource);
    }
}
