<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Transform;

class RangePoint implements \JsonSerializable
{
    /**
     * 最大经度.
     * @var float
     */
    public $maxLongitude;

    /**
     * 最大纬度.
     * @var float
     */
    public $maxLatitude;

    /**
     * 最小经度.
     * @var float
     */
    public $minLongitude;

    /**
     * 最小纬度.
     * @var float
     */
    public $minLatitude;

    /**
     * 坐标点范围矩阵.
     * @copyright (c) zishang520 All Rights Reserved
     * @param float $maxLongitude 最大经度
     * @param float $maxLatitude 最大纬度
     * @param float $minLongitude 最小经度
     * @param float $minLatitude 最小纬度
     */
    public function __construct(float $maxLongitude, float $maxLatitude, float $minLongitude, float $minLatitude)
    {
        $this->setMaxLongitude($maxLongitude);
        $this->setMaxLatitude($maxLatitude);
        $this->setMinLongitude($minLongitude);
        $this->setMinLatitude($minLatitude);
    }

    public function setMaxLatitude(float $maxLatitude): self
    {
        $this->maxLatitude = $maxLatitude;
        return $this;
    }

    public function setMaxLongitude(float $maxLongitude): self
    {
        $this->maxLongitude = $maxLongitude;
        return $this;
    }

    public function setMinLatitude(float $minLatitude): self
    {
        $this->minLatitude = $minLatitude;
        return $this;
    }

    public function setMinLongitude(float $minLongitude): self
    {
        $this->minLongitude = $minLongitude;
        return $this;
    }

    public function getPolygon(string $to = Transform::WGS84): Polygon
    {
        if (!class_exists($class = sprintf('\%s\Point%s', __NAMESPACE__, $to), true)) {
            throw new \InvalidArgumentException(sprintf('Coordinate system "%s" does not exist.', $to));
        }
        return new Polygon(new $class($this->minLongitude, $this->maxLatitude), new $class($this->maxLongitude, $this->maxLatitude), new $class($this->maxLongitude, $this->minLatitude), new $class($this->minLongitude, $this->minLatitude));
    }

    public function toArray(): array
    {
        return [
            'maxLongitude' => $this->maxLongitude,
            'maxLatitude' => $this->maxLatitude,
            'minLongitude' => $this->minLongitude,
            'minLatitude' => $this->minLatitude,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
