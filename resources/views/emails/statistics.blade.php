{{-- File: resources/views/emails/device-statistics.blade.php --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo Thống kê Tài sản Thiết bị</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f6f9;
            padding: 20px;
        }
        
        .email-container {
            max-width: 700px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #1565C0 0%, #42A5F5 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        
        .message-section {
            background: #f8f9ff;
            border-left: 4px solid #1565C0;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        
        .stats-container {
            margin: 25px 0;
        }
        
        .stats-title {
            font-size: 18px;
            font-weight: 600;
            color: #1565C0;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .stats-title::before {
            content: "📊";
            margin-right: 8px;
            font-size: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-percentage {
            font-size: 12px;
            color: #28a745;
            margin-top: 5px;
        }
        
        /* Màu sắc theo trạng thái */
        .stat-card.using .stat-number { color: #28a745; }
        .stat-card.available .stat-number { color: #007bff; }
        .stat-card.broken .stat-number { color: #dc3545; }
        .stat-card.lost .stat-number { color: #6f42c1; }
        .stat-card.expiring .stat-number { color: #fd7e14; }
        .stat-card.total .stat-number { color: #1565C0; }
        
        .attachment-info {
            background: #e8f5e8;
            border: 1px solid #c3e6c3;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .attachment-title {
            font-size: 16px;
            font-weight: 600;
            color: #155724;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .attachment-title::before {
            content: "📎";
            margin-right: 8px;
            font-size: 18px;
        }
        
        .attachment-features {
            list-style: none;
            margin: 15px 0;
        }
        
        .attachment-features li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .attachment-features li::before {
            content: "✅";
            position: absolute;
            left: 0;
        }
        
        .alerts {
            margin: 25px 0;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert::before {
            font-size: 18px;
            margin-right: 10px;
        }
        
        .alert-warning::before { content: "⚠️"; }
        .alert-danger::before { content: "🚨"; }
        
        .report-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .report-details h3 {
            color: #1565C0;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 500;
            color: #495057;
        }
        
        .detail-value {
            color: #1565C0;
            font-weight: 600;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .footer h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .contact-item::before {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .contact-email::before { content: "📧"; }
        .contact-phone::before { content: "📱"; }
        .contact-web::before { content: "🌐"; }
        
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-style: italic;
            opacity: 0.8;
        }
        
        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .header, .content, .footer {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Báo cáo Thống kê Tài sản Thiết bị</h1>
            <p>Hệ thống Quản lý Tài sản - Công ty TNHH ABC</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Custom Message -->
            @if(isset($messageText) && $messageText)
            <div class="message-section">
                <strong>Nội dung:</strong><br>
                {!! nl2br(e($messageText)) !!}
            </div>
            @endif

            <p>Hệ thống đã tạo báo cáo thống kê tài sản thiết bị chi tiết. Dưới đây là tóm tắt thông tin quan trọng:</p>

            <!-- Statistics -->
            <div class="stats-container">
                <div class="stats-title">Tóm tắt Thống kê</div>
                
                <div class="stats-grid">
                    <div class="stat-card total">
                        <div class="stat-number">{{ number_format($statistics['total'] ?? 0) }}</div>
                        <div class="stat-label">Tổng thiết bị</div>
                    </div>
                    
                    <div class="stat-card using">
                        <div class="stat-number">{{ number_format($statistics['in_use'] ?? 0) }}</div>
                        <div class="stat-label">Đang sử dụng</div>
                        <div class="stat-percentage">
                            {{ ($statistics['total'] ?? 0) > 0 ? round(($statistics['in_use'] ?? 0)/($statistics['total'] ?? 1)*100, 1) : 0 }}%
                        </div>
                    </div>
                    
                    <div class="stat-card available">
                        <div class="stat-number">{{ number_format($statistics['available'] ?? 0) }}</div>
                        <div class="stat-label">Chưa sử dụng</div>
                        <div class="stat-percentage">
                            {{ ($statistics['total'] ?? 0) > 0 ? round(($statistics['available'] ?? 0)/($statistics['total'] ?? 1)*100, 1) : 0 }}%
                        </div>
                    </div>
                    
                    <div class="stat-card broken">
                        <div class="stat-number">{{ number_format($statistics['broken'] ?? 0) }}</div>
                        <div class="stat-label">Hư hỏng</div>
                        <div class="stat-percentage">
                            {{ ($statistics['total'] ?? 0) > 0 ? round(($statistics['broken'] ?? 0)/($statistics['total'] ?? 1)*100, 1) : 0 }}%
                        </div>
                    </div>
                    
                    <div class="stat-card lost">
                        <div class="stat-number">{{ number_format($statistics['lost'] ?? 0) }}</div>
                        <div class="stat-label">Mất thiết bị</div>
                        <div class="stat-percentage">
                            {{ ($statistics['total'] ?? 0) > 0 ? round(($statistics['lost'] ?? 0)/($statistics['total'] ?? 1)*100, 1) : 0 }}%
                        </div>
                    </div>
                    
                    @if(isset($statistics['expiring_soon']))
                    <div class="stat-card expiring">
                        <div class="stat-number">{{ number_format($statistics['expiring_soon'] ?? 0) }}</div>
                        <div class="stat-label">Sắp hết hạn</div>
                        <div class="stat-percentage">
                            {{ ($statistics['total'] ?? 0) > 0 ? round(($statistics['expiring_soon'] ?? 0)/($statistics['total'] ?? 1)*100, 1) : 0 }}%
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Alerts -->
            @if(isset($alerts) && count($alerts) > 0)
            <div class="alerts">
                @foreach($alerts as $alert)
                <div class="alert alert-{{ $alert['type'] ?? 'warning' }}">
                    {{ $alert['message'] }}
                </div>
                @endforeach
            </div>
            @endif

            <!-- Report Details -->
            <div class="report-details">
                <h3>Chi tiết Báo cáo</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Thời gian tạo báo cáo:</span>
                    <span class="detail-value">{{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('H:i:s, \n\g\à\y d/m/Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Kỳ báo cáo:</span>
                    <span class="detail-value">{{ $reportPeriod ?? 'Tháng ' . now()->format('m/Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Người tạo báo cáo:</span>
                    <span class="detail-value">{{ $createdBy ?? 'Hệ thống tự động' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Loại báo cáo:</span>
                    <span class="detail-value">{{ $reportType ?? 'Thống kê tổng quan' }}</span>
                </div>
            </div>

            <!-- Attachment Information -->
            @if(isset($hasAttachment) && $hasAttachment)
            <div class="attachment-info">
                <div class="attachment-title">File đính kèm</div>
                <p>Báo cáo chi tiết đã được đính kèm trong email này với các thông tin sau:</p>
                
                <ul class="attachment-features">
                    <li>Danh sách chi tiết tất cả thiết bị</li>
                    <li>Phân tích xu hướng theo thời gian</li>
                    <li>Báo cáo tài chính liên quan</li>
                    <li>Đề xuất hành động cần thiết</li>
                    <li>Biểu đồ và hình ảnh minh họa</li>
                </ul>
                
                <p><strong>Lưu ý:</strong> File đính kèm có định dạng Excel (.xlsx) để bạn có thể dễ dàng xử lý và phân tích dữ liệu.</p>
            </div>
            @endif

            <!-- Recommendations -->
            @if(isset($recommendations) && count($recommendations) > 0)
            <div class="report-details">
                <h3>Khuyến nghị</h3>
                @foreach($recommendations as $index => $recommendation)
                <div class="detail-row">
                    <span class="detail-label">{{ $index + 1 }}.</span>
                    <span class="detail-value">{{ $recommendation }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <p style="margin-top: 30px;">
                Cảm ơn quý khách đã sử dụng hệ thống quản lý tài sản của chúng tôi. 
                Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi qua thông tin bên dưới.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <h3>Thông tin Liên hệ</h3>
            
            <div class="contact-info">
                <div class="contact-item contact-email">
                    support@company-abc.com
                </div>
                <div class="contact-item contact-phone">
                    (+84) 123 456 789
                </div>
                <div class="contact-item contact-web">
                    www.company-abc.com
                </div>
            </div>
            
            <div class="signature">
                <p>Trân trọng,<br>
                <strong>Ban Quản lý Hệ thống</strong><br>
                Công ty TNHH ABC</p>
            </div>
        </div>
    </div>
</body>
</html>