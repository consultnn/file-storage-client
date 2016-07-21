<?php

namespace serieznyi\filestorage\providers;

class Image extends AbstractProvider
{
    public $defaultImage;

    public function thumbnail($fileIdentifier, $params = [])
    {
        $options = [];
        $translit = '';
        if (isset($options['translit'])) {
            $translit = $options['translit'];
            unset($options['translit']);
        }

        $src = static::absoluteUrl($fileIdentifier, $params, $translit);

        if (isset($params['w']) && !isset($options['width'])) {
            $options['width'] = $params['w'];
        }

        if (isset($params['h']) && !isset($options['height'])) {
            $options['height'] = $params['h'];
        }

        return self::img($src, $options);
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
            $this->owner->downloadToken . $filePath . $params . $this->owner->downloadToken
        );

        return str_pad($this->internalBaseConvert($hash, 16, 36), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param $hash
     * @param array $params
     * @param string $translit
     * @return null|string
     */
    public function absoluteUrl($hash, array $params = [], $translit = '')
    {
        if (!$hash) {
            return $this->defaultImage;
        }

        $pathInfo = pathinfo($hash);
        $fileName = $pathInfo['filename'];

        if (!empty($params['f'])) {
            $pathInfo['extension'] = $params['f'];
            unset($params['f']);
        }

        ksort($params);

        $encodedParams = $this->encodeParams($params);

        $result = $this->owner->server
            . '/' . $fileName
            . '_' . $this->internalHash($hash, $encodedParams)
            . $encodedParams;

        if ($translit) {
            $result .= '/'.$translit;
        }

        if (!empty($pathInfo['extension'])) {
            $result .='.'.$pathInfo['extension'];
        }

        return $result;
    }

    private static function img($src, $options)
    {
        $attributes = '';

        foreach ($options as $key => $value) {
            $attributes .= sprintf('$s="$s"', $key, $value);
        }

        return '<img src="'. $src. '" ' . $attributes . ' />';
    }
}