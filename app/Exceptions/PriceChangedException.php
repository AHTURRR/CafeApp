<?php
namespace App\Exceptions;

use Exception;

class PriceChangedException extends Exception
{
    private int $newTotal;

    public function __construct(string $message, int $newTotal)
    {
        parent::__construct($message);
        $this->newTotal = $newTotal;
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'PRICE_CHANGED',
            'total' => $this->newTotal,
        ], 409);
    }
}
