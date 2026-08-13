<?php
// SMS Notification System for MSWD Portal
// Handles sending SMS notifications for application status changes
// This is a placeholder implementation that can be integrated with SMS gateway APIs

class SMSNotifier {
    private $enabled;
    private $api_key;
    private $sender_id;
    
    public function __construct() {
        $this->enabled = env('SMS_NOTIFICATIONS_ENABLED', false);
        $this->api_key = env('SMS_API_KEY', '');
        $this->sender_id = env('SMS_SENDER_ID', 'MSWD');
    }
    
    /**
     * Send application status update SMS
     */
    public function sendStatusUpdate($phone_number, $tracking_number, $assistance_type, $new_status) {
        if (!$this->enabled || empty($phone_number)) {
            return false;
        }
        
        $message = "MSWD Cervantes: Your application for {$assistance_type} ({$tracking_number}) status is now " . strtoupper(str_replace('_', ' ', $new_status)) . ". Track at mswd.cervantes.gov.ph";
        
        return $this->send($phone_number, $message);
    }
    
    /**
     * Send application submission confirmation SMS
     */
    public function sendSubmissionConfirmation($phone_number, $tracking_number, $assistance_type) {
        if (!$this->enabled || empty($phone_number)) {
            return false;
        }
        
        $message = "MSWD Cervantes: Your application for {$assistance_type} has been received. Tracking #: {$tracking_number}. Track status at mswd.cervantes.gov.ph";
        
        return $this->send($phone_number, $message);
    }
    
    /**
     * Send document request SMS
     */
    public function sendDocumentRequest($phone_number, $tracking_number) {
        if (!$this->enabled || empty($phone_number)) {
            return false;
        }
        
        $message = "MSWD Cervantes: Additional documents required for application {$tracking_number}. Please visit MSWD office or call for details.";
        
        return $this->send($phone_number, $message);
    }
    
    /**
     * Send SMS using configured gateway
     * This is a placeholder - integrate with your SMS provider (e.g., Globe Labs, Smart, Twilio)
     */
    private function send($phone_number, $message) {
        // Placeholder implementation
        // Integrate with your SMS gateway API here
        
        // Example integration patterns:
        
        // 1. Globe Labs API
        // $url = "https://api.globelabs.com.ph/smsmessaging/v1/outbound/binding/subscriptions/send";
        // $data = [
        //     'outboundSMSMessageRequest' => [
        //         'address' => 'tel:' . $phone_number,
        //         'senderAddress' => 'tel:' . $this->sender_id,
        //         'outboundSMSTextMessage' => ['message' => $message]
        //     ]
        // ];
        
        // 2. Smart API
        // $url = "https://post.chikka.com/smsapi/request";
        // $data = [
        //     'message_type' => 'SEND',
        //     'mobile_number' => $phone_number,
        //     'shortcode' => $this->sender_id,
        //     'message_id' => uniqid(),
        //     'message' => $message,
        //     'client_id' => $this->api_key,
        //     'secret_key' => env('SMS_SECRET_KEY', '')
        // ];
        
        // 3. Twilio API
        // require_once 'vendor/autoload.php';
        // $client = new Twilio\Rest\Client($this->api_key, env('SMS_AUTH_TOKEN', ''));
        // $client->messages->create(
        //     $phone_number,
        //     [
        //         'from' => $this->sender_id,
        //         'body' => $message
        //     ]
        // );
        
        // Log SMS attempt
        logSecurityEvent('sms_sent', null, [
            'phone_number' => $this->maskPhoneNumber($phone_number),
            'message_length' => strlen($message)
        ]);
        
        // Return true for now (placeholder)
        // In production, return actual API response
        return true;
    }
    
    /**
     * Mask phone number for logging (privacy)
     */
    private function maskPhoneNumber($phone_number) {
        if (strlen($phone_number) <= 4) {
            return '****';
        }
        return substr($phone_number, 0, 4) . '****' . substr($phone_number, -4);
    }
    
    /**
     * Validate phone number format
     */
    public function validatePhoneNumber($phone_number) {
        // Remove spaces, dashes, and parentheses
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone_number);
        
        // Check if it's a valid PH mobile number (starts with 09 and has 11 digits)
        return preg_match('/^09\d{9}$/', $cleaned);
    }
    
    /**
     * Format phone number to standard format
     */
    public function formatPhoneNumber($phone_number) {
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone_number);
        
        // Add +63 prefix if not present
        if (strpos($cleaned, '+63') === 0) {
            return $cleaned;
        }
        
        if (strpos($cleaned, '0') === 0) {
            return '+63' . substr($cleaned, 1);
        }
        
        return '+63' . $cleaned;
    }
}
?>
