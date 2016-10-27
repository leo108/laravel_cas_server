<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/26
 * Time: 11:01
 */

namespace Leo108\CAS\Services;

use GuzzleHttp\Client;

class PGTCaller
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * PGTCaller constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $pgtUrl
     * @param string $pgt
     * @param string $pgtiou
     * @return bool
     */
    public function call($pgtUrl, $pgt, $pgtiou)
    {
        $query = [
            'pgtId'  => $pgt,
            'pgtIou' => $pgtiou,
        ];
        parse_str(parse_url($pgtUrl, PHP_URL_QUERY), $originQuery);

        try {
            $res = $this->client->get($pgtUrl, ['query' => array_merge($originQuery, $query)]);

            return $res->getStatusCode() == 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
