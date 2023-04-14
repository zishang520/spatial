<?php

namespace luoyy\Spatial\Support;

use luoyy\Spatial\Contracts\Point;

class LineString implements \JsonSerializable, \IteratorAggregate
{
    /**
     * 坐标点.
     * @var array<Point>
     */
    public array $points;

    /**
     * 是否输出数组.
     */
    protected bool $_useArray = false;

    /**
     * 是否输出高度.
     */
    protected bool $_useAltitude = false;

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
        $data = array_map(fn ($point) => $point->useArray($this->_useArray)->useAltitude($this->_useAltitude)->toArray(), $this->points);
        if ($this->_useArray) {
            return $data;
        }
        return ['points' => $data];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toGeometry(): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => array_map(fn ($point) => (clone $point)->useArray(true)->useAltitude($this->_useAltitude)->toArray(), $this->points),
        ];
    }
}
