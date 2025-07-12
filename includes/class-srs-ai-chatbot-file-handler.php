<?php
/**
 * File Handler for uploads and processing
 * 
 * @package SRS_AI_ChatBot
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SRS_AI_ChatBot_File_Handler {

    /**
     * File settings
     */
    private $file_settings;

    /**
     * Upload directory
     */
    private $upload_dir;

    /**
     * Constructor
     */
    public function __construct() {
        $this->file_settings = get_option('srs_ai_chatbot_file_settings', array());
        
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/srs-ai-chatbot/';
        
        // Ensure upload directory exists
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }

    /**
     * Handle file upload
     */
    public function handle_upload($file, $session_id, $chatbot_id) {
        // Validate file
        $validation = $this->validate_file($file);
        if (!$validation['valid']) {
            throw new Exception($validation['error']);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = $session_id . '_' . uniqid() . '.' . $file_extension;
        $file_path = $this->upload_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception(__('Failed to save uploaded file.', 'srs-ai-chatbot'));
        }

        // Calculate file hash
        $file_hash = hash_file('sha256', $file_path);

        // Process file content
        $processed_content = $this->process_file($file_path, $file['type']);

        // Save file record to database
        global $wpdb;
        $table = $wpdb->prefix . 'srs_file_uploads';
        
        $file_data = array(
            'session_id' => $session_id,
            'chatbot_id' => $chatbot_id,
            'original_name' => $file['name'],
            'file_name' => $unique_filename,
            'file_path' => $file_path,
            'file_size' => $file['size'],
            'file_type' => $file_extension,
            'mime_type' => $file['type'],
            'file_hash' => $file_hash,
            'processed' => !empty($processed_content),
            'extracted_text' => $processed_content,
            'expires_at' => $this->calculate_expiry_date()
        );

        $wpdb->insert($table, $file_data);

        return array(
            'success' => true,
            'file_id' => $wpdb->insert_id,
            'filename' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'content' => $processed_content
        );
    }
    /**
     * Validate uploaded file
     */
    private function validate_file($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'valid' => false,
                'error' => $this->get_upload_error_message($file['error'])
            );
        }

        // Check file size
        $max_size = $this->file_settings['max_file_size'] ?? 10485760; // 10MB default
        if ($file['size'] > $max_size) {
            return array(
                'valid' => false,
                'error' => sprintf(__('File size exceeds %s limit.', 'srs-ai-chatbot'), size_format($max_size))
            );
        }

        // Check file type
        $allowed_types = $this->file_settings['allowed_types'] ?? array('pdf', 'docx', 'txt', 'jpg', 'jpeg', 'png');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return array(
                'valid' => false,
                'error' => sprintf(__('File type "%s" is not allowed.', 'srs-ai-chatbot'), $file_extension)
            );
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = array(
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        );

        if (!in_array($mime_type, $allowed_mimes)) {
            return array(
                'valid' => false,
                'error' => __('Invalid file type detected.', 'srs-ai-chatbot')
            );
        }

        return array('valid' => true);
    }
    /**
     * Process file content
     */
    private function process_file($file_path, $mime_type) {
        switch ($mime_type) {
            case 'text/plain':
                return $this->process_text_file($file_path);
            
            case 'application/pdf':
                return $this->process_pdf_file($file_path);
            
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return $this->process_docx_file($file_path);
            
            case 'image/jpeg':
            case 'image/png':
                // Images are handled differently - they're sent to vision models
                return $this->process_image_file($file_path);
            
            default:
                return '';
        }
    }

    /**
     * Process text file
     */
    private function process_text_file($file_path) {
        $content = file_get_contents($file_path);
        return wp_strip_all_tags($content);
    }

    /**
     * Process PDF file
     */
    private function process_pdf_file($file_path) {
        // Check if smalot/pdfparser is available
        if (!class_exists('Smalot\PdfParser\Parser')) {
            return __('PDF processing requires smalot/pdfparser library.', 'srs-ai-chatbot');
        }

        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file_path);
            $text = $pdf->getText();
            return wp_strip_all_tags($text);
        } catch (Exception $e) {
            return __('Failed to extract text from PDF.', 'srs-ai-chatbot');
        }
    }

    /**
     * Process DOCX file
     */
    private function process_docx_file($file_path) {
        $zip = new ZipArchive();
        if ($zip->open($file_path) === TRUE) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($xml !== false) {
                // Extract text from XML
                $dom = new DOMDocument();
                $dom->loadXML($xml);
                $text = $dom->textContent;
                return wp_strip_all_tags($text);
            }
        }
        
        return __('Failed to extract text from DOCX file.', 'srs-ai-chatbot');
    }
    /**
     * Process image file
     */
    private function process_image_file($file_path) {
        // For images, we return a special marker that indicates this is an image
        // The actual image processing will be handled by vision-capable models
        return '[IMAGE_FILE]';
    }

    /**
     * Get upload error message
     */
    private function get_upload_error_message($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return __('File exceeds upload_max_filesize directive.', 'srs-ai-chatbot');
            case UPLOAD_ERR_FORM_SIZE:
                return __('File exceeds MAX_FILE_SIZE directive.', 'srs-ai-chatbot');
            case UPLOAD_ERR_PARTIAL:
                return __('File was only partially uploaded.', 'srs-ai-chatbot');
            case UPLOAD_ERR_NO_FILE:
                return __('No file was uploaded.', 'srs-ai-chatbot');
            case UPLOAD_ERR_NO_TMP_DIR:
                return __('Missing a temporary folder.', 'srs-ai-chatbot');
            case UPLOAD_ERR_CANT_WRITE:
                return __('Failed to write file to disk.', 'srs-ai-chatbot');
            case UPLOAD_ERR_EXTENSION:
                return __('File upload stopped by extension.', 'srs-ai-chatbot');
            default:
                return __('Unknown upload error.', 'srs-ai-chatbot');
        }
    }

    /**
     * Calculate file expiry date
     */
    private function calculate_expiry_date() {
        $retention_days = $this->file_settings['retention_days'] ?? 30;
        return date('Y-m-d H:i:s', strtotime("+{$retention_days} days"));
    }

    /**
     * Cleanup expired files
     */
    public function cleanup_expired_files() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_file_uploads';
        $expired_files = $wpdb->get_results(
            "SELECT * FROM $table WHERE expires_at < NOW()"
        );

        foreach ($expired_files as $file) {
            // Delete physical file
            if (file_exists($file->file_path)) {
                unlink($file->file_path);
            }
            
            // Delete database record
            $wpdb->delete($table, array('id' => $file->id), array('%d'));
        }

        return count($expired_files);
    }

    /**
     * Get file by session
     */
    public function get_session_files($session_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srs_file_uploads';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE session_id = %s ORDER BY uploaded_at DESC",
            $session_id
        ));
    }
}
