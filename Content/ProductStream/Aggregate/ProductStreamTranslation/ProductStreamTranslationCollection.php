<?php declare(strict_types=1);

namespace Shopware\Core\Content\ProductStream\Aggregate\ProductStreamTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class ProductStreamTranslationCollection extends EntityCollection
{
    public function getProductStreamIds(): array
    {
        return $this->fmap(function (ProductStreamTranslationEntity $productStreamTranslation) {
            return $productStreamTranslation->getProductStreamId();
        });
    }

    public function filterByProductStreamId(string $id): self
    {
        return $this->filter(function (ProductStreamTranslationEntity $productStreamTranslation) use ($id) {
            return $productStreamTranslation->getProductStreamId() === $id;
        });
    }

    public function getLanguageIds(): array
    {
        return $this->fmap(function (ProductStreamTranslationEntity $productStreamTranslation) {
            return $productStreamTranslation->getLanguageId();
        });
    }

    public function filterByLanguageId(string $id): self
    {
        return $this->filter(function (ProductStreamTranslationEntity $productStreamTranslation) use ($id) {
            return $productStreamTranslation->getLanguageId() === $id;
        });
    }

    protected function getExpectedClass(): string
    {
        return ProductStreamTranslationEntity::class;
    }
}
