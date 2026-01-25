<?php

/**
 * Bootstrap file for mPDF dependencies
 * This file must be included before any mPDF classes
 */

// Define PSR Log interfaces and classes (in dependency order)
require_once __DIR__ . '/PsrLog/Psr/Log/LogLevel.php';
require_once __DIR__ . '/PsrLog/Psr/Log/LoggerInterface.php';
require_once __DIR__ . '/PsrLog/Psr/Log/LoggerAwareInterface.php';
require_once __DIR__ . '/PsrLog/Psr/Log/LoggerAwareTrait.php';
require_once __DIR__ . '/PsrLog/Psr/Log/LoggerTrait.php';
require_once __DIR__ . '/PsrLog/Psr/Log/InvalidArgumentException.php';
require_once __DIR__ . '/PsrLog/Psr/Log/AbstractLogger.php';
require_once __DIR__ . '/PsrLog/Psr/Log/NullLogger.php';
