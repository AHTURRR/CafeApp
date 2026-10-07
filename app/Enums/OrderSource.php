<?php
namespace App\Enums;

enum OrderSource: string
{
    case CUSTOMER = 'customer';
    case CASHIER = 'cashier';
}