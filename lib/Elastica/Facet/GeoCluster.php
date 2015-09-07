<?hh // strict
namespace Elastica\Facet;

use Indexish;

/**
 * Implements the Geo Cluster facet.
 *
 * @author Konstantin Nikiforov <konstantin.nikiforov@gmail.com>
 *
 * @link https://github.com/zenobase/geocluster-facet
 * @deprecated Facets are deprecated and will be removed in a future release. You are encouraged to migrate to aggregations instead.
 */
class GeoCluster extends AbstractFacet
{
    /**
     * @param string $fieldName
     *
     * @return $this
     */
    public function setField(string $fieldName) : this
    {
        $this->setParam('field', $fieldName);

        return $this;
    }

    /**
     * @param float $factor
     *
     * @return $this
     */
    public function setFactor(float $factor) : this
    {
        $this->setParam('factor', $factor);

        return $this;
    }

    /**
     * @param bool $showIds
     *
     * @return $this
     */
    public function setShowIds(bool $showIds) : this
    {
        $this->setParam('showIds', $showIds);

        return $this;
    }

    /**
     * Creates the full facet definition, which includes the basic
     * facet definition of the parent.
     *
     * @see \Elastica\Facet\AbstractFacet::toArray()
     *
     * @throws \Elastica\Exception\InvalidException When the right fields haven't been set.
     *
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $this->_setFacetParam('geo_cluster', $this->_params);

        return parent::toArray();
    }
}
