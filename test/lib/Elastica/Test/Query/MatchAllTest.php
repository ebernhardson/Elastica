<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\MatchAll;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;

class MatchAllTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new MatchAll();

        $expectedArray = array('match_all' => Map {});

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testMatchAllIndicesTypes() : void
    {
        $index1 = $this->_createIndex();
        $index2 = $this->_createIndex();

        $client = $index1->getClient();

        $search1 = new Search($client);
        $resultSet1 = $search1->search(new MatchAll())->getWaitHandle()->join();

        $doc1 = new Document('1', array('name' => 'ruflin'));
        $doc2 = new Document('1', array('name' => 'ruflin'));
        $index1->getType('test')->addDocument($doc1)->getWaitHandle()->join();
        $index2->getType('test')->addDocument($doc2)->getWaitHandle()->join();

        $index1->refresh()->getWaitHandle()->join();
        $index2->refresh()->getWaitHandle()->join();

        $search2 = new Search($client);
        $resultSet2 = $search2->search(new MatchAll())->getWaitHandle()->join();

        $this->assertEquals($resultSet1->getTotalHits() + 2, $resultSet2->getTotalHits());
    }
}
