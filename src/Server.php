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

        $stream = stream_socket_client('tcp://' . $this->host . ':' . $this->port, $errno, $errstr, 3);
        if (!$stream)
        {
            throw new \Exception('Could not connect: ' . $errstr);
        }

        try {
            stream_set_timeout($stream, 3);
            $header = fread($stream, 64);
            if ($header === FALSE) {
                throw new \Exception("Couldn't read header from remote host");
            }
            $header_parts = explode(' ', $header);

            if (count($header_parts) != 3) {
                throw new \Exception('Not a Votifier v2 server');
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
            if (fwrite($stream, $payload) === FALSE) {
                throw new \Exception("Couldn't write to remote host");
            }

            $response = fread($stream, 256);

            if (!$response) {
                throw new \Exception('Unable to read server response');
            }

            $result = json_decode($response);
            if ($result->status !== "ok") {
                throw new \Exception('Votifier server error: ' . $result->cause . ': ' . $result->error);
            }
        } finally {
            fclose($stream);
        }
    }
}