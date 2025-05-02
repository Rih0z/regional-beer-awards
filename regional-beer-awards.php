/**
 * File: regional-beer-awards/regional-beer-awards.php
 * Description: Main plugin file with plugin header
 */
<?php
/**
 * Plugin Name: Regional Beer Awards Display
 * Plugin URI: https://rihobeer.com/plugins/regional-beer-awards/
 * Description: 複数のビールコンテストの受賞データを地域別に表示するためのプラグイン
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://rihobeer.com/
 * Text Domain: regional-beer-awards
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// 直接アクセス禁止
if (!defined('ABSPATH')) {
    exit;
}

// 定数定義
define('RBA_VERSION', '2.0.0');
define('RBA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RBA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RBA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 必要なファイルを読み込み
require_once RBA_PLUGIN_DIR . 'includes/class-beer-awards-post-type.php';
require_once RBA_PLUGIN_DIR . 'includes/class-beer-awards-admin.php';
require_once RBA_PLUGIN_DIR . 'includes/class-beer-awards-shortcode.php';
require_once RBA_PLUGIN_DIR . 'includes/class-beer-awards-widget.php';
require_once RBA_PLUGIN_DIR . 'includes/class-beer-awards-rest-api.php';

// メインプラグインクラス
class Regional_Beer_Awards {
    // シングルトンパターン
    private static $instance = null;
    
    // インスタンスの取得
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // コンストラクタ
    private function __construct() {
        // 初期化
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // 各機能クラスのインスタンス化
        $this->post_type = new Beer_Awards_Post_Type();
        $this->admin = new Beer_Awards_Admin();
        $this->shortcode = new Beer_Awards_Shortcode();
        $this->widget = new Beer_Awards_Widget();
        $this->rest_api = new Beer_Awards_REST_API();
        
        // アセット読み込み
        add_action('wp_enqueue_scripts', array($this, 'enqueue_front_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    // テキストドメイン読み込み（多言語対応）
    public function load_textdomain() {
        load_plugin_textdomain('regional-beer-awards', false, dirname(RBA_PLUGIN_BASENAME) . '/languages');
    }
    
    // フロントエンド用アセット読み込み
    public function enqueue_front_assets() {
        wp_enqueue_style('beer-awards-style', RBA_PLUGIN_URL . 'assets/css/beer-awards.css', array(), RBA_VERSION);
        wp_enqueue_script('beer-awards-script', RBA_PLUGIN_URL . 'assets/js/beer-awards.js', array('jquery'), RBA_VERSION, true);
        
        wp_localize_script('beer-awards-script', 'beer_awards_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('beer_filter_nonce')
        ));
    }
    
    // 管理画面用アセット読み込み
    public function enqueue_admin_assets($hook) {
        // 管理画面の特定ページのみでアセットを読み込み
        if (strpos($hook, 'beer-data-import') !== false) {
            wp_enqueue_style('beer-awards-admin-style', RBA_PLUGIN_URL . 'assets/css/beer-awards-admin.css', array(), RBA_VERSION);
            wp_enqueue_script('beer-awards-admin-script', RBA_PLUGIN_URL . 'assets/js/beer-awards-admin.js', array('jquery'), RBA_VERSION, true);
        }
    }
    
    // アクティベーション時の処理
    public static function activate() {
        // カスタム投稿タイプと分類の登録
        require_once RBA_PLUGIN_DIR . 'includes/class-beer-awards-post-type.php';
        $post_type = new Beer_Awards_Post_Type();
        $post_type->register_post_types();
        $post_type->register_taxonomies();
        
        // パーマリンク構造のフラッシュ
        flush_rewrite_rules();
    }
    
    // 非アクティベーション時の処理
    public static function deactivate() {
        // 必要な処理を記述
        flush_rewrite_rules();
    }
}

// インスタンス化
function regional_beer_awards() {
    return Regional_Beer_Awards::get_instance();
}
regional_beer_awards();

// アクティベーション・非アクティベーションフック
register_activation_hook(__FILE__, array('Regional_Beer_Awards', 'activate'));
register_deactivation_hook(__FILE__, array('Regional_Beer_Awards', 'deactivate'));

/**
 * File: regional-beer-awards/readme.txt
 * Description: Plugin description file for WordPress.org
 */
=== Regional Beer Awards Display ===
Contributors: yourname
Tags: beer, awards, competition, craft beer
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

複数のビールコンテストの受賞データを地域別に表示するためのプラグイン

== Description ==
Regional Beer Awards Displayは、World Beer Cup、GABFなど様々なビールコンテストの受賞データを管理し、地域別に表示するためのプラグインです。

主な機能:
* 複数のコンテストに対応
* 地域ごとのフィルタリング
* CSV/JSONのインポート/エクスポート
* マークダウンテーブルからJSONへの変換
* レスポンシブデザイン対応

== Installation ==
1. プラグインをアップロードし、有効化する
2. データをインポートする（CSVまたはJSON形式）
3. ショートコードを使って表示する: [beer_search]

== Frequently Asked Questions ==
= コンテスト別に表示するには？ =
`[beer_search contest="World Beer Cup" year="2025"]`のようにショートコードのパラメータで指定できます。

== Changelog ==
= 2.0.0 =
* 複数コンテスト対応機能を追加
* JSONインポート/エクスポート機能を追加
* マークダウン変換ツールを追加

== Upgrade Notice ==
= 2.0.0 =
複数のビールコンテストに対応するようになりました。

/**
 * File: regional-beer-awards/uninstall.php
 * Description: Handles plugin uninstallation
 */
<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 設定の削除
delete_option('rba_settings');

// カスタム投稿タイプのデータ削除（オプション）
// 注意: これはすべての投稿を削除します。必要に応じてコメントアウト
$args = array(
    'post_type' => 'award_beer',
    'posts_per_page' => -1,
);
$query = new WP_Query($args);

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        wp_delete_post(get_the_ID(), true); // 第2引数trueで完全削除
    }
}
wp_reset_postdata();

// カスタムタクソノミーの削除
$taxonomies = array('beer_contest', 'beer_year', 'beer_category', 'beer_country', 'beer_state', 'beer_city', 'beer_medal');
foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ));
    
    foreach ($terms as $term) {
        wp_delete_term($term->term_id, $taxonomy);
    }
}

/**
 * File: regional-beer-awards/includes/class-beer-awards-post-type.php
 * Description: Registers custom post types and taxonomies
 */
<?php
/**
 * カスタム投稿タイプと分類を管理するクラス
 */
class Beer_Awards_Post_Type {
    public function __construct() {
        // カスタム投稿タイプと分類の登録
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        
        // カスタムメタボックスの追加
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        
        // カスタム投稿タイプのカラム管理
        add_filter('manage_award_beer_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_award_beer_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-award_beer_sortable_columns', array($this, 'sortable_columns'));
    }
    
    // カスタム投稿タイプの登録
    public function register_post_types() {
        register_post_type('award_beer', array(
            'labels' => array(
                'name' => __('受賞ビール', 'regional-beer-awards'),
                'singular_name' => __('受賞ビール', 'regional-beer-awards'),
                'add_new' => __('新規追加', 'regional-beer-awards'),
                'add_new_item' => __('新規受賞ビールを追加', 'regional-beer-awards'),
                'edit_item' => __('受賞ビールを編集', 'regional-beer-awards'),
                'view_item' => __('受賞ビールを表示', 'regional-beer-awards'),
                'search_items' => __('受賞ビールを検索', 'regional-beer-awards'),
                'not_found' => __('受賞ビールが見つかりません', 'regional-beer-awards'),
                'not_found_in_trash' => __('ゴミ箱に受賞ビールはありません', 'regional-beer-awards'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-awards',
            'menu_position' => 5,
            'rewrite' => array('slug' => 'beer-awards'),
            'show_in_rest' => true, // REST API対応
        ));
    }
    
    // 分類の登録
    public function register_taxonomies() {
        // コンテスト（大会）タクソノミー
        register_taxonomy(
            'beer_contest',
            'award_beer',
            array(
                'label' => __('コンテスト', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
            )
        );
        
        // 開催年タクソノミー
        register_taxonomy(
            'beer_year',
            'award_beer',
            array(
                'label' => __('開催年', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
            )
        );
        
        // カテゴリータクソノミー
        register_taxonomy(
            'beer_category',
            'award_beer',
            array(
                'label' => __('ビールカテゴリー', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
            )
        );
        
        // 国タクソノミー
        register_taxonomy(
            'beer_country',
            'award_beer',
            array(
                'label' => __('国', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
            )
        );
        
        // 都道府県タクソノミー
        register_taxonomy(
            'beer_state',
            'award_beer',
            array(
                'label' => __('都道府県/州', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_in_rest' => true,
            )
        );
        
        // 都市タクソノミー
        register_taxonomy(
            'beer_city',
            'award_beer',
            array(
                'label' => __('都市', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_in_rest' => true,
            )
        );
        
        // 受賞メダルタクソノミー
        register_taxonomy(
            'beer_medal',
            'award_beer',
            array(
                'label' => __('メダル', 'regional-beer-awards'),
                'hierarchical' => true,
                'show_admin_column' => true,
                'show_in_rest' => true,
            )
        );
    }
    
    // メタボックスの追加
    public function add_meta_boxes() {
        add_meta_box(
            'beer_details',
            __('ビール詳細情報', 'regional-beer-awards'),
            array($this, 'meta_box_callback'),
            'award_beer',
            'normal',
            'high'
        );
    }
    
    // メタボックスの表示
    public function meta_box_callback($post) {
        wp_nonce_field(basename(__FILE__), 'beer_details_nonce');
        
        // カスタムフィールドから値を取得
        $brewery = get_post_meta($post->ID, '_brewery', true);
        $place = get_post_meta($post->ID, '_place', true);
        
        ?>
        <div class="beer-meta-fields">
            <p>
                <label for="brewery"><?php _e('醸造所:', 'regional-beer-awards'); ?></label>
                <input type="text" id="brewery" name="brewery" value="<?php echo esc_attr($brewery); ?>" class="widefat">
            </p>
            <p>
                <label for="place"><?php _e('順位:', 'regional-beer-awards'); ?></label>
                <input type="number" id="place" name="place" value="<?php echo esc_attr($place); ?>" min="1" max="3">
            </p>
        </div>
        <?php
    }
    
    // メタボックスデータの保存
    public function save_meta_box_data($post_id) {
        // チェックボックスの確認
        if (!isset($_POST['beer_details_nonce']) || !wp_verify_nonce($_POST['beer_details_nonce'], basename(__FILE__))) {
            return $post_id;
        }
        
        // 自動保存チェック
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        
        // 権限の確認
        if ('award_beer' == $_POST['post_type'] && !current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        
        // フィールドの更新
        if (isset($_POST['brewery'])) {
            update_post_meta($post_id, '_brewery', sanitize_text_field($_POST['brewery']));
        }
        
        if (isset($_POST['place'])) {
            update_post_meta($post_id, '_place', intval($_POST['place']));
        }
    }
    
    // 管理画面のカラム設定
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('ビール名', 'regional-beer-awards');
        $new_columns['brewery'] = __('醸造所', 'regional-beer-awards');
        $new_columns['taxonomy-beer_medal'] = __('メダル', 'regional-beer-awards');
        $new_columns['taxonomy-beer_category'] = __('カテゴリー', 'regional-beer-awards');
        $new_columns['taxonomy-beer_country'] = __('国', 'regional-beer-awards');
        $new_columns['taxonomy-beer_contest'] = __('コンテスト', 'regional-beer-awards');
        $new_columns['taxonomy-beer_year'] = __('年', 'regional-beer-awards');
        
        return $new_columns;
    }
    
    // カスタムカラムの内容表示
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'brewery':
                echo esc_html(get_post_meta($post_id, '_brewery', true));
                break;
        }
    }
    
    // ソート可能なカラム設定
    public function sortable_columns($columns) {
        $columns['brewery'] = 'brewery';
        $columns['taxonomy-beer_medal'] = 'taxonomy-beer_medal';
        $columns['taxonomy-beer_category'] = 'taxonomy-beer_category';
        $columns['taxonomy-beer_country'] = 'taxonomy-beer_country';
        $columns['taxonomy-beer_contest'] = 'taxonomy-beer_contest';
        $columns['taxonomy-beer_year'] = 'taxonomy-beer_year';
        
        return $columns;
    }
}

/**
 * File: regional-beer-awards/includes/class-beer-awards-admin.php
 * Description: Admin functionality and import/export options
 */
<?php
/**
 * 管理画面の機能を管理するクラス
 */
class Beer_Awards_Admin {
    public function __construct() {
        // 管理メニューの追加
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // インポート・エクスポート処理のハンドリング
        add_action('admin_init', array($this, 'handle_import_export'));
        
        // AJAXハンドラーの登録
        add_action('wp_ajax_convert_markdown_to_json', array($this, 'ajax_convert_markdown_to_json'));
    }
    
    // 管理メニューの追加
    public function add_admin_menu() {
        // データインポートページ
        add_submenu_page(
            'edit.php?post_type=award_beer', // 親メニュー
            __('ビールデータインポート', 'regional-beer-awards'), // ページタイトル
            __('データインポート', 'regional-beer-awards'), // メニュータイトル
            'manage_options', // 権限
            'beer-data-import', // ページスラッグ
            array($this, 'render_import_page') // 表示関数
        );
        
        // 設定ページ
        add_submenu_page(
            'edit.php?post_type=award_beer',
            __('設定', 'regional-beer-awards'),
            __('設定', 'regional-beer-awards'),
            'manage_options',
            'beer-settings',
            array($this, 'render_settings_page')
        );
    }
    
    // インポートページの表示
    public function render_import_page() {
        // テンプレートを読み込み
        require_once RBA_PLUGIN_DIR . 'templates/admin/import-page.php';
    }
    
    // 設定ページの表示
    public function render_settings_page() {
        // テンプレートを読み込み
        require_once RBA_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }
    
    // インポート・エクスポート処理
    public function handle_import_export() {
        // インポート処理
        if (isset($_POST['csv_import_submit']) && isset($_FILES['csv_file'])) {
            $this->handle_csv_import();
        }
        
        if (isset($_POST['json_import_submit']) && isset($_FILES['json_file'])) {
            $this->handle_json_import();
        }
        
        // エクスポート処理
        if (isset($_GET['action']) && $_GET['action'] == 'export') {
            if (isset($_GET['format']) && $_GET['format'] == 'csv') {
                $this->export_csv();
            } else if (isset($_GET['format']) && $_GET['format'] == 'json') {
                $this->export_json();
            }
        }
    }
    
    // CSV形式のインポート処理
    private function handle_csv_import() {
        if (!wp_verify_nonce($_POST['beer_import_nonce'], 'beer_csv_import_action')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'regional-beer-awards'));
        }
        
        // CSVImporterクラスを使用して処理
        require_once RBA_PLUGIN_DIR . 'includes/importers/class-csv-importer.php';
        $importer = new Beer_Awards_CSV_Importer();
        $result = $importer->import($_FILES['csv_file'], $_POST['contest_name'], $_POST['contest_year'], $_POST['csv_encoding']);
        
        // 結果を通知
        $this->show_import_result($result);
    }
    
    // JSON形式のインポート処理
    private function handle_json_import() {
        if (!wp_verify_nonce($_POST['beer_json_import_nonce'], 'beer_json_import_action')) {
            wp_die(__('セキュリティチェックに失敗しました。', 'regional-beer-awards'));
        }
        
        // JSONImporterクラスを使用して処理
        require_once RBA_PLUGIN_DIR . 'includes/importers/class-json-importer.php';
        $importer = new Beer_Awards_JSON_Importer();
        $result = $importer->import($_FILES['json_file']);
        
        // 結果を通知
        $this->show_import_result($result);
    }
    
    // インポート結果の表示
    private function show_import_result($result) {
        if ($result['success']) {
            add_settings_error(
                'beer_import',
                'beer_import_success',
                sprintf(
                    __('インポート完了: 処理 %d 件, 作成 %d 件, 更新 %d 件, スキップ %d 件', 'regional-beer-awards'),
                    $result['processed'],
                    $result['created'],
                    $result['updated'],
                    $result['skipped']
                ),
                'updated'
            );
            
            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    add_settings_error(
                        'beer_import',
                        'beer_import_error',
                        $error,
                        'error'
                    );
                }
            }
        } else {
            add_settings_error(
                'beer_import',
                'beer_import_error',
                $result['message'],
                'error'
            );
        }
    }
    
    // CSV形式のエクスポート
    private function export_csv() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('この機能を使用する権限がありません。', 'regional-beer-awards'));
        }
        
        // CSVExporterクラスを使用して処理
        require_once RBA_PLUGIN_DIR . 'includes/exporters/class-csv-exporter.php';
        $exporter = new Beer_Awards_CSV_Exporter();
        $exporter->export();
        
        // エクスポート後は終了
        exit;
    }
    
    // JSON形式のエクスポート
    private function export_json() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('この機能を使用する権限がありません。', 'regional-beer-awards'));
        }
        
        // JSONExporterクラスを使用して処理
        require_once RBA_PLUGIN_DIR . 'includes/exporters/class-json-exporter.php';
        $exporter = new Beer_Awards_JSON_Exporter();
        $exporter->export();
        
        // エクスポート後は終了
        exit;
    }
    
    // マークダウンからJSONへの変換AJAX処理
    public function ajax_convert_markdown_to_json() {
        check_ajax_referer('beer_filter_nonce', 'nonce');
        
        $markdown = isset($_POST['markdown']) ? sanitize_textarea_field($_POST['markdown']) : '';
        $contest_name = isset($_POST['contest_name']) ? sanitize_text_field($_POST['contest_name']) : '';
        $contest_year = isset($_POST['contest_year']) ? sanitize_text_field($_POST['contest_year']) : '';
        
        if (empty($markdown) || empty($contest_name) || empty($contest_year)) {
            wp_send_json_error(array('message' => __('入力項目がすべて入力されていることを確認してください。', 'regional-beer-awards')));
            return;
        }
        
        // マークダウン変換クラスを使用
        require_once RBA_PLUGIN_DIR . 'includes/importers/class-markdown-converter.php';
        $converter = new Beer_Awards_Markdown_Converter();
        $result = $converter->convert_to_json($markdown, $contest_name, $contest_year);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'json' => $result['json'],
                'message' => __('変換が完了しました！', 'regional-beer-awards')
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['message']
            ));
        }
    }
}

/**
 * File: regional-beer-awards/includes/class-beer-awards-shortcode.php
 * Description: Registers and handles shortcodes
 */
<?php
/**
 * ショートコードを管理するクラス
 */
class Beer_Awards_Shortcode {
    public function __construct() {
        // ショートコードの登録
        add_shortcode('beer_search', array($this, 'beer_search_shortcode'));
        add_shortcode('brewery_awards', array($this, 'brewery_awards_shortcode'));
        add_shortcode('beer_stats', array($this, 'beer_stats_shortcode'));
        
        // AJAXハンドラーの登録
        add_action('wp_ajax_update_beer_results', array($this, 'ajax_update_beer_results'));
        add_action('wp_ajax_nopriv_update_beer_results', array($this, 'ajax_update_beer_results'));
        
        add_action('wp_ajax_get_states_by_country', array($this, 'ajax_get_states_by_country'));
        add_action('wp_ajax_nopriv_get_states_by_country', array($this, 'ajax_get_states_by_country'));
        
        add_action('wp_ajax_get_cities_by_state', array($this, 'ajax_get_cities_by_state'));
        add_action('wp_ajax_nopriv_get_cities_by_state', array($this, 'ajax_get_cities_by_state'));
    }
    
    // ビール検索フォームのショートコード
    public function beer_search_shortcode($atts) {
        // ショートコード属性の取得
        $attributes = shortcode_atts(
            array(
                'contest' => '',  // デフォルトのコンテストを指定可能
                'year' => '',     // デフォルトの年を指定可能
            ), 
            $atts
        );
        
        // テンプレートを読み込み
        ob_start();
        include(RBA_PLUGIN_DIR . 'templates/search-form.php');
        return ob_get_clean();
    }
    
    // 醸造所別の受賞履歴ショートコード
    public function brewery_awards_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'brewery' => '',
                'limit' => 50,
            ), 
            $atts
        );
        
        if (empty($attributes['brewery'])) {
            return '<p>' . __('醸造所名を指定してください。', 'regional-beer-awards') . '</p>';
        }
        
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => $attributes['limit'],
            'meta_query' => array(
                array(
                    'key' => '_brewery',
                    'value' => $attributes['brewery'],
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<h2>' . esc_html($attributes['brewery']) . ' ' . __('の受賞履歴', 'regional-beer-awards') . '</h2>';
            echo '<div class="brewery-awards-list">';
            
            while ($query->have_posts()) {
                $query->the_post();
                include(RBA_PLUGIN_DIR . 'templates/brewery-award-item.php');
            }
            
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('この醸造所の受賞記録は見つかりませんでした。', 'regional-beer-awards') . '</p>';
        }
        
        return ob_get_clean();
    }
    
    // 統計ダッシュボードのショートコード
    public function beer_stats_shortcode($atts) {
        $attributes = shortcode_atts(
            array(
                'contest' => '',
                'year' => '',
                'chart_type' => 'country', // country, category, medal
            ), 
            $atts
        );
        
        // 統計データの収集処理
        $stats_data = $this->collect_stats_data($attributes['contest'], $attributes['year'], $attributes['chart_type']);
        
        ob_start();
        include(RBA_PLUGIN_DIR . 'templates/stats-dashboard.php');
        return ob_get_clean();
    }
    
    // 統計データの収集
    private function collect_stats_data($contest, $year, $chart_type) {
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => -1,
            'tax_query' => array(),
        );
        
        // コンテストで絞り込み
        if (!empty($contest)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_contest',
                'field' => 'slug',
                'terms' => $contest,
            );
        }
        
        // 年で絞り込み
        if (!empty($year)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_year',
                'field' => 'slug',
                'terms' => $year,
            );
        }
        
        $query = new WP_Query($args);
        
        $stats = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                switch ($chart_type) {
                    case 'country':
                        $terms = get_the_terms(get_the_ID(), 'beer_country');
                        if ($terms && !is_wp_error($terms)) {
                            $country = $terms[0]->name;
                            if (!isset($stats[$country])) {
                                $stats[$country] = 0;
                            }
                            $stats[$country]++;
                        }
                        break;
                        
                    case 'category':
                        $terms = get_the_terms(get_the_ID(), 'beer_category');
                        if ($terms && !is_wp_error($terms)) {
                            $category = $terms[0]->name;
                            if (!isset($stats[$category])) {
                                $stats[$category] = 0;
                            }
                            $stats[$category]++;
                        }
                        break;
                        
                    case 'medal':
                        $terms = get_the_terms(get_the_ID(), 'beer_medal');
                        if ($terms && !is_wp_error($terms)) {
                            $medal = $terms[0]->name;
                            if (!isset($stats[$medal])) {
                                $stats[$medal] = 0;
                            }
                            $stats[$medal]++;
                        }
                        break;
                }
            }
            
            wp_reset_postdata();
            
            // 値の降順でソート
            arsort($stats);
            
            // 上位10項目のみ
            $stats = array_slice($stats, 0, 10);
        }
        
        return $stats;
    }
    
    // 検索結果を更新するAJAX処理
    public function ajax_update_beer_results() {
        check_ajax_referer('beer_filter_nonce', 'nonce');
        
        // フォームデータを解析
        parse_str($_POST['formData'], $form_data);
        
        // クエリを構築
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => isset($form_data['beer_per_page']) ? intval($form_data['beer_per_page']) : 50,
            'paged' => isset($form_data['paged']) ? intval($form_data['paged']) : 1,
            'tax_query' => array(),
        );
        
        // 検索クエリ
        if (isset($form_data['beer_search']) && !empty($form_data['beer_search'])) {
            $args['s'] = sanitize_text_field($form_data['beer_search']);
        }
        
        // タクソノミーフィルターを追加
        if (isset($form_data['beer_contest']) && !empty($form_data['beer_contest'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_contest',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_contest']),
            );
        }
        
        if (isset($form_data['beer_year']) && !empty($form_data['beer_year'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_year',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_year']),
            );
        }
        
        if (isset($form_data['beer_country']) && !empty($form_data['beer_country'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_country',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_country']),
            );
        }
        
        if (isset($form_data['beer_state']) && !empty($form_data['beer_state'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_state',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_state']),
            );
        }
        
        if (isset($form_data['beer_city']) && !empty($form_data['beer_city'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_city',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_city']),
            );
        }
        
        if (isset($form_data['beer_category']) && !empty($form_data['beer_category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_category']),
            );
        }
        
        if (isset($form_data['beer_medal']) && !empty($form_data['beer_medal'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_medal',
                'field' => 'slug',
                'terms' => sanitize_text_field($form_data['beer_medal']),
            );
        }
        
        // タクソノミークエリの関係設定
        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            // 結果数と並べ替えオプションを表示
            include(RBA_PLUGIN_DIR . 'templates/results-header.php');
            
            // 設定に基づいてビューを選択
            $view_type = 'grid'; // デフォルト
            include(RBA_PLUGIN_DIR . 'templates/' . $view_type . '-view.php');
            
            // ページネーション
            include(RBA_PLUGIN_DIR . 'templates/pagination.php');
            
            wp_reset_postdata();
        } else {
            include(RBA_PLUGIN_DIR . 'templates/no-results.php');
        }
        
        $html = ob_get_clean();
        echo $html;
        wp_die();
    }
    
    // 国に基づいて州/県を取得するAJAX処理
    public function ajax_get_states_by_country() {
        check_ajax_referer('beer_filter_nonce', 'nonce');
        
        $country_slug = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        
        if (empty($country_slug)) {
            wp_send_json_error(__('国が指定されていません', 'regional-beer-awards'));
        }
        
        // 国のterm_idを取得
        $country = get_term_by('slug', $country_slug, 'beer_country');
        
        if (!$country) {
            wp_send_json_error(__('指定された国が見つかりません', 'regional-beer-awards'));
        }
        
        // この国に関連する州/県を検索
        $posts = get_posts(array(
            'post_type' => 'award_beer',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'beer_country',
                    'field' => 'term_id',
                    'terms' => $country->term_id,
                ),
            ),
        ));
        
        // 投稿から関連する州/県を収集
        $state_ids = array();
        foreach ($posts as $post) {
            $post_states = wp_get_object_terms($post->ID, 'beer_state');
            foreach ($post_states as $state) {
                $state_ids[$state->term_id] = $state;
            }
        }
        
        $states = array();
        foreach ($state_ids as $state) {
            $states[] = array(
                'term_id' => $state->term_id,
                'name' => $state->name,
                'slug' => $state->slug,
            );
        }
        
        // 名前でソート
        usort($states, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        wp_send_json($states);
    }
    
    // 州/県に基づいて都市を取得するAJAX処理
    public function ajax_get_cities_by_state() {
        check_ajax_referer('beer_filter_nonce', 'nonce');
        
        $state_slug = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
        
        if (empty($state_slug)) {
            wp_send_json_error(__('州/県が指定されていません', 'regional-beer-awards'));
        }
        
        // 州/県のterm_idを取得
        $state = get_term_by('slug', $state_slug, 'beer_state');
        
        if (!$state) {
            wp_send_json_error(__('指定された州/県が見つかりません', 'regional-beer-awards'));
        }
        
        // この州/県に関連する都市を検索
        $posts = get_posts(array(
            'post_type' => 'award_beer',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'beer_state',
                    'field' => 'term_id',
                    'terms' => $state->term_id,
                ),
            ),
        ));
        
        // 投稿から関連する都市を収集
        $city_ids = array();
        foreach ($posts as $post) {
            $post_cities = wp_get_object_terms($post->ID, 'beer_city');
            foreach ($post_cities as $city) {
                $city_ids[$city->term_id] = $city;
            }
        }
        
        $cities = array();
        foreach ($city_ids as $city) {
            $cities[] = array(
                'term_id' => $city->term_id,
                'name' => $city->name,
                'slug' => $city->slug,
            );
        }
        
        // 名前でソート
        usort($cities, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        wp_send_json($cities);
    }
}

/**
 * File: regional-beer-awards/includes/class-beer-awards-widget.php
 * Description: Custom widgets for displaying beer awards
 */
<?php
/**
 * ウィジェットを管理するクラス
 */
class Beer_Awards_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'beer_awards_widget',
            __('受賞ビール表示', 'regional-beer-awards'),
            array('description' => __('受賞ビールを表示するウィジェット', 'regional-beer-awards'))
        );
        
        // ウィジェットの登録
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    // ウィジェットの登録
    public function register_widgets() {
        register_widget('Beer_Awards_Widget');
    }
    
    // ウィジェットの表示内容
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        $contest = !empty($instance['contest']) ? $instance['contest'] : '';
        $year = !empty($instance['year']) ? $instance['year'] : '';
        $medal = !empty($instance['medal']) ? $instance['medal'] : '';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        // 受賞ビールの取得
        $query_args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => $limit,
            'tax_query' => array(),
        );
        
        // コンテストで絞り込み
        if (!empty($contest)) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'beer_contest',
                'field' => 'slug',
                'terms' => $contest,
            );
        }
        
        // 年で絞り込み
        if (!empty($year)) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'beer_year',
                'field' => 'slug',
                'terms' => $year,
            );
        }
        
        // メダルで絞り込み
        if (!empty($medal)) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'beer_medal',
                'field' => 'slug',
                'terms' => $medal,
            );
        }
        
        // タクソノミークエリの関係設定
        if (count($query_args['tax_query']) > 1) {
            $query_args['tax_query']['relation'] = 'AND';
        }
        
        $query = new WP_Query($query_args);
        
        if ($query->have_posts()) {
            echo '<ul class="beer-awards-widget-list">';
            
            while ($query->have_posts()) {
                $query->the_post();
                echo '<li class="beer-award-item">';
                
                // メダル
                $medals = get_the_terms(get_the_ID(), 'beer_medal');
                if ($medals && !is_wp_error($medals)) {
                    $medal_class = strtolower($medals[0]->name) . '-medal';
                    echo '<span class="medal-icon ' . $medal_class . '">' . $medals[0]->name . '</span>';
                }
                
                // ビール名
                echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                
                // 醸造所
                $brewery = get_post_meta(get_the_ID(), '_brewery', true);
                if ($brewery) {
                    echo '<span class="beer-brewery">' . $brewery . '</span>';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
            
            wp_reset_postdata();
        } else {
            echo '<p>' . __('該当する受賞ビールはありません。', 'regional-beer-awards') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    // ウィジェットのフォーム表示
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('受賞ビール', 'regional-beer-awards');
        $contest = !empty($instance['contest']) ? $instance['contest'] : '';
        $year = !empty($instance['year']) ? $instance['year'] : '';
        $medal = !empty($instance['medal']) ? $instance['medal'] : '';
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 5;
        
        // コンテスト一覧を取得
        $contests = get_terms(array(
            'taxonomy' => 'beer_contest',
            'hide_empty' => true,
        ));
        
        // 年の一覧を取得
        $years = get_terms(array(
            'taxonomy' => 'beer_year',
            'hide_empty' => true,
            'order' => 'DESC',
        ));
        
        // メダルの一覧を取得
        $medals = get_terms(array(
            'taxonomy' => 'beer_medal',
            'hide_empty' => true,
        ));
        
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('タイトル:', 'regional-beer-awards'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('contest'); ?>"><?php _e('コンテスト:', 'regional-beer-awards'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('contest'); ?>" name="<?php echo $this->get_field_name('contest'); ?>">
                <option value=""><?php _e('すべてのコンテスト', 'regional-beer-awards'); ?></option>
                <?php foreach ($contests as $contest_term) : ?>
                    <option value="<?php echo $contest_term->slug; ?>" <?php selected($contest, $contest_term->slug); ?>><?php echo $contest_term->name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('year'); ?>"><?php _e('年:', 'regional-beer-awards'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('year'); ?>" name="<?php echo $this->get_field_name('year'); ?>">
                <option value=""><?php _e('すべての年', 'regional-beer-awards'); ?></option>
                <?php foreach ($years as $year_term) : ?>
                    <option value="<?php echo $year_term->slug; ?>" <?php selected($year, $year_term->slug); ?>><?php echo $year_term->name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('medal'); ?>"><?php _e('メダル:', 'regional-beer-awards'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('medal'); ?>" name="<?php echo $this->get_field_name('medal'); ?>">
                <option value=""><?php _e('すべてのメダル', 'regional-beer-awards'); ?></option>
                <?php foreach ($medals as $medal_term) : ?>
                    <option value="<?php echo $medal_term->slug; ?>" <?php selected($medal, $medal_term->slug); ?>><?php echo $medal_term->name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('表示件数:', 'regional-beer-awards'); ?></label>
            <input class="tiny-text" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="50">
        </p>
        <?php
    }
    
    // ウィジェット設定の保存
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['contest'] = (!empty($new_instance['contest'])) ? sanitize_text_field($new_instance['contest']) : '';
        $instance['year'] = (!empty($new_instance['year'])) ? sanitize_text_field($new_instance['year']) : '';
        $instance['medal'] = (!empty($new_instance['medal'])) ? sanitize_text_field($new_instance['medal']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 5;
        
        return $instance;
    }
}

/**
 * File: regional-beer-awards/includes/class-beer-awards-rest-api.php
 * Description: Registers REST API endpoints
 */
<?php
/**
 * REST APIのエンドポイントを管理するクラス
 */
class Beer_Awards_REST_API {
    public function __construct() {
        // REST APIの初期化
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    // REST APIのルート登録
    public function register_rest_routes() {
        register_rest_route('beer-awards/v1', '/contests', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_contests'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('beer-awards/v1', '/awards', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_awards'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('beer-awards/v1', '/brewery/(?P<brewery>[a-zA-Z0-9-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_brewery_awards'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('beer-awards/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => '__return_true'
        ));
    }
    
    // コンテスト一覧を取得するエンドポイント
    public function get_contests($request) {
        $contests = get_terms(array(
            'taxonomy' => 'beer_contest',
            'hide_empty' => true
        ));
        
        $data = array();
        foreach ($contests as $contest) {
            // 年の一覧も取得
            $years = get_terms(array(
                'taxonomy' => 'beer_year',
                'hide_empty' => true,
                'meta_query' => array(
                    array(
                        'relation' => 'EXISTS',
                        array(
                            'key' => 'contest_id',
                            'value' => $contest->term_id,
                            'compare' => '='
                        )
                    )
                )
            ));
            
            $years_data = array();
            foreach ($years as $year) {
                $years_data[] = array(
                    'id' => $year->term_id,
                    'name' => $year->name,
                    'slug' => $year->slug,
                    'count' => $year->count
                );
            }
            
            $data[] = array(
                'id' => $contest->term_id,
                'name' => $contest->name,
                'slug' => $contest->slug,
                'count' => $contest->count,
                'years' => $years_data
            );
        }
        
        return new WP_REST_Response($data, 200);
    }
    
    // 受賞データを取得するエンドポイント
    public function get_awards($request) {
        $params = $request->get_params();
        
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => isset($params['per_page']) ? intval($params['per_page']) : 20,
            'paged' => isset($params['page']) ? intval($params['page']) : 1,
            'tax_query' => array()
        );
        
        // 絞り込み条件を追加
        if (!empty($params['contest'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_contest',
                'field' => 'slug',
                'terms' => $params['contest']
            );
        }
        
        if (!empty($params['year'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_year',
                'field' => 'slug',
                'terms' => $params['year']
            );
        }
        
        if (!empty($params['country'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_country',
                'field' => 'slug',
                'terms' => $params['country']
            );
        }
        
        if (!empty($params['state'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_state',
                'field' => 'slug',
                'terms' => $params['state']
            );
        }
        
        if (!empty($params['city'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_city',
                'field' => 'slug',
                'terms' => $params['city']
            );
        }
        
        if (!empty($params['category'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_category',
                'field' => 'slug',
                'terms' => $params['category']
            );
        }
        
        if (!empty($params['medal'])) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_medal',
                'field' => 'slug',
                'terms' => $params['medal']
            );
        }
        
        // タクソノミークエリの関係設定
        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
        
        $query = new WP_Query($args);
        $awards = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                // タクソノミーの取得
                $contest_terms = get_the_terms(get_the_ID(), 'beer_contest');
                $year_terms = get_the_terms(get_the_ID(), 'beer_year');
                $country_terms = get_the_terms(get_the_ID(), 'beer_country');
                $state_terms = get_the_terms(get_the_ID(), 'beer_state');
                $city_terms = get_the_terms(get_the_ID(), 'beer_city');
                $category_terms = get_the_terms(get_the_ID(), 'beer_category');
                $medal_terms = get_the_terms(get_the_ID(), 'beer_medal');
                
                $awards[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'brewery' => get_post_meta(get_the_ID(), '_brewery', true),
                    'place' => get_post_meta(get_the_ID(), '_place', true),
                    'contest' => $contest_terms ? $contest_terms[0]->name : '',
                    'year' => $year_terms ? $year_terms[0]->name : '',
                    'country' => $country_terms ? $country_terms[0]->name : '',
                    'state' => $state_terms ? $state_terms[0]->name : '',
                    'city' => $city_terms ? $city_terms[0]->name : '',
                    'category' => $category_terms ? $category_terms[0]->name : '',
                    'medal' => $medal_terms ? $medal_terms[0]->name : '',
                    'link' => get_permalink()
                );
            }
        }
        
        wp_reset_postdata();
        
        return new WP_REST_Response(array(
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'awards' => $awards
        ), 200);
    }
    
    // 醸造所の受賞履歴を取得するエンドポイント
    public function get_brewery_awards($request) {
        $brewery = urldecode($request['brewery']);
        
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_brewery',
                    'value' => $brewery,
                    'compare' => 'LIKE'
                )
            )
        );
        
        $query = new WP_Query($args);
        $awards = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                // タクソノミーの取得
                $contest_terms = get_the_terms(get_the_ID(), 'beer_contest');
                $year_terms = get_the_terms(get_the_ID(), 'beer_year');
                $category_terms = get_the_terms(get_the_ID(), 'beer_category');
                $medal_terms = get_the_terms(get_the_ID(), 'beer_medal');
                
                $awards[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'place' => get_post_meta(get_the_ID(), '_place', true),
                    'contest' => $contest_terms ? $contest_terms[0]->name : '',
                    'year' => $year_terms ? $year_terms[0]->name : '',
                    'category' => $category_terms ? $category_terms[0]->name : '',
                    'medal' => $medal_terms ? $medal_terms[0]->name : '',
                    'link' => get_permalink()
                );
            }
        }
        
        wp_reset_postdata();
        
        return new WP_REST_Response(array(
            'brewery' => $brewery,
            'total' => count($awards),
            'awards' => $awards
        ), 200);
    }
    
    // 統計データを取得するエンドポイント
    public function get_stats($request) {
        $params = $request->get_params();
        
        $contest = isset($params['contest']) ? $params['contest'] : '';
        $year = isset($params['year']) ? $params['year'] : '';
        $stat_type = isset($params['type']) ? $params['type'] : 'country';
        
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => -1,
            'tax_query' => array()
        );
        
        // コンテストで絞り込み
        if (!empty($contest)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_contest',
                'field' => 'slug',
                'terms' => $contest
            );
        }
        
        // 年で絞り込み
        if (!empty($year)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_year',
                'field' => 'slug',
                'terms' => $year
            );
        }
        
        $query = new WP_Query($args);
        
        $stats = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                switch ($stat_type) {
                    case 'country':
                        $terms = get_the_terms(get_the_ID(), 'beer_country');
                        if ($terms && !is_wp_error($terms)) {
                            $country = $terms[0]->name;
                            if (!isset($stats[$country])) {
                                $stats[$country] = 0;
                            }
                            $stats[$country]++;
                        }
                        break;
                        
                    case 'category':
                        $terms = get_the_terms(get_the_ID(), 'beer_category');
                        if ($terms && !is_wp_error($terms)) {
                            $category = $terms[0]->name;
                            if (!isset($stats[$category])) {
                                $stats[$category] = 0;
                            }
                            $stats[$category]++;
                        }
                        break;
                        
                    case 'medal':
                        $terms = get_the_terms(get_the_ID(), 'beer_medal');
                        if ($terms && !is_wp_error($terms)) {
                            $medal = $terms[0]->name;
                            if (!isset($stats[$medal])) {
                                $stats[$medal] = 0;
                            }
                            $stats[$medal]++;
                        }
                        break;
                        
                    case 'brewery':
                        $brewery = get_post_meta(get_the_ID(), '_brewery', true);
                        if (!empty($brewery)) {
                            if (!isset($stats[$brewery])) {
                                $stats[$brewery] = 0;
                            }
                            $stats[$brewery]++;
                        }
                        break;
                }
            }
            
            wp_reset_postdata();
            
            // 値の降順でソート
            arsort($stats);
        }
        
        // 結果を配列形式に変換
        $result = array();
        foreach ($stats as $key => $value) {
            $result[] = array(
                'name' => $key,
                'count' => $value
            );
        }
        
        return new WP_REST_Response(array(
            'total' => array_sum($stats),
            'stats' => $result
        ), 200);
    }
}

/**
 * File: regional-beer-awards/includes/importers/class-csv-importer.php
 * Description: Handles CSV file imports
 */
<?php
/**
 * CSV形式のインポートを処理するクラス
 */
class Beer_Awards_CSV_Importer {
    /**
     * CSVファイルからデータをインポート
     *
     * @param array $file $_FILES['csv_file']形式
     * @param string $contest_name コンテスト名
     * @param string $contest_year 開催年
     * @param string $encoding 文字コード
     * @return array 処理結果
     */
    public function import($file, $contest_name, $contest_year, $encoding = 'UTF-8') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'success' => false,
                'message' => sprintf(__('ファイルのアップロードに失敗しました。エラーコード: %d', 'regional-beer-awards'), $file['error'])
            );
        }
        
        // CSVファイルの文字コードをUTF-8に変換
        $csv_content = file_get_contents($file['tmp_name']);
        if ($encoding !== 'UTF-8' && mb_detect_encoding($csv_content, 'UTF-8, SJIS, EUC-JP, ASCII') !== 'UTF-8') {
            $csv_content = mb_convert_encoding($csv_content, 'UTF-8', $encoding);
            $temp_file = tempnam(sys_get_temp_dir(), 'csv_');
            file_put_contents($temp_file, $csv_content);
            $file_path = $temp_file;
        } else {
            $file_path = $file['tmp_name'];
        }
        
        $handle = fopen($file_path, 'r');
        if ($handle === false) {
            return array(
                'success' => false,
                'message' => __('ファイルを開けませんでした', 'regional-beer-awards')
            );
        }
        
        // ヘッダー行の読み込み
        $header = fgetcsv($handle, 0, ',');
        
        // 必要なヘッダーカラムの確認
        $required_columns = array('Award', 'Beer Name', 'Brewery', 'Category', 'City', 'State', 'Country', 'Place');
        $missing_columns = array_diff($required_columns, $header);
        
        if (!empty($missing_columns)) {
            fclose($handle);
            return array(
                'success' => false,
                'message' => sprintf(
                    __('CSVファイルに必要なカラムが不足しています: %s', 'regional-beer-awards'),
                    implode(', ', $missing_columns)
                )
            );
        }
        
        // ヘッダーのインデックスを取得
        $header_indices = array_flip($header);
        
        // コンテストと年をタクソノミーに登録
        $contest_term = term_exists($contest_name, 'beer_contest');
        if (!$contest_term) {
            $contest_term = wp_insert_term($contest_name, 'beer_contest');
        }
        
        $year_term = term_exists($contest_year, 'beer_year');
        if (!$year_term) {
            $year_term = wp_insert_term($contest_year, 'beer_year');
        }
        
        // 処理カウンター
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        // 各行を処理
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $processed++;
            
            try {
                // データの取得
                $award = isset($data[$header_indices['Award']]) ? $data[$header_indices['Award']] : '';
                $beer_name = isset($data[$header_indices['Beer Name']]) ? $data[$header_indices['Beer Name']] : '';
                $brewery = isset($data[$header_indices['Brewery']]) ? $data[$header_indices['Brewery']] : '';
                $category = isset($data[$header_indices['Category']]) ? $data[$header_indices['Category']] : '';
                $city = isset($data[$header_indices['City']]) ? $data[$header_indices['City']] : '';
                $state = isset($data[$header_indices['State']]) ? $data[$header_indices['State']] : '';
                $country = isset($data[$header_indices['Country']]) ? $data[$header_indices['Country']] : '';
                $place = isset($data[$header_indices['Place']]) ? $data[$header_indices['Place']] : '';
                
                // ビール名と醸造所は必須
                if (empty($beer_name) || empty($brewery)) {
                    $skipped++;
                    continue;
                }
                
                // 既存の投稿を検索
                $existing_posts = get_posts(array(
                    'post_type' => 'award_beer',
                    'post_status' => 'publish',
                    'title' => $beer_name,
                    'meta_query' => array(
                        array(
                            'key' => '_brewery',
                            'value' => $brewery,
                            'compare' => '='
                        )
                    ),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'beer_contest',
                            'field' => 'name',
                            'terms' => $contest_name
                        ),
                        array(
                            'taxonomy' => 'beer_year',
                            'field' => 'name',
                            'terms' => $contest_year
                        )
                    ),
                    'posts_per_page' => 1
                ));
                
                if (!empty($existing_posts)) {
                    // 既存の投稿を更新
                    $post_id = $existing_posts[0]->ID;
                    $updated++;
                } else {
                    // 新規投稿を作成
                    $post_id = wp_insert_post(array(
                        'post_title' => $beer_name,
                        'post_type' => 'award_beer',
                        'post_status' => 'publish'
                    ));
                    
                    if (is_wp_error($post_id)) {
                        $errors[] = sprintf(
                            __('行 %d: %s - %s', 'regional-beer-awards'),
                            $processed,
                            $beer_name,
                            $post_id->get_error_message()
                        );
                        $skipped++;
                        continue;
                    }
                    
                    $created++;
                }
                
                // メタデータを更新
                update_post_meta($post_id, '_brewery', sanitize_text_field($brewery));
                update_post_meta($post_id, '_place', intval($place));
                
                // タクソノミーを設定
                wp_set_object_terms($post_id, $contest_name, 'beer_contest');
                wp_set_object_terms($post_id, $contest_year, 'beer_year');
                
                if (!empty($award)) {
                    wp_set_object_terms($post_id, sanitize_text_field($award), 'beer_medal');
                }
                
                if (!empty($category)) {
                    wp_set_object_terms($post_id, sanitize_text_field($category), 'beer_category');
                }
                
                if (!empty($country)) {
                    wp_set_object_terms($post_id, sanitize_text_field($country), 'beer_country');
                }
                
                if (!empty($state)) {
                    wp_set_object_terms($post_id, sanitize_text_field($state), 'beer_state');
                }
                
                if (!empty($city)) {
                    wp_set_object_terms($post_id, sanitize_text_field($city), 'beer_city');
                }
            } catch (Exception $e) {
                $errors[] = sprintf(
                    __('行 %d: %s - %s', 'regional-beer-awards'),
                    $processed,
                    isset($beer_name) ? $beer_name : __('不明', 'regional-beer-awards'),
                    $e->getMessage()
                );
                $skipped++;
            }
        }
        
        fclose($handle);
        
        // 一時ファイルの削除
        if (isset($temp_file) && file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        return array(
            'success' => true,
            'message' => __('CSVインポート完了', 'regional-beer-awards'),
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }
}

/**
 * File: regional-beer-awards/includes/importers/class-json-importer.php
 * Description: Handles JSON file imports
 */
<?php
/**
 * JSON形式のインポートを処理するクラス
 */
class Beer_Awards_JSON_Importer {
    /**
     * JSONファイルからデータをインポート
     *
     * @param array $file $_FILES['json_file']形式
     * @return array 処理結果
     */
    public function import($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'success' => false,
                'message' => sprintf(__('ファイルのアップロードに失敗しました。エラーコード: %d', 'regional-beer-awards'), $file['error'])
            );
        }
        
        // JSONファイルを読み込む
        $json_content = file_get_contents($file['tmp_name']);
        $data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => sprintf(__('JSONの解析に失敗しました: %s', 'regional-beer-awards'), json_last_error_msg())
            );
        }
        
        // 必要なデータがあるか確認
        if (!isset($data['contest']) || !isset($data['year']) || !isset($data['awards']) || !is_array($data['awards'])) {
            return array(
                'success' => false,
                'message' => __('JSONの形式が正しくありません。contest, year, awardsが必要です。', 'regional-beer-awards')
            );
        }
        
        $contest_name = sanitize_text_field($data['contest']);
        $contest_year = sanitize_text_field($data['year']);
        $awards = $data['awards'];
        
        // コンテストと年をタクソノミーに登録
        $contest_term = term_exists($contest_name, 'beer_contest');
        if (!$contest_term) {
            $contest_term = wp_insert_term($contest_name, 'beer_contest');
        }
        
        $year_term = term_exists($contest_year, 'beer_year');
        if (!$year_term) {
            $year_term = wp_insert_term($contest_year, 'beer_year');
        }
        
        // 処理カウンター
        $processed = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = array();
        
        // 各受賞データを処理
        foreach ($awards as $award) {
            $processed++;
            
            try {
                // 必須フィールドの確認
                if (!isset($award['beer_name']) || !isset($award['brewery'])) {
                    $skipped++;
                    $errors[] = sprintf(__('項目 %d: ビール名または醸造所がありません。', 'regional-beer-awards'), $processed);
                    continue;
                }
                
                $beer_name = sanitize_text_field($award['beer_name']);
                $brewery = sanitize_text_field($award['brewery']);
                $medal = isset($award['medal']) ? sanitize_text_field($award['medal']) : '';
                $category = isset($award['category']) ? sanitize_text_field($award['category']) : '';
                $city = isset($award['city']) ? sanitize_text_field($award['city']) : '';
                $state = isset($award['state']) ? sanitize_text_field($award['state']) : '';
                $country = isset($award['country']) ? sanitize_text_field($award['country']) : '';
                $place = isset($award['place']) ? intval($award['place']) : '';
                
                // 既存の投稿を検索
                $existing_posts = get_posts(array(
                    'post_type' => 'award_beer',
                    'post_status' => 'publish',
                    'title' => $beer_name,
                    'meta_query' => array(
                        array(
                            'key' => '_brewery',
                            'value' => $brewery,
                            'compare' => '='
                        )
                    ),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'beer_contest',
                            'field' => 'name',
                            'terms' => $contest_name
                        ),
                        array(
                            'taxonomy' => 'beer_year',
                            'field' => 'name',
                            'terms' => $contest_year
                        )
                    ),
                    'posts_per_page' => 1
                ));
                
                if (!empty($existing_posts)) {
                    // 既存の投稿を更新
                    $post_id = $existing_posts[0]->ID;
                    $updated++;
                } else {
                    // 新規投稿を作成
                    $post_id = wp_insert_post(array(
                        'post_title' => $beer_name,
                        'post_type' => 'award_beer',
                        'post_status' => 'publish'
                    ));
                    
                    if (is_wp_error($post_id)) {
                        $errors[] = sprintf(
                            __('項目 %d: %s - %s', 'regional-beer-awards'),
                            $processed,
                            $beer_name,
                            $post_id->get_error_message()
                        );
                        $skipped++;
                        continue;
                    }
                    
                    $created++;
                }
                
                // メタデータを更新
                update_post_meta($post_id, '_brewery', $brewery);
                update_post_meta($post_id, '_place', $place);
                
                // タクソノミーを設定
                wp_set_object_terms($post_id, $contest_name, 'beer_contest');
                wp_set_object_terms($post_id, $contest_year, 'beer_year');
                
                if (!empty($medal)) {
                    wp_set_object_terms($post_id, $medal, 'beer_medal');
                }
                
                if (!empty($category)) {
                    wp_set_object_terms($post_id, $category, 'beer_category');
                }
                
                if (!empty($country)) {
                    wp_set_object_terms($post_id, $country, 'beer_country');
                }
                
                if (!empty($state)) {
                    wp_set_object_terms($post_id, $state, 'beer_state');
                }
                
                if (!empty($city)) {
                    wp_set_object_terms($post_id, $city, 'beer_city');
                }
            } catch (Exception $e) {
                $errors[] = sprintf(
                    __('項目 %d: %s - %s', 'regional-beer-awards'),
                    $processed,
                    isset($beer_name) ? $beer_name : __('不明', 'regional-beer-awards'),
                    $e->getMessage()
                );
                $skipped++;
            }
        }
        
        return array(
            'success' => true,
            'message' => __('JSONインポート完了', 'regional-beer-awards'),
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        );
    }
}

/**
 * File: regional-beer-awards/includes/importers/class-markdown-converter.php
 * Description: Converts markdown tables to JSON
 */
<?php
/**
 * マークダウンテーブルをJSONに変換するクラス
 */
class Beer_Awards_Markdown_Converter {
    /**
     * マークダウンテーブルをJSONに変換
     *
     * @param string $markdown マークダウンテーブル
     * @param string $contest_name コンテスト名
     * @param string $contest_year 開催年
     * @return array 変換結果
     */
    public function convert_to_json($markdown, $contest_name, $contest_year) {
        try {
            // 改行で分割して行ごとに処理
            $lines = explode("\n", trim($markdown));
            
            if (count($lines) < 3) {
                return array(
                    'success' => false,
                    'message' => __('マークダウンテーブルの形式が正しくありません。最低3行（ヘッダー、区切り、データ）が必要です。', 'regional-beer-awards')
                );
            }
            
            // ヘッダー行を取得し、カラム名を抽出
            $header_line = trim($lines[0]);
            $headers = array_map('trim', explode('|', $header_line));
            $headers = array_filter($headers, function($item) {
                return $item !== '';
            });
            
            // 区切り行をスキップ
            
            // データ行を処理
            $awards = array();
            for ($i = 2; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                
                // 空行はスキップ
                if (empty($line)) continue;
                
                $cells = array_map('trim', explode('|', $line));
                
                // 最初と最後の空セルを除外（テーブル両端の|による）
                $cells = array_filter($cells, function($item, $index) use ($cells) {
                    return $index !== 0 || $item !== '' && $index !== count($cells) - 1 || $item !== '';
                }, ARRAY_FILTER_USE_BOTH);
                
                $cells = array_values($cells); // インデックスをリセット
                
                if (count($cells) !== count($headers)) {
                    continue; // カラム数が一致しない行はスキップ
                }
                
                $award = array();
                $headers_array = array_values($headers); // インデックスをリセット
                
                for ($j = 0; $j < count($headers_array); $j++) {
                    $key = strtolower(str_replace(' ', '_', $headers_array[$j]));
                    
                    // キー名を標準化
                    if ($key === 'award') $key = 'medal';
                    
                    $award[$key] = $cells[$j];
                    
                    // placeは数値に変換
                    if ($key === 'place') {
                        $award[$key] = intval($cells[$j]);
                    }
                }
                
                $awards[] = $award;
            }
            
            // JSON構造を作成
            $json_data = array(
                'contest' => $contest_name,
                'year' => $contest_year,
                'awards' => $awards
            );
            
            return array(
                'success' => true,
                'message' => __('変換完了', 'regional-beer-awards'),
                'json' => $json_data
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}

/**
 * File: regional-beer-awards/includes/exporters/class-csv-exporter.php
 * Description: Exports data to CSV format
 */
<?php
/**
 * CSV形式でデータをエクスポートするクラス
 */
class Beer_Awards_CSV_Exporter {
    /**
     * 受賞データをCSV形式でエクスポート
     */
    public function export() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            wp_die(__('この機能を使用する権限がありません。', 'regional-beer-awards'));
        }
        
        // クエリパラメータを取得
        $contest = isset($_GET['contest']) ? sanitize_text_field($_GET['contest']) : '';
        $year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '';
        
        // カスタム投稿タイプからデータを取得
        $args = array(
            'post_type' => 'award_beer',
            'posts_per_page' => -1,
            'tax_query' => array(),
        );
        
        // コンテストで絞り込み
        if (!empty($contest)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_contest',
                'field' => 'slug',
                'terms' => $contest,
            );
        }
        
        // 年で絞り込み
        if (!empty($year)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'beer_year',
                'field' => 'slug',
                'terms' => $year,
            );
        }
        
        $query = new WP_Query($args);
        
        if (!$query->have_posts()) {
            wp_die(__('エクスポートするデータがありません', 'regional-beer-awards'));
        }
        
        // CSVのヘッダー行
        $csv_data = array(
            array('Award', 'Beer Name', 'Brewery', 'Category', 'City', 'State', 'Country', 'Place', 'Contest', 'Year')
        );
        
        // 各投稿を処理
        while ($query->have_posts()) {
            $query->the_post();
            
            $brewery = get_post_meta(get_the_ID(), '_brewery', true);
            $place = get_post_meta(get_the_ID(), '_place', true);
            
            $medals = get_the_terms(get_the_ID(), 'beer_medal');
            $medal = !empty($medals) ? $medals[0]->name : '';
            
            $categories = get_the_terms(get_the_ID(), 'beer_category');
            $category = !empty($categories) ? $categories[0]->name : '';
            
            $cities = get_the_terms(get_the_ID(), 'beer_city');
            $city = !empty($cities) ? $cities[0]->name : '';
            
            $states = get_the_terms(get_the_ID(), 'beer_state');
            $state = !empty($states) ? $states[0]->name : '';
            
            $countries = get_the_terms(get_the_ID(), 'beer_country');
            $country = !empty($countries) ? $countries[0]->name : '';
            
            $contests = get_the_terms(get_the_ID(), 'beer
