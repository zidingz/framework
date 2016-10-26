<?php
namespace Swoole\Memory;

use Swoole\Exception\InvalidParam;

/**
 * C语言struct操作封装类
 * 目前支持的类型：
 * int, int8, int16, int32, int64, uint, uint8, uint16, uint32, uint64, long, ulong
 * float, double
 * char[n], uchar[n] 注意C语言存在内存对齐问题
 * @package Swoole\Memory
 */
abstract class Struct
{
    protected $size = 0;
    protected $fileds = array();
    protected $is32bit;

    /**
     * 主机字节序或者网络字节序
     */
    protected $convertBigEndian;

    const REGX = '#@\w+\s+([a-z0-9\[\]]+)\s+#i';

    /**
     * @param bool $convertBigEndian
     * @throws InvalidParam
     */
    function __construct($convertBigEndian = true)
    {
        $this->is32bit = (PHP_INT_SIZE === 4);
        $this->convertBigEndian = $convertBigEndian;
        $rClass = new \ReflectionClass(get_class($this));
        $props = $rClass->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $p)
        {
            if (preg_match(self::REGX, $p->getDocComment(), $match))
            {
                $field = $this->parseFieldType($match[1]);
                $this->fileds[] =$field;
                $this->size += $field->size;
            }
        }
    }

    /**
     * @return int
     */
    function size()
    {
        return $this->size;
    }

    /**
     * @param $fieldType
     * @return Field
     * @throws InvalidParam
     */
    protected function parseFieldType($fieldType)
    {
        $signed = false;
        start_switch:
        switch ($fieldType[0])
        {
            case 'u':
                $signed = true;
                $fieldType = substr($fieldType, 1);
                goto start_switch;

            case 'i':
                if ($fieldType == 'int')
                {
                    $size = 4;
                }
                else
                {
                    $size = substr($fieldType, 3) / 8;
                }
                $type = Field::INT;
                break;

            case 'l':
                $size = $this->is32bit ? 4 : 8;
                $type = Field::INT;
                break;

            case 'f':
                $size = 4;
                $type = Field::FLOAT;
                break;

            case 'd':
                $size = $this->is32bit ? 4 : 8;
                $type = Field::FLOAT;
                break;

            case 'c':
                $size = intval(substr($fieldType, 5));
                $type = Field::CHAR;
                break;
            default:
                throw new InvalidParam("invalid field type [{$fieldType[0]}].");
        }

        return new Field($type, $size, $signed);
    }

    /**
     * 打包数据
     * @param array $data
     * @return string
     * @throws InvalidParam
     */
    function pack(array $data)
    {
        if (count($data) != count($this->fileds))
        {
            throw new InvalidParam("invalid data.");
        }

        $_binStr = '';
        foreach ($this->fileds as $k => $field)
        {
            /**
             * @var $field Field
             */
            switch ($field->type)
            {
                case Field::INT:
                    switch ($field->size)
                    {
                        case 1:
                            $_binStr .= pack($field->signed ? 'c' : 'C', $data[$k]);
                            break;
                        case 2:
                            if ($this->convertBigEndian)
                            {
                                //网络字节序只有无符号的编码方式
                                $_binStr .= pack('n', $data[$k]);
                            }
                            else
                            {
                                $_binStr .= pack($field->signed ? 's' : 'S', $data[$k]);
                            }
                            break;
                        case 4:
                            if ($this->convertBigEndian)
                            {
                                //网络字节序只有无符号的编码方式
                                $_binStr .= pack('N', $data[$k]);
                            }
                            else
                            {
                                $_binStr .= pack($field->signed ? 'l' : 'L', $data[$k]);
                            }
                            break;
                        case 8:
                            if ($this->convertBigEndian)
                            {
                                //网络字节序只有无符号的编码方式
                                $_binStr .= pack('J', $data[$k]);
                            }
                            else
                            {
                                $_binStr .= pack($field->signed ? 'q' : 'Q', $data[$k]);
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                case Field::FLOAT:
                    $_binStr .= pack($field->size == 4 ? 'f' : 'd', $data[$k]);
                    break;
                case Field::CHAR:
                    //C字符串末尾必须为\0，最大只能保存(size-1)个字节
                    if (strlen($data[$k]) > $field->size - 1)
                    {
                        throw new InvalidParam("string is too long.");
                    }
                    $_binStr .=  pack('a' . ($field->size - 1) . 'x', $data[$k]);;
                    break;
                default:
                    break;
            }
        }

        return $_binStr;
    }

    /**
     * 解包数据
     * @param $str
     * @return array
     */
    function unpack($str)
    {
        $data = array();
        foreach ($this->fileds as $k => $field)
        {
            /**
             * @var $field Field
             */
            switch ($field->type)
            {
                case Field::INT:
                    switch ($field->size)
                    {
                        case 1:
                            list(, $data[$k]) = unpack($field->signed ? 'c' : 'C', $str);
                            break;
                        case 2:
                            if ($this->convertBigEndian)
                            {
                                //网络字节序只有无符号的编码方式
                                list(, $data[$k]) = unpack('n', $str);
                            }
                            else
                            {
                                list(, $data[$k]) = unpack($field->signed ? 's' : 'S', $str);
                            }
                            break;
                        case 4:
                            if ($this->convertBigEndian)
                            {
                                //网络字节序只有无符号的编码方式
                                list(, $data[$k]) = unpack('N', $str);
                            }
                            else
                            {
                                list(, $data[$k]) = unpack($field->signed ? 'l' : 'L', $str);
                            }
                            break;
                        case 8:
                            if ($this->convertBigEndian)
                            {
                                //网络字节序只有无符号的编码方式
                                list(, $data[$k]) = unpack('J', $str);
                            }
                            else
                            {
                                list(, $data[$k]) = unpack($field->signed ? 'q' : 'Q', $str);
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                case Field::FLOAT:
                    list(, $data[$k]) = unpack($field->size == 4 ? 'f' : 'd', $str);
                    break;
                case Field::CHAR:
                    list(, $tmp) = unpack('a' . $field->size, $str);
                    $data[$k] = rtrim($tmp, "\0");
                    break;
                default:
                    break;
            }
            $str = substr($str, $field->size);
        }
        return $data;
    }
}

class Field
{
    public $type;
    public $size;
    public $signed;

    const INT = 1;
    const FLOAT = 2;
    const CHAR = 3;

    function __construct($type, $size, $signed)
    {
        $this->type = $type;
        $this->size = $size;
        $this->signed = $signed;
    }
}