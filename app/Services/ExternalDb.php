<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Helper untuk akses database connection eksternal (ebilling, saleskit, fieldops).
 *
 * Setiap call dicek apakah connection valid + reachable. Kalau gagal, return null
 * supaya caller bisa fallback ke default (empty data, 0, dll).
 *
 * Jangan pakai ini di hot path (per-request check ada overhead) — better: cache
 * reachability check selama 1 menit via cache().
 */
class ExternalDb
{
    /**
     * Cek apakah connection 'name' reachable. Return true/false.
     * Cache hasilnya 1 menit.
     */
    public static function available(string $name): bool
    {
        $cacheKey = "ext_db:available:{$name}";
        return cache()->remember($cacheKey, 60, function () use ($name) {
            try {
                $cfg = config("database.connections.{$name}");
                if (!$cfg || empty($cfg['host'])) {
                    return false;
                }
                DB::connection($name)->getPdo();
                return true;
            } catch (\Throwable $e) {
                Log::debug("ExternalDb: {$name} not available", ['err' => $e->getMessage()]);
                return false;
            }
        });
    }

    /**
     * Get connection kalau available, else null.
     * Selalu wrap dalam try/catch supaya caller gak perlu defensive.
     */
    public static function connection(string $name)
    {
        try {
            if (!self::available($name)) {
                return null;
            }
            return DB::connection($name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Forget cache (untuk testing / paksa recheck).
     */
    public static function forget(string $name): void
    {
        cache()->forget("ext_db:available:{$name}");
    }
}
