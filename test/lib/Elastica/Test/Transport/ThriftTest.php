<?hh
namespace Elastica\Test\Transport;

use Elastica\Connection;
use Elastica\Document;
use Elastica\Index;
use Elastica\Test\Base as BaseTest;

class ThriftTest extends BaseTest
{
    public static function setUpBeforeClass() : void
    {
        if (!class_exists('Elasticsearch\\RestClient')) {
            self::markTestSkipped('munkie/elasticsearch-thrift-php package should be installed to run thrift transport tests');
        }
    }

    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $host = $this->_getHost();
        $port = 9500;
        $client = $this->_getClient(array('host' => $host, 'port' => $port, 'transport' => 'Thrift'));

        $this->assertEquals($host, $client->getConnection()->getHost());
        $this->assertEquals($port, $client->getConnection()->getPort());
    }

    /**
     * @group functional
     * @dataProvider configProvider
     */
    public function testSearchRequest($config) : void
    {
        $this->_checkPlugin();

        // Creates a new index 'xodoa' and a type 'user' inside this index
        $client = $this->_getClient($config);

        $index = $client->getIndex('elastica_test1');
        $index->create(array(), true)->getWaitHandle()->join();

        $type = $index->getType('user');

        // Adds 1 document to the index
        $doc1 = new Document('1',
            array('username' => 'hans', 'test' => array('2', '3', '5'))
        );
        $doc1->setVersion(0);
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Adds a list of documents with _bulk upload to the index
        $docs = array();
        $docs[] = new Document('2',
            array('username' => 'john', 'test' => array('1', '3', '6'))
        );
        $docs[] = new Document('3',
            array('username' => 'rolf', 'test' => array('2', '3', '7'))
        );
        $type->addDocuments($docs)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();
        $resultSet = $type->search('rolf')->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->getTotalHits());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\ConnectionException
     */
    public function testInvalidHostRequest() : void
    {
        $this->_checkPlugin();

        $client = $this->_getClient(array('host' => 'unknown', 'port' => 9555, 'transport' => 'Thrift'));
        $client->getStatus()->getWaitHandle()->join();
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\ResponseException
     */
    public function testInvalidElasticRequest() : void
    {
        $this->_checkPlugin();

        $connection = new Connection();
        $connection->setHost($this->_getHost());
        $connection->setPort(9500);
        $connection->setTransport('Thrift');

        $client = $this->_getClient();
        $client->addConnection($connection);

        $index = new Index($client, 'missing_index');
        $index->getStatus()->getWaitHandle()->join();
    }

    public function configProvider() : array<array<array>>
    {
        return array(
            array(
                array(
                    'host' => $this->_getHost(),
                    'port' => 9500,
                    'transport' => 'Thrift',
                ),
            ),
            array(
                array(
                    'host' => $this->_getHost(),
                    'port' => 9500,
                    'transport' => 'Thrift',
                    'config' => array(
                        'framedTransport' => false,
                        'sendTimeout' => 10000,
                        'recvTimeout' => 20000,
                    ),
                ),
            ),
        );
    }

    protected function _checkPlugin() : void
    {
        $nodes = $this->_getClient()->getCluster()->getWaitHandle()->join()->getNodes();
        if (empty($nodes) || !$nodes[0]->getInfo()->getWaitHandle()->join()->hasPlugin('transport-thrift')->getWaitHandle()->join()) {
            $this->markTestSkipped('transport-thrift plugin not installed.');
        }
    }
}
