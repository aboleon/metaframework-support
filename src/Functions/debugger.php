<?php

use Illuminate\Database\Eloquent\Builder;

function d($var, ?string $varname = null): void
{
    echo '<div class="mfw-meta-parser" style=\'font-family: "Monaco", "Menlo", "Consolas", "Courier New", monospace;font-size: 14px;text-align: left;
    background: #f2f9ff;
    border: 1px solid #d2ecf6;padding: 2%;clear: both;\'><pre class="dumper" style="white-space: break-spaces;
    word-break: break-all;
    padding: 2%;
    color: #44606e;">';
    echo $varname ? '<span style="font-family: \'Monaco\', \'Menlo\', \'Consolas\', \'Courier New\', monospace;padding: 10px 16px; background: #c3ec94; display: inline-block; border-top: 1px dashed #808080;border-bottom: 1px dashed #808080;margin-bottom: 14px">' . $varname . '</span><br>' : null;
    $sep = '';

    if (is_object($var)) {
        $class = get_class($var);
        $strlen = strlen("Instance of : " . $class);
        for ($i = 0; $i < $strlen; ++$i) {
            $sep .= '-';
        }
        echo '<em>Instance of : ' . $class . '</em><br>' . $sep . '<br>';
        method_exists($class, 'toArray') ? print_r($var->toArray()) : print_r($var);
    } elseif (is_string($var)) {
        var_dump($var);
    } else {
        is_array($var) ? print_r($var) : var_dump($var);
    }
    echo '</pre></div>';
}

function de($var, $varname = null): void
{
    d($var, $varname);
    exit;
}

function dSql(Builder $query)
{

    $sql = $query->toSql();
    $bindings = $query->getBindings();

// Combine the bindings into the SQL query
    foreach ($bindings as $binding) {
        $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
        $sql = preg_replace('/\?/', $value, $sql, 1);
    }

// Output the SQL query with bindings
    d($sql);

}

function deSql(Builder $query)
{
    dSql($query);
    exit;
}

function get_variable_name(&$var): string
{
    $trace = debug_backtrace();
    $vLine = file(__FILE__)[$trace[0]['line'] - 1];
    preg_match('#\\$(\w+)#', $vLine, $matches);
    return $matches[1];
}