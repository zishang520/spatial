<?php

namespace luoyy\Spatial\Geometry;

use luoyy\Spatial\BoundingBox;
use luoyy\Spatial\CoordinateReferenceSystem\CoordinateReferenceSystem;
use luoyy\Spatial\Enums\TypeEnum;

/**
 * 多线串（MultiLineString）几何对象。
 *
 * 坐标由多个 LineString 的坐标数组组成。
 *
 * @see http://www.geojson.org/geojson-spec.html#multilinestring
 * @since 1.0
 */
class MultiLineString extends Geometry
{
    /**
     * 构造函数。
     *
     * @param array<LineString|array<Point|array<int|float>>> $lineStrings lineString 对象数组或坐标数组
     * @param CoordinateReferenceSystem|BoundingBox ...$args 可选参数，坐标参考系、边界框或坐标系统枚举。
     */
    public function __construct(array $lineStrings, CoordinateReferenceSystem|BoundingBox ...$args)
    {
        $this->coordinates = \array_map(
            static function ($lineString): array {
                if (! $lineString instanceof LineString) {
                    $lineString = new LineString($lineString);
                }
                return $lineString->getCoordinates();
            },
            $lineStrings
        );
        $this->setOptionalConstructorArgs($args);
    }

    /**
     * 获取类型。
     */
    public function getType(): TypeEnum
    {
        return TypeEnum::MULTI_LINE_STRING;
    }
}
