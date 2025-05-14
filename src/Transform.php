<?php

namespace luoyy\Spatial;

use luoyy\Spatial\Contracts\PointInterface;
use luoyy\Spatial\Enums\CoordinateSystemEnum;
use luoyy\Spatial\Support\PointBD09;
use luoyy\Spatial\Support\PointCGCS2000;
use luoyy\Spatial\Support\PointGCJ02;
use luoyy\Spatial\Support\PointGK;
use luoyy\Spatial\Support\PointWGS84;

/**
 * 坐标转换服务类，提供多种主流坐标系间的转换算法。
 * 支持 WGS84、CGCS2000、GCJ02、BD09、GK 等。
 */
class Transform
{
    protected const X_PI = M_PI * 3000.0 / 180.0;

    protected const GCJ02_A_ELLIPSOID = 6378245.0;

    protected const GCJ02_EE_ELLIPSOID = 0.00669342162296594323;

    protected const A_WGS84 = 6378137.0;

    protected const F_WGS84_INV = 298.257223563;

    protected const F_WGS84 = 1 / self::F_WGS84_INV;

    protected const E2_WGS84 = (2 * self::F_WGS84) - (self::F_WGS84 * self::F_WGS84);

    protected const A_CGCS2000 = 6378137.0;

    protected const F_CGCS2000_INV = 298.257222101;

    protected const F_CGCS2000 = 1 / self::F_CGCS2000_INV;

    protected const E2_CGCS2000 = (2 * self::F_CGCS2000) - (self::F_CGCS2000 * self::F_CGCS2000);

    protected const E_PRIME2_CGCS2000 = self::E2_CGCS2000 / (1 - self::E2_CGCS2000);

    protected const DEFAULT_HELMERT_PARAMS_WGS84_TO_CGCS2000 = [
        'tx' => 0, 'ty' => 0, 'tz' => 0,
        's' => 0,
        'rx' => 0, 'ry' => 0, 'rz' => 0
    ];

    protected const DEFAULT_HELMERT_PARAMS_CGCS2000_TO_WGS84 = [
        'tx' => 0, 'ty' => 0, 'tz' => 0,
        's' => 0,
        'rx' => 0, 'ry' => 0, 'rz' => 0
    ];

    protected const GK_FALSE_EASTING = 500000.0;

    protected const GK_FALSE_NORTHING = 0.0;

    protected const ITERATION_PRECISION_PHI = 1E-12;

    protected const ITERATION_PRECISION_DEG_GCJ_INV = 1E-9;

    protected const ITERATION_PRECISION_DEG_BD_INV = 1E-10;

    protected const MAX_ITERATIONS_GEODETIC = 10;

    protected const MAX_ITERATIONS_FOOTPOINT = 10;

    protected const MAX_ITERATIONS_GCJ02_INV = 15;

    protected const MAX_ITERATIONS_BD09_INV = 15;

    /**
     * WGS84 坐标系转 CGCS2000 坐标系。
     *
     * @param PointWGS84 $point WGS84 坐标点
     * @param array $helmert 七参数（Helmert）变换参数，默认零变换
     * @return PointCGCS2000 CGCS2000 坐标点
     */
    public static function WGS84_CGCS2000(PointWGS84 $point, array $helmert = self::DEFAULT_HELMERT_PARAMS_WGS84_TO_CGCS2000): PointCGCS2000
    {
        return new PointCGCS2000(self::geoHelmert($point, ['a' => self::A_WGS84, 'e2' => self::E2_WGS84], ['a' => self::A_CGCS2000, 'e2' => self::E2_CGCS2000], $helmert));
    }

    /**
     * CGCS2000 坐标系转 WGS84 坐标系。
     *
     * @param PointCGCS2000 $point CGCS2000 坐标点
     * @param array $helmert 七参数（Helmert）变换参数，默认零变换
     * @return PointWGS84 WGS84 坐标点
     */
    public static function CGCS2000_WGS84(PointCGCS2000 $point, array $helmert = self::DEFAULT_HELMERT_PARAMS_CGCS2000_TO_WGS84): PointWGS84
    {
        return new PointWGS84(self::geoHelmert($point, ['a' => self::A_CGCS2000, 'e2' => self::E2_CGCS2000], ['a' => self::A_WGS84, 'e2' => self::E2_WGS84], $helmert));
    }

    /**
     * WGS84 坐标系转 GCJ02（火星坐标系）。
     *
     * @param PointWGS84 $point WGS84 坐标点
     * @return PointGCJ02 GCJ02 坐标点
     */
    public static function WGS84_GCJ02(PointWGS84 $point): PointGCJ02
    {
        if (self::outChina($point)) {
            return new PointGCJ02($point->getCoordinates());
        }
        [$lng, $lat, $h] = [$point->getLongitude(), $point->getLatitude(), $point->getAltitude()];
        $dLat = self::dLat($lng - 105, $lat - 35);
        $dLng = self::dLng($lng - 105, $lat - 35);
        $rLat = $lat / 180 * M_PI;
        $magic = 1 - self::GCJ02_EE_ELLIPSOID * pow(sin($rLat), 2);
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180) / ((self::GCJ02_A_ELLIPSOID * (1 - self::GCJ02_EE_ELLIPSOID)) / ($magic * $sqrtMagic) * M_PI);
        $dLng = ($dLng * 180) / (self::GCJ02_A_ELLIPSOID / $sqrtMagic * cos($rLat) * M_PI);
        return new PointGCJ02($h == 0 ? [$lng + $dLng, $lat + $dLat] : [$lng + $dLng, $lat + $dLat, $h]);
    }

    /**
     * GCJ02（火星坐标系）转 WGS84 坐标系。
     *
     * @param PointGCJ02 $point GCJ02 坐标点
     * @return PointWGS84 WGS84 坐标点
     */
    public static function GCJ02_WGS84(PointGCJ02 $point): PointWGS84
    {
        if (self::outChina($point)) {
            return new PointWGS84($point->getCoordinates());
        }
        [$lng, $lat, $h] = [$point->getLongitude(), $point->getLatitude(), $point->getAltitude()];
        for ($wLng = $lng, $wLat = $lat, $i = 0; $i < self::MAX_ITERATIONS_GCJ02_INV; $i++) {
            $tmp = self::WGS84_GCJ02(new PointWGS84([$wLng, $wLat]));
            [$dLng, $dLat] = [$tmp->getLongitude() - $lng, $tmp->getLatitude() - $lat];
            if (abs($dLng) < self::ITERATION_PRECISION_DEG_GCJ_INV && abs($dLat) < self::ITERATION_PRECISION_DEG_GCJ_INV) {
                break;
            }
            $wLng -= $dLng;
            $wLat -= $dLat;
        }
        return new PointWGS84($h == 0 ? [$wLng, $wLat] : [$wLng, $wLat, $h]);
    }

    /**
     * GCJ02（火星坐标系）转 BD09（百度坐标系）。
     *
     * @param PointGCJ02 $point GCJ02 坐标点
     * @return PointBD09 BD09 坐标点
     */
    public static function GCJ02_BD09(PointGCJ02 $point): PointBD09
    {
        [$lng, $lat, $h] = [$point->getLongitude(), $point->getLatitude(), $point->getAltitude()];
        [$z, $t] = [sqrt($lng * $lng + $lat * $lat) + 0.00002 * sin($lat * self::X_PI), atan2($lat, $lng) + 0.000003 * cos($lng * self::X_PI)];
        return new PointBD09($h == 0 ? [$z * cos($t) + 0.0065, $z * sin($t) + 0.006] : [$z * cos($t) + 0.0065, $z * sin($t) + 0.006, $h]);
    }

    /**
     * WGS84 坐标系直接转 BD09（百度坐标系）。
     *
     * @param PointWGS84 $point WGS84 坐标点
     * @return PointBD09 BD09 坐标点
     */
    public static function WGS84_BD09(PointWGS84 $point): PointBD09
    {
        return self::GCJ02_BD09(self::WGS84_GCJ02($point));
    }

    /**
     * BD09（百度坐标系）转 GCJ02（火星坐标系）。
     *
     * @param PointBD09 $point BD09 坐标点
     * @return PointGCJ02 GCJ02 坐标点
     */
    public static function BD09_GCJ02(PointBD09 $point): PointGCJ02
    {
        [$lng, $lat, $h] = [$point->getLongitude(), $point->getLatitude(), $point->getAltitude()];
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * self::X_PI);
        $t = atan2($y, $x) - 0.000003 * cos($x * self::X_PI);
        for ($gLng = $z * cos($t),$gLat = $z * sin($t),$i = 0; $i < self::MAX_ITERATIONS_BD09_INV; $i++) {
            $tmp = self::GCJ02_BD09(new PointGCJ02([$gLng, $gLat]));
            [$dLng, $dLat] = [$tmp->getLongitude() - $lng, $tmp->getLatitude() - $lat];
            if (abs($dLng) < self::ITERATION_PRECISION_DEG_BD_INV && abs($dLat) < self::ITERATION_PRECISION_DEG_BD_INV) {
                break;
            }
            $gLng -= $dLng;
            $gLat -= $dLat;
        }
        return new PointGCJ02($h == 0 ? [$gLng, $gLat] : [$gLng, $gLat, $h]);
    }

    /**
     * BD09（百度坐标系）转 WGS84 坐标系。
     *
     * @param PointBD09 $point BD09 坐标点
     * @return PointWGS84 WGS84 坐标点
     */
    public static function BD09_WGS84(PointBD09 $point): PointWGS84
    {
        return self::GCJ02_WGS84(self::BD09_GCJ02($point));
    }

    /**
     * CGCS2000 坐标系转高斯-克吕格(GK)投影坐标。
     *
     * @param PointCGCS2000 $point CGCS2000 坐标点
     * @param int|null $w 分带宽度，3 或 6，默认3度带
     * @return PointGK GK投影坐标点（含带号）
     */
    public static function CGCS2000_GK(PointCGCS2000 $point, ?int $w = 3): PointGK
    {
        [$lng, $lat] = [$point->getLongitude(), $point->getLatitude()];
        $phi = deg2rad($lat);
        $lam = deg2rad($lng);
        $z = $w === 6 ? (int) floor($lng / 6) + 1 : (int) round($lng / 3);
        $L0 = deg2rad($w === 6 ? $z * 6 - 3 : $z * 3);
        $a = self::A_CGCS2000;
        $e2 = self::E2_CGCS2000;
        $ep2 = self::E_PRIME2_CGCS2000;
        $M = self::mArc($phi, $a, $e2);
        $dl = $lam - $L0;
        $sp = sin($phi);
        $cp = cos($phi);
        $tp = tan($phi);
        $N = $a / sqrt(1 - $e2 * $sp * $sp);
        $t2 = $tp * $tp;
        $eta2 = $ep2 * $cp * $cp;
        $x2 = $dl * $dl * $cp * $cp / 2;
        $x4 = pow($dl, 4) * pow($cp, 4) / 24 * (5 - $t2 + 9 * $eta2 + 4 * $eta2 * $eta2);
        $x6 = pow($dl, 6) * pow($cp, 6) / 720 * (61 - 58 * $t2 + $t2 * $t2 + 600 * $eta2 - 330 * $ep2);
        $north = $M + $N * $tp * ($x2 + $x4 + $x6) + self::GK_FALSE_NORTHING;
        $y1 = $dl;
        $y3 = pow($dl, 3) * $cp * $cp / 6 * (1 - $t2 + $eta2);
        $y5 = pow($dl, 5) * pow($cp, 4) / 120 * (5 - 18 * $t2 + $t2 * $t2 + 14 * $eta2 - 58 * $t2 * $eta2 + ($ep2 > 0 ? 4 * pow($eta2, 2) : 0));
        $east = $N * $cp * ($y1 + $y3 + $y5) + self::GK_FALSE_EASTING;
        return new PointGK([$east, $north, $z]);
    }

    /**
     * GK投影坐标转 CGCS2000 坐标系。
     *
     * @param PointGK $point GK投影坐标点（含带号）
     * @param int $w 分带宽度，3 或 6，默认3度带
     * @return PointCGCS2000 CGCS2000 坐标点
     */
    public static function GK_CGCS2000(PointGK $point, int $w = 3): PointCGCS2000
    {
        [$e, $n, $z] = [$point->getLongitude(), $point->getLatitude(), $point->getAltitude()];
        $a = self::A_CGCS2000;
        $e2 = self::E2_CGCS2000;
        $ep2 = self::E_PRIME2_CGCS2000;
        $L0 = deg2rad($w === 6 ? $z * 6 - 3 : $z * 3);
        $x = $n - self::GK_FALSE_NORTHING;
        $y = $e - self::GK_FALSE_EASTING;
        $phi1 = $x / ($a * (1 - $e2 / 4 - 3 * pow($e2, 2) / 64 - 5 * pow($e2, 3) / 256));
        for ($i = 0; $i < self::MAX_ITERATIONS_FOOTPOINT; $i++) {
            $M1 = self::mArc($phi1, $a, $e2);
            $rho1 = $a * (1 - $e2) / pow(1 - $e2 * pow(sin($phi1), 2), 1.5);
            $dphi1 = ($x - $M1) / $rho1;
            $phi1 += $dphi1;
            if (abs($dphi1) < self::ITERATION_PRECISION_PHI) {
                break;
            }
        }
        $sp1 = sin($phi1);
        $cp1 = cos($phi1);
        $tp1 = tan($phi1);
        $t1_2 = $tp1 * $tp1;
        $N1 = $a / sqrt(1 - $e2 * $sp1 * $sp1);
        $R1 = $a * (1 - $e2) / pow(1 - $e2 * $sp1 * $sp1, 1.5);
        $eta1_2 = $ep2 * $cp1 * $cp1;
        $D = $y / $N1;
        $D2 = $D * $D;
        $lat = $phi1 - $N1 * $tp1 / $R1 * $D2 / 2 + $N1 * $tp1 / $R1 * pow($D, 4) / 24 * (5 + 3 * $t1_2 + $eta1_2 - 9 * $eta1_2 * $t1_2 - 4 * pow($eta1_2, 2)) - $N1 * $tp1 / $R1 * pow($D, 6) / 720 * (61 + 90 * $t1_2 + 45 * pow($t1_2, 2) + 46 * $eta1_2 - 252 * $t1_2 * $eta1_2 - 90 * pow($t1_2, 2) * $eta1_2);
        $lon = $L0 + ($D - pow($D, 3) / 6 * (1 + 2 * $t1_2 + $eta1_2) + pow($D, 5) / 120 * (5 - 18 * $t1_2 + pow($t1_2, 2) + 14 * $eta1_2 - 58 * $t1_2 * $eta1_2 + ($ep2 > 0 ? 4 * pow($eta1_2, 2) : 0))) / $cp1;
        return new PointCGCS2000([rad2deg($lon), rad2deg($lat)]);
    }

    /**
     * 判断坐标点是否在中国境内。
     *
     * @param PointInterface $point 坐标点
     * @return bool true 表示在中国境外，false 表示在中国境内
     */
    public static function outChina(PointInterface $point): bool
    {
        return ! ($point->getLongitude() >= 72.004 && $point->getLongitude() <= 137.8347 && $point->getLatitude() >= 0.8293 && $point->getLatitude() <= 55.8271);
    }

    /**
     * 通用坐标系转换。
     *
     * @param PointInterface $point 原始点
     * @param CoordinateSystemEnum $to 目标坐标系
     * @return PointInterface 转换后的点
     * @throws \InvalidArgumentException 不支持的转换类型
     */
    public static function transform(PointInterface $point, CoordinateSystemEnum $to): PointInterface
    {
        if (($from = $point->getCoordinateSystem()) === $to) {
            return $point;
        }
        if (! method_exists(static::class, $method = sprintf('%s_%s', $from->name, $to->name))) {
            throw new \InvalidArgumentException("Conversion type [{$from->name}] to [{$to->name}] is not supported. Supported types: BD09, WGS84, GCJ02.");
        }
        return call_user_func([static::class, $method], $point);
    }

    /**
     * GCJ02 坐标系纬度偏移量计算。
     * @param float $lng 经度差值
     * @param float $lat 纬度差值
     * @return float 偏移量
     */
    private static function dLat(float $lng, float $lat): float
    {
        $ret = -100 + 2 * $lng + 3 * $lat + 0.2 * $lat * $lat + 0.1 * $lng * $lat + 0.2 * sqrt(abs($lng));
        $ret += (20 * sin(6 * $lng * M_PI) + 20 * sin(2 * $lng * M_PI)) * 2 / 3;
        $ret += (20 * sin($lat * M_PI) + 40 * sin($lat / 3 * M_PI)) * 2 / 3;
        $ret += (160 * sin($lat / 12 * M_PI) + 320 * sin($lat * M_PI / 30)) * 2 / 3;
        return $ret;
    }

    /**
     * GCJ02 坐标系经度偏移量计算。
     * @param float $lng 经度差值
     * @param float $lat 纬度差值
     * @return float 偏移量
     */
    private static function dLng(float $lng, float $lat): float
    {
        $ret = 300 + $lng + 2 * $lat + 0.1 * $lng * $lng + 0.1 * $lng * $lat + 0.1 * sqrt(abs($lng));
        $ret += (20 * sin(6 * $lng * M_PI) + 20 * sin(2 * $lng * M_PI)) * 2 / 3;
        $ret += (20 * sin($lng * M_PI) + 40 * sin($lng / 3 * M_PI)) * 2 / 3;
        $ret += (150 * sin($lng / 12 * M_PI) + 300 * sin($lng / 30 * M_PI)) * 2 / 3;
        return $ret;
    }

    /**
     * 计算子午线弧长。
     * @param float $phi 纬度（弧度）
     * @param float $a 椭球长半轴
     * @param float $e2 椭球第一偏心率平方
     * @return float 弧长
     */
    private static function mArc($phi, $a, $e2)
    {
        $m0 = $a * (1 - $e2 / 4 - 3 * pow($e2, 2) / 64 - 5 * pow($e2, 3) / 256);
        $m2 = $a * (3 / 8 * $e2 + 3 * pow($e2, 2) / 32 + 45 * pow($e2, 3) / 1024);
        $m4 = $a * (15 * pow($e2, 2) / 256 + 45 * pow($e2, 3) / 1024);
        $m6 = $a * (35 * pow($e2, 3) / 3072);
        return $m0 * $phi - $m2 * sin(2 * $phi) + $m4 * sin(4 * $phi) - $m6 * sin(6 * $phi);
    }

    /**
     * 七参数（Helmert）变换实现。
     * @param PointInterface $point 源坐标点
     * @param array $src 源椭球参数（a, e2）
     * @param array $dst 目标椭球参数（a, e2）
     * @param array $p 七参数数组（tx, ty, tz, s, rx, ry, rz）
     * @return array 转换后坐标 [lng, lat, h]
     */
    private static function geoHelmert(PointInterface $point, array $src, array $dst, array $p): array
    {
        $phi = deg2rad($point->getLatitude());
        $lam = deg2rad($point->getLongitude());
        $h = $point->getAltitude();
        $sp = sin($phi);
        $cp = cos($phi);
        $sl = sin($lam);
        $cl = cos($lam);
        $N = $src['a'] / sqrt(1 - $src['e2'] * $sp * $sp);
        $x = ($N + $h) * $cp * $cl;
        $y = ($N + $h) * $cp * $sl;
        $z = ($N * (1 - $src['e2']) + $h) * $sp;

        $s = 1 + $p['s'];
        $tx = $p['tx'] + $s * ($x + $p['rz'] * $y - $p['ry'] * $z);
        $ty = $p['ty'] + $s * (-$p['rz'] * $x + $y + $p['rx'] * $z);
        $tz = $p['tz'] + $s * ($p['ry'] * $x - $p['rx'] * $y + $z);

        $lam = atan2($ty, $tx);
        $p_val = sqrt($tx * $tx + $ty * $ty);
        if ($p_val == 0) {
            return [rad2deg($lam), rad2deg($tz >= 0 ? M_PI_2 : -M_PI_2), abs($tz) - $dst['a'] * sqrt(1 - $dst['e2'])];
        }

        $phi = atan2($tz, $p_val * (1 - $dst['e2']));
        for ($i = 0; $i < self::MAX_ITERATIONS_GEODETIC; $i++) {
            $N = $dst['a'] / sqrt(1 - $dst['e2'] * pow(sin($phi), 2));
            $h = $p_val / cos($phi) - $N;
            $phi2 = atan($tz / ($p_val * (1 - $dst['e2'] * $N / ($N + $h))));
            if (abs($phi2 - $phi) < self::ITERATION_PRECISION_PHI) {
                break;
            }
            $phi = $phi2;
        }
        $N = $dst['a'] / sqrt(1 - $dst['e2'] * pow(sin($phi), 2));
        $h = $p_val / cos($phi) - $N;

        return [rad2deg($lam), rad2deg($phi), $h];
    }
}
