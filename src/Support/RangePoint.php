<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Enums\CoordinateSystemEnum;

/**
 * 坐标点范围矩阵对象。
 *
 * 用于描述经纬度（可选高度）范围，并可生成对应多边形。
 */
class RangePoint implements \JsonSerializable
{
    /**
     * @var float 最大经度
     */
    public float $maxLongitude;

    /**
     * @var float 最大纬度
     */
    public float $maxLatitude;

    /**
     * @var float 最小经度
     */
    public float $minLongitude;

    /**
     * @var float 最小纬度
     */
    public float $minLatitude;

    /**
     * @var float 海拔
     */
    public float $altitude = 0;

    /**
     * 构造函数。
     *
     * @param float $maxLongitude 最大经度
     * @param float $maxLatitude 最大纬度
     * @param float $minLongitude 最小经度
     * @param float $minLatitude 最小纬度
     * @param float $altitude 海拔，默认0
     */
    public function __construct(float $maxLongitude, float $maxLatitude, float $minLongitude, float $minLatitude, float $altitude = 0)
    {
        $this->maxLongitude = $maxLongitude;
        $this->maxLatitude = $maxLatitude;
        $this->minLongitude = $minLongitude;
        $this->minLatitude = $minLatitude;
        $this->altitude = $altitude;
    }

    /**
     * 静态工厂方法：通过数组创建 RangePoint。
     *
     * @param array $data 包含 maxLongitude、maxLatitude、minLongitude、minLatitude、altitude 的数组
     */
    public static function fromArray(array $data): RangePoint
    {
        return new self(
            $data['maxLongitude'] ?? 0,
            $data['maxLatitude'] ?? 0,
            $data['minLongitude'] ?? 0,
            $data['minLatitude'] ?? 0,
            $data['altitude'] ?? 0
        );
    }

    /**
     * 获取当前范围对应的多边形对象。
     *
     * @param CoordinateSystemEnum $coordinateSystemEnum 目标坐标系，默认 WGS84
     */
    public function getPolygon(CoordinateSystemEnum $coordinateSystemEnum = CoordinateSystemEnum::WGS84): Polygon
    {
        $coords = [
            [$this->minLongitude, $this->maxLatitude],
            [$this->maxLongitude, $this->maxLatitude],
            [$this->maxLongitude, $this->minLatitude],
            [$this->minLongitude, $this->minLatitude],
            [$this->minLongitude, $this->maxLatitude],
        ];
        if ($this->altitude != 0) {
            $coords = array_map(fn($c): array => array_merge($c, [$this->altitude]), $coords);
        }
        $points = array_map(fn($c): object => new ($coordinateSystemEnum->value)($c), $coords);
        return new Polygon([$points]);
    }

    /**
     * 转为数组。
     */
    public function toArray(): array
    {
        return [
            'maxLongitude' => $this->maxLongitude,
            'maxLatitude' => $this->maxLatitude,
            'minLongitude' => $this->minLongitude,
            'minLatitude' => $this->minLatitude,
            'altitude' => $this->altitude,
        ];
    }

    /**
     * 实现 JsonSerializable 接口，序列化为数组。
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
