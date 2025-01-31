<?php

use Alchemy\Component\Yaml\Yaml;

class Language
{
    protected string $languageFolder;
    protected string $fileExtension = '.yaml';
    public array $availableLanguages = [];
    protected $languageData = [];

    public function __construct(public string $languageCode = 'en')
    {
        global $config;
        $this->languageFolder = ($config->settings['languageFolder']) ?? null;
        $this->loadLanguage();
    }

    protected function loadLanguage()
    {
        $languageFile = rtrim($this->languageFolder, '/') . '/' . $this->languageCode . $this->fileExtension;
        $yaml = new Yaml();
        $this->languageData = $yaml->load($languageFile);
    }

    public static function getAvailableLanguages(): array
    {
        global $config;
        return array_map('trim', explode(',', strtolower($config->settings['languages'])));
    }

    public function getLanguageData()
    {
        return $this->languageData;
    }

    // dot notation handler from https://stackoverflow.com/a/39118759
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $array = ['L' => $this->languageData];
        if (! static::accessible($array)) {
            return value($default);
        }
        if (is_null($key)) {
            return $array;
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        if (strpos($key, '.') === false) {
            return $array[$key] ?? value($default);
        }
        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }
        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
