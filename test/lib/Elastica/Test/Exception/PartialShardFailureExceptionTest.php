<?hh
namespace Elastica\Test\Exception;

use Elastica\Document;
use Elastica\Exception\PartialShardFailureException;
use Elastica\Query;
use Elastica\ResultSet;

class PartialShardFailureExceptionTest extends AbstractExceptionTest
{
    /**
     * @group functional
     */
    public function testPartialFailure() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('elastica_partial_failure');
        $index->create(array(
            'index' => array(
                'number_of_shards' => 5,
                'number_of_replicas' => 0,
            ),
        ), true)->getWaitHandle()->join();

        $type = $index->getType('folks');

        $type->addDocument(new Document('', array('name' => 'ruflin')))->getWaitHandle()->join();
        $type->addDocument(new Document('', array('name' => 'bobrik')))->getWaitHandle()->join();
        $type->addDocument(new Document('', array('name' => 'kimchy')))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $query = Query::create(Map {
            'query' => Map {
                'filtered' => Map {
                    'filter' => Map {
                        'script' => Map {
                            'script' => 'doc["undefined"] > 8', // compiles, but doesn't work
                        },
                    },
                },
            },
        });

        try {
            $result = $index->search($query)->getWaitHandle()->join();

            $this->fail('PartialShardFailureException should have been thrown');
        } catch (PartialShardFailureException $e) {
            $resultSet = new ResultSet($e->getResponse(), $query);
            $this->assertEquals(0, count($resultSet->getResults()));
        }
    }
}
