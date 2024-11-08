<?php

class DeepZoomHandler extends ImageHandler
{

    public function canHandle($file)
    {
        return strtolower($file->getExtension()) === 'dzi';
    }

    /**
     * Return a map of transformation parameters that this handler supports.
     */
    public function getParamMap()
    {
        return [
            'img_width' => 'width',
            'dzi_x' => 'x',
            'dzi_y' => 'y',
            'dzi_z' => 'z'
        ];
    }

    /**
     * Validate the parameters for the image.
     */
    public function validateParam($name, $value)
    {
        /*
        switch ($name) {
            case 'img_width':
                return is_int($value) && $value > 0;
            default:
                return false;
        }
        */
        // These are params, like x y and z, passed when creating the image. Accept all of them
        return true;
    }

    /**
     * Create a parameter string from an associative array of parameters.
     */
    public function makeParamString($params)
    {
        return serialize($params);
    }

    /**
     * Parse a parameter string into an associative array of parameters.
     */
    public function parseParamString($string)
    {
        // Check if $string is indeed a string before unserializing
        if (is_string($string)) {
            return unserialize($string);
        }

        // If $string is not a string, return an empty array or handle it as needed
        return [];
    }

    /**
     * Return metadata about the file.
     */
    public function getSizeAndMetadata($state, $path)
    {
        $xml = new SimpleXMLElement(file_get_contents($path));

        // Use XPath to retrieve the Size element with the specified namespace
        $size = $xml->children()->Size;

        // Access the Width and Height attributes
        $width = (string) $size['Width'];
        $height = (string) $size['Height'];

        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Normalizes transformation parameters.
     */
    public function normaliseParams($image, &$params)
    {
        // Ensure width and height parameters are positive integers
        $params['width'] = isset($params['width']) && is_int($params['width']) ? max(1, $params['width']) : 100;
        $params['height'] = isset($params['height']) && is_int($params['height']) ? max(1, $params['height']) : 100;
        return true;
    }

    /**
     * Performs the transformation operation. Typically this would generate a
     * thumbnail or other derivative version, but here we'll render the OpenSeaDragon viewer.
     */
    public function doTransform($file, $dstPath, $dstUrl, $params, $flags = 0)
    {
        //$intended_width = $params['width'];

        // wfDebugLog('DeepZoom', "Run validateParam");
        wfDebugLog('DeepZoom', print_r($params, true));

        return new DeepZoomOutput($file, $this->getContainerId($file), $params);
    }

    private function base64JsSafe($input)
    {
        // Standard base64 encoding
        $base64 = base64_encode($input);

        // Replace '+' with '_', '/' with '_', and remove '=' padding
        $safeBase64 = strtr($base64, '+/', '__');
        $safeBase64 = rtrim($safeBase64, '=');

        // Ensure it doesn’t start with a number by prepending an underscore if necessary
        if (is_numeric($safeBase64[0])) {
            $safeBase64 = '_' . $safeBase64;
        }

        return $safeBase64;
    }

    private function getContainerId($file)
    {
        // Generate randomly for now, since there are collisions with the same file on the same page
        // return 'openseadragon_' . md5($file->getTitle()->getDBkey());

        // Ensure it's base64 safe

        return 'openseadragon_' . $this->base64JsSafe(random_bytes(64));
    }
}

class DeepZoomOutput extends MediaTransformOutput
{
    private $container_id;
    private $params;

    protected $file;
    protected $lang;
    protected $page;
    protected $path;
    protected $storagePath;
    protected $url;
    protected $width;
    protected $height;

    public function __construct($file, $container_id, $params)
    {
        $this->file = $file;
        $this->container_id = $container_id;
        $this->params = $params;

        if ($this->file->exists() && $this->file->getLocalRefPath()) {
            $xml = new SimpleXMLElement(file_get_contents($this->file->getLocalRefPath()));

            // Use XPath to retrieve the Size element with the specified namespace
            $size = $xml->children()->Size;

            $this->width = (int) $size['Width'];
            $this->height = (int) $size['Height'];
        } else {
            $this->width = 0;
            $this->height = 0;
        }

        $this->lang = "en";
        $this->page = "";
        $this->path = $this->file->getPath();
        $this->storagePath = $this->file->getPath();
        $this->url = $this->file->getFullUrl();
    }

    // MediaWiki aggressively converts all links it sees to wiki links
    private function breakUrlForJs($url)
    {
        // Split the URL by the first "://", then join it back with concatenation in JavaScript
        $parts = explode('://', $url, 2);
        if (count($parts) === 2) {
            return '"' . $parts[0] . ':" + "//' . $parts[1] . '"';
        }

        // If there's no "://", return as a quoted string
        return '"' . $url . '"';
    }

    public function toHtml($options = [])
    {
        $dzi_url = $this->breakUrlForJs($this->file->getFullUrl());
        $dzi_info_url = $this->breakUrlForJs($this->file->getTitle()->getFullURL());
        $openseadragon_images_url = $this->breakUrlForJs("https://cdnjs.cloudflare.com/ajax/libs/openseadragon/5.0.0/images/");
        $output = <<<EOF
<div class="mw-file-element" id="$this->container_id" style="width:400px; height:400px; background-color:black;"></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/openseadragon/5.0.0/openseadragon.min.js"></script>
<script>
    let viewer_$this->container_id = OpenSeadragon({
        id: "$this->container_id",
        prefixUrl: $openseadragon_images_url,
        tileSources: $dzi_url
    });
    viewer_$this->container_id.addHandler('canvas-click', function(target, info) {
        window.location.replace($dzi_info_url);
    });
EOF;

        if (isset($this->params['x']) && isset($this->params['y']) && isset($this->params['z'])) {
            // Go to specified location in image on load
            $x = (float) $this->params['x'];
            $y = (float) $this->params['y'];
            $z = (float) $this->params['z'];
            $output .= <<<EOF
    viewer_$this->container_id.addHandler('open', function() {
        viewer_$this->container_id.viewport.zoomTo($z);
        viewer_$this->container_id.viewport.panTo(new OpenSeadragon.Point($x, $y));
    });
EOF;
        }

        $output .= "</script>";

        return $output;
    }

    public function toText()
    {
        return "";
    }

    public function getHtmlMsg()
    {
        return "";
    }

    public function getMsg()
    {
        return "";
    }

    public function isError()
    {
        return false;
    }

    public function getHttpStatusCode()
    {
        return 200;
    }
}
