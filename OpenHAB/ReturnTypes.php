<?php
//special return types
//import: use ValueOrError, ValueBoolean, ValueNumber, ValueString, ValueArray;
class ValueOrError
{
    public $value = NULL, $trigger = '', $notFound = false, $error = false, $errorText = '', $errorCode = '';
    const ERR__DB_ERROR = 'db_error';
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    public function __invoke($value)
    {
        return $this->setValue($value);
    } //$r = new ValueOrError; $r($value);
    public function trigger($str)
    {
        $this->trigger = $str;
        return $this;
    }
    public function notFound()
    {
        $this->notFound = true;
        return $this;
    }
    public function getDump(): string
    {
        return implode("; ", array_filter([
            $this->error ? 'FAIL: ' . $this->errorText . ($this->errorCode === '' ? '' : ' (' . $this->errorCode . ')') : 'SUCCESS',
            $this->notFound ? 'not found' : false,
            is_null($this->value) ? false : json_encode($this->value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $this->error && $this->trigger ? $this->trigger : false,
        ]));
    }
    public function __toString()
    {
        return $this->getDump();
    } //echo (new ValueOrError);
    public function dbError($errorText, $trigger = '')
    {
        $this->error($errorText, self::ERR__DB_ERROR, $trigger);
        return $this;
    }
    public function error($errorText, $errorCode = '', $trigger = '')
    {
        $this->error = true;
        $this->errorText = $errorText;
        $this->errorCode = $errorCode;
        if ($trigger) {
            $this->trigger = $trigger;
        } else {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            //$this->trigger = json_encode($bt[0], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $class = empty($bt[1]['class']) ? '' : $bt[1]['class'] . '::';
            $func = empty($bt[1]['function']) ? '' : $bt[1]['function'];
            $line = empty($bt[0]['line']) ? '' : ':' . $bt[0]['line'];
            $this->trigger = "$class$func$line";
        }
        return $this;
    }
}
class ValueArray extends ValueOrError
{
    public function notFound()
    {
        $this->notFound = true;
        $this->value = [];
        return $this;
    }
}
class ValueBoolean extends ValueOrError
{
    public function setValue($success)
    {
        $this->value = !empty($success);
        return $this;
    }
    public function success()
    {
        $this->value = true;
        return $this;
    }
}
class ValueNumber extends ValueOrError
{
    public function setInteger($n)
    {
        $this->value = intval($n);
        return $this;
    }
    public function setFloat($n)
    {
        $this->value = floatval($n);
        return $this;
    }
    public function __invoke($value)
    {
        if (is_float($value) || is_double($value)) {
            return $this->setFloat($value);
        }
        return $this->setInteger($value);
    }
}
class ValueString extends ValueOrError
{
    public function setValue($string)
    {
        $this->value = (string) $string;
        return $this;
    }
}
