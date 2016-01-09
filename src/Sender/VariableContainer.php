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
}