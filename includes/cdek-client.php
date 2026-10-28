<?php

/** CDEK API v2 transport. Credentials never leave the server. */
class Dornott_Cdek_Client
{
    private $config;
    private $transport;
    private $token = '';

    public function __construct(array $config, $transport = null)
    {
        $this->config = $config;
        $this->transport = $transport ?: 'wp_remote_request';
    }

    private function failure($status = 0, $uncertain = false)
    {
        return ['ok' => false, 'status' => $status, 'data' => [], 'uncertain' => $uncertain,
            'message' => 'СДЭК временно недоступен. Попробуйте позже.'];
    }

    private function send($method, $path, $data, $auth = false)
    {
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        if (!in_array($base, ['https://api.cdek.ru/v2', 'https://api.edu.cdek.ru/v2'], true)) {
            return $this->failure();
        }
        $url = $base . '/' . $path;
        $args = ['method' => $method, 'timeout' => 8, 'redirection' => 0,
            'headers' => ['Accept' => 'application/json']];
        if ($auth) {
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $args['body'] = http_build_query($data);
        } else {
            $args['headers']['Authorization'] = 'Bearer ' . $this->token;
            if ($method === 'GET') {
                if ($data) $url .= '?' . http_build_query($data);
            } else {
                $args['headers']['Content-Type'] = 'application/json';
                $args['body'] = wp_json_encode($data);
            }
        }
        $response = call_user_func($this->transport, $url, $args);
        $creating = $method === 'POST' && $path === 'orders';
        if (is_wp_error($response)) return $this->failure(0, $creating);
        $status = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) return $this->failure($status, $creating);
        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'data' => $body,
            'total' => (string) wp_remote_retrieve_header($response, 'x-total-elements'),
            'uncertain' => $creating && ($status >= 500 || $status === 0),
            'message' => $status >= 200 && $status < 300 ? '' : 'Ошибка обращения к СДЭК (HTTP ' . $status . ').'];
    }

    private function authorize()
    {
        if (empty($this->config['client_id']) || empty($this->config['client_secret'])) return false;
        $result = $this->send('POST', 'oauth/token', ['grant_type' => 'client_credentials',
            'client_id' => $this->config['client_id'], 'client_secret' => $this->config['client_secret']], true);
        if (!$result['ok'] || empty($result['data']['access_token'])) return false;
        $this->token = (string) $result['data']['access_token'];
        return true;
    }

    public function request($method, $path, array $data = [])
    {
        if ($this->token === '' && !$this->authorize()) return $this->failure();
        $result = $this->send($method, $path, $data);
        // A rejected token cannot have created an order. Refresh only once.
        if ($result['status'] === 401 && $this->authorize()) $result = $this->send($method, $path, $data);
        return $result;
    }
}
