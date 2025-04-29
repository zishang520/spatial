<?php

namespace luoyy\Spatial\Contracts;

use luoyy\Spatial\Enums\PointEnum;
use luoyy\Spatial\Spatial;
use luoyy\Spatial\Transform;

abstract class Point implements \JsonSerializable, \Stringable
{
    public const COORDINATE_SYSTEM = PointEnum::WGS84;

    /**
     * 经度.
     */
    public float $longitude;

    /**
     * 纬度.
     */
    public float $latitude;

    /**
     * 海拔高度.
     */
    public float $altitude = 0;

    /**
     * 是否自动修正.
     */
    protected bool $noAutofix = false;

    /**
     * 是否输出数组.
     */
    protected bool $_useArray = false;

    /**
     * 是否输出高度.
     */
    protected bool $_useAltitude = false;

    /**
     * 坐标点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param bool|null $noAutofix noAutoFix表示是否自动将经度修正到 [-180,180] 区间内，缺省为false
     * @throw \RangeException
     */
    public function __construct(float $longitude, float $latitude, ?bool $noAutofix = null, float $altitude = 0)
    {
        $this->noAutofix = $noAutofix ?? $this->noAutofix;
        $this->setLongitude($longitude);
        $this->setLatitude($latitude);
        $this->setAltitude($altitude);
    }

    public function __toString(): string
    {
        return $this->_useAltitude ? "{$this->longitude},{$this->latitude},{$this->altitude}" : "{$this->longitude},{$this->latitude}";
    }

    /**
     * 坐标点.
     * @copyright (c) zishang520 All Rights Reserved
     * @param float $longitude 经度
     * @param float $latitude 纬度
     * @param bool|null $noAutofix noAutoFix表示是否自动将经度修正到 [-180,180] 区间内，缺省为false
     * @throw \RangeException
     */
    public static function make(float $longitude, float $latitude, ?bool $noAutofix = null, float $altitude = 0): static
    {
        return new static($longitude, $latitude, $noAutofix,  $altitude);
    }

    public function setLatitude(float $latitude): static
    {
        if (!is_finite($latitude)) {
            throw new \RangeException('Latitude must be a finite value.');
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
            throw new \RangeException('Longitude must be a finite value.');
        }
        if (!$this->noAutofix) {
            $longitude = fmod($longitude + 180, 360) + (-180 > $longitude || $longitude == 180 ? 180 : -180);
        }
        $this->longitude = $longitude;
        return $this;
    }

    public function setAltitude(float $altitude): static
    {
        if (!is_finite($altitude)) {
            throw new \RangeException('Altitude must be a finite value.');
        }
        $this->altitude = $altitude;
        return $this;
    }

    /**
     * 是否输出数组.
     * @copyright (c) zishang520 All Rights Reserved
     */
    public function useArray(bool $useArray = true)
    {
        $this->_useArray = $useArray;
        return $this;
    }

    /**
     * 是否输出高度.
     * @copyright (c) zishang520 All Rights Reserved
     */
    public function useAltitude(bool $useAltitude = true)
    {
        $this->_useAltitude = $useAltitude;
        return $this;
    }

    public function toArray(): array
    {
        $data = ['longitude' => $this->longitude, 'latitude' => $this->latitude];
        if ($this->_useAltitude) {
            $data['altitude'] = $this->altitude;
        }
        if ($this->_useArray) {
            return array_values($data);
        }
        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toGeometry(): array
    {
        return [
            'type' => 'Point',
            'coordinates' => (clone $this)->useArray(true)->useAltitude($this->_useAltitude)->toArray(),
        ];
    }

    public function transform(PointEnum $to): Point
    {
        return Transform::transform($this, $to);
    }

    public function move(float $dist, float $bearing, float $radius = Spatial::EARTH_RADIUS): static
    {
        return Spatial::move($this, $dist, $bearing, $radius);
    }
}
