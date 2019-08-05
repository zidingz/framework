<?php

namespace SPF\Validator;

use SPF\Exception\LogicException;
use SPF\Exception\ValidateException;

class Validator
{
    /**
     * User defined validate rules.
     * 
     * @var array
     */
    protected static $rules = [];

    /**
     * User defined validate failed error messages.
     * 
     * @var array
     */
    protected static $messages = [
        '*' => 'The %s argument must be %s',
    ];

    /**
     * Validate class with method, params map.
     * 
     * @var array
     */
    protected static $validateMap = [];

    /**
     * Add new rule, event replace the framework rules.
     * 
     * @param string $rule
     * @param callable $func function($attribute, $value, $params = [], $args = [])
     */
    public static function addRule(string $rule, callable $func)
    {
        $rule = static::replaceRuleName($rule);
        static::$rules[$rule] = $func;
    }

    /**
     * Add new rule, event replace the framework validate fail error messages.
     * 
     * @param string $rule
     * @param callable $func function($field, $value, $params = [], $args = [])
     */
    public static function addMessage(string $rule, callable $func)
    {
        $rule = static::replaceRuleName($rule);
        static::$messages[$rule] = $func;
    }

    /**
     * Set the validate class with method, params.
     * 
     * @param array $map
     */
    public static function setValidateMap(array $map)
    {
        static::$validateMap = $map;
    }

    /**
     * Get the validate class with method, params map.
     */
    public static function getValidateMap()
    {
        return static::$validateMap;
    }

    /**
     * Validate arguments.
     * 
     * @param array $args
     * @param array $rules
     */
    public static function validate($args, $argRules)
    {
        $errors = [];
        foreach($args as $idx => $value) {
            if (!isset($argRules[$idx])) {
                continue;
            }
            $field = $argRules[$idx]['field'];
            $rules = $argRules[$idx]['rules'];
            foreach($rules as $rule => $params) {
                if (isset(static::$rules[$rule])) {
                    // Use the user defined rules
                    if (call_user_func(static::$rules[$rule], $rules, $value, $params, $args) === false) {
                        $errors[$field][$rule] = static::formatFailMessage($rule, $field, $value, $params, $args);
                    }
                } elseif (method_exists(ValidateRules::class, 'validate'.ucfirst($rule))) {
                    // Use the framework provided rules
                    $callable = ValidateRules::class.'::validate'.ucfirst($rule);
                    if (call_user_func($callable, $rules, $value, $params, $args) === false) {
                        $errors[$field][$rule] = static::formatFailMessage($rule, $field, $value, $params, $args);
                    }
                } else {
                    throw new LogicException("Validate rule [{$rule}] not found");
                }
            }
        }

        if (count($errors) > 0) {
            throw new ValidateException($errors);
        }
    }

    /**
     * Replace validate rule from snake_case to camelCase.
     * 
     * @param string $rule
     * 
     * @return string
     */
    protected static function replaceRuleName($rule)
    {
        return preg_replace_callback('/_([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $rule);
    }

    /**
     * Format validate fail error message.
     * 
     * @param string $rule
     * @param string $field
     * @param mixed value
     * @param array $params
     * @param array $args
     * 
     * @return string
     */
    protected static function formatFailMessage($rule, $field, $value, $params = [], $args = []) {
        if (isset(static::$messages[$rule])) {
            return call_user_func(static::$messages[$rule], $field, $value, $params, $args);
        } elseif (method_exists(ValidateRules::class, 'message' . ucfirst($rule))) {
            $callable = ValidateRules::class . '::message' . ucfirst($rule);
            return call_user_func($callable, $field, $value, $params, $args);
        } else {
            return sprintf(static::$messages['*'], $field, $rule);
        }
    }
}
