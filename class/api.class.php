<?php
class API
{
    private $baseUrl;
    private $token;

    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function get($endpoint, $params = [])
    {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $this->request('GET', $endpoint);
    }

    public function post($endpoint, $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put($endpoint, $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete($endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    private function request($method, $endpoint, $data = null)
    {
        if (empty($this->token) && !empty($_SESSION['site'][APP_NAME]['token'])) {
            $this->setToken($_SESSION['site'][APP_NAME]['token']);
        }

        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if (!empty($this->token)) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,

            /*CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false*/
            /*
            download from: https://curl.se/docs/caextract.html
            "D:\WAMP2026\bin\php\php8.3.28\extras\ssl\cacert.pem"
            */
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        /*
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
        */
        /*
        echo $method . "<br>";
        echo $this->baseUrl . $endpoint . "<br>";
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
        */

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                'success' => false,
                'httpCode' => 0,
                'data' => [
                            'error' => 'API_UNAVAILABLE',
                            'message' => 'El servicio no está disponible temporalmente.'
                        ],
                'error' => curl_error($ch)
            ];
            exit;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $data = json_decode('', true);

        #echo $httpCode."<br />";
        #curl_close($ch);

        if ($httpCode === 403) {//si el token expira, cierra sesión y regresa al login
            $apiResponse = json_decode($response, true);
            $mensaje = $apiResponse['message'] ?? '';

            if (stripos($mensaje, 'expir') !== false) {
                //NO SALIR AQUÍ PORQUE LA RESPUESTA NO LLEGA AL SCRIPT ORIGINAL
                #unset($_SESSION['site'][APP_NAME]);
                #header('Location:'.WEB_URL.'SALIR');
                return [
                'success' => false,
                'httpCode' => $httpCode,
                'data' => [
                            'error' => 'SESSION_ENDED',
                            'message' => 'La sesión expiró.'
                        ]
                ];
                exit;
            }
        }

        if ($httpCode == 503) {
            
            return [
                'success' => false,
                'httpCode' => $httpCode,
                'data' => [
                            'error' => 'API_UNAVAILABLE',
                            'message' => 'El servicio no está disponible temporalmente.'
                        ]
            ];
            exit;
        }

        $data = json_decode($response, true);
        
        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'httpCode' => $httpCode,
            'data' => $data
        ];
    }

    public function login($email, $password)
    {
        return $this->post('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);
    }
}
?>