<?php

namespace luoyy\Spatial\Feature;

use luoyy\Spatial\AbstractCollection;
use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;
use luoyy\Spatial\GeoJson;

use function array_map;
use function array_merge;

/**
 * GeoJSON 要素集合对象（FeatureCollection）。
 *
 * 用于存放多个 Feature 要素。
 *
 * @see http://www.geojson.org/geojson-spec.html#feature-collection-objects
 * @since 1.0
 */
class FeatureCollection extends AbstractCollection
{
    /**
     * 构造函数。
     *
     * @param array<Feature> $features 要素数组
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数
     */
    public function __construct(array $features, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        parent::__construct($features, Feature::class);
        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取集合类型。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::FEATURE_COLLECTION;
    }

    /**
     * 获取集合中的 Feature 对象。
     * @return array<Feature>
     */
    public function getFeatures(): array
    {
        return $this->items;
    }

    /**
     * 序列化为 GeoJSON 数组。
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            ['features' => array_map(
                static fn(Feature $feature) => $feature->jsonSerialize(),
                $this->items
            )]
        );
    }
}
