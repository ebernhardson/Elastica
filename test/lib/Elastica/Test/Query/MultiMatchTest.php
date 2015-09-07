<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\MultiMatch;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;
use Elastica\Type\Mapping;

class MultiMatchTest extends BaseTest
{
    private $index;
    private $multiMatch;

    private static array<array> $data = array(
        array('id' => 1, 'name' => 'Rodolfo', 'last_name' => 'Moraes',   'full_name' => 'Rodolfo Moraes'),
        array('id' => 2, 'name' => 'Tristan', 'last_name' => 'Maindron', 'full_name' => 'Tristan Maindron'),
        array('id' => 3, 'name' => 'Monique', 'last_name' => 'Maindron', 'full_name' => 'Monique Maindron'),
        array('id' => 4, 'name' => 'John',    'last_name' => 'not Doe',  'full_name' => 'John not Doe'),
    );

    /**
     * @group functional
     */
    public function testMinimumShouldMatch() : void
    {
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Tristan Maindron');
        $multiMatch->setFields(array('full_name', 'name'));
        $multiMatch->setMinimumShouldMatch('2<100%');
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testAndOperator() : void
    {
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Monique Maindron');
        $multiMatch->setFields(array('full_name', 'name'));
        $multiMatch->setOperator(MultiMatch::OPERATOR_AND);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testType() : void
    {
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Trist');
        $multiMatch->setFields(array('full_name', 'name'));
        $multiMatch->setType(MultiMatch::TYPE_PHRASE_PREFIX);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testFuzzy() : void
    {
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Tritsan'); // Misspell on purpose
        $multiMatch->setFields(array('full_name', 'name'));
        $multiMatch->setFuzziness(2.0);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());

        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Tritsan'); // Misspell on purpose
        $multiMatch->setFields(array('full_name', 'name'));
        $multiMatch->setFuzziness(0.0);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testFuzzyWithOptions1() : void
    {
        // Here Elasticsearch will not accept mispells
        // on the first 6 letters.
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Tritsan'); // Misspell on purpose
        $multiMatch->setFields(array('full_name', 'name'));
        $multiMatch->setFuzziness(2.0);
        $multiMatch->setPrefixLength(6);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testFuzzyWithOptions2() : void
    {
        // Here with a 'M' search we should hit 'Moraes' first
        // and then stop because MaxExpansion = 1.
        // If MaxExpansion was set to 2, we could hit "Maindron" too.
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('M');
        $multiMatch->setFields(array('name'));
        $multiMatch->setType(MultiMatch::TYPE_PHRASE_PREFIX);
        $multiMatch->setPrefixLength(0);
        $multiMatch->setMaxExpansions(1);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testZeroTerm() : void
    {
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('not'); // This is a stopword.
        $multiMatch->setFields(array('full_name', 'last_name'));
        $multiMatch->setZeroTermsQuery(MultiMatch::ZERO_TERM_NONE);
        $multiMatch->setAnalyzer('stops');
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(0, $resultSet->count());

        $multiMatch->setZeroTermsQuery(MultiMatch::ZERO_TERM_ALL);
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(4, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testBaseMultiMatch() : void
    {
        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Rodolfo');
        $multiMatch->setFields(array('name', 'last_name'));
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());

        $multiMatch = new MultiMatch();
        $multiMatch->setQuery('Moraes');
        $multiMatch->setFields(array('name', 'last_name'));
        $resultSet = $this->_getResults($multiMatch);

        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * Executes the query with the current multimatch.
     */
    private function _getResults(MultiMatch $multiMatch) : \Elastica\ResultSet
    {
        return $this->_generateIndex()->search(new Query($multiMatch))->getWaitHandle()->join();
    }

    /**
     * Builds an index for testing.
     */
    private function _generateIndex() : \Elastica\Index
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');

        $index->create(array(
            'analysis' => array(
                'analyzer' => array(
                    'noStops' => array(
                        'type' => 'standard',
                        'stopwords' => '_none_',
                    ),
                    'stops' => array(
                        'type' => 'standard',
                        'stopwords' => array('not'),
                    ),
                ),
            ),
        ), true)->getWaitHandle()->join();

        $type = $index->getType('test');

        $mapping = new Mapping($type, array(
            'name' => array('type' => 'string', 'store' => 'no', 'analyzer' => 'noStops'),
            'last_name' => array('type' => 'string', 'store' => 'no', 'analyzer' => 'noStops'),
            'full_name' => array('type' => 'string', 'store' => 'no', 'analyzer' => 'noStops'),
        ));

        $type->setMapping($mapping)->getWaitHandle()->join();

        foreach (self::$data as $key => $docData) {
            $type->addDocument(new Document((string) $key, $docData))->getWaitHandle()->join();
        }

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        return $index;
    }
}
