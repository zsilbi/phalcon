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

namespace Phalcon\Url;

/**
 * Interface for Phalcon\Url\UrlInterface
 */
interface UrlInterface
{
    /**
     * Generates a URL
     *
     * @param string|array|null $uri
     * @param array|object|null $args Optional arguments to be appended to the query string
     * @param bool|null         $local
     *
     * @return string
     */
    public function get($uri = null, $args = null, bool $local = null): string;


    /**
     * Generates a URL for a static resource
     *
     *```php
     * // Generate a URL for a static resource
     * echo $url->getStatic("img/logo.png");
     *
     * // Generate a URL for a static predefined route
     * echo $url->getStatic(
     *     [
     *         "for" => "logo-cdn",
     *     ]
     * );
     *```
     *
     * @param array|string|null $uri = [
     *     'for' => ''
     * ]
     */
    public function getStatic($uri = null): string;

    /**
     * Returns a base path
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Returns the prefix for all the generated urls. By default /
     *
     * @return string
     */
    public function getBaseUri(): string;

    /**
     * Sets a base paths for all the generated paths
     *
     * @param string $basePath
     *
     * @return UrlInterface
     */
    public function setBasePath(string $basePath): UrlInterface;

    /**
     * Sets a prefix to all the urls generated
     *
     * @param string $baseUri
     *
     * @return UrlInterface
     */
    public function setBaseUri(string $baseUri): UrlInterface;

    /**
     * Generates a local path
     *
     * @param string|null $path
     *
     * @return string
     */
    public function path(string $path = null): string;
}
