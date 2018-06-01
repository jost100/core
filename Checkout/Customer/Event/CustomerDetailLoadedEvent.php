<?php declare(strict_types=1);

namespace Shopware\Checkout\Customer\Event;

use Shopware\System\Touchpoint\Event\TouchpointBasicLoadedEvent;
use Shopware\Framework\Context;
use Shopware\Checkout\Customer\Aggregate\CustomerAddress\Event\CustomerAddressBasicLoadedEvent;
use Shopware\Checkout\Customer\Aggregate\CustomerGroup\Event\CustomerGroupBasicLoadedEvent;
use Shopware\Checkout\Customer\Collection\CustomerDetailCollection;
use Shopware\Checkout\Order\Event\OrderBasicLoadedEvent;
use Shopware\Checkout\Payment\Event\PaymentMethodBasicLoadedEvent;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Framework\Event\NestedEventCollection;

class CustomerDetailLoadedEvent extends NestedEvent
{
    public const NAME = 'customer.detail.loaded';

    /**
     * @var \Shopware\Framework\Context
     */
    protected $context;

    /**
     * @var CustomerDetailCollection
     */
    protected $customers;

    public function __construct(CustomerDetailCollection $customers, Context $context)
    {
        $this->context = $context;
        $this->customers = $customers;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCustomers(): CustomerDetailCollection
    {
        return $this->customers;
    }

    public function getEvents(): ?NestedEventCollection
    {
        $events = [];
        if ($this->customers->getGroups()->count() > 0) {
            $events[] = new CustomerGroupBasicLoadedEvent($this->customers->getGroups(), $this->context);
        }
        if ($this->customers->getDefaultPaymentMethods()->count() > 0) {
            $events[] = new PaymentMethodBasicLoadedEvent($this->customers->getDefaultPaymentMethods(), $this->context);
        }
        if ($this->customers->getTouchpoints()->count() > 0) {
            $events[] = new TouchpointBasicLoadedEvent($this->customers->getTouchpoints(), $this->context);
        }
        if ($this->customers->getLastPaymentMethods()->count() > 0) {
            $events[] = new PaymentMethodBasicLoadedEvent($this->customers->getLastPaymentMethods(), $this->context);
        }
        if ($this->customers->getDefaultBillingAddress()->count() > 0) {
            $events[] = new CustomerAddressBasicLoadedEvent($this->customers->getDefaultBillingAddress(), $this->context);
        }
        if ($this->customers->getDefaultShippingAddress()->count() > 0) {
            $events[] = new CustomerAddressBasicLoadedEvent($this->customers->getDefaultShippingAddress(), $this->context);
        }
        if ($this->customers->getAddresses()->count() > 0) {
            $events[] = new CustomerAddressBasicLoadedEvent($this->customers->getAddresses(), $this->context);
        }
        if ($this->customers->getOrders()->count() > 0) {
            $events[] = new OrderBasicLoadedEvent($this->customers->getOrders(), $this->context);
        }

        return new NestedEventCollection($events);
    }
}
