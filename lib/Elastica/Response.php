<?hh
namespace Elastica;

use Elastica\Exception\JSONParseException;
use Elastica\Exception\NotFoundException;
use Indexish;

/**
 * Elastica Response object.
 *
 * Stores query time, and result array -> is given to result set, returned by ...
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Response
{
    /**
     * Query time.
     *
     * @var float Query time
     */
    protected ?float $_queryTime = null;

    /**
     * Response string (json).
     *
     * @var string Response
     */
    protected string $_responseString = '';

    /**
     * Error.
     *
     * @var bool Error
     */
    protected bool $_error = false;

    /**
     * Transfer info.
     *
     * @var array transfer info
     */
    protected array $_transferInfo = array();

    /**
     * Response.
     *
     * @var array Response
     */
    protected mixed $_response = null;

    /**
     * HTTP response status code.
     *
     * @var int
     */
    protected ?int $_status = null;

    /**
     * Construct.
     *
     * @param string|array $responseString Response string (json)
     * @param int          $responseStatus http status code
     */
    public function __construct(mixed $responseString, ?int $responseStatus = null)
    {
        if ($responseString instanceof Indexish) {
            $this->_response = $responseString;
        } else {
            $this->_responseString = (string) $responseString;
        }
        $this->_status = $responseStatus;
    }

    /**
     * Error message.
     *
     * @return string Error message
     */
    public function getError() : string
    {
        $message = '';
        $response = $this->getData();

        if (isset(/* UNSAFE_EXPR */ $response['error'])) {
            $message = (string) /* UNSAFE_EXPR */ $response['error'];
        }

        return $message;
    }

    /**
     * True if response has error.
     *
     * @return bool True if response has error
     */
    public function hasError() : bool
    {
        $response = $this->getData();

        if (isset(/* UNSAFE_EXPR */ $response['error'])) {
            return true;
        }

        return false;
    }

    /**
     * True if response has failed shards.
     *
     * @return bool True if response has failed shards
     */
    public function hasFailedShards() : bool
    {
        try {
            $shardsStatistics = $this->getShardsStatistics();
        } catch (NotFoundException $e) {
            return false;
        }

        return array_key_exists('failures', $shardsStatistics);
    }

    /**
     * Checks if the query returned ok.
     *
     * @return bool True if ok
     */
    public function isOk() : bool
    {
        $data = $this->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }

        // Bulk insert checks. Check every item
        if (isset($data['status'])) {
            if ($data['status'] >= 200 && $data['status'] <= 300) {
                return true;
            }

            return false;
        }

        if (isset($data['items'])) {
            if (isset($data['errors']) && true === $data['errors']) {
                return false;
            }

            foreach ($data['items'] as $item) {
                if (isset($item['index']['ok']) && false == $item['index']['ok']) {
                    return false;
                } elseif (isset($item['index']['status']) && ($item['index']['status'] < 200 || $item['index']['status'] >= 300)) {
                    return false;
                }
            }

            return true;
        }

        if ($this->_status !== null && ($this->_status >= 200 && $this->_status <= 300)) {
            // http status is ok
            return true;
        }

        return (isset($data['ok']) && $data['ok']);
    }

    /**
     * @return int
     */
    public function getStatus() : ?int
    {
        return $this->_status;
    }

    /**
     * Response data array.
     *
     * @return array|string Response data array
     */
    public function getData() : mixed
    {
        if ($this->_response == null) {
            if ($this->_responseString === null) {
                $this->_error = true;
                $decoded = null;
            } else {
                try {
                    $decoded = JSON::parse($this->_responseString);
                } catch (JSONParseException $e) {
                    // leave response as is if parse fails
                    $this->_error = true;
                    $decoded = null;
                }
            }

            if ($decoded === '') {
                $decoded = array();
            } elseif (is_string($decoded)) {
                $decoded  = array('message' => $decoded);
            } elseif ($decoded === null) {
                $decoded = $this->_responseString;
            }

            $this->_response = $decoded;
        }

        return $this->_response;
    }

    /**
     * Gets the transfer information.
     *
     * @return array Information about the curl request.
     */
    public function getTransferInfo() : array
    {
        return $this->_transferInfo;
    }

    /**
     * Sets the transfer info of the curl request. This function is called
     * from the \Elastica\Client::_callService .
     *
     * @param array $transferInfo The curl transfer information.
     *
     * @return $this
     */
    public function setTransferInfo(array $transferInfo) : this
    {
        $this->_transferInfo = $transferInfo;

        return $this;
    }

    /**
     * Returns query execution time.
     *
     * @return float Query time
     */
    public function getQueryTime() : float
    {
        return (float) $this->_queryTime;
    }

    /**
     * Sets the query time.
     *
     * @param float $queryTime Query time
     *
     * @return $this
     */
    public function setQueryTime($queryTime) : this
    {
        $this->_queryTime = $queryTime;

        return $this;
    }

    /**
     * Time request took.
     *
     * @throws \Elastica\Exception\NotFoundException
     *
     * @return int Time request took
     */
    public function getEngineTime() : int
    {
        $data = $this->getData();

        if (!isset(/* UNSAFE_EXPR */ $data['took'])) {
            throw new NotFoundException('Unable to find the field [took]from the response');
        }

        return (int) /* UNSAFE_EXPR */ $data['took'];
    }

    /**
     * Get the _shard statistics for the response.
     *
     * @throws \Elastica\Exception\NotFoundException
     *
     * @return array
     */
    public function getShardsStatistics() : array
    {
        $data = $this->getData();

        if (!isset(/* UNSAFE_EXPR */ $data['_shards'])) {
            throw new NotFoundException('Unable to find the field [_shards] from the response');
        }

        return /* UNSAFE_EXPR */ $data['_shards'];
    }

    /**
     * Get the _scroll value for the response.
     *
     * @throws \Elastica\Exception\NotFoundException
     *
     * @return string
     */
    public function getScrollId() : string
    {
        $data = $this->getData();

        if (!isset(/* UNSAFE_EXPR */ $data['_scroll_id'])) {
            throw new NotFoundException('Unable to find the field [_scroll_id] from the response');
        }

        return /* UNSAFE_EXPR */ $data['_scroll_id'];
    }
}
