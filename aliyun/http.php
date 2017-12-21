<?php
header("Content-type: text/html; charset=utf-8");
set_time_limit(800);
date_default_timezone_set('Asia/Shanghai');
define('ENABLE_HTTP_PROXY', false);
define('HTTP_PROXY_IP', '127.0.0.1');
define('HTTP_PROXY_PORT', '8080');

// 阿里控制台可以获取到
const AccessKeyId = 'LTAI1znjajDhdNui';
const AccessKeySecret = 'fWwy6aNhahSrlmHbXBLAw8QrnGWKjj';

$to_url = "http://nlsapi.aliyun.com/recognize?model=chat";
interface ISigner
{
    public function getSignatureMethod();
    public function getSignatureVersion();
    public function signString($source, $accessSecret);
}
class ShaHmac1Signer implements ISigner
{
    public function signString($source, $accessSecret)
    {
        return    base64_encode(hash_hmac('sha1', $source, $accessSecret, true));
    }
    public function getSignatureMethod()
    {
        return "HMAC-SHA1";
    }
    public function getSignatureVersion()
    {
        return "2.0";
    }
}
class ShaHmac256Signer implements ISigner
{
    public function signString($source, $accessSecret)
    {
        return    base64_encode(hash_hmac('sha256', $source, $accessSecret, true));
    }
    public function getSignatureMethod()
    {
        return "HMAC-SHA256";
    }
    public function getSignatureVersion()
    {
        return "2.0";
    }
}
function encode_body($body){
    return base64_encode(md5(base64_encode(md5($body,true)),true));
}
function signature ($source){
    $signer = new ShaHmac1Signer();
    return $signer->signString($source, AccessKeySecret);
}

include_once './HttpHelper.php';

/*
 *  发送POST请求
 */
function sendAsrPost($audioData, $audioFormat, $sampleRate, $url) {
    $result = "";
    $request = new HttpHelper();
        $realUrl = $url;
        $method = "POST";
        $accept = "application/json";
        $content_type = "audio/".$audioFormat.";samplerate=".$sampleRate;
        $length = strlen($audioData);
        var_dump($length);
        $date = date("D, d M Y H:m:s e",time());
        // 1.对body做MD5+BASE64加密
        $bodyMd5 = encode_body($audioData);
        $stringToSign = $method."\n".$accept."\n".$bodyMd5."\n".$content_type."\n".$date ;
        // 2.计算 HMAC-SHA1
        $sig = new ShaHmac1Signer();
        $signature = $sig->signString($stringToSign, AccessKeySecret);
        // 3.得到 authorization header
        $authHeader = "Dataplus ".AccessKeyId.":".$signature;
        // 打开和URL之间的连接
        $headers["accept"] = $accept;
        $headers["content-type"] = $content_type;
        $headers["date"] = $date;
        $headers["Authorization"] = $authHeader;
        $headers["Content-Length"] = $length;
        $response = $request->curl($realUrl,$method,$audioData,$headers);
        return $response;
}

$response = sendAsrPost(file_get_contents('./1.wav'), 'wav', 16000,$to_url);
echo '<pre>';
var_dump ($response->getBody());
echo '</pre>';

/*$data = file_get_contents('./resource/1.wav');
$length = strlen($data);
echo '<pre>';
var_dump($data);
echo '</pre>';
var_dump($length);*/

/*$response = $client->getAcsResponse($request);
print_r($response);*/

?>
