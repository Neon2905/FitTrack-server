<?php
class Env
{
    /**
     * Get an environment variable, with optional default and type casting.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value === false && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        }

        // Remove debug echo if not needed
        echo "Value: ".$value;

        if ($value === false) {
            return $default;
        }

        // Try to cast to int or bool if appropriate
        if (is_numeric($value)) {
            return $value + 0;
        }
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }

        return $value;
    }
}