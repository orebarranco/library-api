<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AuditLog;

use App\Http\Resources\Api\V1\AuditLog\AuditLogResource;
use App\Models\AuditLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class AuditLogController
{
    use ApiResponse;

    private const int PER_PAGE = 15;

    /**
     * The trail, newest first. Read-only by design: there is no store, update
     * or destroy, because an audit log that can be edited proves nothing.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = QueryBuilder::for(AuditLog::query()->with('user'))
            ->allowedFilters(
                AllowedFilter::exact('action'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('model_type'),
            )
            ->defaultSort('-created_at')
            ->paginate(self::PER_PAGE)
            ->appends($request->query());

        return $this->successCollection(AuditLogResource::collection($logs));
    }
}
