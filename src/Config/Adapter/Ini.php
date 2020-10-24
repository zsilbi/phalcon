<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Config\Adapter;

use Phalcon\Config\Config;
use Phalcon\Config\Exception;

use function basename;
use function call_user_func_array;
use function count;
use function is_array;
use function is_numeric;
use function is_string;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function parse_ini_file;
use function preg_match;

/**
 * Reads ini files and converts them to Phalcon\Config objects.
 *
 * Given the next configuration file:
 *
 *```ini
 * [database]
 * adapter = Mysql
 * host = localhost
 * username = scott
 * password = cheetah
 * dbname = test_db
 *
 * [phalcon]
 * controllersDir = "../app/controllers/"
 * modelsDir = "../app/models/"
 * viewsDir = "../app/views/"
 * ```
 *
 * You can read it as follows:
 *
 *```php
 * use Phalcon\Config\Adapter\Ini;
 *
 * $config = new Ini("path/config.ini");
 *
 * echo $config->phalcon->controllersDir;
 * echo $config->database->username;
 *```
 *
 * PHP constants may also be parsed in the ini file, so if you define a constant
 * as an ini value before calling the constructor, the constant's value will be
 * integrated into the results. To use it this way you must specify the optional
 * second parameter as `INI_SCANNER_NORMAL` when calling the constructor:
 *
 * ```php
 * $config = new \Phalcon\Config\Adapter\Ini(
 *     "path/config-with-constants.ini",
 *     INI_SCANNER_NORMAL
 * );
 * ```
 */
class Ini extends Config
{
    /**
     * Ini constructor.
     *
     * @param string   $filePath
     * @param int|null $mode
     *
     * @throws Exception
     */
    public function __construct(string $filePath, $mode = null)
    {
        // Default to INI_SCANNER_RAW if not specified
        if (null === $mode) {
            $mode = INI_SCANNER_RAW;
        }

        $iniConfig = parse_ini_file($filePath, true, $mode);

        if (false === $iniConfig) {
            throw new Exception(
                'Configuration file ' . basename($filePath) . ' cannot be loaded'
            );
        }

        $config = [];

        foreach ($iniConfig as $section => $directives) {
            if (true === is_array($directives)) {
                $sections = [];

                foreach ($directives as $path => $lastValue) {
                    $sections[] = $this->parseIniString(
                        (string)$path,
                        $lastValue
                    );
                }

                if (count($sections)) {
                    $config[$section] = call_user_func_array(
                        'array_replace_recursive',
                        $sections
                    );
                }
            } else {
                $config[$section] = $this->cast($directives);
            }
        }

        parent::__construct($config);
    }

    /**
     * We have to cast values manually because parse_ini_file() has a poor
     * implementation.
     *
     * @param mixed
     *
     * @return mixed
     */
    protected function cast($ini)
    {
        if (true === is_array($ini)) {
            foreach ($ini as $key => $value) {
                $ini[$key] = $this->cast($value);
            }

            return $ini;
        }

        // Decode true
        $ini      = (string)$ini;
        $lowerIni = mb_strtolower($ini);

        switch ($lowerIni) {
            case 'true':
            case 'yes':
            case 'on':
                return true;
            case 'false':
            case 'no':
            case 'off':
                return false;
            case 'null':
                return null;
        }

        // Decode float/int
        if (is_string($ini) && is_numeric($ini)) {
            if (preg_match('/[.]+/', $ini)) {
                return (double)$ini;
            } else {
                return (int)$ini;
            }
        }

        return $ini;
    }

    /**
     * Build multidimensional array from string
     *
     * @param string $path
     * @param mixed  $value
     *
     * @return array
     */
    protected function parseIniString(string $path, $value): array
    {
        $value    = $this->cast($value);
        $position = mb_strpos($path, '.');

        if (false === $position) {
            return [
                $path => $value
            ];
        }

        $key  = mb_substr($path, 0, $position);
        $path = mb_substr($path, $position + 1);

        return [
            $key => $this->parseIniString($path, $value)
        ];
    }
}
