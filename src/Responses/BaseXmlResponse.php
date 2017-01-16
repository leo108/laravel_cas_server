<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 15:10
 */

namespace Leo108\CAS\Responses;

use Illuminate\Support\Str;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;

class BaseXmlResponse
{
    /**
     * @var SimpleXMLElement
     */
    protected $node;

    /**
     * BaseXmlResponse constructor.
     */
    public function __construct()
    {
        $this->node = $this->getRootNode();
    }

    /**
     * @return SimpleXMLElement
     */
    protected function getRootNode()
    {
        return simplexml_load_string('<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>');
    }

    /**
     * @param SimpleXMLElement $xml
     * @param string           $xpath
     */
    protected function removeByXPath(SimpleXMLElement $xml, $xpath)
    {
        $nodes = $xml->xpath($xpath);
        foreach ($nodes as $node) {
            $dom = dom_import_simplexml($node);
            $dom->parentNode->removeChild($dom);
        }
    }

    /**
     * remove the first line of xml string
     * @param string $str
     * @return string
     */
    protected function removeXmlFirstLine($str)
    {
        $first = '<?xml version="1.0"?>';
        if (Str::startsWith($str, $first)) {
            return trim(substr($str, strlen($first)));
        }

        return $str;
    }

    /**
     * @param mixed $value
     * @return false|string
     */
    protected function stringify($value)
    {
        if (is_string($value)) {
            $str = $value;
        } else if (is_object($value) && method_exists($value, '__toString')) {
            $str = $value->__toString();
        } else if ($value instanceof \Serializable) {
            $str = serialize($value);
        } else {
            //array or object that doesn't have __toString method
            //json_encode will return false if encode failed
            $str = json_encode($value);
        }

        return $str;
    }

    /**
     * @return Response
     */
    public function toResponse()
    {
        $content = $this->removeXmlFirstLine($this->node->asXML());

        return new Response($content, 200, ['Content-Type' => 'application/xml']);
    }
}
