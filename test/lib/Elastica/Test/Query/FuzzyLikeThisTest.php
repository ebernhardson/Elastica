<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Index;
use Elastica\Query\FuzzyLikeThis;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;
use Elastica\Type\Mapping;

class FuzzyLikeThisTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testSearch() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test');
        $index->create(array(), true)->getWaitHandle()->join();
        $index->getSettings()->setNumberOfReplicas(0)->getWaitHandle()->join();
        //$index->getSettings()->setNumberOfShards(1)->getWaitHandle()->join();

        $type = new Type($index, 'helloworldfuzzy');
        $mapping = new Mapping($type, array(
               'email' => array('store' => 'yes', 'type' => 'string', 'index' => 'analyzed'),
               'content' => array('store' => 'yes', 'type' => 'string',  'index' => 'analyzed'),
          ));

        $mapping->setSource(array('enabled' => false));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $doc = new Document('1000', array('email' => 'testemail@gmail.com', 'content' => 'This is a sample post. Hello World Fuzzy Like This!'));
        $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $fltQuery = new FuzzyLikeThis();
        $fltQuery->setLikeText('sample gmail');
        $fltQuery->addFields(array('email', 'content'));
        $fltQuery->setMinSimilarity(0.3);
        $fltQuery->setMaxQueryTerms(3);
        $resultSet = $type->search($fltQuery)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group unit
     */
    public function testSetPrefixLength() : void
    {
        $query = new FuzzyLikeThis();

        $length = 3;
        $query->setPrefixLength($length);

        $data = $query->toArray();

        $this->assertEquals($length, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['prefix_length']);
    }

    /**
     * @group unit
     */
    public function testAddFields() : void
    {
        $query = new FuzzyLikeThis();

        $fields = array('test1', 'test2');
        $query->addFields($fields);

        $data = $query->toArray();

        $this->assertEquals($fields, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['fields']);
    }

    /**
     * @group unit
     */
    public function testSetLikeText() : void
    {
        $query = new FuzzyLikeThis();

        $text = ' hello world';
        $query->setLikeText($text);

        $data = $query->toArray();

        $this->assertEquals(trim($text), /* UNSAFE_EXPR */ $data['fuzzy_like_this']['like_text']);
    }

    /**
     * @group unit
     */
    public function testSetIgnoreTF() : void
    {
        $query = new FuzzyLikeThis();

        $ignoreTF = false;
        $query->setIgnoreTF($ignoreTF);
        $data = $query->toArray();
        $this->assertEquals($ignoreTF, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['ignore_tf']);

        $ignoreTF = true;
        $query->setIgnoreTF($ignoreTF);
        $data = $query->toArray();
        $this->assertEquals($ignoreTF, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['ignore_tf']);
    }

    /**
     * @group unit
     */
    public function testSetIgnoreTFDefault() : void
    {
        $query = new FuzzyLikeThis();

        $data = $query->toArray();

        $defaultIgnoreTF = false;
        $this->assertEquals($defaultIgnoreTF, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['ignore_tf']);
    }

    /**
     * @group unit
     */
    public function testSetMinSimilarity() : void
    {
        $query = new FuzzyLikeThis();

        $similarity = 2.0;
        $query->setMinSimilarity($similarity);

        $data = $query->toArray();

        $this->assertEquals($similarity, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['min_similarity']);
    }

    /**
     * @group unit
     */
    public function testSetBoost() : void
    {
        $query = new FuzzyLikeThis();

        $boost = 2.2;
        $query->setBoost($boost);

        $data = $query->toArray();

        $this->assertEquals($boost, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['boost']);
    }

    /**
     * @group unit
     */
    public function testAddAnalyzerViasetParam() : void
    {
        $analyzer = 'snowball';

        $query = new FuzzyLikeThis();
        $query->setParam('analyzer', $analyzer);

        $data = $query->toArray();
        $this->assertEquals($analyzer, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['analyzer']);
    }

    /**
     * @group unit
     */
    public function testSetAnalyzer() : void
    {
        $analyzer = 'snowball';

        $query = new FuzzyLikeThis();
        $query->setAnalyzer($analyzer);

        $data = $query->toArray();
        $this->assertEquals($analyzer, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['analyzer']);
    }

    /**
     * @group unit
     */
    public function testAnalyzerNotPresentInArrayToMaintainDefaultOfField() : void
    {
        $query = new FuzzyLikeThis();

        $data = $query->toArray();
        $this->assertArrayNotHasKey('analyzer', $data);
    }

    /**
     * @group unit
     */
    public function testArgArrayFieldsOverwrittenBySetParams() : void
    {
        $query = new FuzzyLikeThis();
        $query->setMaxQueryTerms(100);
        $query->setParam('max_query_terms', 200);

        $data = $query->toArray();
		$this->assertEquals(200, /* UNSAFE_EXPR */ $data['fuzzy_like_this']['max_query_terms']);
    }

    /**
     * @group functional
     */
    public function testSearchSetAnalyzer() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test');
        $index->create(array('analysis' => array(
            'analyzer' => array(
               'searchAnalyzer' => array(
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => array('myStopWords'),
                ),
            ),
            'filter' => array(
                'myStopWords' => array(
                    'type' => 'stop',
                    'stopwords' => array('The'),
                ),
            ),
        )), true)->getWaitHandle()->join();

        $index->getSettings()->setNumberOfReplicas(0)->getWaitHandle()->join();
        //$index->getSettings()->setNumberOfShards(1)->getWaitHandle()->join();

        $type = new Type($index, 'helloworldfuzzy');
        $mapping = new Mapping($type, array(
               'email' => array('store' => 'yes', 'type' => 'string', 'index' => 'analyzed'),
               'content' => array('store' => 'yes', 'type' => 'string',  'index' => 'analyzed'),
          ));

        $mapping->setSource(array('enabled' => false));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1000', array('email' => 'testemail@gmail.com', 'content' => 'The Fuzzy Test!')),
            new Document('1001', array('email' => 'testemail@gmail.com', 'content' => 'Elastica Fuzzy Test')),
        ))->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $fltQuery = new FuzzyLikeThis();
        $fltQuery->addFields(array('email', 'content'));
        $fltQuery->setLikeText('The');

        $fltQuery->setMinSimilarity(0.1);
        $fltQuery->setMaxQueryTerms(3);

        // Test before analyzer applied, should return 1 result
        $resultSet = $type->search($fltQuery)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $fltQuery->setParam('analyzer', 'searchAnalyzer');

        $resultSet = $type->search($fltQuery)->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testNoLikeTextProvidedShouldReturnNoResults() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test');
        $index->create(array(), true)->getWaitHandle()->join();
        $index->getSettings()->setNumberOfReplicas(0)->getWaitHandle()->join();

        $type = new Type($index, 'helloworldfuzzy');
        $mapping = new Mapping($type, array(
            'email' => array('store' => 'yes', 'type' => 'string', 'index' => 'analyzed'),
            'content' => array('store' => 'yes', 'type' => 'string',  'index' => 'analyzed'),
        ));

        $mapping->setSource(array('enabled' => false));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $doc = new Document('1000', array('email' => 'testemail@gmail.com', 'content' => 'This is a sample post. Hello World Fuzzy Like This!'));
        $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $fltQuery = new FuzzyLikeThis();
        $fltQuery->setLikeText('');
        $fltQuery->addFields(array('email', 'content'));
        $fltQuery->setMinSimilarity(0.3);
        $fltQuery->setMaxQueryTerms(3);
        $resultSet = $type->search($fltQuery)->getWaitHandle()->join();

        $this->assertEquals(0, $resultSet->count());
    }
}
