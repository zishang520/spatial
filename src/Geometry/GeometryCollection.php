<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\AbstractCollection;
use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

use function array_map;
use function array_merge;

/**
 * 几何对象集合类，包含多个 Geometry 对象。
 *
 * @see http://www.geojson.org/geojson-spec.html#geometry-collection
 * @since 1.0
 */
class GeometryCollection extends AbstractCollection
{
    /**
     * 构造函数。
     *
     * @param array<Geometry> $geometries Geometry 对象数组。
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     */
    public function __construct(array $geometries, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        parent::__construct($geometries, Geometry::class);
        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取集合类型。
     *
     * @return TypeEnum 类型枚举，恒为 TypeEnum::GEOMETRY_COLLECTION。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::GEOMETRY_COLLECTION;
    }

    /**
     * 获取集合中的 Geometry 对象。
     *
     * @return array<Geometry> Geometry 对象数组。
     */
    public function getGeometries(): array
    {
        return $this->items;
    }

    /**
     * 序列化为 GeoJSON 数组。
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            ['geometries' => array_map(
                static fn(Geometry $geometry) => $geometry->jsonSerialize(),
                $this->items
            )]
        );
    }
}
