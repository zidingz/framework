<?php

namespace SPF\Tool;


use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use ReflectionProperty;

/**
 * 命名空间下类库反射表,一个项目只能初始化调用一次
 *
 * Class ReflectionClassMap
 * @package SPF\Tool
 */
class ReflectionClassMap
{
    public $namespace;
    public $project_src;
    static $isInitedMap = false;
    static $obj = null;
    public $map = [];

    function __construct($namespace, $project_src)
    {
        if (empty($namespace) or empty($project_src)) {
            throw new RuntimeException("namespace or src can not be empty", 1);
        }

        if (!is_dir($project_src)) {
            throw new RuntimeException("invalid path, $project_src not exists ,", 2);
        }
        $this->namespace = $namespace;
        $this->project_src = $project_src;
    }

    static function getInstance($namespace, $project_src)
    {
        if (!self::$obj) {
            self::$obj = new self($namespace, $project_src);
        }
        return self::$obj;
    }


    function getMap()
    {
        if (self::$isInitedMap) {
            return $this->map;
        }
        $root = $this->project_src;
        $dir_iterator = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator);

        $map = [];
        foreach ($iterator as $file) {
            $class_file = substr($file, strlen($root) + 1);
            $class_name = dirname($class_file) . '\\' . basename($class_file, '.php');
            $class_name = str_replace("/", "\\", $class_name);
            $ns_class_name = $this->namespace . "\\" . $class_name;
            $class = new ReflectionClass($ns_class_name);
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                $method_name = strtolower($method->getName());
                $docValidateRules = $this->parseMethodDocValidates($method->getDocComment());
                
                foreach($method->getParameters() as $idx => $param) {
                    $param_name = $param->getName();
                    $rules = $docValidateRules[$param_name] ?? [];
                    $type = (string) $param->getType();
                    $extends = [];
                    if (class_exists($type)) {
                        $this->parseClassPropertyByReflection($type, $extends);
                    }
                    // if the param is not optional, then adding required rule into rules
                    if ($param->isOptional() === false) {
                        $rules['required'] = [];
                    }
                    $map[$ns_class_name][$method_name][$idx] = [
                        'field' => $param_name,
                        'type' => $type,
                        'is_optional' => $param->isOptional(),
                        'rules' => $rules,
                        'extends' => $extends,
                    ];
                }
            }
        }
        self::$isInitedMap = true;
        $this->map = $map;
        return $map;
    }

    /**
     * 解析方法中文档参数注释
     * 必须包含 @param type? $fieldName {{rule1|rule2:param1|rule3:param2,param3}}
     * 
     * @param string $doc 通过反射获取的文档注释 ReflectionMethod->getDocComment
     * 
     * @return array
     */
    protected function parseMethodDocValidates($doc)
    {
        $rules = [];
        foreach(explode("\n", $doc) as $line) {
            if (preg_match('/@(param|var)(.*?)\$([^\s]+)(.*?)\{\{([^\}]+)\}\}/', $line, $matches) > 0) {
                $field = trim($matches[3]);
                $rule = [];
                foreach(explode('|', trim($matches[5])) as $rule_item) {
                    $rule_parts = explode(':', trim($rule_item), 2);
                    $params = isset($rule_parts[1]) ? explode(',', trim($rule_parts[1])) : [];
                    $rule[trim($rule_parts[0])] = $params;
                }
                $rules[$field] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Parse Class Property By Reflection.
     * 
     * @param string $class
     * @param array $extends
     */
    protected function parseClassPropertyByReflection($class, &$extends = [])
    {
        $refClass = new ReflectionClass($class);
        foreach($refClass->getProperties(ReflectionProperty::IS_PUBLIC) as $refProp) {
            $parsed = $this->parseClassPropertyDocValidates($refProp->getDocComment());
            if ($parsed === false) {
                continue;
            }

            $name = $refProp->getName();
            $extends[$name] = $parsed;
        }
    }

    /**
     * Parse Class Property Document validates.
     * 
     * @param string $doc
     * 
     * @return boolean|array
     */
    protected function parseClassPropertyDocValidates($doc)
    {
        foreach (explode("\n", $doc) as $line) {
            if (preg_match('/@(param|var)\s*([^\s]+)?((.*?)\{\{([^\}]+)\}\})?/', $line, $matches) > 0) {
                if (!isset($matches[2])) {
                    continue;
                }

                $type = trim($matches[2]);
                $rules = [];
                $extends = [];
                if (isset($matches[5])) {
                    foreach (explode('|', trim($matches[5])) as $rule_item) {
                        $rule_parts = explode(':', trim($rule_item), 2);
                        $params = isset($rule_parts[1]) ? explode(',', trim($rule_parts[1])) : [];
                        $rules[trim($rule_parts[0])] = $params;
                    }
                }
                if (class_exists($type)) {
                    $this->parseClassPropertyByReflection($type, $extends);
                }

                return compact('type', 'rules', 'extends');
            }
        }
        
        return false;
    }
}