<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseEnvironmentService
{
    private const CACHE_STORE = 'file';
    private const CACHE_KEY = 'book2book.db_mode';

    public function getMode(): string
    {
        $mode = Cache::store(self::CACHE_STORE)->get(self::CACHE_KEY, config('book2book.db_mode', 'sandbox'));

        return $this->normalizeMode($mode);
    }

    public function setMode(string $mode): void
    {
        Cache::store(self::CACHE_STORE)->forever(self::CACHE_KEY, $this->normalizeMode($mode));
    }

    public function apply(): void
    {
        $connection = $this->getConnectionName();
        config(['database.default' => $connection]);
        DB::setDefaultConnection($connection);
    }

    public function getConnectionName(): string
    {
        return $this->getMode() === 'production' ? 'production' : 'sandbox';
    }

    private function normalizeMode(?string $mode): string
    {
        return $mode === 'production' ? 'production' : 'sandbox';
    }
}
