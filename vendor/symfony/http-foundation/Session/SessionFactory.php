<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210923\Symfony\Component\HttpFoundation\Session;

use RectorPrefix20210923\Symfony\Component\HttpFoundation\RequestStack;
use RectorPrefix20210923\Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;
// Help opcache.preload discover always-needed symbols
\class_exists(\RectorPrefix20210923\Symfony\Component\HttpFoundation\Session\Session::class);
/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class SessionFactory
{
    private $requestStack;
    private $storageFactory;
    private $usageReporter;
    public function __construct(\RectorPrefix20210923\Symfony\Component\HttpFoundation\RequestStack $requestStack, \RectorPrefix20210923\Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface $storageFactory, callable $usageReporter = null)
    {
        $this->requestStack = $requestStack;
        $this->storageFactory = $storageFactory;
        $this->usageReporter = $usageReporter;
    }
    public function createSession() : \RectorPrefix20210923\Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return new \RectorPrefix20210923\Symfony\Component\HttpFoundation\Session\Session($this->storageFactory->createStorage($this->requestStack->getMainRequest()), null, null, $this->usageReporter);
    }
}
