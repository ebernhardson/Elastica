<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\Wildcard;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class WildcardTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstructEmpty() : void
    {
        $wildcard = new Wildcard();
        $this->assertEmpty($wildcard->getParams());
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $key = 'name';
        $value = 'Ru*lin';
        $boost = 2.0;

        $wildcard = new Wildcard($key, $value, $boost);

        $expectedArray = array(
            'wildcard' => Map {
                $key => array(
                    'value' => $value,
                    'boost' => $boost,
                ),
            },
        );

        $this->assertEquals($expectedArray, $wildcard->toArray());
    }

    /**
     * @group functional
     */
    public function testSearchWithAnalyzer() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');

        $indexParams = array(
            'analysis' => array(
                'analyzer' => array(
                    'lw' => array(
                        'type' => 'custom',
                        'tokenizer' => 'keyword',
                        'filter' => array('lowercase'),
                    ),
                ),
            ),
        );

        $index->create($indexParams, true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $mapping = new Mapping($type, array(
                'name' => array('type' => 'string', 'store' => 'no', 'analyzer' => 'lw'),
            )
        );
        $type->setMapping($mapping)->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'Baden')),
            new Document('4', array('name' => 'Baden Baden')),
            new Document('5', array('name' => 'New Orleans')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $query = new Wildcard();
        $query->setValue('name', 'ba*');
        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(3, $resultSet->count());

        $query = new Wildcard();
        $query->setValue('name', 'baden*');
        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());

        $query = new Wildcard();
        $query->setValue('name', 'baden b*');
        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());

        $query = new Wildcard();
        $query->setValue('name', 'baden bas*');
        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(0, $resultSet->count());
    }
}
