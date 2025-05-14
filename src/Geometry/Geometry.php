<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\GeoJson;

/**
 * 几何对象基类，所有几何类型（点、线、面等）继承自此类。
 *
 * @see http://www.geojson.org/geojson-spec.html#geometry-objects
 * @since 1.0
 */
abstract class Geometry extends GeoJson
{
    /**
     * @var array 坐标数组，具体结构由子类定义。
     */
    protected array $coordinates = [];

    /**
     * @var int|null 空间参考ID（SRID），可选。
     */
    protected ?int $srid = null;

    /**
     * 获取当前几何对象的坐标数组。
     *
     * @return array 坐标数组。
     */
    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    /**
     * 获取空间参考ID（SRID）。
     *
     * @return int|null SRID，若未设置则为 null。
     */
    public function getSrid(): ?int
    {
        return $this->srid;
    }

    /**
     * 设置空间参考ID（SRID）。
     *
     * @param int|null $srid SRID。
     */
    public function setSrid(?int $srid): void
    {
        $this->srid = $srid;
    }

    /**
     * 序列化为 GeoJSON 数组。
     *
     */
    public function jsonSerialize(): array
    {
        $json = parent::jsonSerialize();

        if (isset($this->coordinates)) {
            $json['coordinates'] = $this->coordinates;
        }

        return $json;
    }
}
