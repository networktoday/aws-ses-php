<?php

require_once __DIR__.'/../vendor/autoload.php';

// docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html
require_once __DIR__.'/config.php';
// In config.php, simply define AWS_CREDENTIALS_KEY and AWS_CREDENTIALS_SECRET
$client = new Aws\Ses\SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
    'credentials' => [
        'key'    => AWS_CREDENTIALS_KEY,
        'secret' => AWS_CREDENTIALS_SECRET
    ]
]);

// docs.aws.amazon.com/ses/latest/APIReference/API_GetSendQuota.html
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

// A slight modification of sesblog.amazon.com/post/TxKR75VKOYDS60
define('MIN_SLEEP_SECONDS', 1);
define('MAX_SLEEP_SECONDS', 600);
function getSleepDuration($currentTry) {
    // Exponential backoff.
    $currentSleep = MIN_SLEEP_MICROS * pow(2, $currentTry);
    return min($currentSleep, MAX_SLEEP_MICROS);
}

// docs.aws.amazon.com/ses/latest/APIReference/API_SendEmail.html
function buildEmailFormat($to, $subject, $textBody, $htmlBody='') {
    $emailBody = [
        'Text' => [
            'Charset' => 'UTF-8',
            'Data' => $textBody
        ]
    ];
    if ($htmlBody !== '') {
        $emailBody['Html'] = [
            'Charset' => 'UTF-8',
            'Data' => $htmlBody
        ];
    }
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
            'Body' => $emailBody
        ],
        'ReplyToAddresses.member.N' => array('wwwild<hello@wwwild.com>'),
        'ReturnPath' => 'wwwild<hello@wwwild.com>',
        'Source' => 'wwwild<hello@wwwild.com>',
    ];
}

// A slight modification of sesblog.amazon.com/post/TxKR75VKOYDS60
define('MAX_RETRIES', 10);
function sendEmail($to, $subject, $textBody, $htmlBody='') {
    global $client;
    $email = buildEmailFormat($to, $subject, $textBody, $htmlBody);
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
