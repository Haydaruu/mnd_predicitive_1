<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AsteriskAMIService
{
    private $socket;
    private $host;
    private $port;
    private $username;
    private $secret;
    private $connected = false;

    public function __construct()
    {
        $this->host = config('asterisk.ami.host', '192.168.18.66'); // IP laptop PBX
        $this->port = config('asterisk.ami.port', 5038);
        $this->username = config('asterisk.ami.username', 'admin');
        $this->secret = config('asterisk.ami.secret', 'amp111');
    }

    public function connect(): bool
    {
        try {
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            
            if (!$this->socket) {
                Log::error('AMI: Failed to create socket');
                return false;
            }

            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 10, 'usec' => 0]);
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 10, 'usec' => 0]);

            $result = socket_connect($this->socket, $this->host, $this->port);
            
            if (!$result) {
                Log::error('AMI: Failed to connect to ' . $this->host . ':' . $this->port);
                return false;
            }

            // Read welcome message
            $this->readResponse();

            // Login
            if ($this->login()) {
                $this->connected = true;
                Log::info('AMI: Successfully connected and logged in');
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('AMI Connection Error: ' . $e->getMessage());
            return false;
        }
    }

    private function login(): bool
    {
        $loginAction = "Action: Login\r\n";
        $loginAction .= "Username: {$this->username}\r\n";
        $loginAction .= "Secret: {$this->secret}\r\n";
        $loginAction .= "\r\n";

        socket_write($this->socket, $loginAction, strlen($loginAction));
        
        $response = $this->readResponse();
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function originateCall(string $channel, string $context, string $extension, string $priority = '1', array $variables = []): bool
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }

        $actionId = uniqid('call_');
        
        $originateAction = "Action: Originate\r\n";
        $originateAction .= "ActionID: {$actionId}\r\n";
        $originateAction .= "Channel: {$channel}\r\n";
        $originateAction .= "Context: {$context}\r\n";
        $originateAction .= "Exten: {$extension}\r\n";
        $originateAction .= "Priority: {$priority}\r\n";
        $originateAction .= "Timeout: 30000\r\n";
        $originateAction .= "CallerID: Predictive Dialer <1000>\r\n";
        
        // Add custom variables
        foreach ($variables as $key => $value) {
            $originateAction .= "Variable: {$key}={$value}\r\n";
        }
        
        $originateAction .= "\r\n";

        socket_write($this->socket, $originateAction, strlen($originateAction));
        
        $response = $this->readResponse();
        
        Log::info('AMI Originate Response: ' . $response);
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function hangupCall(string $channel): bool
    {
        if (!$this->connected) {
            return false;
        }

        $hangupAction = "Action: Hangup\r\n";
        $hangupAction .= "Channel: {$channel}\r\n";
        $hangupAction .= "\r\n";

        socket_write($this->socket, $hangupAction, strlen($hangupAction));
        
        $response = $this->readResponse();
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function getChannelStatus(string $channel): array
    {
        if (!$this->connected) {
            return [];
        }

        $statusAction = "Action: Status\r\n";
        $statusAction .= "Channel: {$channel}\r\n";
        $statusAction .= "\r\n";

        socket_write($this->socket, $statusAction, strlen($statusAction));
        
        $response = $this->readResponse();
        
        return $this->parseResponse($response);
    }

    public function getActiveChannels(): array
    {
        if (!$this->connected) {
            return [];
        }

        $statusAction = "Action: Status\r\n\r\n";

        socket_write($this->socket, $statusAction, strlen($statusAction));
        
        $response = $this->readResponse();
        
        return $this->parseMultipleEvents($response);
    }

    private function readResponse(): string
    {
        $response = '';
        $buffer = '';
        
        while (true) {
            $data = socket_read($this->socket, 1024);
            
            if ($data === false || $data === '') {
                break;
            }
            
            $buffer .= $data;
            
            // Check for end of response (double CRLF)
            if (strpos($buffer, "\r\n\r\n") !== false) {
                $response = $buffer;
                break;
            }
        }
        
        return $response;
    }

    private function parseResponse(string $response): array
    {
        $lines = explode("\r\n", $response);
        $parsed = [];
        
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $parsed[trim($key)] = trim($value);
            }
        }
        
        return $parsed;
    }

    private function parseMultipleEvents(string $response): array
    {
        $events = [];
        $currentEvent = [];
        $lines = explode("\r\n", $response);
        
        foreach ($lines as $line) {
            if (trim($line) === '') {
                if (!empty($currentEvent)) {
                    $events[] = $currentEvent;
                    $currentEvent = [];
                }
            } elseif (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $currentEvent[trim($key)] = trim($value);
            }
        }
        
        if (!empty($currentEvent)) {
            $events[] = $currentEvent;
        }
        
        return $events;
    }

    public function disconnect(): void
    {
        if ($this->socket && $this->connected) {
            $logoffAction = "Action: Logoff\r\n\r\n";
            socket_write($this->socket, $logoffAction, strlen($logoffAction));
            socket_close($this->socket);
            $this->connected = false;
            Log::info('AMI: Disconnected');
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}