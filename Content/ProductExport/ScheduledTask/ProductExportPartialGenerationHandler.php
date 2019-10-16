<?php declare(strict_types=1);

namespace Shopware\Core\Content\ProductExport\ScheduledTask;

use Shopware\Core\Content\ProductExport\ProductExportEntity;
use Shopware\Core\Content\ProductExport\Service\ProductExportFileHandlerInterface;
use Shopware\Core\Content\ProductExport\Service\ProductExportGeneratorInterface;
use Shopware\Core\Content\ProductExport\Service\ProductExportRendererInterface;
use Shopware\Core\Content\ProductExport\Struct\ExportBehavior;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Shopware\Core\Framework\Translation\Translator;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductExportPartialGenerationHandler extends AbstractMessageHandler
{
    /** @var SalesChannelContextFactory */
    private $salesChannelContextFactory;

    /** @var EntityRepository */
    private $productExportRepository;

    /** @var ProductExportGeneratorInterface */
    private $productExportGenerator;

    /** @var int */
    private $readBufferSize;

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var ProductExportFileHandlerInterface */
    private $productExportFileHandler;

    /** @var ProductExportRendererInterface */
    private $productExportRender;

    /** @var Translator */
    private $translator;

    /** @var SalesChannelContextServiceInterface */
    private $salesChannelContextService;

    public function __construct(
        ProductExportGeneratorInterface $productExportGenerator,
        SalesChannelContextFactory $salesChannelContextFactory,
        EntityRepository $productExportRepository,
        ProductExportFileHandlerInterface $productExportFileHandler,
        MessageBusInterface $messageBus,
        ProductExportRendererInterface $productExportRender,
        Translator $translator,
        SalesChannelContextServiceInterface $salesChannelContextService,
        int $readBufferSize
    ) {
        $this->productExportGenerator = $productExportGenerator;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->productExportRepository = $productExportRepository;
        $this->readBufferSize = $readBufferSize;
        $this->messageBus = $messageBus;
        $this->productExportFileHandler = $productExportFileHandler;
        $this->productExportRender = $productExportRender;
        $this->translator = $translator;
        $this->salesChannelContextService = $salesChannelContextService;
    }

    public static function getHandledMessages(): iterable
    {
        return [
            ProductExportPartialGeneration::class,
        ];
    }

    /**
     * @param ProductExportPartialGeneration $productExportPartialGeneration
     *
     * @throws SalesChannelNotFoundException
     * @throws InconsistentCriteriaIdsException
     */
    public function handle($productExportPartialGeneration): void
    {
        $criteria = new Criteria(array_filter([$productExportPartialGeneration->getProductExportId()]));
        $criteria
            ->addAssociation('salesChannel')
            ->addAssociation('salesChannelDomain.salesChannel')
            ->addAssociation('salesChannelDomain.language.locale')
            ->addAssociation('productStream.filters.queries')
            ->setLimit(1);

        $salesChannelContext = $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $productExportPartialGeneration->getSalesChannelId()
        );

        if ($salesChannelContext->getSalesChannel()->getTypeId() !== Defaults::SALES_CHANNEL_TYPE_STOREFRONT) {
            throw new SalesChannelNotFoundException();
        }

        $productExports = $this->productExportRepository->search($criteria, $salesChannelContext->getContext());

        if ($productExports->count() === 0) {
            return;
        }

        $exportBehavior = new ExportBehavior(
            false,
            false,
            true,
            false,
            false,
            $productExportPartialGeneration->getOffset()
        );

        /** @var ProductExportEntity $productExport */
        $productExport = $productExports->first();
        $exportResult = $this->productExportGenerator->generate(
            $productExport,
            $exportBehavior
        );

        $filePath = $this->productExportFileHandler->getFilePath($productExport, true);
        $this->productExportFileHandler->writeProductExportContent(
            $exportResult->getContent(),
            $filePath,
            $productExportPartialGeneration->getOffset() > 0
        );

        if ($productExportPartialGeneration->getOffset() + $this->readBufferSize < $exportResult->getTotal()) {
            $this->messageBus->dispatch(
                new ProductExportPartialGeneration(
                    $productExportPartialGeneration->getProductExportId(),
                    $productExportPartialGeneration->getSalesChannelId(),
                    $productExportPartialGeneration->getOffset() + $this->readBufferSize,
                    $productExportPartialGeneration->getOffset() + $this->readBufferSize * 2 >= $exportResult->getTotal()
                )
            );

            return;
        }

        $this->finalizeExport($productExport, $filePath);
    }

    private function finalizeExport(ProductExportEntity $productExport, string $filePath): void
    {
        $context = $this->salesChannelContextService->get(
            $productExport->getStorefrontSalesChannelId(),
            Uuid::randomHex(),
            $productExport->getSalesChannelDomain()->getLanguageId()
        );

        $this->translator->injectSettings(
            $productExport->getStorefrontSalesChannelId(),
            $productExport->getSalesChannelDomain()->getLanguageId(),
            $productExport->getSalesChannelDomain()->getLanguage()->getLocaleId(),
            $context->getContext()
        );

        $headerContent = $this->productExportRender->renderHeader($productExport, $context);
        $footerContent = $this->productExportRender->renderFooter($productExport, $context);
        $finalFilePath = $this->productExportFileHandler->getFilePath($productExport);

        $this->translator->resetInjection();

        $this->productExportFileHandler->finalizePartialProductExport(
            $filePath,
            $finalFilePath,
            $headerContent,
            $footerContent
        );
    }
}
