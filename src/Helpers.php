<?php

namespace Cervantes;

/**
 * Pure, framework-free helper functions extracted from the request handlers.
 *
 * The handlers themselves mix HTTP, session and database concerns which makes
 * them impractical to unit test directly. The reusable business logic lives
 * here so it can be covered by unit tests and shared across handlers.
 */
class Helpers
{
    /**
     * Build the stored file name for an upload.
     *
     * Mirrors the historical behaviour of `time() . '_' . basename($name)`
     * used by the news, procurement and job-application upload handlers.
     */
    public static function uploadFileName(string $originalName, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();

        return $timestamp . '_' . basename($originalName);
    }

    /**
     * Whether the given raw value is a usable numeric record id.
     */
    public static function isValidId(mixed $value): bool
    {
        return isset($value) && $value !== '' && is_numeric($value);
    }

    /**
     * Normalise a raw request value into an integer record id.
     */
    public static function toId(mixed $value): int
    {
        return (int) $value;
    }

    /**
     * Destination path a user should be redirected to after a successful login,
     * based on their role.
     */
    public static function loginRedirect(string $role): string
    {
        return $role === 'SUPER_ADMIN'
            ? 'admin/dashboard.php'
            : 'lgu/dashboard.php';
    }

    /**
     * The status every newly posted job is forced to.
     */
    public static function defaultJobStatus(): string
    {
        return 'OPEN';
    }

    /**
     * Trim a possibly-missing request value into a clean string.
     */
    public static function sanitizeText(?string $value): string
    {
        return trim($value ?? '');
    }
}
