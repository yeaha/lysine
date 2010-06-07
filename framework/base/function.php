<?php
function app() {
    return Ly_Application::instance();
}

function cfg() {
    $args = func_get_args();
    return call_user_func_array(array(app(), 'getConfig'), $args);
}

function req() {
    return Ly_Request::instance();
}

function render_view($file, array $vars = null) {
    static $instance;
    if (!$instance) $instance = new Ly_View_Render(cfg('view'));

    return $instance->reset()->fetch($file, $vars);
}

/**
 * 根据key路径，在array中找出结果
 * 如果key不存在，返回false
 *
 * @param array $target
 * @param array $path
 * @return mixed
 */
function array_spider(array $target, array $path = array()) {
    while (list(, $key) = each($path)) {
        if (!is_array($target)) return false;
        if (!array_key_exists($key, $target)) return false;
        $target = $target[$key];
    }

    return $target;
}
