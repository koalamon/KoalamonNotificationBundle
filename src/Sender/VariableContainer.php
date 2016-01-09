<?php

namespace Koalamon\NotificationBundle\Sender;

class VariableContainer
{
    private $variables = array();

    public function addVariable($key, $value)
    {
        $this->variables[$key] = $value;
    }

    public function replace($text)
    {
        foreach ($this->variables as $key => $variable) {
            $text = str_replace('${' . $key . '}', $variable, $text);
        }
        return $text;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function getTemplateVariables()
    {
        $vars = array();
        foreach ($this->variables as $key => $var) {
            $vars[str_replace('.', '_', $key)] = $var;
        }
        return $vars;
    }
}