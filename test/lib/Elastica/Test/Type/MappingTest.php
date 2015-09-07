<?hh
namespace Elastica\Test\Type;

use Elastica\Document;
use Elastica\Query;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;
use Elastica\Type\Mapping;

class MappingTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testMappingStoreFields() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');

        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $mapping = new Mapping($type,
            array(
                'firstname' => array('type' => 'string', 'store' => 'yes'),
                // default is store => no expected
                'lastname' => array('type' => 'string'),
            )
        );
        $mapping->disableSource();

        $type->setMapping($mapping)->getWaitHandle()->join();

        $firstname = 'Nicolas';
        $doc = new Document('1',
            array(
                'firstname' => $firstname,
                'lastname' => 'Ruflin',
            )
        );

        $type->addDocument($doc)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();
        $queryString = new QueryString('ruflin');
        $query = Query::create($queryString);
        $query->setFields(array('*'));

        $resultSet = $type->search($query)->getWaitHandle()->join();
        $result = $resultSet->current();
        $fields = $result->getFields();

        $this->assertEquals($firstname, $fields['firstname'][0]);
        $this->assertArrayNotHasKey('lastname', $fields);
        $this->assertEquals(1, count($fields));

        $index->flush()->getWaitHandle()->join();
        $document = $type->getDocument('1')->getWaitHandle()->join();

        $this->assertEmpty($document->getData());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testEnableAllField() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $mapping = new Mapping($type, array());

        $mapping->enableAllField();

        $data = $mapping->toArray();
        $this->assertTrue($data[$type->getName()]['_all']['enabled']);

        $response = $mapping->send()->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testEnableTtl() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');

        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $mapping = new Mapping($type, array());

        $mapping->enableTtl();

        $data = $mapping->toArray();
        $this->assertTrue($data[$type->getName()]['_ttl']['enabled']);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testNestedMapping() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');

        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        //$this->markTestIncomplete('nested mapping is not set right yet');
        $mapping = new Mapping($type,
            array(
                'test' => array(
                    'type' => 'object', 'store' => 'yes', 'properties' => array(
                        'user' => array(
                            'properties' => array(
                                'firstname' => array('type' => 'string', 'store' => 'yes'),
                                'lastname' => array('type' => 'string', 'store' => 'yes'),
                                'age' => array('type' => 'integer', 'store' => 'yes'),
                            ),
                        ),
                    ),
                ),
            )
        );

        $response = $type->setMapping($mapping)->getWaitHandle()->join();
        $this->assertFalse($response->hasError());

        $doc = new Document('1', array(
            'user' => array(
                'firstname' => 'Nicolas',
                'lastname' => 'Ruflin',
                'age' => 9,
            ),
        ));

        $type->addDocument($doc)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();
        $resultSet = $type->search('ruflin')->getWaitHandle()->join();
        $this->assertEquals($resultSet->count(), 1);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testParentMapping() : void
    {
        $index = $this->_createIndex();
        $parenttype = new Type($index, 'parenttype');
        $parentmapping = new Mapping($parenttype,
            array(
                'name' => array('type' => 'string', 'store' => 'yes'),
            )
        );

        $parenttype->setMapping($parentmapping)->getWaitHandle()->join();

        $childtype = new Type($index, 'childtype');
        $childmapping = new Mapping($childtype,
            array(
                'name' => array('type' => 'string', 'store' => 'yes'),
            )
        );
        $childmapping->setParent('parenttype');

        $childtype->setMapping($childmapping)->getWaitHandle()->join();

        $data = $childmapping->toArray();
        $this->assertEquals('parenttype', $data[$childtype->getName()]['_parent']['type']);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testMappingExample() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('notes');

        $mapping = new Mapping($type,
            array(
                'note' => array(
                    'store' => 'yes', 'properties' => array(
                        'titulo' => array('type' => 'string', 'store' => 'no', 'include_in_all' => true, 'boost' => 1.0),
                        'contenido' => array('type' => 'string', 'store' => 'no', 'include_in_all' => true, 'boost' => 1.0),
                    ),
                ),
            )
        );

        $type->setMapping($mapping)->getWaitHandle()->join();

        $doc = new Document('1', array(
                'note' => array(
                    array(
                        'titulo' => 'nota1',
                        'contenido' => 'contenido1',
                    ),
                    array(
                        'titulo' => 'nota2',
                        'contenido' => 'contenido2',
                    ),
                ),
            )
        );

        $type->addDocument($doc)->getWaitHandle()->join();

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     *
     * Test setting a dynamic template and validate whether the right mapping is applied after adding a document which
     * should match the dynamic template. The example is the template_1 from the Elasticsearch documentation.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-root-object-type.html
     */
    public function testDynamicTemplate() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('person');

        // set a dynamic template "template_1" which creates a multi field for multi* matches.
        $mapping = new Mapping($type);
        $mapping->setParam('dynamic_templates', array(
            array('template_1' => array(
                'match' => 'multi*',
                'mapping' => array(
                    'type' => 'multi_field',
                    'fields' => array(
                        '{name}' => array('type' => '{dynamic_type}', 'index' => 'analyzed'),
                        'org' => array('type' => '{dynamic_type}', 'index' => 'not_analyzed'),
                    ),
                ),
            )),
        ));

        $mapping->send()->getWaitHandle()->join();

        // when running the tests, the mapping sometimes isn't available yet. Optimize index to enforce reload mapping.
        $index->optimize()->getWaitHandle()->join();

        // create a document which should create a mapping for the field: multiname.
        $testDoc = new Document('person1', array('multiname' => 'Jasper van Wanrooy'), $type);
        $index->addDocuments(array($testDoc))->getWaitHandle()->join();
        sleep(1);   //sleep 1 to ensure that the test passes every time

        // read the mapping from Elasticsearch and assert that the multiname.org field is "not_analyzed"
        $newMapping = $type->getMapping()->getWaitHandle()->join();
        $this->assertArrayHasKey('person', $newMapping,
            'Person type not available in mapping from ES. Mapping set at all?');
        $this->assertArrayHasKey('properties', $newMapping['person'],
            'Person type doesnt have any properties. Document properly added?');
        $this->assertArrayHasKey('multiname', $newMapping['person']['properties'],
            'The multiname property is not added to the mapping. Document properly added?');
        $this->assertArrayHasKey('fields', $newMapping['person']['properties']['multiname'],
            'The multiname field of the Person type is presumably not a multi_field type. Dynamic mapping not applied?');
        $this->assertArrayHasKey('org', $newMapping['person']['properties']['multiname']['fields'],
            'The multi* matcher did not create a mapping for the multiname.org property when indexing the document.');
        $this->assertArrayHasKey('index', $newMapping['person']['properties']['multiname']['fields']['org'],
            'Indexing status of the multiname.org not available. Dynamic mapping not fully applied!');
        $this->assertEquals('not_analyzed', $newMapping['person']['properties']['multiname']['fields']['org']['index']);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetMeta() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $mapping = new Mapping($type, array(
            'firstname' => array('type' => 'string', 'store' => 'yes'),
            'lastname' => array('type' => 'string'),
        ));
        $mapping->setMeta(array('class' => 'test'));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $mappingData = $type->getMapping()->getWaitHandle()->join();
        $this->assertEquals('test', $mappingData['test']['_meta']['class']);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetters() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $properties = array(
            'firstname' => array('type' => 'string', 'store' => 'yes'),
            'lastname' => array('type' => 'string'),
        );
        $mapping = new Mapping($type, $properties);
        $all = array(
           'enabled' => true,
           'store' => 'yes',
        );
        $mapping->setParam('_all', $all);
        $get_all = $mapping->getParam('_all');

        $this->assertEquals($get_all, $all);

        $this->assertNull($mapping->getParam('_boost'));

        $this->assertEquals($properties, $mapping->getProperties());

        $index->delete()->getWaitHandle()->join();
    }
}
