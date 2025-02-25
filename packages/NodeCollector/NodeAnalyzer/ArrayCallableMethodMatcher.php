<?php

declare (strict_types=1);
namespace Rector\NodeCollector\NodeAnalyzer;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeWithClassName;
use Rector\Core\PhpParser\Node\Value\ValueResolver;
use Rector\Core\ValueObject\MethodName;
use Rector\NodeCollector\ValueObject\ArrayCallable;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;
final class ArrayCallableMethodMatcher
{
    /**
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    /**
     * @var \Rector\Core\PhpParser\Node\Value\ValueResolver
     */
    private $valueResolver;
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    public function __construct(\Rector\NodeNameResolver\NodeNameResolver $nodeNameResolver, \Rector\NodeTypeResolver\NodeTypeResolver $nodeTypeResolver, \Rector\Core\PhpParser\Node\Value\ValueResolver $valueResolver, \PHPStan\Reflection\ReflectionProvider $reflectionProvider)
    {
        $this->nodeNameResolver = $nodeNameResolver;
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->valueResolver = $valueResolver;
        $this->reflectionProvider = $reflectionProvider;
    }
    /**
     * Matches array like: "[$this, 'methodName']" → ['ClassName', 'methodName']
     * Returns back value $array when unknown method of callable used, eg: [$this, $other]
     * @see https://github.com/rectorphp/rector-src/pull/908
     * @see https://github.com/rectorphp/rector-src/pull/909
     * @return null|\PhpParser\Node\Expr\Array_|\Rector\NodeCollector\ValueObject\ArrayCallable
     */
    public function match(\PhpParser\Node\Expr\Array_ $array)
    {
        $arrayItems = $array->items;
        if (\count($arrayItems) !== 2) {
            return null;
        }
        if ($this->shouldSkipNullItems($array)) {
            return null;
        }
        /** @var ArrayItem[] $items */
        $items = $array->items;
        // $this, self, static, FQN
        $firstItemValue = $items[0]->value;
        $calleeType = $firstItemValue instanceof \PhpParser\Node\Expr\ClassConstFetch ? $this->resolveClassConstFetchType($firstItemValue) : $this->nodeTypeResolver->resolve($firstItemValue);
        if (!$calleeType instanceof \PHPStan\Type\TypeWithClassName) {
            return null;
        }
        $values = $this->valueResolver->getValue($array);
        if ($values === []) {
            return $array;
        }
        if ($this->shouldSkipAssociativeArray($values)) {
            return null;
        }
        $secondItemValue = $items[1]->value;
        if (!$secondItemValue instanceof \PhpParser\Node\Scalar\String_) {
            return null;
        }
        if ($this->isCallbackAtFunctionNames($array, ['register_shutdown_function', 'forward_static_call'])) {
            return null;
        }
        $className = $calleeType->getClassName();
        $methodName = $secondItemValue->value;
        if ($methodName === \Rector\Core\ValueObject\MethodName::CONSTRUCT) {
            return null;
        }
        return new \Rector\NodeCollector\ValueObject\ArrayCallable($firstItemValue, $className, $methodName);
    }
    private function shouldSkipNullItems(\PhpParser\Node\Expr\Array_ $array) : bool
    {
        if ($array->items[0] === null) {
            return \true;
        }
        return $array->items[1] === null;
    }
    /**
     * @param mixed $values
     */
    private function shouldSkipAssociativeArray($values) : bool
    {
        if (!\is_array($values)) {
            return \false;
        }
        $keys = \array_keys($values);
        return $keys !== [0, 1] && $keys !== [1];
    }
    /**
     * @param string[] $functionNames
     */
    private function isCallbackAtFunctionNames(\PhpParser\Node\Expr\Array_ $array, array $functionNames) : bool
    {
        $parentNode = $array->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::PARENT_NODE);
        if (!$parentNode instanceof \PhpParser\Node\Arg) {
            return \false;
        }
        $parentParentNode = $parentNode->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::PARENT_NODE);
        if (!$parentParentNode instanceof \PhpParser\Node\Expr\FuncCall) {
            return \false;
        }
        return $this->nodeNameResolver->isNames($parentParentNode, $functionNames);
    }
    /**
     * @return \PHPStan\Type\MixedType|\PHPStan\Type\ObjectType
     */
    private function resolveClassConstFetchType(\PhpParser\Node\Expr\ClassConstFetch $classConstFetch)
    {
        $classConstantReference = $this->valueResolver->getValue($classConstFetch);
        if ($classConstantReference === 'static') {
            $classConstantReference = $classConstFetch->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::CLASS_NAME);
        }
        // non-class value
        if (!\is_string($classConstantReference)) {
            return new \PHPStan\Type\MixedType();
        }
        if (!$this->reflectionProvider->hasClass($classConstantReference)) {
            return new \PHPStan\Type\MixedType();
        }
        $classReflection = $this->reflectionProvider->getClass($classConstantReference);
        $scope = $classConstFetch->getAttribute(\Rector\NodeTypeResolver\Node\AttributeKey::SCOPE);
        $hasConstruct = $classReflection->hasMethod(\Rector\Core\ValueObject\MethodName::CONSTRUCT);
        if ($hasConstruct) {
            $methodReflection = $classReflection->getMethod(\Rector\Core\ValueObject\MethodName::CONSTRUCT, $scope);
            $parametersAcceptor = \PHPStan\Reflection\ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
                if ($parameterReflection->getDefaultValue() === null) {
                    return new \PHPStan\Type\MixedType();
                }
            }
        }
        return new \PHPStan\Type\ObjectType($classConstantReference, null, $classReflection);
    }
}
