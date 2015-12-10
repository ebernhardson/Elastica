<?php
namespace Elastica\Cluster;

use Elastica\Client;
use Elastica\Request;

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
    protected $_client = null;

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
     * @return array Settings data (persistent and transient)
     */
    public function get()
    {
        return $this->request()->getData();
    }

    /**
     * Returns the current persistent settings of the cluster.
     *
     * If param is set, only specified setting is return.
     *
     * @param string $setting OPTIONAL Setting name to return
     *
     * @return array|string|null Settings data
     */
    public function getPersistent($setting = '')
    {
        $data = $this->get();
        $settings = $data['persistent'];

        if (!empty($setting)) {
            if (isset($settings[$setting])) {
                return $settings[$setting];
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
     * @return array|string|null Settings data
     */
    public function getTransient($setting = '')
    {
        $data = $this->get();
        $settings = $data['transient'];

        if (!empty($setting)) {
            if (isset($settings[$setting])) {
                return $settings[$setting];
            } else {
                if (strpos($setting, '.') !== false) {
                    // convert dot notation to nested arrays
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

        return $settings;
    }

    /**
     * Sets persistent setting.
     *
     * @param string $key
     * @param string $value
     * @param array  $query OPTIONAL Query string parameters to send with setting
     *
     * @return \Elastica\Response
     */
    public function setPersistent($key, $value, array $query = array())
    {
        return $this->set(
            array(
                'persistent' => array(
                    $key => $value,
                ),
            ),
            $query
        );
    }

    /**
     * Sets transient settings.
     *
     * @param string $key
     * @param string $value
     * @param array  $query OPTIONAL Query string parameters to send with setting
     *
     * @return \Elastica\Response
     */
    public function setTransient($key, $value, array $query = array())
    {
        return $this->set(
            array(
                'transient' => array(
                    $key => $value,
                ),
            ),
            $query
        );
    }

    /**
     * Sets the cluster to read only.
     *
     * Second param can be used to set it persistent
     *
     * @param bool  $readOnly
     * @param bool  $persistent
     * @param array $query      OPTIONAL Query string parameters to send with setting
     *
     * @return \Elastica\Response $response
     */
    public function setReadOnly($readOnly = true, $persistent = false, array $query = array())
    {
        $key = 'cluster.blocks.read_only';

        if ($persistent) {
            $response = $this->setPersistent($key, $readOnly, $query);
        } else {
            $response = $this->setTransient($key, $readOnly, $query);
        }

        return $response;
    }

    /**
     * Set settings for cluster.
     *
     * @param array $settings Raw settings (including persistent or transient)
     * @param array $query    OPTIONAL Query string parameters to send with setting
     *
     * @return \Elastica\Response
     */
    public function set(array $settings, array $query = array())
    {
        return $this->request($settings, Request::PUT, $query);
    }

    /**
     * Get the client.
     *
     * @return \Elastica\Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Sends settings request.
     *
     * @param array  $data   OPTIONAL Data array
     * @param string $method OPTIONAL Transfer method (default = \Elastica\Request::GET)
     * @param array  $query  OPTIONAL Query params
     *
     * @return \Elastica\Response Response object
     */
    public function request(array $data = array(), $method = Request::GET, array $query = array())
    {
        $path = '_cluster/settings';

        return $this->getClient()->request($path, $method, $data, $query);
    }
}
