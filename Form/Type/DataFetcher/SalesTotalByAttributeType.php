<?php

namespace OpositaTest\Bundle\ReportBundle\Form\Type\DataFetcher;

use Sylius\Bundle\CoreBundle\DataFetcher\NumberOfOrdersDataFetcher;
use Sylius\Component\Core\Model\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Odiseo Team <team@odiseo.com.ar>
 */
class SalesTotalByAttributeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('attribute', 'choice', [
                'choices' => array('atributo 1'),
                'multiple' => false,
                'label' => 'Attribute',
            ])
            ->add('start', 'date', [
                'label' => 'sylius.form.report.user_registration.start',
            ])
            ->add('end', 'date', [
                'label' => 'sylius.form.report.user_registration.end',
            ])
            ->add('period', 'choice', [
                'choices' => NumberOfOrdersDataFetcher::getPeriodChoices(),
                'multiple' => false,
                'label' => 'sylius.form.report.user_registration.period',
            ])
            ->add('empty_records', 'checkbox', [
                'label' => 'sylius.form.report.user_registration.empty_records',
                'required' => false,
            ])
            ->add('options', 'sylius_product_option_choice', [
                'required' => false,
                'multiple' => true,
                'label' => 'sylius.form.product.options',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'opositatest_data_fetcher_sales_total_by_attribute';
    }
}