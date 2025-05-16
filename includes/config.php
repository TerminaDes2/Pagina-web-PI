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
        'key'      => 'GDaHkQHsv2Tbw3YC026AENYk8Cj9HQVTakgMtqfjovkQTgEl1zJEJQQJ99BEACLArgHXJ3w3AAAbACOGYqyI',
        'endpoint' => 'https://api.cognitive.microsofttranslator.com/',
        'location' => 'southcentralus'
    ]
];
?>
