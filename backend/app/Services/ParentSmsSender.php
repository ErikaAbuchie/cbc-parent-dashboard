<?php

namespace App\Services;

interface ParentSmsSender
{
    public function send(string $phone, string $message): void;
}
