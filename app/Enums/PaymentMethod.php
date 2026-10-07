<?php
namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case QRIS = 'QRIS';
    case EWALLET = 'EWALLET';
    case VIRTUAL_ACCOUNT = 'VIRTUAL_ACCOUNT';
}