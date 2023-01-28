<?php

namespace luoyy\Spatial\Support;

use IteratorAggregate;
use JsonSerializable;
use LengthException;
use luoyy\Spatial\Contracts\Point;
use Traversable;

/**
 * 多边形不用处理最后一个点与第一个点相对.
 */
class Polygon implements JsonSerializable, IteratorAggregate
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
            throw new LengthException('Polygon requires at least three points.');
        }
        $this->points = $points;
        return $this;
    }

    public function addPoint(Point $point): self
    {
        array_push($this->points, $point);
        return $this;
    }

    public function getIterator(): Traversable
    {
        yield from $this->build();
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
        return $this->useArray ? array_map(function ($point) {
            return $point->useArray($this->useArray)->toArray();
        }, $this->build()) : ['points' => array_map(function ($point) {
            return $point->useArray($this->useArray)->toArray();
        }, $this->build())];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function build()
    {
        if (end($this->points) != $this->points[0]) {
            $this->addPoint($this->points[0]);
        }
        return $this->points;
    }
}
