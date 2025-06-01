<?php

namespace luoyy\Spatial;

use luoyy\Spatial\Geometry\Point;

/**
 * EGM96 高程模型工具类。
 *
 * 提供基于 EGM96 模型的平均海平面高程、椭球高与大地高互转等功能。
 */
class EGM96Universal
{
    public const RADIAN = M_PI / 180;

    public const INTERVAL = (15 / 60) * self::RADIAN;

    public const NUM_ROWS = 721;

    public const NUM_COLS = 1440;

    private const DATA_FILE = '.data.bin';

    /**
     * @var resource|false|null
     */
    private static $dt;

    /**
     * 根据 EGM96 获取平均海平面高度。
     *
     * @param Point $point 原始坐标（请设置 altitude 属性值）
     * @throws \RangeException 纬度超出范围
     */
    public static function meanSeaLevel(Point $point): float
    {
        $lat = self::normalizeRadians($point->getLatitude() * self::RADIAN);
        if ($lat > M_PI || $lat < -M_PI) {
            throw new \RangeException('Invalid latitude.');
        }
        $lon = self::normalizeRadians($point->getLongitude() * self::RADIAN);

        $topRow = (int) floor(((M_PI / 2) - $lat) / self::INTERVAL);
        $topRow = ($topRow == self::NUM_ROWS - 1) ? $topRow - 1 : $topRow;
        $bottomRow = $topRow + 1;

        $leftCol = (int) floor(self::normalizeRadians($lon, M_PI) / self::INTERVAL);
        $rightCol = ($leftCol + 1) % self::NUM_COLS;

        $topLeft = self::getValue($topRow, $leftCol);
        $bottomLeft = self::getValue($bottomRow, $leftCol);
        $bottomRight = self::getValue($bottomRow, $rightCol);
        $topRight = self::getValue($topRow, $rightCol);

        $lonLeft = self::normalizeRadians($leftCol * self::INTERVAL);
        $latTop = (M_PI / 2) - ($topRow * self::INTERVAL);

        $leftProp = ($lon - $lonLeft) / self::INTERVAL;
        $topProp = ($latTop - $lat) / self::INTERVAL;

        return self::bilinearInterpolation($topLeft, $bottomLeft, $bottomRight, $topRight, $leftProp, $topProp);
    }

    /**
     * 将 WGS84 椭球高转换为 EGM96 高程。
     *
     * @param Point $point 原始坐标（请设置 altitude 属性值）
     */
    public static function ellipsoidToEgm96(Point $point): float
    {
        return $point->getAltitude() - self::meanSeaLevel($point);
    }

    /**
     * 将 EGM96 高程转换为 WGS84 椭球高。
     *
     * @param Point $point 原始坐标（请设置 altitude 属性值）
     */
    public static function egm96ToEllipsoid(Point $point): float
    {
        return $point->getAltitude() + self::meanSeaLevel($point);
    }

    private static function bilinearInterpolation(float $topLeft, float $bottomLeft, float $bottomRight, float $topRight, float $x, float $y): float
    {
        $top = self::linearInterpolation($topLeft, $topRight, $x);
        $bottom = self::linearInterpolation($bottomLeft, $bottomRight, $x);

        return self::linearInterpolation($top, $bottom, $y);
    }

    private static function linearInterpolation(float $a, float $b, float $prop): float
    {
        return $a + (($b - $a) * $prop);
    }

    private static function normalizeRadians(float $rads, float $center = 0): float
    {
        return $rads - (2 * M_PI) * floor(($rads + M_PI - $center) / (2 * M_PI));
    }

    private static function getData(int $id): int
    {
        $fp = self::data();
        if ($fp === false) {
            throw new \RuntimeException('File read failed.');
        }
        if (fseek($fp, $id * 2 + 1, SEEK_SET) !== 0) {
            throw new \OutOfBoundsException('File offset setting failed.');
        }
        if (($data = fread($fp, 2)) === false) {
            throw new \UnexpectedValueException('Failed to read file content.');
        }
        if (($u = unpack('s', $data)) === false) {
            throw new \UnexpectedValueException('Data unpacking failed.');
        }
        return $u[1];
    }

    private static function getValue(int $row, int $col): float
    {
        return self::getData($row * self::NUM_COLS + $col) / 100;
    }

    /**
     * @return resource|false
     */
    private static function data()
    {
        if (is_null(self::$dt)) {
            self::$dt = fopen(__DIR__ . DIRECTORY_SEPARATOR . self::DATA_FILE, 'r');
        }
        return self::$dt;
    }
}
