<?php

namespace Skalpa\Silex\Symfony\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Calls several request matchers until one successfully recognizes the request.
 */
class ChainRequestMatcher implements RequestMatcherInterface
{
    private $matchers;

    /**
     * @param RequestMatcherInterface[]|UrlMatcherInterface[] $matchers
     */
    public function __construct(array $matchers)
    {
        foreach ($matchers as $matcher) {
            if (!$matcher instanceof RequestMatcherInterface && !$matcher instanceof UrlMatcherInterface) {
                throw new \InvalidArgumentException(sprintf('Invalid request matcher. Expected an instance of %s or %s, got %s.', RequestMatcherInterface::class, UrlMatcherInterface::class, is_object($matcher) ? get_class($matcher) : gettype($matcher)));
            }
        }
        $this->matchers = $matchers;
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
