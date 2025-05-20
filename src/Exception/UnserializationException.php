<?php

namespace luoyy\Spatial\Exception;

/**
 * 反序列化异常。
 *
 * 用于处理 GeoJSON、BoundingBox、CRS 等对象反序列化时的类型、属性、缺失或类型不支持等错误。
 */
class UnserializationException extends \RuntimeException
{
    /**
     * 创建类型无效的反序列化异常。
     *
     * @param string $context 上下文描述
     * @param mixed $value 实际值
     * @param string $expectedType 期望类型
     */
    public static function invalidValue(string $context, $value, string $expectedType): UnserializationException
    {
        return new self(\sprintf(
            '%s expected value of type %s, %s given',
            $context,
            $expectedType,
            \get_debug_type($value)
        ));
    }

    /**
     * 创建属性类型无效的反序列化异常。
     *
     * @param string $context 上下文描述
     * @param string $property 属性名
     * @param mixed $value 实际值
     * @param string $expectedType 期望类型
     */
    public static function invalidProperty(string $context, string $property, $value, string $expectedType): UnserializationException
    {
        return new self(\sprintf(
            '%s expected "%s" property of type %s, %s given',
            $context,
            $property,
            $expectedType,
            \is_object($value) ? \get_class($value) : \gettype($value)
        ));
    }

    /**
     * 创建缺失属性的反序列化异常。
     *
     * @param string $context 上下文描述
     * @param string $property 属性名
     * @param string $expectedType 期望类型
     */
    public static function missingProperty(string $context, string $property, string $expectedType): UnserializationException
    {
        return new self(\sprintf(
            '%s expected "%s" property of type %s, none given',
            $context,
            $property,
            $expectedType
        ));
    }

    /**
     * 创建类型不支持的反序列化异常。
     *
     * @param string $context 上下文描述
     * @param string $value 类型值
     */
    public static function unsupportedType(string $context, string $value): UnserializationException
    {
        return new self(\sprintf('Invalid %s type "%s"', $context, $value));
    }
}
