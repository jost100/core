<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\SalesChannel;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SuccessResponse;

abstract class AbstractRemoveWishlistProductRoute
{
    abstract public function getDecorated(): AbstractRemoveWishlistProductRoute;

    /**
     * @deprecated tag:v6.4.0 - Parameter $customer will be mandatory in future implementation
     */
    abstract public function delete(string $productId, SalesChannelContext $context/*, CustomerEntity $customer*/): SuccessResponse;
}
