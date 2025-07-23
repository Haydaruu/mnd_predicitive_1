<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'SuperAdmin';
    case Admin = 'Admin';
    case Agent = 'Agent';
}
