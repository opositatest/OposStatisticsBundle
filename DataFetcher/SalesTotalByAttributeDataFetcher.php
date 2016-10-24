<?php

namespace OpositaTest\Bundle\ReportBundle\DataFetcher;

use Doctrine\DBAL\Query\QueryBuilder;
use OpositaTest\Bundle\ReportBundle\DataFetchers;

/**
 * Número de compras de un tipo de producto (un producto puede tener un atributo
 * cualquiera “XXXX”, saber cuantas compras de productos que tengan XXXX se han
 * hecho) y totales (con su importe)
 *
 * Ejemplo: El usuario elige la fecha y el atributo “ODISEO” entre todos los
 * disponibles, y verá como resultado el producto con atributo “ODISEO” se ha
 * comprado 70 veces entre el 1 de Enero de 2016 y el 15 de Marzo de 2016
 * con un importe total de $3.000
 *
 * @author Odiseo Team <team@odiseo.com.ar>
 */
class SalesTotalByAttributeDataFetcher extends TimePeriod
{
    /**
     * {@inheritdoc}
     */
    protected function getData(array $configuration = [])
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $baseCurrencyCode = $configuration['baseCurrency'] ? 'in '.$configuration['baseCurrency']->getCode() : '';
        $attributeId = $configuration['attribute'];

        $secondSelect = 'COUNT(o.id) as "Cantidad"';
        if($configuration['viewMode'] == 'total')
        {
            $secondSelect = 'TRUNCATE(SUM(o.total * o.exchange_rate)/ 100,2) as "total sum '.$baseCurrencyCode.'"';
        }
        $queryBuilder
            ->select('DATE(o.completed_at) as date', $secondSelect)
            ->from('sylius_order', 'o')
            ->leftJoin('o','sylius_order_item', 'oi', 'o.id = oi.order_id')
            ->leftJoin( 'oi','sylius_product_variant', 'v', 'oi.variant_id = v.id')
            ->leftJoin( 'v','sylius_product', 'p',  'v.product_id = p.id')
            ->leftJoin( 'p','sylius_product_attribute_value', 'av',  'p.id = av.product_id')
            ->leftJoin( 'av','sylius_product_attribute', 'a',  'a.id = av.attribute_id')
            ->where('o.completed_at IS NOT null')
            ->andWhere('a.id = :attributeId')
            ->setParameter('attributeId', $attributeId)
        ;

        $queryBuilder = $this->addTimePeriodQueryBuilder($queryBuilder, $configuration);

        return $queryBuilder
            ->execute()
            ->fetchAll()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return DataFetchers::SALES_TOTAL_BY_ATTRIBUTE;
    }
}
