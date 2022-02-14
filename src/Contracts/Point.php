<?php

namespace luoyy\Spatial\Contracts;

use JsonSerializable;
use luoyy\Spatial\Enums\PointEnum;
use luoyy\Spatial\Spatial;
use luoyy\Spatial\Transform;
use RangeException;

abstract class Point implements JsonSerializable
{
    public const COORDINATE_SYSTEM = PointEnum::WGS84;

    /**
     * 经度.
     * @var float
     */
    public $longitude;

    /**
     * 纬度.
     * @var float
     */
    public $latitude;

    /**
     * 是否自动修正.
     * @var bool
     */
    protected $noAutofix = false;

    /**
     * 是否输出数组.
     * @var bool
     */
    protected $useArray = false;

    /**
     * 坐标点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param bool|null $noAutofix noAutoFix表示是否自动将经度修正到 [-180,180] 区间内，缺省为false
     * @throw RangeException
     */
    public function __construct(float $longitude, float $latitude, ?bool $noAutofix = null)
    {
        $this->noAutofix = $noAutofix ?? $this->noAutofix;
        $this->setLongitude($longitude);
        $this->setLatitude($latitude);
    }

    public function setLatitude(float $latitude): static
    {
        if (!is_finite($latitude)) {
            throw new RangeException('Latitude must be a finite value.');
        }
        if (!$this->noAutofix) {
            $latitude = max(min($latitude, 90), -90);
        }
        $this->latitude = $latitude;
        return $this;
    }

    public function setLongitude(float $longitude): static
    {
        if (!is_finite($longitude)) {
            throw new RangeException('Longitude must be a finite value.');
        }
        if (!$this->noAutofix) {
            $longitude = fmod($longitude + 180, 360) + (-180 > $longitude || $longitude === 180 ? 180 : -180);
        }
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * 是否输出数组.
     * @copyright (c) zishang520 All Rights Reserved
     */
    public function useArray(bool $useArray = true)
    {
        $this->useArray = $useArray;
        return $this;
    }

    public function toArray(): array
    {
        return $this->useArray ? [$this->longitude, $this->latitude] : ['longitude' => $this->longitude, 'latitude' => $this->latitude];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function transform(PointEnum $to): Point
    {
        return Transform::transform($this, $to);
    }

    public function move(int $dist, int $bearing, float $radius = Spatial::EARTH_RADIUS): static
    {
        return Spatial::move($this, $dist, $bearing, $radius);
    }
}
