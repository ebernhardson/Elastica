<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\Match;
use Elastica\Query\MatchPhrase;
use Elastica\Query\MatchPhrasePrefix;
use Elastica\Test\Base as BaseTest;

class MatchTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $field = 'test';
        $testQuery = 'Nicolas Ruflin';
        $type = 'phrase';
        $operator = 'and';
        $analyzer = 'myanalyzer';
        $boost = 2.0;
        $minimumShouldMatch = 2;
        $fuzziness = 0.3;
        $fuzzyRewrite = 'constant_score_boolean';
        $prefixLength = 3;
        $maxExpansions = 12;

        $query = new Match();
        $query->setFieldQuery($field, $testQuery);
        $query->setFieldType($field, $type);
        $query->setFieldOperator($field, $operator);
        $query->setFieldAnalyzer($field, $analyzer);
        $query->setFieldBoost($field, $boost);
        $query->setFieldMinimumShouldMatch($field, $minimumShouldMatch);
        $query->setFieldFuzziness($field, $fuzziness);
        $query->setFieldFuzzyRewrite($field, $fuzzyRewrite);
        $query->setFieldPrefixLength($field, $prefixLength);
        $query->setFieldMaxExpansions($field, $maxExpansions);

        $expectedArray = array(
            'match' => Map {
                $field => array(
                    'query' => $testQuery,
                    'type' => $type,
                    'operator' => $operator,
                    'analyzer' => $analyzer,
                    'boost' => $boost,
                    'minimum_should_match' => $minimumShouldMatch,
                    'fuzziness' => $fuzziness,
                    'fuzzy_rewrite' => $fuzzyRewrite,
                    'prefix_length' => $prefixLength,
                    'max_expansions' => $maxExpansions,
                ),
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testMatch() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';
        $operator = 'or';

        $query = new Match();
        $query->setFieldQuery($field, 'Basel New');
        $query->setFieldOperator($field, $operator);

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(4, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchSetFieldBoost() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';
        $operator = 'or';

        $query = new Match();
        $query->setFieldQuery($field, 'Basel New');
        $query->setFieldOperator($field, $operator);
        $query->setFieldBoost($field, 1.2);

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(4, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchSetFieldBoostWithString() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';
        $operator = 'or';

        $query = new Match();
        $query->setFieldQuery($field, 'Basel New');
        $query->setFieldOperator($field, $operator);
        $query->setFieldBoost($field, 1.2);

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(4, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchZeroTerm() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $query = new Match();
        $query->setFieldQuery('name', '');
        $query->setFieldZeroTermsQuery('name', Match::ZERO_TERM_ALL);

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchPhrase() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';
        $type = 'phrase';

        $query = new Match();
        $query->setFieldQuery($field, 'New York');
        $query->setFieldType($field, $type);

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchPhraseAlias() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';

        $query = new MatchPhrase();
        $query->setFieldQuery($field, 'New York');

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchPhrasePrefix() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';
        $type = 'phrase_prefix';

        $query = new Match();
        $query->setFieldQuery($field, 'New');
        $query->setFieldType($field, $type);

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testMatchPhrasePrefixAlias() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'Basel-Stadt')),
            new Document('2', array('name' => 'New York')),
            new Document('3', array('name' => 'New Hampshire')),
            new Document('4', array('name' => 'Basel Land')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $field = 'name';

        $query = new MatchPhrasePrefix();
        $query->setFieldQuery($field, 'New');

        $resultSet = $index->search($query)->getWaitHandle()->join();

        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group unit
     */
    public function testMatchFuzzinessType() : void
    {
        $field = 'test';
        $query = new Match();

        $fuzziness = 'AUTO';
        $query->setFieldFuzziness($field, $fuzziness);

        $parameters = $query->getParam($field);
        $this->assertEquals($fuzziness, /* UNSAFE_EXPR */ $parameters['fuzziness']);

        $fuzziness = 0.3;
        $query->setFieldFuzziness($field, $fuzziness);

        $parameters = $query->getParam($field);
        $this->assertEquals($fuzziness, /* UNSAFE_EXPR */ $parameters['fuzziness']);
    }

    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $match = new Match(null, 'values');
        $this->assertEquals(array('match' => Map {}), $match->toArray());

        $match = new Match('field', null);
        $this->assertEquals(array('match' => Map {}), $match->toArray());

        $match1 = new Match('field', 'values');
        $match2 = new Match();
        $match2->setField('field', 'values');
        $this->assertEquals($match1->toArray(), $match2->toArray());
    }
}
