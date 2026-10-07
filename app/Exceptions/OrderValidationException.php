<?php
namespace App\Exceptions;

use Exception;

class OrderValidationException extends Exception
{
    private array $issues;

    public function __construct(string $message, array $issues)
    {
        parent::__construct($message);
        $this->issues = $issues;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'ORDER_ITEMS_INVALID',
            'issues' => $this->issues,
        ], 422);
    }
}
