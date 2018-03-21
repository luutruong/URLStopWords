<?php
/**
 * @license
 * Copyright 2017 TruongLuu. All Rights Reserved.
 */
namespace Truonglv\URLStopWords;

class Router extends XFCP_Router
{
    protected $urlStopWords_linkPrefix = null;
    protected $urlStopWords_maxWords = null;
    protected $urlStopWords_disallowWords = null;
    protected $urlStopWords_cache = [];

    public function buildFinalUrl($modifier, $routeUrl, array $parameters = [])
    {
        $output = parent::buildFinalUrl($modifier, $routeUrl, $parameters);
        if (isset($this->urlStopWords_cache[$output])) {
            return $this->urlStopWords_cache[$output];
        }

        $options = \XF::options();
        if ($this->urlStopWords_maxWords === null) {
            $this->urlStopWords_maxWords = $options->tl_URLStopWords_maxWords;
        }

        if ($this->urlStopWords_disallowWords === null) {
            $disallowWords = $options->tl_URLStopWords_disallowWords;

            if (!empty($disallowWords)) {
                $disallowWords = preg_split('/,/', $disallowWords, -1, PREG_SPLIT_NO_EMPTY);
                $disallowWords = array_unique($disallowWords);
            }

            $this->urlStopWords_disallowWords = $disallowWords;
        }
        
        if ($this->urlStopWords_linkPrefix === 'threads'
            && ($this->urlStopWords_maxWords > 0 || !empty($this->urlStopWords_disallowWords))
        ) {
            $segments = explode('/', $output);
            $total = count($segments);

            $titleIndex = -1;
            for ($i = 0; $i < $total; $i++) {
                if ($segments[$i] === 'threads') {
                    $nextIndex = $i + 1;
                    if ($nextIndex < $total && $segments[$nextIndex] === 'threads') {
                        continue;
                    }

                    $titleIndex = $nextIndex;

                    break;
                }
            }

            if ($titleIndex >= 0) {
                $titleParts = explode('-', $segments[$titleIndex]);

                $lastPart = end($titleParts);
                array_pop($titleParts);

                $lastParts = explode('.', $lastPart);
                $titleParts[] = $lastParts[0];

                if (!empty($this->urlStopWords_disallowWords)) {
                    foreach ($titleParts as $index => &$value) {
                        if (array_search($value, $this->urlStopWords_disallowWords) !== false) {
                            unset($titleParts[$index]);
                        }
                    }
                    unset($value);
                }

                while ($this->urlStopWords_maxWords > 0 && count($titleParts) > $this->urlStopWords_maxWords) {
                    array_pop($titleParts);
                }

                $newTitle = implode('-', $titleParts);
                $segments[$titleIndex] = $newTitle . '.' . $lastParts[1];
            }

            $newOutput = implode('/', $segments);
            $this->urlStopWords_cache[$output] = $newOutput;

            $output = $newOutput;
        }

        return $output;
    }

    protected function buildRouteUrl($prefix, array $route, $action, $data = null, array &$parameters = [])
    {
        $this->urlStopWords_linkPrefix = $prefix;

        return parent::buildRouteUrl($prefix, $route, $action, $data, $parameters);
    }
}
