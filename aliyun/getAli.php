<?php

require_once ('HttpHelper.php');

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

class AsrApi
{
    /*
    * AccessKeyId
    * @var string
    */
    protected $accessKeyId = "LTAI1znjajDhdNui";

    /*
    * AccessKeySecret
    * @var string
    */
    protected $accessKeySecret = "fWwy6aNhahSrlmHbXBLAw8QrnGWKjj";

    protected $method = "POST";

    protected $accept = "application/json";

    protected $to_url = 'http://nlsapi.aliyun.com/recognize?model=chat';

    public function encode_body($body)
    {
        return base64_encode(md5(base64_encode(md5($body,true)),true));
    }

    public function signature ($source, $AccessKeySecret)
    {
        $signer = new ShaHmac1Signer();
        return $signer->signString($source, $AccessKeySecret);
    }

    public function getAsrResResponse($audioData, $audioFormat, $sampleRate)
    {
        $result = "";
        $request = new HttpHelper();
        $realUrl = $this->to_url;
        $method = $this->method;
        $accept = $this->accept;
        $content_type = "audio/".$audioFormat.";samplerate=".$sampleRate;
        $length = strlen($audioData);
        $date = date("D, d M Y H:m:s e",time());
        // 1.对body做MD5+BASE64加密
        $bodyMd5 = $this->encode_body($audioData);
        $stringToSign = $method."\n".$accept."\n".$bodyMd5."\n".$content_type."\n".$date ;
        // 2.计算 HMAC-SHA1
        $signature = $this->signature($stringToSign, $this->accessKeySecret);
        // $sig = new ShaHmac1Signer();
        // $signature = $sig->signString($stringToSign, AccessKeySecret);
        // 3.得到 authorization header
        $authHeader = "Dataplus ".$this->accessKeyId.":".$signature;
        // 打开和URL之间的连接
        $headers["accept"] = $accept;
        $headers["content-type"] = $content_type;
        $headers["date"] = $date;
        $headers["Authorization"] = $authHeader;
        $headers["Content-Length"] = $length;
        $response = $request->curl($realUrl,$method,$audioData,$headers);
        return $response;
    }
}
?>