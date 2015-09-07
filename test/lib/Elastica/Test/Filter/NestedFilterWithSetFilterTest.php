<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\Nested;
use Elastica\Filter\Terms;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class NestedFilterWithSetFilterTest extends BaseTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();
        $type = $index->getType('user');

        $type->setMapping(new Mapping(null, array(
            'firstname' => array('type' => 'string', 'store' => 'yes'),
            // default is store => no expected
            'lastname' => array('type' => 'string'),
            'hobbies' => array(
                'type' => 'nested',
                'include_in_parent' => true,
                'properties' => array('hobby' => array('type' => 'string')),
            ),
        )))->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1', array(
                'firstname' => 'Nicolas',
                'lastname' => 'Ruflin',
                'hobbies' => array(
                    array('hobby' => 'opensource'),
                ),
            )),
            new Document('2', array(
                'firstname' => 'Nicolas',
                'lastname' => 'Ippolito',
                'hobbies' => array(
                    array('hobby' => 'opensource'),
                    array('hobby' => 'guitar'),
                ),
            )),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $filter = new Nested();
        $this->assertEquals(array('nested' => Map {}), $filter->toArray());
        $query = new Terms();
        $query->setTerms('hobby', array('guitar'));
        $filter->setPath('hobbies');
        $filter->setFilter($query);

        $expectedArray = array(
            'nested' => Map {
                'path' => 'hobbies',
                'filter' => array('terms' => Map {
                    'hobby' => array('guitar'),
                }),
            },
        );

        $this->assertEquals($expectedArray, $filter->toArray());
    }

    /**
     * @group functional
     */
    public function testShouldReturnTheRightNumberOfResult() : void
    {
        $filter = new Nested();
        $this->assertEquals(array('nested' => Map {}), $filter->toArray());
        $query = new Terms();
        $query->setTerms('hobby', array('guitar'));
        $filter->setPath('hobbies');
        $filter->setFilter($query);

        $client = $this->_getClient();
        $search = new Search($client);
        $index = $this->_getIndexForTest();
        $search->addIndex($index);
        $resultSet = $search->search($filter)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->getTotalHits());

        $filter = new Nested();
        $this->assertEquals(array('nested' => Map {}), $filter->toArray());
        $query = new Terms();
        $query->setTerms('hobby', array('opensource'));
        $filter->setPath('hobbies');
        $filter->setFilter($query);

        $client = $this->_getClient();
        $search = new Search($client);
        $index = $this->_getIndexForTest();
        $search->addIndex($index);
        $resultSet = $search->search($filter)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->getTotalHits());
    }
}
