<?hh
namespace Elastica\Transport;

use Elastica\Exception\Connection\HttpException;
use Elastica\Exception\PartialShardFailureException;
use Elastica\Exception\ResponseException;
use Elastica\JSON;
use Elastica\Request;
use Elastica\Response;
use Indexish;

/**
 * Elastica Http Transport object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Http extends AbstractTransport
{
    /**
     * Http scheme.
     *
     * @var string Http scheme
     */
    protected string $_scheme = 'http';

    /**
     * Curl resource to reuse.
     *
     * @var resource Curl resource to reuse
     */
    protected static $_curlConnection = null;

    /**
     * Makes calls to the elasticsearch server.
     *
     * All calls that are made to the server are done through this function
     *
     * @param \Elastica\Request $request
     * @param array             $params  Host, Port, ...
     *
     * @throws \Elastica\Exception\ConnectionException
     * @throws \Elastica\Exception\ResponseException
     * @throws \Elastica\Exception\Connection\HttpException
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public async function exec(Request $request, Indexish<string, mixed> $params) : Awaitable<Response>
    {
        $connection = $this->getConnection();

        $conn = $this->_getConnection($connection->isPersistent());

        // If url is set, url is taken. Otherwise port, host and path
        $url = $connection->hasConfig('url') ? (string) $connection->getConfig('url') : '';

        if (!empty($url)) {
            $baseUri = $url;
        } else {
            $baseUri = $this->_scheme.'://'.$connection->getHost().':'.$connection->getPort().'/'.$connection->getPath();
        }

        $baseUri .= $request->getPath();

        $query = $request->getQuery();

        if (!empty($query)) {
            $baseUri .= '?'.http_build_query($query);
        }

        curl_setopt($conn, CURLOPT_URL, $baseUri);
        curl_setopt($conn, CURLOPT_TIMEOUT, $connection->getTimeout());
        curl_setopt($conn, CURLOPT_FORBID_REUSE, 0);

        /* @see Connection::setConnectTimeout() */
        $connectTimeout = $connection->getConnectTimeout();
        if ($connectTimeout > 0) {
            curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        }

        $proxy = $connection->getProxy();

        // See: https://github.com/facebook/hhvm/issues/4875
        if (is_null($proxy) && defined('HHVM_VERSION')) {
            $proxy = getenv('http_proxy') ?: null;
        }

        if (!is_null($proxy)) {
            curl_setopt($conn, CURLOPT_PROXY, $proxy);
        }

        $this->_setupCurl($conn);

        $headersConfig = $connection->hasConfig('headers') ? $connection->getConfig('headers') : array();

        if (!empty($headersConfig)) {
            $headers = array();
            while (list($header, $headerValue) = each($headersConfig)) {
                array_push($headers, $header.': '.$headerValue);
            }

            curl_setopt($conn, CURLOPT_HTTPHEADER, $headers);
        }

        // TODO: REFACTOR
        $data = $request->getData();
        $httpMethod = $request->getMethod();

        if (!empty($data) || '0' === $data) {
            if ($this->hasParam('postWithRequestBody') && $this->getParam('postWithRequestBody') == true) {
                $httpMethod = Request::POST;
            }

            if ($data instanceof Indexish) {
                $content = JSON::stringify($data, 'JSON_ELASTICSEARCH');
            } else {
                $content = $data;
            }

            // Escaping of / not necessary. Causes problems in base64 encoding of files
            $content = str_replace('\/', '/', $content);

            curl_setopt($conn, CURLOPT_POSTFIELDS, $content);
        } else {
            curl_setopt($conn, CURLOPT_POSTFIELDS, '');
        }

        curl_setopt($conn, CURLOPT_NOBODY, $httpMethod == 'HEAD');
        curl_setopt($conn, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);

        $start = microtime(true);

        $responsePair = await $this->curl_exec($conn);
		$errorNumber = $responsePair[0];
		$responseString = $responsePair[1];
		//$responseString = curl_exec($conn);

        $end = microtime(true);

        // Checks if error exists
        //$errorNumber = curl_errno($conn);

        $response = new Response($responseString, curl_getinfo($conn, CURLINFO_HTTP_CODE));
        $response->setQueryTime($end - $start);
        $response->setTransferInfo(curl_getinfo($conn));

        if ($response->hasError()) {
            throw new ResponseException($request, $response);
        }

        if ($response->hasFailedShards()) {
            throw new PartialShardFailureException($request, $response);
        }

        if ($errorNumber > 0) {
            throw new HttpException((string) $errorNumber, $request, $response);
        }

        return $response;
    }

    /**
     * Called to add additional curl params.
     *
     * @param resource $curlConnection Curl connection
     */
    protected function _setupCurl(resource $curlConnection) : void
    {
        if ($this->getConnection()->hasConfig('curl')) {
            $opts = $this->getConnection()->getConfig('curl');
            if ($opts instanceof Indexish) {
                foreach ($opts as $key => $param) {
                    curl_setopt($curlConnection, $key, $param);
                }
            }
        }
    }

    /**
     * Return Curl resource.
     *
     * @param bool $persistent False if not persistent connection
     *
     * @return resource Connection resource
     */
    protected function _getConnection(bool $persistent = true) : resource
    {
        if (!$persistent || !self::$_curlConnection) {
            self::$_curlConnection = curl_init();
        }

        return self::$_curlConnection;
	}

	protected async function curl_exec(mixed $urlOrHandle): Awaitable<Pair<int, string>> {
	  if (is_string($urlOrHandle)) {
	    $ch = curl_init($urlOrHandle);
	  } else if (is_resource($urlOrHandle) &&
	             (get_resource_type($urlOrHandle) == "curl")) {
	    $ch = $urlOrHandle;
	  } else {
	    throw new Exception(__FUNCTION__." expects string of cURL handle");
	  }
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	  $mh = curl_multi_init();
	  curl_multi_add_handle($mh, $ch);
	  $sleep_ms = 10;
	  do {
	    $active = 1;
	    do {
	      $status = curl_multi_exec($mh, $active);
	    } while ($status == CURLM_CALL_MULTI_PERFORM);
	    if (!$active) break;
	    $select = await curl_multi_await($mh);
	    /* If cURL is built without ares support, DNS queries don't have a socket
	     * to wait on, so curl_multi_await() (and curl_select() in PHP5) will return
	     * -1, and polling is required.
	     */
	    if ($select == -1) {
	      await SleepWaitHandle::create($sleep_ms * 1000);
	      if ($sleep_ms < 1000) {
	        $sleep_ms *= 2;
	      }
	    } else {
	      $sleep_ms = 10;
	    }
	  } while ($status === CURLM_OK);
	  $content = (string)curl_multi_getcontent($ch);
      $info = curl_multi_info_read($mh);
	  curl_multi_remove_handle($mh, $ch);
	  curl_multi_close($mh);
	  return Pair {$info['result'], $content};
	}
}

