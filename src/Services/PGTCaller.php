<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/10/26
 * Time: 11:01
 */

namespace Leo108\Cas\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PGTCaller
{
    protected Client $client;
    protected CasConfig $casConfig;

    public function __construct(Client $client, CasConfig $casConfig)
    {
        $this->client = $client;
        $this->casConfig = $casConfig;
    }

    /**
     * @param  string  $pgtUrl
     * @param  string  $pgt
     * @param  string  $pgtiou
     * @return bool
     */
    public function call(string $pgtUrl, string $pgt, string $pgtiou): bool
    {
        /** @var string $originQueryStr */
        $originQueryStr = \Safe\parse_url($pgtUrl, PHP_URL_QUERY);
        parse_str($originQueryStr, $originQuery);

        try {
            $option = [
                'query' => array_merge($originQuery, [
                    'pgtId' => $pgt,
                    'pgtIou' => $pgtiou,
                ]),
                'verify' => $this->casConfig->verify_ssl,
            ];
            $res = $this->client->get($pgtUrl, $option);

            return $res->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::warning('call pgt url failed, msg:'.$e->getMessage());

            return false;
        }
    }
}
