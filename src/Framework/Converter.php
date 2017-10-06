<?php

namespace AmaTeam\Image\Projection\Framework;

use AmaTeam\Image\Projection\API\ConverterInterface;
use AmaTeam\Image\Projection\API\SpecificationInterface;
use AmaTeam\Image\Projection\API\Type\ReaderInterface;
use AmaTeam\Image\Projection\API\Conversion\FilterInterface;
use AmaTeam\Image\Projection\Type\Registry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Converter implements ConverterInterface
{
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Registry $registry
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Registry $registry,
        LoggerInterface $logger = null
    ) {
        $this->registry = $registry;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * @param SpecificationInterface $source
     * @param SpecificationInterface[] $targets
     * @param FilterInterface[] $filters
     * @return Conversion[]
     */
    public function createConversions(
        SpecificationInterface $source,
        array $targets,
        ...$filters
    ) {
        $context = ['source' => $source, 'targets' => $targets];
        $message = 'Converting {source} to targets {targets}';
        $this->logger->debug($message, $context);
        $reader = $this->getReader($source);
        $pipelines = [];
        foreach ($targets as $target) {
            $pipelines[] = $this
                ->instantiateConversion($reader, $target, $filters);
        }
        return $pipelines;
    }

    /**
     * Creates conversion pipeline for single target.
     *
     * @param SpecificationInterface $source
     * @param SpecificationInterface $target
     * @param FilterInterface[] $filters
     * @return Conversion
     */
    public function createConversion(
        SpecificationInterface $source,
        SpecificationInterface $target,
        ...$filters
    ) {
        $context = ['source' => $source, 'target' => $target];
        $message = 'Converting {source} to target {target}';
        $this->logger->debug($message, $context);
        $reader = $this->getReader($source);
        return $this->instantiateConversion($reader, $target, $filters);
    }

    /**
     * @param SpecificationInterface $source
     * @return ReaderInterface
     */
    private function getReader(SpecificationInterface $source)
    {
        return $this
            ->registry
            ->getHandler($source->getType())
            ->read($source);
    }

    /**
     * @param ReaderInterface $source
     * @param SpecificationInterface $target
     * @param FilterInterface[] $filters
     * @return Conversion
     */
    private function instantiateConversion(
        ReaderInterface $source,
        SpecificationInterface $target,
        array $filters
    ) {
        $generator = $this
            ->registry
            ->getHandler($target->getType())
            ->createGenerator($source, $target, $filters);
        return new Conversion($target, $generator, $this->logger);
    }
}
