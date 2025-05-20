<?php

namespace luoyy\Spatial\Enums;

use luoyy\Spatial\Support\PointBD09;
use luoyy\Spatial\Support\PointCGCS2000;
use luoyy\Spatial\Support\PointGCJ02;
use luoyy\Spatial\Support\PointGK;
use luoyy\Spatial\Support\PointWGS84;

/**
 * 坐标系类型枚举。
 *
 * 用于标识支持的主流地理坐标系统。
 *
 * - WGS84：全球 GPS 标准坐标系
 * - GCJ02：中国火星坐标系
 * - BD09：百度坐标系
 * - CGCS2000：中国大地坐标系 2000
 * - GK：高斯-克吕格投影坐标系
 */
enum CoordinateSystemEnum: string
{
    case WGS84 = PointWGS84::class;      // WGS84 坐标系
    case GCJ02 = PointGCJ02::class;      // GCJ02 坐标系
    case BD09 = PointBD09::class;        // 百度 BD09 坐标系
    case CGCS2000 = PointCGCS2000::class; // CGCS2000 坐标系
    case GK = PointGK::class;            // 高斯-克吕格投影坐标系
}
