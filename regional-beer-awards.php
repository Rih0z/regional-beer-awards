<?php
/**
 * Plugin Name: Regional Beer Awards Display Multi-Contest
 * Description: 複数のビールコンテストの受賞データを地域別に表示するためのプラグイン
 * Version: 2.0
 * Author: Your Name
 */

// カスタム投稿タイプ「award_beer」の作成
function create_award_beer_post_type() {
    register_post_type('award_beer',
        array(
            'labels' => array(
                'name' => __('受賞ビール'),
                'singular_name' => __('受賞ビール')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-awards',
        )
    );
}
add_action('init', 'create_award_beer_post_type');

// カスタムタクソノミーの作成
function create_award_beer_taxonomies() {
    // コンテスト（大会）タクソノミー - 新規追加
    register_taxonomy(
        'beer_contest',
        'award_beer',
        array(
            'label' => __('コンテスト'),
            'hierarchical' => true,
            'show_admin_column' => true,
        )
    );
    
    // 開催年タクソノミー - 新規追加
    register_taxonomy(
        'beer_year',
        'award_beer',
        array(
            'label' => __('開催年'),
            'hierarchical' => true,
            'show_admin_column' => true,
        )
    );
    
    // カテゴリータクソノミー
    register_taxonomy(
        'beer_category',
        'award_beer',
        array(
            'label' => __('ビールカテゴリー'),
            'hierarchical' => true,
            'show_admin_column' => true,
        )
    );
    
    // 国タクソノミー
    register_taxonomy(
        'beer_country',
        'award_beer',
        array(
            'label' => __('国'),
            'hierarchical' => true,
            'show_admin_column' => true,
        )
    );
    
    // 都道府県タクソノミー
    register_taxonomy(
        'beer_state',
        'award_beer',
        array(
            'label' => __('都道府県/州'),
            'hierarchical' => true,
        )
    );
    
    // 都市タクソノミー
    register_taxonomy(
        'beer_city',
        'award_beer',
        array(
            'label' => __('都市'),
            'hierarchical' => true,
        )
    );
    
    // 受賞メダルタクソノミー
    register_taxonomy(
        'beer_medal',
        'award_beer',
        array(
            'label' => __('メダル'),
            'hierarchical' => true,
            'show_admin_column' => true,
        )
    );
}
add_action('init', 'create_award_beer_taxonomies');

// カスタムフィールドのセットアップ
function add_beer_meta_boxes() {
    add_meta_box(
        'beer_details',
        'ビール詳細情報',
        'beer_details_callback',
        'award_beer',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_beer_meta_boxes');

// メタボックスの内容を表示
function beer_details_callback($post) {
    wp_nonce_field(basename(__FILE__), 'beer_details_nonce');
    
    // カスタムフィールドから値を取得
    $brewery = get_post_meta($post->ID, '_brewery', true);
    $place = get_post_meta($post->ID, '_place', true);
    
    ?>
    <div class="beer-meta-fields">
        <p>
            <label for="brewery">醸造所:</label>
            <input type="text" id="brewery" name="brewery" value="<?php echo esc_attr($brewery); ?>" class="widefat">
        </p>
        <p>
            <label for="place">順位:</label>
            <input type="number" id="place" name="place" value="<?php echo esc_attr($place); ?>" min="1" max="3">
        </p>
    </div>
    <?php
}

// メタボックスのデータを保存
function save_beer_details($post_id) {
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
add_action('save_post', 'save_beer_details');

// フロントエンドでビール検索フォームを表示するショートコード
function beer_search_form_shortcode($atts) {
    // ショートコード属性の取得
    $attributes = shortcode_atts(
        array(
            'contest' => '',  // デフォルトのコンテストを指定可能
            'year' => '',     // デフォルトの年を指定可能
        ), 
        $atts
    );
    
    ob_start();
    
    // コンテストの一覧を取得
    $contests = get_terms(array(
        'taxonomy' => 'beer_contest',
        'hide_empty' => true,
    ));
    
    // 年の一覧を取得
    $years = get_terms(array(
        'taxonomy' => 'beer_year',
        'hide_empty' => true,
        'order' => 'DESC',  // 新しい年順に表示
    ));
    
    // 国の一覧を取得
    $countries = get_terms(array(
        'taxonomy' => 'beer_country',
        'hide_empty' => true,
    ));
    
    // カテゴリーの一覧を取得
    $categories = get_terms(array(
        'taxonomy' => 'beer_category',
        'hide_empty' => true,
    ));
    
    // メダルの一覧を取得
    $medals = get_terms(array(
        'taxonomy' => 'beer_medal',
        'hide_empty' => true,
    ));
    ?>
    
    <div class="beer-search-form">
        <h3>受賞ビール検索</h3>
        <form id="beer-filter-form" method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label for="beer-contest">コンテスト:</label>
                    <select id="beer-contest" name="beer_contest" class="form-control">
                        <option value="">すべてのコンテスト</option>
                        <?php foreach ($contests as $contest): ?>
                            <option value="<?php echo $contest->slug; ?>" <?php selected($attributes['contest'], $contest->slug); ?>><?php echo $contest->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="beer-year">開催年:</label>
                    <select id="beer-year" name="beer_year" class="form-control">
                        <option value="">すべての年</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year->slug; ?>" <?php selected($attributes['year'], $year->slug); ?>><?php echo $year->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="beer-medal">メダル:</label>
                    <select id="beer-medal" name="beer_medal" class="form-control">
                        <option value="">すべてのメダル</option>
                        <?php foreach ($medals as $medal): ?>
                            <option value="<?php echo $medal->slug; ?>"><?php echo $medal->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="beer-country">国:</label>
                    <select id="beer-country" name="beer_country" class="form-control">
                        <option value="">すべての国</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo $country->slug; ?>"><?php echo $country->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="beer-state">都道府県/州:</label>
                    <select id="beer-state" name="beer_state" class="form-control">
                        <option value="">すべての都道府県/州</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="beer-city">都市:</label>
                    <select id="beer-city" name="beer_city" class="form-control">
                        <option value="">すべての都市</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="beer-category">カテゴリー:</label>
                    <select id="beer-category" name="beer_category" class="form-control">
                        <option value="">すべてのカテゴリー</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->slug; ?>"><?php echo $category->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="beer-search">検索:</label>
                    <input type="text" id="beer-search" name="beer_search" class="form-control" placeholder="ビール名や醸造所を検索...">
                </div>
            </div>
            
            <div class="form-row">
                <button type="submit" class="submit-btn">検索</button>
                <button type="reset" class="reset-btn">リセット</button>
            </div>
        </form>
    </div>
    
    <div id="beer-results">
        <?php echo beer_results_display(); ?>
    </div>
    
    <?php
    return ob_get_clean();
}
add_shortcode('beer_search', 'beer_search_form_shortcode');

// ビール検索結果を表示する関数
function beer_results_display() {
    $args = array(
        'post_type' => 'award_beer',
        'posts_per_page' => 50,  // 一度に表示する件数を制限
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'tax_query' => array(),
    );
    
    // 検索クエリ
    if (isset($_GET['beer_search']) && !empty($_GET['beer_search'])) {
        $search_term = sanitize_text_field($_GET['beer_search']);
        $args['s'] = $search_term;
    }
    
    // コンテストのフィルター
    if (isset($_GET['beer_contest']) && !empty($_GET['beer_contest'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_contest',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_contest']),
        );
    }
    
    // 年のフィルター
    if (isset($_GET['beer_year']) && !empty($_GET['beer_year'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_year',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_year']),
        );
    }
    
    // 国のフィルター
    if (isset($_GET['beer_country']) && !empty($_GET['beer_country'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_country',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_country']),
        );
    }
    
    // 州/県のフィルター
    if (isset($_GET['beer_state']) && !empty($_GET['beer_state'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_state',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_state']),
        );
    }
    
    // 都市のフィルター
    if (isset($_GET['beer_city']) && !empty($_GET['beer_city'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_city',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_city']),
        );
    }
    
    // カテゴリーのフィルター
    if (isset($_GET['beer_category']) && !empty($_GET['beer_category'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_category',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_category']),
        );
    }
    
    // メダルのフィルター
    if (isset($_GET['beer_medal']) && !empty($_GET['beer_medal'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'beer_medal',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['beer_medal']),
        );
    }
    
    // 複数のタクソノミークエリの関係設定
    if (count($args['tax_query']) > 1) {
        $args['tax_query']['relation'] = 'AND';
    }
    
    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) :
        ?>
        <div class="beer-results-count">
            <p><?php echo $query->found_posts; ?> 件の受賞ビールが見つかりました</p>
        </div>
        
        <div class="beer-results-grid">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                // カスタムフィールドから値を取得
                $brewery = get_post_meta(get_the_ID(), '_brewery', true);
                $place = get_post_meta(get_the_ID(), '_place', true);
                
                // タクソノミーから値を取得
                $contests = get_the_terms(get_the_ID(), 'beer_contest');
                $contest = $contests ? $contests[0]->name : '';
                
                $years = get_the_terms(get_the_ID(), 'beer_year');
                $year = $years ? $years[0]->name : '';
                
                $countries = get_the_terms(get_the_ID(), 'beer_country');
                $country = $countries ? $countries[0]->name : '';
                
                $states = get_the_terms(get_the_ID(), 'beer_state');
                $state = $states ? $states[0]->name : '';
                
                $cities = get_the_terms(get_the_ID(), 'beer_city');
                $city = $cities ? $cities[0]->name : '';
                
                $categories = get_the_terms(get_the_ID(), 'beer_category');
                $category = $categories ? $categories[0]->name : '';
                
                $medals = get_the_terms(get_the_ID(), 'beer_medal');
                $medal = $medals ? $medals[0]->name : '';
                
                // メダルによって背景色のクラスを設定
                $medal_class = '';
                if (strtolower($medal) == 'gold' || $medal == '金' || $medal == 'Gold') {
                    $medal_class = 'gold-medal';
                } elseif (strtolower($medal) == 'silver' || $medal == '銀' || $medal == 'Silver') {
                    $medal_class = 'silver-medal';
                } elseif (strtolower($medal) == 'bronze' || $medal == '銅' || $medal == 'Bronze') {
                    $medal_class = 'bronze-medal';
                }
                ?>
                
                <div class="beer-card <?php echo $medal_class; ?>">
                    <div class="beer-medal"><?php echo $medal; ?></div>
                    <h3 class="beer-title"><?php the_title(); ?></h3>
                    <div class="beer-brewery"><?php echo esc_html($brewery); ?></div>
                    <div class="beer-category"><?php echo esc_html($category); ?></div>
                    <div class="beer-location">
                        <?php if ($city): ?>
                            <span class="beer-city"><?php echo esc_html($city); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($state): ?>
                            <span class="beer-state"><?php echo esc_html($state); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($country): ?>
                            <span class="beer-country"><?php echo esc_html($country); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="beer-contest">
                        <?php echo esc_html($contest); ?> <?php echo esc_html($year); ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php
        // ページネーション
        $big = 999999999;
        echo '<div class="beer-pagination">';
        echo paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo; 前へ',
            'next_text' => '次へ &raquo;',
        ));
        echo '</div>';
        ?>
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="no-results">
            <p>条件に一致する受賞ビールはありませんでした。検索条件を変更してお試しください。</p>
        </div>
    <?php endif;
    
    return ob_get_clean();
}

// AJAXで検索結果を更新する処理
function update_beer_results() {
    check_ajax_referer('beer_filter_nonce', 'nonce');
    echo beer_results_display();
    wp_die();
}
add_action('wp_ajax_update_beer_results', 'update_beer_results');
add_action('wp_ajax_nopriv_update_beer_results', 'update_beer_results');

// スクリプトとスタイルの読み込み
function enqueue_beer_scripts() {
    wp_enqueue_style('beer-awards-style', plugin_dir_url(__FILE__) . 'css/beer-awards.css');
    wp_enqueue_script('beer-awards-script', plugin_dir_url(__FILE__) . 'js/beer-awards.js', array('jquery'), '2.0', true);
    
    wp_localize_script('beer-awards-script', 'beer_awards_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('beer_filter_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_beer_scripts');

// AJAX用の関数：国に基づいて州/県を取得
function get_states_by_country() {
    check_ajax_referer('beer_filter_nonce', 'nonce');
    
    $country_slug = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
    
    if (empty($country_slug)) {
        wp_send_json_error('国が指定されていません');
    }
    
    // 国のterm_idを取得
    $country = get_term_by('slug', $country_slug, 'beer_country');
    
    if (!$country) {
        wp_send_json_error('指定された国が見つかりません');
    }
    
    // この国に関連する州/県を検索
    $states = array();
    
    // 国に紐づく投稿IDを取得
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
add_action('wp_ajax_get_states_by_country', 'get_states_by_country');
add_action('wp_ajax_nopriv_get_states_by_country', 'get_states_by_country');

// AJAX用の関数：州/県に基づいて都市を取得
function get_cities_by_state() {
    check_ajax_referer('beer_filter_nonce', 'nonce');
    
    $state_slug = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    
    if (empty($state_slug)) {
        wp_send_json_error('州/県が指定されていません');
    }
    
    // 州/県のterm_idを取得
    $state = get_term_by('slug', $state_slug, 'beer_state');
    
    if (!$state) {
        wp_send_json_error('指定された州/県が見つかりません');
    }
    
    // この州/県に関連する都市を検索
    $cities = array();
    
    // 州/県に紐づく投稿IDを取得
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
add_action('wp_ajax_get_cities_by_state', 'get_cities_by_state');
add_action('wp_ajax_nopriv_get_cities_by_state', 'get_cities_by_state');

// 管理メニューに「ビールデータインポート」を追加
function add_beer_import_menu() {
    add_submenu_page(
        'edit.php?post_type=award_beer',
        'ビールデータインポート',
        'データインポート',
        'manage_options',
        'beer-data-import',
        'beer_import_page'
    );
}
add_action('admin_menu', 'add_beer_import_menu');

// インポートページの内容
function beer_import_page() {
    ?>
    <div class="wrap">
        <h1>ビール受賞データのインポート</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=beer-data-import&tab=csv" class="nav-tab <?php echo !isset($_GET['tab']) || $_GET['tab'] == 'csv' ? 'nav-tab-active' : ''; ?>">CSVインポート</a>
            <a href="?page=beer-data-import&tab=json" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'json' ? 'nav-tab-active' : ''; ?>">JSONインポート</a>
            <a href="?page=beer-data-import&tab=markdown" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'markdown' ? 'nav-tab-active' : ''; ?>">マークダウン変換</a>
        </h2>
        
        <div class="tab-content">
            <?php
            $tab = isset($_GET['tab']) ? $_GET['tab'] : 'csv';
            
            switch ($tab) {
                case 'json':
                    display_json_import_form();
                    break;
                case 'markdown':
                    display_markdown_converter();
                    break;
                default: // csv
                    display_csv_import_form();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

// CSVインポートフォームの表示
function display_csv_import_form() {
    ?>
    <div class="beer-import-section">
        <h3>CSVファイルからビールの受賞データをインポート</h3>
        <p>CSVファイルのフォーマット：Award, Beer Name, Brewery, Category, City, State, Country, Place</p>
        
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="contest_name">コンテスト名</label></th>
                    <td>
                        <input type="text" name="contest_name" id="contest_name" class="regular-text" required>
                        <p class="description">例：World Beer Cup, GABF, IBA など</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contest_year">開催年</label></th>
                    <td>
                        <input type="number" name="contest_year" id="contest_year" class="small-text" min="1900" max="2100" value="<?php echo date('Y'); ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="csv_file">CSVファイル</label></th>
                    <td><input type="file" name="csv_file" id="csv_file" accept=".csv" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="csv_encoding">文字コード</label></th>
                    <td>
                        <select name="csv_encoding" id="csv_encoding">
                            <option value="UTF-8">UTF-8</option>
                            <option value="SJIS">Shift-JIS</option>
                            <option value="EUC-JP">EUC-JP</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <?php wp_nonce_field('beer_csv_import_action', 'beer_import_nonce'); ?>
            <p class="submit">
                <input type="submit" name="csv_import_submit" class="button button-primary" value="CSVをインポート">
            </p>
        </form>
    </div>
    <?php
    
    // インポート処理
    if (isset($_POST['csv_import_submit']) && isset($_FILES['csv_file'])) {
        if (!wp_verify_nonce($_POST['beer_import_nonce'], 'beer_csv_import_action')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        $file = $_FILES['csv_file'];
        $contest_name = sanitize_text_field($_POST['contest_name']);
        $contest_year = intval($_POST['contest_year']);
        $encoding = sanitize_text_field($_POST['csv_encoding']);
        
        // ファイルのバリデーション
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error"><p>ファイルのアップロードに失敗しました。エラーコード: ' . $file['error'] . '</p></div>';
            return;
        }
        
        // インポート処理を実行
        $result = import_beer_awards_from_csv($file['tmp_name'], $contest_name, $contest_year, $encoding);
        
        if ($result['success']) {
            echo '<div class="updated"><p>' . $result['message'] . '<br>処理 ' . $result['processed'] . ' 件, 作成 ' . $result['created'] . ' 件, 更新 ' . $result['updated'] . ' 件, スキップ ' . $result['skipped'] . ' 件</p></div>';
            
            if (!empty($result['errors'])) {
                echo '<div class="error"><p>以下のエラーが発生しました：</p><ul>';
                foreach ($result['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
            }
        } else {
            echo '<div class="error"><p>' . $result['message'] . '</p></div>';
        }
    }
}

// JSONインポートフォームの表示
function display_json_import_form() {
    ?>
    <div class="beer-import-section">
        <h3>JSONファイルからビールの受賞データをインポート</h3>
        <p>JSONファイルの形式については、サンプルダウンロードをご参照ください。</p>
        
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="json_file">JSONファイル</label></th>
                    <td><input type="file" name="json_file" id="json_file" accept=".json" required></td>
                </tr>
            </table>
            
            <?php wp_nonce_field('beer_json_import_action', 'beer_json_import_nonce'); ?>
            <p class="submit">
                <input type="submit" name="json_import_submit" class="button button-primary" value="JSONをインポート">
            </p>
        </form>
        
        <div class="beer-sample-json">
            <h4>JSONサンプル形式</h4>
            <pre>
{
  "contest": "World Beer Cup",
  "year": "2025",
  "awards": [
    {
      "medal": "Gold",
      "beer_name": "Clubhaus Lager",
      "brewery": "Von Ebert Brewing",
      "category": "American Light Lager",
      "city": "Portland",
      "state": "OR",
      "country": "USA",
      "place": 1
    },
    ...
  ]
}
            </pre>
            <a href="#" class="button" id="download-sample-json">サンプルJSONをダウンロード</a>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // サンプルJSONをダウンロード
        $('#download-sample-json').on('click', function(e) {
            e.preventDefault();
            
            var sampleJson = {
                "contest": "World Beer Cup",
                "year": "2025",
                "awards": [
                    {
                        "medal": "Gold",
                        "beer_name": "Clubhaus Lager",
                        "brewery": "Von Ebert Brewing",
                        "category": "American Light Lager",
                        "city": "Portland",
                        "state": "OR",
                        "country": "USA",
                        "place": 1
                    },
                    {
                        "medal": "Silver",
                        "beer_name": "Old Fortwaukee",
                        "brewery": "Coopersmith's Pub & Brewing",
                        "category": "American Light Lager",
                        "city": "Fort Collins",
                        "state": "CO",
                        "country": "USA",
                        "place": 2
                    }
                ]
            };
            
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(sampleJson, null, 2));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "sample_beer_awards.json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        });
    });
    </script>
    <?php
    
    // インポート処理
    if (isset($_POST['json_import_submit']) && isset($_FILES['json_file'])) {
        if (!wp_verify_nonce($_POST['beer_json_import_nonce'], 'beer_json_import_action')) {
            wp_die('セキュリティチェックに失敗しました。');
        }
        
        $file = $_FILES['json_file'];
        
        // ファイルのバリデーション
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error"><p>ファイルのアップロードに失敗しました。エラーコード: ' . $file['error'] . '</p></div>';
            return;
        }
        
        // JSONファイルの処理
        $result = import_beer_awards_from_json($file['tmp_name']);
        
        if ($result['success']) {
            echo '<div class="updated"><p>' . $result['message'] . '<br>処理 ' . $result['processed'] . ' 件, 作成 ' . $result['created'] . ' 件, 更新 ' . $result['updated'] . ' 件, スキップ ' . $result['skipped'] . ' 件</p></div>';
            
            if (!empty($result['errors'])) {
                echo '<div class="error"><p>以下のエラーが発生しました：</p><ul>';
                foreach ($result['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
            }
        } else {
            echo '<div class="error"><p>' . $result['message'] . '</p></div>';
        }
    }
}

// マークダウン変換ツールの表示
function display_markdown_converter() {
    ?>
    <div class="beer-import-section">
        <h3>マークダウンテーブルをJSONに変換</h3>
        <p>マークダウン形式のテーブルをJSONに変換します。</p>
        
        <div class="markdown-converter">
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="contest_name_md">コンテスト名</label>
                    <input type="text" id="contest_name_md" class="regular-text" value="World Beer Cup">
                </div>
                <div class="form-group">
                    <label for="contest_year_md">開催年</label>
                    <input type="number" id="contest_year_md" class="small-text" min="1900" max="2100" value="2025">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="markdown_input">マークダウンテーブルを入力</label>
                    <textarea id="markdown_input" rows="10" class="large-text code" placeholder="| Award | Beer Name | Brewery | Category | City | State | Country | Place |
|-------|-----------|---------|----------|------|-------|---------|-------|
| Gold | Clubhaus Lager | Von Ebert Brewing | American Light Lager | Portland | OR | USA | 1 |"></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <button type="button" id="convert_to_json" class="button button-primary">JSONに変換</button>
            </div>
            
            <div class="form-row">
                <div class="form-group full-width">
                    <label for="json_output">JSON出力</label>
                    <textarea id="json_output" rows="10" class="large-text code" readonly></textarea>
                </div>
            </div>
            
            <div class="form-row">
                <button type="button" id="download_json" class="button" disabled>JSONをダウンロード</button>
                <button type="button" id="copy_json" class="button" disabled>JSONをコピー</button>
            </div>
        </div>
    </div>
    
    <style>
    .form-row {
        margin-bottom: 15px;
    }
    .form-group {
        display: inline-block;
        margin-right: 15px;
        vertical-align: top;
    }
    .form-group.full-width {
        display: block;
        width: 100%;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .markdown-converter textarea {
        width: 100%;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // マークダウンをJSONに変換
        $('#convert_to_json').on('click', function() {
            var markdown = $('#markdown_input').val();
            var contestName = $('#contest_name_md').val();
            var contestYear = $('#contest_year_md').val();
            
            if (!markdown || !contestName || !contestYear) {
                alert('全ての項目を入力してください。');
                return;
            }
            
            try {
                var jsonData = convertMarkdownToJson(markdown, contestName, contestYear);
                $('#json_output').val(JSON.stringify(jsonData, null, 2));
                $('#download_json, #copy_json').prop('disabled', false);
            } catch (e) {
                alert('変換エラー: ' + e.message);
            }
        });
        
        // JSONをダウンロード
        $('#download_json').on('click', function() {
            var jsonStr = $('#json_output').val();
            if (!jsonStr) return;
            
            var contestName = $('#contest_name_md').val();
            var contestYear = $('#contest_year_md').val();
            var filename = contestName.replace(/\s+/g, '_') + '_' + contestYear + '.json';
            
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(jsonStr);
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", filename);
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        });
        
        // JSONをコピー
        $('#copy_json').on('click', function() {
            var jsonStr = $('#json_output').val();
            if (!jsonStr) return;
            
            $('#json_output').select();
            document.execCommand('copy');
            
            alert('JSONをクリップボードにコピーしました。');
        });
        
        // マークダウンをJSONに変換する関数
        function convertMarkdownToJson(markdown, contestName, contestYear) {
            // 改行で分割して行ごとに処理
            var lines = markdown.trim().split('\n');
            
            if (lines.length < 3) {
                throw new Error('マークダウンテーブルの形式が正しくありません。最低3行（ヘッダー、区切り、データ）が必要です。');
            }
            
            // ヘッダー行を取得し、カラム名を抽出
            var headerLine = lines[0].trim();
            var headers = headerLine.split('|').map(function(item) {
                return item.trim();
            }).filter(function(item) {
                return item !== '';
            });
            
            // 区切り行をスキップ
            
            // データ行を処理
            var awards = [];
            for (var i = 2; i < lines.length; i++) {
                var line = lines[i].trim();
                
                // 空行はスキップ
                if (!line) continue;
                
                var cells = line.split('|').map(function(item) {
                    return item.trim();
                }).filter(function(item, index) {
                    // 最初と最後の空セルを除外（テーブル両端の|による）
                    return index > 0 && index <= headers.length;
                });
                
                if (cells.length !== headers.length) {
                    console.warn('行 ' + (i + 1) + ': カラム数が一致しません。スキップします。', cells);
                    continue;
                }
                
                var award = {};
                for (var j = 0; j < headers.length; j++) {
                    var key = headers[j].toLowerCase().replace(/\s+/g, '_');
                    
                    // キー名を標準化
                    if (key === 'award') key = 'medal';
                    
                    award[key] = cells[j];
                    
                    // placeは数値に変換
                    if (key === 'place') {
                        award[key] = parseInt(cells[j], 10) || cells[j];
                    }
                }
                
                awards.push(award);
            }
            
            return {
                contest: contestName,
                year: contestYear,
                awards: awards
            };
        }
    });
    </script>
    <?php
}

// JSONファイルからビール受賞データをインポートする関数
function import_beer_awards_from_json($file_path) {
    if (!file_exists($file_path)) {
        return array(
            'success' => false,
            'message' => 'ファイルが存在しません: ' . $file_path
        );
    }
    
    // JSONファイルを読み込む
    $json_content = file_get_contents($file_path);
    $data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array(
            'success' => false,
            'message' => 'JSONの解析に失敗しました: ' . json_last_error_msg()
        );
    }
    
    // 必要なデータがあるか確認
    if (!isset($data['contest']) || !isset($data['year']) || !isset($data['awards']) || !is_array($data['awards'])) {
        return array(
            'success' => false,
            'message' => 'JSONの形式が正しくありません。contest, year, awardsが必要です。'
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
                $errors[] = '行 ' . $processed . ': ビール名または醸造所がありません。';
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
                    $errors[] = '行 ' . $processed . ': ' . $beer_name . ' - ' . $post_id->get_error_message();
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
            $errors[] = '行 ' . $processed . ': ' . (isset($beer_name) ? $beer_name : '不明') . ' - ' . $e->getMessage();
            $skipped++;
        }
    }
    
    return array(
        'success' => true,
        'message' => 'JSONインポート完了',
        'processed' => $processed,
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors
    );
}

// CSVファイルからビール受賞データをインポートする関数
function import_beer_awards_from_csv($file_path, $contest_name, $contest_year, $encoding = 'UTF-8') {
    if (!file_exists($file_path)) {
        return array(
            'success' => false,
            'message' => 'ファイルが存在しません: ' . $file_path
        );
    }
    
    // CSVファイルの文字コードをUTF-8に変換
    $csv_content = file_get_contents($file_path);
    if ($encoding !== 'UTF-8' && mb_detect_encoding($csv_content, 'UTF-8, SJIS, EUC-JP, ASCII') !== 'UTF-8') {
        $csv_content = mb_convert_encoding($csv_content, 'UTF-8', $encoding);
        $temp_file = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($temp_file, $csv_content);
        $file_path = $temp_file;
    }
    
    $handle = fopen($file_path, 'r');
    if ($handle === false) {
        return array(
            'success' => false,
            'message' => 'ファイルを開けませんでした: ' . $file_path
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
            'message' => 'CSVファイルに必要なカラムが不足しています: ' . implode(', ', $missing_columns)
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
                    $errors[] = '行 ' . $processed . ': ' . $beer_name . ' - ' . $post_id->get_error_message();
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
            $errors[] = '行 ' . $processed . ': ' . (isset($beer_name) ? $beer_name : '不明') . ' - ' . $e->getMessage();
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
        'message' => 'CSVインポート完了',
        'processed' => $processed,
        'created' => $created,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors
    );
}
