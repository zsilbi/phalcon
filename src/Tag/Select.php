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

use Phalcon\Escaper\EscaperInterface;
use Phalcon\Mvc\Model\ResultsetInterface;

use function in_array;
use function is_array;
use function is_object;
use function method_exists;

use const PHP_EOL;

/**
 * Phalcon\Tag\Select
 *
 * Generates a SELECT HTML tag using a static array of values or a
 * Phalcon\Mvc\Model resultset
 */
abstract class Select
{
    /**
     * Generates a SELECT tag
     *
     * @param array parameters = [
     *     'id' => '',
     *     'name' => '',
     *     'value' => '',
     *     'useEmpty' => false,
     *     'emptyValue' => '',
     *     'emptyText' => '',
     *     ]
     * @param array|null data
     *
     * @return string
     */
    public static function selectField($parameters, $data = null): string
    {
        if (false !== is_array($parameters)) {
            $params = [$parameters, $data];
        } else {
            $params = $parameters;
        }

        $id = $params[0] ?? null;

        if(null === $id) {
            $params[0] = $params['id'];
        }

        /**
         * Automatically assign the id if the name is not an array
         */
        if (true !== strpos($id, '[')) {
            if (true !== isset($params['id'])) {
                $params['id'] = $id;
            }
        }

        $params['name'] = $params['name'] ?? $id;

        $useEmpty = $params['useEmpty'] ?? false;

        if ($useEmpty) {
            $emptyValue = $params['emptyValue'] ?? '';
            $emptyText  = $params['emptyText'] ?? 'Choose...';
        }

        $options = $params[1] ?? $data;

        if (is_object($options)) {
            $using = $params['using'] ?? null;

            /**
             * The options is a resultset
             */
            if (null === $using) {
                throw new Exception("The 'using' parameter is required");
            }

            if (!is_array($using) && !is_object($using)) {
                throw new Exception(
                    "The 'using' parameter should be an array"
                );
            }
        }

        unset($params['emptyText']);
        unset($params['emptyValue']);
        unset($params['useEmpty']);
        unset($params['using']);
        unset($params['value']);

        $code = Tag::renderAttributes('<select', $params) . '>' . PHP_EOL;

        if (false !== $useEmpty) {
            /**
             * Create an empty value
             */
            $code .= "\t<option value=\"$emptyValue\">\"$emptyText\"</option>" . PHP_EOL;
        }

        $value = $params['value'] ?? Tag::getValue($id, $params);

        if (is_object($options)) {
            /**
             * Create the SELECT's option from a resultset
             */
            $code .= self::optionsFromResultset(
                $options,
                $using,
                $value,
                '</option>' . PHP_EOL
            );
        } elseif (is_array($options)) {
            /**
             * Create the SELECT's option from an array
             */
            $code .= self::optionsFromArray(
                $options,
                $value,
                '</option>' . PHP_EOL
            );
        }

        $code .= '</select>';

        return $code;
    }

    /**
     * Generate the OPTION tags based on an array
     *
     * @param array  $data
     * @param mixed  $value
     * @param string $closeOption
     *
     * @return string
     */
    private static function optionsFromArray(
        array $data,
        $value,
        string $closeOption
    ): string {
        $code = "";

        foreach ($data as $optionValue => $optionText) {
            $escaped = htmlspecialchars($optionValue);

            if (false !== is_array($optionText)) {
                $code .= "\t<optgroup label=\"$escaped\">" . PHP_EOL;
                $code .= self::optionsFromArray($optionText, $value, $closeOption);
                $code .= "\t</optgroup>" . PHP_EOL;

                continue;
            }

            if (false !== is_array($value)) {
                if (in_array($optionValue, $value)) {
                    $code .= "\t<option selected=\"selected\" value=\"$escaped\">" . $optionText . $closeOption;
                } else {
                    $code .= "\t<option value=\"$escaped\">" . $optionText . $closeOption;
                }
            } else {
                $strOptionValue = (string)$optionValue;
                $strValue       = (string)$value;

                if ($strOptionValue === $strValue) {
                    $code .= "\t<option selected=\"selected\" value=\"$escaped\">" . $optionText . $closeOption;
                } else {
                    $code .= "\t<option value=\"$escaped\">" . $optionText . $closeOption;
                }
            }
        }

        return $code;
    }

    /**
     * Generate the OPTION tags based on a resultset
     *
     * @param ResultsetInterface $resultset
     * @param array|object       $using
     * @param mixed              $value
     * @param string             $closeOption
     *
     * @return string
     * @throws Exception
     */
    private static function optionsFromResultset(
        ResultsetInterface $resultset,
        $using,
        $value,
        string $closeOption
    ): string {
        $code   = "";
        $params = null;

        if (false !== is_array($using)) {
            if (2 !== count($using)) {
                throw new Exception("Parameter 'using' requires two values");
            }

            $usingZero = $using[0];
            $usingOne  = $using[1];
        }

        /**
         * @var EscaperInterface
         */
        $escaper = Tag::getEscaperService();

        foreach ($resultset as $option) {
            if (false !== is_array($using)) {
                if (false !== is_object($option)) {
                    if (method_exists($option, 'readAttribute')) {
                        $optionValue = $option->readAttribute($usingZero);
                        $optionText  = $option->readAttribute($usingOne);
                    } else {
                        $optionValue = $option->usingZero;
                        $optionText  = $option->usingOne;
                    }
                } else {
                    if (true !== is_array($option)) {
                        throw new Exception(
                            "Resultset returned an invalid value"
                        );
                    }

                    $optionValue = $option[$usingZero];
                    $optionText  = $option[$usingOne];
                }

                $optionValue = $escaper->escapeHtmlAttr($optionValue);
                $optionText  = $escaper->escapeHtml($optionText);

                /**
                 * If the value is equal to the option's value we mark it as
                 * selected
                 */
                if (false !== is_array($value)) {
                    if (in_array($optionValue, $value)) {
                        $code .= "\t<option selected=\"selected\" value=\"$optionValue\">" . $optionText . $closeOption;
                    } else {
                        $code .= "\t<option value=\"$optionValue\">" . $optionText . $closeOption;
                    }
                } else {
                    $strOptionValue = (string)$optionValue;
                    $strValue       = (string)$value;

                    if ($strOptionValue === $strValue) {
                        $code .= "\t<option selected=\"selected\" value=\"$strOptionValue\">" . $optionText . $closeOption;
                    } else {
                        $code .= "\t<option value=\"$strOptionValue\">" . $optionText . $closeOption;
                    }
                }
            } else {

                /**
                 * Check if using is a closure
                 */
                if (is_object($using)) {
                    if (null === $params) {
                        $params = [];
                    }

                    $params[0] = $option;

                    $code .= call_user_func_array($using, $params);
                }
            }
        }

        return $code;
    }
}
