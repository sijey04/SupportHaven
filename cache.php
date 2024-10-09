<?php
function getCache($key) {
    return apcu_fetch($key);
}

function setCache($key, $value, $ttl = 3600) {
    apcu_store($key, $value, $ttl);
}

function clearCache($key) {
    apcu_delete($key);
}
?>