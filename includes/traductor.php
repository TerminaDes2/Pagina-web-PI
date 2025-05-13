<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Translator {
    private $config;
    private $conn;
    
    public function __construct($dbConnection) {
        $config = include(__DIR__ . '/config.php');
        $this->config = $config['translator'];
        $this->conn = $dbConnection;
        
        if (!isset($_SESSION['idioma'])) {
            $_SESSION['idioma'] = 'es';
        }
    }

    public function traducirTexto($texto) {
        if (!isset($_SESSION['idioma']) || $_SESSION['idioma'] === 'es') {
            return $texto; // No traducir si el idioma es español o si no está configurado
        }
        $url = $this->config['endpoint'] . "/translate?api-version=3.0&to=" . $_SESSION['idioma'];
        
        $headers = [
            'Ocp-Apim-Subscription-Key: ' . $this->config['key'],
            'Ocp-Apim-Subscription-Region: ' . $this->config['location'], // Región actualizada
            'Content-Type: application/json'
        ];

        $body = json_encode([['Text' => $texto]]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Solo para pruebas locales
            CURLOPT_TIMEOUT        => 20 // Tiempo límite de 20 segundos para la solicitud cURL
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            error_log("Error cURL: " . curl_error($ch)); // Registrar el error
            curl_close($ch);
            return $texto; // Devolver el texto original
        }
        curl_close($ch);

        if ($httpCode == 200) {
            $result = json_decode($response, true);
            return $result[0]['translations'][0]['text'];
        }
        
        error_log("Error $httpCode: " . print_r($response, true)); // Registrar el error
        return $texto; // Devolver el texto original
    }

    public function cambiarIdioma($nuevoIdioma) {
        $_SESSION['idioma'] = $nuevoIdioma;
    }
    public function traducirHTML($html) {
        if (!isset($_SESSION['idioma']) || $_SESSION['idioma'] === 'es') {
            return $html; // No traducir si el idioma es español o si no está configurado
        }
        $url = $this->config['endpoint'] . "/translate?api-version=3.0&to=" . $_SESSION['idioma'] . "&textType=html";
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        // Traducir nodos de texto
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()');
        
        foreach ($textNodes as $node) {
            if (trim($node->nodeValue)) {
                $traducido = $this->traducirTexto($node->nodeValue);
                $node->nodeValue = htmlspecialchars_decode($traducido);
            }
        }
        
        // Devolver solo el body
        return $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
        if ($httpCode == 200) {
            $result = json_decode($response, true);
            return $result[0]['translations'][0]['text'];
        }
        
        return $html; // Fallback al contenido original
    }
    
    public function __($texto) {
        return $this->traducirTexto($texto);
    }
}

set_time_limit(300); // Aumenta el tiempo máximo de ejecución del script a 60 segundos