<?php declare(strict_types=1);

namespace Shopware\Core\System\Country\Aggregate\CountryState\SalesChannel;

use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SalesChannelCountryStateDefinition extends CountryStateDefinition implements SalesChannelDefinitionInterface
{
    use SalesChannelDefinitionTrait;

    public static function processApiCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
        $criteria->addFilter(
            new EqualsFilter('country_state.country.salesChannels.id', $context->getSalesChannel()->getId())
        );
    }

    protected static function defineFields(): FieldCollection
    {
        $fields = parent::defineFields();

        self::decorateDefinitions($fields);

        return $fields;
    }
}
