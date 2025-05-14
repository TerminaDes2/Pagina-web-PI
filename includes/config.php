<?php
// Configuración de Azure Translator
define('AZURE_TRANSLATOR_KEY', 'GDaHkQHsv2Tbw3YC026AENYk8Cj9HQVTakgMtqfjovkQTgEl1zJEJQQJ99BEACLArgHXJ3w3AAAbACOGYqyI');
define('AZURE_TRANSLATOR_REGION', 'southcentralus');
define('AZURE_TRANSLATOR_ENDPOINT', 'https://api-southcentralus.cognitive.microsofttranslator.com/');

// Configuración para la traducción automática
define('AZURE_TRANSLATOR_PATH', '/translate?api-version=3.0');
define('AZURE_TRANSLATOR_LANG', 'es');

// Config.php
return [
    'translator' => [
        'key'      => 'bedFkL5bZyqj9lsdC9wAtxagbaZLAFaPs2Y8f7t51A6ciP7igj5vJQQJ99BEACLArgHXJ3w3AAAbACOGXmXi',
        'endpoint' => 'https://api.cognitive.microsofttranslator.com/',
        'location' => 'southcentralus'
    ]
];
?>