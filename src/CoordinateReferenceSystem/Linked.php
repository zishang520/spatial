<?php

namespace luoyy\Spatial\CoordinateReferenceSystem;

use ArrayObject;
use luoyy\Spatial\Exception\UnserializationException;

use function is_array;
use function is_object;

/**
 * 链接型坐标参考系对象。
 *
 * @deprecated 1.1 规范已移除 CRS，GeoJSON 标准不再推荐使用 'crs' 字段。
 * @see https://www.rfc-editor.org/rfc/rfc7946#appendix-B.1
 * @see http://www.geojson.org/geojson-spec.html#linked-crs
 * @since 1.0
 */
class Linked extends CoordinateReferenceSystem
{
    /**
     * 构造函数。
     *
     * @param string $href 链接地址
     * @param string|null $type 类型
     */
    public function __construct(string $href, ?string $type = null)
    {
        $this->properties = ['href' => $href];

        if ($type !== null) {
            $this->properties['type'] = $type;
        }
    }

    /**
     * 获取类型。
     *
     */
    public function getType(): string
    {
        return 'link';
    }

    /**
     * 工厂方法：通过属性反序列化 Linked CRS 对象。
     *
     * @param array|object $properties
     * @throws UnserializationException
     */
    protected static function jsonUnserializeFromProperties($properties): self
    {
        if (! is_array($properties) && ! is_object($properties)) {
            throw UnserializationException::invalidProperty('Linked CRS', 'properties', $properties, 'array or object');
        }

        $properties = new ArrayObject($properties);

        if (! $properties->offsetExists('href')) {
            throw UnserializationException::missingProperty('Linked CRS', 'properties.href', 'string');
        }

        $href = (string) $properties['href'];
        $type = isset($properties['type']) ? (string) $properties['type'] : null;

        return new self($href, $type);
    }
}
