<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Translator {
    private $config;
    private $conn;
    private $cacheDir;

    public function __construct($dbConnection) {
        $config = include(__DIR__ . '/config.php');
        $this->config = $config['translator'];
        $this->conn = $dbConnection;
        $this->cacheDir = __DIR__ . '/cache'; // Directorio para almacenar la caché

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        if (!isset($_SESSION['idioma'])) {
            $_SESSION['idioma'] = 'es';
        }
    }

    private function getCacheKey($texto) {
        return $this->cacheDir . '/' . md5($texto . $_SESSION['idioma']) . '.cache';
    }

    private function getFromCache($texto) {
        $cacheKey = $this->getCacheKey($texto);
        if (file_exists($cacheKey)) {
            return file_get_contents($cacheKey);
        }
        return false;
    }

    private function saveToCache($texto, $traduccion) {
        $cacheKey = $this->getCacheKey($texto);
        file_put_contents($cacheKey, $traduccion);
    }

    public function traducirTexto($texto) {
        if (!isset($_SESSION['idioma']) || $_SESSION['idioma'] === 'es') {
            return $texto; // No traducir si el idioma es español o si no está configurado
        }

        // Verificar si la traducción ya está en caché
        $traduccionCache = $this->getFromCache($texto);
        if ($traduccionCache !== false) {
            return $traduccionCache;
        }

        $url = $this->config['endpoint'] . "/translate?api-version=3.0&to=" . $_SESSION['idioma'];
        $headers = [
            'Ocp-Apim-Subscription-Key: ' . $this->config['key'],
            'Ocp-Apim-Subscription-Region: ' . $this->config['location'],
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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 20
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log("Error cURL: " . curl_error($ch));
            curl_close($ch);
            return $texto;
        }
        curl_close($ch);

        if ($httpCode == 200) {
            $result = json_decode($response, true);
            $traduccion = $result[0]['translations'][0]['text'];
            $this->saveToCache($texto, $traduccion); // Guardar en caché
            return $traduccion;
        }

        error_log("Error $httpCode: " . print_r($response, true));
        return $texto;
    }

    public function cambiarIdioma($nuevoIdioma) {
        $_SESSION['idioma'] = $nuevoIdioma;
    }

    public function traducirHTML($html) {
        if (!isset($_SESSION['idioma']) || $_SESSION['idioma'] === 'es') {
            return $html;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()');

        foreach ($textNodes as $node) {
            if (trim($node->nodeValue)) {
                $traducido = $this->traducirTexto($node->nodeValue);
                $node->nodeValue = htmlspecialchars_decode($traducido);
            }
        }

        return $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
    }

    public function __($texto) {
        return $this->traducirTexto($texto);
    }
}

set_time_limit(300); // Aumenta el tiempo máximo de ejecución del script a 60 segundos