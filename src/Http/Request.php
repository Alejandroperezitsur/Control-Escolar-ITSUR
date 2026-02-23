<?php
namespace App\Http;

class Request
{
    public static function getString(string $key, ?string $default = null): ?string
    {
        $val = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW);
        if ($val === null || $val === false) {
            return $default;
        }
        $val = trim((string)$val);
        if ($val === '') {
            return $default;
        }
        return $val;
    }

    public static function getInt(string $key, ?int $default = null): ?int
    {
        $val = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
        if ($val === null || $val === false) {
            return $default;
        }
        return (int)$val;
    }

    public static function postString(string $key, ?string $default = null): ?string
    {
        $val = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW);
        if ($val === null || $val === false) {
            return $default;
        }
        $val = trim((string)$val);
        if ($val === '') {
            return $default;
        }
        return $val;
    }

    public static function postInt(string $key, ?int $default = null): ?int
    {
        $val = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
        if ($val === null || $val === false) {
            return $default;
        }
        return (int)$val;
    }

    public static function hasGet(string $key): bool
    {
        $val = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        return $val !== null;
    }

    public static function hasPost(string $key): bool
    {
        $val = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        return $val !== null;
    }

    public static function getAll(): array
    {
        $data = filter_input_array(INPUT_GET, FILTER_UNSAFE_RAW) ?? [];
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }
        return $data;
    }

    public static function postAll(): array
    {
        $data = filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) ?? [];
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $data[$k] = trim($v);
            }
        }
        return $data;
    }

    public static function input(string $key)
    {
        $v = filter_input(INPUT_POST, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        if ($v !== null) {
            return is_string($v) ? trim($v) : $v;
        }
        $v = filter_input(INPUT_GET, $key, FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        if ($v !== null) {
            return is_string($v) ? trim($v) : $v;
        }
        return null;
    }
}
