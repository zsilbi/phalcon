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

namespace Phalcon\Tag;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Escaper\EscaperInterface;

//use Phalcon\Http\ResponseInterface;
//use Phalcon\Html\Link\Link;
//use Phalcon\Html\Link\Serializer\Header;
use Phalcon\Support\Str\Friendly;
use Phalcon\Support\Exception as SupportException;
use Phalcon\Url\UrlInterface;

use function is_array;
use function is_object;
use function is_resource;
use function is_string;
use function strpos;

use const PHP_EOL;

/**
 * Phalcon\Tag is designed to simplify building of HTML tags.
 * It provides a set of helpers to generate HTML in a dynamic way.
 * This component is a class that you can extend to add more helpers.
 */
class Tag
{
    const HTML32               = 1;
    const HTML401_STRICT       = 2;
    const HTML401_TRANSITIONAL = 3;
    const HTML401_FRAMESET     = 4;
    const HTML5                = 5;
    const XHTML10_STRICT       = 6;
    const XHTML10_TRANSITIONAL = 7;
    const XHTML10_FRAMESET     = 8;
    const XHTML11              = 9;
    const XHTML20              = 10;
    const XHTML5               = 11;

    /**
     * @var bool
     */
    protected static bool $autoEscape = true;

    /**
     * DI Container
     *
     * @var DiInterface|null
     */
    protected static ?DiInterface $container;

    /**
     * Pre-assigned values for components
     *
     * @var array|null
     */
    protected static ?array $displayValues;

    /**
     * @var array
     */
    protected static array $documentAppendTitle = [];

    /**
     * @var array
     */
    protected static array $documentPrependTitle = [];

    /**
     * HTML document title
     *
     * @var string|null
     */
    protected static ?string $documentTitle;

    /**
     * @var string|null
     */
    protected static ?string $documentTitleSeparator;

    /**
     * @var int
     */
    protected static int $documentType = self::XHTML5;

    /**
     * @var EscaperInterface|null
     */
    protected static $escaperService;

    /**
     * @var UrlInterface|null
     */
    protected static $urlService;

    /**
     * Appends a text to current document title
     *
     * @param array|string $title
     */
    public static function appendTitle($title): void
    {
        if (false !== is_array($title)) {
            self::$documentAppendTitle = $title;
        } else {
            self::$documentAppendTitle[] = $title;
        }
    }

    /**
     * Builds a HTML input[type="check"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function checkField($parameters): string
    {
        return self::inputFieldChecked('checkbox', $parameters);
    }

    /**
     * Builds a HTML input[type="color"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function colorField($parameters): string
    {
        return self::inputField('color', $parameters);
    }

    /**
     * Builds a HTML input[type="date"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function dateField($parameters): string
    {
        return self::inputField('date', $parameters);
    }

    /**
     * Builds a HTML input[type="datetime"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function dateTimeField($parameters): string
    {
        return self::inputField('datetime', $parameters);
    }

    /**
     * Builds a HTML input[type="datetime-local"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function dateTimeLocalField($parameters): string
    {
        return self::inputField('datetime-local', $parameters);
    }

    /**
     * Alias of Phalcon\Tag::setDefault()
     */
    public static function displayTo(string $id, $value): void
    {
        self::setDefault($id, $value);
    }

    /**
     * Builds a HTML input[type="email"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function emailField($parameters): string
    {
        return self::inputField('email', $parameters);
    }

    /**
     * Builds a HTML close FORM tag
     *
     * @return string
     */
    public static function endForm(): string
    {
        return "</form>";
    }

    /**
     * Builds a HTML input[type="file"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'id' => '',
     *                          'name' => ''
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function fileField($parameters): string
    {
        return self::inputField('file', $parameters);
    }

    /**
     * Builds a HTML FORM tag
     *
     * @param array $parameters = [
     *                          'method' => 'post',
     *                          'action' => '',
     *                          'parameters' => '',
     *                          'name' => '',
     *                          'class' => '',
     *                          'id' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function form($parameters): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        $paramsAction = $params[0] ?? $params['action'];

        /**
         * By default the method is POST
         */
        if (true !== isset($params['method'])) {
            $params['method'] = 'post';
        }

        $action = null;

        if (true !== empty($paramsAction)) {
            $action = self::getUrlService()->get($paramsAction);
        }

        /**
         * Check for extra parameters
         */
        if (isset($params['parameters']) && $params['parameters']) {
            $action .= "?" . $params['parameters'];
        }

        if (true !== empty($action)) {
            $params["action"] = $action;
        }

        return self::renderAttributes("<form", $params) . ">";
    }

    /**
     * Converts texts into URL-friendly titles
     *
     * @param string $text
     * @param string $separator
     * @param bool   $lowercase
     * @param null   $replace
     *
     * @return string
     * @throws Exception
     */
    public static function friendlyTitle(
        string $text,
        string $separator = "-",
        bool $lowercase = true,
        $replace = null
    ): string {
        try {
            return (new Friendly())($text, $separator, $lowercase, $replace);
        } catch (SupportException $exception) {
            throw new Exception(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Get the document type declaration of content
     *
     * @return string
     */
    public static function getDocType(): string
    {
        switch (self::$documentType) {
            case 1:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2 Final//EN\">" . PHP_EOL;

            case 2:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/html4/strict.dtd\">" . PHP_EOL;

            case 3:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/html4/loose.dtd\">" . PHP_EOL;

            case 4:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Frameset//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/html4/frameset.dtd\">" . PHP_EOL;

            case 6:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">" . PHP_EOL;

            case 7:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">" . PHP_EOL;

            case 8:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Frameset//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd\">" . PHP_EOL;

            case 9:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"" . PHP_EOL . "\t\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">" . PHP_EOL;

            case 10:
                return "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 2.0//EN\"" . PHP_EOL . "\t\"http://www.w3.org/MarkUp/DTD/xhtml2.dtd\">" . PHP_EOL;

            case 5:
            case 11:
                return "<!DOCTYPE html>" . PHP_EOL;
        }

        return '';
    }

    /**
     * Obtains the 'escaper' service if required
     *
     * @param array $params
     *
     * @return EscaperInterface|null
     */
    public static function getEscaper(array $params): ?EscaperInterface
    {
        $autoEscape = $params['escape'] ?? self::$autoEscape;

        if (true != $autoEscape) {
            return null;
        }

        return self::getEscaperService();
    }

    /**
     * Internally gets the request dispatcher
     *
     * @return DiInterface
     */
    public static function getDI(): DiInterface
    {
        $di = self::$container;

        if (true !== is_object($di)) {
            $di = Di::getDefault();
        }

        return $di;
    }

    /**
     * Returns an Escaper service from the default DI
     *
     * @return EscaperInterface
     * @throws Exception
     */
    public static function getEscaperService(): EscaperInterface
    {
        $escaper = self::$escaperService;

        if (true !== is_object($escaper)) {
            $container = self::getDI();

            if (true !== is_object($container)) {
                throw new Exception(
                    "A dependency injection container is required to access the 'escaper' service"
                );
            }

            /**
             * @var EscaperInterface $escaper
             */
            $escaper = $container->getShared('escaper');

            self::$escaperService = $escaper;
        }

        return $escaper;
    }

    /**
     * Gets the current document title. The title will be automatically escaped.
     *
     * @param bool $prepend
     * @param bool $append
     *
     * @return string
     */
    public static function getTitle(bool $prepend = true, bool $append = true): string
    {
        $escaper = self::getEscaper(["escape" => true]);

        $items  = [];
        $output = '';

        if (null !== $escaper) {
            $documentTitle = $escaper->escapeHtml(self::$documentTitle);

            $documentTitleSeparator = $escaper->escapeHtml(
                self::$documentTitleSeparator
            );
        } else {
            $documentTitle          = self::$documentTitle;
            $documentTitleSeparator = self::$documentTitleSeparator;
        }

        if (false !== $prepend) {
            $documentPrependTitle = self::$documentPrependTitle;

            if (true !== empty($documentPrependTitle)) {
                $tmp = array_reverse($documentPrependTitle);

                foreach ($tmp as $title) {
                    if (null !== $escaper) {
                        $items[] = $escaper->escapeHtml($title);
                    } else {
                        $items[] = $title;
                    }
                }
            }
        }

        if (true !== empty($documentTitle)) {
            $items[] = $documentTitle;
        }

        if (false !== $append) {
            $documentAppendTitle = self::$documentAppendTitle;

            if (true !== empty($documentAppendTitle)) {
                foreach ($documentAppendTitle as $title) {
                    if (null !== $escaper) {
                        $items[] = $escaper->escapeHtml($title);
                    } else {
                        $items[] = $title;
                    }
                }
            }
        }

        if (false !== empty($documentTitleSeparator)) {
            $documentTitleSeparator = "";
        }

        if (true !== empty($items)) {
            $output = implode($documentTitleSeparator, $items);
        }

        return $output;
    }

    /**
     * Gets the current document title separator
     *
     * @return string
     */
    public static function getTitleSeparator(): string
    {
        return self::$documentTitleSeparator;
    }

    /**
     * Returns a URL service from the default DI
     *
     * @return UrlInterface
     * @throws Exception
     */
    public static function getUrlService(): UrlInterface
    {
        $url = self::$urlService;

        if (true !== is_object($url)) {
            $container = self::getDI();

            if (true !== is_object($container)) {
                throw new Exception(
                    "A dependency injection container is required to access the 'url' service"
                );
            }

            /**
             * @var UrlInterface $url
             */
            $url = $container->getShared("url");

            self::$urlService = $url;
        }

        return $url;
    }

    /**
     * Every helper calls this function to check whether a component has a
     * predefined value using Phalcon\Tag::setDefault() or value from $_POST
     *
     * @param string $name
     * @param array  $params
     *
     * @return mixed
     */
    public static function getValue($name, array $params = [])
    {
        $value = $params['value'] ?? null;

        if (null !== $value) {
            return $value;
        }

        /**
         * Check if there is a predefined value for it
         */
        $value = self::$displayValues[$name] ?? null;

        if (null !== $value) {
            return $value;
        }

        /**
         * Check if there is a post value for the item
         */
        $value = $_POST[$name] ?? null;

        return $value;
    }

    /**
     * Check if a helper has a default value set using Phalcon\Tag::setDefault()
     * or value from $_POST
     *
     * @return bool
     */
    public static function hasValue($name): bool
    {
        /**
         * Check if there is a predefined or a POST value for it
         */
        return isset(self::$displayValues[$name]) || isset($_POST[$name]);
    }

    /**
     * Builds a HTML input[type="hidden"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function hiddenField($parameters): string
    {
        return self::inputField('hidden', $parameters);
    }

    /**
     * Builds HTML IMG tags
     *
     * @param array|string $parameters = [
     *                                 'src' => '',
     *                                 'class' => '',
     *                                 'id' => '',
     *                                 'name' => ''
     *                                 ]
     * @param bool         $local
     *
     * @return string
     * @throws Exception
     */
    public static function image($parameters = null, bool $local = true): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;

            if (isset($params[1])) {
                $local = (bool)$params[1];
            }
        }

        if (!isset($params["src"])) {
            $params['src'] = $params[0] ?? '';
        }

        /**
         * Use the "url" service if the URI is local
         */
        if (false !== $local) {
            $params['src'] = self::getUrlService()->getStatic($params['src']);
        }

        $code = self::renderAttributes("<img", $params);

        /**
         * Check if Doctype is XHTML
         */
        if (self::$documentType > self::HTML5) {
            $code .= " />";
        } else {
            $code .= ">";
        }

        return $code;
    }

    /**
     * Builds a HTML input[type="image"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function imageInput($parameters): string
    {
        return self::inputField('image', $parameters, true);
    }

    /**
     * Builds a SCRIPT[type="javascript"] tag
     *
     * @param array|string $parameters = [
     *                                 'local' => false,
     *                                 'src' => '',
     *                                 'type' => 'text/javascript'
     *                                 'rel' => ''
     *                                 ]
     * @param bool         $local
     *
     * @return string
     * @throws Exception
     */
    public static function javascriptInclude($parameters = null, bool $local = true): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        if (false !== isset($params[1])) {
            $local = (bool)$params[1];
        } else {
            if (false !== isset($params['local'])) {
                $local = (bool)$params['local'];

                unset($params['local']);
            }
        }

        if (!isset($params['type']) && self::$documentType < self::HTML5) {
            $params['type'] = 'text/javascript';
        }

        if (true !== (isset($params['src']))) {
            $params['src'] = $params[0] ?? '';
        }

        /**
         * URLs are generated through the 'url' service
         */
        if ($local) {
            $params['src'] = self::getUrlService()->getStatic($params['src']);
        }

        return self::renderAttributes('<script', $params) . '></script>' . PHP_EOL;
    }

    /**
     * Builds a HTML A tag using framework conventions
     *
     * @param             $parameters array|string = [
     *                                'action' => '',
     *                                'text' => '',
     *                                'local' => false,
     *                                'query' => '',
     *                                'class' => '',
     *                                'name' => '',
     *                                'href' => '',
     *                                'id' => ''
     *                                ]
     * @param string|null $text
     * @param bool        $local
     *
     * @return string
     * @throws Exception
     */
    public static function linkTo($parameters, $text = null, $local = true): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        $action = $params[0] ?? $params['action'] ?? '';
        $text   = $params[1] ?? $params['text'] ?? $text;
        $local  = $params[2] ?? $params['local'] ?? $local;
        $query  = $params['query'] ?? null;

        unset($params['action']);
        unset($params['text']);
        unset($params['local']);
        unset($params['query']);

        $params['href'] = self::getUrlService()->get($action, $query, $local);

        return self::renderAttributes('<a', $params) . '>' . $text . '</a>';
    }

    /**
     * Builds a HTML input[type="month"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function monthField($parameters): string
    {
        return self::inputField('month', $parameters);
    }

    /**
     * Builds a HTML input[type="number"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function numericField($parameters): string
    {
        return self::inputField('number', $parameters);
    }


    /**
     * Builds a HTML input[type="password"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function passwordField($parameters): string
    {
        return self::inputField('password', $parameters);
    }

    /**
     * Prepends a text to current document title
     *
     * @param string|array $title
     */
    public static function prependTitle($title): void
    {
        if (false !== is_array($title)) {
            self::$documentPrependTitle = $title;
        } else {
            self::$documentPrependTitle[] = $title;
        }
    }

//    /**
//     * Parses the preload element passed and sets the necessary link headers
//     */
//    public static function preload($parameters): string
//    {
//        if (true !== is_array($parameters)) {
//            $params = [$parameters];
//        } else {
//            $params = $parameters;
//        }
//
//        /**
//         * Grab the element
//         */
//        $href = $params[0] ?? '';
//
//        $container = self::getDI();
//
//        /**
//         * Check if we have the response object in the container
//         */
//        if ($container && $container->has('response')) {
//            if (isset($params[1])) {
//                $attributes = $params[1];
//            } else {
//                $attributes = ['as' => 'style'];
//            }
//
//            /**
//             * href comes wrapped with ''. Remove them
//             *
//             * @var ResponseInterface $response
//             */
//            $response = $container->get('response');
//
//            $link = new Link(
//                'preload',
//                str_replace("'", '', $href),
//                $attributes
//            );
//
//            $header = 'Link: ' . (new Header())->serialize([$link]);
//
//            $response->setRawHeader($header);
//        }
//
//        return $href;
//    }

    /**
     * Builds a HTML input[type="radio"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function radioField($parameters): string
    {
        return self::inputFieldChecked('radio', $parameters);
    }

    /**
     * Builds a HTML input[type="range"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function rangeField($parameters): string
    {
        return self::inputField('range', $parameters);
    }

    /**
     * Renders parameters keeping order in their HTML attributes
     *
     * @param string $code
     * @param array  $attributes = [
     *                           'rel' => null,
     *                           'type' => null,
     *                           'for' => null,
     *                           'src' => null,
     *                           'href' => null,
     *                           'action' => null,
     *                           'id' => null,
     *                           'name' => null,
     *                           'value' => null,
     *                           'class' => null
     *                           ]
     *
     * @return string
     * @throws Exception
     */
    public static function renderAttributes(string $code, array $attributes): string
    {
        $order = [
            'rel',
            'type',
            'for',
            'src',
            'href',
            'action',
            'id',
            'name',
            'value',
            'class'
        ];

        $attrs = [];

        foreach ($order as $key) {
            if (isset($attributes[$key])) {
                $attr[$key] = $attributes[$key];
            }
        }

        foreach ($attributes as $key => $value) {
            if (true !== isset($attrs[$key])) {
                $attrs[$key] = $value;
            }
        }

        $escaper = self::getEscaper($attributes);

        unset($attrs['escape']);

        $newCode = $code;

        foreach ($attrs as $key => $value) {
            if (false !== is_string($key) && null !== $value) {
                if (false !== is_array($value) || false !== is_resource($value)) {
                    throw new Exception(
                        "Value at index: '" . $key . "' type: '" . gettype($value) . "' cannot be rendered"
                    );
                }

                if ($escaper) {
                    $escaped = $escaper->escapeHtmlAttr($value);
                } else {
                    $escaped = $value;
                }

                $newCode .= ' ' . $key . '="' . $escaped . '"';
            }
        }

        return $newCode;
    }

    /**
     * Renders the title with title tags. The title is automatically escaped
     *
     * @param bool $prepend
     * @param bool $append
     *
     * @return string
     */
    public static function renderTitle(bool $prepend = true, bool $append = true): string
    {
        return '<title>' . self::getTitle($prepend, $append) . '</title>' . PHP_EOL;
    }

    /**
     * Resets the request and internal values to avoid those fields will have
     * any default value.
     *
     * @deprecated Will be removed in 4.0.0
     */
    public static function resetInput(): void
    {
        self::$displayValues          = [];
        self::$documentTitle          = null;
        self::$documentAppendTitle    = [];
        self::$documentPrependTitle   = [];
        self::$documentTitleSeparator = null;
    }

    /**
     * Builds a HTML input[type="search"] tag
     *
     * @param array $parameters = [
     *                          'class' => '',
     *                          'name' => '',
     *                          'src' => '',
     *                          'id' => '',
     *                          'value' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function searchField($parameters): string
    {
        return self::inputField('search', $parameters);
    }

    /**
     * Builds a HTML SELECT tag using a Phalcon\Mvc\Model resultset as options
     *
     * @param array      $parameters = [
     *                               'id' => '',
     *                               'name' => '',
     *                               'value' => '',
     *                               'useEmpty' => false,
     *                               'emptyValue' => '',
     *                               'emptyText' => '',
     *                               ]
     * @param array|null $data
     *
     * @return string
     * @throws Exception
     */
    public static function select($parameters, $data = null): string
    {
        return Select::selectField($parameters, $data);
    }

    /**
     * Builds a HTML SELECT tag using a PHP array for options
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'useEmpty' => false,
     *                          'emptyValue' => '',
     *                          'emptyText' => '',
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function selectStatic($parameters, $data = null): string
    {
        return Select::selectField($parameters, $data);
    }

    /**
     * Set autoescape mode in generated HTML
     *
     * @param bool $autoescape
     */
    public static function setAutoescape(bool $autoescape): void
    {
        self::$autoEscape = $autoescape;
    }

    /**
     * Assigns default values to generated tags by helpers
     *
     * @param string     $id
     * @param mixed|null $value
     *
     * @throws Exception
     */
    public static function setDefault(string $id, $value): void
    {
        if (null !== $value) {
            if (is_array($value) || is_object($value)) {
                throw new Exception(
                    'Only scalar values can be assigned to UI components'
                );
            }
        }
        self::$displayValues[$id] = $value;
    }

    /**
     * Assigns default values to generated tags by helpers
     *
     * @param array $values
     * @param bool  $merge
     */
    public static function setDefaults(array $values, bool $merge = false): void
    {
        if ($merge && is_array(self::$displayValues)) {
            self::$displayValues = array_merge(self::$displayValues, $values);
        } else {
            self::$displayValues = $values;
        }
    }

    /**
     * Sets the dependency injector container.
     *
     * @param DiInterface $container
     */
    public static function setDI(DiInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Set the document type of content
     *
     * @param int $docType
     */
    public static function setDocType(int $docType): void
    {
        if ($docType < self::HTML32 || $docType > self::XHTML5) {
            self::$documentType = self::HTML5;
        } else {
            self::$documentType = $docType;
        }
    }

    /**
     * Set the title of view content
     *
     * @param string $title
     */
    public static function setTitle(string $title): void
    {
        self::$documentTitle = $title;
    }

    /**
     * Set the title separator of view content
     *
     * @param string $titleSeparator
     */
    public static function setTitleSeparator(string $titleSeparator): void
    {
        self::$documentTitleSeparator = $titleSeparator;
    }

    /**
     * Builds a LINK[rel="stylesheet"] tag
     *
     * @param array|null $attributes = [
     *                               'rel' => null,
     *                               'href' => null,
     *                               'type' => null,
     *                               'local' => null
     *                               ]
     * @param bool       $local
     *
     * @return string
     * @throws Exception
     */
    public static function stylesheetLink($parameters = null, bool $local = true): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        if (false !== isset($params[1])) {
            $local = (bool)$params[1];
        } else {
            if (false !== isset($params['local'])) {
                $local = (bool)$params['local'];

                unset($params['local']);
            }
        }

        if (true !== isset($params['type'])) {
            $params['type'] = "text/css";
        }

        if (true !== isset($params['href'])) {
            $params['href'] = $params[0] ?? '';
        }

        /**
         * URLs are generated through the "url" service
         */
        if ($local) {
            $params['href'] = self::getUrlService()->getStatic(
                $params['href']
            );
        }

        if (true !== isset($params['rel'])) {
            $params['rel'] = "stylesheet";
        }

        $code = self::renderAttributes('<link', $params);

        /**
         * Check if Doctype is XHTML
         */
        if (self::$documentType > self::HTML5) {
            $code .= ' />' . PHP_EOL;
        } else {
            $code .= '>' . PHP_EOL;
        }

        return $code;
    }

    /**
     * Builds a HTML input[type="submit"] tag
     *
     * @param $parameters
     *
     * @return string
     * @throws Exception
     */
    public static function submitButton($parameters): string
    {
        return self::inputField('submit', $parameters, true);
    }

    /**
     * Builds a HTML tag
     *
     * @param string $tagName
     * @param null   $parameters
     * @param bool   $selfClose
     * @param bool   $onlyStart
     * @param bool   $useEol
     *
     * @return string
     * @throws Exception
     */
    public static function tagHtml(
        string $tagName,
        $parameters = null,
        bool $selfClose = false,
        bool $onlyStart = false,
        bool $useEol = false
    ): string {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        $localCode = self::renderAttributes('<' . $tagName, $params);

        /**
         * Check if Doctype is XHTML
         */
        if (self::$documentType > self::HTML5) {
            if (false !== $selfClose) {
                $localCode .= " />";
            } else {
                $localCode .= ">";
            }
        } else {
            if (false !== $onlyStart) {
                $localCode .= ">";
            } else {
                $localCode .= "></" . $tagName . ">";
            }
        }

        if ($useEol) {
            $localCode .= PHP_EOL;
        }

        return $localCode;
    }

    /**
     * Builds a HTML tag closing tag
     *
     * @param string $tagName
     * @param bool   $useEol
     *
     * @return string
     */
    public static function tagHtmlClose(string $tagName, bool $useEol = false): string
    {
        if ($useEol) {
            return '</' . $tagName . '>' . PHP_EOL;
        }

        return '</' . $tagName . '>';
    }

    /**
     * Builds a HTML input[type="tel"] tag
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'class' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function telField($parameters): string
    {
        return self::inputField('tel', $parameters);
    }

    /**
     * Builds a HTML TEXTAREA tag
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'class' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function textArea($parameters): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        if (true !== isset($params[0])) {
            if (false !== isset($param['id'])) {
                $params[0] = $params['id'];
            }
        }

        $id = $params[0];

        if (true !== isset($params['name'])) {
            $params['name'] = $id;
        } else {
            $name = $params['name'];

            if (false !== empty($name)) {
                $params['name'] = $id;
            }
        }

        if (true !== isset($params['id'])) {
            $params['id'] = $id;
        }

        if (false !== isset($params['value'])) {
            $content = $params['value'];

            unset($params['value']);
        } else {
            $content = self::getValue($id, $params);
        }

        $code = self::renderAttributes('<textarea', $params);
        $code .= '>' . htmlspecialchars($content) . '</textarea>';

        return $code;
    }

    /**
     * Builds a HTML input[type="text"] tag
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'class' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function textField($parameters): string
    {
        return self::inputField('text', $parameters);
    }

    /**
     * Builds a HTML input[type="time"] tag
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'class' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function timeField($parameters): string
    {
        return self::inputField('time', $parameters);
    }

    /**
     * Builds a HTML input[type="url"] tag
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'class' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function urlField($parameters): string
    {
        return self::inputField('url', $parameters);
    }

    /**
     * Builds a HTML input[type="week"] tag
     *
     * @param array $parameters = [
     *                          'id' => '',
     *                          'name' => '',
     *                          'value' => '',
     *                          'class' => ''
     *                          ]
     *
     * @return string
     * @throws Exception
     */
    public static function weekField($parameters): string
    {
        return self::inputField('week', $parameters);
    }

    /**
     * Builds generic INPUT tags
     *
     * @param string $type
     * @param array paramters = [
     *     'id' => '',
     *     'name' => '',
     *     'value' => '',
     *     'class' => '',
     *     'type' => ''
     *     ]
     * @param bool   $asValue
     *
     * @return string
     * @throws Exception
     */
    static final protected function inputField(string $type, $parameters, bool $asValue = false): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        if (true !== $asValue) {
            $id   = $params[0] ?? $params['id'];
            $name = $params['name'] ?? null;

            if (false !== empty($name)) {
                $params['name'] = $id;
            }

            /**
             * Automatically assign the id if the name is not an array
             */
            if (false !== is_string($id)) {
                if (true !== strpos($id, '[') && !isset($params['id'])) {
                    $params['id'] = $id;
                }
            }

            $params['value'] = self::getValue($id, $params);
        } else {
            /**
             * Use the "id" as value if the user hadn't set it
             */
            if (true !== isset ($params['value'])) {
                $params['value'] = $params[0] ?? null;
            }
        }

        $params['type'] = $type;

        $code = self::renderAttributes('<input', $params);

        /**
         * Check if Doctype is XHTML
         */
        if (self::$documentType > self::HTML5) {
            $code .= " />";
        } else {
            $code .= ">";
        }

        return $code;
    }

    /**
     * Builds INPUT tags that implements the checked attribute
     */
    static final protected function inputFieldChecked(string $type, $parameters): string
    {
        if (true !== is_array($parameters)) {
            $params = [$parameters];
        } else {
            $params = $parameters;
        }

        $id   = $params[0] ?? $params['id'];
        $name = $params['name'] ?? null;

        if (false !== empty($name)) {
            $params['name'] = $id;
        }

        if (true !== strpos($id, '[') && !isset($params['id'])) {
            $params['id'] = $id;
        }

        /**
         * Automatically check inputs
         */
        $currentValue = $params['value'] ?? null;
        $value        = self::getValue($id, $params);

        if (null !== $currentValue) {
            unset($params['value']);

            if (null != $value && $currentValue == $value) {
                $params['checked'] = 'checked';
            }

            $params['value'] = $currentValue;
        } else {
            /**
             * Evaluate the value in POST
             */
            if (null !== $value) {
                $params['checked'] = 'checked';
            }

            /**
             * Update the value anyways
             */
            $params['value'] = $value;
        }

        $params['type'] = $type;
        $code           = self::renderAttributes('<input', $params);

        /**
         * Check if Doctype is XHTML
         */
        if (self::$documentType > self::HTML5) {
            $code .= " />";
        } else {
            $code .= ">";
        }

        return $code;
    }
}
