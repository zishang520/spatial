# 阿里云核心SDK号码认证模块

### 安装包
```bash
composer require luoyy/aliyun-core-pns
```
### Quick Sample Usage
```php
/**
 * PNS DEMO
 */
use luoyy\AliCore\Facades\AcsClient;
use luoyy\AliCore\Pns\Request\GetMobileRequest;


// 初始化GetMobileRequest实例用于设置手机号认证获取手机号的参数
$request = new GetMobileRequest();
// 必填，app端SDK获取的登录token。请参考: https://help.aliyun.com/document_detail/189865.html?spm=a2c4g.11186623.6.581.3f0e73041pspL0
$request->setAccessToken(/*access_token*/);
// 选填，外部流水号。请参考: https://help.aliyun.com/document_detail/189865.html?spm=a2c4g.11186623.6.581.3f0e73041pspL0
$request->setOutId(/*out_id*/);
$response = AcsClient::getAcsResponse($request);
var_dump($response);
```
