<?php

namespace Opos\Bundle\ReportBundle\DataFetcher;

use Doctrine\DBAL\Query\QueryBuilder;
use Opos\Bundle\ReportBundle\DataFetchers;

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
        $attributesValue = $configuration['attributes'];
        $buyback = $configuration['buyback'];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $baseCurrencyCode = $configuration['baseCurrency'] ? 'in '.$configuration['baseCurrency']->getCode() : '';
        $secondSelect = 'COUNT(o.id) as "Cantidad"';
        if($configuration['viewMode'] == 'total')
        {
            $secondSelect = 'TRUNCATE((o.total * o.exchange_rate)/100,2) as "total sum '.$baseCurrencyCode.'"';
        }

        $queryBuilder
            ->select('DATE(o.completed_at) as date', $secondSelect)
        ;

        $queryBuilder = $this->addTimePeriodQueryBuilder($queryBuilder, $configuration);
        $queryBuilder = $this->addQueriesByAttributeId($queryBuilder, $attributesValue);

        if($buyback)
        {
            // Fetch the orders by attribute
            $ordersFetched = $this->getBuybackOrdersWithAttribute($configuration);

            // validacion - forzar a que no traiga ningun valor si no tuvo recompras
            if(count($ordersFetched)<=0)$ordersFetched = array(1=>0);

            $queryBuilder->andWhere($queryBuilder->expr()->in('o.id', $ordersFetched));
        }


        return $queryBuilder
            ->execute()
            ->fetchAll()
        ;
    }

    protected function getBuybackOrdersWithAttribute(array $configuration = [])
    {
        $attributesValue = $configuration['attributes'];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        //Get the orders to verify wich is a buyback
        $queryBuilder
            ->select('o.id as O_ID', 'c.id as C_ID', 'p.id P_ID')
        ;
        $queryBuilder = $this->addQueriesByAttributeId($queryBuilder, $attributesValue);

        $orders = $queryBuilder->execute()->fetchAll();

        $ordersFetched = [];
        $productBuybacks = [];
        foreach($orders as $order)
        {
            $value = $order['C_ID'].'_'.$order['P_ID'];
            if(in_array($value, $productBuybacks))
            {
                $ordersFetched[] = $order['O_ID'];
            }
            $productBuybacks[] = $value;
        }

        return $ordersFetched;
    }

    protected function addQueriesByAttributeId(QueryBuilder $queryBuilder, $attributesValue)
    {
        $queryBuilder
            ->from('sylius_order', 'o')
            ->leftJoin('o','sylius_customer', 'c', 'c.id = o.customer_id')
            ->leftJoin('o','sylius_order_item', 'oi', 'o.id = oi.order_id')
            ->leftJoin( 'oi','sylius_product_variant', 'v', 'oi.variant_id = v.id')
            ->leftJoin( 'v','sylius_product', 'p',  'v.product_id = p.id')
            ->leftJoin( 'p','sylius_product_attribute_value', 'av',  'p.id = av.product_id')
            ->leftJoin( 'av','sylius_product_attribute', 'a',  'a.id = av.attribute_id')
            ->andWhere('o.completed_at IS NOT null');

        if(isset($attributesValue)){
            foreach($attributesValue as $attributeId)
            {
                $queryBuilder
                    ->andWhere('a.id = :attributeId')
                    ->setParameter('attributeId', $attributeId)
                ;
            }
        }
        return $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return DataFetchers::SALES_TOTAL_BY_ATTRIBUTE;
    }
}
