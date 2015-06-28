<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/config.php';

$client = new Aws\Ses\SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
    'credentials' => [
        'key'    => AWS_CREDENTIALS_KEY,
        'secret' => AWS_CREDENTIALS_SECRET
    ]
]);

function getSendQuota() {
    global $client;
    try {
        $result = $client->getSendQuota();
        return [
            'max_daily_send'    => $result['Max24HourSend'],
            'daily_send_so_far' => $result['SentLast24Hours'],
            'max_send_rate'     => $result['MaxSendRate']
        ];
    } catch (Exception $e) {
        if (method_exists($e, 'getAwsErrorCode')) {
            $errorMsg = $e->getAwsErrorCode();
        } else {
            $errorMsg = $e->getMessage();
        }
        return [
            'error' => $errorMsg
        ];
    }
}

/* Exponential backoff. */
define('MIN_SLEEP_SECONDS', 1); /* 1 second. */
define('MAX_SLEEP_SECONDS', 600); /* 10 minutes. */
function getSleepDuration($currentTry) {
    $currentSleep = MIN_SLEEP_MICROS * pow(2, $currentTry);
    return min($currentSleep, MAX_SLEEP_MICROS);
}

function buildEmailFormat($to, $subject, $body) {
    return [
        'Destination' => [
            // Amazon recommends one call to sendEmail per recipient.
            'ToAddresses' => array($to)
        ],
        'Message' => [
            'Subject' => [
                'Charset' => 'UTF-8',
                'Data' => $subject
            ],
            'Body' => [
                'Text' => [
                    'Charset' => 'UTF-8',
                    'Data' => $body
                ]
            ]
        ],
        'ReplyToAddresses.member.N' => array('wwwild<hello@wwwild.com>'),
        'ReturnPath' => 'wwwild<hello@wwwild.com>',
        'Source' => 'wwwild<hello@wwwild.com>',
    ];

}

define('MAX_RETRIES', 10);
function sendEmail($to, $subject, $body) {
    global $client;
    $email = buildEmailFormat($to, $subject, $body);
    $numRetries = MAX_RETRIES;
    while ($numRetries > 0) {
        try {
            $result = $client->sendEmail($email);
            return [
                'success' => true
            ];
        } catch (Aws\Ses\Exception\SesException $e) {
            $errorCode = $e->getAwsErrorCode();
            if ($errorCode === 'Throttling') {
                sleep(getSleepDuration(MAX_RETRIES - $numRetries));
            } else {
                return [
                    'error' => $errorCode
                ];
            }
        } catch (Exception $e) {
            if (method_exists($e, 'getAwsErrorCode')) {
                $errorMsg = $e->getAwsErrorCode();
            } else {
                // Substring so can fit in varchar(255).
                $errorMsg = substr($e->getMessage(), 0, 255);
            }
            return [
                'error' => $errorMsg
            ];
        }
        $numRetries--;
    }

    return [
        'error' => 'FAILURE_TO_SEND'
    ];
}
