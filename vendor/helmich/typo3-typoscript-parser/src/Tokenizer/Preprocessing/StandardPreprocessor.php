<?php

declare (strict_types=1);
namespace RectorPrefix20210923\Helmich\TypoScriptParser\Tokenizer\Preprocessing;

/**
 * Helper class that provides the standard pre-processing behaviour
 *
 * @package Helmich\TypoScriptParser\Tokenizer\Preprocessing
 */
class StandardPreprocessor extends \RectorPrefix20210923\Helmich\TypoScriptParser\Tokenizer\Preprocessing\ProcessorChain
{
    public function __construct(string $eolChar = "\n")
    {
        $this->processors = [new \RectorPrefix20210923\Helmich\TypoScriptParser\Tokenizer\Preprocessing\UnifyLineEndingsPreprocessor($eolChar), new \RectorPrefix20210923\Helmich\TypoScriptParser\Tokenizer\Preprocessing\RemoveTrailingWhitespacePreprocessor($eolChar)];
    }
}
