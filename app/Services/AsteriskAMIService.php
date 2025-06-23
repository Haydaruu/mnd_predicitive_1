<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class AsteriskAMIService
{
    private $socket;
    private $host;
    private $port;
    private $username;
    private $secret;
    private $timeout;
    private $connected = false;
    private $actionId = 0;

    public function __construct()
    {
        $this->host = config('asterisk.ami.host');
        $this->port = config('asterisk.ami.port');
        $this->username = config('asterisk.ami.username');
        $this->secret = config('asterisk.ami.secret');
        $this->timeout = config('asterisk.ami.timeout');
    }

    public function connect(): bool
    {
        try {
            Log::info('🔌 Attempting AMI connection', [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username
            ]);

            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            
            if (!$this->socket) {
                $error = socket_strerror(socket_last_error());
                Log::error('❌ Failed to create socket', ['error' => $error]);
                return false;
            }

            // Set socket options
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
                'sec' => config('asterisk.ami.read_timeout', 10), 
                'usec' => 0
            ]);
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, [
                'sec' => config('asterisk.ami.connect_timeout', 5), 
                'usec' => 0
            ]);

            $result = socket_connect($this->socket, $this->host, $this->port);
            
            if (!$result) {
                $error = socket_strerror(socket_last_error($this->socket));
                Log::error('❌ Failed to connect to Asterisk AMI', [
                    'host' => $this->host,
                    'port' => $this->port,
                    'error' => $error
                ]);
                return false;
            }

            // Read welcome message
            $welcome = $this->readResponse();
            Log::debug('📨 AMI Welcome message', ['message' => $welcome]);

            // Login
            if ($this->login()) {
                $this->connected = true;
                Log::info('✅ AMI Successfully connected and logged in');
                return true;
            }

            Log::error('❌ AMI Login failed');
            return false;

        } catch (Exception $e) {
            Log::error('❌ AMI Connection Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function login(): bool
    {
        $actionId = $this->getNextActionId();
        
        $loginAction = "Action: Login\r\n";
        $loginAction .= "ActionID: {$actionId}\r\n";
        $loginAction .= "Username: {$this->username}\r\n";
        $loginAction .= "Secret: {$this->secret}\r\n";
        $loginAction .= "\r\n";

        if (!$this->sendCommand($loginAction)) {
            return false;
        }
        
        $response = $this->readResponse();
        Log::debug('🔐 AMI Login response', ['response' => $response]);
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function originateCall(string $channel, string $context, string $extension, string $priority = '1', array $variables = [], int $timeout = 30000): bool
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }

        $actionId = $this->getNextActionId();
        
        $originateAction = "Action: Originate\r\n";
        $originateAction .= "ActionID: {$actionId}\r\n";
        $originateAction .= "Channel: {$channel}\r\n";
        $originateAction .= "Context: {$context}\r\n";
        $originateAction .= "Exten: {$extension}\r\n";
        $originateAction .= "Priority: {$priority}\r\n";
        $originateAction .= "Timeout: {$timeout}\r\n";
        $originateAction .= "CallerID: Predictive Dialer <1000>\r\n";
        $originateAction .= "Async: true\r\n";
        
        // Add custom variables
        foreach ($variables as $key => $value) {
            $originateAction .= "Variable: {$key}={$value}\r\n";
        }
        
        $originateAction .= "\r\n";

        if (!$this->sendCommand($originateAction)) {
            return false;
        }
        
        $response = $this->readResponse();
        
        Log::info('📞 AMI Originate Response', [
            'action_id' => $actionId,
            'channel' => $channel,
            'response' => $response
        ]);
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function hangupCall(string $channel): bool
    {
        if (!$this->connected) {
            return false;
        }

        $actionId = $this->getNextActionId();

        $hangupAction = "Action: Hangup\r\n";
        $hangupAction .= "ActionID: {$actionId}\r\n";
        $hangupAction .= "Channel: {$channel}\r\n";
        $hangupAction .= "\r\n";

        if (!$this->sendCommand($hangupAction)) {
            return false;
        }
        
        $response = $this->readResponse();
        
        Log::info('📴 AMI Hangup Response', [
            'action_id' => $actionId,
            'channel' => $channel,
            'response' => $response
        ]);
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function getChannelStatus(string $channel): array
    {
        if (!$this->connected) {
            return [];
        }

        $actionId = $this->getNextActionId();

        $statusAction = "Action: Status\r\n";
        $statusAction .= "ActionID: {$actionId}\r\n";
        $statusAction .= "Channel: {$channel}\r\n";
        $statusAction .= "\r\n";

        if (!$this->sendCommand($statusAction)) {
            return [];
        }
        
        $response = $this->readResponse();
        
        return $this->parseResponse($response);
    }

    public function getActiveChannels(): array
    {
        if (!$this->connected) {
            return [];
        }

        $actionId = $this->getNextActionId();

        $statusAction = "Action: Status\r\n";
        $statusAction .= "ActionID: {$actionId}\r\n";
        $statusAction .= "\r\n";

        if (!$this->sendCommand($statusAction)) {
            return [];
        }
        
        $response = $this->readResponse();
        
        return $this->parseMultipleEvents($response);
    }

    public function getAgentStatus(): array
    {
        if (!$this->connected) {
            return [];
        }

        $actionId = $this->getNextActionId();

        $agentAction = "Action: Agents\r\n";
        $agentAction .= "ActionID: {$actionId}\r\n";
        $agentAction .= "\r\n";

        if (!$this->sendCommand($agentAction)) {
            return [];
        }
        
        $response = $this->readResponse();
        
        return $this->parseMultipleEvents($response);
    }

    public function queueAdd(string $queue, string $interface, int $penalty = 0): bool
    {
        if (!$this->connected) {
            return false;
        }

        $actionId = $this->getNextActionId();

        $queueAction = "Action: QueueAdd\r\n";
        $queueAction .= "ActionID: {$actionId}\r\n";
        $queueAction .= "Queue: {$queue}\r\n";
        $queueAction .= "Interface: {$interface}\r\n";
        $queueAction .= "Penalty: {$penalty}\r\n";
        $queueAction .= "\r\n";

        if (!$this->sendCommand($queueAction)) {
            return false;
        }
        
        $response = $this->readResponse();
        
        return strpos($response, 'Response: Success') !== false;
    }

    public function queueRemove(string $queue, string $interface): bool
    {
        if (!$this->connected) {
            return false;
        }

        $actionId = $this->getNextActionId();

        $queueAction = "Action: QueueRemove\r\n";
        $queueAction .= "ActionID: {$actionId}\r\n";
        $queueAction .= "Queue: {$queue}\r\n";
        $queueAction .= "Interface: {$interface}\r\n";
        $queueAction .= "\r\n";

        if (!$this->sendCommand($queueAction)) {
            return false;
        }
        
        $response = $this->readResponse();
        
        return strpos($response, 'Response: Success') !== false;
    }

    private function sendCommand(string $command): bool
    {
        if (!$this->socket) {
            Log::error('❌ No socket connection available');
            return false;
        }

        $bytes = socket_write($this->socket, $command, strlen($command));
        
        if ($bytes === false) {
            $error = socket_strerror(socket_last_error($this->socket));
            Log::error('❌ Failed to send AMI command', [
                'command' => trim($command),
                'error' => $error
            ]);
            return false;
        }

        return true;
    }

    private function readResponse(): string
    {
        if (!$this->socket) {
            return '';
        }

        $response = '';
        $buffer = '';
        $maxAttempts = 100;
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $attempts++;
            
            $data = socket_read($this->socket, 1024);
            
            if ($data === false) {
                $error = socket_strerror(socket_last_error($this->socket));
                Log::warning('⚠️ Socket read error', ['error' => $error, 'attempts' => $attempts]);
                break;
            }
            
            if ($data === '') {
                // No more data
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

    private function getNextActionId(): string
    {
        return 'action_' . (++$this->actionId) . '_' . time();
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function disconnect(): void
    {
        if ($this->socket && $this->connected) {
            $logoffAction = "Action: Logoff\r\n\r\n";
            $this->sendCommand($logoffAction);
            socket_close($this->socket);
            $this->connected = false;
            Log::info('🔌 AMI Disconnected');
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}