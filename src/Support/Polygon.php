<?php

namespace luoyy\Spatial\Support;

use LengthException;
use luoyy\Spatial\Contracts\Point;

/**
 * 多边形不用处理最后一个点与第一个点相对.
 */
class Polygon implements \JsonSerializable, \IteratorAggregate
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
     * 多边形.
     * @copyright (c) zishang520 All Rights Reserved
     * @param Point $points 点
     * @throw LengthException
     */
    public function __construct(Point ...$points)
    {
        $this->setPoints(...$points);
    }

    public function setPoints(Point ...$points): self
    {
        if (count($points) < 3) {
            throw new \LengthException('Polygon requires at least three points.');
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
        yield from $this->build();
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
        $data = array_map(fn ($point) => $point->useArray($this->_useArray)->useAltitude($this->_useAltitude)->toArray(), $this->build());
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
            'type' => 'Polygon',
            'coordinates' => [array_map(fn ($point) => (clone $point)->useArray(true)->useAltitude($this->_useAltitude)->toArray(), $this->build())],
        ];
    }

    protected function build()
    {
        if (end($this->points) != $this->points[0]) {
            $this->addPoint($this->points[0]);
        }
        return $this->points;
    }
}
