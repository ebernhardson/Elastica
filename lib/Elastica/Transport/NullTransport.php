<?hh // strict
namespace Elastica\Transport;

use Elastica\JSON;
use Elastica\Request;
use Elastica\Response;
use Indexish;

/**
 * Elastica Null Transport object.
 *
 * This is used in case you just need a test transport that doesn't do any connection to an elasticsearch
 * host but still returns a valid response object
 *
 * @author James Boehmer <james.boehmer@jamesboehmer.com>
 */
class NullTransport extends AbstractTransport
{
    /**
     * Null transport.
     *
     * @param \Elastica\Request $request
     * @param array             $params  Hostname, port, path, ...
     *
     * @return Awaitable<\Elastica\Response> Response empty object
     */
    public async function exec(Request $request, Indexish<string, mixed> $params) : Awaitable<Response>
    {
        $response = array(
            'took' => 0,
            'timed_out' => false,
            '_shards' => array(
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
            ),
            'hits' => array(
                'total' => 0,
                'max_score' => null,
                'hits' => array(),
            ),
            'params' => $params,
        );

        return new Response(JSON::stringify($response));
    }
}
