<?php

namespace App\Enums;

enum BookCondition: string
{
    case NewLike = 'new_like';
    case Good = 'good';
    case Acceptable = 'acceptable';
    case Poor = 'poor';
}
