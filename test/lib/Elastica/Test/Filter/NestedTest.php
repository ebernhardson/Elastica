<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\Nested;
use Elastica\Query\Terms;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class NestedTest extends BaseTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex('elastica_test_filter_nested');
        $type = $index->getType('user');
        $mapping = new Mapping();
        $mapping->setProperties(
            array(
                'firstname' => array('type' => 'string', 'store' => 'yes'),
                // default is store => no expected
                'lastname' => array('type' => 'string'),
                'hobbies' => array(
                    'type' => 'nested',
                    'include_in_parent' => true,
                    'properties' => array('hobby' => array('type' => 'string')),
                ),
            )
        );
        $type->setMapping($mapping)->getWaitHandle()->join();

        $response = $type->addDocuments(array(
            new Document('1',
                array(
                    'firstname' => 'Nicolas',
                    'lastname' => 'Ruflin',
                    'hobbies' => array(
                        array('hobby' => 'opensource'),
                    ),
                )
            ),
            new Document('2',
                array(
                    'firstname' => 'Nicolas',
                    'lastname' => 'Ippolito',
                    'hobbies' => array(
                        array('hobby' => 'opensource'),
                        array('hobby' => 'guitar'),
                    ),
                )
            ),
        ));

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
        $filter->setQuery($query);

        $expectedArray = array(
            'nested' => Map {
                'path' => 'hobbies',
                'query' => array('terms' => Map {
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
        $filter->setQuery($query);

        $search = new Search($this->_getClient());
        $search->addIndex($this->_getIndexForTest());
        $resultSet = $search->search($filter)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->getTotalHits());

        $filter = new Nested();
        $this->assertEquals(array('nested' => Map {}), $filter->toArray());
        $query = new Terms();
        $query->setTerms('hobby', array('opensource'));
        $filter->setPath('hobbies');
        $filter->setQuery($query);

        $search = new Search($this->_getClient());
        $search->addIndex($this->_getIndexForTest());
        $resultSet = $search->search($filter)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->getTotalHits());
    }

    /**
     * @group unit
     */
    public function testSetJoin() : void
    {
        $filter = new Nested();

        $this->assertTrue($filter->setJoin(true)->getParam('join'));

        $this->assertFalse($filter->setJoin(false)->getParam('join'));

        $returnValue = $filter->setJoin(true);
        $this->assertInstanceOf('Elastica\Filter\Nested', $returnValue);
    }
}
