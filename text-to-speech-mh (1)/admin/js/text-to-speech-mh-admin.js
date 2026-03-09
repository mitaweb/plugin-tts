(function( $ ) {
	'use strict';

	// ============================================================
	// Cấu hình voices Vbee
	// ============================================================
	var VBEE_VOICES = [
		{ id: 'hn_female_ngochuyen_full_48k-fhg',  name: 'Ngọc Huyền – Nữ Hà Nội' },
		{ id: 'hn_male_manhdung_full_48k-fhg',     name: 'Mạnh Dũng – Nam Hà Nội' },
		{ id: 'sg_female_maiphuong_full_48k-fhg',  name: 'Mai Phương – Nữ Sài Gòn' },
		{ id: 'sg_male_chidat_full_48k-fhg',       name: 'Chí Đạt – Nam Sài Gòn' },
		{ id: 'hue_female_tuha_full_48k-fhg',      name: 'Tú Hà – Nữ Huế' },
		{ id: 'hn_female_thutrang_full_48k-fhg',   name: 'Thu Trang – Nữ Hà Nội' },
		{ id: 'sg_female_caotinh_full_48k-fhg',    name: 'Cao Tinh – Nữ Sài Gòn' },
		{ id: 'hn_male_trunghieu_full_48k-fhg',    name: 'Trung Hiếu – Nam Hà Nội' },
	];

	// ============================================================
	// Helper: cập nhật dropdown voice theo channel
	// Gọi hàm này bất cứ khi nào channel thay đổi
	// ============================================================
	function updateVoicesByChannel(channel, $voiceSelect) {
		if (channel !== 'Vbee') return; // Viettel/Zalo tự xử lý như cũ

		$voiceSelect.empty();
		$.each(VBEE_VOICES, function(i, v) {
			$voiceSelect.append(
				$('<option>', { value: v.id, text: v.name })
			);
		});
	}

	// ============================================================
	// Helper: load & lưu settings Vbee qua AJAX tts_options
	// ============================================================
	function loadVbeeSettings() {
		$.post(
			ajaxurl,
			{ action: 'tts_options', act: 'get', key: 'tts_settings' },
			function(res) {
				if (res && res.data) {
					$('#tts_vbee_app_id').val(res.data.vbee_app_id || '');
					$('#tts_vbee_app_secret').val(res.data.vbee_app_secret || '');
				}
			}
		);
	}

	function saveVbeeSettings(callback) {
		// Đọc toàn bộ settings hiện tại rồi merge Vbee vào
		$.post(
			ajaxurl,
			{ action: 'tts_options', act: 'get', key: 'tts_settings' },
			function(res) {
				var current = res && res.data ? res.data : {};
				var merged = $.extend({}, current, {
					vbee_app_id:     $('#tts_vbee_app_id').val().trim(),
					vbee_app_secret: $('#tts_vbee_app_secret').val().trim(),
				});
				$.post(
					ajaxurl,
					{ action: 'tts_options', act: 'set', key: 'tts_settings', data: merged },
					function(saveRes) {
						if (typeof callback === 'function') callback(saveRes);
					}
				);
			}
		);
	}

	// ============================================================
	// DOM ready
	// ============================================================
	jQuery(document).ready(function($) {

		// ----------------------------------------------------------
		// Podcast cover picker (code gốc giữ nguyên)
		// ----------------------------------------------------------
		$('#podcast_cover_button').click(function() {
			if (typeof wp !== 'undefined' && wp.media) {
				var file_frame = wp.media({
					title: 'Chọn cover',
					button: { text: 'Chọn cover' },
					multiple: false
				});
				file_frame.on('select', function() {
					var attachment = file_frame.state().get('selection').first().toJSON();
					$('#podcast_cover').val(attachment.url);
					$('.cur_cover').html('<img width="100" height="100" src="' + attachment.url + '">');
				});
				file_frame.open();
			} else {
				console.error('wp.media is not available.');
			}
		});

		$('#podcast_cover').keyup(function() {
			var src = $(this).val();
			$('.cur_cover').html(src ? '<img width="100" height="100" src="' + src + '">' : '');
		});

		// ----------------------------------------------------------
		// Vbee: inject UI settings vào trang settings nếu chưa có
		// ----------------------------------------------------------
		// Plugin thường render settings trong .tts-settings-wrap hoặc
		// form#tts_settings_form. Em inject sau field viettel_tokens.
		// Nếu selector không khớp, anh đổi lại selector cho đúng.
		// ----------------------------------------------------------
		var $viettelField = $('[name="viettel_tokens"], #viettel_tokens').closest('.tts-field, .form-group, tr, div').last();

		if ($viettelField.length && !$('#tts_vbee_app_id').length) {
			var vbeeHtml = '\
<div class="tts-field tts-vbee-settings" style="margin-top:12px;">\
    <h3 style="margin-bottom:8px;">⚙️ Vbee API</h3>\
    <p style="margin:0 0 6px;">\
        Lấy <strong>App ID</strong> và <strong>App Secret</strong> tại \
        <a href="https://studio.vbee.vn" target="_blank">studio.vbee.vn</a> → API Management.\
    </p>\
    <label style="display:block;margin-bottom:4px;">App ID</label>\
    <input id="tts_vbee_app_id" type="text" placeholder="Nhập Vbee App ID"\
           style="width:100%;max-width:480px;margin-bottom:10px;" />\
    <label style="display:block;margin-bottom:4px;">App Secret</label>\
    <input id="tts_vbee_app_secret" type="password" placeholder="Nhập Vbee App Secret"\
           style="width:100%;max-width:480px;margin-bottom:10px;" />\
    <button id="tts_vbee_save_btn" type="button" class="button button-primary">Lưu Vbee Settings</button>\
    <span id="tts_vbee_save_msg" style="margin-left:10px;color:green;"></span>\
</div>';
			$viettelField.after(vbeeHtml);
			loadVbeeSettings();
		}

		// Lưu Vbee settings
		$(document).on('click', '#tts_vbee_save_btn', function() {
			var $btn = $(this);
			$btn.prop('disabled', true).text('Đang lưu...');
			saveVbeeSettings(function(res) {
				$btn.prop('disabled', false).text('Lưu Vbee Settings');
				var msg = res && res.success ? '✅ Đã lưu!' : '❌ Lỗi lưu settings!';
				$('#tts_vbee_save_msg').text(msg).show();
				setTimeout(function() { $('#tts_vbee_save_msg').fadeOut(); }, 3000);
			});
		});

		// ----------------------------------------------------------
		// Vbee: cập nhật voice dropdown khi channel đổi sang Vbee
		// Plugin thường dùng select#tts_channel hoặc [name="channel"]
		// ----------------------------------------------------------
		$(document).on('change', '#tts_channel, [name="channel"], .tts-channel-select', function() {
			var channel = $(this).val();
			var $voiceSelect = $('#tts_voice, [name="voice"], .tts-voice-select');

			if (channel === 'Vbee') {
				updateVoicesByChannel('Vbee', $voiceSelect);

				// Hiện gợi ý tốc độ cho Vbee (0.5 – 2.0)
				$('#tts_speed_hint').text('Vbee: tốc độ 0.5 (chậm) → 1.0 (bình thường) → 2.0 (nhanh)');
			} else {
				$('#tts_speed_hint').text('');
			}
		});

		// ----------------------------------------------------------
		// Nếu page load sẵn channel = Vbee thì cũng cập nhật voices
		// ----------------------------------------------------------
		var currentChannel = $('#tts_channel, [name="channel"], .tts-channel-select').val();
		if (currentChannel === 'Vbee') {
			updateVoicesByChannel('Vbee', $('#tts_voice, [name="voice"], .tts-voice-select'));
		}

	}); // end document.ready

})( jQuery );