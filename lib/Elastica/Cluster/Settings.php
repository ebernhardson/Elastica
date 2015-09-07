<?hh
namespace Elastica\Cluster;

use Elastica\Client;
use Elastica\Request;
use Elastica\Response;
use Indexish;

/**
 * Cluster settings.
 *
 * @author   Nicolas Ruflin <spam@ruflin.com>
 *
 * @link     http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-update-settings.html
 */
class Settings
{
    /**
     * @var \Elastica\Client Client object
     */
    protected Client $_client;

    /**
     * Creates a cluster object.
     *
     * @param \Elastica\Client $client Connection client object
     */
    public function __construct(Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Returns settings data.
     *
     * @return Awaitable<array> Settings data (persistent and transient)
     */
    public async function get() : Awaitable<Indexish<string, mixed>>
    {
        $response = await $this->request();
        $data = $response->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        return $data;
    }

    /**
     * Returns the current persistent settings of the cluster.
     *
     * If param is set, only specified setting is return.
     *
     * @param string $setting OPTIONAL Setting name to return
     *
     * @return Awaitable<array|string|null> Settings data
     */
    public async function getPersistent(string $setting = '') : Awaitable<mixed>
    {
        $data = await $this->get();
        $settings = $data['persistent'];

        if (!empty($setting)) {
            if (isset(/* UNSAFE_EXPR */ $settings[$setting])) {
                return /* UNSAFE_EXPR */ $settings[$setting];
            } else {
                return;
            }
        }

        return $settings;
    }

    /**
     * Returns the current transient settings of the cluster.
     *
     * If param is set, only specified setting is return.
     *
     * @param string $setting OPTIONAL Setting name to return
     *
     * @return Awaitable<array|string|null> Settings data
     */
    public async function getTransient(string $setting = '') : Awaitable<mixed>
    {
        $data = await $this->get();
        $settings = $data['transient'];

        if (!empty($setting)) {
            if (isset(/* UNSAFE_EXPR */ $settings[$setting])) {
                return /* UNSAFE_EXPR */ $settings[$setting];
            } else {
                if (strpos($setting, '.') !== false) {
                    // convert dot notation to nested arrays
                    $keys = explode('.', $setting);
                    foreach ($keys as $key) {
                        if (isset(/* UNSAFE_EXPR */ $settings[$key])) {
                            $settings = /* UNSAFE_EXPR */ $settings[$key];
                        } else {
                            return;
                        }
                    }

                    return $settings;
                }

                return;
            }
        }

        return $settings;
    }

    /**
     * Sets persistent setting.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function setPersistent(string $key, mixed $value) : Awaitable<Response>
    {
        return $this->set(
            array(
                'persistent' => array(
                    $key => $value,
                ),
            )
        );
    }

    /**
     * Sets transient settings.
     *
     * @param string $key
     * @param string|bool  $value
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function setTransient(string $key, mixed $value) : Awaitable<Response>
    {
        return $this->set(
            array(
                'transient' => array(
                    $key => $value,
                ),
            )
        );
    }

    /**
     * Sets the cluster to read only.
     *
     * Second param can be used to set it persistent
     *
     * @param bool $readOnly
     * @param bool $persistent
     *
     * @return Awaitable<\Elastica\Response> $response
     */
    public function setReadOnly(bool $readOnly = true, bool $persistent = false) : Awaitable<Response>
    {
        $key = 'cluster.blocks.read_only';

        if ($persistent) {
            $response = $this->setPersistent($key, $readOnly);
        } else {
            $response = $this->setTransient($key, $readOnly);
        }

        return $response;
    }

    /**
     * Set settings for cluster.
     *
     * @param array $settings Raw settings (including persistent or transient)
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function set(array $settings) : Awaitable<Response>
    {
        return $this->request($settings, Request::PUT);
    }

    /**
     * Get the client.
     *
     * @return \Elastica\Client
     */
    public function getClient() : Client
    {
        return $this->_client;
    }

    /**
     * Sends settings request.
     *
     * @param array  $data   OPTIONAL Data array
     * @param string $method OPTIONAL Transfer method (default = \Elastica\Request::GET)
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function request(array $data = array(), string $method = Request::GET) : Awaitable<Response>
    {
        $path = '_cluster/settings';

        return $this->getClient()->request($path, $method, $data);
    }
}
