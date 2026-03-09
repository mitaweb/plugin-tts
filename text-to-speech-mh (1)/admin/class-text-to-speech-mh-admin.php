<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://dominhhai.com/
 * @since      1.0.0
 *
 * @package    Text_To_Speech_Mh
 * @subpackage Text_To_Speech_Mh/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Text_To_Speech_Mh
 * @subpackage Text_To_Speech_Mh/admin
 * @author     Minh Hai <minhhai27121994@gmail.com>
 */
include_once "vendor/phpmp3.php";
function doi_thoi_gian_rss_format($date) {
    $datetime = date_create_from_format('D, d M Y H:i:s O', $date);
    return $datetime ? $datetime->format('D, d M Y H:i:s'): ''; 
}
add_action('wp_ajax_capnhatlai_podcast', 'capnhatlai_podcast');
add_action('wp_ajax_nopriv_capnhatlai_podcast', 'capnhatlai_podcast');
function capnhatlai_podcast(){
    $tts_google_option    = get_option('tts_google_option');
    $podcast_name         = $tts_google_option['podcast_name'];
    $podcast_author_name  = $tts_google_option['podcast_author_name'];
    $podcast_author_email = $tts_google_option['podcast_author_email'];
    $podcast_copyright    = $tts_google_option['podcast_copyright'];
    $podcast_type         = $tts_google_option['podcast_type'];
    $podcast_description  = $tts_google_option['podcast_description'];
    $podcast_cover        = $tts_google_option['podcast_cover'];
    $podcast_num          = $tts_google_option['podcast_num'];
    $lang                 = $tts_google_option['lang'];
    $args = array(
    'posts_per_page' => $podcast_num,
    'post_type'      => array('post', 'product','page'), 
    'post_status'    => 'publish',
    'meta_query'     => array(
        array(
            'key'      => 'tts_audio_', 
            'compare'  => 'EXISTS' 
        )
    )
);

    $query = new WP_Query($args);

    $podcast_item = '';
    if ($query->have_posts()):
        require_once ABSPATH . 'wp-admin/includes/media.php';

        while ($query->have_posts()): $query->the_post();
            $tag_arr = get_the_tags();
            $item_tags = '';
            if (!empty($tag_arr)) {
                foreach($tag_arr as $tag) {
                    $item_tags .= $tag->name . ', ';
                }
                $item_tags = rtrim($item_tags, ', ');
            }

            $id = get_the_ID();

            $item_title = esc_html(get_the_title());
            if (get_the_excerpt() != '') {
                $item_brief = wp_strip_all_tags(get_the_excerpt());
            }else{
                $item_brief = wp_strip_all_tags(wp_trim_words(get_the_content(), 100, ''));
				
            }
            $item_link = get_the_permalink();
            $item_shortlink = esc_url(wp_get_shortlink());
            $item_mp3_link = home_url('/wp-content/uploads/tts_uploads/audio_') . $id . '.mp3';
            $item_mp3_file = ABSPATH . 'wp-content/uploads/tts_uploads/audio_' . $id . '.mp3';

            $item_image = get_the_post_thumbnail_url($id);

            $keyword = get_post_meta($id, 'crawl_keyword', true);

            $text_link = $item_link;
            if ($keyword != '') {
                $text_link = $keyword;
            }

            $view_detail = '. View detail <a href="' . $item_link . '">' . $text_link . '</a>';
            if ($lang == 'vi-VN') {
                $view_detail = '. Xem chi tiết <a href="' . $item_link . '">' . $text_link . '</a>';
            }


            //rss item podcast all
            if (file_exists($item_mp3_file)) {
                $item_mp3_metadata = wp_read_audio_metadata($item_mp3_file);
				$ngay_viet_bai = get_the_date('D, d M Y H:i:s O');
				$ngay_sua_bai = get_the_modified_date('D, d M Y H:i:s O');
				if ($ngay_viet_bai !== $ngay_sua_bai) {
					$ngay_viet_bai = $ngay_sua_bai;
				}
				$pub_date_rss = doi_thoi_gian_rss_format($ngay_viet_bai); 


                $podcast_item .= '<item>
                       <title><![CDATA[' . $item_title . ']]></title>
                       <itunes:title><![CDATA[' . $item_title . ']]></itunes:title>
                       <itunes:summary><![CDATA[' . $item_brief . $view_detail . ']]></itunes:summary>
                       <description><![CDATA[' . $item_brief . $view_detail . ']]></description>
                       <googleplay:description><![CDATA[' . $item_brief . $view_detail . ']]></googleplay:description>
                       <link>'. $item_link . '</link>
                       <enclosure url="' . $item_mp3_link . '" length="' . $item_mp3_metadata['filesize'] . '" type="audio/mp3" />
                       <guid isPermaLink="false">' . $item_shortlink . '</guid>
                       <itunes:image href="' . $item_image . '" />
                       <itunes:author>' . $podcast_author_name . '</itunes:author>
                       <googleplay:author>' . $podcast_author_name . '</googleplay:author>
                       <itunes:duration>' . $item_mp3_metadata['length_formatted'] . '</itunes:duration>
                       <itunes:episodeType>full</itunes:episodeType>
                       <itunes:explicit>no</itunes:explicit>
                      <pubDate>' . $ngay_sua_bai . ' GMT </pubDate>
                   </item>';
            }
        endwhile;
    endif;

    $rss_podcast = '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0"
                xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
                xmlns:content="http://purl.org/rss/1.0/modules/content/"
                xmlns:googleplay="http://www.google.com/schemas/play-podcasts/1.0"
                xmlns:atom="http://www.w3.org/2005/Atom">
                <channel>
                    <title>' . $podcast_name . '</title>
                    <link>' . home_url('/') . '</link>
                    <atom:link href="' . home_url('/podcast.xml') . '" rel="self" type="application/rss+xml" />
                    <description><![CDATA[' . $podcast_description . ']]></description>
                    <generator>' . $podcast_copyright . ' v1.0</generator>
                    <lastBuildDate>' . date(DATE_RSS, time()) . '</lastBuildDate>
                    <language>' . $lang . '</language>
                    <copyright>' . $podcast_copyright . '</copyright>
                    <itunes:image href="' . $podcast_cover . '" />
                    <image>
                        <url>' . $podcast_cover . '</url>
                        <title>' . $podcast_name . '</title>
                        <link>' . home_url('/') . '</link>
                    </image>
                    <googleplay:image href="' . $podcast_cover . '"/>
                    <itunes:summary><![CDATA[' . $podcast_description . ']]></itunes:summary>
                    <googleplay:description><![CDATA[' . $podcast_description . ']]></googleplay:description>
                    <itunes:author>' . $podcast_author_name . '</itunes:author>
                    <itunes:owner>
                        <itunes:name>' . $podcast_author_name . '</itunes:name>
                        <itunes:email>' . $podcast_author_email . '</itunes:email>
                    </itunes:owner>
                    <googleplay:owner>' . $podcast_author_email . '</googleplay:owner>
                    <googleplay:author>' . $podcast_author_name . '</googleplay:author>
                    <itunes:explicit>no</itunes:explicit>
                    <googleplay:explicit>no</googleplay:explicit>
                    <itunes:type>episodic</itunes:type>';

                    $podcast_type_arr = explode('|', $podcast_type);
                    if (isset($podcast_type_arr[1]) && $podcast_type_arr[1] != '') {
                        $rss_podcast .= '<itunes:category text="' . htmlspecialchars($podcast_type_arr[0]) . '">
                                            <itunes:category text="' . htmlspecialchars($podcast_type_arr[1]) . '"/>
                                        </itunes:category>
                                        <googleplay:category text="' . htmlspecialchars($podcast_type_arr[0]) . '">
                                            <googleplay:category text="' . htmlspecialchars($podcast_type_arr[1]) . '"/>
                                        </googleplay:category>';
                    }else{
                        $rss_podcast .= '<itunes:category text="' . htmlspecialchars($podcast_type) . '" />
                                        <googleplay:category text="' . htmlspecialchars($podcast_type) . '"/>';
                    }

    $rss_podcast .= $podcast_item;
    $rss_podcast .= '</channel>
            </rss>';

    $podcast_xml = ABSPATH . 'podcast.xml';
    if(!file_exists($podcast_xml)){
        $file_podcast = fopen($podcast_xml,'w') or die("can't open file");
        fclose($file_podcast);
    }
    if(!$file_podcast = fopen($podcast_xml,'w')){
        echo "can't open file";
        exit();
    }
    if (fwrite($file_podcast, $rss_podcast) === false) {
        echo "Can't write to file";
        exit();
    }
    fclose($file_podcast);

    wp_reset_postdata();
    die();
}
class Text_To_Speech_Mh_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	private $settings;
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version; 

	private $loader;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		$this->loader = new Text_To_Speech_Mh_Loader();
		$settings = get_option('tts_settings', false);
		if($settings)
			$this->settings = json_decode($settings, true);
		else{
			$settings = add_option('tts_settings', '{"active":"true","autoplay":"false","zalo_tokens":"","viettel_tokens":"","vbee_app_id":"","vbee_app_secret":""}');
		}

		add_action('admin_menu', [$this, 'add_admin_pages']);
		add_action('wp_ajax_tts_generate_file', [$this, 'tts_generate_file']);
		add_action('wp_ajax_tts_get_post_files', [$this, 'tts_get_post_files']);
		add_action('wp_ajax_tts_remove_post_files', [$this, 'tts_remove_post_files']);
		add_action('wp_ajax_tts_remove_all', [$this, 'tts_remove_all']);
		add_action('wp_ajax_tts_options', [$this, 'tts_options']);
	}
	
	public function add_admin_pages()
	{
			add_menu_page(
		        __( 'Chuyển thành giọng nói', 'nguyenlan' ),
		        'Chuyển thành giọng nói',
		        'manage_options',
		        'text-to-speed',
		        [$this, 'admin_template'],
		        'dashicons-controls-volumeon',
		        110
		    );
		add_submenu_page(
        'text-to-speed', 
        __( 'Tạo podcast', 'nguyenlan' ),
        'Tạo podcast',
        'manage_options',
        'create-podcast',
        [$this, 'cai_dat_podcast']
    );
	}
	
public function cai_dat_podcast() {
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tts_google_option'])) {
    $tts_google_option = $_POST['tts_google_option'];
    $filtered_tts_google_option = array_map('sanitize_text_field', $tts_google_option);
    update_option('tts_google_option', $filtered_tts_google_option);
    echo '<div class="alert alert-success" role="alert">Cấu hình Podcasts đã được lưu thành công!</div>';
}	
    $tts_google_option = get_option('tts_google_option');
    $podcast_name = isset($tts_google_option['podcast_name']) ? $tts_google_option['podcast_name'] : '';
    $podcast_author_name = isset($tts_google_option['podcast_author_name']) ? $tts_google_option['podcast_author_name'] : '';
    $podcast_author_email = isset($tts_google_option['podcast_author_email']) ? $tts_google_option['podcast_author_email'] : '';
    $podcast_copyright = isset($tts_google_option['podcast_copyright']) ? $tts_google_option['podcast_copyright'] : '';
    $podcast_type = isset($tts_google_option['podcast_type']) ? $tts_google_option['podcast_type'] : '';
    $podcast_description = isset($tts_google_option['podcast_description']) ? $tts_google_option['podcast_description'] : '';
    $podcast_cover = isset($tts_google_option['podcast_cover']) ? $tts_google_option['podcast_cover'] : '';
    $podcast_num = isset($tts_google_option['podcast_num']) ? $tts_google_option['podcast_num'] : '';
	$podcast_lang = isset($tts_google_option['lang']) ? $tts_google_option['lang'] : '';
	
	
	
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
<h1 class="header-title mt-3">Podcasts</h1>
<form method="post" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
<div class="row">
                <div class="col-3">
                    <label for="podcast_name" class="form-label">Podcasts name</label>
                    <input name="tts_google_option[podcast_name]" id="podcast_name" class="field-hide form-control" value="<?php echo esc_attr($podcast_name); ?>" type="text">
                </div>
                <div class="col-3">
                    <label for="podcast_author_name" class="form-label">Author name</label>
                    <input name="tts_google_option[podcast_author_name]" id="podcast_author_name" class="field-hide form-control" value="<?php echo esc_attr($podcast_author_name); ?>" type="text">
                </div>
                <div class="col-3">
                    <label for="podcast_author_email" class="form-label">Contact Email</label>
                    <span class="badge rounded-pill bg-warning" data-bs-toggle="modal" data-bs-target="#modal_podcast_author_email">?</span>
                    <input name="tts_google_option[podcast_author_email]" id="podcast_author_email" class="field-hide form-control" value="<?php echo esc_attr($podcast_author_email); ?>" type="text">
                </div>
                <div class="col-3">
                    <label for="podcast_copyright" class="form-label">Copyright</label>
                    <input name="tts_google_option[podcast_copyright]" id="podcast_copyright" class="field-hide form-control" value="<?php echo esc_attr($podcast_copyright); ?>" type="text">
                </div>
				<div class="col-3">
                    <label for="lang" class="form-label">Language / locale</label>
					<?php
						$tts_gg = array( 'af-ZA' => array( 'name'     => 'Afrikaans (Suid-Afrika)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'ar-XA' => array( 'name'     => 'Arabic, multi-region', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'ms-MY' => array( 'name'     => 'Bahasa Melayu (Malaysia)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'ca-ES' => array( 'name'     => 'Català (Espanya)', 'Standard' => 'A', 'Wavenet'  => '' ), 'da-DK' => array( 'name'     => 'Dansk (Danmark)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'de-DE' => array( 'name'     => 'Deutsch (Deutschland)', 'Standard' => 'A,B,C,D,E,F', 'Wavenet'  => 'A,B,C,D,E,F' ), 'en-AU' => array( 'name'     => 'English (Australia)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'en-GB' => array( 'name'     => 'English (Great Britain)', 'Standard' => 'A,B,C,D,F', 'Wavenet'  => 'A,B,C,D,F' ), 'en-IN' => array( 'name'     => 'English (India)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'en-US' => array( 'name'     => 'English (United States)', 'Standard' => 'A,B,C,D,E,F,G,H,I,J', 'Wavenet'  => 'A,B,C,D,E,F,G,H,I,J' ), 'es-ES' => array( 'name'     => 'Español (España)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'B,C,D' ), 'es-US' => array( 'name'     => 'Español (Estados Unidos)', 'Standard' => 'A,B,C', 'Wavenet'  => 'A,B,C' ), 'fil-PH' => array( 'name'     => 'Filipino (Pilipinas)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'fr-CA' => array( 'name'     => 'Français (Canada)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'fr-FR' => array( 'name'     => 'Français (France)', 'Standard' => 'A,B,C,D,E', 'Wavenet'  => 'A,B,C,D,E' ), 'it-IT' => array( 'name'     => 'Italiano (Italia)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'lv-LV' => array( 'name'     => 'Latviešu (latviešu)', 'Standard' => 'A', 'Wavenet'  => '' ), 'hu-HU' => array( 'name'     => 'Magyar (Magyarország)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'nl-NL' => array( 'name'     => 'Nederlands (Nederland)', 'Standard' => 'A,B,C,D,E', 'Wavenet'  => 'A,B,C,D,E' ), 'nb-NO' => array( 'name'     => 'Norsk bokmål (Norge)', 'Standard' => 'A,B,C,D,E', 'Wavenet'  => 'A,B,C,D,E' ), 'pl-PL' => array( 'name'     => 'Polski (Polska)', 'Standard' => 'A,B,C,D,E', 'Wavenet'  => 'A,B,C,D,E' ), 'pt-BR' => array( 'name'     => 'Português (Brasil)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'pt-PT' => array( 'name'     => 'Português (Portugal)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'ro-RO' => array( 'name'     => 'Română (România)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'sk-SK' => array( 'name'     => 'Slovenčina (Slovensko)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'fi-FI' => array( 'name'     => 'Suomi (Suomi)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'sv-SE' => array( 'name'     => 'Svenska (Sverige)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'vi-VN' => array( 'name'     => 'Tiếng Việt (Việt Nam)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'tr-TR' => array( 'name'     => 'Türkçe (Türkiye)', 'Standard' => 'A,B,C,D,E', 'Wavenet'  => 'A,B,C,D,E' ), 'is-IS' => array( 'name'     => 'Íslenska (Ísland)', 'Standard' => 'A', 'Wavenet'  => '' ), 'cs-CZ' => array( 'name'     => 'Čeština (Česká republika)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'el-GR' => array( 'name'     => 'Ελληνικά (Ελλάδα)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'bg-BG' => array( 'name'     => 'Български (България)', 'Standard' => 'A', 'Wavenet'  => '' ), 'ru-RU' => array( 'name'     => 'Русский (Россия)', 'Standard' => 'A,B,C,D,E', 'Wavenet'  => 'A,B,C,D,E' ), 'sr-RS' => array( 'name'     => 'Српски (Србија)', 'Standard' => 'A', 'Wavenet'  => '' ), 'uk-UA' => array( 'name'     => 'Українська (Україна)', 'Standard' => 'A', 'Wavenet'  => 'A' ), 'hi-IN' => array( 'name'     => 'हिन्दी (भारत)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'bn-IN' => array( 'name'     => 'বাংলা (ভারত)', 'Standard' => 'A,B', 'Wavenet'  => 'A,B' ), 'gu-IN' => array( 'name'     => 'ગુજરાતી (ભારત)', 'Standard' => 'A,B', 'Wavenet'  => 'A,B' ), 'ta-IN' => array( 'name'     => 'தமிழ் (இந்தியா)', 'Standard' => 'A,B', 'Wavenet'  => 'A,B' ), 'te-IN' => array( 'name'     => 'తెలుగు (భారతదేశం)', 'Standard' => 'A,B', 'Wavenet'  => '' ), 'kn-IN' => array( 'name'     => 'ಕನ್ನಡ (ಭಾರತ)', 'Standard' => 'A,B', 'Wavenet'  => 'A,B' ), 'ml-IN' => array( 'name'     => 'മലയാളം (ഇന്ത്യ)', 'Standard' => 'A,B', 'Wavenet'  => 'A,B' ), 'th-TH' => array( 'name'     => 'ไทย (ประเทศไทย)', 'Standard' => 'A', 'Wavenet'  => '' ), 'cmn-TW' => array( 'name'     => '國語 (台灣)', 'Standard' => 'A,B,C', 'Wavenet'  => 'A,B,C' ), 'yue-HK' => array( 'name'     => '廣東話 (香港)', 'Standard' => 'A,B,C,D', 'Wavenet'  => '' ), 'ja-JP' => array( 'name'     => '日本語（日本)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'cmn-CN' => array( 'name'     => '普通话 (中国大陆)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ), 'ko-KR' => array( 'name'     => '한국어 (대한민국)', 'Standard' => 'A,B,C,D', 'Wavenet'  => 'A,B,C,D' ) );
						
                        $cur_lang = isset($this->options['lang'])?esc_attr($this->options['lang']):'';
						?>	
                    
				<select name="tts_google_option[lang]" id="podcast_lang" class="form-select select-hide">
					<option value="">Select language</option>
					<?php
					foreach ($tts_gg as $lang => $code) {
						$selected = ($podcast_lang == $lang) ? 'selected="selected"' : '';
						echo '<option value="' . esc_attr($lang) . '" ' . $selected . '>' . $code['name'] . '</option>';
					}
					?>
				</select>
                </div>
                <div class="col-3">
                    <?php
                        $podcast_types = array( 'Arts' => 'Arts', 'Arts|Books' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Books', 'Arts|Design' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Design', 'Arts|Fashion &amp; Beauty' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Fashion &amp; Beauty', 'Arts|Food' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Food', 'Arts|Performing Arts' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Performing Arts', 'Arts|Visual Arts' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Visual Arts', 'Business' => 'Business', 'Business|Careers' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Careers', 'Business|Entrepreneurship' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Entrepreneurship', 'Business|Investing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Investing', 'Business|Management' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Management', 'Business|Marketing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Marketing', 'Business|Non-Profit' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Non-Profit', 'Comedy' => 'Comedy', 'Comedy|Comedy Interviews' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comedy Interviews', 'Comedy|Improv' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Improv', 'Comedy|Stand-Up' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stand-Up', 'Education' => 'Education', 'Education|Courses' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Courses', 'Education|How To' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;How To', 'Education|Language Learning' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Language Learning', 'Education|Self-Improvement' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Self-Improvement', 'Fiction' => 'Fiction', 'Fiction|Comedy Fiction' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Comedy Fiction', 'Fiction|Drama' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Drama', 'Fiction|Science Fiction' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Science Fiction', 'Government' => 'Government', 'History' => 'History', 'Health &amp; Fitness' => 'Health &amp; Fitness', 'Health &amp; Fitness|Alternative Health' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Alternative Health', 'Health &amp; Fitness|Fitness' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Fitness', 'Health &amp; Fitness|Medicine' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Medicine', 'Health &amp; Fitness|Mental Health' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mental Health', 'Health &amp; Fitness|Nutrition' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nutrition', 'Health &amp; Fitness|Sexuality' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sexuality', 'Kids &amp; Family' => 'Kids &amp; Family', 'Kids &amp; Family|Education for Kids' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Education for Kids', 'Kids &amp; Family|Parenting' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Parenting', 'Kids &amp; Family|Pets &amp; Animals' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pets &amp; Animals', 'Kids &amp; Family|Stories for Kids' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Stories for Kids', 'Leisure' => 'Leisure', 'Leisure|Animation &amp; Manga' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Animation &amp; Manga', 'Leisure|Automotive' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Automotive', 'Leisure|Aviation' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Aviation', 'Leisure|Crafts' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Crafts', 'Leisure|Games' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Games', 'Leisure|Hobbies' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hobbies', 'Leisure|Home &amp; Garden' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Home &amp; Garden', 'Leisure|Video Games' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Video Games', 'Music' => 'Music', 'Music|Music Commentary' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Music Commentary', 'Music|Music History' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Music History', 'Music|Music Interviews' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Music Interviews', 'News' => 'News', 'News|Business News' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Business News', 'News|Daily News' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Daily News', 'News|Entertainment News' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Entertainment News', 'News|News Commentary' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;News Commentary', 'News|Politics' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Politics', 'News|Sports News' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sports News', 'News|Tech News' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tech News', 'Religion &amp; Spirituality' => 'Religion &amp; Spirituality', 'Religion &amp; Spirituality|Buddhism' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Buddhism', 'Religion &amp; Spirituality|Christianity' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Christianity', 'Religion &amp; Spirituality|Hinduism' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hinduism', 'Religion &amp; Spirituality|Islam' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Islam', 'Religion &amp; Spirituality|Judaism' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Judaism', 'Religion &amp; Spirituality|Religion' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Religion', 'Religion &amp; Spirituality|Spirituality' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Spirituality', 'Science' => 'Science', 'Science|Astronomy' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Astronomy', 'Science|Chemistry' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Chemistry', 'Science|Earth Sciences' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Earth Sciences', 'Science|Life Sciences' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Life Sciences', 'Science|Mathematics' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Mathematics', 'Science|Natural Sciences' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Natural Sciences', 'Science|Nature' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Nature', 'Science|Physics' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Physics', 'Science|Social Sciences' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Social Sciences', 'Society &amp; Culture' => 'Society &amp; Culture', 'Society &amp; Culture|Documentary' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Documentary', 'Society &amp; Culture|Personal Journals' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Personal Journals', 'Society &amp; Culture|Philosophy' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Philosophy', 'Society &amp; Culture|Places &amp; Travel' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Places &amp; Travel', 'Society &amp; Culture|Relationships' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Relationships', 'Sports' => 'Sports', 'Sports|Baseball' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Baseball', 'Sports|Basketball' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Basketball', 'Sports|Cricket' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Cricket', 'Sports|Fantasy Sports' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Fantasy Sports', 'Sports|Football' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Football', 'Sports|Golf' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Golf', 'Sports|Hockey' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hockey', 'Sports|Rugby' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Rugby', 'Sports|Running' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Running', 'Sports|Soccer' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Soccer', 'Sports|Swimming' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Swimming', 'Sports|Tennis' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tennis', 'Sports|Volleyball' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Volleyball', 'Sports|Wilderness' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Wilderness', 'Sports|Wrestling' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Wrestling', 'Technology' => 'Technology', 'True Crime' => 'True Crime', 'TV &amp; Film' => 'TV &amp; Film', 'TV &amp; Film|After Shows' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;After Shows', 'TV &amp; Film|Film History' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Film History', 'TV &amp; Film|Film Interviews' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Film Interviews', 'TV &amp; Film|Film Reviews' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Film Reviews', 'TV &amp; Film|TV Reviews' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;TV Reviews', );
    $cur_type = isset($tts_google_option['podcast_type']) ? esc_attr($tts_google_option['podcast_type']) : '';
?>
			<label for="podcast_type" class="form-label">Podcast category</label>
			<select name="tts_google_option[podcast_type]" id="podcast_type" class="form-select select-hide">
				<option value="">Select podcast category</option>
				<?php
					foreach ($podcast_types as $type => $type_name) {
						$selected = ($cur_type == $type) ? 'selected="selected"' : '';
						echo '<option value="' . esc_attr($type) . '" ' . $selected . '>' . $type_name . '</option>';
					}
				?>
			</select>

                </div>
                <div class="col-3">
                    <label for="podcast_num" class="form-label">Record number</label>
                    <input name="tts_google_option[podcast_num]" id="podcast_num" class="field-hide form-control" value="<?php echo esc_attr($podcast_num); ?>" type="text">
                </div>
                <div class="col-6">
                    <label for="podcast_description" class="form-label">Podcast description</label>
                    <textarea name="tts_google_option[podcast_description]" id="podcast_description" class="textarea-hide large-text" rows="7"><?php echo esc_attr($podcast_description); ?></textarea>
                </div>
                <div class="col-xs-12 col-sm-6 mb-3">
                    <label for="podcast_cover" class="form-label label-podcast-cover">Cover art</label>
                    <span class="badge rounded-pill bg-warning" data-bs-toggle="modal" data-bs-target="#modal_podcast_cover">?</span>
                    <div class="row">
                        <div class="col-4 cur_cover">
						 <?php echo isset($podcast_cover)?'<img src="' . esc_attr($podcast_cover) . '" style="max-width:250px">':''; ?>
						                   
                        </div>
                        <div class="col-8">
                            <div class="mb-3">
                                <input name="tts_google_option[podcast_cover]" id="podcast_cover" class="field-hide" type="text" value="<?php echo esc_attr($podcast_cover); ?>">
                            </div>
                            <button id="podcast_cover_button" type="button" class="btn btn-info">Upload cover art</button>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <button type="submit" class="btn btn-info luu_cau_hinh_podcast ms-0" name="submit_form">Lưu cấu hình Podcasts</button>
					 <button type="button" class="btn btn-info update-podcast ms-0">Update feed Podcasts</button>
                </div>
                <div class="col-6">
                    <?php
                        if (file_exists(ABSPATH . 'podcast.xml')) {
                            echo '<a target="_blank" href="' . home_url('/podcast.xml') . '">' . home_url('/podcast.xml') . '</a> <span class="badge rounded-pill bg-success" data-bs-toggle="modal" data-bs-target="#modal_podcast_xml">?</span>';
                        }
                    ?>
                </div>
</div>
</form>
<?php ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    "use strict";

    const loading = '<div class="spinner-grow text-white loading" role="status"><span class="visually-hidden">Loading...</span></div>';

    $('.field-hide').keyup(function(){
        var cur_val = $(this).val();
        var cur_id = $(this).attr('id');
        $('.' + cur_id).attr('value', cur_val);
    });

    $('.form-check-input').click(function(){
        var cur_id = $(this).attr('id');
        if($(this).is(':checked')){
            $('.' + cur_id).attr('value', 1);
        }else{
            $('.' + cur_id).attr('value', 0);
        }
    });

    $('.textarea-hide').keyup(function(){
        var cur_text = $(this).val();
        var cur_id = $(this).attr('id');
        $('.' + cur_id).html(cur_text);
    });
    $('.update-podcast').click(function(){
        $.ajax({
            method: "POST",
            url: '<?php echo admin_url('admin-ajax.php');?>',
            data: {
                action: 'capnhatlai_podcast'
            },
            beforeSend: function(){
                $('<div class="modal-backdrop fade show"></div>').appendTo($('body').append(loading));
            },
            success: function(content){
                setTimeout(function () {
                    $('.modal-backdrop').remove();
                    $('.loading').remove();
                    $('#toast').find('.toast-body').html('Update rss podcast successful!');
                    $("#toast").toast('show');
                }, 100);
            }
        });
    });


});
</script>
<?php
}


	public function admin_template()
	{
		require_once plugin_dir_path( __FILE__ ) . 'partials/' .$this->plugin_name . '-admin-display.php';
	}

	public function tts_remove_all(){
		global $wpdb;
		$upload_path = wp_upload_dir();
		$files = glob($upload_path['basedir']. '/tts_uploads/*'); // get all file names
		// print_r($files);
		foreach($files as $file){ // iterate files
		  if(is_file($file)) {
		    unlink($file); // delete file
		  }
		}
		$rows = $wpdb->get_results( "DELETE FROM " . $wpdb->base_prefix . "postmeta WHERE meta_key LIKE 'tts_audio_'");
		echo json_encode(['success' => 1, 'msg' => 'Thao thác thành công!']);
		die();
	}

	public function tts_options()
	{
		$act = isset($_POST['act']) ? $_POST['act'] : null;
		$key = isset($_POST['key']) ? $_POST['key'] : null;
		if($act && $key)
		{
			if($act == 'get')
			{
				$data = get_option($key, false);
				echo json_encode(['success' => 1, 'data' => $data ? json_decode($data) : null]);
			}
			if($act == 'set')
			{
				$data = isset($_POST['data']) ? $_POST['data'] : null;
				if(!$data){
					echo json_encode(['success' => 0, 'msg' => 'Có lỗi xảy ra!', 'data' => json_decode($data)]);
					die();
				}
				$option = get_option($key, false);
				if( $option ) {
				   update_option($key, json_encode($data));
				}else {
				
				   add_option( $key, json_encode($data));
				}
				$data = get_option($key, false);
				echo json_encode(['success' => 1, 'msg' => 'Lưu dữ liệu thành công!', 'data' => json_decode($data)]);
			}

		}
		die();
	}
	public function tts_get_post_files(){ 
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM " . $wpdb->base_prefix . "postmeta WHERE meta_key LIKE 'tts_audio_'");
		$data = [];
		if($rows)
			foreach ($rows as $key => $row) {
				$data[] = [
					'id' => $row->post_id,
					'file' => $row->meta_value,
				];
			}
		echo json_encode(['tts_files' => $data]);
		die();
	}

	public function tts_remove_post_files(){ 
		$ids =  isset($_POST['ids']) ? $_POST['ids'] : null;
		
		if(!$ids){
			echo json_encode(['code' => 0, 'msg' => 'Có lỗi xảy ra']);
			die();
		}
		$upload_path = wp_upload_dir();

		foreach ($ids as $key => $id) {
			$file = $upload_path['basedir']. '/tts_uploads/audio_'.$id.'.mp3';
			
			if (file_exists($upload_path['basedir']. '/tts_uploads')) 
				unlink($file);
			delete_post_meta($id, 'tts_audio_');
		}
		echo json_encode(['code' => 1, 'msg' => 'Thao tác xóa hoàn tất']); 
		die();
		
	}

	public function tts_generate_file(){ 
		$id =  isset($_POST['id']) ? $_POST['id'] : null;
		$file = isset($_POST['file']) ? $_POST['file'] : null;

		if(!$id || !$file){
			echo json_encode(['code' => 0, 'msg' => 'Có lỗi xảy ra']);
			die();
		}
	
		$post = get_post($id);
		if($post)
		{
			$content = $post->post_content;
			$content = preg_replace('#\[[^\]]+\]#', '',$content);
			$content = preg_replace("/<img[^>]+\>/i", " ", $content);   
			$content = wp_strip_all_tags($content);   
			// $content = '..........' . wp_strip_all_tags($content) . '..........';   
			$this->curlAudio($content, $file, $id);
		}
			
		die();
	}

	public function curlAudio($content, $file, $post_id)
	{
		// e
		$upload_path = wp_upload_dir();
		// die();
		$opt = get_option('tts_settings', '{}');
		$opt_arr = is_array($opt) ? $opt : (json_decode($opt, true) ?? []);
		$viettel_tokens = $opt_arr['viettel_tokens'] ?? '';

		if($file['channel'] == 'Vbee'){
			$params = [
				'text'       => $content,
				'voice_name' => $file['voice'],
				'speed'      => floatval($file['speed']),
				'app_id'     => $opt_arr['vbee_app_id'] ?? '',
				'app_secret' => $opt_arr['vbee_app_secret'] ?? '',
			];
			$this->vbee($params, $post_id);
			die();
		}

		if($file['channel'] == 'Viettel'){
			$params = [
				'text' => $content,
				'voice' => $file['voice'],
				'speed' => $file['speed'],
				'tts_return_option' => 3,
				'token' => $viettel_tokens,
				'without_filter' => false
			];
			// echo json_encode($params);
			$response = $this->viettel($params, $post_id);
			die();
		}

		if($file['channel'] == 'Zalo'){
			$params = [
				'input' => $content,
				'speaker_id' => $file['voice'],
				'speed' => $file['speed'],
				
			];
			
			$arrays = $this->zalo($params);
			// print_r($arrays);
			// die();
			sleep(2);
			$b = null;
			foreach ($arrays as $key => $d) {
					$b  .= file_get_contents($d);
			}
			$response = $b;
		}
		
	
		if (!file_exists($upload_path['basedir']. '/tts_uploads')) {
		    mkdir($upload_path['basedir']. '/tts_uploads');
		}
		$file_dir = $upload_path['basedir']. '/tts_uploads/audio_'.$post_id.'.mp3';



		file_put_contents($file_dir, $response);
		
		if($file['channel'] == 'Viettel'){
			$audio = new PHPMP3($file_dir);
			$audio->setFileInfoExact();
			$time = $audio->time;
			$mp3 = $audio->extract(5, $time - 10);
			$mp3->save($file_dir);
		}


		$meta_key = 'tts_audio_'; 
		$file = $upload_path['baseurl'] . '/tts_uploads/audio_'.$post_id.'.mp3';
		$existing_pms = get_post_meta( $post_id, $meta_key, true );
		if($existing_pms)
			update_post_meta( $post_id, $meta_key, $file);
		else
			add_post_meta( $post_id, $meta_key, $file);


		echo json_encode(['code' => 1, 'file' => $file, 'id' => $post_id, 'msg' => 'Tạo tệp mp3 thành công cho bài viết: ' . $post_id ]);

		die();
	}


	protected function viettel($params, $post_id)
	{
		$upload_path = wp_upload_dir();
		$endpoint = 'https://viettelai.vn/tts/speech_synthesis';
		
		
		$args = [
			'timeout' => 60,
			'headers' => [
				'accept' => '*/*',
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode($params, JSON_UNESCAPED_UNICODE),
			'sslverify' => false,
		];

		$res = wp_remote_post($endpoint, $args);
		if (is_wp_error($res)) {
			echo json_encode(['code' => 0, 'msg' => 'HTTP Error: ' . $res->get_error_message()]);
			return;
		}

		$code = wp_remote_retrieve_response_code($res);
		$body = wp_remote_retrieve_body($res);

		if ($code < 200 || $code >= 300 || !$body) {
			echo json_encode(['code' => 0, 'msg' => 'API trả về HTTP ' . $code . ' hoặc body rỗng. Body: ' . substr($body, 0, 4000)]);
			return;
		}

		$file_url = $this->save_audio_payload($body, $post_id);
		if (!$file_url) {
			echo json_encode(['code' => 0, 'msg' => 'Không nhận dạng được định dạng trả về. Body: ' . substr($body, 0, 4000)]);
			return;
		}

		echo json_encode(['code' => 1, 'file' => $file_url, 'id' => $post_id, 'msg' => 'Tạo tệp mp3 thành công cho bài viết: ' . $post_id]);
	}

	/**
	 * Vbee TTS API
	 * Docs: https://documenter.getpostman.com/view/12951168/Uz5FHbSd
	 */
	protected function vbee($params, $post_id)
	{
		$endpoint = 'https://vbee.vn/api/v1/tts/speech_synthesis';

		$body = [
			'app_id'     => $params['app_id'],
			'app_secret' => $params['app_secret'],
			'text'       => $params['text'],
			'voice_name' => $params['voice_name'],
			'speed'      => floatval($params['speed'] ?? 1.0),
		];

		$args = [
			'timeout'   => 120,
			'headers'   => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'      => wp_json_encode($body, JSON_UNESCAPED_UNICODE),
			'sslverify' => false,
		];

		$res = wp_remote_post($endpoint, $args);

		if (is_wp_error($res)) {
			echo json_encode(['code' => 0, 'msg' => 'Vbee lỗi kết nối: ' . $res->get_error_message()]);
			return;
		}

		$http_code = wp_remote_retrieve_response_code($res);
		$body_raw  = wp_remote_retrieve_body($res);
		$json      = json_decode($body_raw, true);

		if (is_array($json)) {
			// Trường hợp 1: JSON trả về audio_url → tải về
			$audio_url = $json['audio_url']
				?? ($json['data']['audio_url'] ?? null);

			if ($audio_url) {
				$dl = wp_remote_get($audio_url, ['timeout' => 120, 'sslverify' => false]);
				if (!is_wp_error($dl) && 200 === wp_remote_retrieve_response_code($dl)) {
					$bin = wp_remote_retrieve_body($dl);
					if ($bin) {
						$file_url = $this->save_audio_payload($bin, $post_id);
						echo json_encode(['code' => 1, 'file' => $file_url, 'id' => $post_id, 'msg' => 'Vbee: Tạo tệp mp3 thành công cho bài viết: ' . $post_id]);
						return;
					}
				}
				echo json_encode(['code' => 0, 'msg' => 'Vbee: Không tải được audio từ URL: ' . $audio_url]);
				return;
			}

			// Trường hợp 2: JSON trả về base64
			foreach (['audio', 'audio_data', 'audioData', 'data'] as $k) {
				if (!empty($json[$k]) && is_string($json[$k])) {
					$b64 = preg_replace('#^data:audio/[a-z0-9.+-]+;base64,#i', '', $json[$k]);
					$bin = base64_decode($b64, true);
					if ($bin) {
						$file_url = $this->save_audio_payload($bin, $post_id);
						echo json_encode(['code' => 1, 'file' => $file_url, 'id' => $post_id, 'msg' => 'Vbee: Tạo tệp mp3 thành công cho bài viết: ' . $post_id]);
						return;
					}
				}
			}

			// Lỗi rõ ràng từ API
			$err = $json['message'] ?? ($json['error'] ?? ($json['msg'] ?? 'Lỗi không xác định'));
			echo json_encode(['code' => 0, 'msg' => 'Vbee API lỗi (HTTP ' . $http_code . '): ' . $err]);
			return;
		}

		// Trường hợp 3: body trực tiếp là binary MP3
		if (preg_match('#^ID3|^\xFF[\xFB\xF3\xF2]#', $body_raw) || strlen($body_raw) > 1000) {
			$file_url = $this->save_audio_payload($body_raw, $post_id);
			echo json_encode(['code' => 1, 'file' => $file_url, 'id' => $post_id, 'msg' => 'Vbee: Tạo tệp mp3 thành công cho bài viết: ' . $post_id]);
			return;
		}

		echo json_encode(['code' => 0, 'msg' => 'Vbee: Không nhận dạng được response (HTTP ' . $http_code . '). Body: ' . substr($body_raw, 0, 300)]);
	}

	private function save_audio_payload($body, $post_id)
	{
		$upload = wp_upload_dir();
		$dir = trailingslashit($upload['basedir']) . 'tts_uploads';
		$url = trailingslashit($upload['baseurl']) . 'tts_uploads';
		if (!file_exists($dir)) wp_mkdir_p($dir);

		$name = 'audio_' . $post_id . '.mp3';
		$path = $dir . '/' . $name;
		$href = $url . '/' . $name;

		$json = json_decode($body, true);
		if (is_array($json)) {
		
			if (!empty($json['audio_url'])) {
				$r = wp_remote_get($json['audio_url'], ['timeout' => 60]);
				if (!is_wp_error($r) && 200 === wp_remote_retrieve_response_code($r)) {
					$bin = wp_remote_retrieve_body($r);
					if ($bin) {
						file_put_contents($path, $bin);
						$this->update_post_meta($post_id, $href);
						return $href;
					}
				}
			}
		
			foreach (['data', 'audio', 'audioData', 'audio_base64', 'result'] as $k) {
				if (!empty($json[$k]) && is_string($json[$k])) {
					$b64 = preg_replace('#^data:audio/[a-z0-9.+-]+;base64,#i', '', $json[$k]);
					$bin = base64_decode($b64, true);
					if ($bin) {
						file_put_contents($path, $bin);
						$this->update_post_meta($post_id, $href);
						return $href;
					}
				}
			}
		}

		
		if (preg_match('#^ID3|^\xFF\xFB#', $body) || strlen($body) > 200) {
			file_put_contents($path, $body);
			$this->update_post_meta($post_id, $href);
			return $href;
		}
		return false;
	}

	private function update_post_meta($post_id, $file_url)
	{
		$meta_key = 'tts_audio_';
		$existing_pms = get_post_meta($post_id, $meta_key, true);
		if ($existing_pms) {
			update_post_meta($post_id, $meta_key, $file_url);
		} else {
			add_post_meta($post_id, $meta_key, $file_url);
		}
	}


	public function zalo($params)
	{

		$params['input'] = preg_replace( "/\r|\n/", "", $params['input'] );
		$urls = [];
		$x = 1990;
		$lines = explode("\n", wordwrap($params['input'], $x));
		
		foreach ($lines as $key => $line) {
			$param_new = [
				'input' => $line,
				'speaker_id' => $params['speaker_id'],
				'speed' => $params['speed'],
				'encode_type' => 1
				
			];

			$tokens = preg_split('/\r\n|[\r\n]/', $this->settings['zalo_tokens']);
			foreach ($tokens as $key => $token) {
				$curl = curl_init();

				curl_setopt_array($curl, array(
				  CURLOPT_URL => 'https://api.zalo.ai/v1/tts/synthesize',
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => '',
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 0,
				  CURLOPT_FOLLOWLOCATION => true,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => 'POST',
				  CURLOPT_POSTFIELDS => http_build_query($param_new),
				  CURLOPT_HTTPHEADER => array(
				    'apikey: ' . trim($token),
				    'Content-Type: application/x-www-form-urlencoded'
				  ),
				));

				$response = curl_exec($curl);

				curl_close($curl);
				$response = json_decode($response, true);
			
				if(!$response['error_code']){
					$urls[] = $response['data']['url'];
					break;
				}
			}

			
		}
		
		return $urls;
		
	}

	public function generateRandomString($length = 10) {
	    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}


	public function curlGetFile($url)
	{
		$url = $url;
		$process = curl_init($url); 
		curl_setopt($process, CURLOPT_HEADER, 0); 
		curl_setopt($process, CURLOPT_POST, 1); 
		curl_setopt($process, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($process,CURLOPT_CONNECTTIMEOUT,1);
		$response = curl_exec($process); 
		curl_close($process); 

		return $response;
	}
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Text_To_Speech_Mh_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Text_To_Speech_Mh_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/text-to-speech-mh-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Text_To_Speech_Mh_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Text_To_Speech_Mh_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_media();	
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/text-to-speech-mh-admin.js', array( 'jquery' ), $this->version, false );

	}

}