<?php

namespace consultnn\filestorage\client;

use CURLFile;

class File
{
    private $_curl;

    public $server;
    public $projectName;
    public $uploadToken;
    public $downloadToken;

    private function send()
    {
        $curl = $this->getCurl();

        $data = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl), curl_errno($curl));
        }

        return json_decode($data, true);
    }

    private function makeUploadUrl()
    {
        return trim($this->server, '/')
            . '/upload'
            . '/' . $this->projectName
            . '/' . $this->uploadToken;
    }

    protected function getCurl()
    {
        if ($this->_curl) {
            return $this->_curl;
        }

        $this->_curl = curl_init();

        curl_setopt_array(
            $this->_curl,
            [
                CURLOPT_TIMEOUT_MS => 5000,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_POST => true,
                CURLOPT_USERAGENT => 'PHP ' . __CLASS__,
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_URL => $this->makeUploadUrl(),
            ]
        );

        return $this->_curl;
    }

    public function upload($filePath)
    {
        $filePath = (array) $filePath;
        $curl = $this->getCurl();
        $data = [];

        foreach ($filePath as $key => $file) {
            $data[basename($file)] = new CurlFile( $file );
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        return $this->send();
    }

    public function uploadByUrl($urls)
    {
        $urls = [
            'urls' => (array) $urls
        ];

        $curl = $this->getCurl();

        curl_setopt(
            $curl,
            CURLOPT_POSTFIELDS,
            http_build_query($urls)
        );

        return $this->send();
    }

    /**
     * @param $hash
     * @param array $params
     * @return null|string
     */
    public function get($hash, array $params = [])
    {
        if (!$hash) {
            return null;
        }

        $pathInfo = pathinfo($hash);
        $fileName = $pathInfo['filename'];

        if (!empty($params['f'])) {
            $pathInfo['extension'] = $params['f'];
            unset($params['f']);
        }

        ksort($params);

        $encodedParams = $this->encodeParams($params);

        $result = $this->server
            . '/' . $fileName
            . '_' . $this->internalHash($hash, $encodedParams)
            . $encodedParams;

        if (array_key_exists('translit', $params)) {
            $result .= '/' . $params['translit'];
        }

        if (!empty($pathInfo['extension'])) {
            $result .='.'.$pathInfo['extension'];
        }

        return $result;
    }

    /**
     * @param array $params
     * @return string
     */
    private function encodeParams(array $params)
    {
        $result = '';
        foreach ($params as $key => $value) {
            $result .= '_'.$key.'-'.$value;
        }
        return $result;
    }

    private static function internalBaseConvert($number, $fromBase, $toBase)
    {
        return gmp_strval(gmp_init($number, $fromBase), $toBase);
    }

    /**
     * @param $filePath
     * @param $params
     * @return string
     */
    private function internalHash($filePath, $params)
    {
        $hash = hash(
            'crc32',
            $this->downloadToken . $filePath . $params . $this->downloadToken
        );

        return str_pad(self::internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }
}
