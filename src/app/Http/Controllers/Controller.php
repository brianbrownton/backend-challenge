<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Lumen\Routing\Controller as BaseController;
use PDO;

class Controller extends BaseController
{
    protected $db;

    /**
     * Controller constructor.
     */
    public function __construct(Request $request)
    {
        try {
            $this->db = new PDO(
                'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DB'),
                env('DB_USER'),
                env('DB_PASS'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

        }
        catch (\Exception $e) {
            echo "error connecting to everlywell db";
        }
    }

    protected function buildCacheKey(array $args): string
    {
        return hash('sha256', json_encode($args));
    }
}
