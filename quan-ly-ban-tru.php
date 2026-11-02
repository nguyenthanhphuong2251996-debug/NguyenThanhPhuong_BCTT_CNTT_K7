<?php
/*
Plugin Name: Quản lý bán trú
Description: Hệ thống quản lý bán trú học sinh trường tiểu học
Version: 5.4
Author: Trường Tiểu học Nguyễn An Ninh
*/

if (!defined('ABSPATH')) {
    exit;
}

/* =====================================================
   PHÂN QUYỀN & VAI TRÒ HỆ THỐNG
===================================================== */
function qlbt_setup_roles() {
    $caps = array(
        'qlbt_access'      => 'Truy cập hệ thống bán trú',
        'qlbt_manage'      => 'Quản trị bán trú',
        'qlbt_hoc_sinh'    => 'Quản lý học sinh',
        'qlbt_diem_danh'   => 'Điểm danh',
        'qlbt_thuc_don'    => 'Quản lý thực đơn',
        'qlbt_suat_an'     => 'Quản lý suất ăn',
        'qlbt_phu_huynh'   => 'Quản lý phụ huynh',
        'qlbt_thu_phi'     => 'Quản lý thu phí',
        'qlbt_bao_cao'     => 'Xem báo cáo',
        'qlbt_backup'      => 'Sao lưu dữ liệu',
    );

    $roles = array(
        'qlbt_nhan_vien_cntt' => array('Nhân viên CNTT', array_keys($caps)),
        'qlbt_giao_vien'      => array('Giáo viên', array('qlbt_access','qlbt_diem_danh','qlbt_hoc_sinh','qlbt_suat_an')),
        'qlbt_thu_quy'        => array('Thủ quỹ', array('qlbt_access','qlbt_thu_phi','qlbt_bao_cao','qlbt_hoc_sinh')),
        'qlbt_quan_ly'        => array('Quản lý bán trú', array('qlbt_access','qlbt_manage','qlbt_hoc_sinh','qlbt_diem_danh','qlbt_thuc_don','qlbt_suat_an','qlbt_phu_huynh','qlbt_thu_phi','qlbt_bao_cao')),
    );

    foreach ($roles as $slug => $data) {
        $role = get_role($slug);
        if (!$role) {
            $role = add_role($slug, $data[0], array('read' => true));
        }
        if ($role) {
            foreach ($data[1] as $cap) {
                $role->add_cap($cap);
            }
        }
    }

    $admin = get_role('administrator');
    if ($admin) {
        foreach (array_keys($caps) as $cap) {
            $admin->add_cap($cap);
        }
    }
}
add_action('init', 'qlbt_setup_roles');

/* =====================================================
   GIAO DIỆN ĐĂNG NHẬP THƯƠNG HIỆU NHÀ TRƯỜNG
===================================================== */
function qlbt_login_branding() {
    ?>
    <style>
        body.login{background:linear-gradient(135deg,#eff6ff 0%,#f8fafc 50%,#e0f2fe 100%)}
        .login h1 a{background-image:none!important;width:auto!important;height:auto!important;text-indent:0!important;font-size:0!important;margin-bottom:12px!important}
        .login h1 a:before{content:'🍱';display:block;font-size:54px;line-height:1;margin-bottom:10px}
        .login h1 a:after{content:'QUẢN LÝ BÁN TRÚ';display:block;color:#173b8f;font-size:24px;font-weight:800;letter-spacing:.5px}
        .login form{border:0!important;border-radius:18px!important;box-shadow:0 15px 40px rgba(15,23,42,.10)!important;padding:28px!important}
        .login #nav,.login #backtoblog{text-align:center}
        .login .button-primary{background:#2563eb!important;border-color:#2563eb!important;border-radius:9px!important;box-shadow:none!important}
        .qlbt-login-school{max-width:320px;margin:-8px auto 20px;text-align:center;color:#64748b;font-size:13px;line-height:1.5}
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'qlbt_login_branding');
function qlbt_login_message($message) {
    return '<div class="qlbt-login-school">Trường Tiểu học Nguyễn An Ninh<br>Hệ thống quản lý học sinh bán trú</div>' . $message;
}
add_filter('login_message','qlbt_login_message');
function qlbt_login_logo_url() { return home_url('/'); }
add_filter('login_headerurl','qlbt_login_logo_url');
function qlbt_login_logo_title() { return 'Quản lý bán trú - Trường Tiểu học Nguyễn An Ninh'; }
add_filter('login_headertext','qlbt_login_logo_title');


/* =====================================================
   TẠO DATABASE
===================================================== */

function qlbt_create_database() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_hoc_sinh = $wpdb->prefix . 'bt_hoc_sinh';
    $table_diem_danh = $wpdb->prefix . 'bt_diem_danh';
    $table_thuc_don = $wpdb->prefix . 'bt_thuc_don';
    $table_thu_phi = $wpdb->prefix . 'bt_thu_phi';

    $sql_hoc_sinh = "CREATE TABLE $table_hoc_sinh (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ma_hoc_sinh varchar(50) NOT NULL,
        ho_ten varchar(255) NOT NULL,
        ngay_sinh date DEFAULT NULL,
        gioi_tinh varchar(20) DEFAULT '',
        lop varchar(50) DEFAULT '',
        nam_hoc varchar(20) DEFAULT '',
        ho_ten_phu_huynh varchar(255) DEFAULT '',
        so_dien_thoai varchar(30) DEFAULT '',
        dia_chi text,
        trang_thai varchar(50) DEFAULT 'Đang bán trú',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY ma_hoc_sinh (ma_hoc_sinh)
    ) $charset_collate;";

    $sql_diem_danh = "CREATE TABLE $table_diem_danh (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        hoc_sinh_id bigint(20) unsigned NOT NULL,
        ngay date NOT NULL,
        trang_thai varchar(30) NOT NULL DEFAULT 'co_mat',
        ghi_chu text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY hoc_sinh_ngay (hoc_sinh_id, ngay),
        KEY ngay (ngay),
        KEY hoc_sinh_id (hoc_sinh_id)
    ) $charset_collate;";

    $sql_thuc_don = "CREATE TABLE $table_thuc_don (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ngay date NOT NULL,
        bua_sang text,
        bua_trua text,
        bua_xe text,
        ghi_chu text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY ngay (ngay)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql_hoc_sinh);
    dbDelta($sql_diem_danh);
    dbDelta($sql_thuc_don);

    $sql_thu_phi = "CREATE TABLE $table_thu_phi (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        hoc_sinh_id bigint(20) unsigned NOT NULL,
        thang varchar(7) NOT NULL,
        don_gia decimal(12,2) NOT NULL DEFAULT 0,
        so_ngay int(11) NOT NULL DEFAULT 0,
        phai_thu decimal(12,2) NOT NULL DEFAULT 0,
        da_thu decimal(12,2) NOT NULL DEFAULT 0,
        trang_thai varchar(30) NOT NULL DEFAULT 'Chưa thu',
        ghi_chu text,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY hoc_sinh_thang (hoc_sinh_id, thang),
        KEY thang (thang)
    ) $charset_collate;";
    dbDelta($sql_thu_phi);
}

register_activation_hook(
    __FILE__,
    'qlbt_create_database'
);


/* =====================================================
   DASHBOARD
===================================================== */

function qlbt_admin_style() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || strpos((string)$screen->id, 'qlbt') === false) {
        return;
    }
    ?>
    <style>
        .qlbt-app{max-width:1400px;margin:20px 20px 40px 0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif}
        .qlbt-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#173b8f 0%,#2563eb 55%,#38bdf8 100%);border-radius:22px;padding:34px 38px;color:#fff;box-shadow:0 12px 30px rgba(37,99,235,.20);margin-bottom:22px}
        .qlbt-hero:before,.qlbt-hero:after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.09);pointer-events:none}
        .qlbt-hero:before{width:260px;height:260px;right:-80px;top:-120px}.qlbt-hero:after{width:180px;height:180px;right:180px;bottom:-120px}
        .qlbt-hero-content{position:relative;z-index:2;display:flex;justify-content:space-between;align-items:center;gap:25px}
        .qlbt-brand{display:flex;align-items:center;gap:18px}.qlbt-logo{width:68px;height:68px;border-radius:18px;background:#fff;display:flex;align-items:center;justify-content:center;font-size:38px;box-shadow:0 8px 20px rgba(0,0,0,.14)}
        .qlbt-hero h1{margin:0 0 7px;font-size:30px;line-height:1.15;color:#fff}.qlbt-hero p{margin:0;color:rgba(255,255,255,.88);font-size:15px}.qlbt-date{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);border-radius:14px;padding:12px 16px;text-align:right;backdrop-filter:blur(5px)}
        .qlbt-date strong{display:block;font-size:17px;color:#fff}.qlbt-date span{font-size:12px;color:rgba(255,255,255,.78)}
        .qlbt-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;margin-bottom:18px}.qlbt-card{background:#fff;border:1px solid #e5e7eb;border-radius:17px;padding:22px;box-shadow:0 4px 16px rgba(15,23,42,.05)}
        .qlbt-stat{position:relative;overflow:hidden}.qlbt-stat:after{content:"";position:absolute;width:90px;height:90px;border-radius:50%;right:-30px;top:-35px;background:rgba(37,99,235,.06)}
        .qlbt-stat-top{display:flex;justify-content:space-between;align-items:center;color:#64748b;font-size:13px;font-weight:600}.qlbt-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:21px;background:#eff6ff}.qlbt-number{font-size:31px;font-weight:800;color:#0f172a;margin-top:13px;line-height:1}.qlbt-note{margin-top:8px;font-size:12px;color:#94a3b8}.qlbt-blue .qlbt-icon{background:#eaf2ff}.qlbt-green .qlbt-icon{background:#ecfdf5}.qlbt-red .qlbt-icon{background:#fff1f2}.qlbt-orange .qlbt-icon{background:#fff7ed}
        .qlbt-main-grid{display:grid;grid-template-columns:1.5fr 1fr;gap:18px}.qlbt-card h2{font-size:17px;margin:0;color:#0f172a}.qlbt-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}.qlbt-section-head small{color:#94a3b8}
        .qlbt-progress-wrap{background:#f8fafc;border-radius:14px;padding:17px}.qlbt-progress-row{display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px}.qlbt-progress{height:10px;background:#e2e8f0;border-radius:20px;overflow:hidden}.qlbt-progress>span{display:block;height:100%;border-radius:20px;background:linear-gradient(90deg,#2563eb,#38bdf8)}
        .qlbt-money{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:15px}.qlbt-money-box{padding:15px;border-radius:13px;background:#f8fafc}.qlbt-money-box b{font-size:20px;display:block;margin-top:5px}.qlbt-money-box small{color:#64748b}
        .qlbt-actions{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}.qlbt-action{text-decoration:none!important;border:1px solid #e2e8f0;border-radius:13px;padding:14px 12px;background:#fff;transition:.18s;display:flex;align-items:center;gap:10px}.qlbt-action:hover{transform:translateY(-2px);border-color:#93c5fd;box-shadow:0 7px 16px rgba(37,99,235,.10);background:#f8fbff}.qlbt-action .aicon{width:34px;height:34px;border-radius:9px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:18px}.qlbt-action strong{display:block;color:#0f172a;font-size:13px}.qlbt-action span{display:block;color:#94a3b8;font-size:11px;margin-top:2px}
        .qlbt-info{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:18px}.qlbt-info .qlbt-card{padding:18px}.qlbt-mini{display:flex;gap:12px;align-items:center}.qlbt-mini-icon{width:40px;height:40px;border-radius:11px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:19px}.qlbt-mini b{font-size:14px}.qlbt-mini span{display:block;color:#64748b;font-size:12px;margin-top:3px}
        .qlbt-footer{text-align:center;color:#94a3b8;font-size:12px;padding:22px 0 5px}.qlbt-footer b{color:#475569}
        .qlbt-table{width:100%;border-collapse:separate;border-spacing:0}.qlbt-table th{background:#f8fafc;color:#475569;font-weight:700;font-size:12px;text-transform:uppercase}.qlbt-table th,.qlbt-table td{padding:11px 13px;border-bottom:1px solid #edf2f7}.qlbt-table tr:last-child td{border-bottom:0}
        @media(max-width:1100px){.qlbt-grid{grid-template-columns:repeat(2,1fr)}.qlbt-main-grid,.qlbt-info{grid-template-columns:1fr}.qlbt-hero-content{align-items:flex-start;flex-direction:column}.qlbt-date{text-align:left}}
        /* ===== APP UI 5.2: đồng bộ toàn bộ màn hình ===== */
        #wpcontent{background:#f1f5f9}
        #wpcontent .wrap{max-width:1400px}
        #wpcontent .wrap>h1{font-size:25px;font-weight:800;color:#0f172a;margin:22px 0 7px;letter-spacing:-.3px}
        #wpcontent .wrap>h1:after{content:"";display:block;width:48px;height:4px;border-radius:10px;background:linear-gradient(90deg,#2563eb,#38bdf8);margin-top:10px}
        #wpcontent .wrap>p{color:#64748b}
        #wpcontent .wrap>hr{border:0;border-top:1px solid #e2e8f0;margin:18px 0}
        #wpcontent .widefat,#wpcontent table.widefat{border:0!important;border-radius:14px;overflow:hidden;box-shadow:0 5px 20px rgba(15,23,42,.06);background:#fff}
        #wpcontent .widefat thead th{background:#eef5ff!important;color:#334155!important;border-bottom:1px solid #dbeafe!important;font-size:12px;text-transform:uppercase;letter-spacing:.25px;padding:13px 12px}
        #wpcontent .widefat td{padding:12px;border-bottom:1px solid #f1f5f9!important;color:#334155}
        #wpcontent .widefat tbody tr:hover{background:#f8fbff}
        #wpcontent .button{border-radius:9px;border-color:#dbe3ef;box-shadow:none;min-height:34px}
        #wpcontent .button-primary{background:#2563eb;border-color:#2563eb;box-shadow:0 5px 12px rgba(37,99,235,.16)}
        #wpcontent input[type=text],#wpcontent input[type=search],#wpcontent input[type=number],#wpcontent input[type=date],#wpcontent input[type=month],#wpcontent select,#wpcontent textarea{border:1px solid #dbe3ef;border-radius:9px;min-height:36px;padding:5px 10px;box-shadow:none}
        #wpcontent input:focus,#wpcontent select:focus,#wpcontent textarea:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,.10);outline:0}
        #wpcontent .wrap form[method=get],#wpcontent .wrap form[method=post]{background:#fff!important;border:1px solid #e5e7eb!important;border-radius:14px!important;box-shadow:0 4px 15px rgba(15,23,42,.04);padding:18px!important}
        #wpcontent .notice{border-radius:10px}
        .qlbt-page-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;box-shadow:0 5px 20px rgba(15,23,42,.05);margin:18px 0}
        .qlbt-badge{display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:700;background:#eff6ff;color:#1d4ed8}
        .qlbt-badge-green{background:#ecfdf5;color:#047857}.qlbt-badge-red{background:#fff1f2;color:#be123c}.qlbt-badge-orange{background:#fff7ed;color:#c2410c}
        #wpadminbar{background:#0f172a!important}
        .qlbt-chart-grid{display:grid;grid-template-columns:1.15fr 1fr;gap:18px;margin-top:18px}.qlbt-chart-card{background:#fff;border:1px solid #e5e7eb;border-radius:17px;padding:22px;box-shadow:0 4px 16px rgba(15,23,42,.05)}.qlbt-chart-title{font-size:17px;font-weight:800;color:#0f172a;margin-bottom:18px}.qlbt-bars{display:flex;align-items:flex-end;gap:12px;height:190px;padding:10px 4px 0}.qlbt-bar-col{flex:1;text-align:center;min-width:0}.qlbt-bar-value{font-size:11px;font-weight:800;color:#2563eb;margin-bottom:6px}.qlbt-bar{height:125px;display:flex;align-items:flex-end;justify-content:center}.qlbt-bar i{display:block;width:42px;max-width:75%;min-height:4px;border-radius:9px 9px 3px 3px;background:linear-gradient(180deg,#38bdf8,#2563eb)}.qlbt-bar-label{font-size:11px;color:#64748b;margin-top:8px;font-weight:700}.qlbt-class-list{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.qlbt-class-item{background:#f8fafc;border-radius:12px;padding:12px}.qlbt-class-item .top{display:flex;justify-content:space-between;font-size:12px;font-weight:700}.qlbt-class-item .track{height:7px;background:#e2e8f0;border-radius:10px;margin-top:8px;overflow:hidden}.qlbt-class-item .track span{display:block;height:100%;border-radius:10px;background:linear-gradient(90deg,#2563eb,#60a5fa)}.qlbt-print-actions{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0}.qlbt-print-paper{background:#fff;max-width:850px;margin:20px auto;padding:42px 50px;border:1px solid #e5e7eb;box-shadow:0 10px 30px rgba(15,23,42,.08)}.qlbt-print-head{text-align:center;border-bottom:2px solid #0f172a;padding-bottom:18px;margin-bottom:22px}.qlbt-print-head h1{font-size:22px;margin:8px 0;color:#0f172a}.qlbt-print-head p{margin:3px 0;color:#475569}.qlbt-receipt-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px}.qlbt-print-table{width:100%;border-collapse:collapse}.qlbt-print-table th,.qlbt-print-table td{border:1px solid #cbd5e1;padding:10px}.qlbt-print-table th{background:#f1f5f9}.qlbt-sign{display:grid;grid-template-columns:1fr 1fr;gap:40px;text-align:center;margin-top:45px}.qlbt-sign div{min-height:100px}.qlbt-about-hero{background:linear-gradient(135deg,#173b8f,#2563eb,#38bdf8);color:#fff;border-radius:20px;padding:34px;margin:20px 0}.qlbt-about-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.qlbt-about-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:22px;box-shadow:0 4px 16px rgba(15,23,42,.05)}.qlbt-about-card h2{margin-top:0;color:#0f172a}.qlbt-step{display:flex;gap:12px;margin:13px 0}.qlbt-step b{width:30px;height:30px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;flex:none}@media(max-width:900px){.qlbt-chart-grid,.qlbt-about-grid{grid-template-columns:1fr}.qlbt-class-list{grid-template-columns:repeat(2,1fr)}}@media print{#adminmenumain,#wpadminbar,#wpfooter,.notice,.update-nag,.qlbt-no-print,.wrap>h1,.wrap>p,.wrap>hr,form,.button{display:none!important}#wpcontent{margin-left:0!important;background:#fff!important}.qlbt-print-paper{box-shadow:none;border:0;margin:0;max-width:none;padding:20px}.qlbt-print-table{font-size:12px}}
        @media(max-width:700px){#wpcontent .wrap{margin-right:10px}.qlbt-page-card{padding:14px}.qlbt-hero{padding:25px 22px}.qlbt-hero h1{font-size:24px}.qlbt-money{grid-template-columns:1fr}}

    </style>
    <?php
}
add_action('admin_head', 'qlbt_admin_style');

function qlbt_dashboard() {
    global $wpdb;

    $table_hs = $wpdb->prefix . 'bt_hoc_sinh';
    $table_dd = $wpdb->prefix . 'bt_diem_danh';
    $table_tp = $wpdb->prefix . 'bt_thu_phi';

    $today = current_time('Y-m-d');
    $month = current_time('Y-m');

    $tong_hoc_sinh = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_hs WHERE trang_thai='Đang bán trú'");
    $co_mat = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_dd dd INNER JOIN $table_hs hs ON hs.id=dd.hoc_sinh_id WHERE dd.ngay=%s AND dd.trang_thai='co_mat' AND hs.trang_thai='Đang bán trú'", $today));
    $vang = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_dd dd INNER JOIN $table_hs hs ON hs.id=dd.hoc_sinh_id WHERE dd.ngay=%s AND dd.trang_thai='vang' AND hs.trang_thai='Đang bán trú'", $today));
    $co_phep = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_dd dd INNER JOIN $table_hs hs ON hs.id=dd.hoc_sinh_id WHERE dd.ngay=%s AND dd.trang_thai='co_phep' AND hs.trang_thai='Đang bán trú'", $today));
    $tong_phai_thu = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(phai_thu),0) FROM $table_tp WHERE thang=%s", $month));
    $tong_da_thu = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(da_thu),0) FROM $table_tp WHERE thang=%s", $month));
    $tong_con_no = max(0, $tong_phai_thu - $tong_da_thu);
    $ty_le_co_mat = $tong_hoc_sinh > 0 ? min(100, round(($co_mat / $tong_hoc_sinh) * 100)) : 0;
    $ty_le_thu = $tong_phai_thu > 0 ? min(100, round(($tong_da_thu / $tong_phai_thu) * 100)) : 0;
    $ngay_hien_thi = wp_date('l, d/m/Y', current_time('timestamp'));

    $actions = array(
        array('qlbt-hoc-sinh','👨‍🎓','Học sinh','Quản lý danh sách'),
        array('qlbt-diem-danh','📋','Điểm danh','Theo dõi hàng ngày'),
        array('qlbt-thuc-don','🍲','Thực đơn','Món ăn trong ngày'),
        array('qlbt-suat-an','🍽️','Suất ăn','Thống kê số suất'),
        array('qlbt-phu-huynh','👨‍👩‍👧','Phụ huynh','Thông tin liên hệ'),
        array('qlbt-thu-phi','💰','Thu phí','Theo dõi học phí'),
        array('qlbt-bao-cao','📊','Báo cáo','Tổng hợp dữ liệu'),
        array('qlbt-sao-luu','💾','Sao lưu','Bảo vệ dữ liệu'),
    );
    ?>
    <div class="wrap qlbt-app">
        <div class="qlbt-hero">
            <div class="qlbt-hero-content">
                <div class="qlbt-brand">
                    <div class="qlbt-logo">🍱</div>
                    <div>
                        <h1>QUẢN LÝ BÁN TRÚ</h1>
                        <p>Hệ thống quản lý học sinh, điểm danh, thực đơn, suất ăn và thu phí</p>
                        <p><strong>Trường Tiểu học Nguyễn An Ninh</strong></p>
                    </div>
                </div>
                <div class="qlbt-date"><strong><?php echo esc_html(wp_date('d/m/Y', current_time('timestamp'))); ?></strong><span><?php echo esc_html($ngay_hien_thi); ?></span></div>
            </div>
        </div>

        <div class="qlbt-grid">
            <div class="qlbt-card qlbt-stat qlbt-blue"><div class="qlbt-stat-top"><span>HỌC SINH BÁN TRÚ</span><div class="qlbt-icon">👨‍🎓</div></div><div class="qlbt-number"><?php echo number_format_i18n($tong_hoc_sinh); ?></div><div class="qlbt-note">Đang tham gia bán trú</div></div>
            <div class="qlbt-card qlbt-stat qlbt-green"><div class="qlbt-stat-top"><span>CÓ MẶT HÔM NAY</span><div class="qlbt-icon">✅</div></div><div class="qlbt-number" style="color:#16a34a"><?php echo number_format_i18n($co_mat); ?></div><div class="qlbt-note">Tỷ lệ có mặt: <?php echo esc_html($ty_le_co_mat); ?>%</div></div>
            <div class="qlbt-card qlbt-stat qlbt-red"><div class="qlbt-stat-top"><span>VẮNG HÔM NAY</span><div class="qlbt-icon">❌</div></div><div class="qlbt-number" style="color:#dc2626"><?php echo number_format_i18n($vang); ?></div><div class="qlbt-note">Có phép: <?php echo number_format_i18n($co_phep); ?> học sinh</div></div>
            <div class="qlbt-card qlbt-stat qlbt-orange"><div class="qlbt-stat-top"><span>CÒN PHẢI THU</span><div class="qlbt-icon">💰</div></div><div class="qlbt-number" style="color:#d97706;font-size:25px"><?php echo number_format_i18n($tong_con_no); ?> đ</div><div class="qlbt-note">Tháng <?php echo esc_html(wp_date('m/Y', current_time('timestamp'))); ?></div></div>
        </div>

        <div class="qlbt-main-grid">
            <div class="qlbt-card">
                <div class="qlbt-section-head"><h2>📈 Tình hình bán trú hôm nay</h2><small><?php echo esc_html(wp_date('d/m/Y', current_time('timestamp'))); ?></small></div>
                <div class="qlbt-progress-wrap">
                    <div class="qlbt-progress-row"><span>Chuyên cần</span><strong><?php echo esc_html($ty_le_co_mat); ?>%</strong></div>
                    <div class="qlbt-progress"><span style="width:<?php echo esc_attr($ty_le_co_mat); ?>%"></span></div>
                    <div style="display:flex;gap:22px;margin-top:13px;font-size:12px;color:#64748b"><span>🟢 Có mặt: <b style="color:#16a34a"><?php echo number_format_i18n($co_mat); ?></b></span><span>🔴 Vắng: <b style="color:#dc2626"><?php echo number_format_i18n($vang); ?></b></span><span>🟡 Có phép: <b style="color:#d97706"><?php echo number_format_i18n($co_phep); ?></b></span></div>
                </div>
                <div class="qlbt-money">
                    <div class="qlbt-money-box"><small>💵 Tổng phải thu tháng này</small><b><?php echo number_format_i18n($tong_phai_thu); ?> đ</b></div>
                    <div class="qlbt-money-box"><small>💳 Đã thu</small><b style="color:#16a34a"><?php echo number_format_i18n($tong_da_thu); ?> đ</b></div>
                </div>
                <div style="margin-top:15px"><div class="qlbt-progress-row"><span>Tiến độ thu phí</span><strong><?php echo esc_html($ty_le_thu); ?>%</strong></div><div class="qlbt-progress"><span style="width:<?php echo esc_attr($ty_le_thu); ?>%;background:linear-gradient(90deg,#16a34a,#4ade80)"></span></div></div>
            </div>

            <div class="qlbt-card">
                <div class="qlbt-section-head"><h2>⚡ Truy cập nhanh</h2><small>Thao tác</small></div>
                <div class="qlbt-actions">
                    <?php foreach ($actions as $a): ?>
                        <a class="qlbt-action" href="<?php echo esc_url(admin_url('admin.php?page='.$a[0])); ?>"><div class="aicon"><?php echo esc_html($a[1]); ?></div><div><strong><?php echo esc_html($a[2]); ?></strong><span><?php echo esc_html($a[3]); ?></span></div></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="qlbt-info">
            <div class="qlbt-card"><div class="qlbt-mini"><div class="qlbt-mini-icon">📅</div><div><b>Điểm danh hàng ngày</b><span>Ghi nhận có mặt, vắng, có phép nhanh chóng.</span></div></div></div>
            <div class="qlbt-card"><div class="qlbt-mini"><div class="qlbt-mini-icon">🍽️</div><div><b>Quản lý suất ăn</b><span>Thống kê số suất theo ngày và theo lớp.</span></div></div></div>
            <div class="qlbt-card"><div class="qlbt-mini"><div class="qlbt-mini-icon">🔐</div><div><b>Dữ liệu an toàn</b><span>Thường xuyên sao lưu dữ liệu hệ thống.</span></div></div></div>
        </div>

        <?php
        $chart_classes = array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C');
        $class_counts = array(); $class_max = 1;
        foreach ($chart_classes as $cc) {
            $num = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_hs WHERE trang_thai='Đang bán trú' AND lop=%s", $cc));
            $class_counts[$cc]=$num; if($num>$class_max)$class_max=$num;
        }
        $week_labels=array(); $week_values=array();
        for($d=6;$d>=0;$d--){
            $date=wp_date('Y-m-d',strtotime('-'.$d.' days',current_time('timestamp')));
            $week_labels[]=wp_date('d/m',strtotime($date));
            $week_values[]=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_dd dd INNER JOIN $table_hs hs ON hs.id=dd.hoc_sinh_id WHERE dd.ngay=%s AND dd.trang_thai='co_mat' AND hs.trang_thai='Đang bán trú'",$date));
        }
        $week_max=max(1,max($week_values));
        ?>
        <div class="qlbt-chart-grid">
            <div class="qlbt-chart-card">
                <div class="qlbt-chart-title">📈 Số học sinh có mặt 7 ngày gần nhất</div>
                <div class="qlbt-bars">
                <?php foreach($week_values as $i=>$v): $height=max(4,round(($v/$week_max)*115)); ?>
                    <div class="qlbt-bar-col"><div class="qlbt-bar-value"><?php echo number_format_i18n($v); ?></div><div class="qlbt-bar"><i style="height:<?php echo esc_attr($height); ?>px"></i></div><div class="qlbt-bar-label"><?php echo esc_html($week_labels[$i]); ?></div></div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="qlbt-chart-card">
                <div class="qlbt-chart-title">👨‍🎓 Phân bố học sinh theo lớp</div>
                <div class="qlbt-class-list">
                <?php foreach($class_counts as $cc=>$num): $pct=round(($num/$class_max)*100); ?>
                    <div class="qlbt-class-item"><div class="top"><span>Lớp <?php echo esc_html($cc); ?></span><span><?php echo number_format_i18n($num); ?></span></div><div class="track"><span style="width:<?php echo esc_attr($pct); ?>%"></span></div></div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="qlbt-footer">© <?php echo esc_html(wp_date('Y', current_time('timestamp'))); ?> <b>Trường Tiểu học Nguyễn An Ninh</b> · Hệ thống Quản lý bán trú · Phiên bản 5.4.1</div>
    </div>
    <?php
}

/* =====================================================
   NHẬP HỌC SINH TỪ EXCEL / CSV
===================================================== */

function qlbt_import_hoc_sinh() {
    global $wpdb;
    $table = $wpdb->prefix . 'bt_hoc_sinh';

    echo '<div class="wrap">';
    echo '<h1>📥 Nhập học sinh từ Excel</h1>';
    echo '<p>Nhập hàng loạt học sinh từ file <strong>.xlsx</strong> hoặc <strong>.csv</strong>. Mã học sinh đã tồn tại sẽ được bỏ qua để tránh trùng dữ liệu.</p>';

    if (isset($_POST['qlbt_import_excel_submit'])) {
        if (!isset($_POST['qlbt_import_excel_nonce']) || !wp_verify_nonce($_POST['qlbt_import_excel_nonce'], 'qlbt_import_excel')) {
            echo '<div class="notice notice-error"><p>Phiên làm việc không hợp lệ. Vui lòng thử lại.</p></div>';
        } elseif (!current_user_can('qlbt_hoc_sinh')) {
            echo '<div class="notice notice-error"><p>Bạn không có quyền nhập học sinh.</p></div>';
        } elseif (empty($_FILES['qlbt_excel']['tmp_name'])) {
            echo '<div class="notice notice-error"><p>Vui lòng chọn file Excel hoặc CSV.</p></div>';
        } else {
            $file = $_FILES['qlbt_excel'];
            $name = sanitize_file_name($file['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed = array('xlsx', 'csv');
            $rows = array();
            $error = '';

            if (!in_array($ext, $allowed, true)) {
                $error = 'Chỉ hỗ trợ file .xlsx hoặc .csv.';
            } elseif ((int)$file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Không thể tải file lên. Mã lỗi: ' . (int)$file['error'];
            } elseif ($ext === 'csv') {
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle) {
                    $header = fgetcsv($handle, 0, ',');
                    if ($header) {
                        $header = array_map(function($v){ return trim((string)$v); }, $header);
                        while (($row = fgetcsv($handle, 0, ',')) !== false) {
                            $rows[] = array_combine(array_pad($header, count($row), ''), array_pad($row, count($header), '')) ?: $row;
                        }
                    }
                    fclose($handle);
                } else {
                    $error = 'Không đọc được file CSV.';
                }
            } else {
                if (!class_exists('ZipArchive')) {
                    $error = 'Máy chủ PHP chưa bật ZipArchive nên chưa đọc được file XLSX. Hãy bật extension zip trong XAMPP/PHP.';
                } else {
                    $zip = new ZipArchive();
                    if ($zip->open($file['tmp_name']) !== true) {
                        $error = 'Không mở được file Excel XLSX.';
                    } else {
                        // Đọc XLSX bằng DOMXPath + namespace chuẩn để tương thích tốt với Excel desktop.
                        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
                        $shared = array();

                        $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
                        if ($shared_xml !== false) {
                            $dom_shared = new DOMDocument();
                            if (@$dom_shared->loadXML($shared_xml)) {
                                $xp_shared = new DOMXPath($dom_shared);
                                $xp_shared->registerNamespace('x', $ns);
                                foreach ($xp_shared->query('//x:sst/x:si') as $si) {
                                    $parts = $xp_shared->query('.//x:t', $si);
                                    $text = '';
                                    foreach ($parts as $t) $text .= $t->textContent;
                                    $shared[] = $text;
                                }
                            }
                        }

                        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
                        if ($sheet_xml === false) {
                            $error = 'Không tìm thấy Sheet1 trong file Excel.';
                        } else {
                            $dom_sheet = new DOMDocument();
                            if (!@$dom_sheet->loadXML($sheet_xml)) {
                                $error = 'File Excel bị lỗi hoặc không đúng định dạng XLSX.';
                            } else {
                                $xp = new DOMXPath($dom_sheet);
                                $xp->registerNamespace('x', $ns);
                                $header = array();
                                $row_index = 0;

                                foreach ($xp->query('//x:sheetData/x:row') as $xmlrow) {
                                    $row_index++;
                                    $cells = array();
                                    foreach ($xp->query('./x:c', $xmlrow) as $cell) {
                                        $ref = $cell->getAttribute('r');
                                        $col = 0;
                                        if (preg_match('/([A-Z]+)[0-9]+/', $ref, $m)) {
                                            foreach (str_split($m[1]) as $ch) $col = $col * 26 + (ord($ch) - 64);
                                            $col--;
                                        }

                                        $type = $cell->getAttribute('t');
                                        $val = '';
                                        $v_nodes = $xp->query('./x:v', $cell);
                                        $v = ($v_nodes->length > 0) ? $v_nodes->item(0)->textContent : '';

                                        if ($type === 's') {
                                            $idx_shared = (int)$v;
                                            $val = ($idx_shared >= 0 && isset($shared[$idx_shared])) ? $shared[$idx_shared] : '';
                                        } elseif ($type === 'inlineStr') {
                                            foreach ($xp->query('./x:is//x:t', $cell) as $t) $val .= $t->textContent;
                                        } elseif ($type === 'str') {
                                            $val = $v;
                                        } else {
                                            $val = $v;
                                        }
                                        $cells[$col] = $val;
                                    }

                                    if ($row_index === 1) {
                                        $max = $cells ? max(array_keys($cells)) : -1;
                                        for ($i=0; $i <= $max; $i++) {
                                            $header[$i] = isset($cells[$i]) ? trim((string)$cells[$i]) : '';
                                        }
                                    } elseif ($header) {
                                        $max = count($header) - 1;
                                        $row = array();
                                        for ($i=0; $i <= $max; $i++) $row[] = isset($cells[$i]) ? $cells[$i] : '';
                                        $rows[] = array_combine($header, $row) ?: $row;
                                    }
                                }
                            }
                        }
                        $zip->close();
                    }
                }
            }

            if (!$error && empty($rows)) $error = 'File không có dữ liệu học sinh.';

            if ($error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            } else {
                $map = array(
                    'Mã học sinh'=>'ma_hoc_sinh', 'Ma hoc sinh'=>'ma_hoc_sinh', 'Mã HS'=>'ma_hoc_sinh', 'ma_hoc_sinh'=>'ma_hoc_sinh',
                    'Họ tên'=>'ho_ten', 'Ho ten'=>'ho_ten', 'Họ và tên'=>'ho_ten', 'ho_ten'=>'ho_ten',
                    'Ngày sinh'=>'ngay_sinh', 'Ngay sinh'=>'ngay_sinh', 'ngay_sinh'=>'ngay_sinh',
                    'Giới tính'=>'gioi_tinh', 'Gioi tinh'=>'gioi_tinh', 'gioi_tinh'=>'gioi_tinh',
                    'Lớp'=>'lop', 'Lop'=>'lop', 'lop'=>'lop',
                    'Năm học'=>'nam_hoc', 'Nam hoc'=>'nam_hoc', 'nam_hoc'=>'nam_hoc',
                    'Phụ huynh'=>'ho_ten_phu_huynh', 'Phụ huynh/Họ tên'=>'ho_ten_phu_huynh', 'Họ tên phụ huynh'=>'ho_ten_phu_huynh', 'ho_ten_phu_huynh'=>'ho_ten_phu_huynh',
                    'Số điện thoại'=>'so_dien_thoai', 'So dien thoai'=>'so_dien_thoai', 'so_dien_thoai'=>'so_dien_thoai',
                    'Địa chỉ'=>'dia_chi', 'Dia chi'=>'dia_chi', 'dia_chi'=>'dia_chi',
                    'Trạng thái'=>'trang_thai', 'Trang thai'=>'trang_thai', 'trang_thai'=>'trang_thai'
                );
                $normalize_header = function($h) use ($map) {
                    $h = trim((string)$h);
                    return isset($map[$h]) ? $map[$h] : $h;
                };
                $inserted = 0; $skipped = 0; $invalid = 0; $messages = array();
                foreach ($rows as $idx => $row) {
                    $clean = array();
                    foreach ($row as $k=>$v) { $clean[$normalize_header($k)] = is_string($v) ? trim($v) : $v; }
                    $ma = isset($clean['ma_hoc_sinh']) ? sanitize_text_field($clean['ma_hoc_sinh']) : '';
                    $ten = isset($clean['ho_ten']) ? sanitize_text_field($clean['ho_ten']) : '';
                    if ($ma === '' || $ten === '') { $invalid++; $messages[] = 'Dòng ' . ($idx+2) . ': thiếu Mã học sinh hoặc Họ tên.'; continue; }
                    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE ma_hoc_sinh=%s", $ma));
                    if ($exists) { $skipped++; continue; }
                    $date = isset($clean['ngay_sinh']) ? trim((string)$clean['ngay_sinh']) : '';
                    // Excel có thể lưu ngày sinh dưới dạng số serial (ví dụ 44182).
                    if ($date !== '' && is_numeric($date) && (float)$date > 1000) {
                        $ts = ((float)$date - 25569) * 86400;
                        $date = gmdate('Y-m-d', (int)$ts);
                    } elseif (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $date, $m)) {
                        $date = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                    }
                    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = null;

                    $phone = isset($clean['so_dien_thoai']) ? trim((string)$clean['so_dien_thoai']) : '';
                    if (preg_match('/^\d{9}$/', $phone)) $phone = '0' . $phone;
                    $data = array(
                        'ma_hoc_sinh'=>$ma,
                        'ho_ten'=>$ten,
                        'ngay_sinh'=>$date,
                        'gioi_tinh'=>isset($clean['gioi_tinh']) ? sanitize_text_field($clean['gioi_tinh']) : '',
                        'lop'=>isset($clean['lop']) ? sanitize_text_field($clean['lop']) : '',
                        'nam_hoc'=>isset($clean['nam_hoc']) ? sanitize_text_field($clean['nam_hoc']) : '',
                        'ho_ten_phu_huynh'=>isset($clean['ho_ten_phu_huynh']) ? sanitize_text_field($clean['ho_ten_phu_huynh']) : '',
                        'so_dien_thoai'=>sanitize_text_field($phone),
                        'dia_chi'=>isset($clean['dia_chi']) ? sanitize_textarea_field($clean['dia_chi']) : '',
                        'trang_thai'=>isset($clean['trang_thai']) && $clean['trang_thai'] !== '' ? sanitize_text_field($clean['trang_thai']) : 'Đang bán trú'
                    );
                    $ok = $wpdb->insert($table, $data);
                    if ($ok) $inserted++; else { $invalid++; $messages[] = 'Dòng ' . ($idx+2) . ': không thể thêm dữ liệu.'; }
                }
                echo '<div class="notice notice-success"><p><strong>Đã nhập ' . number_format_i18n($inserted) . ' học sinh.</strong> Bỏ qua trùng mã: ' . number_format_i18n($skipped) . ' · Dòng lỗi: ' . number_format_i18n($invalid) . '.</p></div>';
                if ($messages) echo '<div class="notice notice-warning"><p>' . esc_html(implode(' | ', array_slice($messages,0,20))) . '</p></div>';
            }
        }
    }

    echo '<div style="max-width:900px;background:#fff;padding:24px;border:1px solid #dcdcde;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.04)">';
    echo '<h2>📄 Cách nhập nhanh</h2><ol><li>Tải file Excel mẫu.</li><li>Điền dữ liệu học sinh, mỗi học sinh một dòng.</li><li>Lưu file dạng <strong>.xlsx</strong> rồi tải lên bên dưới.</li><li>Hệ thống tự bỏ qua mã học sinh đã tồn tại.</li></ol>';
    echo '<p><strong>Cột bắt buộc:</strong> Mã học sinh, Họ tên. Các cột còn lại có thể để trống.</p>';
    echo '<p><a class="button button-secondary" href="' . esc_url(admin_url('admin.php?page=qlbt-hoc-sinh')) . '">← Quay lại Học sinh</a> <a class="button" href="' . esc_url(admin_url('admin-post.php?action=qlbt_tai_excel_mau')) . '">⬇ Tải Excel mẫu</a></p>';
    echo '<hr><form method="post" enctype="multipart/form-data">';
    wp_nonce_field('qlbt_import_excel','qlbt_import_excel_nonce');
    echo '<input type="file" name="qlbt_excel" accept=".xlsx,.csv" required> <button type="submit" name="qlbt_import_excel_submit" class="button button-primary">📥 Nhập dữ liệu</button>';
    echo '</form></div></div>';
}

function qlbt_tai_excel_mau() {
    if (!current_user_can('qlbt_hoc_sinh')) wp_die('Bạn không có quyền.');
    $filename = 'mau_nhap_hoc_sinh_ban_tru.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output','w');
    fputcsv($out, array('Mã học sinh','Họ tên','Ngày sinh','Giới tính','Lớp','Năm học','Phụ huynh','Số điện thoại','Địa chỉ','Trạng thái'));
    fputcsv($out, array('HS0001','Nguyễn Văn A','15/09/2018','Nam','1A','2026-2027','Nguyễn Văn B','0900000000','Hóc Môn, TP.HCM','Đang bán trú'));
    fputcsv($out, array('HS0002','Trần Thị B','20/03/2018','Nữ','1A','2026-2027','Trần Văn C','0911111111','Hóc Môn, TP.HCM','Đang bán trú'));
    fclose($out); exit;
}
add_action('admin_post_qlbt_tai_excel_mau','qlbt_tai_excel_mau');

/* =====================================================
   QUẢN LÝ HỌC SINH
===================================================== */

function qlbt_hoc_sinh() {

    global $wpdb;

    $table = $wpdb->prefix . 'bt_hoc_sinh';


    /* ================================================
       XÓA HỌC SINH
    ================================================= */

    if (
        isset($_GET['xoa']) &&
        isset($_GET['_wpnonce'])
    ) {

        $id = intval($_GET['xoa']);

        if (
            wp_verify_nonce(
                $_GET['_wpnonce'],
                'qlbt_xoa_hoc_sinh_' . $id
            )
        ) {

            $wpdb->delete(
                $table,
                array(
                    'id' => $id
                ),
                array('%d')
            );

            echo '<div class="notice notice-success is-dismissible">
                    <p>Đã xóa học sinh thành công.</p>
                  </div>';
        }
    }


    /* ================================================
       LƯU HỌC SINH
    ================================================= */

    if (
        isset($_POST['qlbt_luu_hoc_sinh']) &&
        check_admin_referer(
            'qlbt_luu_hoc_sinh',
            'qlbt_nonce'
        )
    ) {

        $id = isset($_POST['id'])
            ? intval($_POST['id'])
            : 0;


        $data = array(

            'ma_hoc_sinh' => sanitize_text_field(
                $_POST['ma_hoc_sinh']
            ),

            'ho_ten' => sanitize_text_field(
                $_POST['ho_ten']
            ),

            'ngay_sinh' => sanitize_text_field(
                $_POST['ngay_sinh']
            ),

            'gioi_tinh' => sanitize_text_field(
                $_POST['gioi_tinh']
            ),

            'lop' => sanitize_text_field(
                $_POST['lop']
            ),

            'nam_hoc' => sanitize_text_field(
                $_POST['nam_hoc']
            ),

            'ho_ten_phu_huynh' => sanitize_text_field(
                $_POST['ho_ten_phu_huynh']
            ),

            'so_dien_thoai' => sanitize_text_field(
                $_POST['so_dien_thoai']
            ),

            'dia_chi' => sanitize_textarea_field(
                $_POST['dia_chi']
            ),

            'trang_thai' => sanitize_text_field(
                $_POST['trang_thai']
            )
        );


        /* SỬA */

        if ($id > 0) {

            $wpdb->update(
                $table,
                $data,
                array(
                    'id' => $id
                )
            );

            echo '<div class="notice notice-success is-dismissible">
                    <p>Đã cập nhật thông tin học sinh.</p>
                  </div>';

        }

        /* THÊM */

        else {

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table
                     WHERE ma_hoc_sinh = %s",
                    $data['ma_hoc_sinh']
                )
            );


            if ($exists) {

                echo '<div class="notice notice-error">
                        <p>
                        Mã học sinh đã tồn tại.
                        Vui lòng nhập mã khác.
                        </p>
                      </div>';

            } else {

                $wpdb->insert(
                    $table,
                    $data
                );

                echo '<div class="notice notice-success is-dismissible">
                        <p>
                        Đã thêm học sinh thành công.
                        </p>
                      </div>';
            }
        }
    }


    /* ================================================
       LẤY HỌC SINH ĐỂ SỬA
    ================================================= */

    $edit_student = null;

    if (isset($_GET['sua'])) {

        $edit_id = intval($_GET['sua']);

        $edit_student = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $edit_id
            )
        );
    }


    /* ================================================
       TÌM KIẾM
    ================================================= */

    $keyword = isset($_GET['keyword'])
        ? sanitize_text_field($_GET['keyword'])
        : '';


    $lop_filter = isset($_GET['lop_filter'])
        ? sanitize_text_field($_GET['lop_filter'])
        : '';


    $where = "WHERE 1=1";


    if ($keyword != '') {

        $like = '%' . $wpdb->esc_like($keyword) . '%';

        $where .= $wpdb->prepare(
            " AND (
                ho_ten LIKE %s
                OR ma_hoc_sinh LIKE %s
                OR ho_ten_phu_huynh LIKE %s
                OR so_dien_thoai LIKE %s
            )",
            $like,
            $like,
            $like,
            $like
        );
    }


    if ($lop_filter != '') {

        $where .= $wpdb->prepare(
            " AND lop = %s",
            $lop_filter
        );
    }


    /* ================================================
       PHÂN TRANG
    ================================================= */

    $per_page = 20;

    $paged = isset($_GET['paged'])
        ? max(1, intval($_GET['paged']))
        : 1;

    $offset = ($paged - 1) * $per_page;


    $total = $wpdb->get_var(
        "SELECT COUNT(*)
         FROM $table
         $where"
    );


    $students = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT *
             FROM $table
             $where
             ORDER BY id DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );


    $total_pages = ceil(
        $total / $per_page
    );


    /* ================================================
       FORM
    ================================================= */

    ?>

    <div class="wrap">

        <h1>
            QUẢN LÝ HỌC SINH BÁN TRÚ
        </h1>


        <hr>


        <?php if ($edit_student): ?>

            <h2>
                ✏️ Sửa thông tin học sinh
            </h2>

        <?php else: ?>

            <h2>
                ➕ Thêm học sinh
            </h2>

        <?php endif; ?>


        <form method="post">

            <?php
            wp_nonce_field(
                'qlbt_luu_hoc_sinh',
                'qlbt_nonce'
            );
            ?>


            <input
                type="hidden"
                name="id"
                value="<?php
                echo $edit_student
                    ? esc_attr($edit_student->id)
                    : '';
                ?>"
            >


            <table class="form-table">

                <tr>

                    <th>
                        Mã học sinh *
                    </th>

                    <td>

                        <input
                            type="text"
                            name="ma_hoc_sinh"
                            required
                            style="width:300px"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->ma_hoc_sinh
                                )
                                : '';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Họ và tên *
                    </th>

                    <td>

                        <input
                            type="text"
                            name="ho_ten"
                            required
                            style="width:400px"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->ho_ten
                                )
                                : '';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Ngày sinh
                    </th>

                    <td>

                        <input
                            type="date"
                            name="ngay_sinh"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->ngay_sinh
                                )
                                : '';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Giới tính
                    </th>

                    <td>

                        <select name="gioi_tinh">

                            <option value="">
                                -- Chọn --
                            </option>

                            <option
                                value="Nam"
                                <?php
                                if (
                                    $edit_student &&
                                    $edit_student->gioi_tinh == 'Nam'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                Nam
                            </option>

                            <option
                                value="Nữ"
                                <?php
                                if (
                                    $edit_student &&
                                    $edit_student->gioi_tinh == 'Nữ'
                                ) {
                                    echo 'selected';
                                }
                                ?>
                            >
                                Nữ
                            </option>

                        </select>

                    </td>

                </tr>


                <tr>

                    <th>
                        Lớp
                    </th>

                    <td>

                        <input
                            type="text"
                            name="lop"
                            placeholder="Ví dụ: 3A"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->lop
                                )
                                : '';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Năm học
                    </th>

                    <td>

                        <input
                            type="text"
                            name="nam_hoc"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->nam_hoc
                                )
                                : '2026-2027';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Họ tên phụ huynh
                    </th>

                    <td>

                        <input
                            type="text"
                            name="ho_ten_phu_huynh"
                            style="width:400px"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->ho_ten_phu_huynh
                                )
                                : '';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Số điện thoại
                    </th>

                    <td>

                        <input
                            type="text"
                            name="so_dien_thoai"
                            value="<?php
                            echo $edit_student
                                ? esc_attr(
                                    $edit_student->so_dien_thoai
                                )
                                : '';
                            ?>"
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Địa chỉ
                    </th>

                    <td>

                        <textarea
                            name="dia_chi"
                            rows="3"
                            style="width:400px"
                        ><?php
                        echo $edit_student
                            ? esc_textarea(
                                $edit_student->dia_chi
                            )
                            : '';
                        ?></textarea>

                    </td>

                </tr>


                <tr>

                    <th>
                        Trạng thái
                    </th>

                    <td>

                        <select name="trang_thai">

                            <?php

                            $status =
                                $edit_student
                                ? $edit_student->trang_thai
                                : 'Đang bán trú';

                            ?>

                            <option
                                value="Đang bán trú"
                                <?php
                                selected(
                                    $status,
                                    'Đang bán trú'
                                );
                                ?>
                            >
                                Đang bán trú
                            </option>

                            <option
                                value="Tạm nghỉ"
                                <?php
                                selected(
                                    $status,
                                    'Tạm nghỉ'
                                );
                                ?>
                            >
                                Tạm nghỉ
                            </option>

                            <option
                                value="Nghỉ bán trú"
                                <?php
                                selected(
                                    $status,
                                    'Nghỉ bán trú'
                                );
                                ?>
                            >
                                Nghỉ bán trú
                            </option>

                        </select>

                    </td>

                </tr>

            </table>


            <p>

                <button
                    type="submit"
                    name="qlbt_luu_hoc_sinh"
                    class="button button-primary button-large"
                >

                    <?php
                    echo $edit_student
                        ? '💾 Cập nhật học sinh'
                        : '➕ Thêm học sinh';
                    ?>

                </button>


                <?php if ($edit_student): ?>

                    <a
                        href="<?php
                        echo admin_url(
                            'admin.php?page=qlbt-hoc-sinh'
                        );
                        ?>"
                        class="button button-large"
                    >
                        Hủy
                    </a>

                <?php endif; ?>

            </p>

        </form>


        <hr>


        <!-- TÌM KIẾM -->

        <h2>
            🔎 Tìm kiếm học sinh
        </h2>


        <form method="get">

            <input
                type="hidden"
                name="page"
                value="qlbt-hoc-sinh"
            >


            <input
                type="text"
                name="keyword"
                placeholder="Nhập tên, mã HS, phụ huynh hoặc SĐT..."
                value="<?php
                echo esc_attr($keyword);
                ?>"
                style="width:350px"
            >


            <select name="lop_filter">

                <option value="">
                    -- Tất cả lớp --
                </option>

                <?php

                $classes = array(
                    '1A','1B','1C',
                    '2A','2B','2C',
                    '3A','3B','3C',
                    '4A','4B','4C',
                    '5A','5B','5C'
                );

                foreach ($classes as $class) {

                    ?>

                    <option
                        value="<?php echo esc_attr($class); ?>"
                        <?php
                        selected(
                            $lop_filter,
                            $class
                        );
                        ?>
                    >

                        <?php echo esc_html($class); ?>

                    </option>

                    <?php
                }

                ?>

            </select>


            <button
                type="submit"
                class="button button-primary"
            >
                🔎 Tìm kiếm
            </button>


            <a
                href="<?php
                echo admin_url(
                    'admin.php?page=qlbt-hoc-sinh'
                );
                ?>"
                class="button"
            >
                Xóa bộ lọc
            </a>

        </form>


        <br>


        <h2>

            Danh sách học sinh

            <span style="
                font-size:14px;
                font-weight:normal;
            ">
                Tổng:
                <?php echo esc_html($total); ?>
                học sinh
            </span>

        </h2>


        <table class="widefat striped">

            <thead>

                <tr>

                    <th>STT</th>

                    <th>Mã HS</th>

                    <th>Họ và tên</th>

                    <th>Ngày sinh</th>

                    <th>GT</th>

                    <th>Lớp</th>

                    <th>Phụ huynh</th>

                    <th>SĐT</th>

                    <th>Trạng thái</th>

                    <th>Thao tác</th>

                </tr>

            </thead>


            <tbody>

            <?php

            if ($students) {

                $stt = $offset + 1;

                foreach ($students as $hs) {

                    ?>

                    <tr>

                        <td>
                            <?php echo $stt++; ?>
                        </td>


                        <td>
                            <?php
                            echo esc_html(
                                $hs->ma_hoc_sinh
                            );
                            ?>
                        </td>


                        <td>

                            <strong>
                                <?php
                                echo esc_html(
                                    $hs->ho_ten
                                );
                                ?>
                            </strong>

                        </td>


                        <td>
                            <?php
                            echo esc_html(
                                $hs->ngay_sinh
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo esc_html(
                                $hs->gioi_tinh
                            );
                            ?>
                        </td>


                        <td>

                            <strong>
                                <?php
                                echo esc_html(
                                    $hs->lop
                                );
                                ?>
                            </strong>

                        </td>


                        <td>
                            <?php
                            echo esc_html(
                                $hs->ho_ten_phu_huynh
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo esc_html(
                                $hs->so_dien_thoai
                            );
                            ?>
                        </td>


                        <td>

                            <?php

                            if (
                                $hs->trang_thai ==
                                'Đang bán trú'
                            ) {

                                echo '<span style="
                                color:green;
                                font-weight:bold;
                                ">
                                Đang bán trú
                                </span>';

                            } else {

                                echo esc_html(
                                    $hs->trang_thai
                                );
                            }

                            ?>

                        </td>


                        <td>

                            <?php

                            $edit_url = admin_url(
                                'admin.php?page=qlbt-hoc-sinh&sua=' .
                                $hs->id
                            );


                            $delete_url = wp_nonce_url(
                                admin_url(
                                    'admin.php?page=qlbt-hoc-sinh&xoa=' .
                                    $hs->id
                                ),
                                'qlbt_xoa_hoc_sinh_' .
                                $hs->id
                            );

                            ?>


                            <a
                                href="<?php
                                echo esc_url($edit_url);
                                ?>"
                                class="button button-small"
                            >
                                Sửa
                            </a>


                            <a
                                href="<?php
                                echo esc_url($delete_url);
                                ?>"
                                class="button button-small"
                                onclick="return confirm(
                                'Bạn có chắc chắn muốn xóa học sinh này?'
                                );"
                            >
                                Xóa
                            </a>

                        </td>

                    </tr>

                    <?php
                }

            } else {

                ?>

                <tr>

                    <td colspan="10">

                        <strong>
                            Không tìm thấy học sinh.
                        </strong>

                    </td>

                </tr>

                <?php
            }

            ?>

            </tbody>

        </table>


        <?php

        /* PHÂN TRANG */

        if ($total_pages > 1) {

            echo '<div style="margin-top:20px;">';

            echo paginate_links(
                array(
                    'base' => add_query_arg(
                        'paged',
                        '%#%'
                    ),
                    'format' => '',
                    'current' => $paged,
                    'total' => $total_pages,
                    'prev_text' => '« Trước',
                    'next_text' => 'Sau »'
                )
            );

            echo '</div>';
        }

        ?>

    </div>

    <?php
}


/* =====================================================
   ĐIỂM DANH - TẠM THỜI
===================================================== */

function qlbt_diem_danh() {

    global $wpdb;

    $table_hoc_sinh = $wpdb->prefix . 'bt_hoc_sinh';

    $table_diem_danh = $wpdb->prefix . 'bt_diem_danh';


    /* ==========================================
       NGÀY
    ========================================== */

    $ngay = isset($_GET['ngay'])
        ? sanitize_text_field($_GET['ngay'])
        : date('Y-m-d');


    /* ==========================================
       LỚP
    ========================================== */

    $lop = isset($_GET['lop'])
        ? sanitize_text_field($_GET['lop'])
        : '';


    /* ==========================================
       LƯU ĐIỂM DANH
    ========================================== */

    if (
        isset($_POST['qlbt_luu_diem_danh'])
    ) {

        check_admin_referer(
            'qlbt_luu_diem_danh',
            'qlbt_diem_danh_nonce'
        );


        $ngay_luu = sanitize_text_field(
            $_POST['ngay']
        );


        if (
            isset($_POST['diem_danh']) &&
            is_array($_POST['diem_danh'])
        ) {

            foreach (
                $_POST['diem_danh']
                as $hoc_sinh_id => $trang_thai
            ) {

                $hoc_sinh_id = intval(
                    $hoc_sinh_id
                );


                $trang_thai = sanitize_text_field(
                    $trang_thai
                );


                /*
                 * Chỉ cho phép 3 trạng thái
                 */

                $trang_thai_hop_le = array(
                    'co_mat',
                    'vang',
                    'co_phep'
                );


                if (
                    !in_array(
                        $trang_thai,
                        $trang_thai_hop_le,
                        true
                    )
                ) {

                    $trang_thai = 'co_mat';
                }


                /*
                 * INSERT hoặc UPDATE
                 */

                $wpdb->replace(
                    $table_diem_danh,
                    array(
                        'hoc_sinh_id' => $hoc_sinh_id,
                        'ngay' => $ngay_luu,
                        'trang_thai' => $trang_thai
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s'
                    )
                );
            }
        }


        echo '<div class="notice notice-success is-dismissible">
            <p>
                <strong>Đã lưu điểm danh thành công!</strong>
            </p>
        </div>';
    }


    /* ==========================================
       DANH SÁCH LỚP
    ========================================== */

    $ds_lop = array(
        '1A',
        '1B',
        '1C',
        '2A',
        '2B',
        '2C',
        '3A',
        '3B',
        '3C',
        '4A',
        '4B',
        '4C',
        '5A',
        '5B',
        '5C'
    );


    /* ==========================================
       LẤY HỌC SINH
    ========================================== */

    $students = array();


    if ($lop != '') {

        $students = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM $table_hoc_sinh
                 WHERE lop = %s
                 AND trang_thai = 'Đang bán trú'
                 ORDER BY ho_ten ASC",
                $lop
            )
        );
    }


    /* ==========================================
       LẤY ĐIỂM DANH ĐÃ LƯU
    ========================================== */

    $attendance = array();


    if ($lop != '' && !empty($students)) {

        foreach ($students as $student) {

            $status = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT trang_thai
                     FROM $table_diem_danh
                     WHERE hoc_sinh_id = %d
                     AND ngay = %s",
                    $student->id,
                    $ngay
                )
            );


            if ($status) {

                $attendance[
                    $student->id
                ] = $status;

            } else {

                /*
                 * Nếu chưa điểm danh
                 * mặc định để trống
                 */

                $attendance[
                    $student->id
                ] = '';
            }
        }
    }


    /* ==========================================
       THỐNG KÊ
    ========================================== */

    $co_mat = 0;

    $vang = 0;

    $co_phep = 0;


    foreach ($attendance as $status) {

        if ($status == 'co_mat') {

            $co_mat++;

        } elseif ($status == 'vang') {

            $vang++;

        } elseif ($status == 'co_phep') {

            $co_phep++;
        }
    }


    ?>

    <div class="wrap">

        <h1>
            🍱 ĐIỂM DANH BÁN TRÚ
        </h1>


        <hr>


        <!--
        ========================================
        CHỌN NGÀY + LỚP
        ========================================
        -->

        <form method="get">

            <input
                type="hidden"
                name="page"
                value="qlbt-diem-danh"
            >


            <table class="form-table">

                <tr>

                    <th>
                        Ngày điểm danh
                    </th>

                    <td>

                        <input
                            type="date"
                            name="ngay"
                            value="<?php
                            echo esc_attr($ngay);
                            ?>"
                            required
                        >

                    </td>

                </tr>


                <tr>

                    <th>
                        Lớp
                    </th>

                    <td>

                        <select
                            name="lop"
                            style="min-width:180px"
                        >

                            <option value="">
                                -- Chọn lớp --
                            </option>


                            <?php

                            foreach (
                                $ds_lop
                                as $ten_lop
                            ) {

                                ?>

                                <option
                                    value="<?php
                                    echo esc_attr(
                                        $ten_lop
                                    );
                                    ?>"
                                    <?php
                                    selected(
                                        $lop,
                                        $ten_lop
                                    );
                                    ?>
                                >

                                    <?php
                                    echo esc_html(
                                        $ten_lop
                                    );
                                    ?>

                                </option>

                                <?php
                            }

                            ?>

                        </select>

                    </td>

                </tr>

            </table>


            <button
                type="submit"
                class="button button-primary button-large"
            >
                🔎 Xem danh sách
            </button>

        </form>


        <?php if ($lop != ''): ?>

            <hr>


            <!--
            ========================================
            THỐNG KÊ
            ========================================
            -->

            <div style="
                display:grid;
                grid-template-columns:
                repeat(4, 1fr);
                gap:15px;
                margin:20px 0;
            ">


                <div style="
                    background:#fff;
                    border:1px solid #ddd;
                    padding:20px;
                    text-align:center;
                ">

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                    ">

                        <?php
                        echo count($students);
                        ?>

                    </div>

                    <div>
                        Tổng học sinh
                    </div>

                </div>


                <div style="
                    background:#fff;
                    border:1px solid #ddd;
                    padding:20px;
                    text-align:center;
                ">

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:green;
                    ">

                        <?php
                        echo $co_mat;
                        ?>

                    </div>

                    <div>
                        Có mặt
                    </div>

                </div>


                <div style="
                    background:#fff;
                    border:1px solid #ddd;
                    padding:20px;
                    text-align:center;
                ">

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:red;
                    ">

                        <?php
                        echo $vang;
                        ?>

                    </div>

                    <div>
                        Vắng
                    </div>

                </div>


                <div style="
                    background:#fff;
                    border:1px solid #ddd;
                    padding:20px;
                    text-align:center;
                ">

                    <div style="
                        font-size:30px;
                        font-weight:bold;
                        color:#d98200;
                    ">

                        <?php
                        echo $co_phep;
                        ?>

                    </div>

                    <div>
                        Có phép
                    </div>

                </div>

            </div>


            <!--
            ========================================
            FORM ĐIỂM DANH
            ========================================
            -->

            <form method="post">

                <?php

                wp_nonce_field(
                    'qlbt_luu_diem_danh',
                    'qlbt_diem_danh_nonce'
                );

                ?>


                <input
                    type="hidden"
                    name="ngay"
                    value="<?php
                    echo esc_attr($ngay);
                    ?>"
                >


                <div style="
                    margin-bottom:15px;
                ">

                    <button
                        type="button"
                        class="button"
                        onclick="qlbtTatCaCoMat()"
                    >
                        ✅ Có mặt tất cả
                    </button>


                    <button
                        type="button"
                        class="button"
                        onclick="qlbtBoChon()"
                    >
                        ⭕ Bỏ chọn
                    </button>

                </div>


                <table class="widefat striped">

                    <thead>

                        <tr>

                            <th style="width:60px">
                                STT
                            </th>

                            <th>
                                Mã HS
                            </th>

                            <th>
                                Họ và tên
                            </th>

                            <th>
                                Ngày sinh
                            </th>

                            <th style="
                                text-align:center;
                            ">
                                Có mặt
                            </th>

                            <th style="
                                text-align:center;
                            ">
                                Vắng
                            </th>

                            <th style="
                                text-align:center;
                            ">
                                Có phép
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php

                    if (!empty($students)) {

                        $stt = 1;


                        foreach (
                            $students
                            as $student
                        ) {

                            $status =
                                isset(
                                    $attendance[
                                        $student->id
                                    ]
                                )
                                ? $attendance[
                                    $student->id
                                ]
                                : '';

                            ?>

                            <tr>

                                <td>
                                    <?php
                                    echo $stt++;
                                    ?>
                                </td>


                                <td>

                                    <?php
                                    echo esc_html(
                                        $student->
                                        ma_hoc_sinh
                                    );
                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo esc_html(
                                            $student->ho_ten
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo esc_html(
                                        $student->ngay_sinh
                                    );
                                    ?>

                                </td>


                                <td style="
                                    text-align:center;
                                ">

                                    <input
                                        type="radio"
                                        name="diem_danh[
                                        <?php
                                        echo $student->id;
                                        ?>
                                        ]"
                                        value="co_mat"
                                        <?php
                                        checked(
                                            $status,
                                            'co_mat'
                                        );
                                        ?>
                                    >

                                </td>


                                <td style="
                                    text-align:center;
                                ">

                                    <input
                                        type="radio"
                                        name="diem_danh[
                                        <?php
                                        echo $student->id;
                                        ?>
                                        ]"
                                        value="vang"
                                        <?php
                                        checked(
                                            $status,
                                            'vang'
                                        );
                                        ?>
                                    >

                                </td>


                                <td style="
                                    text-align:center;
                                ">

                                    <input
                                        type="radio"
                                        name="diem_danh[
                                        <?php
                                        echo $student->id;
                                        ?>
                                        ]"
                                        value="co_phep"
                                        <?php
                                        checked(
                                            $status,
                                            'co_phep'
                                        );
                                        ?>
                                    >

                                </td>

                            </tr>

                            <?php
                        }

                    } else {

                        ?>

                        <tr>

                            <td
                                colspan="7"
                                style="
                                text-align:center;
                                padding:30px;
                                "
                            >

                                <strong>
                                    Không có học sinh
                                    bán trú trong lớp này.
                                </strong>

                            </td>

                        </tr>

                        <?php
                    }

                    ?>

                    </tbody>

                </table>


                <?php if (!empty($students)): ?>

                    <br>


                    <button
                        type="submit"
                        name="qlbt_luu_diem_danh"
                        class="button button-primary button-large"
                    >

                        💾 Lưu điểm danh

                    </button>


                <?php endif; ?>

            </form>


            <!--
            ========================================
            JAVASCRIPT
            ========================================
            -->

            <script>

            function qlbtTatCaCoMat() {

                const radios =
                    document.querySelectorAll(
                        'input[type="radio"][value="co_mat"]'
                    );

                radios.forEach(
                    function(radio) {

                        radio.checked = true;

                    }
                );
            }


            function qlbtBoChon() {

                const radios =
                    document.querySelectorAll(
                        'input[type="radio"]'
                    );

                radios.forEach(
                    function(radio) {

                        radio.checked = false;

                    }
                );
            }

            </script>


        <?php endif; ?>

    </div>

    <?php
}


/* =====================================================
   THỰC ĐƠN - TẠM THỜI
===================================================== */

function qlbt_thuc_don() {

    global $wpdb;

    $table = $wpdb->prefix . 'bt_thuc_don';

    // Tự đảm bảo bảng thực đơn tồn tại, kể cả khi plugin đã được kích hoạt từ trước.
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ngay date NOT NULL,
        bua_sang text,
        bua_trua text,
        bua_xe text,
        ghi_chu text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY ngay (ngay)
    ) $charset_collate;";
    dbDelta($sql);

    $ngay = isset($_GET['ngay']) ? sanitize_text_field($_GET['ngay']) : current_time('Y-m-d');

    if (isset($_POST['qlbt_luu_thuc_don'])) {
        check_admin_referer('qlbt_luu_thuc_don', 'qlbt_thuc_don_nonce');

        $ngay_luu = sanitize_text_field($_POST['ngay']);
        $bua_sang = sanitize_textarea_field($_POST['bua_sang'] ?? '');
        $bua_trua = sanitize_textarea_field($_POST['bua_trua'] ?? '');
        $bua_xe = sanitize_textarea_field($_POST['bua_xe'] ?? '');
        $ghi_chu = sanitize_textarea_field($_POST['ghi_chu'] ?? '');

        $wpdb->replace(
            $table,
            array(
                'ngay' => $ngay_luu,
                'bua_sang' => $bua_sang,
                'bua_trua' => $bua_trua,
                'bua_xe' => $bua_xe,
                'ghi_chu' => $ghi_chu
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        $ngay = $ngay_luu;

        echo '<div class="notice notice-success is-dismissible"><p><strong>Đã lưu thực đơn ngày ' . esc_html($ngay) . '.</strong></p></div>';
    }

    $menu = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE ngay = %s", $ngay)
    );

    $bua_sang = $menu ? $menu->bua_sang : '';
    $bua_trua = $menu ? $menu->bua_trua : '';
    $bua_xe = $menu ? $menu->bua_xe : '';
    $ghi_chu = $menu ? $menu->ghi_chu : '';
    ?>

    <div class="wrap">
        <h1>🍱 QUẢN LÝ THỰC ĐƠN BÁN TRÚ</h1>
        <p>Nhập thực đơn theo từng ngày. Có thể sửa lại thực đơn bất cứ lúc nào.</p>

        <hr>

        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="page" value="qlbt-thuc-don">
            <label><strong>Chọn ngày: </strong></label>
            <input type="date" name="ngay" value="<?php echo esc_attr($ngay); ?>" required>
            <button type="submit" class="button">🔎 Xem thực đơn</button>
        </form>

        <form method="post">
            <?php wp_nonce_field('qlbt_luu_thuc_don', 'qlbt_thuc_don_nonce'); ?>
            <input type="hidden" name="ngay" value="<?php echo esc_attr($ngay); ?>">

            <table class="form-table" style="max-width:1000px;">
                <tr>
                    <th scope="row">📅 Ngày</th>
                    <td><strong><?php echo esc_html($ngay); ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">🌅 Bữa sáng</th>
                    <td>
                        <textarea name="bua_sang" rows="4" style="width:100%;" placeholder="Ví dụ: Bánh mì trứng + Sữa"> <?php echo esc_textarea($bua_sang); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🍚 Bữa trưa</th>
                    <td>
                        <textarea name="bua_trua" rows="6" style="width:100%;" placeholder="Ví dụ: Cơm + Thịt kho + Canh bí + Rau xào + Trái cây"><?php echo esc_textarea($bua_trua); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🍰 Bữa xế</th>
                    <td>
                        <textarea name="bua_xe" rows="4" style="width:100%;" placeholder="Ví dụ: Bánh ngọt + Sữa"> <?php echo esc_textarea($bua_xe); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">📝 Ghi chú</th>
                    <td>
                        <textarea name="ghi_chu" rows="3" style="width:100%;" placeholder="Ghi chú nếu có..."><?php echo esc_textarea($ghi_chu); ?></textarea>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="qlbt_luu_thuc_don" class="button button-primary button-large">💾 Lưu thực đơn</button>
            </p>
        </form>

        <hr>

        <h2>📋 Xem nhanh thực đơn</h2>
        <div style="background:#fff;border:1px solid #ddd;padding:25px;max-width:1000px;">
            <h3>🍱 Thực đơn ngày <?php echo esc_html($ngay); ?></h3>

            <p><strong>🌅 Bữa sáng:</strong><br>
            <?php echo nl2br(esc_html($bua_sang ?: 'Chưa nhập')); ?></p>

            <p><strong>🍚 Bữa trưa:</strong><br>
            <?php echo nl2br(esc_html($bua_trua ?: 'Chưa nhập')); ?></p>

            <p><strong>🍰 Bữa xế:</strong><br>
            <?php echo nl2br(esc_html($bua_xe ?: 'Chưa nhập')); ?></p>

            <?php if ($ghi_chu !== ''): ?>
                <p><strong>📝 Ghi chú:</strong><br>
                <?php echo nl2br(esc_html($ghi_chu)); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php
}

/* =====================================================
   THU PHÍ - TẠM THỜI
===================================================== */

function qlbt_thu_phi() {

    global $wpdb;

    $table_hoc_sinh = $wpdb->prefix . 'bt_hoc_sinh';
    $table_diem_danh = $wpdb->prefix . 'bt_diem_danh';
    $table_thu_phi = $wpdb->prefix . 'bt_thu_phi';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_thu_phi (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        hoc_sinh_id bigint(20) unsigned NOT NULL,
        thang varchar(7) NOT NULL,
        don_gia decimal(12,2) NOT NULL DEFAULT 0,
        so_ngay int(11) NOT NULL DEFAULT 0,
        phai_thu decimal(12,2) NOT NULL DEFAULT 0,
        da_thu decimal(12,2) NOT NULL DEFAULT 0,
        trang_thai varchar(30) NOT NULL DEFAULT 'Chưa thu',
        ghi_chu text,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY hoc_sinh_thang (hoc_sinh_id, thang),
        KEY thang (thang)
    ) $charset_collate;";
    dbDelta($sql);

    $thang = isset($_GET['thang']) ? sanitize_text_field($_GET['thang']) : current_time('Y-m');
    $lop = isset($_GET['lop']) ? sanitize_text_field($_GET['lop']) : '';
    $don_gia = isset($_GET['don_gia']) ? floatval($_GET['don_gia']) : 35000;

    $ds_lop = array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C');

    if (isset($_POST['qlbt_luu_thu_phi'])) {
        check_admin_referer('qlbt_luu_thu_phi', 'qlbt_thu_phi_nonce');

        $thang = sanitize_text_field($_POST['thang']);
        $lop = sanitize_text_field($_POST['lop']);
        $don_gia = floatval($_POST['don_gia']);

        if (isset($_POST['phi']) && is_array($_POST['phi'])) {
            foreach ($_POST['phi'] as $hoc_sinh_id => $data) {
                $hoc_sinh_id = intval($hoc_sinh_id);
                $so_ngay = isset($data['so_ngay']) ? max(0, intval($data['so_ngay'])) : 0;
                $da_thu = isset($data['da_thu']) ? max(0, floatval($data['da_thu'])) : 0;
                $phai_thu = $so_ngay * $don_gia;

                if ($da_thu <= 0) {
                    $trang_thai = 'Chưa thu';
                } elseif ($da_thu >= $phai_thu && $phai_thu > 0) {
                    $trang_thai = 'Đã thu đủ';
                } else {
                    $trang_thai = 'Thu một phần';
                }

                $wpdb->replace(
                    $table_thu_phi,
                    array(
                        'hoc_sinh_id' => $hoc_sinh_id,
                        'thang' => $thang,
                        'don_gia' => $don_gia,
                        'so_ngay' => $so_ngay,
                        'phai_thu' => $phai_thu,
                        'da_thu' => $da_thu,
                        'trang_thai' => $trang_thai
                    ),
                    array('%d','%s','%f','%d','%f','%f','%s')
                );
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p><strong>Đã lưu thông tin thu phí tháng ' . esc_html($thang) . '.</strong></p></div>';
    }

    $where = "WHERE trang_thai = 'Đang bán trú'";
    $params = array();

    if ($lop !== '') {
        $where .= ' AND lop = %s';
        $params[] = $lop;
    }

    $query = "SELECT * FROM $table_hoc_sinh $where ORDER BY lop ASC, ho_ten ASC";
    $students = !empty($params)
        ? $wpdb->get_results($wpdb->prepare($query, $params))
        : $wpdb->get_results($query);

    $data_phi = array();

    foreach ($students as $student) {
        $so_ngay = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_diem_danh
                 WHERE hoc_sinh_id = %d
                 AND DATE_FORMAT(ngay, '%%Y-%%m') = %s
                 AND trang_thai = 'co_mat'",
                $student->id,
                $thang
            )
        );

        $saved = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_thu_phi WHERE hoc_sinh_id = %d AND thang = %s",
                $student->id,
                $thang
            )
        );

        $data_phi[$student->id] = array(
            'so_ngay' => $saved ? (int) $saved->so_ngay : $so_ngay,
            'da_thu' => $saved ? (float) $saved->da_thu : 0,
            'phai_thu' => $saved ? (float) $saved->phai_thu : ($so_ngay * $don_gia),
            'trang_thai' => $saved ? $saved->trang_thai : 'Chưa thu'
        );
    }

    $tong_phai_thu = 0;
    $tong_da_thu = 0;
    foreach ($data_phi as $item) {
        $tong_phai_thu += $item['phai_thu'];
        $tong_da_thu += $item['da_thu'];
    }
    $tong_con_no = max(0, $tong_phai_thu - $tong_da_thu);
    ?>

    <div class="wrap">
        <h1>💰 QUẢN LÝ THU PHÍ BÁN TRÚ</h1>
        <p>Tiền phải thu được tính tự động theo <strong>số ngày có mặt</strong> trong tháng × <strong>đơn giá</strong>.</p>

        <hr>

        <form method="get">
            <input type="hidden" name="page" value="qlbt-thu-phi">

            <label><strong>Tháng:</strong></label>
            <input type="month" name="thang" value="<?php echo esc_attr($thang); ?>" required>

            <label style="margin-left:15px;"><strong>Lớp:</strong></label>
            <select name="lop">
                <option value="">-- Tất cả lớp --</option>
                <?php foreach ($ds_lop as $ten_lop): ?>
                    <option value="<?php echo esc_attr($ten_lop); ?>" <?php selected($lop, $ten_lop); ?>><?php echo esc_html($ten_lop); ?></option>
                <?php endforeach; ?>
            </select>

            <label style="margin-left:15px;"><strong>Đơn giá/ngày:</strong></label>
            <input type="number" name="don_gia" value="<?php echo esc_attr($don_gia); ?>" min="0" step="1000" style="width:130px;">

            <button type="submit" class="button button-primary">🔎 Xem</button>
        </form>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin:20px 0;max-width:1000px;">
            <div style="background:#fff;border:1px solid #ddd;padding:20px;text-align:center;">
                <div style="font-size:25px;font-weight:bold;"><?php echo number_format_i18n($tong_phai_thu); ?> đ</div>
                <div>Tổng phải thu</div>
            </div>
            <div style="background:#fff;border:1px solid #ddd;padding:20px;text-align:center;">
                <div style="font-size:25px;font-weight:bold;"><?php echo number_format_i18n($tong_da_thu); ?> đ</div>
                <div>Đã thu</div>
            </div>
            <div style="background:#fff;border:1px solid #ddd;padding:20px;text-align:center;">
                <div style="font-size:25px;font-weight:bold;"><?php echo number_format_i18n($tong_con_no); ?> đ</div>
                <div>Còn phải thu</div>
            </div>
        </div>

        <form method="post">
            <?php wp_nonce_field('qlbt_luu_thu_phi', 'qlbt_thu_phi_nonce'); ?>
            <input type="hidden" name="thang" value="<?php echo esc_attr($thang); ?>">
            <input type="hidden" name="lop" value="<?php echo esc_attr($lop); ?>">
            <input type="hidden" name="don_gia" value="<?php echo esc_attr($don_gia); ?>">

            <table class="widefat striped" style="max-width:1200px;">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã HS</th>
                        <th>Họ và tên</th>
                        <th>Lớp</th>
                        <th>Số ngày có mặt</th>
                        <th>Phải thu</th>
                        <th>Đã thu</th>
                        <th>Còn lại</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($students)): ?>
                    <?php $stt = 1; foreach ($students as $student):
                        $item = $data_phi[$student->id];
                        $con_lai = max(0, $item['phai_thu'] - $item['da_thu']);
                    ?>
                        <tr>
                            <td><?php echo $stt++; ?></td>
                            <td><?php echo esc_html($student->ma_hoc_sinh); ?></td>
                            <td><strong><?php echo esc_html($student->ho_ten); ?></strong></td>
                            <td><?php echo esc_html($student->lop); ?></td>
                            <td>
                                <input type="number" min="0" name="phi[<?php echo $student->id; ?>][so_ngay]" value="<?php echo esc_attr($item['so_ngay']); ?>" style="width:80px;">
                            </td>
                            <td><?php echo number_format_i18n($item['phai_thu']); ?> đ</td>
                            <td>
                                <input type="number" min="0" step="1000" name="phi[<?php echo $student->id; ?>][da_thu]" value="<?php echo esc_attr($item['da_thu']); ?>" style="width:120px;">
                            </td>
                            <td><?php echo number_format_i18n($con_lai); ?> đ</td>
                            <td>
                                <?php if ($item['trang_thai'] === 'Đã thu đủ'): ?>
                                    <span style="color:green;font-weight:bold;">Đã thu đủ</span>
                                <?php elseif ($item['trang_thai'] === 'Thu một phần'): ?>
                                    <span style="color:#d98200;font-weight:bold;">Thu một phần</span>
                                <?php else: ?>
                                    <span style="color:red;font-weight:bold;">Chưa thu</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;padding:30px;">Không có học sinh bán trú.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($students)): ?>
                <p style="margin-top:15px;">
                    <button type="submit" name="qlbt_luu_thu_phi" class="button button-primary button-large">💾 Lưu thu phí</button>
                </p>
            <?php endif; ?>
        </form>
    </div>

    <?php
}


/* =====================================================
   QUẢN LÝ SUẤT ĂN
===================================================== */

function qlbt_suat_an() {

    global $wpdb;

    $table_hs = $wpdb->prefix . 'bt_hoc_sinh';
    $table_dd = $wpdb->prefix . 'bt_diem_danh';

    $ngay = isset($_GET['ngay']) ? sanitize_text_field($_GET['ngay']) : current_time('Y-m-d');
    $lop = isset($_GET['lop']) ? sanitize_text_field($_GET['lop']) : '';
    $thang = isset($_GET['thang']) ? sanitize_text_field($_GET['thang']) : current_time('Y-m');

    $ds_lop = array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C');

    $where = "WHERE hs.trang_thai='Đang bán trú'";
    $params = array($ngay);
    if ($lop !== '') { $where .= ' AND hs.lop=%s'; $params[] = $lop; }

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT hs.ma_hoc_sinh,hs.ho_ten,hs.lop,
                COALESCE(dd.trang_thai,'chua_diem_danh') trang_thai
         FROM $table_hs hs
         LEFT JOIN $table_dd dd ON dd.hoc_sinh_id=hs.id AND dd.ngay=%s
         $where ORDER BY hs.lop,hs.ho_ten", $params
    ));

    $co_mat=0; $vang=0; $co_phep=0; $chua=0;
    foreach ($rows as $r) {
        if ($r->trang_thai==='co_mat') $co_mat++;
        elseif ($r->trang_thai==='vang') $vang++;
        elseif ($r->trang_thai==='co_phep') $co_phep++;
        else $chua++;
    }

    $month_start=$thang.'-01';
    $month_end=date('Y-m-t',strtotime($month_start));
    $where_month="WHERE hs.trang_thai='Đang bán trú'";
    $params_month=array();
    if ($lop!=='') { $where_month.=' AND hs.lop=%s'; $params_month[]=$lop; }
    $where_month.=' AND dd.ngay BETWEEN %s AND %s AND dd.trang_thai=\'co_mat\'';
    $params_month[]=$month_start;
    $params_month[]=$month_end;
    $monthly=(int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_dd dd INNER JOIN $table_hs hs ON hs.id=dd.hoc_sinh_id $where_month",
        $params_month
    ));

    ?>
    <div class="wrap">
        <h1>🍽️ QUẢN LÝ SUẤT ĂN</h1>
        <p>Mỗi học sinh có trạng thái <strong>Có mặt</strong> được tính là <strong>1 suất ăn</strong> trong ngày.</p>
        <hr>

        <form method="get" style="background:#fff;border:1px solid #ddd;padding:15px;margin-bottom:20px;">
            <input type="hidden" name="page" value="qlbt-suat-an">
            <label><strong>Ngày:</strong></label>
            <input type="date" name="ngay" value="<?php echo esc_attr($ngay); ?>" required>
            <label style="margin-left:15px;"><strong>Lớp:</strong></label>
            <select name="lop">
                <option value="">-- Tất cả lớp --</option>
                <?php foreach ($ds_lop as $c): ?><option value="<?php echo esc_attr($c); ?>" <?php selected($lop,$c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?>
            </select>
            <button class="button button-primary">🔎 Xem</button>
        </form>

        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:15px;margin:20px 0;">
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n(count($rows)); ?></div>Tổng HS</div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:green;"><?php echo number_format_i18n($co_mat); ?></div>🍽️ Suất ăn</div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:red;"><?php echo number_format_i18n($vang); ?></div>Vắng</div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;color:#d98200;"><?php echo number_format_i18n($co_phep); ?></div>Có phép</div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n($chua); ?></div>Chưa điểm danh</div>
        </div>

        <div style="background:#fff;border:1px solid #ddd;padding:18px;margin-bottom:20px;">
            <strong>📅 Tổng suất ăn trong tháng <?php echo esc_html($thang); ?>:</strong>
            <span style="font-size:25px;font-weight:bold;margin-left:10px;"><?php echo number_format_i18n($monthly); ?></span>
            <form method="get" style="display:inline;margin-left:30px;">
                <input type="hidden" name="page" value="qlbt-suat-an">
                <input type="hidden" name="ngay" value="<?php echo esc_attr($ngay); ?>">
                <input type="hidden" name="lop" value="<?php echo esc_attr($lop); ?>">
                <label>Tháng: </label><input type="month" name="thang" value="<?php echo esc_attr($thang); ?>">
                <button class="button">Xem tháng</button>
            </form>
        </div>

        <h2>📋 Chi tiết ngày <?php echo esc_html($ngay); ?></h2>
        <table class="widefat striped"><thead><tr><th>STT</th><th>Mã HS</th><th>Họ và tên</th><th>Lớp</th><th>Trạng thái</th><th>Suất ăn</th></tr></thead><tbody>
        <?php if ($rows): $i=1; foreach ($rows as $r): ?>
            <tr>
                <td><?php echo $i++; ?></td><td><?php echo esc_html($r->ma_hoc_sinh); ?></td><td><strong><?php echo esc_html($r->ho_ten); ?></strong></td><td><?php echo esc_html($r->lop); ?></td>
                <td><?php if($r->trang_thai==='co_mat') echo '<span style="color:green;font-weight:bold;">Có mặt</span>'; elseif($r->trang_thai==='vang') echo '<span style="color:red;font-weight:bold;">Vắng</span>'; elseif($r->trang_thai==='co_phep') echo '<span style="color:#d98200;font-weight:bold;">Có phép</span>'; else echo '<span style="color:#777;">Chưa điểm danh</span>'; ?></td>
                <td><?php echo $r->trang_thai==='co_mat' ? '1 suất' : '0 suất'; ?></td>
            </tr>
        <?php endforeach; else: ?><tr><td colspan="6" style="text-align:center;padding:30px;">Chưa có học sinh.</td></tr><?php endif; ?>
        </tbody></table>

        <p style="margin-top:15px;color:#666;">Muốn cập nhật số suất ăn, hãy vào <strong>Điểm danh</strong> và lưu trạng thái Có mặt/Vắng/Có phép.</p>
    </div>
    <?php
}


/* =====================================================
   BÁO CÁO & THỐNG KÊ
===================================================== */

function qlbt_bao_cao() {

    global $wpdb;

    $table_hoc_sinh = $wpdb->prefix . 'bt_hoc_sinh';
    $table_diem_danh = $wpdb->prefix . 'bt_diem_danh';
    $table_thu_phi = $wpdb->prefix . 'bt_thu_phi';

    $thang = isset($_GET['thang']) ? sanitize_text_field($_GET['thang']) : current_time('Y-m');
    $lop = isset($_GET['lop']) ? sanitize_text_field($_GET['lop']) : '';

    if (!preg_match('/^\d{4}-\d{2}$/', $thang)) {
        $thang = current_time('Y-m');
    }

    $ds_lop = array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C');

    $where = "WHERE hs.trang_thai = 'Đang bán trú'";
    $params = array();

    if ($lop !== '') {
        $where .= ' AND hs.lop = %s';
        $params[] = $lop;
    }

    $sql = "SELECT hs.id, hs.ma_hoc_sinh, hs.ho_ten, hs.lop,
                   COALESCE(dd.so_ngay_co_mat,0) AS so_ngay_co_mat,
                   COALESCE(tp.phai_thu, dd.so_ngay_co_mat * 0, 0) AS phai_thu,
                   COALESCE(tp.da_thu,0) AS da_thu,
                   COALESCE(tp.trang_thai,'Chưa thu') AS trang_thai_thu
            FROM $table_hoc_sinh hs
            LEFT JOIN (
                SELECT hoc_sinh_id, COUNT(*) AS so_ngay_co_mat
                FROM $table_diem_danh
                WHERE DATE_FORMAT(ngay,'%%Y-%%m') = %s
                  AND trang_thai = 'co_mat'
                GROUP BY hoc_sinh_id
            ) dd ON dd.hoc_sinh_id = hs.id
            LEFT JOIN $table_thu_phi tp
              ON tp.hoc_sinh_id = hs.id AND tp.thang = %s
            $where
            ORDER BY hs.lop ASC, hs.ho_ten ASC";

    $query_params = array($thang, $thang);
    foreach ($params as $param) {
        $query_params[] = $param;
    }

    $rows = $wpdb->get_results($wpdb->prepare($sql, $query_params));

    $tong_hoc_sinh = count($rows);
    $tong_co_mat = 0;
    $tong_phai_thu = 0;
    $tong_da_thu = 0;
    $tong_con_no = 0;

    foreach ($rows as $row) {
        $tong_co_mat += (int)$row->so_ngay_co_mat;
        $tong_phai_thu += (float)$row->phai_thu;
        $tong_da_thu += (float)$row->da_thu;
        $tong_con_no += max(0, (float)$row->phai_thu - (float)$row->da_thu);
    }

    $ngay_dau = $thang . '-01';
    $ngay_cuoi = date('Y-m-t', strtotime($ngay_dau));

    $tong_vang = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_diem_danh dd
         INNER JOIN $table_hoc_sinh hs ON hs.id = dd.hoc_sinh_id
         WHERE dd.ngay BETWEEN %s AND %s
           AND dd.trang_thai = 'vang'
           AND hs.trang_thai = 'Đang bán trú'" . ($lop !== '' ? ' AND hs.lop = %s' : ''),
        $lop !== '' ? array($ngay_dau, $ngay_cuoi, $lop) : array($ngay_dau, $ngay_cuoi)
    ));

    $tong_co_phep = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_diem_danh dd
         INNER JOIN $table_hoc_sinh hs ON hs.id = dd.hoc_sinh_id
         WHERE dd.ngay BETWEEN %s AND %s
           AND dd.trang_thai = 'co_phep'
           AND hs.trang_thai = 'Đang bán trú'" . ($lop !== '' ? ' AND hs.lop = %s' : ''),
        $lop !== '' ? array($ngay_dau, $ngay_cuoi, $lop) : array($ngay_dau, $ngay_cuoi)
    ));

    $today = current_time('Y-m-d');
    $today_co_mat = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_diem_danh dd
         INNER JOIN $table_hoc_sinh hs ON hs.id = dd.hoc_sinh_id
         WHERE dd.ngay = %s AND dd.trang_thai = 'co_mat'
           AND hs.trang_thai = 'Đang bán trú'" . ($lop !== '' ? ' AND hs.lop = %s' : ''),
        $lop !== '' ? array($today, $lop) : array($today)
    ));

    ?>
    <div class="wrap">
        <h1>📊 BÁO CÁO & THỐNG KÊ BÁN TRÚ</h1>
        <p>Tổng hợp điểm danh và thu phí theo tháng. Các số liệu được lấy trực tiếp từ dữ liệu đã nhập.</p>
        <hr>

        <form method="get" style="background:#fff;border:1px solid #ddd;padding:15px;margin-bottom:20px;">
            <input type="hidden" name="page" value="qlbt-bao-cao">
            <label><strong>Tháng:</strong></label>
            <input type="month" name="thang" value="<?php echo esc_attr($thang); ?>" required>
            <label style="margin-left:15px;"><strong>Lớp:</strong></label>
            <select name="lop">
                <option value="">-- Tất cả lớp --</option>
                <?php foreach ($ds_lop as $ten_lop): ?>
                    <option value="<?php echo esc_attr($ten_lop); ?>" <?php selected($lop,$ten_lop); ?>><?php echo esc_html($ten_lop); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button button-primary" type="submit">🔎 Xem báo cáo</button>
            <button class="button" type="button" onclick="window.print()">🖨️ In báo cáo</button>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=qlbt_export_bao_cao&thang='.rawurlencode($thang).'&lop='.rawurlencode($lop)), 'qlbt_export_bao_cao')); ?>">📥 Xuất Excel (CSV)</a>
        </form>

        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:15px;margin:20px 0;">
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n($tong_hoc_sinh); ?></div><div>Học sinh bán trú</div></div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n($today_co_mat); ?></div><div>Có mặt hôm nay</div></div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n($tong_co_mat); ?></div><div>Lượt có mặt trong tháng</div></div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n($tong_vang + $tong_co_phep); ?></div><div>Lượt vắng / có phép</div></div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;text-align:center;"><div style="font-size:28px;font-weight:bold;"><?php echo number_format_i18n($tong_con_no); ?> đ</div><div>Còn phải thu</div></div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:20px;">
            <div style="background:#fff;border:1px solid #ddd;padding:18px;"><strong>Tổng phải thu</strong><div style="font-size:24px;margin-top:8px;"><?php echo number_format_i18n($tong_phai_thu); ?> đ</div></div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;"><strong>Đã thu</strong><div style="font-size:24px;margin-top:8px;"><?php echo number_format_i18n($tong_da_thu); ?> đ</div></div>
            <div style="background:#fff;border:1px solid #ddd;padding:18px;"><strong>Vắng / Có phép</strong><div style="font-size:24px;margin-top:8px;"><?php echo number_format_i18n($tong_vang); ?> / <?php echo number_format_i18n($tong_co_phep); ?></div></div>
        </div>

        <h2>📋 Chi tiết theo học sinh</h2>
        <table class="widefat striped" id="qlbt-bao-cao-table">
            <thead><tr>
                <th>STT</th><th>Mã HS</th><th>Họ và tên</th><th>Lớp</th>
                <th>Số ngày có mặt</th><th>Phải thu</th><th>Đã thu</th><th>Còn nợ</th><th>Trạng thái</th>
            </tr></thead>
            <tbody>
            <?php if (!empty($rows)): ?>
                <?php $stt=1; foreach ($rows as $row): $con_no=max(0,(float)$row->phai_thu-(float)$row->da_thu); ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><?php echo esc_html($row->ma_hoc_sinh); ?></td>
                        <td><strong><?php echo esc_html($row->ho_ten); ?></strong></td>
                        <td><?php echo esc_html($row->lop); ?></td>
                        <td><?php echo number_format_i18n((int)$row->so_ngay_co_mat); ?></td>
                        <td><?php echo number_format_i18n((float)$row->phai_thu); ?> đ</td>
                        <td><?php echo number_format_i18n((float)$row->da_thu); ?> đ</td>
                        <td><?php echo number_format_i18n($con_no); ?> đ</td>
                        <td>
                            <?php if ($con_no <= 0 && (float)$row->phai_thu > 0): ?>
                                <span style="color:green;font-weight:bold;">Đã thu đủ</span>
                            <?php elseif ((float)$row->da_thu > 0): ?>
                                <span style="color:#d98200;font-weight:bold;">Thu một phần</span>
                            <?php else: ?>
                                <span style="color:red;font-weight:bold;">Chưa thu</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;padding:30px;">Chưa có dữ liệu cho bộ lọc này.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <p style="margin-top:15px;color:#666;">Tháng báo cáo: <strong><?php echo esc_html($thang); ?></strong><?php echo $lop !== '' ? ' — Lớp <strong>'.esc_html($lop).'</strong>' : ' — Tất cả lớp'; ?>.</p>
    </div>
    <style>
    @media print {
        #adminmenumain,#wpadminbar,#screen-meta,#screen-meta-links,.notice,.update-nag,#wpfooter,.wrap form,.wrap .button { display:none !important; }
        #wpcontent { margin-left:0 !important; }
        .wrap { margin:0 !important; }
        table { font-size:12px; }
    }
    </style>
    <?php
}

function qlbt_export_bao_cao() {
    if (!current_user_can('qlbt_bao_cao')) {
        wp_die('Bạn không có quyền xuất báo cáo.');
    }
    check_admin_referer('qlbt_export_bao_cao');

    global $wpdb;
    $table_hoc_sinh = $wpdb->prefix . 'bt_hoc_sinh';
    $table_diem_danh = $wpdb->prefix . 'bt_diem_danh';
    $table_thu_phi = $wpdb->prefix . 'bt_thu_phi';
    $thang = isset($_GET['thang']) ? sanitize_text_field($_GET['thang']) : current_time('Y-m');
    $lop = isset($_GET['lop']) ? sanitize_text_field($_GET['lop']) : '';

    if (!preg_match('/^\d{4}-\d{2}$/', $thang)) $thang = current_time('Y-m');

    $where = "WHERE hs.trang_thai = 'Đang bán trú'";
    $params = array($thang,$thang);
    if ($lop !== '') { $where .= ' AND hs.lop = %s'; $params[] = $lop; }

    $sql = "SELECT hs.ma_hoc_sinh,hs.ho_ten,hs.lop,
                   COALESCE(dd.so_ngay_co_mat,0) so_ngay_co_mat,
                   COALESCE(tp.phai_thu,0) phai_thu,
                   COALESCE(tp.da_thu,0) da_thu
            FROM $table_hoc_sinh hs
            LEFT JOIN (SELECT hoc_sinh_id,COUNT(*) so_ngay_co_mat FROM $table_diem_danh WHERE DATE_FORMAT(ngay,'%%Y-%%m')=%s AND trang_thai='co_mat' GROUP BY hoc_sinh_id) dd ON dd.hoc_sinh_id=hs.id
            LEFT JOIN $table_thu_phi tp ON tp.hoc_sinh_id=hs.id AND tp.thang=%s
            $where ORDER BY hs.lop,hs.ho_ten";
    $rows = $wpdb->get_results($wpdb->prepare($sql,$params));

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="bao-cao-ban-tru-'.$thang.'.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output','w');
    fputcsv($out,array('BÁO CÁO BÁN TRÚ THÁNG',$thang));
    fputcsv($out,array('Mã HS','Họ và tên','Lớp','Số ngày có mặt','Phải thu','Đã thu','Còn nợ','Trạng thái'));
    foreach ($rows as $row) {
        $con_no=max(0,(float)$row->phai_thu-(float)$row->da_thu);
        $status=$con_no<=0 && (float)$row->phai_thu>0 ? 'Đã thu đủ' : ((float)$row->da_thu>0 ? 'Thu một phần':'Chưa thu');
        fputcsv($out,array($row->ma_hoc_sinh,$row->ho_ten,$row->lop,(int)$row->so_ngay_co_mat,(float)$row->phai_thu,(float)$row->da_thu,$con_no,$status));
    }
    fclose($out);
    exit;
}

add_action('admin_post_qlbt_export_bao_cao','qlbt_export_bao_cao');


/* =====================================================
   QUẢN LÝ PHỤ HUYNH
===================================================== */
function qlbt_phu_huynh() {
    global $wpdb;
    $table = $wpdb->prefix . 'bt_hoc_sinh';
    $keyword = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
    $lop = isset($_GET['lop']) ? sanitize_text_field($_GET['lop']) : '';
    $where = "WHERE trang_thai='Đang bán trú'";
    $params = array();
    if ($keyword !== '') {
        $like = '%' . $wpdb->esc_like($keyword) . '%';
        $where .= " AND (ho_ten LIKE %s OR ma_hoc_sinh LIKE %s OR ho_ten_phu_huynh LIKE %s OR so_dien_thoai LIKE %s)";
        $params = array($like,$like,$like,$like);
    }
    if ($lop !== '') { $where .= ' AND lop=%s'; $params[]=$lop; }
    $sql="SELECT id,ma_hoc_sinh,ho_ten,lop,ho_ten_phu_huynh,so_dien_thoai,dia_chi FROM $table $where ORDER BY lop,ho_ten";
    $rows = $params ? $wpdb->get_results($wpdb->prepare($sql,$params)) : $wpdb->get_results($sql);
    $ds_lop=array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C');
    ?>
    <div class="wrap">
      <h1>👨‍👩‍👧 QUẢN LÝ PHỤ HUYNH</h1>
      <p>Danh sách liên hệ phụ huynh/người giám hộ của học sinh đang bán trú.</p>
      <form method="get" style="margin:15px 0;">
        <input type="hidden" name="page" value="qlbt-phu-huynh">
        <input type="search" name="keyword" value="<?php echo esc_attr($keyword); ?>" placeholder="Tên HS, mã HS, phụ huynh, số điện thoại" style="width:320px;">
        <select name="lop"><option value="">-- Tất cả lớp --</option><?php foreach($ds_lop as $l): ?><option value="<?php echo esc_attr($l); ?>" <?php selected($lop,$l); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?></select>
        <button class="button button-primary">🔎 Tìm kiếm</button>
      </form>
      <table class="widefat striped">
        <thead><tr><th>STT</th><th>Mã HS</th><th>Học sinh</th><th>Lớp</th><th>Phụ huynh/người giám hộ</th><th>Số điện thoại</th><th>Địa chỉ</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php if($rows): $stt=1; foreach($rows as $r): ?>
          <tr><td><?php echo $stt++; ?></td><td><?php echo esc_html($r->ma_hoc_sinh); ?></td><td><strong><?php echo esc_html($r->ho_ten); ?></strong></td><td><?php echo esc_html($r->lop); ?></td><td><?php echo esc_html($r->ho_ten_phu_huynh); ?></td><td><?php echo esc_html($r->so_dien_thoai); ?></td><td><?php echo esc_html($r->dia_chi); ?></td><td><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=qlbt-hoc-sinh&sua='.intval($r->id))); ?>">✏️ Sửa hồ sơ</a></td></tr>
        <?php endforeach; else: ?><tr><td colspan="8" style="text-align:center;padding:30px;">Không tìm thấy dữ liệu.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

/* =====================================================
   SAO LƯU DỮ LIỆU
===================================================== */
function qlbt_backup() {
    global $wpdb;
    $tables = array(
        $wpdb->prefix.'bt_hoc_sinh',
        $wpdb->prefix.'bt_diem_danh',
        $wpdb->prefix.'bt_thuc_don',
        $wpdb->prefix.'bt_thu_phi'
    );
    $exists=array();
    foreach($tables as $t) { $exists[$t] = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t)) === $t); }
    ?>
    <div class="wrap">
      <h1>💾 SAO LƯU DỮ LIỆU</h1>
      <div style="background:#fff;border:1px solid #ddd;padding:22px;max-width:850px;">
        <h2>🔐 Sao lưu toàn bộ dữ liệu bán trú</h2>
        <p>File sao lưu chứa dữ liệu học sinh, điểm danh, thực đơn và thu phí hiện có trong hệ thống. Nên sao lưu định kỳ trước khi nâng cấp hoặc thay đổi máy chủ.</p>
        <p><a class="button button-primary button-large" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=qlbt_backup_sql'),'qlbt_backup_sql')); ?>">⬇️ Tải file sao lưu SQL</a></p>
        <hr>
        <h3>Tình trạng dữ liệu</h3>
        <ul><?php foreach($tables as $t): ?><li><?php echo $exists[$t] ? '✅' : '⚠️'; ?> <?php echo esc_html($t); ?><?php if($exists[$t]): ?> — <strong><?php echo number_format_i18n((int)$wpdb->get_var("SELECT COUNT(*) FROM $t")); ?></strong> dòng<?php endif; ?></li><?php endforeach; ?></ul>
      </div>
    </div>
    <?php
}

function qlbt_backup_sql() {
    if (!current_user_can('qlbt_backup')) wp_die('Bạn không có quyền sao lưu dữ liệu.');
    check_admin_referer('qlbt_backup_sql');
    global $wpdb;
    $tables=array($wpdb->prefix.'bt_hoc_sinh',$wpdb->prefix.'bt_diem_danh',$wpdb->prefix.'bt_thuc_don',$wpdb->prefix.'bt_thu_phi');
    $out="-- SAO LƯU QUẢN LÝ BÁN TRÚ\n-- Ngày: ".current_time('Y-m-d H:i:s')."\n\nSET NAMES utf8mb4;\n\n";
    foreach($tables as $table) {
        if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table)) !== $table) continue;
        $create=$wpdb->get_row("SHOW CREATE TABLE `$table`",ARRAY_N);
        if($create) $out .= "DROP TABLE IF EXISTS `$table`;\n".$create[1].";\n\n";
        $rows=$wpdb->get_results("SELECT * FROM `$table`",ARRAY_A);
        foreach($rows as $row) {
            $cols=array(); $vals=array();
            foreach($row as $col=>$val){ $cols[]='`'.$col.'`'; $vals[] = is_null($val) ? 'NULL' : "'".esc_sql($val)."'"; }
            $out .= "INSERT INTO `$table` (".implode(',',$cols).") VALUES (".implode(',',$vals).");\n";
        }
        $out .= "\n";
    }
    nocache_headers();
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="ban-tru-backup-'.current_time('Y-m-d-His').'.sql"');
    echo $out; exit;
}
add_action('admin_post_qlbt_backup_sql','qlbt_backup_sql');

/* =====================================================
   PHÂN QUYỀN NGƯỜI DÙNG
===================================================== */
function qlbt_phan_quyen() {
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền quản lý người dùng.');
    }

    if (isset($_POST['qlbt_create_user'])) {
        check_admin_referer('qlbt_create_user');
        $username = isset($_POST['username']) ? sanitize_user(wp_unslash($_POST['username']), true) : '';
        $display_name = isset($_POST['display_name']) ? sanitize_text_field(wp_unslash($_POST['display_name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $role = isset($_POST['new_role']) ? sanitize_key($_POST['new_role']) : 'qlbt_giao_vien';
        $allowed = array('qlbt_nhan_vien_cntt','qlbt_giao_vien','qlbt_thu_quy','qlbt_quan_ly');

        if ($username === '' || $display_name === '' || $email === '' || $password === '') {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Vui lòng nhập đầy đủ tài khoản, họ tên, email và mật khẩu.</p></div>';
        } elseif (!in_array($role, $allowed, true)) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Vai trò không hợp lệ.</p></div>';
        } elseif (username_exists($username)) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Tên đăng nhập <strong>'.esc_html($username).'</strong> đã tồn tại.</p></div>';
        } elseif (email_exists($email)) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Email <strong>'.esc_html($email).'</strong> đã được sử dụng.</p></div>';
        } elseif (strlen($password) < 8) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Mật khẩu phải có ít nhất 8 ký tự.</p></div>';
        } else {
            $new_user = wp_insert_user(array(
                'user_login'   => $username,
                'user_pass'    => $password,
                'user_email'   => $email,
                'display_name' => $display_name,
                'nickname'     => $display_name,
                'role'         => $role,
            ));
            if (is_wp_error($new_user)) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Không tạo được tài khoản: '.esc_html($new_user->get_error_message()).'</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Đã tạo tài khoản <strong>'.esc_html($username).'</strong> với vai trò <strong>'.esc_html($role).'</strong>.</p></div>';
            }
        }
    }

    if (isset($_POST['qlbt_assign_role'])) {
        check_admin_referer('qlbt_assign_role');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $role = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';
        $allowed = array('qlbt_nhan_vien_cntt','qlbt_giao_vien','qlbt_thu_quy','qlbt_quan_ly');
        $user = $user_id ? get_user_by('id',$user_id) : false;
        if ($user && in_array($role,$allowed,true)) {
            $user->set_role($role);
            echo '<div class="notice notice-success is-dismissible"><p>✅ Đã cập nhật quyền cho <strong>'.esc_html($user->display_name).'</strong>.</p></div>';
        }
    }

    $users = get_users(array('orderby'=>'display_name','order'=>'ASC','fields'=>'all'));
    $roles = array(
        'qlbt_nhan_vien_cntt' => '🖥️ Nhân viên CNTT',
        'qlbt_giao_vien' => '👩‍🏫 Giáo viên',
        'qlbt_thu_quy' => '💰 Thủ quỹ',
        'qlbt_quan_ly' => '🏫 Quản lý bán trú',
    );
    ?>
    <div class="wrap">
        <h1>🔐 PHÂN QUYỀN NGƯỜI DÙNG</h1>
        <p>Thiết lập tài khoản theo đúng nhiệm vụ trong hệ thống bán trú.</p>

        <div class="qlbt-page-card">
            <h2 style="margin-top:0">➕ Tạo tài khoản nhân viên</h2>
            <p style="color:#64748b">Tạo trực tiếp tài khoản WordPress cho Nhân viên CNTT, Giáo viên, Thủ quỹ hoặc Quản lý bán trú.</p>
            <form method="post" style="display:grid;grid-template-columns:repeat(2,minmax(260px,1fr));gap:14px;align-items:end">
                <?php wp_nonce_field('qlbt_create_user'); ?>
                <input type="hidden" name="qlbt_create_user" value="1">
                <div><label><strong>Tên đăng nhập</strong></label><input type="text" name="username" class="regular-text" required placeholder="vd: giaovien01"></div>
                <div><label><strong>Họ và tên</strong></label><input type="text" name="display_name" class="regular-text" required placeholder="Nguyễn Văn A"></div>
                <div><label><strong>Email</strong></label><input type="email" name="email" class="regular-text" required placeholder="email@truong.edu.vn"></div>
                <div><label><strong>Mật khẩu</strong> <span style="font-weight:normal;color:#64748b">(tối thiểu 8 ký tự)</span></label><input type="password" name="password" class="regular-text" minlength="8" required></div>
                <div><label><strong>Vai trò</strong></label><select name="new_role" style="min-width:260px">
                    <option value="qlbt_nhan_vien_cntt">🖥️ Nhân viên CNTT</option>
                    <option value="qlbt_giao_vien">👩‍🏫 Giáo viên</option>
                    <option value="qlbt_thu_quy">💰 Thủ quỹ</option>
                    <option value="qlbt_quan_ly">🏫 Quản lý bán trú</option>
                </select></div>
                <div><button class="button button-primary" type="submit" style="height:38px;padding:0 20px">➕ Tạo tài khoản</button></div>
            </form>
        </div>

        <div class="qlbt-page-card">
            <h2 style="margin-top:0">👥 Tài khoản hệ thống</h2>
            <p style="color:#64748b">Tài khoản <strong>Administrator</strong> vẫn có toàn quyền WordPress và toàn bộ chức năng bán trú.</p>
            <table class="widefat">
                <thead><tr><th>Tài khoản</th><th>Email</th><th>Vai trò hiện tại</th><th>Cấp quyền bán trú</th></tr></thead>
                <tbody>
                <?php foreach($users as $u): ?>
                    <tr>
                        <td><strong><?php echo esc_html($u->display_name); ?></strong></td>
                        <td><?php echo esc_html($u->user_email); ?></td>
                        <td><?php echo esc_html(implode(', ', $u->roles)); ?></td>
                        <td>
                            <?php if (in_array('administrator',$u->roles,true)): ?>
                                <span class="qlbt-badge qlbt-badge-green">Administrator · Toàn quyền</span>
                            <?php else: ?>
                                <form method="post" style="display:flex;gap:8px;align-items:center;padding:0!important;border:0!important;box-shadow:none!important;background:transparent!important">
                                    <?php wp_nonce_field('qlbt_assign_role'); ?>
                                    <input type="hidden" name="qlbt_assign_role" value="1">
                                    <input type="hidden" name="user_id" value="<?php echo intval($u->ID); ?>">
                                    <select name="role">
                                        <?php foreach($roles as $slug=>$label): ?><option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option><?php endforeach; ?>
                                    </select>
                                    <button class="button button-primary" type="submit">Lưu quyền</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="qlbt-page-card">
            <h2 style="margin-top:0">📌 Quyền theo vai trò</h2>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                <div><strong>🖥️ CNTT</strong><p>Toàn bộ chức năng bán trú, sao lưu và báo cáo.</p></div>
                <div><strong>👩‍🏫 Giáo viên</strong><p>Học sinh, điểm danh và suất ăn.</p></div>
                <div><strong>💰 Thủ quỹ</strong><p>Thu phí, báo cáo và xem học sinh.</p></div>
                <div><strong>🏫 Quản lý</strong><p>Quản lý toàn bộ nghiệp vụ bán trú, trừ sao lưu hệ thống.</p></div>
            </div>
        </div>
    </div>
    <?php
}


/* =====================================================
   IN ẤN & TRANG GIỚI THIỆU HỆ THỐNG
===================================================== */
function qlbt_in_an() {
    if (!current_user_can('qlbt_access')) wp_die('Bạn không có quyền truy cập.');
    $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
    if ($type === 'phieu-thu') { qlbt_print_phieu_thu(); return; }
    if ($type === 'suat-an') { qlbt_print_suat_an(); return; }
    if ($type === 'diem-danh') { qlbt_print_diem_danh(); return; }
    ?>
    <div class="wrap">
      <h1>🖨️ IN ẤN HỒ SƠ</h1>
      <p>Chọn loại biểu mẫu cần in. Các mẫu được thiết kế theo khổ A4 và tối ưu khi in.</p>
      <div class="qlbt-about-grid" style="margin-top:20px">
        <div class="qlbt-about-card"><div style="font-size:34px">💰</div><h2>Phiếu thu bán trú</h2><p>In phiếu xác nhận số tiền đã thu của từng học sinh theo tháng.</p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=qlbt-in-an&type=phieu-thu')); ?>">Mở mẫu phiếu thu</a></div>
        <div class="qlbt-about-card"><div style="font-size:34px">🍽️</div><h2>Danh sách suất ăn</h2><p>In danh sách số suất ăn theo ngày, có thể lọc theo lớp.</p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=qlbt-in-an&type=suat-an')); ?>">Mở danh sách suất ăn</a></div>
        <div class="qlbt-about-card"><div style="font-size:34px">📋</div><h2>Danh sách điểm danh</h2><p>In danh sách học sinh và trạng thái điểm danh trong ngày.</p><a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=qlbt-in-an&type=diem-danh')); ?>">Mở danh sách điểm danh</a></div>
      </div>
    </div>
    <?php
}

function qlbt_print_phieu_thu() {
    if (!current_user_can('qlbt_thu_phi') && !current_user_can('qlbt_access')) wp_die('Bạn không có quyền.');
    global $wpdb;
    $hs_table=$wpdb->prefix.'bt_hoc_sinh'; $tp_table=$wpdb->prefix.'bt_thu_phi';
    $thang=isset($_GET['thang']) ? sanitize_text_field($_GET['thang']) : current_time('Y-m');
    $id=isset($_GET['hoc_sinh_id']) ? absint($_GET['hoc_sinh_id']) : 0;
    $students=$wpdb->get_results("SELECT id,ma_hoc_sinh,ho_ten,lop FROM $hs_table WHERE trang_thai='Đang bán trú' ORDER BY lop,ho_ten");
    $row=$id ? $wpdb->get_row($wpdb->prepare("SELECT hs.*,tp.so_ngay,tp.phai_thu,tp.da_thu,tp.trang_thai,tp.ghi_chu FROM $hs_table hs LEFT JOIN $tp_table tp ON tp.hoc_sinh_id=hs.id AND tp.thang=%s WHERE hs.id=%d",$thang,$id)) : false;
    ?>
    <div class="wrap qlbt-no-print">
      <h1>💰 PHIẾU THU BÁN TRÚ</h1>
      <form method="get"><input type="hidden" name="page" value="qlbt-in-an"><input type="hidden" name="type" value="phieu-thu"><label>Tháng: </label><input type="month" name="thang" value="<?php echo esc_attr($thang); ?>"><label style="margin-left:12px">Học sinh: </label><select name="hoc_sinh_id" style="min-width:300px"><option value="">-- Chọn học sinh --</option><?php foreach($students as $st): ?><option value="<?php echo intval($st->id); ?>" <?php selected($id,$st->id); ?>><?php echo esc_html($st->ho_ten.' - Lớp '.$st->lop); ?></option><?php endforeach; ?></select> <button class="button button-primary">Xem phiếu</button></form>
    </div>
    <?php if($row): $con_lai=max(0,(float)$row->phai_thu-(float)$row->da_thu); ?>
      <div class="qlbt-print-paper">
        <div class="qlbt-print-head"><strong>TRƯỜNG TIỂU HỌC NGUYỄN AN NINH</strong><h1>PHIẾU THU TIỀN BÁN TRÚ</h1><p>Tháng <?php echo esc_html(date('m/Y',strtotime($thang.'-01'))); ?></p></div>
        <div class="qlbt-receipt-meta"><div><strong>Họ và tên:</strong> <?php echo esc_html($row->ho_ten); ?></div><div><strong>Lớp:</strong> <?php echo esc_html($row->lop); ?></div><div><strong>Mã học sinh:</strong> <?php echo esc_html($row->ma_hoc_sinh); ?></div><div><strong>Số ngày có mặt:</strong> <?php echo number_format_i18n((int)$row->so_ngay); ?> ngày</div></div>
        <table class="qlbt-print-table"><thead><tr><th>Nội dung</th><th style="width:180px">Số tiền</th></tr></thead><tbody><tr><td>Tiền bán trú phải thu</td><td><?php echo number_format_i18n($row->phai_thu); ?> đ</td></tr><tr><td>Đã thu</td><td><?php echo number_format_i18n($row->da_thu); ?> đ</td></tr><tr><td><strong>Còn phải thu</strong></td><td><strong><?php echo number_format_i18n($con_lai); ?> đ</strong></td></tr></tbody></table>
        <p style="margin-top:18px"><strong>Trạng thái:</strong> <?php echo esc_html($row->trang_thai ?: 'Chưa thu'); ?></p>
        <div class="qlbt-sign"><div><strong>Người nộp tiền</strong><br><br><br>________________</div><div><strong>Người thu tiền</strong><br><br><br>________________</div></div>
        <p style="text-align:center;margin-top:30px;color:#64748b;font-size:11px">In từ Hệ thống Quản lý bán trú · Trường Tiểu học Nguyễn An Ninh</p>
      </div>
      <div class="wrap qlbt-no-print" style="text-align:center"><button class="button button-primary button-large" onclick="window.print()">🖨️ In phiếu</button></div>
    <?php elseif($id): ?><div class="wrap qlbt-no-print"><div class="notice notice-warning"><p>Chưa có dữ liệu thu phí cho học sinh này trong tháng <?php echo esc_html($thang); ?>.</p></div></div><?php endif;
}

function qlbt_print_suat_an() {
    if (!current_user_can('qlbt_suat_an') && !current_user_can('qlbt_access')) wp_die('Bạn không có quyền.');
    global $wpdb; $hs=$wpdb->prefix.'bt_hoc_sinh'; $dd=$wpdb->prefix.'bt_diem_danh';
    $ngay=isset($_GET['ngay'])?sanitize_text_field($_GET['ngay']):current_time('Y-m-d'); $lop=isset($_GET['lop'])?sanitize_text_field($_GET['lop']):'';
    $where="WHERE hs.trang_thai='Đang bán trú'"; $params=array($ngay); if($lop!==''){ $where.=' AND hs.lop=%s'; $params[]=$lop; }
    $rows=$wpdb->get_results($wpdb->prepare("SELECT hs.ma_hoc_sinh,hs.ho_ten,hs.lop,COALESCE(dd.trang_thai,'chua_diem_danh') trang_thai FROM $hs hs LEFT JOIN $dd dd ON dd.hoc_sinh_id=hs.id AND dd.ngay=%s $where ORDER BY hs.lop,hs.ho_ten",$params));
    ?>
    <div class="wrap qlbt-no-print"><h1>🍽️ IN DANH SÁCH SUẤT ĂN</h1><form method="get"><input type="hidden" name="page" value="qlbt-in-an"><input type="hidden" name="type" value="suat-an"><label>Ngày: </label><input type="date" name="ngay" value="<?php echo esc_attr($ngay); ?>"><label style="margin-left:12px">Lớp: </label><select name="lop"><option value="">-- Tất cả lớp --</option><?php foreach(array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C') as $c): ?><option value="<?php echo esc_attr($c); ?>" <?php selected($lop,$c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?></select> <button class="button button-primary">Xem danh sách</button></form></div>
    <div class="qlbt-print-paper"><div class="qlbt-print-head"><strong>TRƯỜNG TIỂU HỌC NGUYỄN AN NINH</strong><h1>DANH SÁCH SUẤT ĂN BÁN TRÚ</h1><p>Ngày <?php echo esc_html(wp_date('d/m/Y',strtotime($ngay))); ?><?php echo $lop?' · Lớp '.esc_html($lop):''; ?></p></div><table class="qlbt-print-table"><thead><tr><th>STT</th><th>Mã HS</th><th>Họ và tên</th><th>Lớp</th><th>Trạng thái</th><th>Suất</th></tr></thead><tbody><?php $i=1;$total=0;foreach($rows as $r){$meal=$r->trang_thai==='co_mat'?1:0;$total+=$meal;echo '<tr><td>'.($i++).'</td><td>'.esc_html($r->ma_hoc_sinh).'</td><td>'.esc_html($r->ho_ten).'</td><td>'.esc_html($r->lop).'</td><td>'.esc_html($r->trang_thai==='co_mat'?'Có mặt':($r->trang_thai==='vang'?'Vắng':($r->trang_thai==='co_phep'?'Có phép':'Chưa điểm danh'))).'</td><td>'.$meal.'</td></tr>';} ?><tr><td colspan="5" style="text-align:right"><strong>TỔNG SỐ SUẤT</strong></td><td><strong><?php echo number_format_i18n($total); ?></strong></td></tr></tbody></table><div class="qlbt-sign"><div><strong>Người lập</strong><br><br><br>________________</div><div><strong>Người phụ trách</strong><br><br><br>________________</div></div></div><div class="wrap qlbt-no-print" style="text-align:center"><button class="button button-primary button-large" onclick="window.print()">🖨️ In danh sách</button></div>
    <?php
}

function qlbt_print_diem_danh() {
    if (!current_user_can('qlbt_diem_danh') && !current_user_can('qlbt_access')) wp_die('Bạn không có quyền.');
    global $wpdb; $hs=$wpdb->prefix.'bt_hoc_sinh'; $dd=$wpdb->prefix.'bt_diem_danh';
    $ngay=isset($_GET['ngay'])?sanitize_text_field($_GET['ngay']):current_time('Y-m-d'); $lop=isset($_GET['lop'])?sanitize_text_field($_GET['lop']):'';
    $where="WHERE hs.trang_thai='Đang bán trú'"; $params=array($ngay); if($lop!==''){ $where.=' AND hs.lop=%s'; $params[]=$lop; }
    $rows=$wpdb->get_results($wpdb->prepare("SELECT hs.ma_hoc_sinh,hs.ho_ten,hs.lop,COALESCE(dd.trang_thai,'chua_diem_danh') trang_thai FROM $hs hs LEFT JOIN $dd dd ON dd.hoc_sinh_id=hs.id AND dd.ngay=%s $where ORDER BY hs.lop,hs.ho_ten",$params));
    ?>
    <div class="wrap qlbt-no-print"><h1>📋 IN DANH SÁCH ĐIỂM DANH</h1><form method="get"><input type="hidden" name="page" value="qlbt-in-an"><input type="hidden" name="type" value="diem-danh"><label>Ngày: </label><input type="date" name="ngay" value="<?php echo esc_attr($ngay); ?>"><label style="margin-left:12px">Lớp: </label><select name="lop"><option value="">-- Tất cả lớp --</option><?php foreach(array('1A','1B','1C','2A','2B','2C','3A','3B','3C','4A','4B','4C','5A','5B','5C') as $c): ?><option value="<?php echo esc_attr($c); ?>" <?php selected($lop,$c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?></select> <button class="button button-primary">Xem danh sách</button></form></div>
    <div class="qlbt-print-paper"><div class="qlbt-print-head"><strong>TRƯỜNG TIỂU HỌC NGUYỄN AN NINH</strong><h1>DANH SÁCH ĐIỂM DANH BÁN TRÚ</h1><p>Ngày <?php echo esc_html(wp_date('d/m/Y',strtotime($ngay))); ?><?php echo $lop?' · Lớp '.esc_html($lop):''; ?></p></div><table class="qlbt-print-table"><thead><tr><th>STT</th><th>Mã HS</th><th>Họ và tên</th><th>Lớp</th><th>Trạng thái</th><th>Ghi chú</th></tr></thead><tbody><?php $i=1;foreach($rows as $r){echo '<tr><td>'.($i++).'</td><td>'.esc_html($r->ma_hoc_sinh).'</td><td>'.esc_html($r->ho_ten).'</td><td>'.esc_html($r->lop).'</td><td>'.esc_html($r->trang_thai==='co_mat'?'Có mặt':($r->trang_thai==='vang'?'Vắng':($r->trang_thai==='co_phep'?'Có phép':'Chưa điểm danh'))).'</td><td></td></tr>';} ?></tbody></table><div class="qlbt-sign"><div><strong>Người lập</strong><br><br><br>________________</div><div><strong>Người kiểm tra</strong><br><br><br>________________</div></div></div><div class="wrap qlbt-no-print" style="text-align:center"><button class="button button-primary button-large" onclick="window.print()">🖨️ In danh sách</button></div>
    <?php
}

function qlbt_about() {
    if (!current_user_can('qlbt_access')) wp_die('Bạn không có quyền truy cập.');
    ?>
    <div class="wrap">
      <div class="qlbt-about-hero"><div style="font-size:44px">🍱</div><h1 style="color:#fff;margin:8px 0">QUẢN LÝ BÁN TRÚ</h1><p style="font-size:16px;margin:0;color:rgba(255,255,255,.9)">Hệ thống hỗ trợ quản lý học sinh bán trú · Trường Tiểu học Nguyễn An Ninh</p></div>
      <div class="qlbt-about-grid">
        <div class="qlbt-about-card"><h2>🎯 Mục tiêu</h2><p>Tin học hóa quy trình quản lý học sinh bán trú, điểm danh, suất ăn, thực đơn, phụ huynh và thu phí; giảm thao tác thủ công và hỗ trợ tổng hợp báo cáo nhanh.</p></div>
        <div class="qlbt-about-card"><h2>🧩 Chức năng</h2><p>Quản lý học sinh · Điểm danh · Thực đơn · Suất ăn · Phụ huynh · Thu phí · Báo cáo · Sao lưu · Phân quyền · In ấn.</p></div>
        <div class="qlbt-about-card"><h2>💻 Công nghệ</h2><p>WordPress · PHP · MySQL · HTML/CSS · XAMPP. Hệ thống được thiết kế để có thể triển khai từ máy tính nội bộ đến hosting.</p></div>
      </div>
      <div class="qlbt-page-card" style="margin-top:18px"><h2 style="margin-top:0">📌 Quy trình sử dụng đề xuất</h2><div class="qlbt-step"><b>1</b><div><strong>Học sinh</strong><br><span>Nhập và cập nhật danh sách học sinh bán trú.</span></div></div><div class="qlbt-step"><b>2</b><div><strong>Điểm danh</strong><br><span>Ghi nhận có mặt, vắng, có phép hằng ngày.</span></div></div><div class="qlbt-step"><b>3</b><div><strong>Suất ăn & thực đơn</strong><br><span>Theo dõi số suất thực tế và nội dung bữa ăn.</span></div></div><div class="qlbt-step"><b>4</b><div><strong>Thu phí</strong><br><span>Tính tiền theo số ngày có mặt và ghi nhận số tiền đã thu.</span></div></div><div class="qlbt-step"><b>5</b><div><strong>Báo cáo & sao lưu</strong><br><span>Tổng hợp số liệu, in biểu mẫu và sao lưu dữ liệu định kỳ.</span></div></div></div>
      <div style="text-align:center;color:#64748b;padding:15px">Phiên bản 5.4 · Trường Tiểu học Nguyễn An Ninh</div>
    </div>
    <?php
}

/* =====================================================
   MENU QUẢN LÝ BÁN TRÚ
===================================================== */

function qlbt_menu() {

    add_menu_page(
        'Quản lý bán trú',
        'QUẢN LÝ BÁN TRÚ',
        'qlbt_access',
        'qlbt-dashboard',
        'qlbt_dashboard',
        'dashicons-food',
        26
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Tổng quan',
        'Tổng quan',
        'qlbt_access',
        'qlbt-dashboard',
        'qlbt_dashboard'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Học sinh',
        'Học sinh',
        'qlbt_hoc_sinh',
        'qlbt-hoc-sinh',
        'qlbt_hoc_sinh'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Nhập học sinh từ Excel',
        '📥 Nhập Excel',
        'qlbt_hoc_sinh',
        'qlbt-import-hoc-sinh',
        'qlbt_import_hoc_sinh'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Điểm danh',
        'Điểm danh',
        'qlbt_diem_danh',
        'qlbt-diem-danh',
        'qlbt_diem_danh'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Thực đơn',
        'Thực đơn',
        'qlbt_thuc_don',
        'qlbt-thuc-don',
        'qlbt_thuc_don'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Suất ăn',
        'Suất ăn',
        'qlbt_suat_an',
        'qlbt-suat-an',
        'qlbt_suat_an'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Phụ huynh',
        '👨‍👩‍👧 Phụ huynh',
        'qlbt_phu_huynh',
        'qlbt-phu-huynh',
        'qlbt_phu_huynh'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Thu phí',
        'Thu phí',
        'qlbt_thu_phi',
        'qlbt-thu-phi',
        'qlbt_thu_phi'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Sao lưu dữ liệu',
        '💾 Sao lưu',
        'qlbt_backup',
        'qlbt-backup',
        'qlbt_backup'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Báo cáo & Thống kê',
        '📊 Báo cáo',
        'qlbt_bao_cao',
        'qlbt-bao-cao',
        'qlbt_bao_cao'
    );
    add_submenu_page(
        'qlbt-dashboard',
        'Phân quyền người dùng',
        '🔐 Phân quyền',
        'manage_options',
        'qlbt-phan-quyen',
        'qlbt_phan_quyen'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'In ấn hồ sơ',
        '🖨️ In ấn',
        'qlbt_access',
        'qlbt-in-an',
        'qlbt_in_an'
    );

    add_submenu_page(
        'qlbt-dashboard',
        'Giới thiệu hệ thống',
        'ℹ️ Giới thiệu',
        'qlbt_access',
        'qlbt-gioi-thieu',
        'qlbt_about'
    );

}

add_action('admin_menu', 'qlbt_menu');



/* =====================================================
   TRẢI NGHIỆM ỨNG DỤNG: ẨN BỚT WORDPRESS VỚI TÀI KHOẢN NGHIỆP VỤ
===================================================== */
function qlbt_admin_body_class($classes) {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && strpos((string)$screen->id, 'qlbt') !== false) {
        $classes .= ' qlbt-app-screen';
    }
    return $classes;
}
add_filter('admin_body_class','qlbt_admin_body_class');

function qlbt_restrict_wp_menus() {
    if (current_user_can('manage_options')) return;
    remove_menu_page('edit.php');
    remove_menu_page('upload.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('themes.php');
    remove_menu_page('plugins.php');
    remove_menu_page('users.php');
    remove_menu_page('tools.php');
    remove_menu_page('options-general.php');
}
add_action('admin_menu','qlbt_restrict_wp_menus',999);

function qlbt_admin_footer_branding() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || strpos((string)$screen->id, 'qlbt') === false) return;
    echo '<div style="text-align:center;color:#94a3b8;padding:12px 0 8px;font-size:11px">🍱 <strong>QUẢN LÝ BÁN TRÚ</strong> · Trường Tiểu học Nguyễn An Ninh · Phiên bản 5.4.1</div>';
}
add_action('admin_footer','qlbt_admin_footer_branding');
