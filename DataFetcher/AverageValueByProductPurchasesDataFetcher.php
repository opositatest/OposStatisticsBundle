<?php

namespace Opos\Bundle\ReportBundle\DataFetcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Opos\Bundle\ReportBundle\DataFetchers;

/**
 * Valor promedio del valor de un tipo de producto de todas las compras completadas
 *
 * EJ: tiempo medio de subscripcion
 *
 * @author Odiseo Team <team@odiseo.com.ar>
 */
class AverageValueByProductPurchasesDataFetcher extends TimePeriod
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getData(array $configuration = [])
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $queryBuilder
            ->select('DATE(o.completed_at) as date', 'av.integer_value as "Average Value"')
            ->from('sylius_order', 'o')
            ->leftJoin('o','sylius_order_item', 'oi', 'o.id = oi.order_id')
            ->leftJoin( 'oi','sylius_product_variant', 'v', 'oi.variant_id = v.id')
            ->leftJoin( 'v','sylius_product', 'p',  'v.product_id = p.id')
            ->leftJoin( 'p','sylius_product_attribute_value', 'av',  'p.id = av.product_id')
            ->leftJoin( 'av','sylius_product_attribute', 'a',  'a.id = av.attribute_id')
            ->where('o.completed_at IS NOT null')
            ->andWhere('av.integer_value IS NOT null')
        ;
        if(isset($configuration['taxons'])) {
            foreach ($configuration['taxons'] as $taxon) {
                $queryBuilder
                    ->andWhere('p.main_taxon_id = :id')
                    ->setParameter('id', $taxon->getId());
            }
        }

        if(isset($configuration['attributes'])) {
            foreach ($configuration['attribute'] as $attributeId) {
                $queryBuilder
                    ->andWhere('a.id = :attributeId')
                    ->setParameter('attributeId', $attributeId);
            }
        }

        $queryBuilder = $this->addTimePeriodQueryBuilder($queryBuilder, $configuration);

        $ordersCompleted = $queryBuilder->execute()->fetchAll();

        return $this->getMediaResults($ordersCompleted, $configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return DataFetchers::AVERAGE_VALUE_BY_PRODUCT_PURCHASES;
    }
}