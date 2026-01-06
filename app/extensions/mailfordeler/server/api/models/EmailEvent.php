<?php
namespace app\extensions\mailfordeler\api\models;

use app\inc\Model;
use app\models\Database;
use app\conf\App;

class EmailEvent extends Model
{
    public function __construct()
    {
        // Ensure we only use the mailfordeler database connection
        Database::setDb("gc2mailfordeler");
        
        parent::__construct();
    }
    
    /**
     * Record a bounce event in the database
     * 
     * @param array $data The bounce data from Postmark
     * @return bool Success status
     */
    public function recordBounce(array $data): bool
    {
        $sql = "INSERT INTO email_events (
                    event_type, 
                    message_id, 
                    email, 
                    occurred_at, 
                    details
                ) VALUES (
                    'bounce',
                    :message_id,
                    :email,
                    :occurred_at,
                    :details
                )";
                
        $res = $this->prepare($sql);
        $res->execute([
            "message_id" => $data['MessageID'],
            "email" => $data['Email'],
            "occurred_at" => $data['BouncedAt'],
            "details" => json_encode($data)
        ]);
        
        return $res ? true : false;
    }

    /**
     * Record a delivery event in the database
     * 
     * @param array $data The delivery data from Postmark
     * @return bool Success status
     */
    public function recordDelivery(array $data): bool
    {
        $sql = "INSERT INTO email_events (
                    event_type, 
                    message_id, 
                    email, 
                    occurred_at, 
                    details
                ) VALUES (
                    'delivery',
                    :message_id,
                    :email,
                    :occurred_at,
                    :details
                )";
                
        $res = $this->prepare($sql);
        $res->execute([
            "message_id" => $data['MessageID'],
            "email" => $data['Recipient'],
            "occurred_at" => $data['DeliveredAt'],
            "details" => json_encode($data)
        ]);
        
        return $res ? true : false;
    }

    /**
     * Record an open event in the database
     * 
     * @param array $data The open data from Postmark
     * @return bool Success status
     */
    public function recordOpen(array $data): bool
    {
        $sql = "INSERT INTO email_events (
                    event_type, 
                    message_id, 
                    email, 
                    occurred_at, 
                    details
                ) VALUES (
                    'open',
                    :message_id,
                    :email,
                    :occurred_at,
                    :details
                )";
                
        $res = $this->prepare($sql);
        $res->execute([
            "message_id" => $data['MessageID'],
            "email" => $data['Recipient'],
            "occurred_at" => $data['ReceivedAt'],
            "details" => json_encode($data)
        ]);
        
        return $res ? true : false;
    }

    /**
     * Record a spam complaint event in the database
     * 
     * @param array $data The spam complaint data from Postmark
     * @return bool Success status
     */
    public function recordSpamComplaint(array $data): bool
    {
        $sql = "INSERT INTO email_events (
                    event_type, 
                    message_id, 
                    email, 
                    occurred_at, 
                    details
                ) VALUES (
                    'spam_complaint',
                    :message_id,
                    :email,
                    :occurred_at,
                    :details
                )";
                
        $res = $this->prepare($sql);
        $res->execute([
            "message_id" => $data['MessageID'],
            "email" => $data['Email'],
            "occurred_at" => $data['BouncedAt'],
            "details" => json_encode($data)
        ]);
        
        return $res ? true : false;
    }

    /**
     * Generic method to record any email event type
     * 
     * @param string $eventType The type of event (bounce, delivery, open, spam_complaint)
     * @param array $data The event data from Postmark
     * @return bool Success status
     */
    public function recordEvent(string $eventType, array $data): bool
    {
        switch ($eventType) {
            case 'bounce':
                return $this->recordBounce($data);
            case 'delivery':
                return $this->recordDelivery($data);
            case 'open':
                return $this->recordOpen($data);
            case 'spam_complaint':
                return $this->recordSpamComplaint($data);
            default:
                return false;
        }
    }

    /**
     * Automatically detect event type from Postmark webhook data and record it
     * 
     * @param array $data The webhook data from Postmark
     * @return bool Success status
     */
    public function processWebhook(array $data): bool
    {
        if (!isset($data['RecordType'])) {
            return false;
        }

        switch ($data['RecordType']) {
            case 'Bounce':
                return $this->recordBounce($data);
            case 'Delivery':
                return $this->recordDelivery($data);
            case 'Open':
                return $this->recordOpen($data);
            case 'SpamComplaint':
                return $this->recordSpamComplaint($data);
            default:
                return false;
        }
    }
}