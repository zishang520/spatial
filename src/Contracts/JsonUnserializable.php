<?php

namespace luoyy\Spatial\Contracts;

use luoyy\Spatial\Exception\UnserializationException;

/**
 * JSON 反序列化接口。
 *
 * 用于通过已解码的 JSON 数据工厂化创建对象，常用于 GeoJson、BoundingBox、CRS 等类。
 *
 * @since 1.0
 */
interface JsonUnserializable
{
    /**
     * 通过已解码的 JSON 值工厂化创建对象。
     *
     * @param mixed $json 解码后的 JSON 数据
     * @throws UnserializationException 反序列化失败时抛出
     */
    public static function jsonUnserialize(mixed $json): mixed;
}
