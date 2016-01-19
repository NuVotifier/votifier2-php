<?php

namespace Imaginarycode\Votifier2;

class Server
{
    private $host;
    private $port;
    private $token;

    /**
     * Server constructor.
     * @param $host
     * @param $port
     * @param $token
     */
    public function __construct($host, $port, $token)
    {
        $this->host = $host;
        $this->port = $port;
        $this->token = $token;
    }

    /**
     * Sends a vote.
     * @param Vote $vote
     * @throws \ErrorException
     */
    public function sendVote(Vote $vote)
    {
        if (!$vote)
        {
            throw new \InvalidArgumentException('vote not provided');
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!socket_connect($socket, $this->host, $this->port))
        {
            throw new \ErrorException('Could not connect: ' . socket_strerror(socket_last_error($socket)));
        }
        $header = socket_read($socket, 64, PHP_NORMAL_READ);

        if (!$header)
        {
            socket_close($socket);
            throw new \ErrorException('Remote host error: ' . socket_strerror(socket_last_error($socket)));
        }

        $header_parts = explode(' ', $header);

        if (count($header_parts) != 3)
        {
            socket_close($socket);
            throw new \ErrorException('Not a Votifier v2 server');
        }

        $challenge = substr($header_parts[2], 0, -1);

        $payload_json = json_encode(array(
            "username" => $vote->username,
            "serviceName" => $vote->serviceName,
            "timestamp" => $vote->timestamp,
            "address" => $vote->address,
            "challenge" => $challenge
        ));
        $signature = base64_encode(hash_hmac('sha256', $payload_json, $this->token, true));
        $message_json = json_encode(array("signature" => $signature, "payload" => $payload_json));

        $payload = pack('nn', 0x733a, strlen($message_json)) . $message_json;
        if (socket_write($socket, $payload) === FALSE)
        {
            socket_close($socket);
            throw new \ErrorException('Remote host error: ' . socket_strerror(socket_last_error($socket)));
        }

        $response = socket_read($socket, 256);

        if (!$response)
        {
            socket_close($socket);
            throw new \ErrorException('Remote host error: ' . socket_strerror(socket_last_error($socket)));
        }

        $result = json_decode($response);
        socket_close($socket);

        if ($result->status !== "ok")
        {
            throw new \ErrorException('Remote server error: ' . $result->cause . ': ' . $result->error);
        }
    }
}