<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Librarian = 'librarian';
    case Admin = 'admin';
}
