<?php

namespace consultnn\filestorage\client;

class Client
{
    private $guzzle;

    /**
     * @var string
     */
    public $serverName;

    /**
     * @var string
     */
    public $projectName;

    /**
     * @var string
     */
    public $uploadToken;

    /**
     * @var string
     */
    public $downloadToken;

    public function __construct()
    {
        $this->guzzle = new \GuzzleHttp\Client();
    }

    public function getUploadUrl()
    {
        return trim($this->serverName, '/')
        . '/upload'
        . '/' . $this->projectName
        . '/' . $this->uploadToken;
    }

    public function upload($files)
    {
        $files = (array) $files;

        $multipart = [];
        foreach ($files as $file) {
            $multipart[] = [
                'name'     => basename($file),
                'contents' => fopen($file, 'r')
            ];
        }

        $response = $this->guzzle->request(
            'POST',
            $this->getUploadUrl(),
            ['multipart' => $multipart]
        );

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody(), true);
        }
    }

    /**
     * @param array $urls
     * @return array
     */
    public function uploadByUrl($urls)
    {
        $urls = (array) $urls;
        $urls = array_combine($urls, $urls);

        $files = [];
        foreach ($urls as $key => $url) {
            $tempPath = tempnam('/tmp', 'file_storage_');

            try {
                $this->guzzle->request('GET', $url, ['sink' => $tempPath]);
            } catch (\Exception $e) {
                $urls[basename($key)] = false;
                unset($urls[$key]);

                continue;
            }

            $files[] = $tempPath;
            unset($urls[$key]);
        }

        return array_merge(
            $files ? $this->upload($files) : [],
            $urls
        );
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

        $result = $this->serverName
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
