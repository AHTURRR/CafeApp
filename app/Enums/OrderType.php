<?php
namespace App\Enums;

enum OrderType: string
{
    case DINE_IN = 'DINE_IN';
    case TAKE_AWAY = 'TAKE_AWAY';
}