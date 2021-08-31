<?php

declare (strict_types=1);
namespace Rector\Nette\ValueObject;

use PhpParser\Node\Expr;
final class TemplateParametersAssigns
{
    /**
     * @var \Rector\Nette\ValueObject\AlwaysTemplateParameterAssign[]
     */
    private $templateParameterAssigns;
    /**
     * @var \Rector\Nette\ValueObject\ParameterAssign[]
     */
    private $conditionalTemplateParameterAssign;
    /**
     * @var \Rector\Nette\ValueObject\AlwaysTemplateParameterAssign[]
     */
    private $defaultChangeableTemplateParameterAssigns;
    /**
     * @param AlwaysTemplateParameterAssign[] $templateParameterAssigns
     * @param ParameterAssign[] $conditionalTemplateParameterAssign
     * @param AlwaysTemplateParameterAssign[] $defaultChangeableTemplateParameterAssigns
     */
    public function __construct(array $templateParameterAssigns, array $conditionalTemplateParameterAssign, array $defaultChangeableTemplateParameterAssigns)
    {
        $this->templateParameterAssigns = $templateParameterAssigns;
        $this->conditionalTemplateParameterAssign = $conditionalTemplateParameterAssign;
        $this->defaultChangeableTemplateParameterAssigns = $defaultChangeableTemplateParameterAssigns;
    }
    /**
     * @return ParameterAssign[]
     */
    public function getConditionalTemplateParameterAssign() : array
    {
        return $this->conditionalTemplateParameterAssign;
    }
    /**
     * @return string[]
     */
    public function getConditionalVariableNames() : array
    {
        $conditionalVariableNames = [];
        foreach ($this->conditionalTemplateParameterAssign as $conditionalTemplateParameterAssign) {
            $conditionalVariableNames[] = $conditionalTemplateParameterAssign->getParameterName();
        }
        return \array_unique($conditionalVariableNames);
    }
    /**
     * @return AlwaysTemplateParameterAssign[]
     */
    public function getTemplateParameterAssigns() : array
    {
        return $this->templateParameterAssigns;
    }
    /**
     * @return array<string, Expr>
     */
    public function getTemplateVariables() : array
    {
        $templateVariables = [];
        foreach ($this->templateParameterAssigns as $templateParameterAssign) {
            $templateVariables[$templateParameterAssign->getParameterName()] = $templateParameterAssign->getAssignedExpr();
        }
        foreach ($this->defaultChangeableTemplateParameterAssigns as $alwaysTemplateParameterAssign) {
            $templateVariables[$alwaysTemplateParameterAssign->getParameterName()] = $alwaysTemplateParameterAssign->getAssignedExpr();
        }
        return $templateVariables;
    }
    /**
     * @return AlwaysTemplateParameterAssign[]
     */
    public function getDefaultChangeableTemplateParameterAssigns() : array
    {
        return $this->defaultChangeableTemplateParameterAssigns;
    }
}
