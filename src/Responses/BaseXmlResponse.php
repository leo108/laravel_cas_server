<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/25
 * Time: 15:10
 */

namespace Leo108\Cas\Responses;

use Illuminate\Support\Str;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;

class BaseXmlResponse
{
    protected SimpleXMLElement $node;

    public function __construct()
    {
        $this->node = $this->getRootNode();
    }

    protected function getRootNode(): SimpleXMLElement
    {
        return \Safe\simplexml_load_string('<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas"/>');
    }

    protected function removeByXPath(SimpleXMLElement $xml, string $xpath): void
    {
        $nodes = $xml->xpath($xpath);

        if ($nodes === null || $nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            $dom = dom_import_simplexml($node);

            if ($dom->parentNode !== null) {
                $dom->parentNode->removeChild($dom);
            }
        }
    }

    /**
     * remove the first line of xml string
     *
     * @param  string  $str
     * @return string
     */
    protected function removeXmlFirstLine(string $str): string
    {
        $first = '<?xml version="1.0"?>';

        if (Str::startsWith($str, $first)) {
            return trim(substr($str, strlen($first)));
        }

        return $str;
    }

    protected function stringify(mixed $value): string
    {
        if (is_string($value)) {
            $str = $value;
        } else {
            $str = \Safe\json_encode($value);
        }

        return $str;
    }

    public function toResponse(): Response
    {
        $xml = $this->node->asXML();

        if ($xml === false) {
            throw new \RuntimeException('Failed to generate xml');
        }

        $content = $this->removeXmlFirstLine($xml);

        return new Response($content, 200, ['Content-Type' => 'application/xml']);
    }
}
