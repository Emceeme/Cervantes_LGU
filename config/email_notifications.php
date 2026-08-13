<?php
// Email Notification System for MSWD Portal
// Handles sending email notifications for application status changes

class EmailNotifier {
    private $from_email;
    private $from_name;
    private $enabled;
    
    public function __construct() {
        $this->from_email = env('SMTP_FROM_EMAIL', 'mswd@cervantes.gov.ph');
        $this->from_name = env('SMTP_FROM_NAME', 'MSWD Cervantes');
        $this->enabled = env('EMAIL_NOTIFICATIONS_ENABLED', false);
    }
    
    /**
     * Send application status update email
     */
    public function sendStatusUpdate($to_email, $to_name, $tracking_number, $assistance_type, $old_status, $new_status, $remarks = '') {
        if (!$this->enabled || empty($to_email)) {
            return false;
        }
        
        $subject = "Application Status Update - {$tracking_number}";
        $message = $this->buildStatusUpdateEmail($to_name, $tracking_number, $assistance_type, $old_status, $new_status, $remarks);
        
        return $this->send($to_email, $to_name, $subject, $message);
    }
    
    /**
     * Send application submission confirmation email
     */
    public function sendSubmissionConfirmation($to_email, $to_name, $tracking_number, $assistance_type) {
        if (!$this->enabled || empty($to_email)) {
            return false;
        }
        
        $subject = "Application Received - {$tracking_number}";
        $message = $this->buildSubmissionEmail($to_name, $tracking_number, $assistance_type);
        
        return $this->send($to_email, $to_name, $subject, $message);
    }
    
    /**
     * Send document request email
     */
    public function sendDocumentRequest($to_email, $to_name, $tracking_number, $required_documents) {
        if (!$this->enabled || empty($to_email)) {
            return false;
        }
        
        $subject = "Additional Documents Required - {$tracking_number}";
        $message = $this->buildDocumentRequestEmail($to_name, $tracking_number, $required_documents);
        
        return $this->send($to_email, $to_name, $subject, $message);
    }
    
    /**
     * Build status update email content
     */
    private function buildStatusUpdateEmail($to_name, $tracking_number, $assistance_type, $old_status, $new_status, $remarks) {
        $status_colors = [
            'pending' => '#f59e0b',
            'under_review' => '#3b82f6',
            'approved' => '#22c55e',
            'rejected' => '#ef4444'
        ];
        
        $color = $status_colors[$new_status] ?? '#6b7280';
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0057B8, #0EA5E9); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
                .status-box { background: white; padding: 20px; border-left: 5px solid {$color}; margin: 20px 0; border-radius: 5px; }
                .tracking-number { background: #e0f2fe; padding: 10px; text-align: center; font-weight: bold; color: #0057B8; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>MSWD Cervantes</h1>
                    <p>Municipal Social Welfare and Development</p>
                </div>
                <div class='content'>
                    <h2>Application Status Update</h2>
                    <p>Dear {$to_name},</p>
                    <p>Your application for <strong>{$assistance_type}</strong> has been updated.</p>
                    
                    <div class='tracking-number'>
                        Tracking Number: {$tracking_number}
                    </div>
                    
                    <div class='status-box'>
                        <p><strong>Status Change:</strong></p>
                        <p>From: " . ucfirst(str_replace('_', ' ', $old_status)) . "</p>
                        <p>To: <strong>" . ucfirst(str_replace('_', ' ', $new_status)) . "</strong></p>
                    </div>
                    
                    " . (!empty($remarks) ? "<p><strong>Remarks:</strong> {$remarks}</p>" : "") . "
                    
                    <p>You can track your application status anytime by visiting our portal and entering your tracking number.</p>
                    
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply.</p>
                        <p>© " . date('Y') . " Municipality of Cervantes</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build submission confirmation email content
     */
    private function buildSubmissionEmail($to_name, $tracking_number, $assistance_type) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0057B8, #0EA5E9); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
                .tracking-number { background: #e0f2fe; padding: 15px; text-align: center; font-weight: bold; color: #0057B8; border-radius: 5px; margin: 20px 0; font-size: 18px; }
                .info-box { background: white; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #e5e7eb; }
                .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>MSWD Cervantes</h1>
                    <p>Municipal Social Welfare and Development</p>
                </div>
                <div class='content'>
                    <h2>Application Received</h2>
                    <p>Dear {$to_name},</p>
                    <p>Thank you for your application. We have successfully received your request for assistance.</p>
                    
                    <div class='tracking-number'>
                        {$tracking_number}
                    </div>
                    
                    <div class='info-box'>
                        <p><strong>Assistance Type:</strong> {$assistance_type}</p>
                        <p><strong>Submitted:</strong> " . date('F d, Y g:i A') . "</p>
                    </div>
                    
                    <p>Your application is now being reviewed by our social workers. You will receive updates on your application status via email.</p>
                    
                    <p>You can also track your application status anytime by visiting our portal and entering your tracking number.</p>
                    
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply.</p>
                        <p>© " . date('Y') . " Municipality of Cervantes</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Build document request email content
     */
    private function buildDocumentRequestEmail($to_name, $tracking_number, $required_documents) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #0057B8, #0EA5E9); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px; }
                .tracking-number { background: #fef3c7; padding: 10px; text-align: center; font-weight: bold; color: #92400e; border-radius: 5px; margin: 20px 0; }
                .document-list { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border: 1px solid #e5e7eb; }
                .document-list ul { list-style-type: none; padding: 0; }
                .document-list li { padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
                .document-list li:last-child { border-bottom: none; }
                .footer { text-align: center; margin-top: 30px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>MSWD Cervantes</h1>
                    <p>Municipal Social Welfare and Development</p>
                </div>
                <div class='content'>
                    <h2>Additional Documents Required</h2>
                    <p>Dear {$to_name},</p>
                    <p>We are processing your application, but we need additional documents to proceed.</p>
                    
                    <div class='tracking-number'>
                        Tracking Number: {$tracking_number}
                    </div>
                    
                    <div class='document-list'>
                        <h3>Required Documents:</h3>
                        <ul>
                            <li>✓ {$required_documents}</li>
                        </ul>
                    </div>
                    
                    <p>Please submit the required documents as soon as possible to avoid delays in processing your application.</p>
                    
                    <div class='footer'>
                        <p>This is an automated email. Please do not reply.</p>
                        <p>© " . date('Y') . " Municipality of Cervantes</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send email using PHP mail() or SMTP
     */
    private function send($to_email, $to_name, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $to = "{$to_name} <{$to_email}>";
        
        // Try to send email
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        // Log result
        if ($result) {
            logSecurityEvent('email_sent', null, [
                'to_email' => $to_email,
                'subject' => $subject
            ]);
        } else {
            logError('Email send failed', ['to_email' => $to_email, 'subject' => $subject]);
        }
        
        return $result;
    }
}
?>
