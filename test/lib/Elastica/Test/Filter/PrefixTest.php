<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\Prefix;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class PrefixTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $field = 'name';
        $prefix = 'ruf';

        $filter = new Prefix($field, $prefix);

        $expectedArray = array(
            'prefix' => Map {
                $field => $prefix,
            },
        );

        $this->assertequals($expectedArray, $filter->toArray());
    }

    /**
     * @group functional
     */
    public function testDifferentPrefixes() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');

        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $mapping = new Mapping($type, array(
                'name' => array('type' => 'string', 'store' => 'no', 'index' => 'not_analyzed'),
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

        $query = new Prefix('name', 'Ba');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(3, $resultSet->count());

        // Lower case should not return a result
        $query = new Prefix('name', 'ba');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());

        $query = new Prefix('name', 'Baden');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        $query = new Prefix('name', 'Baden B');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $query = new Prefix('name', 'Baden Bas');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testDifferentPrefixesLowercase() : void
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

        $query = new Prefix('name', 'ba');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(3, $resultSet->count());

        // Upper case should not return a result
        $query = new Prefix('name', 'Ba');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());

        $query = new Prefix('name', 'baden');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        $query = new Prefix('name', 'baden b');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $query = new Prefix('name', 'baden bas');
        $resultSet = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());
    }
}
