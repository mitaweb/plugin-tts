<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://nguyenlan.io.vn/
 * @since             1.0.0
 * @package           Text_To_Speech_Mh
 *
 * @wordpress-plugin
 * Plugin Name:       Text to Speech
 * Plugin URI:        text-to-speech
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Nguyễn Lân + MH
 * Author URI:        https://nguyenlan.io.vn/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       text-to-speech-mh
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TEXT_TO_SPEECH_MH_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-text-to-speech-mh-activator.php
 */
function activate_text_to_speech_mh() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-text-to-speech-mh-activator.php';
	Text_To_Speech_Mh_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-text-to-speech-mh-deactivator.php
 */
function deactivate_text_to_speech_mh() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-text-to-speech-mh-deactivator.php';
	Text_To_Speech_Mh_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_text_to_speech_mh' );
register_deactivation_hook( __FILE__, 'deactivate_text_to_speech_mh' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-text-to-speech-mh.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_text_to_speech_mh() {
    $plugin = new Text_To_Speech_Mh();
    $plugin->run();

    $theme = wp_get_theme();
    $parent_theme = $theme->parent();

    if ( $theme->template === 'flatsome' || ( $parent_theme && $parent_theme->template === 'flatsome' ) ) {

        add_action('ux_builder_setup', function() {
            $advanced_file = get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php';
            $advanced_options = file_exists($advanced_file) ? require $advanced_file : array();

            add_ux_builder_shortcode('audio_baiviet', array(
                'name'     => __('File audio', 'text-domain'),
                'category' => __('Content', 'text-domain'),
                'priority' => 1,
                'options'  => array(
                    'advanced_options' => $advanced_options,
                ),
            ));
        });

        add_shortcode('audio_baiviet', function ($atts, $content = null) {
            $atts = shortcode_atts(array(
                'class' => '',
                'visibility' => '',
            ), $atts);

            if ($atts['visibility'] === 'hidden') {
                return '';
            }

            $file_audio = get_post_meta(get_the_ID(), 'tts_audio_', true);
            if (!$file_audio) {
                return '';
            }

            ob_start();
            ?>
            <div class="doc-bai-viet">
                <audio controls>
                    <source src="<?php echo esc_url($file_audio); ?>" type="audio/mp3">
                    Trình duyệt của bạn không hỗ trợ HTML5
                </audio>
            </div>
            <?php
            return ob_get_clean();
        });

    }
}
run_text_to_speech_mh();
