<?hh
namespace Elastica;

use Elastica\Exception\NotFoundException;
use Elastica\Exception\ResponseException;
use Indexish;

/**
 * Class Snapshot.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-snapshots.html
 */
class Snapshot
{
    /**
     * @var Client
     */
    protected Client $_client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Register a snapshot repository.
     *
     * @param string $name     the name of the repository
     * @param string $type     the repository type ("fs" for file system)
     * @param array  $settings Additional repository settings. If type "fs" is used, the "location" setting must be provided.
     *
     * @return Awaitable<Response>
     */
    public function registerRepository(string $name, string $type, array $settings = array()) : Awaitable<Response>
    {
        $data = array(
            'type' => $type,
            'settings' => $settings,
        );

        return $this->request($name, Request::PUT, $data);
    }

    /**
     * Retrieve a repository record by name.
     *
     * @param string $name the name of the desired repository
     *
     * @throws Exception\ResponseException
     * @throws Exception\NotFoundException
     *
     * @return Awaitable<array>
     */
    public async function getRepository(string $name) : Awaitable<array>
    {
        try {
            $response = await $this->request($name);
        } catch (ResponseException $e) {
            if ($e->getResponse()->getStatus() == 404) {
                throw new NotFoundException("Repository '".$name."' does not exist.");
            }
            throw $e;
        }
        $data = $response->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        return $data[$name];
    }

    /**
     * Retrieve all repository records.
     *
     * @return Awaitable<array>
     */
    public async function getAllRepositories() : Awaitable<Indexish<string, mixed>>
    {
        $response = await $this->request('_all');
        $data = $response->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        return $data;
    }

    /**
     * Create a new snapshot.
     *
     * @param string $repository        the name of the repository in which this snapshot should be stored
     * @param string $name              the name of this snapshot
     * @param array  $options           optional settings for this snapshot
     * @param bool   $waitForCompletion if true, the request will not return until the snapshot operation is complete
     *
     * @return Awaitable<Response>
     */
    public function createSnapshot(string $repository, string $name, array $options = array(), bool $waitForCompletion = false) : Awaitable<Response>
    {
        return $this->request($repository.'/'.$name, Request::PUT, $options, array('wait_for_completion' => $waitForCompletion));
    }

    /**
     * Retrieve data regarding a specific snapshot.
     *
     * @param string $repository the name of the repository from which to retrieve the snapshot
     * @param string $name       the name of the desired snapshot
     *
     * @throws Exception\ResponseException
     * @throws Exception\NotFoundException
     *
     * @return Awaitable<array>
     */
    public async function getSnapshot(string $repository, string $name) : Awaitable<array>
    {
        try {
            $response = await $this->request($repository.'/'.$name);
        } catch (ResponseException $e) {
            if ($e->getResponse()->getStatus() == 404) {
                throw new NotFoundException("Snapshot '".$name."' does not exist in repository '".$repository."'.");
            }
            throw $e;
        }
        $data = $response->getData();
        if (isset(/* UNSAFE_EXPR */ $data['snapshots'][0])) {
            $snapshot = /* UNSAFE_EXPR */ $data['snapshots'][0];
            if ($snapshot instanceof Indexish) {
                return $snapshot;
            }
        }
        throw new \RuntimeException('expected array');
    }

    /**
     * Retrieve data regarding all snapshots in the given repository.
     *
     * @param string $repository the repository name
     *
     * @return Awaitable<array>
     */
    public async function getAllSnapshots(string $repository) : Awaitable<Indexish<string, mixed>>
    {
        $response = await $this->request($repository.'/_all');
        $data = $response->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        return $data;
    }

    /**
     * Delete a snapshot.
     *
     * @param string $repository the repository in which the snapshot resides
     * @param string $name       the name of the snapshot to be deleted
     *
     * @return Awaitable<Response>
     */
    public function deleteSnapshot(string $repository, string $name) : Awaitable<Response>
    {
        return $this->request($repository.'/'.$name, Request::DELETE);
    }

    /**
     * Restore a snapshot.
     *
     * @param string $repository        the name of the repository
     * @param string $name              the name of the snapshot
     * @param array  $options           options for the restore operation
     * @param bool   $waitForCompletion if true, the request will not return until the restore operation is complete
     *
     * @return Awaitable<Response>
     */
    public function restoreSnapshot(string $repository, string $name, array $options = array(), bool $waitForCompletion = false) : Awaitable<Response>
    {
        return $this->request($repository.'/'.$name.'/_restore', Request::POST, $options, array('wait_for_completion' => $waitForCompletion));
    }

    /**
     * Perform a snapshot request.
     *
     * @param string $path   the URL
     * @param string $method the HTTP method
     * @param array  $data   request body data
     * @param array  $query  query string parameters
     *
     * @return Awaitable<Response>
     */
    public function request(string $path, string $method = Request::GET, array $data = array(), array $query = array()) : Awaitable<Response>
    {
        return $this->_client->request('/_snapshot/'.$path, $method, $data, $query);
    }
}
