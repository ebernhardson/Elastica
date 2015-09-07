<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;

class DocumentTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testAddFile() : void
    {
        $fileName = '/dev/null';
        if (!file_exists($fileName)) {
            $this->markTestSkipped("File {$fileName} does not exist.");
        }
        $doc = new Document();
        $returnValue = $doc->addFile('key', $fileName);
        $this->assertInstanceOf('Elastica\Document', $returnValue);
    }

    /**
     * @group unit
     */
    public function testAddGeoPoint() : void
    {
        $doc = new Document();
        $returnValue = $doc->addGeoPoint('point', 38.89859, -77.035971);
        $this->assertInstanceOf('Elastica\Document', $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetData() : void
    {
        $doc = new Document();
        $returnValue = $doc->setData(array('data'));
        $this->assertInstanceOf('Elastica\Document', $returnValue);
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $id = '17';
        $data = array('hello' => 'world');
        $type = 'testtype';
        $index = 'textindex';

        $doc = new Document($id, $data, $type, $index);

        $result = Map {'_index' => $index, '_type' => $type, '_id' => $id, '_source' => $data};
        $this->assertEquals($result, $doc->toArray());
    }

    /**
     * @group unit
     */
    public function testSetType() : void
    {
        $document = new Document();
        $document->setType('type');

        $this->assertEquals('type', $document->getType());

        $index = new Index($this->_getClient(), 'index');
        $type = $index->getType('type');

        $document->setIndex('index2');
        $this->assertEquals('index2', $document->getIndex());

        $document->setType($type);

        $this->assertEquals('index', $document->getIndex());
        $this->assertEquals('type', $document->getType());
    }

    /**
     * @group unit
     */
    public function testSetIndex() : void
    {
        $document = new Document();
        $document->setIndex('index2');
        $document->setType('type2');

        $this->assertEquals('index2', $document->getIndex());
        $this->assertEquals('type2', $document->getType());

        $index = new Index($this->_getClient(), 'index');

        $document->setIndex($index);

        $this->assertEquals('index', $document->getIndex());
        $this->assertEquals('type2', $document->getType());
    }

    /**
     * @group unit
     */
    public function testHasId() : void
    {
        $document = new Document();
        $this->assertFalse($document->hasId());
        $document->setId('');
        $this->assertFalse($document->hasId());
        $document->setId('0');
        $this->assertTrue($document->hasId());
        $document->setId('hello');
        $this->assertTrue($document->hasId());
    }

    /**
     * @group unit
     */
    public function testGetOptions() : void
    {
        $document = new Document();
        $document->setIndex('index');
        $document->setOpType('create');
        $document->setParent('2');
        $document->setId('1');

        $options = $document->getOptions(array('index', 'type', 'id', 'parent'));

        $this->assertInstanceOf('HH\Map', $options);
        $this->assertEquals(3, count($options));
        $this->assertTrue(/* UNSAFE_EXPR */ $options->contains('index'));
        $this->assertTrue(/* UNSAFE_EXPR */ $options->contains('id'));
        $this->assertTrue(/* UNSAFE_EXPR */ $options->contains('parent'));
        $this->assertEquals('index', $options['index']);
        $this->assertEquals(1, $options['id']);
        $this->assertEquals('2', $options['parent']);
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('type'));
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('op_type'));
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('_index'));
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('_id'));
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('_parent'));

        $options = $document->getOptions(array('parent', 'op_type', 'percolate'), true);

        $this->assertInstanceOf('HH\Map', $options);
        $this->assertEquals(2, count($options));
        $this->assertTrue(/* UNSAFE_EXPR */ $options->contains('_parent'));
        $this->assertTrue(/* UNSAFE_EXPR */ $options->contains('_op_type'));
        $this->assertEquals('2', $options['_parent']);
        $this->assertEquals('create', $options['_op_type']);
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('percolate'));
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('op_type'));
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('parent'));
    }

    /**
     * @group unit
     */
    public function testGetSetHasRemove() : void
    {
        $document = new Document('1', array('field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3', 'field4' => null));

        $this->assertEquals('value1', $document->get('field1'));
        $this->assertEquals('value2', $document->get('field2'));
        $this->assertEquals('value3', $document->get('field3'));
        $this->assertNull($document->get('field4'));
        try {
            $document->get('field5');
            $this->fail('Undefined field get should throw exception');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        $this->assertTrue($document->has('field1'));
        $this->assertTrue($document->has('field2'));
        $this->assertTrue($document->has('field3'));
        $this->assertTrue($document->has('field4'));
        $this->assertFalse($document->has('field5'), 'Field5 should not be isset, because it is not set');

        $data = $document->getData();

        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2', /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field3', $data);
        $this->assertEquals('value3', /* UNSAFE_EXPR */ $data['field3']);
        $this->assertArrayHasKey('field4', $data);
        $this->assertNull(/* UNSAFE_EXPR */ $data['field4']);

        $returnValue = $document->set('field1', 'changed1');
        $this->assertInstanceOf('Elastica\Document', $returnValue);
        $returnValue = $document->remove('field3');
        $this->assertInstanceOf('Elastica\Document', $returnValue);
        try {
            $document->remove('field5');
            $this->fail('Undefined field unset should throw exception');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        $this->assertEquals('changed1', $document->get('field1'));
        $this->assertFalse($document->has('field3'));

        $newData = $document->getData();

        $this->assertNotEquals($data, $newData);
    }

    /**
     * @group unit
     */
    public function testDataPropertiesOverloading() : void
    {
        $document = new Document('1', array('field1' => 'value1', 'field2' => 'value2', 'field3' => 'value3', 'field4' => null));

        $this->assertEquals('value1', $document->get('field1'));
        $this->assertEquals('value2', $document->get('field2'));
        $this->assertEquals('value3', $document->get('field3'));
        $this->assertNull($document->get('field4'));
        try {
            $document->get('field5');
            $this->fail('Undefined field get should throw exception');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        $this->assertTrue($document->__isset('field1'));
        $this->assertTrue($document->__isset('field2'));
        $this->assertTrue($document->__isset('field3'));
        $this->assertFalse($document->__isset('field4'), 'Field4 should not be isset, because it is null');
        $this->assertFalse($document->__isset('field5'), 'Field5 should not be isset, because it is not set');

        $data = $document->getData();

        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2', /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field3', $data);
        $this->assertEquals('value3', /* UNSAFE_EXPR */ $data['field3']);
        $this->assertArrayHasKey('field4', $data);
        $this->assertNull(/* UNSAFE_EXPR */ $data['field4']);

        $document->set('field1', 'changed1');
        $document->remove('field3');
        try {
            $document->remove('field5');
            $this->fail('Undefined field unset should throw exception');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        $this->assertEquals('changed1', $document->get('field1'));
        $this->assertFalse($document->has('field3'));

        $newData = $document->getData();

        $this->assertNotEquals($data, $newData);
    }

    /**
     * @group unit
     */
    public function testSetTtl() : void
    {
        $document = new Document();

        $this->assertFalse($document->hasTtl());
        $options = $document->getOptions();
        $this->assertFalse(/* UNSAFE_EXPR */ $options->contains('ttl'));

        $document->setTtl('1d');

        $newOptions = $document->getOptions();

        $this->assertTrue(/* UNSAFE_EXPR */ $newOptions->contains('ttl'));
        $this->assertEquals('1d', $newOptions['ttl']);
        $this->assertNotEquals($options, $newOptions);

        $this->assertTrue($document->hasTtl());
        $this->assertEquals('1d', $document->getTtl());
    }

    /**
     * @group unit
     */
    public function testSerializedData() : void
    {
        $data = '{"user":"rolf"}';
        $document = new Document('1', $data);

        $this->assertFalse($document->has('user'));

        try {
            $document->get('user');
            $this->fail('User field should not be available');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        try {
            $document->remove('user');
            $this->fail('User field should not be available for removal');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        try {
            $document->set('name', 'shawn');
            $this->fail('Document should not allow to set new data');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @group unit
     */
    public function testUpsert() : void
    {
        $document = new Document();

        $upsert = new Document();
        $upsert->setData(array('someproperty' => 'somevalue'));

        $this->assertFalse($document->hasUpsert());

        $document->setUpsert($upsert);

        $this->assertTrue($document->hasUpsert());
        $this->assertSame($upsert, $document->getUpsert());
    }

    /**
     * @group unit
     */
    public function testDocAsUpsert() : void
    {
        $document = new Document();

        $this->assertFalse($document->getDocAsUpsert());
        $this->assertSame($document, $document->setDocAsUpsert(true));
        $this->assertTrue($document->getDocAsUpsert());
    }
}
