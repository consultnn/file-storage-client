<?php

namespace consultnn\filestorage;

use CURLFile;

class File
{
    private $_providers;
    private $_curl;

    public $server;
    public $projectName;
    public $uploadToken;
    public $downloadToken;

    public function __construct()
    {
        $this->_providers = [];
    }

    public function __call($name, $arguments)
    {
        foreach ($this->_providers as $object) {
            if (method_exists($object, $name)) {
                return call_user_func_array([$object, $name], $arguments);
            }
        }

        throw new \Exception('Method `'.$name.'` not exists');
    }

    public function __get($name)
    {
        foreach ($this->_providers as $object) {
            if (property_exists($object, $name)) {
                return $object->$name;
            }
        }

        throw new \Exception('Property `'.$name.'` not exists');
    }

    public function addProvider($className, $params = [])
    {
        $providerObject = new $className($this);

        foreach ($params as $property => $value) {
            $providerObject->$property = $value;
        }

        $this->_providers[$className] = $providerObject;

        return $this;
    }

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
        $urls = (array) $urls;

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

        if (isset($params['translit'])) {
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

        return str_pad($this->internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }
}