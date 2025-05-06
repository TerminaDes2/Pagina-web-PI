<?php
// Configuración de Azure Translator
define('AZURE_TRANSLATOR_KEY', 'Xroey8iatE8DHsxAeZGm6GvK0ERLxLwueal1U62nkOIYKGaSGgL0JQQJ99BEACLArgHXJ3w3AAAbACOGUXvz');
define('AZURE_TRANSLATOR_REGION', 'southcentralus');
define('AZURE_TRANSLATOR_ENDPOINT', 'https://piequipo5.cognitiveservices.azure.com/');

// Configuración para la traducción automática
define('AZURE_TRANSLATOR_PATH', '/translate?api-version=3.0');
define('AZURE_TRANSLATOR_LANG', 'es');

// Config.php
return [
    'translator' => [
        'key'      => 'Xroey8iatE8DHsxAeZGm6GvK0ERLxLwueal1U62nkOIYKGaSGgL0JQQJ99BEACLArgHXJ3w3AAAbACOGUXvz',
        'endpoint' => 'https://api.cognitive.microsofttranslator.com',
        'location' => 'southcentralus'
    ]
];
?>
