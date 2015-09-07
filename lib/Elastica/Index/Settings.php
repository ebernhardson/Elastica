<?hh
namespace Elastica\Index;

use Elastica\Exception\NotFoundException;
use Elastica\Exception\ResponseException;
use Elastica\Index as BaseIndex;
use Elastica\Request;
use Elastica\Response;

/**
 * Elastica index settings object.
 *
 * All settings listed in the update settings API (http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html)
 * can be changed on a running indices. To make changes like the merge policy (http://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-merge.html)
 * the index has to be closed first and reopened after the call
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-merge.html
 */
class Settings
{
    /**
     * Response.
     *
     * @var \Elastica\Response Response object
     */
    protected ?Response $_response;

    /**
     * Stats info.
     *
     * @var array Stats info
     */
    protected array $_data = array();

    /**
     * Index.
     *
     * @var \Elastica\Index Index object
     */
    protected BaseIndex $_index;

    const DEFAULT_REFRESH_INTERVAL = '1s';

    /**
     * Construct.
     *
     * @param \Elastica\Index $index Index object
     */
    public function __construct(BaseIndex $index)
    {
        $this->_index = $index;
    }

    /**
     * Returns the current settings of the index.
     *
     * If param is set, only specified setting is return.
     * 'index.' is added in front of $setting.
     *
     * @param string $setting OPTIONAL Setting name to return
     *
     * @return Awaitable<array|string|null> Settings data
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
     */
    public async function get(string $setting) : Awaitable<mixed>
    {
        $request = await $this->request();
        $requestData = $request->getData();
        $data = reset($requestData);

        if (empty($data['settings']) || empty($data['settings']['index'])) {
            // should not append, the request should throw a ResponseException
            throw new NotFoundException('Index '.$this->getIndex()->getName().' not found');
        }
        $settings = $data['settings']['index'];

        if (isset($settings[$setting])) {
            return $settings[$setting];
        } else {
            if (strpos($setting, '.') !== false) {
                // translate old dot-notation settings to nested arrays
                $keys = explode('.', $setting);
                foreach ($keys as $key) {
                    if (isset($settings[$key])) {
                        $settings = $settings[$key];
                    } else {
                        return;
                    }
                }

                return $settings;
            }

            return;
        }
    }

    public async function getAll() : Awaitable<array>
    {
        $request = await $this->request();
        $requestData = $request->getData();
        $data = reset($requestData);

        if (empty($data['settings']) || empty($data['settings']['index'])) {
            // should not append, the request should throw a ResponseException
            throw new NotFoundException('Index '.$this->getIndex()->getName().' not found');
        }
        return $data['settings']['index'];
    }

    /**
     * Sets the number of replicas.
     *
     * @param int $replicas Number of replicas
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function setNumberOfReplicas(int $replicas) : Awaitable<Response>
    {
        $data = array('number_of_replicas' => $replicas);

        return $this->set($data);
    }

    /**
     * Sets the index to read only.
     *
     * @param bool $readOnly (default = true)
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function setReadOnly(bool $readOnly = true) : Awaitable<Response>
    {
        return $this->set(array('blocks.write' => $readOnly));
    }

    /**
     * getReadOnly.
     *
     * @return Awaitable<bool>
     */
    public async function getReadOnly() : Awaitable<bool>
    {
        $write = await $this->get('blocks.write');
        return $write === 'true'; // ES returns a string for this setting
    }

    /**
     * @return Awaitable<bool>
     */
    public async function getBlocksRead() : Awaitable<bool>
    {
        $res = await $this->get('blocks.read');
        return (bool) $res;
    }

    /**
     * @param bool $state OPTIONAL (default = true)
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function setBlocksRead(bool $state = true) : Awaitable<Response>
    {
        $state = $state ? 1 : 0;

        return $this->set(array('blocks.read' => $state));
    }

    /**
     * @return Awaitable<bool>
     */
    public async function getBlocksWrite() : Awaitable<bool>
    {
        $res = await $this->get('blocks.write');
        return (bool) $res;
    }

    /**
     * @param bool $state OPTIONAL (default = true)
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function setBlocksWrite(bool $state = true) : Awaitable<Response>
    {
        $state = $state ? 1 : 0;

        return $this->set(array('blocks.write' => $state));
    }

    /**
     * @return Awaitable<bool>
     */
    public async function getBlocksMetadata() : Awaitable<bool>
    {
        // TODO will have to be replace by block.metadata.write once https://github.com/elasticsearch/elasticsearch/pull/9203 has been fixed
        // the try/catch will have to be remove too
        try {
            $res = await $this->get('blocks.metadata');
            return (bool) $res;
        } catch (ResponseException $e) {
            if (strpos($e->getMessage(), 'ClusterBlockException') !== false) {
                // hacky way to test if the metadata is blocked since bug 9203 is not fixed
                return true;
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param bool $state OPTIONAL (default = true)
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function setBlocksMetadata(bool $state = true) : Awaitable<Response>
    {
        // TODO will have to be replace by block.metadata.write once https://github.com/elasticsearch/elasticsearch/pull/9203 has been fixed
        $state = $state ? 1 : 0;

        return $this->set(array('blocks.metadata' => $state));
    }

    /**
     * Sets the index refresh interval.
     *
     * Value can be for example 3s for 3 seconds or
     * 5m for 5 minutes. -1 refreshing is disabled.
     *
     * @param string $interval Time interval in elasticsearch format.
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function setRefreshInterval(string $interval) : Awaitable<Response>
    {
        return $this->set(array('refresh_interval' => $interval));
    }

    /**
     * Returns the refresh interval.
     *
     * If no interval is set, the default interval is returned
     *
     * @return Awaitable<string> Refresh interval
     */
    public async function getRefreshInterval() : Awaitable<string>
    {
        $interval = await $this->get('refresh_interval');

        if (!is_string($interval) || $interval === '') {
            $interval = self::DEFAULT_REFRESH_INTERVAL;
        }

        return $interval;
    }

    /**
     * Return merge policy.
     *
     * @return Awaitable<string> Merge policy type
     */
    public async function getMergePolicyType() : Awaitable<string>
    {
        $result = await $this->get('merge.policy.type');
        return (string) $result;
    }

    /**
     * Sets merge policy.
     *
     * @param string $type Merge policy type
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-merge.html
     */
    public async function setMergePolicyType(string $type) : Awaitable<Response>
    {
        await $this->getIndex()->close();
        $response = await $this->set(array('merge.policy.type' => $type));
        await $this->getIndex()->open();

        return $response;
    }

    /**
     * Sets the specific merge policies.
     *
     * To have this changes made the index has to be closed and reopened
     *
     * @param string $key   Merge policy key (for ex. expunge_deletes_allowed)
     * @param string $value
     *
     * @return Awaitable<\Elastica\Response>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-merge.html
     */
    public async function setMergePolicy(string $key, string $value) : Awaitable<Response>
    {
        await $this->getIndex()->close();
        $response = await $this->set(array('merge.policy.'.$key => $value));
        await $this->getIndex()->open();

        return $response;
    }

    /**
     * Returns the specific merge policy value.
     *
     * @param string $key Merge policy key (for ex. expunge_deletes_allowed)
     *
     * @return Awaitable<?string> Refresh interval
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules-merge.html
     */
    public async function getMergePolicy(string $key) : Awaitable<?string>
    {
        $settings = await $this->getAll();
        if (isset($settings['merge']['policy'][$key])) {
            return $settings['merge']['policy'][$key];
        }

        return null;
    }

    /**
     * Can be used to set/update settings.
     *
     * @param array $data Arguments
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function set(array $data) : Awaitable<Response>
    {
        return $this->request($data, Request::PUT);
    }

    /**
     * Returns the index object.
     *
     * @return \Elastica\Index Index object
     */
    public function getIndex() : BaseIndex
    {
        return $this->_index;
    }

    /**
     * Updates the given settings for the index.
     *
     * With elasticsearch 0.16 the following settings are supported
     * - index.term_index_interval
     * - index.term_index_divisor
     * - index.translog.flush_threshold_ops
     * - index.translog.flush_threshold_size
     * - index.translog.flush_threshold_period
     * - index.refresh_interval
     * - index.merge.policy
     * - index.auto_expand_replicas
     *
     * @param array  $data   OPTIONAL Data array
     * @param string $method OPTIONAL Transfer method (default = \Elastica\Request::GET)
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function request(array $data = array(), string $method = Request::GET) : Awaitable<Response>
    {
        $path = '_settings';

        if (!empty($data)) {
            $data = array('index' => $data);
        }

        return $this->getIndex()->request($path, $method, $data);
    }
}
