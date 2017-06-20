<?php

namespace Skalpa\Silex\Symfony\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Calls several request matchers until one successfully recognizes the request.
 */
class ChainRequestMatcher implements RequestMatcherInterface
{
    private $context;
    private $matchers;

    /**
     * @param RequestMatcherInterface[]|UrlMatcherInterface[] $matchers
     * @param RequestContext                                  $context
     */
    public function __construct(array $matchers, RequestContext $context)
    {
        foreach ($matchers as $matcher) {
            if (!$matcher instanceof RequestMatcherInterface && !$matcher instanceof UrlMatcherInterface) {
                throw new \InvalidArgumentException(sprintf('Invalid request matcher. Expected an instance of %s or %s, got %s.', RequestMatcherInterface::class, UrlMatcherInterface::class, is_object($matcher) ? get_class($matcher) : gettype($matcher)));
            }
        }
        $this->matchers = $matchers;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        /** @var ResourceNotFoundException $notFound */
        $notFound = null;
        /** @var MethodNotAllowedException $badMethod */
        $badMethod = null;

        foreach ($this->matchers as $matcher) {
            try {
                if ($matcher instanceof RequestMatcherInterface) {
                    return $matcher->matchRequest($request);
                } else {
                    $matcher->setContext($this->context);

                    return $matcher->match($request->getPathInfo());
                }
            } catch (ResourceNotFoundException $e) {
                $notFound = $e;
            } catch (MethodNotAllowedException $e) {
                $badMethod = $e;
            }
        }

        if (null !== $badMethod) {
            // If a matcher recognized the URL but not the method, this takes precedence
            throw $badMethod;
        }
        // Otherwise, re-throw the last ResourceNotFoundException
        throw $notFound;
    }
}
