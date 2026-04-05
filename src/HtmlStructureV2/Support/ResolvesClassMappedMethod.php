<?php

namespace Sc\Util\HtmlStructureV2\Support;

trait ResolvesClassMappedMethod
{
    /**
     * @param array<class-string, string> $methodMap
     */
    private function resolveClassMappedMethod(object $subject, array $methodMap): ?string
    {
        foreach ($methodMap as $class => $method) {
            if ($subject instanceof $class) {
                return $method;
            }
        }

        return null;
    }
}
