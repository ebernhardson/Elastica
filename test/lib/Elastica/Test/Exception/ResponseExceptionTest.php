<?hh
namespace Elastica\Test\Exception;

use Elastica\Document;
use Elastica\Exception\ResponseException;

class ResponseExceptionTest extends AbstractExceptionTest
{
    /**
     * @group functional
     */
    public function testCreateExistingIndex() : void
    {
        $this->_createIndex('woo', true);

        try {
            $this->_createIndex('woo', false);
            $this->fail('Index created when it should fail');
        } catch (ResponseException $ex) {
            $this->assertEquals('IndexAlreadyExistsException', $ex->getElasticsearchException()->getExceptionName());
            $this->assertEquals(400, $ex->getElasticsearchException()->getCode());
        }
    }

    /**
     * @group functional
     */
    public function testBadType() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->setMapping(array(
            'num' => array(
                'type' => 'long',
            ),
        ))->getWaitHandle()->join();

        try {
            $type->addDocument(new Document('', array(
                'num' => 'not number at all',
            )))->getWaitHandle()->join();
            $this->fail('Indexing with wrong type should fail');
        } catch (ResponseException $ex) {
            $this->assertEquals('MapperParsingException', $ex->getElasticsearchException()->getExceptionName());
            $this->assertEquals(400, $ex->getElasticsearchException()->getCode());
        }
    }

    /**
     * @group functional
     */
    public function testWhatever() : void
    {
        $index = $this->_createIndex();
        $index->delete()->getWaitHandle()->join();

        try {
            $index->search()->getWaitHandle()->join();
        } catch (ResponseException $ex) {
            $this->assertEquals('IndexMissingException', $ex->getElasticsearchException()->getExceptionName());
            $this->assertEquals(404, $ex->getElasticsearchException()->getCode());
        }
    }
}
