<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/23
 * Time: 16:26
 */

namespace Leo108\CAS\Traits;

use Illuminate\Support\Str;
use SimpleXMLElement;

trait XmlResponse
{
    /**
     * @return SimpleXMLElement
     */
    protected function getRootNode()
    {
        $str = '<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>';

        return simplexml_load_string($str);
    }

    /**
     * @param SimpleXMLElement $xml
     * @param string           $xpath
     */
    protected function removeByXPath(SimpleXMLElement $xml, $xpath)
    {
        $node = $xml->xpath($xpath);
        unset($node[0]->{0});
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
        $str = null;
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
}
