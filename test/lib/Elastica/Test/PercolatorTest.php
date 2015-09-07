<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Index;
use Elastica\Percolator;
use Elastica\Query;
use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;

class PercolatorTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testConstruct() : void
    {
        $index = $this->_createIndex();
        $percolatorName = $index->getName();

        $percolator = new Percolator($index);

        $query = new Term(Map {'field1' => 'value1'});
        $response = $percolator->registerQuery($percolatorName, $query)->getWaitHandle()->join();

        $data = $response->getData();

        $expectedArray = array(
            '_type' => '.percolator',
            '_index' => $index->getName(),
            '_id' => $percolatorName,
            '_version' => 1,
            'created' => 1,
        );

        $this->assertEquals($expectedArray, $data);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testMatchDoc() : void
    {
        $index = $this->_createIndex();

        $percolator = new Percolator($index);

        $percolatorName = $index->getName();

        $query = new Term(Map {'name' => 'ruflin'});
        $response = $percolator->registerQuery($percolatorName, $query)->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $doc1 = new Document();
        $doc1->set('name', 'ruflin');

        $doc2 = new Document();
        $doc2->set('name', 'nicolas');

        $index->refresh()->getWaitHandle()->join();

        $matches1 = $percolator->matchDoc($doc1)->getWaitHandle()->join();

        $this->assertCount(1, $matches1);
        $firstPercolatorFound = false;
        foreach ($matches1 as $match) {
            if ($match['_id'] == $percolatorName) {
                $firstPercolatorFound = true;
            }
        }
        $this->assertTrue($firstPercolatorFound);

        $matches2 = $percolator->matchDoc($doc2)->getWaitHandle()->join();
        $this->assertEmpty($matches2);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * Test case for using filtered percolator queries based on the Elasticsearch documentation examples.
     *
     * @group functional
     */
    public function testFilteredMatchDoc() : void
    {
        // step one: register create index and setup the percolator query from the ES documentation.
        $index = $this->_createIndex();
        $percolator = new Percolator($index);
        $baseQuery = new Term(Map {'field1' => 'value1'});
        $fields = array('color' => 'blue');

        $response = $percolator->registerQuery('kuku', $baseQuery, $fields)->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        // refreshing is required in order to ensure the query is really ready for execution.
        $index->refresh()->getWaitHandle()->join();

        // step two: match a document which should match the kuku query when filtered on the blue color
        $doc = new Document();
        $doc->set('field1', 'value1');

        $matches = $percolator->matchDoc($doc, new Term(Map {'color' => 'blue'}))->getWaitHandle()->join();
        $this->assertCount(1, $matches, 'No or too much registered query matched.');
        $this->assertEquals('kuku', $matches[0]['_id'], 'A wrong registered query has matched.');

        // step three: validate that using a different color, no registered query matches.
        $matches = $percolator->matchDoc($doc, new Term(Map {'color' => 'green'}))->getWaitHandle()->join();
        $this->assertCount(0, $matches, 'A registered query matched, although nothing should match at all.');

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * Test case for using filtered percolator queries based on the Elasticsearch documentation examples.
     *
     * @group functional
     */
    public function testRegisterAndUnregisterPercolator() : void
    {
        // step one: register create index and setup the percolator query from the ES documentation.
        $index = $this->_createIndex();
        $percolator = new Percolator($index);
        $baseQuery = new Term(Map {'field1' => 'value1'});
        $fields = array('color' => 'blue');

        $response = $percolator->registerQuery('kuku', $baseQuery, $fields)->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        // refreshing is required in order to ensure the query is really ready for execution.
        $index->refresh()->getWaitHandle()->join();

        // step two: match a document which should match the kuku query when filtered on the blue color
        $doc = new Document();
        $doc->set('field1', 'value1');

        $matches = $percolator->matchDoc($doc, new Term(Map {'color' => 'blue'}))->getWaitHandle()->join();
        $this->assertCount(1, $matches, 'No or too much registered query matched.');
        $this->assertEquals('kuku', $matches[0]['_id'], 'A wrong registered query has matched.');

        // step three: validate that using a different color, no registered query matches.
        $matches = $percolator->matchDoc($doc, new Term(Map {'color' => 'green'}))->getWaitHandle()->join();
        $this->assertCount(0, $matches, 'A registered query matched, although nothing should match at all.');

        // unregister percolator query
        $response = $percolator->unregisterQuery('kuku')->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        // refreshing is required in order to ensure the query is really ready for execution.
        $index->refresh()->getWaitHandle()->join();

        $matches = $percolator->matchDoc($doc, new Term(Map {'color' => 'blue'}))->getWaitHandle()->join();
        $this->assertCount(0, $matches, 'Percolator query did not get deleted.');

        $index->delete()->getWaitHandle()->join();
    }

    protected function _getDefaultPercolator(@string $percolatorName = 'existingDoc') : Percolator
    {
        $index = $this->_createIndex();
        $percolator = new Percolator($index);

        $query = new Term(Map {'name' => 'foobar'});
        $percolator->registerQuery($percolatorName, $query, array('field1' => array('tag1', 'tag2')))->getWaitHandle()->join();

        return $percolator;
    }

    protected function _addDefaultDocuments(@\Elastica\Index $index, @string $type = 'testing') : \Elastica\Type
    {
        $type = $index->getType('testing');
        $type->addDocuments(array(
            new Document('1', array('name' => 'foobar')),
            new Document('2', array('name' => 'barbaz')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        return $type;
    }

    /**
     * @group functional
     */
    public function testPercolateExistingDocWithoutAnyParameter() : void
    {
        $percolator = $this->_getDefaultPercolator();
        $index = $percolator->getIndex();
        $type = $this->_addDefaultDocuments($index);

        $matches = $percolator->matchExistingDoc('1', $type->getName())->getWaitHandle()->join();

        $this->assertCount(1, $matches);
        $this->assertEquals('existingDoc', $matches[0]['_id']);
        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testPercolateExistingDocWithPercolateFormatIds() : void
    {
        $percolator = $this->_getDefaultPercolator();
        $index = $percolator->getIndex();
        $type = $this->_addDefaultDocuments($index);

        $parameter = array('percolate_format' => 'ids');
        $matches = $percolator->matchExistingDoc('1', $type->getName(), null, $parameter)->getWaitHandle()->join();

        $this->assertCount(1, $matches);
        $this->assertEquals('existingDoc', $matches[0]);
        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testPercolateExistingDocWithIdThatShouldBeUrlEncoded() : void
    {
        $percolator = $this->_getDefaultPercolator();
        $index = $percolator->getIndex();
        $type = $this->_addDefaultDocuments($index);

        // id with whitespace, should be urlencoded
        $id = 'foo bar 1';

        $type->addDocument(new Document($id, array('name' => 'foobar')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $matches = $percolator->matchExistingDoc($id, $type->getName())->getWaitHandle()->join();

        $this->assertCount(1, $matches);
        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testPercolateWithAdditionalRequestBodyOptions() : void
    {
        $index = $this->_createIndex();
        $percolator = new Percolator($index);

        $query = new Term(Map {'name' => 'foo'});
        $response = $percolator->registerQuery('percotest', $query, array('field1' => array('tag1', 'tag2')))->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $query = new Term(Map {'name' => 'foo'});
        $response = $percolator->registerQuery('percotest1', $query, array('field1' => array('tag2')))->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $doc1 = new Document();
        $doc1->set('name', 'foo');

        $index->refresh()->getWaitHandle()->join();

        $options = array(
            'track_scores' => true,
            'sort' => array('_score' => 'desc'),
            'size' => 1,
        );

        $matches = $percolator->matchDoc($doc1, new Term(Map {'field1' => 'tag2'}), 'type', $options)->getWaitHandle()->join();

        $this->assertCount(1, $matches);
        $this->assertEquals('percotest1', $matches[0]['_id']);
        $this->assertArrayHasKey('_score', $matches[0]);
    }

    /**
     * @group functional
     */
    public function testPercolateExistingDocWithAdditionalRequestBodyOptions() : void
    {
        $percolatorName = 'existingDoc';
        $percolator = $this->_getDefaultPercolator($percolatorName);

        $query = new Term(Map {'name' => 'foobar'});
        $percolator->registerQuery($percolatorName.'1', $query, array('field1' => array('tag2')))->getWaitHandle()->join();

        $index = $percolator->getIndex();
        $type = $this->_addDefaultDocuments($index);

        $options = array(
            'track_scores' => true,
            'sort' => array('_score' => 'desc'),
            'size' => 1,
        );

        $matches = $percolator->matchExistingDoc('1', $type->getName(), new Term(Map {'field1' => 'tag2'}), $options)->getWaitHandle()->join();

        $this->assertCount(1, $matches);
        $this->assertEquals('existingDoc1', $matches[0]['_id']);
        $this->assertArrayHasKey('_score', $matches[0]);
        $index->delete()->getWaitHandle()->join();
    }

    protected function _createIndex($name = null, @bool $delete = true, @int $shards = 1) : \Elastica\Index
    {
        $index = parent::_createIndex($name, $delete, $shards);
        $type = $index->getType('.percolator');

        $mapping = new Type\Mapping($type,
            array(
                'name' => array('type' => 'string'),
                'field1' => array('type' => 'string'),
            )
        );
        $mapping->disableSource();

        $type->setMapping($mapping)->getWaitHandle()->join();

        return $index;
    }
}
