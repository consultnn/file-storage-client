<?php

namespace consultnn\filestorage\client;

use GuzzleHttp\Client as GuzzleClient;
use League\Glide\Urls\UrlBuilderFactory;

class Client
{
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
    public $downloadSignKey;

    /**
     * @var GuzzleClient
     */
    private $guzzle;

    /**
     * Client constructor.
     * @param null|GuzzleClient $guzzle
     * @param string $projectName
     * @param string $uploadToken
     * @param string $downloadSignKey
     * @param string $serverName
     */
    public function __construct(
        string $projectName,
        string $uploadToken,
        string $downloadSignKey,
        string $serverName,
        GuzzleClient $guzzle = null
    )
    {
        if (!$guzzle) {
            $guzzle = new GuzzleClient(['
                timeout' => 15,
                'base_uri' => $serverName,
            ]);
        }

        $this->uploadToken = $uploadToken;
        $this->downloadSignKey = $downloadSignKey;
        $this->serverName = $serverName;
        $this->projectName = $projectName;

        $this->guzzle = $guzzle;
    }

    /**
     * @param $files
     * @return bool|mixed
     */
    public function upload($files)
    {
        $files = (array) $files;

        $multipart = [];
        foreach ($files as $file) {
            if (filter_var($file, FILTER_VALIDATE_URL) !== FALSE) {
                $file = $this->getFileFromUrl($file);
            }

            $multipart[] = [
                'name'     => basename($file),
                'contents' => fopen($file, 'r')
            ];
        }

        $response = $this->guzzle->post(
            'upload',
            [
                'multipart' => $multipart,
                'headers' => $this->getHeaders(),
            ]
        );

        if ($response->getStatusCode() === 200) {
            return json_decode($response->getBody(), true);
        }

        return false;
    }

    /**
     * Store image from url to file
     *
     * @param $url
     * @return string
     */
    private function getFileFromUrl($url)
    {
        $tempPath = tempnam('/tmp', 'file_storage_');

        $this->guzzle->request('GET', $url, ['sink' => $tempPath]);

        return $tempPath;
    }

    /**
     * @param $hash
     * @param array $params
     * @return null|string
     */
    public function makeUrl($hash, array $params = [])
    {
        if (!$hash) {
            return null;
        }

        $urlBuilder = UrlBuilderFactory::create('', $this->downloadSignKey);

        return $this->serverName . $urlBuilder->getUrl($hash, $params);
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        return [
            'X-Project' => $this->projectName,
            'X-Token' => $this->uploadToken,
        ];
    }
}
