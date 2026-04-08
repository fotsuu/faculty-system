<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Ensures the student name column appears first in class record tables,
 * regardless of JSON key order (DB) or Excel column order (session preview).
 */
final class ClassRecordColumnOrder
{
    public static function isStudentNameHeader(string $header): bool
    {
        $t = strtolower(trim($header));
        if ($t === '') {
            return false;
        }

        $canonical = [
            'name of student',
            'name of students',
            'student name',
            'students name',
            'full name',
            'name',
        ];
        if (in_array($t, $canonical, true)) {
            return true;
        }

        if (preg_match('/^name\s+of\s+students?$/u', trim($header))) {
            return true;
        }

        if (str_contains($t, 'name') && str_contains($t, 'student')) {
            return true;
        }

        return false;
    }

    public static function reorderScoreKeys(Collection $scoreKeys): Collection
    {
        $nameHeader = $scoreKeys->first(function ($key) {
            return self::isStudentNameHeader((string) $key);
        });
        if ($nameHeader === null) {
            return $scoreKeys->values();
        }

        return collect([$nameHeader])
            ->merge($scoreKeys->reject(fn ($key) => (string) $key === (string) $nameHeader))
            ->values();
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  array<int, array<int, mixed>>  $bodyRows
     * @return array{headers: array<int, mixed>, rows: array<int, array<int, mixed>>}
     */
    public static function reorderTabularHeadersAndRows(array $headers, array $bodyRows): array
    {
        $nameIdx = null;
        foreach ($headers as $i => $h) {
            if (self::isStudentNameHeader((string) $h)) {
                $nameIdx = $i;
                break;
            }
        }

        if ($nameIdx === null || $nameIdx === 0) {
            return ['headers' => $headers, 'rows' => $bodyRows];
        }

        $newHeaders = array_values($headers);
        $nameHeaderCell = array_splice($newHeaders, $nameIdx, 1);
        array_unshift($newHeaders, $nameHeaderCell[0]);

        $newRows = [];
        foreach ($bodyRows as $row) {
            if (! is_array($row)) {
                $newRows[] = $row;

                continue;
            }
            $copy = array_values($row);
            if (! array_key_exists($nameIdx, $copy)) {
                $newRows[] = $row;

                continue;
            }
            $nameVal = array_splice($copy, $nameIdx, 1);
            array_unshift($copy, $nameVal[0] ?? '');
            $newRows[] = $copy;
        }

        return ['headers' => $newHeaders, 'rows' => $newRows];
    }
}
