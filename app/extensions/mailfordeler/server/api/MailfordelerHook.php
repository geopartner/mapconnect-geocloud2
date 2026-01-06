<?php
/**
 * @author     Rene Borella <rgb@geopartner.dk>
 * @copyright  2025 Geopartner A/S
 *
 */

namespace app\extensions\mailfordeler\api;

use app\api\v4\AbstractApi;
use app\api\v4\AcceptableAccepts;
use app\api\v4\AcceptableContentTypes;
use app\api\v4\AcceptableMethods;
use app\api\v4\ApiInterface;
use app\inc\Input;
use app\extensions\mailfordeler\api\models\EmailEvent;

#[AcceptableMethods(['POST', 'HEAD', 'OPTIONS'])]
class MailfordelerHook extends AbstractApi implements ApiInterface
{
    #[AcceptableContentTypes(['application/json'])]
    #[AcceptableAccepts(['application/json', '*/*'])]
    public function post_index(): array
    {
        $body = Input::getBody();
        $data = json_decode($body, true);
        
        // Initialize EmailEvent handler
        $emailEvent = new EmailEvent();
        $result = false;
        $message = "Unknown event type";
        
        // Process the webhook data based on RecordType
        if (isset($data['RecordType'])) {
            try {
                // Use the EmailEvent class to process the webhook
                $result = $emailEvent->processWebhook($data);
                
                if ($result) {
                    $message = "Successfully processed {$data['RecordType']} event";
                } else {
                    $message = "Failed to process {$data['RecordType']} event";
                }
            } catch (\Exception $e) {
                $message = "Error processing webhook: " . $e->getMessage();
                $result = false;
            }
        }
        
        return [
            'success' => $result, 
            'message' => $message,
            'event_type' => $data['RecordType'] ?? 'unknown'
        ];
    }

    public function get_index(): array
    {
        // Not implemented for this endpoint
        return [];
    }

    public function put_index(): array
    {
        // Not implemented for this endpoint
        return [];
    }

    public function patch_index(): array
    {
        // Not implemented for this endpoint
        return [];
    }

    public function delete_index(): array
    {
        // Not implemented for this endpoint
        return [];
    }

    public function validate(): void
    {
        // Basic validation for webhook payload
        if (Input::getMethod() == 'post') {
            $data = json_decode(Input::getBody(), true);
            
            if (!isset($data['RecordType'])) {
                throw new \app\exceptions\GC2Exception(
                    'Missing RecordType in webhook payload',
                    400, 
                    null,
                    'INVALID_WEBHOOK'
                );
            }
            
            // Validate the RecordType is one we can handle
            $validTypes = ['Bounce', 'SpamComplaint', 'Delivery', 'Open', 'Click'];
            if (!in_array($data['RecordType'], $validTypes)) {
                throw new \app\exceptions\GC2Exception(
                    'Invalid RecordType in webhook payload: ' . $data['RecordType'],
                    400,
                    null,
                    'INVALID_WEBHOOK_TYPE'
                );
            }
            
            // Validate required fields based on RecordType
            switch ($data['RecordType']) {
                case 'Bounce':
                    if (!isset($data['MessageID']) || !isset($data['Email']) || !isset($data['BouncedAt'])) {
                        throw new \app\exceptions\GC2Exception(
                            'Missing required fields for Bounce event',
                            400,
                            null,
                            'INVALID_BOUNCE_DATA'
                        );
                    }
                    break;
                    
                case 'Delivery':
                    if (!isset($data['MessageID']) || !isset($data['Recipient']) || !isset($data['DeliveredAt'])) {
                        throw new \app\exceptions\GC2Exception(
                            'Missing required fields for Delivery event',
                            400,
                            null,
                            'INVALID_DELIVERY_DATA'
                        );
                    }
                    break;
                    
                case 'Open':
                    if (!isset($data['MessageID']) || !isset($data['Recipient']) || !isset($data['ReceivedAt'])) {
                        throw new \app\exceptions\GC2Exception(
                            'Missing required fields for Open event',
                            400,
                            null,
                            'INVALID_OPEN_DATA'
                        );
                    }
                    break;
                    
                case 'SpamComplaint':
                    if (!isset($data['MessageID']) || !isset($data['Email']) || !isset($data['BouncedAt'])) {
                        throw new \app\exceptions\GC2Exception(
                            'Missing required fields for SpamComplaint event',
                            400,
                            null,
                            'INVALID_SPAM_DATA'
                        );
                    }
                    break;
            }
        }
    }
}