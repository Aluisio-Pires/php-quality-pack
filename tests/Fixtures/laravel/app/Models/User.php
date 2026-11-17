<?php

namespace App\Models;

class User
{
    public function isAdmin(string $role): bool
    {
        return $role == 'admin';
    }
}
