<?php

namespace luoyy\Spatial\Feature;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;
use luoyy\Spatial\GeoJson;
use luoyy\Spatial\Geometry\Geometry;
use stdClass;

/**
 * GeoJSON 要素对象（Feature）。
 *
 * 表示具有空间几何和属性的地理要素。
 *
 * @see http://www.geojson.org/geojson-spec.html#feature-objects
 * @since 1.0
 */
class Feature extends GeoJson
{
    protected ?Geometry $geometry;

    /**
     * Properties are a JSON object, which corresponds to an associative array, or null.
     *
     * @see https://www.rfc-editor.org/rfc/rfc7946#section-3.2
     */
    protected ?array $properties;

    /**
     * The identifier is either a JSON string or a number.
     *
     * @see https://www.rfc-editor.org/rfc/rfc7946#section-3.2
     */
    protected int|string|null $id;

    /**
     * 构造函数。
     *
     * @param Geometry|null $geometry 几何对象
     * @param array|null $properties 属性数组
     * @param int|string|null $id 唯一标识
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数
     */
    public function __construct(?Geometry $geometry = null, ?array $properties = null, int|string|null $id = null, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        $this->geometry = $geometry;
        $this->properties = $properties;
        $this->id = $id;

        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     * @return TypeEnum
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::FEATURE;
    }

    /**
     * 获取该要素的 Geometry 对象。
     * @return Geometry|null
     */
    public function getGeometry(): ?Geometry
    {
        return $this->geometry;
    }

    /**
     * 获取该要素的唯一标识。
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        return $this->id;
    }

    /**
     * 获取该要素的属性。
     * @return array|null
     */
    public function getProperties(): ?array
    {
        return $this->properties;
    }

    /**
     * 序列化为 GeoJSON 数组。
     * @return array
     */
    public function jsonSerialize(): array
    {
        $json = parent::jsonSerialize();

        $json['geometry'] = isset($this->geometry) ? $this->geometry->jsonSerialize() : null;
        $json['properties'] = $this->properties ?? null;

        // Ensure empty associative arrays are encoded as JSON objects
        if ($json['properties'] === []) {
            $json['properties'] = new stdClass();
        }

        if (isset($this->id)) {
            $json['id'] = $this->id;
        }

        return $json;
    }
}
