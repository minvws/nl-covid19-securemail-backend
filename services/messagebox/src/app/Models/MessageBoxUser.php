<?php

namespace App\Models;

interface MessageBoxUser
{
    public function getAuthIdentifierName(): string;
    public function getAuthIdentifier(): string;
}
