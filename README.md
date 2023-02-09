# 坐标相关的组件

### 安装包
```bash
composer require luoyy/spatial
```
### Quick Sample Usage
```php
/**
 * DEMO
 */
use luoyy\Spatial\Spatial;
use luoyy\Spatial\Support\LineString;
use luoyy\Spatial\Support\Point; // WGS84
use luoyy\Spatial\Support\Polygon;
use luoyy\Spatial\Transform;

var_dump(Transform::transform(new Point(180, 90), Transform::GCJ02));
var_dump((new Point(180, 90))->transform(Transform::GCJ02));
var_dump(Spatial::ringArea(new Polygon(new Point(116.169465, 39.932670), new Point(116.160260, 39.924492), new Point(116.150625, 39.710019), new Point(116.183198, 39.709920), new Point(116.226950, 39.777616), new Point(116.442621, 39.799892), new Point(116.463478, 39.790066), new Point(116.588276, 39.809551), new Point(116.536091, 39.808859), new Point(116.573856, 39.839643), new Point(116.706380, 39.916740), new Point(116.600293, 39.937770), new Point(116.514805, 39.982375), new Point(116.499935, 40.013710), new Point(116.546520, 40.030443), new Point(116.687668, 40.129961), new Point(116.539697, 40.080659), new Point(116.503390, 40.058474), new Point(116.468800, 40.052578))));
var_dump(Spatial::distance(new Point(116.169465, 39.932670), new Point(116.160260, 39.924492)));
var_dump(Spatial::lineDistance(new LineString(new Point(116.169465, 39.932670), new Point(116.160260, 39.924492), new Point(116.150625, 39.710019), new Point(116.183198, 39.709920), new Point(116.226950, 39.777616), new Point(116.442621, 39.799892), new Point(116.463478, 39.790066), new Point(116.588276, 39.809551), new Point(116.536091, 39.808859), new Point(116.573856, 39.839643), new Point(116.706380, 39.916740), new Point(116.600293, 39.937770), new Point(116.514805, 39.982375), new Point(116.499935, 40.013710), new Point(116.546520, 40.030443), new Point(116.687668, 40.129961), new Point(116.539697, 40.080659), new Point(116.503390, 40.058474), new Point(116.468800, 40.052578))));
```
