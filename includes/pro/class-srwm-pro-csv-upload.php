<?php
/**
 * Pro CSV Upload Class
 * 
 * Handles supplier CSV upload for bulk restock (Pro feature).
 */

if (!defined('ABSPATH')) {
    exit;
}

class SRWM_Pro_CSV_Upload {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'handle_csv_upload'));
        // Removed duplicate AJAX handler - handled in main plugin file
    }
    
    /**
     * Handle CSV upload from supplier
     */
    public function handle_csv_upload() {
        if (!isset($_GET['srwm_csv_upload']) || !isset($_GET['token'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['token']);
        
        if (!$this->validate_csv_token($token)) {
            wp_die(__('Invalid or expired upload link.', 'smart-restock-waitlist'));
        }
        
        // Check if Pro features are active
        if (!$this->is_pro_active()) {
            $this->display_pro_required_message();
            return;
        }
        
        // Debug: Log request information
        error_log('CSV Upload Debug: REQUEST_METHOD = ' . $_SERVER['REQUEST_METHOD']);
        error_log('CSV Upload Debug: $_POST = ' . print_r($_POST, true));
        error_log('CSV Upload Debug: $_FILES = ' . print_r($_FILES, true));
        
        // Handle the upload form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['srwm_csv_submit'])) {
            // Debug: Check if this is a test submission
            if (isset($_POST['debug_form'])) {
                error_log('CSV Upload Debug: Form submitted with debug_form = ' . $_POST['debug_form']);
            }
            
            $this->process_csv_upload($token);
        } else {
            $this->display_csv_upload_form($token);
        }
    }
    
    /**
     * Validate CSV upload token
     */
    private function validate_csv_token($token) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s AND expires_at > NOW() AND used = 0",
            $token
        ));
        
        return $result !== null;
    }
    
    /**
     * Display CSV upload form
     */
    private function display_csv_upload_form($token) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Bulk Restock Upload - Professional Stock Management', 'smart-restock-waitlist'); ?></title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                    color: #333;
                }
                
                .container {
                    max-width: 1000px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
                    overflow: hidden;
                    position: relative;
                }
                
                .container::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
                }
                
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px 30px;
                    text-align: center;
                    position: relative;
                    overflow: hidden;
                }
                
                .header::before {
                    content: '';
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
                    animation: float 6s ease-in-out infinite;
                }
                
                @keyframes float {
                    0%, 100% { transform: translateY(0px) rotate(0deg); }
                    50% { transform: translateY(-20px) rotate(180deg); }
                }
                
                .header h1 {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin-bottom: 10px;
                    position: relative;
                    z-index: 1;
                }
                
                .header p {
                    font-size: 1.1rem;
                    opacity: 0.9;
                    position: relative;
                    z-index: 1;
                }
                
                .header-icon {
                    font-size: 3rem;
                    margin-bottom: 20px;
                    position: relative;
                    z-index: 1;
                }
                
                .content {
                    padding: 40px;
                }
                
                .upload-section {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    margin-bottom: 40px;
                }
                
                .upload-form {
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                    padding: 30px;
                    border-radius: 15px;
                    border: 2px solid #e2e8f0;
                    position: relative;
                }
                
                .upload-form::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #10b981, #059669);
                }
                
                .form-group {
                    margin-bottom: 25px;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #374151;
                    font-size: 0.95rem;
                }
                
                .file-upload-area {
                    border: 3px dashed #d1d5db;
                    border-radius: 16px;
                    padding: 50px 30px;
                    text-align: center;
                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    cursor: pointer;
                    position: relative;
                    overflow: hidden;
                }
                
                .file-upload-area::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
                    transition: left 0.6s;
                }
                
                .file-upload-area:hover::before {
                    left: 100%;
                }
                
                .file-upload-area:hover {
                    border-color: #667eea;
                    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15);
                }
                
                .file-upload-area.dragover {
                    border-color: #10b981;
                    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                    transform: scale(1.02);
                    box-shadow: 0 15px 35px rgba(16, 185, 129, 0.2);
                }
                
                .file-upload-icon {
                    font-size: 4rem;
                    color: #9ca3af;
                    margin-bottom: 20px;
                    transition: all 0.3s ease;
                }
                
                .file-upload-area:hover .file-upload-icon {
                    color: #667eea;
                    transform: scale(1.1);
                }
                
                .file-upload-area.dragover .file-upload-icon {
                    color: #10b981;
                    transform: scale(1.2);
                }
                
                .file-upload-text {
                    font-size: 1.1rem;
                    color: #6b7280;
                    margin-bottom: 10px;
                }
                
                .file-upload-hint {
                    font-size: 0.9rem;
                    color: #9ca3af;
                }
                
                input[type="file"] {
                    position: absolute;
                    opacity: 0;
                    width: 100%;
                    height: 100%;
                    cursor: pointer;
                }
                
                .upload-button {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: white;
                    padding: 18px 40px;
                    border: none;
                    border-radius: 12px;
                    font-size: 1.1rem;
                    font-weight: 600;
                    cursor: pointer;
                    width: 100%;
                    transition: all 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }
                
                .upload-button::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    transition: left 0.5s;
                }
                
                .upload-button:hover::before {
                    left: 100%;
                }
                
                .upload-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
                }
                
                .upload-button:active {
                    transform: translateY(0);
                }
                
                .info-section {
                    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                    padding: 30px;
                    border-radius: 15px;
                    border: 2px solid #bfdbfe;
                }
                
                .info-section h3 {
                    color: #1e40af;
                    font-size: 1.3rem;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .info-section h3 i {
                    font-size: 1.5rem;
                }
                
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin-bottom: 25px;
                }
                
                .info-item {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    border-left: 4px solid #3b82f6;
                }
                
                .info-item h4 {
                    color: #1f2937;
                    font-size: 1rem;
                    margin-bottom: 8px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .info-item p {
                    color: #6b7280;
                    font-size: 0.9rem;
                    line-height: 1.5;
                }
                
                .csv-template {
                    background: white;
                    padding: 25px;
                    border-radius: 12px;
                    border: 2px solid #e5e7eb;
                    margin-top: 25px;
                }
                
                .csv-template h4 {
                    color: #374151;
                    font-size: 1.1rem;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .csv-template code {
                    background: #1f2937;
                    color: #f9fafb;
                    padding: 20px;
                    border-radius: 8px;
                    display: block;
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                    font-size: 0.9rem;
                    line-height: 1.6;
                    white-space: pre-wrap;
                    overflow-x: auto;
                }
                
                .requirements {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    padding: 25px;
                    border-radius: 12px;
                    border: 2px solid #f59e0b;
                    margin-top: 30px;
                }
                
                .requirements h4 {
                    color: #92400e;
                    font-size: 1.1rem;
                    margin-bottom: 15px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .requirements ul {
                    list-style: none;
                    padding: 0;
                }
                
                .requirements li {
                    color: #78350f;
                    padding: 8px 0;
                    padding-left: 25px;
                    position: relative;
                    font-size: 0.95rem;
                }
                
                .requirements li::before {
                    content: '✓';
                    position: absolute;
                    left: 0;
                    color: #059669;
                    font-weight: bold;
                    font-size: 1.1rem;
                }
                
                .footer-note {
                    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
                    padding: 20px;
                    border-radius: 12px;
                    text-align: center;
                    margin-top: 30px;
                    border: 1px solid #d1d5db;
                }
                
                .footer-note p {
                    color: #6b7280;
                    font-size: 0.95rem;
                    line-height: 1.6;
                }
                
                @media (max-width: 768px) {
                    .upload-section {
                        grid-template-columns: 1fr;
                    }
                    
                    .info-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .header h1 {
                        font-size: 2rem;
                    }
                    
                    .content {
                        padding: 20px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="header-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h1><?php _e('Bulk Restock Upload', 'smart-restock-waitlist'); ?></h1>
                    <p><?php echo get_bloginfo('name'); ?> - <?php _e('Professional Stock Management System', 'smart-restock-waitlist'); ?></p>
                </div>
                
                <div class="content">
                    <div class="upload-section">
                        <div class="upload-form">
                            <h3 style="color: #374151; margin-bottom: 25px; font-size: 1.3rem;">
                                <i class="fas fa-upload"></i> <?php _e('Upload Your CSV File', 'smart-restock-waitlist'); ?>
                            </h3>
                            
                            <form method="post" enctype="multipart/form-data" id="csvUploadForm" action="">
                                <input type="hidden" name="srwm_csv_token" value="<?php echo esc_attr($token); ?>">
                                <input type="hidden" name="debug_form" value="1">
                                
                                <div class="form-group">
                                    <label for="csv_file">
                                        <i class="fas fa-file-csv"></i> <?php _e('Select CSV File', 'smart-restock-waitlist'); ?>
                                    </label>
                                    <div class="file-upload-area" id="fileUploadArea">
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <?php _e('Click to browse or drag & drop your CSV file here', 'smart-restock-waitlist'); ?>
                                        </div>
                                        <div class="file-upload-hint">
                                            <?php _e('Maximum file size: 10MB | Supported formats: .csv, .xlsx, .xls', 'smart-restock-waitlist'); ?>
                                        </div>
                                        <input type="file" id="csv_file" name="csv_file" accept=".csv,.xlsx,.xls" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="srwm_csv_submit" class="upload-button">
                                    <i class="fas fa-rocket"></i> <?php _e('Process Restock Data', 'smart-restock-waitlist'); ?>
                                </button>
                            </form>
                        </div>
                        
                        <div class="info-section">
                            <h3>
                                <i class="fas fa-info-circle"></i> <?php _e('Upload Information', 'smart-restock-waitlist'); ?>
                            </h3>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <h4>
                                        <i class="fas fa-clock"></i> <?php _e('Processing Time', 'smart-restock-waitlist'); ?>
                                    </h4>
                                    <p><?php _e('Files are processed instantly. You\'ll receive immediate feedback on the upload results.', 'smart-restock-waitlist'); ?></p>
                                </div>
                                
                                <div class="info-item">
                                    <h4>
                                        <i class="fas fa-shield-alt"></i> <?php _e('Security', 'smart-restock-waitlist'); ?>
                                    </h4>
                                    <p><?php _e('Your data is processed securely. Files are automatically deleted after processing.', 'smart-restock-waitlist'); ?></p>
                                </div>
                                
                                <div class="info-item">
                                    <h4>
                                        <i class="fas fa-bell"></i> <?php _e('Notifications', 'smart-restock-waitlist'); ?>
                                    </h4>
                                    <p><?php _e('Customers on the waitlist will be automatically notified when stock is updated.', 'smart-restock-waitlist'); ?></p>
                                </div>
                                
                                <div class="info-item">
                                    <h4>
                                        <i class="fas fa-chart-line"></i> <?php _e('Analytics', 'smart-restock-waitlist'); ?>
                                    </h4>
                                    <p><?php _e('All restock activities are logged for reporting and analytics purposes.', 'smart-restock-waitlist'); ?></p>
                                </div>
                            </div>
                            
                            <div class="csv-template">
                                <h4>
                                    <i class="fas fa-table"></i> <?php _e('CSV Format Template', 'smart-restock-waitlist'); ?>
                                </h4>
                                <p style="color: #6b7280; margin-bottom: 15px; font-size: 0.95rem;">
                                    <?php _e('Your CSV file must follow this exact format:', 'smart-restock-waitlist'); ?>
                                </p>
                                <code>Product ID,SKU,Quantity,Notes
62,ABC123,50,Restocked from main warehouse
63,DEF456,25,Priority restock
64,GHI789,100,Bulk order fulfillment</code>
                            </div>
                        </div>
                    </div>
                    
                    <div class="requirements">
                        <h4>
                            <i class="fas fa-check-circle"></i> <?php _e('File Requirements', 'smart-restock-waitlist'); ?>
                        </h4>
                        <ul>
                            <li><?php _e('File must be in CSV, XLSX, or XLS format', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('Maximum file size: 10 megabytes', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('First row must contain column headers', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('Product ID must match existing products in the system', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('Quantity must be a positive number', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('SKU must match the product SKU in the system', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('Real-time validation will check your data before upload', 'smart-restock-waitlist'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="footer-note">
                        <p>
                            <i class="fas fa-lightbulb"></i> 
                            <strong><?php _e('Pro Tip:', 'smart-restock-waitlist'); ?></strong> 
                            <?php _e('Download our CSV template from the admin dashboard to ensure your file format is correct. The system will automatically validate your data and provide detailed feedback on any issues.', 'smart-restock-waitlist'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <script>
                // File upload area interactions
                const fileUploadArea = document.getElementById('fileUploadArea');
                const fileInput = document.getElementById('csv_file');
                
                fileUploadArea.addEventListener('click', () => fileInput.click());
                
                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        validateAndPreviewFile(file);
                    }
                });
                
                // Real-time file validation with preview
                function validateAndPreviewFile(file) {
                    const fileName = file.name;
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    
                    // Show loading state
                    const iconElement = fileUploadArea.querySelector('.file-upload-icon');
                    const textElement = fileUploadArea.querySelector('.file-upload-text');
                    const hintElement = fileUploadArea.querySelector('.file-upload-hint');
                    
                    if (iconElement) {
                        iconElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        iconElement.style.color = '#f59e0b';
                    }
                    
                    if (textElement) {
                        textElement.textContent = 'Validating file...';
                        textElement.style.color = '#f59e0b';
                        textElement.style.fontWeight = '600';
                    }
                    
                    if (hintElement) {
                        hintElement.textContent = `Processing ${fileName}`;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                    const fileExtension = fileName.split('.').pop().toLowerCase();
                    const allowedExtensions = ['csv', 'xlsx', 'xls'];
                    
                    if (!allowedExtensions.includes(fileExtension)) {
                        showFileError('Invalid file type. Please upload a CSV, XLSX, or XLS file.');
                        return;
                    }
                    
                    // Validate file size (10MB limit)
                    const maxSize = 10 * 1024 * 1024; // 10MB
                    if (file.size > maxSize) {
                        showFileError(`File size exceeds 10MB limit. Your file size is: ${fileSize} MB`);
                        return;
                    }
                    
                    // Read and preview file
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        try {
                            const csvData = parseCSVData(e.target.result);
                            const validation = validateCSVData(csvData);
                            
                            if (validation.valid) {
                                showFileSuccess(fileName, fileSize, csvData, validation);
                            } else {
                                showFileWarning(fileName, fileSize, csvData, validation);
                            }
                        } catch (error) {
                            showFileError('Error reading file: ' + error.message);
                        }
                    };
                    
                    reader.onerror = function() {
                        showFileError('Error reading file. Please try again.');
                    };
                    
                    reader.readAsText(file);
                }
                
                // Parse CSV data
                function parseCSVData(csvText) {
                    const lines = csvText.split('\n');
                    const data = [];
                    
                    if (lines.length < 2) {
                        throw new Error('File must contain at least a header row and one data row');
                    }
                    
                    const header = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
                    
                    for (let i = 1; i < lines.length; i++) {
                        if (lines[i].trim()) {
                            const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
                            const row = {};
                            
                            header.forEach((key, index) => {
                                row[key] = values[index] || '';
                            });
                            
                            data.push(row);
                        }
                    }
                    
                    return { header, data };
                }
                
                // Validate CSV data
                function validateCSVData(csvData) {
                    const validation = {
                        valid: true,
                        errors: [],
                        warnings: [],
                        suggestions: [],
                        validRows: 0,
                        invalidRows: 0
                    };
                    
                    csvData.data.forEach((row, index) => {
                        let rowValid = true;
                        
                        // Check for required fields
                        if (!row.sku && !row['product_sku'] && !row['product sku']) {
                            validation.errors.push(`Row ${index + 1}: Missing SKU`);
                            rowValid = false;
                        }
                        
                        if (!row.quantity && !row.qty && !row.stock) {
                            validation.errors.push(`Row ${index + 1}: Missing quantity`);
                            rowValid = false;
                        }
                        
                        // Validate quantity
                        const qty = parseInt(row.quantity || row.qty || row.stock);
                        if (isNaN(qty) || qty < 0) {
                            validation.warnings.push(`Row ${index + 1}: Invalid quantity (${row.quantity || row.qty || row.stock})`);
                        }
                        
                        if (rowValid) {
                            validation.validRows++;
                        } else {
                            validation.invalidRows++;
                        }
                    });
                    
                    validation.valid = validation.errors.length === 0;
                    return validation;
                }
                
                // Show file success
                function showFileSuccess(fileName, fileSize, csvData, validation) {
                    const iconElement = fileUploadArea.querySelector('.file-upload-icon');
                    const textElement = fileUploadArea.querySelector('.file-upload-text');
                    const hintElement = fileUploadArea.querySelector('.file-upload-hint');
                    
                    if (iconElement) {
                        iconElement.innerHTML = '<i class="fas fa-check-circle"></i>';
                        iconElement.style.color = '#10b981';
                        iconElement.style.animation = 'bounceIn 0.6s ease';
                    }
                    
                    if (textElement) {
                        textElement.textContent = fileName;
                        textElement.style.color = '#059669';
                        textElement.style.fontWeight = '600';
                    }
                    
                    if (hintElement) {
                        hintElement.textContent = `Size: ${fileSize} MB • ${validation.validRows} rows ready`;
                        hintElement.style.color = '#059669';
                    }
                    
                    // Add success animation
                    fileUploadArea.style.animation = 'pulse 0.6s ease';
                    
                    // Show preview button
                    showPreviewButton(csvData, validation);
                }
                
                // Show file warning
                function showFileWarning(fileName, fileSize, csvData, validation) {
                    const iconElement = fileUploadArea.querySelector('.file-upload-icon');
                    const textElement = fileUploadArea.querySelector('.file-upload-text');
                    const hintElement = fileUploadArea.querySelector('.file-upload-hint');
                    
                    if (iconElement) {
                        iconElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                        iconElement.style.color = '#f59e0b';
                    }
                    
                    if (textElement) {
                        textElement.textContent = fileName;
                        textElement.style.color = '#f59e0b';
                        textElement.style.fontWeight = '600';
                    }
                    
                    if (hintElement) {
                        hintElement.textContent = `${validation.warnings.length} warnings found`;
                        hintElement.style.color = '#f59e0b';
                    }
                    
                    // Show preview button with warnings
                    showPreviewButton(csvData, validation, true);
                }
                
                // Show file error
                function showFileError(message) {
                    const iconElement = fileUploadArea.querySelector('.file-upload-icon');
                    const textElement = fileUploadArea.querySelector('.file-upload-text');
                    const hintElement = fileUploadArea.querySelector('.file-upload-hint');
                    
                    if (iconElement) {
                        iconElement.innerHTML = '<i class="fas fa-times-circle"></i>';
                        iconElement.style.color = '#ef4444';
                    }
                    
                    if (textElement) {
                        textElement.textContent = 'Upload Failed';
                        textElement.style.color = '#dc2626';
                        textElement.style.fontWeight = '600';
                    }
                    
                    if (hintElement) {
                        hintElement.textContent = message;
                        hintElement.style.color = '#dc2626';
                    }
                    
                    // Reset file input
                    fileInput.value = '';
                }
                
                // Show preview button
                function showPreviewButton(csvData, validation, hasWarnings = false) {
                    // Remove existing preview button
                    const existingButton = document.querySelector('.srwm-preview-btn');
                    if (existingButton) {
                        existingButton.remove();
                    }
                    
                    const button = document.createElement('button');
                    button.className = 'srwm-preview-btn';
                    button.innerHTML = '<i class="fas fa-eye"></i> Preview Data';
                    button.style.cssText = `
                        background: ${hasWarnings ? '#f59e0b' : '#10b981'};
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 8px;
                        margin-top: 15px;
                        cursor: pointer;
                        font-weight: 600;
                        transition: all 0.3s ease;
                    `;
                    
                    button.addEventListener('click', function() {
                        showPreviewModal(csvData, validation);
                    });
                    
                    fileUploadArea.appendChild(button);
                }
                
                // Show preview modal
                function showPreviewModal(csvData, validation) {
                    // Remove existing modal
                    const existingModal = document.querySelector('.srwm-preview-modal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    const modal = document.createElement('div');
                    modal.className = 'srwm-preview-modal';
                    modal.innerHTML = `
                        <div class="srwm-preview-overlay">
                            <div class="srwm-preview-content">
                                <div class="srwm-preview-header">
                                    <h3><i class="fas fa-table"></i> Data Preview</h3>
                                    <button class="srwm-preview-close" onclick="closePreviewModal()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="srwm-preview-body">
                                    <div class="srwm-validation-summary">
                                        <div class="validation-item valid">
                                            <i class="fas fa-check-circle"></i>
                                            <span>${validation.validRows} valid rows</span>
                                        </div>
                                        ${validation.warnings.length > 0 ? `
                                            <div class="validation-item warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <span>${validation.warnings.length} warnings</span>
                                            </div>
                                        ` : ''}
                                        ${validation.errors.length > 0 ? `
                                            <div class="validation-item error">
                                                <i class="fas fa-times-circle"></i>
                                                <span>${validation.errors.length} errors</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                    <div class="srwm-preview-table">
                                        ${generatePreviewTable(csvData)}
                                    </div>
                                    ${validation.warnings.length > 0 ? `
                                        <div class="srwm-warnings-section">
                                            <h4><i class="fas fa-exclamation-triangle"></i> Warnings</h4>
                                            <ul>
                                                ${validation.warnings.map(warning => `<li>${warning}</li>`).join('')}
                                            </ul>
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="srwm-preview-footer">
                                    <button class="srwm-btn-secondary" onclick="closePreviewModal()">Cancel</button>
                                    <button class="srwm-btn-primary" onclick="proceedWithUpload()">
                                        <i class="fas fa-upload"></i> Proceed with Upload
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(modal);
                    
                    // Add animation
                    setTimeout(() => {
                        modal.querySelector('.srwm-preview-content').style.transform = 'scale(1)';
                        modal.querySelector('.srwm-preview-content').style.opacity = '1';
                    }, 10);
                }
                
                // Generate preview table
                function generatePreviewTable(csvData) {
                    if (!csvData.data || csvData.data.length === 0) {
                        return '<p>No data to preview</p>';
                    }
                    
                    const headers = Object.keys(csvData.data[0]);
                    const previewRows = csvData.data.slice(0, 10); // Show first 10 rows
                    
                    let tableHTML = '<table class="srwm-preview-table-inner">';
                    
                    // Header
                    tableHTML += '<thead><tr>';
                    headers.forEach(header => {
                        tableHTML += `<th>${header}</th>`;
                    });
                    tableHTML += '</tr></thead>';
                    
                    // Body
                    tableHTML += '<tbody>';
                    previewRows.forEach(row => {
                        tableHTML += '<tr>';
                        headers.forEach(header => {
                            tableHTML += `<td>${row[header] || ''}</td>`;
                        });
                        tableHTML += '</tr>';
                    });
                    tableHTML += '</tbody>';
                    tableHTML += '</table>';
                    
                    if (csvData.data.length > 10) {
                        tableHTML += `<p class="srwm-preview-note">Showing first 10 rows of ${csvData.data.length} total rows</p>`;
                    }
                    
                    return tableHTML;
                }
                
                // Close preview modal
                function closePreviewModal() {
                    const modal = document.querySelector('.srwm-preview-modal');
                    if (modal) {
                        modal.querySelector('.srwm-preview-content').style.transform = 'scale(0.9)';
                        modal.querySelector('.srwm-preview-content').style.opacity = '0';
                        setTimeout(() => {
                            modal.remove();
                        }, 300);
                    }
                }
                
                // Proceed with upload
                function proceedWithUpload() {
                    closePreviewModal();
                    // The form will be submitted normally
                    console.log('Proceeding with upload...');
                }
                
                // Drag and drop functionality
                fileUploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    fileUploadArea.classList.add('dragover');
                });
                
                fileUploadArea.addEventListener('dragleave', () => {
                    fileUploadArea.classList.remove('dragover');
                });
                
                fileUploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    fileUploadArea.classList.remove('dragover');
                    
                    if (e.dataTransfer.files.length > 0) {
                        fileInput.files = e.dataTransfer.files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
            </script>
            
            <style>
                /* Animations */
                @keyframes bounceIn {
                    0% {
                        transform: scale(0.3);
                        opacity: 0;
                    }
                    50% {
                        transform: scale(1.05);
                    }
                    70% {
                        transform: scale(0.9);
                    }
                    100% {
                        transform: scale(1);
                        opacity: 1;
                    }
                }
                
                @keyframes pulse {
                    0% {
                        transform: scale(1);
                    }
                    50% {
                        transform: scale(1.05);
                    }
                    100% {
                        transform: scale(1);
                    }
                }
                
                @keyframes slideInUp {
                    from {
                        transform: translateY(30px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                    }
                    to {
                        opacity: 1;
                    }
                }
                
                /* Preview Modal Styles */
                .srwm-preview-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .srwm-preview-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(5px);
                }
                
                .srwm-preview-content {
                    background: white;
                    border-radius: 16px;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
                    max-width: 90%;
                    max-height: 90%;
                    width: 800px;
                    transform: scale(0.9);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    overflow: hidden;
                }
                
                .srwm-preview-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px 25px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .srwm-preview-header h3 {
                    margin: 0;
                    font-size: 1.2rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                
                .srwm-preview-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5rem;
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 50%;
                    transition: background 0.3s ease;
                }
                
                .srwm-preview-close:hover {
                    background: rgba(255, 255, 255, 0.2);
                }
                
                .srwm-preview-body {
                    padding: 25px;
                    max-height: 500px;
                    overflow-y: auto;
                }
                
                .srwm-validation-summary {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                }
                
                .validation-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 12px;
                    border-radius: 8px;
                    font-size: 0.9rem;
                    font-weight: 600;
                }
                
                .validation-item.valid {
                    background: rgba(16, 185, 129, 0.1);
                    color: #059669;
                }
                
                .validation-item.warning {
                    background: rgba(245, 158, 11, 0.1);
                    color: #d97706;
                }
                
                .validation-item.error {
                    background: rgba(239, 68, 68, 0.1);
                    color: #dc2626;
                }
                
                .srwm-preview-table {
                    background: #f8fafc;
                    border-radius: 8px;
                    padding: 15px;
                    margin-bottom: 20px;
                }
                
                .srwm-preview-table-inner {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 0.9rem;
                }
                
                .srwm-preview-table-inner th,
                .srwm-preview-table-inner td {
                    padding: 8px 12px;
                    text-align: left;
                    border-bottom: 1px solid #e2e8f0;
                }
                
                .srwm-preview-table-inner th {
                    background: #f1f5f9;
                    font-weight: 600;
                    color: #374151;
                }
                
                .srwm-preview-note {
                    text-align: center;
                    color: #6b7280;
                    font-style: italic;
                    margin-top: 10px;
                }
                
                .srwm-warnings-section {
                    background: rgba(245, 158, 11, 0.05);
                    border: 1px solid rgba(245, 158, 11, 0.2);
                    border-radius: 8px;
                    padding: 15px;
                }
                
                .srwm-warnings-section h4 {
                    margin: 0 0 10px 0;
                    color: #d97706;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .srwm-warnings-section ul {
                    margin: 0;
                    padding-left: 20px;
                    color: #6b7280;
                }
                
                .srwm-warnings-section li {
                    margin-bottom: 5px;
                }
                
                .srwm-preview-footer {
                    padding: 20px 25px;
                    border-top: 1px solid #e2e8f0;
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                }
                
                .srwm-btn-secondary {
                    background: #6b7280;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                }
                
                .srwm-btn-secondary:hover {
                    background: #4b5563;
                }
                
                .srwm-btn-primary {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                
                .srwm-btn-primary:hover {
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    transform: translateY(-1px);
                }
            </style>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Process CSV upload
     */
    private function process_csv_upload($token) {
        // Debug: Log upload information
        error_log('CSV Upload Debug: $_FILES = ' . print_r($_FILES, true));
        

        
        if (!isset($_FILES['csv_file'])) {
            wp_die(__('No file was uploaded. Please select a CSV file.', 'smart-restock-waitlist'));
        }
        
        $file = $_FILES['csv_file'];
        
        // Check for specific upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_message = '';
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message = __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'smart-restock-waitlist');
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'smart-restock-waitlist');
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = __('The uploaded file was only partially uploaded.', 'smart-restock-waitlist');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = __('No file was uploaded.', 'smart-restock-waitlist');
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = __('Missing a temporary folder.', 'smart-restock-waitlist');
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = __('Failed to write file to disk.', 'smart-restock-waitlist');
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = __('A PHP extension stopped the file upload.', 'smart-restock-waitlist');
                    break;
                default:
                    $error_message = __('Unknown upload error occurred.', 'smart-restock-waitlist');
                    break;
            }
            wp_die($error_message);
        }
        
        // Check file size (10MB limit)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ($file['size'] > $max_size) {
            wp_die(sprintf(__('File size exceeds the maximum limit of 10MB. Your file size is: %s', 'smart-restock-waitlist'), size_format($file['size'])));
        }
        
        // Validate file type
        $file_type = wp_check_filetype($file['name']);
        $allowed_extensions = array('csv', 'xlsx', 'xls');
        
        if (!in_array($file_type['ext'], $allowed_extensions)) {
            wp_die(__('Please upload a valid CSV, XLSX, or XLS file.', 'smart-restock-waitlist'));
        }
        
        // Read file based on type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension === 'csv') {
            $csv_data = $this->read_csv_file($file['tmp_name']);
        } else {
            $csv_data = $this->read_excel_file($file['tmp_name']);
        }
        
        if (!$csv_data) {
            wp_die(__('Failed to read CSV file. Please check the file format.', 'smart-restock-waitlist'));
        }
        
        // Check if approval is required
        $require_approval = get_option('srwm_csv_require_approval', 'yes');
        
        if ($require_approval === 'yes') {
            // Store for approval
            $this->store_for_approval($token, $file, $csv_data);
        } else {
            // Process immediately
            $results = $this->process_csv_data($csv_data);
            
            // Mark token as used
            $this->mark_token_used($token);
            
            // Display results
            $this->display_upload_results($results);
        }
    }
    
    /**
     * Read Excel file (XLSX/XLS)
     */
    private function read_excel_file($file_path) {
        // For now, we'll convert Excel to CSV format
        // In a production environment, you might want to use a library like PhpSpreadsheet
        
        // Simple conversion for basic Excel files
        $data = array();
        
        // Try to read as CSV first (some Excel files can be read as CSV)
        if (($handle = fopen($file_path, 'r')) !== false) {
            // Read header row
            $header = fgetcsv($handle);
            
            if (!$header || count($header) < 2) {
                fclose($handle);
                return false;
            }
            
            // Find column indexes
            $sku_index = false;
            $quantity_index = false;
            
            $header_lower = array_map('strtolower', $header);
            
            // Try different possible SKU column names
            $sku_names = array('sku', 'product_sku', 'product sku', 'product-sku', 'product_id', 'product id', 'product-id');
            foreach ($sku_names as $sku_name) {
                $index = array_search($sku_name, $header_lower);
                if ($index !== false) {
                    $sku_index = $index;
                    break;
                }
            }
            
            // Try different possible quantity column names
            $quantity_names = array('quantity', 'qty', 'stock', 'stock_quantity', 'stock quantity', 'stock-quantity');
            foreach ($quantity_names as $quantity_name) {
                $index = array_search($quantity_name, $header_lower);
                if ($index !== false) {
                    $quantity_index = $index;
                    break;
                }
            }
            
            if ($sku_index === false || $quantity_index === false) {
                fclose($handle);
                return false;
            }
            
            // Read data rows
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $data[] = array(
                        'sku' => trim($row[$sku_index]),
                        'quantity' => intval($row[$quantity_index])
                    );
                }
            }
            
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Read CSV file
     */
    private function read_csv_file($file_path) {
        $data = array();
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            // Read header row
            $header = fgetcsv($handle);
            
            if (!$header || count($header) < 2) {
                fclose($handle);
                error_log('CSV Upload Debug: Invalid header - ' . print_r($header, true));
                return false;
            }
            
            // Debug: Log header
            error_log('CSV Upload Debug: Header = ' . print_r($header, true));
            
            // Find column indexes - try multiple possible column names
            $sku_index = false;
            $quantity_index = false;
            
            $header_lower = array_map('strtolower', $header);
            
            // Try different possible SKU column names
            $sku_names = array('sku', 'product_sku', 'product sku', 'product-sku', 'product_id', 'product id', 'product-id');
            foreach ($sku_names as $sku_name) {
                $sku_index = array_search($sku_name, $header_lower);
                if ($sku_index !== false) break;
            }
            
            // Try different possible quantity column names
            $quantity_names = array('quantity', 'qty', 'stock', 'stock_quantity', 'stock quantity', 'stock-quantity');
            foreach ($quantity_names as $quantity_name) {
                $quantity_index = array_search($quantity_name, $header_lower);
                if ($quantity_index !== false) break;
            }
            
            if ($sku_index === false || $quantity_index === false) {
                fclose($handle);
                error_log('CSV Upload Debug: SKU index = ' . ($sku_index !== false ? $sku_index : 'false') . ', Quantity index = ' . ($quantity_index !== false ? $quantity_index : 'false'));
                return false;
            }
            
            // Read data rows
            $row_count = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $data[] = array(
                        'sku' => trim($row[$sku_index]),
                        'quantity' => intval($row[$quantity_index])
                    );
                    $row_count++;
                }
            }
            
            fclose($handle);
            error_log('CSV Upload Debug: Processed ' . $row_count . ' rows');
        } else {
            error_log('CSV Upload Debug: Failed to open file: ' . $file_path);
        }
        
        return $data;
    }
    
    /**
     * Process CSV data
     */
    private function process_csv_data($csv_data) {
        $results = array(
            'success' => 0,
            'errors' => array(),
            'skipped' => 0
        );
        
        foreach ($csv_data as $row) {
            $sku = $row['sku'];
            $quantity = $row['quantity'];
            
            if (empty($sku) || $quantity <= 0) {
                $results['skipped']++;
                continue;
            }
            
            // Find product by SKU
            $product_id = wc_get_product_id_by_sku($sku);
            
            if (!$product_id) {
                $results['errors'][] = sprintf(
                    __('Product with SKU "%s" not found.', 'smart-restock-waitlist'),
                    $sku
                );
                continue;
            }
            
            $product = wc_get_product($product_id);
            
            if (!$product) {
                $results['errors'][] = sprintf(
                    __('Failed to load product with SKU "%s".', 'smart-restock-waitlist'),
                    $sku
                );
                continue;
            }
            
            // Restock the product
            $result = SRWM_Waitlist::restock_and_notify($product_id, $quantity);
            
            if ($result) {
                $results['success']++;
                
                // Log the restock action
                $this->log_csv_restock($product_id, $quantity, $sku);
            } else {
                $results['errors'][] = sprintf(
                    __('Failed to restock product "%s" (SKU: %s).', 'smart-restock-waitlist'),
                    $product->get_name(),
                    $sku
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Store upload for approval
     */
    private function store_for_approval($token, $file, $csv_data) {
        global $wpdb;
        
        // Ensure CSV approvals table exists
        global $srwm_plugin;
        if ($srwm_plugin) {
            $srwm_plugin->ensure_csv_approvals_table();
        }
        
        // Get supplier email from token
        $token_data = $wpdb->get_row($wpdb->prepare(
            "SELECT supplier_email FROM {$wpdb->prefix}srwm_csv_tokens WHERE token = %s",
            $token
        ));
        
        if (!$token_data) {
            wp_die(__('Invalid upload token.', 'smart-restock-waitlist'));
        }
        
        // Store in approvals table
        $result = $wpdb->insert(
            $wpdb->prefix . 'srwm_csv_approvals',
            array(
                'token' => $token,
                'supplier_email' => $token_data->supplier_email,
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'upload_data' => json_encode($csv_data),
                'status' => 'pending',
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            // Mark token as used
            $this->mark_token_used($token);
            
            // Send notification email to admin
            $this->send_admin_notification($token_data->supplier_email, $file['name'], count($csv_data));
            
            // Display approval pending message
            $this->display_approval_pending_message($token_data->supplier_email);
        } else {
            wp_die(__('Failed to store upload for approval. Please try again.', 'smart-restock-waitlist'));
        }
    }
    
    /**
     * Send admin notification for pending approval
     */
    private function send_admin_notification($supplier_email, $file_name, $row_count) {
        $admin_email = get_option('admin_email');
        $subject = __('CSV Upload Pending Approval', 'smart-restock-waitlist');
        
        $message = sprintf(
            __('A new CSV upload requires your approval:
            
Supplier: %s
File: %s
Rows: %d

Please review and approve/reject this upload in your admin dashboard.', 'smart-restock-waitlist'),
            $supplier_email,
            $file_name,
            $row_count
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Display approval pending message
     */
    private function display_approval_pending_message($supplier_email) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Upload Submitted for Approval', 'smart-restock-waitlist'); ?></title>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .container {
                    max-width: 600px;
                    background: white;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
                    overflow: hidden;
                    text-align: center;
                }
                
                .header {
                    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
                    color: white;
                    padding: 40px 30px;
                    position: relative;
                }
                
                .header-icon {
                    font-size: 4rem;
                    margin-bottom: 20px;
                }
                
                .header h1 {
                    font-size: 2rem;
                    margin: 0;
                    font-weight: 700;
                }
                
                .content {
                    padding: 40px;
                }
                
                .status-message {
                    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                    border: 2px solid #f59e0b;
                    border-radius: 15px;
                    padding: 30px;
                    margin-bottom: 30px;
                }
                
                .status-message h2 {
                    color: #92400e;
                    margin-bottom: 15px;
                    font-size: 1.3rem;
                }
                
                .status-message p {
                    color: #78350f;
                    line-height: 1.6;
                    margin: 0;
                }
                
                .info-box {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 25px;
                    margin-bottom: 25px;
                }
                
                .info-box h3 {
                    color: #374151;
                    margin-bottom: 15px;
                    font-size: 1.1rem;
                }
                
                .info-box ul {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }
                
                .info-box li {
                    color: #6b7280;
                    padding: 8px 0;
                    padding-left: 25px;
                    position: relative;
                }
                
                .info-box li::before {
                    content: '✓';
                    position: absolute;
                    left: 0;
                    color: #10b981;
                    font-weight: bold;
                }
                
                .contact-info {
                    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                    border: 2px solid #3b82f6;
                    border-radius: 12px;
                    padding: 25px;
                }
                
                .contact-info h3 {
                    color: #1e40af;
                    margin-bottom: 15px;
                    font-size: 1.1rem;
                }
                
                .contact-info p {
                    color: #1e40af;
                    margin: 0;
                    line-height: 1.6;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="header-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h1><?php _e('Upload Submitted', 'smart-restock-waitlist'); ?></h1>
                </div>
                
                <div class="content">
                    <div class="status-message">
                        <h2>
                            <i class="fas fa-hourglass-half"></i> 
                            <?php _e('Pending Admin Approval', 'smart-restock-waitlist'); ?>
                        </h2>
                        <p><?php _e('Your CSV upload has been successfully submitted and is now waiting for admin approval. You will receive an email notification once the upload has been processed.', 'smart-restock-waitlist'); ?></p>
                    </div>
                    
                    <div class="info-box">
                        <h3>
                            <i class="fas fa-info-circle"></i> 
                            <?php _e('What happens next?', 'smart-restock-waitlist'); ?>
                        </h3>
                        <ul>
                            <li><?php _e('Admin will review your upload data', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('Stock will be updated upon approval', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('You\'ll receive email notification of the result', 'smart-restock-waitlist'); ?></li>
                            <li><?php _e('Customers on waitlist will be notified automatically', 'smart-restock-waitlist'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="contact-info">
                        <h3>
                            <i class="fas fa-envelope"></i> 
                            <?php _e('Contact Information', 'smart-restock-waitlist'); ?>
                        </h3>
                        <p><?php _e('If you have any questions about your upload, please contact the admin team. Your upload reference is:', 'smart-restock-waitlist'); ?> <strong><?php echo esc_html($supplier_email); ?></strong></p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Display upload results
     */
    private function display_upload_results($results) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Upload Results', 'smart-restock-waitlist'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background-color: #27ae60;
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .content {
                    padding: 30px;
                }
                .result-summary {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                }
                .success-count {
                    color: #27ae60;
                    font-size: 24px;
                    font-weight: bold;
                }
                .error-count {
                    color: #e74c3c;
                    font-size: 24px;
                    font-weight: bold;
                }
                .error-list {
                    background-color: #f8d7da;
                    border: 1px solid #f5c6cb;
                    border-radius: 5px;
                    padding: 15px;
                    margin-top: 15px;
                }
                .error-list h4 {
                    margin-top: 0;
                    color: #721c24;
                }
                .error-list ul {
                    margin: 0;
                    padding-left: 20px;
                }
                .error-list li {
                    margin-bottom: 5px;
                    color: #721c24;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Upload Complete!', 'smart-restock-waitlist'); ?></h1>
                </div>
                
                <div class="content">
                    <div class="result-summary">
                        <h3><?php _e('Upload Summary', 'smart-restock-waitlist'); ?></h3>
                        <p>
                            <span class="success-count"><?php echo $results['success']; ?></span> 
                            <?php _e('products restocked successfully', 'smart-restock-waitlist'); ?>
                        </p>
                        
                        <?php if ($results['skipped'] > 0): ?>
                            <p><?php echo $results['skipped']; ?> <?php _e('rows skipped (invalid data)', 'smart-restock-waitlist'); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($results['errors'])): ?>
                            <p>
                                <span class="error-count"><?php echo count($results['errors']); ?></span> 
                                <?php _e('errors encountered', 'smart-restock-waitlist'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($results['errors'])): ?>
                        <div class="error-list">
                            <h4><?php _e('Errors:', 'smart-restock-waitlist'); ?></h4>
                            <ul>
                                <?php foreach ($results['errors'] as $error): ?>
                                    <li><?php echo esc_html($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <p style="margin-top: 30px; font-size: 14px; color: #666;">
                        <?php _e('All waiting customers have been notified automatically for successfully restocked products.', 'smart-restock-waitlist'); ?>
                    </p>
                    
                    <p style="margin-top: 20px; font-size: 14px; color: #666;">
                        <?php _e('You can close this window now.', 'smart-restock-waitlist'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Generate CSV upload token for supplier
     */
    public function generate_csv_token($supplier_email) {
        global $wpdb;
        
        $token = wp_generate_password(32, false);
        $expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return false;
        }
        
        $result = $wpdb->insert(
            $table,
            array(
                'token' => $token,
                'supplier_email' => $supplier_email,
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Mark token as used
     */
    private function mark_token_used($token) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_csv_tokens';
        
        $wpdb->update(
            $table,
            array('used' => 1, 'used_at' => current_time('mysql')),
            array('token' => $token),
            array('%d', '%s'),
            array('%s')
        );
    }
    
    /**
     * Log CSV restock action
     */
    private function log_csv_restock($product_id, $quantity, $sku) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'srwm_restock_logs';
        
        $wpdb->insert(
            $table,
            array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'method' => 'csv_upload',
                'ip_address' => $this->get_client_ip(),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    /**
     * AJAX handler for generating CSV upload link (removed - handled in main plugin)
     * This method was causing conflicts with the main plugin's AJAX handler
     */
    
    /**
     * Check if Pro version is active
     */
    private function is_pro_active() {
        return get_option('smart-restock-waitlist-manager_license_status', 'inactive') === 'valid';
    }
    
    /**
     * Display Pro required message
     */
    private function display_pro_required_message() {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('Pro Feature Required', 'smart-restock-waitlist'); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background-color: #dc3545;
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .content {
                    padding: 30px;
                    text-align: center;
                }
                .content h2 {
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                .content p {
                    color: #666;
                    line-height: 1.6;
                    margin-bottom: 20px;
                }
                .btn {
                    display: inline-block;
                    background-color: #007cba;
                    color: white;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 4px;
                    font-weight: bold;
                }
                .btn:hover {
                    background-color: #005a87;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php _e('Pro Feature Required', 'smart-restock-waitlist'); ?></h1>
                </div>
                <div class="content">
                    <h2><?php _e('CSV Upload Feature', 'smart-restock-waitlist'); ?></h2>
                    <p><?php _e('This feature requires a valid Pro license to function. Please activate your Pro license to use the CSV upload functionality.', 'smart-restock-waitlist'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=smart-restock-waitlist-license'); ?>" class="btn"><?php _e('Manage License', 'smart-restock-waitlist'); ?></a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}