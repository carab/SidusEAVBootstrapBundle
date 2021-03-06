<?php

namespace Sidus\EAVBootstrapBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Enabled a simple bootstrap datepicker on date widgets
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DatePickerType extends AbstractType
{
    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'time_type' => \IntlDateFormatter::NONE,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return DateTimePickerType::class;
    }
}
