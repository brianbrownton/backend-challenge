<?php


namespace App;


/**
 * Class ApiResponse
 * @package App
 */
class ApiResponse
{
    public $statusCode;
    public $data;
    public $rowCount;
    public $pages;


    /**
     * ApiResponse constructor.
     * @param array $data
     * @param int $statusCode
     * @param int $rowCount
     * @param int $pageSize
     */
    public function __construct(array $data = [], int $statusCode = 200, int $rowCount = 1, int $pageSize = 1)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->rowCount = $rowCount;
        $this->pages = ceil($rowCount / $pageSize);
    }


    public function ToArray(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'rowCount' => $this->rowCount,
            'pages' => $this->pages,
            'data' => $this->data
        ];
    }
}
