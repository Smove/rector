<?php

declare (strict_types=1);
namespace Rector\Php80\MatchAndRefactor\StrStartsWithMatchAndRefactor;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use Rector\Core\PhpParser\Comparing\NodeComparator;
use Rector\Core\PhpParser\Node\Value\ValueResolver;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\Php80\Contract\StrStartWithMatchAndRefactorInterface;
use Rector\Php80\NodeFactory\StrStartsWithFuncCallFactory;
use Rector\Php80\ValueObject\StrStartsWith;
final class SubstrMatchAndRefactor implements \Rector\Php80\Contract\StrStartWithMatchAndRefactorInterface
{
    /**
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    /**
     * @var \Rector\Core\PhpParser\Comparing\NodeComparator
     */
    private $nodeComparator;
    /**
     * @var \Rector\Php80\NodeFactory\StrStartsWithFuncCallFactory
     */
    private $strStartsWithFuncCallFactory;
    public function __construct(\Rector\NodeNameResolver\NodeNameResolver $nodeNameResolver, \Rector\Core\PhpParser\Node\Value\ValueResolver $valueResolver, \Rector\Core\PhpParser\Comparing\NodeComparator $nodeComparator, \Rector\Php80\NodeFactory\StrStartsWithFuncCallFactory $strStartsWithFuncCallFactory)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->valueResolver = $valueResolver;
        $this->nodeComparator = $nodeComparator;
        $this->strStartsWithFuncCallFactory = $strStartsWithFuncCallFactory;
    }
    /**
     * @param Identical|NotIdentical $binaryOp
     */
    public function match($binaryOp) : ?\Rector\Php80\ValueObject\StrStartsWith
    {
        $isPositive = $binaryOp instanceof \PhpParser\Node\Expr\BinaryOp\Identical;
        if ($binaryOp->left instanceof \PhpParser\Node\Expr\FuncCall && $this->nodeNameResolver->isName($binaryOp->left, 'substr')) {
            /** @var FuncCall $funcCall */
            $funcCall = $binaryOp->left;
            $haystack = $funcCall->args[0]->value;
            return new \Rector\Php80\ValueObject\StrStartsWith($funcCall, $haystack, $binaryOp->right, $isPositive);
        }
        if ($binaryOp->right instanceof \PhpParser\Node\Expr\FuncCall && $this->nodeNameResolver->isName($binaryOp->right, 'substr')) {
            /** @var FuncCall $funcCall */
            $funcCall = $binaryOp->right;
            $haystack = $funcCall->args[0]->value;
            return new \Rector\Php80\ValueObject\StrStartsWith($funcCall, $haystack, $binaryOp->left, $isPositive);
        }
        return null;
    }
    /**
     * @param \Rector\Php80\ValueObject\StrStartsWith $strStartsWith
     */
    public function refactorStrStartsWith($strStartsWith) : ?\PhpParser\Node
    {
        if ($this->isStrlenWithNeedleExpr($strStartsWith)) {
            return $this->strStartsWithFuncCallFactory->createStrStartsWith($strStartsWith);
        }
        if ($this->isHardcodedStringWithLNumberLength($strStartsWith)) {
            return $this->strStartsWithFuncCallFactory->createStrStartsWith($strStartsWith);
        }
        return null;
    }
    private function isStrlenWithNeedleExpr(\Rector\Php80\ValueObject\StrStartsWith $strStartsWith) : bool
    {
        $substrFuncCall = $strStartsWith->getFuncCall();
        if (!$this->valueResolver->isValue($substrFuncCall->args[1]->value, 0)) {
            return \false;
        }
        $secondFuncCallArgValue = $substrFuncCall->args[2]->value;
        if (!$secondFuncCallArgValue instanceof \PhpParser\Node\Expr\FuncCall) {
            return \false;
        }
        if (!$this->nodeNameResolver->isName($secondFuncCallArgValue, 'strlen')) {
            return \false;
        }
        /** @var FuncCall $strlenFuncCall */
        $strlenFuncCall = $substrFuncCall->args[2]->value;
        $needleExpr = $strlenFuncCall->args[0]->value;
        $comparedNeedleExpr = $strStartsWith->getNeedleExpr();
        return $this->nodeComparator->areNodesEqual($needleExpr, $comparedNeedleExpr);
    }
    private function isHardcodedStringWithLNumberLength(\Rector\Php80\ValueObject\StrStartsWith $strStartsWith) : bool
    {
        $substrFuncCall = $strStartsWith->getFuncCall();
        if (!$this->valueResolver->isValue($substrFuncCall->args[1]->value, 0)) {
            return \false;
        }
        $hardcodedStringNeedle = $strStartsWith->getNeedleExpr();
        if (!$hardcodedStringNeedle instanceof \PhpParser\Node\Scalar\String_) {
            return \false;
        }
        $lNumberLength = $substrFuncCall->args[2]->value;
        if (!$lNumberLength instanceof \PhpParser\Node\Scalar\LNumber) {
            return \false;
        }
        return $lNumberLength->value === \strlen($hardcodedStringNeedle->value);
    }
}
