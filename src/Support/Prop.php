<?php

namespace RedisJobInspector\Support;


class Prop
{
    public static function get(array $props, string $propertyName, ?string $class = null): mixed
    {
        if (array_key_exists($propertyName, $props)) {
            return $props[$propertyName];
        }

        if ($class) {
            $key = chr(0) . $class . chr(0) . $propertyName;

            return $props[$key] ?? null;
        }

        foreach ($props as $k => $v) {
            if (str_ends_with($k, $propertyName)) {
                return $v;
            }
        }

        return null;
    }
}
