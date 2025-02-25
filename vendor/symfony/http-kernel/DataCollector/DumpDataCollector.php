<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210923\Symfony\Component\HttpKernel\DataCollector;

use RectorPrefix20210923\Symfony\Component\HttpFoundation\Request;
use RectorPrefix20210923\Symfony\Component\HttpFoundation\RequestStack;
use RectorPrefix20210923\Symfony\Component\HttpFoundation\Response;
use RectorPrefix20210923\Symfony\Component\HttpKernel\Debug\FileLinkFormatter;
use RectorPrefix20210923\Symfony\Component\Stopwatch\Stopwatch;
use RectorPrefix20210923\Symfony\Component\VarDumper\Cloner\Data;
use RectorPrefix20210923\Symfony\Component\VarDumper\Cloner\VarCloner;
use RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\CliDumper;
use RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\DataDumperInterface;
use RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\HtmlDumper;
use RectorPrefix20210923\Symfony\Component\VarDumper\Server\Connection;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @final
 */
class DumpDataCollector extends \RectorPrefix20210923\Symfony\Component\HttpKernel\DataCollector\DataCollector implements \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\DataDumperInterface
{
    private $stopwatch;
    private $fileLinkFormat;
    private $dataCount = 0;
    private $isCollected = \true;
    private $clonesCount = 0;
    private $clonesIndex = 0;
    private $rootRefs;
    private $charset;
    private $requestStack;
    private $dumper;
    private $sourceContextProvider;
    /**
     * @param string|FileLinkFormatter|null       $fileLinkFormat
     * @param DataDumperInterface|Connection|null $dumper
     */
    public function __construct(\RectorPrefix20210923\Symfony\Component\Stopwatch\Stopwatch $stopwatch = null, $fileLinkFormat = null, string $charset = null, \RectorPrefix20210923\Symfony\Component\HttpFoundation\RequestStack $requestStack = null, $dumper = null)
    {
        $this->stopwatch = $stopwatch;
        $this->fileLinkFormat = ($fileLinkFormat ?: \ini_get('xdebug.file_link_format')) ?: \get_cfg_var('xdebug.file_link_format');
        $this->charset = (($charset ?: \ini_get('php.output_encoding')) ?: \ini_get('default_charset')) ?: 'UTF-8';
        $this->requestStack = $requestStack;
        $this->dumper = $dumper;
        // All clones share these properties by reference:
        $this->rootRefs = [&$this->data, &$this->dataCount, &$this->isCollected, &$this->clonesCount];
        $this->sourceContextProvider = $dumper instanceof \RectorPrefix20210923\Symfony\Component\VarDumper\Server\Connection && isset($dumper->getContextProviders()['source']) ? $dumper->getContextProviders()['source'] : new \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider($this->charset);
    }
    public function __clone()
    {
        $this->clonesIndex = ++$this->clonesCount;
    }
    /**
     * @param \Symfony\Component\VarDumper\Cloner\Data $data
     */
    public function dump($data)
    {
        if ($this->stopwatch) {
            $this->stopwatch->start('dump');
        }
        ['name' => $name, 'file' => $file, 'line' => $line, 'file_excerpt' => $fileExcerpt] = $this->sourceContextProvider->getContext();
        if ($this->dumper instanceof \RectorPrefix20210923\Symfony\Component\VarDumper\Server\Connection) {
            if (!$this->dumper->write($data)) {
                $this->isCollected = \false;
            }
        } elseif ($this->dumper) {
            $this->doDump($this->dumper, $data, $name, $file, $line);
        } else {
            $this->isCollected = \false;
        }
        if (!$this->dataCount) {
            $this->data = [];
        }
        $this->data[] = \compact('data', 'name', 'file', 'line', 'fileExcerpt');
        ++$this->dataCount;
        if ($this->stopwatch) {
            $this->stopwatch->stop('dump');
        }
    }
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param \Throwable|null $exception
     */
    public function collect($request, $response, $exception = null)
    {
        if (!$this->dataCount) {
            $this->data = [];
        }
        // Sub-requests and programmatic calls stay in the collected profile.
        if ($this->dumper || $this->requestStack && $this->requestStack->getMainRequest() !== $request || $request->isXmlHttpRequest() || $request->headers->has('Origin')) {
            return;
        }
        // In all other conditions that remove the web debug toolbar, dumps are written on the output.
        if (!$this->requestStack || !$response->headers->has('X-Debug-Token') || $response->isRedirection() || $response->headers->has('Content-Type') && \strpos($response->headers->get('Content-Type'), 'html') === \false || 'html' !== $request->getRequestFormat() || \false === \strripos($response->getContent(), '</body>')) {
            if ($response->headers->has('Content-Type') && \strpos($response->headers->get('Content-Type'), 'html') !== \false) {
                $dumper = new \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\HtmlDumper('php://output', $this->charset);
                $dumper->setDisplayOptions(['fileLinkFormat' => $this->fileLinkFormat]);
            } else {
                $dumper = new \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\CliDumper('php://output', $this->charset);
                if (\method_exists($dumper, 'setDisplayOptions')) {
                    $dumper->setDisplayOptions(['fileLinkFormat' => $this->fileLinkFormat]);
                }
            }
            foreach ($this->data as $dump) {
                $this->doDump($dumper, $dump['data'], $dump['name'], $dump['file'], $dump['line']);
            }
        }
    }
    public function reset()
    {
        if ($this->stopwatch) {
            $this->stopwatch->reset();
        }
        $this->data = [];
        $this->dataCount = 0;
        $this->isCollected = \true;
        $this->clonesCount = 0;
        $this->clonesIndex = 0;
    }
    /**
     * @internal
     */
    public function __sleep() : array
    {
        if (!$this->dataCount) {
            $this->data = [];
        }
        if ($this->clonesCount !== $this->clonesIndex) {
            return [];
        }
        $this->data[] = $this->fileLinkFormat;
        $this->data[] = $this->charset;
        $this->dataCount = 0;
        $this->isCollected = \true;
        return parent::__sleep();
    }
    /**
     * @internal
     */
    public function __wakeup()
    {
        parent::__wakeup();
        $charset = \array_pop($this->data);
        $fileLinkFormat = \array_pop($this->data);
        $this->dataCount = \count($this->data);
        foreach ($this->data as $dump) {
            if (!\is_string($dump['name']) || !\is_string($dump['file']) || !\is_int($dump['line'])) {
                throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__);
            }
        }
        self::__construct($this->stopwatch, \is_string($fileLinkFormat) || $fileLinkFormat instanceof \RectorPrefix20210923\Symfony\Component\HttpKernel\Debug\FileLinkFormatter ? $fileLinkFormat : null, \is_string($charset) ? $charset : null);
    }
    public function getDumpsCount() : int
    {
        return $this->dataCount;
    }
    /**
     * @param string $format
     * @param int $maxDepthLimit
     * @param int $maxItemsPerDepth
     */
    public function getDumps($format, $maxDepthLimit = -1, $maxItemsPerDepth = -1) : array
    {
        $data = \fopen('php://memory', 'r+');
        if ('html' === $format) {
            $dumper = new \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\HtmlDumper($data, $this->charset);
            $dumper->setDisplayOptions(['fileLinkFormat' => $this->fileLinkFormat]);
        } else {
            throw new \InvalidArgumentException(\sprintf('Invalid dump format: "%s".', $format));
        }
        $dumps = [];
        if (!$this->dataCount) {
            return $this->data = [];
        }
        foreach ($this->data as $dump) {
            $dumper->dump($dump['data']->withMaxDepth($maxDepthLimit)->withMaxItemsPerDepth($maxItemsPerDepth));
            $dump['data'] = \stream_get_contents($data, -1, 0);
            \ftruncate($data, 0);
            \rewind($data);
            $dumps[] = $dump;
        }
        return $dumps;
    }
    public function getName() : string
    {
        return 'dump';
    }
    public function __destruct()
    {
        if (0 === $this->clonesCount-- && !$this->isCollected && $this->dataCount) {
            $this->clonesCount = 0;
            $this->isCollected = \true;
            $h = \headers_list();
            $i = \count($h);
            \array_unshift($h, 'Content-Type: ' . \ini_get('default_mimetype'));
            while (0 !== \stripos($h[$i], 'Content-Type:')) {
                --$i;
            }
            if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], \true) && \stripos($h[$i], 'html')) {
                $dumper = new \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\HtmlDumper('php://output', $this->charset);
                $dumper->setDisplayOptions(['fileLinkFormat' => $this->fileLinkFormat]);
            } else {
                $dumper = new \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\CliDumper('php://output', $this->charset);
                if (\method_exists($dumper, 'setDisplayOptions')) {
                    $dumper->setDisplayOptions(['fileLinkFormat' => $this->fileLinkFormat]);
                }
            }
            foreach ($this->data as $i => $dump) {
                $this->data[$i] = null;
                $this->doDump($dumper, $dump['data'], $dump['name'], $dump['file'], $dump['line']);
            }
            $this->data = [];
            $this->dataCount = 0;
        }
    }
    private function doDump(\RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\DataDumperInterface $dumper, \RectorPrefix20210923\Symfony\Component\VarDumper\Cloner\Data $data, string $name, string $file, int $line)
    {
        if ($dumper instanceof \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\CliDumper) {
            $contextDumper = function ($name, $file, $line, $fmt) {
                if ($this instanceof \RectorPrefix20210923\Symfony\Component\VarDumper\Dumper\HtmlDumper) {
                    if ($file) {
                        $s = $this->style('meta', '%s');
                        $f = \strip_tags($this->style('', $file));
                        $name = \strip_tags($this->style('', $name));
                        if ($fmt && ($link = \is_string($fmt) ? \strtr($fmt, ['%f' => $file, '%l' => $line]) : $fmt->format($file, $line))) {
                            $name = \sprintf('<a href="%s" title="%s">' . $s . '</a>', \strip_tags($this->style('', $link)), $f, $name);
                        } else {
                            $name = \sprintf('<abbr title="%s">' . $s . '</abbr>', $f, $name);
                        }
                    } else {
                        $name = $this->style('meta', $name);
                    }
                    $this->line = $name . ' on line ' . $this->style('meta', $line) . ':';
                } else {
                    $this->line = $this->style('meta', $name) . ' on line ' . $this->style('meta', $line) . ':';
                }
                $this->dumpLine(0);
            };
            $contextDumper = $contextDumper->bindTo($dumper, $dumper);
            $contextDumper($name, $file, $line, $this->fileLinkFormat);
        } else {
            $cloner = new \RectorPrefix20210923\Symfony\Component\VarDumper\Cloner\VarCloner();
            $dumper->dump($cloner->cloneVar($name . ' on line ' . $line . ':'));
        }
        $dumper->dump($data);
    }
}
