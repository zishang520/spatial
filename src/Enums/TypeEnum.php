<?php

namespace luoyy\Spatial\Enums;

/**
 * GeoJSON 类型枚举。
 *
 * 用于标识 GeoJSON 支持的所有几何与要素类型。
 *
 * - LINE_STRING：线串
 * - MULTI_LINE_STRING：多线串
 * - MULTI_POINT：多点
 * - MULTI_POLYGON：多多边形
 * - POINT：点
 * - POLYGON：多边形
 * - GEOMETRY_COLLECTION：几何集合
 * - FEATURE：要素
 * - FEATURE_COLLECTION：要素集合
 */
enum TypeEnum: string
{
    case LINE_STRING = 'LineString';           // 线串
    case MULTI_LINE_STRING = 'MultiLineString'; // 多线串
    case MULTI_POINT = 'MultiPoint';           // 多点
    case MULTI_POLYGON = 'MultiPolygon';       // 多多边形
    case POINT = 'Point';                      // 点
    case POLYGON = 'Polygon';                  // 多边形
    case GEOMETRY_COLLECTION = 'GeometryCollection'; // 几何集合
    case FEATURE = 'Feature';                  // 要素
    case FEATURE_COLLECTION = 'FeatureCollection'; // 要素集合
}
