<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point;

class LineString implements \JsonSerializable, \IteratorAggregate
{
    /**
     * 坐标点.
     * @var Point[]
     */
    public $points;

    /**
     * 是否输出数组.
     * @var bool
     */
    protected $useArray = false;

    /**
     * 线.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $points 点
     */
    public function __construct(Point ...$points)
    {
        $this->setPoints(...$points);
    }

    public function setPoints(Point ...$points): self
    {
        if (count($points) < 2) {
            throw new \LengthException('LineString requires at least two points.');
        }
        $this->points = $points;
        return $this;
    }

    public function addPoint(Point $point): self
    {
        array_push($this->points, $point);
        return $this;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->points;
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
        return $this->useArray ? array_map(fn ($point) => $point->useArray($this->useArray)->toArray(), $this->points) : ['points' => array_map(fn ($point) => $point->useArray($this->useArray)->toArray(), $this->points)];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toGeometry(): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => array_map(fn ($point) => $point->useArray(true)->toArray(), $this->points),
        ];
    }
}
