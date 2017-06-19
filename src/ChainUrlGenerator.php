<?php

namespace Skalpa\Silex\Symfony\Routing;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;


/**
 * Generates URLs using other generators.
 */
class ChainUrlGenerator implements UrlGeneratorInterface
{
    private $context;
    private $generators;

    /**
     * @param UrlGeneratorInterface[] $generators
     * @param RequestContext          $context
     */
    public function __construct(array $generators, RequestContext $context)
    {
        foreach ($generators as $generator) {
            if (!$generator instanceof UrlGeneratorInterface) {
                throw new \InvalidArgumentException(sprintf('Invalid URL generator. Expected an instance of %s, got %s.', UrlGeneratorInterface::class, is_object($generator) ? get_class($generator) : gettype($generator)));
            }
            $generator->setContext($context);
        }
        $this->generators = $generators;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        foreach ($this->generators as $generator) {
            $generator->setContext($context);
        }
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        /** @var RouteNotFoundException $notFound */
        $notFound = null;

        foreach ($this->generators as $generator) {
            try {
                return $generator->generate($name, $parameters, $referenceType);
            } catch (RouteNotFoundException $e) {
                $notFound = $e;
            }
        }
        throw $notFound;
    }
}
