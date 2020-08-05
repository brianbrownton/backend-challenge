<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class EverlywellApi extends Controller
{

    public function test(): JsonResponse
    {
        return response()->json(['hi'=>'there']);
    }

    public function allAvailable(int $regionId): JsonResponse
    {
        $query = "{$this->selectRows} {$this->baseQuery} LIMIT {$this->page}, " . self::QUERY_LIMIT;
        $available = Cache::remember(
            $this->buildCacheKey([$query, $regionId]),
            self::TTL,
            function () use ($regionId, $query) {
                $regionDb = $this->db[$regionId];
                $stmt = $regionDb->prepare($query);
                $stmt->execute();

                $data = $stmt->fetchAll();

                return array_map(function ($row) use ($regionId) {
                    return array_merge($row, $this->regions[$regionId]);
                }, $data);
            }
        );

        $countQuery = "{$this->selectCount} {$this->baseQuery}";
        $availableCount = Cache::remember(
            $this->buildCacheKey([$countQuery, $regionId]),
            self::TTL,
            function () use ($regionId, $countQuery) {
                $regionDb = $this->db[$regionId];
                $stmt = $regionDb->prepare($countQuery);
                $stmt->execute();

                $data = $stmt->fetch();

                return $data['rowCount'];
            }
        );

        return response()->json(new ApiResponse($available, 200, $availableCount, self::QUERY_LIMIT));
    }
}
