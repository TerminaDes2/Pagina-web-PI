<?php
// Configuración de Azure Translator - Verificamos si las constantes ya están definidas
if (!defined('AZURE_TRANSLATOR_KEY')) {
    define('AZURE_TRANSLATOR_KEY', 'GDaHkQHsv2Tbw3YC026AENYk8Cj9HQVTakgMtqfjovkQTgEl1zJEJQQJ99BEACLArgHXJ3w3AAAbACOGYqyI');
}
if (!defined('AZURE_TRANSLATOR_REGION')) {
    define('AZURE_TRANSLATOR_REGION', 'southcentralus');
}
if (!defined('AZURE_TRANSLATOR_ENDPOINT')) {
    define('AZURE_TRANSLATOR_ENDPOINT', 'https://api-southcentralus.cognitive.microsofttranslator.com/');
}
if (!defined('AZURE_TRANSLATOR_PATH')) {
    define('AZURE_TRANSLATOR_PATH', '/translate?api-version=3.0');
}
if (!defined('AZURE_TRANSLATOR_LANG')) {
    define('AZURE_TRANSLATOR_LANG', 'es');
}

// Devolvemos configuración como array para mantener compatibilidad con el código existente
return [
    'translator' => [
        'key'      => 'bedFkL5bZyqj9lsdC9wAtxagbaZLAFaPs2Y8f7t51A6ciP7igj5vJQQJ99BEACLArgHXJ3w3AAAbACOGXmXi',
        'endpoint' => 'https://api.cognitive.microsofttranslator.com/',
        'location' => 'southcentralus'
    ]
];
?>