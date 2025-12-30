<?php

namespace App\Enums;

enum ReportType: string
{
    case User = 'user';
    case Book = 'book';
    case Trade = 'trade';
    case Message = 'message';
}
