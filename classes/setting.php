<?php
class ONDOKUSAN_Setting {
	/*
	 * 初期定義
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_page' ] );
		add_action( 'admin_init', [ $this, 'setting_save' ] );

	}

	/*
	 * 設定ページ追加
	 */
	public function add_page() {
		add_options_page( esc_html__('Ondoku settings','ondoku3'), esc_html__('Ondoku','ondoku3'), 'administrator', 'ondokusan_setting_page', [ $this, 'setting_page' ] );
	}

	/*
	 * 設定データ保存
	 */
	public function setting_save() {
		if ( getenv( 'REQUEST_METHOD' ) !== 'POST' || ! isset( $_POST['ondokusan_nonce'] ) || ! isset( $_POST['ondokusan_action'] ) ) return;

		// nonceチェック
		check_admin_referer( 'ondokusan_setting', 'ondokusan_nonce' );

		// 設定値保存
		update_option( 'ondokusan_settings', array(
			'token' => sanitize_text_field( $_POST['ondokusan_token'] ),
			'language' => sanitize_text_field( $_POST['ondokusan_language'] ),
			'voice' => sanitize_text_field( $_POST['ondokusan_voice'] ),
			'speed' => sanitize_text_field( $_POST['ondokusan_speed'] ),
			'pitch' => sanitize_text_field( $_POST['ondokusan_pitch'] ),
		) );

		add_settings_error( 'ondokusan_setting', esc_attr( 'settings_updated' ), esc_html__('Saved the setting','ondoku3'), 'updated' );
	}

	/*
	 * 設定ページ
	 */
	public function setting_page() {

		$option = $this->load_option();

		$voices = $this->voices();

		?>
		<div class="wrap">
			<h2><?php esc_html_e('Ondoku settings','ondoku3'); ?></h2>
			<form method="post" action="" id="ondokusan-setting">
				<?php wp_nonce_field( 'ondokusan_setting', 'ondokusan_nonce' ); ?>
				<input type="hidden" name="ondokusan_action" value="setting">
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e('Access token','ondoku3'); ?></th>
						<td>
							<input type="text" class="large-text" name="ondokusan_token" value="<?php echo esc_attr( $option['token'] ); ?>" />
							<p class="description">
								<?php esc_html_e('Please enter the access token for the Ondoku API request.','ondoku3'); ?><br/>
								<?php esc_html_e('You can get the access token from settings page after you logged in Ondoku.','ondoku3'); ?></br>
								<a href="https://ondoku3.com/users/setting/"><?php esc_html_e('Open Settings','ondoku3'); ?></a>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e('Language','ondoku3'); ?></th>
						<td>
							<select id="ondokusan_languages" name="ondokusan_language">
								<?php
								foreach ($this->voice_languages() as $lang_key => $lang_val) {
									echo '<option value="'.$lang_key.'"'.selected( $option['language'], $lang_key ,false ).'>'.$lang_val.'</option>';
								}
								?>
							</select>
						</td>
					</tr>


					<tr valign="top">
						<th scope="row"><?php esc_html_e('Voices','ondoku3'); ?></th>
						<td>
							<select id="ondokusan_voices" name="ondokusan_voice">
								<?php
								$tmp_lang = $option['language'];
								if( $tmp_lang === 'cmn-CN'){
									$tmp_lang = '-CN-';
								}else if( $tmp_lang === 'cmn-TW'){
									$tmp_lang = '-TW-';
								}
								foreach ($voices as $voice_key => $voice_val) {
									if(stripos($voice_key,$tmp_lang)!==false){
										echo '<option value="'.$voice_key.'"'.selected( $option['voice'], $voice_key ,false ).'>'.$voice_val.'</option>';
									}
								}
								?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php esc_html_e('Speed','ondoku3'); ?> : (<span id="ondokusan_speed_now"><?php echo esc_attr($option['speed']); ?></span>)</th>
						<td>
							<input id="ondokusan_speed" type="range" name="ondokusan_speed" class="" min="0.3" max="4" step="0.1" value="<?php echo esc_attr($option['speed']); ?>" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e('Pitch','ondoku3'); ?> : (<span id="ondokusan_pitch_now"><?php echo esc_attr($option['pitch']); ?></span>)</th>
						<td>
							<input id="ondokusan_pitch" type="range" name="ondokusan_pitch" class="" min="-20" max="20" step="0.1" value="<?php echo esc_attr($option['pitch']); ?>" />
						</td>
					</tr>
				</table>
				<button type="submit" class="button-primary"><?php esc_html_e('Save','ondoku3'); ?></button>
			</form>
		</div>

		<script>

			var voices = <?php echo json_encode($voices); ?>;
			jQuery("#ondokusan_languages").change(function () {

				var lang = jQuery(this).val();

				jQuery('#ondokusan_voices').children().remove();

				if( lang === 'cmn-CN'){
					lang = '-CN-';
				}else if( lang === 'cmn-TW'){
					lang = '-TW-';
				}

				jQuery.each(voices, function(key, value){
					if ( key.indexOf(lang) !== -1 ) {
						jQuery('#ondokusan_voices').append(jQuery('<option>').attr({ value: key }).text(value));
					}

				})
			});

			jQuery("#ondokusan_speed").on("input",function(){
				jQuery("#ondokusan_speed_now").html(jQuery(this).val());
			});
			jQuery("#ondokusan_pitch").on("input",function(){
				jQuery("#ondokusan_pitch_now").html(jQuery(this).val());
			});

		</script>
		<style>
			input[type="range"]{
				width: 100%;
				max-width: 280px;
			}
		</style>
		<?php
	}



	public function load_option() {
		/*設定の読み出し*/

		$option = get_option( 'ondokusan_settings' );

		/*トークン初期化*/
		if(!isset($option['token'])){
			$option['token'] = '';
		}
		/*言語初期化*/
		if(!isset($option['language'])){

			$locale = get_locale();

			if($locale === 'zh-cn'){
				/*中国語*/
				$locale = 'cmn-CN';
			}elseif($locale === 'zh-tw'){
				/*台湾語*/
				$locale = 'cmn-TW';
			}elseif(strlen($locale) > 2){
				/*en-gbなどをen-GBに変換*/
				$substr = substr($locale, 0, -2);
				$locale = str_replace($substr, strtoupper($substr) , $locale);
			}

			$option['language'] = $this->languages_adjustment($locale);
		}
		/*音声初期化*/
		if(!isset($option['voice'])){
			$option['voice'] = $this->voice_adjustment($option['language']);
		}

		/*速度初期化*/
		if(!isset($option['speed'])){
			$option['speed'] = 1;
		}
		/*ピッチ初期化*/
		if(!isset($option['pitch'])){
			$option['pitch'] = 0;
		}
		/*Ver 1.0.1以前の仕様対応*/
		$tmp = get_option( 'ondokusan_token' );
		if($tmp){
			$option['token'] = $tmp;
			delete_option( 'ondokusan_token' );
		}

		update_option( 'ondokusan_settings' , $option);

		/*トークン有効性*/
		if(!isset($option['enable'])){
			$option['enable'] = false;
		}

		update_option( 'ondokusan_settings' , $option);

		return $option;

	}

	public function languages_adjustment($lang) {

		foreach($this->voice_languages() as $locale => $locale_val){
			if(stripos($locale,$lang)!==false){
				return $locale;
			}
		}

		return 'en-US';

	}

	public function voice_adjustment($lang) {

		foreach($this->voices() as $voice => $name ){
			if(stripos($voice,$lang)!==false){
				return $voice;
			}
		}

		return 'en-US-Wavenet-A';
	}

	public function voice_languages() {
		return array(
			"af-ZA" => "Afrikaans (Suid-Afrika)",
			"az-AZ" => "Azərbaycan (Latın, Azərbaycan)",
			"bs-BA" => "Bosanski (Bosna i Hercegovina)",
			"ca-ES" => "Català (Espanya)",
			"cy-GB" => "Cymraeg (Y Deyrnas Unedig)",
			"da-DK" => "Dansk (Danmark)",
			"de-DE" => "Deutsch (Deutschland)",
			"en-AU" => "English (Australia)",
			"en-IN" => "English (India)",
			"en-US" => "English (USA)",
			"en-GB" => "English (United Kingdom)",
			"es-US" => "Español (EE. UU.)",
			"es-ES" => "Español (España)",
			"es-MX" => "Español (México)",
			"fil-PH" => "Filipino (Pilipinas)",
			"fr-CA" => "Français (Canada)",
			"fr-FR" => "Français (France)",
			"ga-IE" => "Gaeilge (Éire)",
			"gl-ES" => "Galego",
			"hr-HR" => "Hrvatski (Hrvatska)",
			"id-ID" => "Indonesia (Indonesia)",
			"it-IT" => "Italiano (Italia)",
			"jv-ID" => "Jawa (Latin, Indonesia)",
			"lv-LV" => "Latviešu (Latvija)",
			"lt-LT" => "Lietuvių (Lietuva)",
			"hu-HU" => "Magyar (Magyarország)",
			"mt-MT" => "Malti (Malta)",
			"ms-MY" => "Melayu (Malaysia)",
			"nl-NL" => "Nederlands (Nederland)",
			"nb-NO" => "Norsk (Norge)",
			"pl-PL" => "Polski (Polska)",
			"pt-BR" => "Português (Brasil)",
			"pt-PT" => "Português (Portugal)",
			"ro-RO" => "Română (România)",
			"sk-SK" => "Slovenčina (Slovensko)",
			"sl-SI" => "Slovenščina (Slovenija)",
			"so-SO" => "Soomaali (Soomaaliya)",
			"fi-FI" => "Suomi (Suomi)",
			"sv-SE" => "Svenska (Sverige)",
			"tr-TR" => "Türkçe (Türkiye)",
			"ur-IN" => "Urdu (India)",
			"ur-PK" => "Urdu (Pakistan)",
			"uz-UZ" => "Uzbek (Latin, Uzbekistan)",
			"vi-VN" => "Việt (Việt Nam)",
			"is-IS" => "Íslenska (Ísland)",
			"cs-CZ" => "Čeština (Česká republika)",
			"el-GR" => "Ελληνικά (Ελλάδα)",
			"bg-BG" => "Български (България)",
			"mk-MK" => "Македонски (Северна Македонија)",
			"mn-MN" => "Монгол (Монгол)",
			"ru-RU" => "Русский (Россия)",
			"uk-UA" => "Українська (Україна)",
			"kk-KZ" => "Қазақ (Қазақстан)",
			"hy-AM" => "Հայերեն (Հայաստան)",
			"he-IL" => "עברית (ישראל)",
			"ar-SA" => "العربية (المملكة العربية السعودية)",
			"ar-EG" => "العربية (مصر)",
			"ar-XA" => "عربى",
			"fa-IR" => "فارسی (ایران)",
			"ps-AF" => "پښتو (افغانستان)",
			"ne-NP" => "नेपाली (नेपाल)",
			"mr-IN" => "मराठी (भारत)",
			"hi-IN" => "हिन्दी (भारत)",
			"bn-BD" => "বাংলা (বাংলাদেশ)",
			"bn-IN" => "বাংলা (ভারত)",
			"gu-IN" => "ગુજરાતી (ભારત)",
			"ta-IN" => "தமிழ் (இந்தியா)",
			"kn-IN" => "ಕನ್ನಡ (ಭಾರತ)",
			"ml-IN" => "മലയാളം (ഇന്ത്യ)",
			"th-TH" => "ไทย (ไทย)",
			"lo-LA" => "ລາວ (ລາວ)",
			"my-MM" => "မြန်မာ (မြန်မာ)",
			"ka-GE" => "ქართული (საქართველო)",
			"am-ET" => "አማርኛ (ኢትዮጵያ)",
			"km-KH" => "ខ្មែរ (កម្ពុជា)",
			"cmn-TW" => "国语（台湾）",
			"cmn-HK" => "广东话（香港）",
			"ja-JP" => "日本語（日本）",
			"cmn-CN" => "普通话（中国大陆）",
			"ko-KR" => "한국어 (대한민국)",
		);
	}

	public function voices() {
		return array(
			"ps-AF-GulNawazNeural" => " ګل نواز",
			"en-US-AIGenerate1Neural" => "AIGenerate1",
			"en-US-AIGenerate2Neural" => "AIGenerate2",
			"en-GB-AbbiNeural" => "Abbi",
			"en-NG-AbeoNeural" => "Abeo",
			"es-ES-AbrilNeural" => "Abril",
			"th-TH-AcharaNeural" => "Achara",
			"en-IN-Aditi" => "Aditi",
			"hi-IN-Aditi" => "Aditi",
			"af-ZA-AdriNeural" => "Adri",
			"pl-PL-AgnieszkaNeural" => "Agnieszka",
			"tr-TR-AhmetNeural" => "Ahmet",
			"eu-ES-AinhoaNeural" => "Ainhoa",
			"fr-FR-AlainNeural" => "Alain",
			"ca-ES-AlbaNeural" => "Alba",
			"cy-GB-AledNeural" => "Aled",
			"es-PE-AlexNeural" => "Alex",
			"en-GB-AlfieNeural" => "Alfie",
			"ro-RO-AlinaNeural" => "Alina",
			"es-US-AlonsoNeural" => "Alonso",
			"es-ES-AlvaroNeural" => "Alvaro",
			"de-DE-AmalaNeural" => "Amala",
			"en-US-AmberNeural" => "Amber",
			"en-GB-Amy" => "Amy",
			"en-US-AnaNeural" => "Ana",
			"eu-ES-AnderNeural" => "Ander",
			"es-EC-AndreaNeural" => "Andrea",
			"es-GT-AndresNeural" => "Andrés",
			"fil-PH-AngeloNeural" => "Angelo",
			"sq-AL-AnilaNeural" => "Anila",
			"en-AU-AnnetteNeural" => "Annette",
			"fr-CA-AntoineNeural" => "Antoine",
			"cs-CZ-AntoninNeural" => "Antonin",
			"pt-BR-AntonioNeural" => "Antonio",
			"et-EE-AnuNeural" => "Anu",
			"id-ID-ArdiNeural" => "Ardi",
			"en-US-AriaNeural" => "Aria",
			"fr-CH-ArianeNeural" => "Ariane",
			"es-ES-ArnauNeural" => "Arnau",
			"nl-BE-ArnaudNeural" => "Arnaud",
			"en-US-AshleyNeural" => "Ashley",
			"en-KE-AsiliaNeural" => "Asilia",
			"sv-SE-Astrid" => "Astrid",
			"el-GR-AthinaNeural" => "Athina",
			"he-IL-AvriNeural" => "Avri",
			"az-AZ-BabekNeural" => "Babək",
			"az-AZ-BanuNeural" => "Banu",
			"es-MX-BeatrizNeural" => "Beatriz",
			"es-CU-BelkysNeural" => "Belkys",
			"en-GB-BellaNeural" => "Bella",
			"it-IT-BenignoNeural" => "Benigno",
			"de-DE-BerndNeural" => "Bernd",
			"it-IT-Bianca" => "Bianca",
			"fil-PH-BlessicaNeural" => "Blessica",
			"en-US-BlueNeural" => "Blue",
			"bg-BG-BorislavNeural" => "Borislav",
			"en-US-BrandonNeural" => "Brandon",
			"pt-BR-BrendaNeural" => "Brenda",
			"en-GB-Brian" => "Brian",
			"fr-FR-BrigitteNeural" => "Brigitte",
			"it-IT-CalimeroNeural" => "Calimero",
			"es-PE-CamilaNeural" => "Camila",
			"pt-BR-Camila" => "Camila",
			"es-MX-CandelaNeural" => "Candela",
			"it-IT-Carla" => "Carla",
			"es-HN-CarlosNeural" => "Carlos",
			"es-MX-CarlotaNeural" => "Carlota",
			"en-AU-CarlyNeural" => "Carly",
			"ro-RO-Carmen" => "Carmen",
			"it-IT-CataldoNeural" => "Cataldo",
			"es-CL-CatalinaNeural" => "Catalina",
			"es-MX-CecilioNeural" => "Cecilio",
			"fr-FR-CelesteNeural" => "Celeste",
			"fr-FR-Celine" => "Celine",
			"fr-CA-Chantal" => "Chantal",
			"fr-BE-CharlineNeural" => "Charline",
			"en-KE-ChilembaNeural" => "Chilemba",
			"da-DK-ChristelNeural" => "Christel",
			"de-DE-ChristophNeural" => "Christoph",
			"en-US-ChristopherNeural" => "Christopher",
			"en-CA-ClaraNeural" => "Clara",
			"fr-FR-ClaudeNeural" => "Claude",
			"nl-NL-ColetteNeural" => "Colette",
			"ga-IE-ColmNeural" => "Colm",
			"es-ES-Conchita" => "Conchita",
			"en-IE-ConnorNeural" => "Connor",
			"de-DE-ConradNeural" => "Conrad",
			"en-US-CoraNeural" => "Cora",
			"fr-FR-CoralieNeural" => "Coralie",
			"pt-PT-Cristiano" => "Cristiano",
			"es-MX-DaliaNeural" => "Dalia",
			"es-ES-DarioNeural" => "Dario",
			"ru-RU-DariyaNeural" => "Dariya",
			"en-AU-DarrenNeural" => "Darren",
			"sw-TZ-DaudiNeural" => "Daudi",
			"en-US-DavisNeural" => "Davis",
			"nl-BE-DenaNeural" => "Dena",
			"fr-FR-DeniseNeural" => "Denise",
			"it-IT-DiegoNeural" => "Diego",
			"jv-ID-DimasNeural" => "Dimas",
			"ru-RU-DmitryNeural" => "Dmitry",
			"pt-BR-DonatoNeural" => "Donato",
			"is-IS-Dora" => "Dora",
			"pt-PT-DuarteNeural" => "Duarte",
			"en-AU-DuncanNeural" => "Duncan",
			"es-AR-ElenaNeural" => "Elena",
			"es-ES-EliasNeural" => "Elias",
			"en-TZ-ElimuNeural" => "Elimu",
			"en-US-ElizabethNeural" => "Elizabeth",
			"de-DE-ElkeNeural" => "Elke",
			"en-GB-ElliotNeural" => "Elliot",
			"fr-FR-EloiseNeural" => "Eloise",
			"it-IT-ElsaNeural" => "Elsa",
			"en-AU-ElsieNeural" => "Elsie",
			"es-ES-ElviraNeural" => "Elvira",
			"pt-BR-ElzaNeural" => "Elza",
			"tr-TR-EmelNeural" => "Emel",
			"ro-RO-EmilNeural" => "Emil",
			"es-DO-EmilioNeural" => "Emilio",
			"en-IE-EmilyNeural" => "Emily",
			"en-GB-Emma" => "Emma",
			"ca-ES-EnricNeural" => "Enric",
			"es-ES-Enrique" => "Enrique",
			"en-US-EricNeural" => "Eric",
			"es-ES-EstrellaNeural" => "Estrella",
			"en-GB-EthanNeural" => "Ethan",
			"lv-LV-EveritaNeural" => "Everita",
			"pl-PL-Ewa" => "Ewa",
			"en-NG-EzinneNeural" => "Ezinne",
			"pt-BR-FabioNeural" => "Fabio",
			"it-IT-FabiolaNeural" => "Fabiola",
			"fr-CH-FabriceNeural" => "Fabrice",
			"es-NI-FedericoNeural" => "Federico",
			"nl-NL-FennaNeural" => "Fenna",
			"pt-PT-FernandaNeural" => "Fernanda",
			"it-IT-FiammaNeural" => "Fiamma",
			"tr-TR-Filiz" => "Filiz",
			"nb-NO-FinnNeural" => "Finn",
			"pt-BR-FranciscaNeural" => "Francisca",
			"en-AU-FreyaNeural" => "Freya",
			"hr-HR-GabrijelaNeural" => "Gabrijela",
			"id-ID-GadisNeural" => "Gadis",
			"en-GB-Geraint" => "Geraint",
			"fr-BE-GerardNeural" => "Gerard",
			"es-MX-GerardoNeural" => "Gerardo",
			"it-IT-GianniNeural" => "Gianni",
			"it-IT-Giorgio" => "Giorgio",
			"pt-BR-GiovannaNeural" => "Giovanna",
			"de-DE-GiselaNeural" => "Gisela",
			"es-CO-GonzaloNeural" => "Gonzalo",
			"bs-BA-GoranNeural" => "Goran",
			"mt-MT-GraceNeural" => "Grace",
			"is-IS-GunnarNeural" => "Gunnar",
			"en-US-GuyNeural" => "Guy",
			"is-IS-GudrunNeural" => "Guðrún",
			"cy-GB-Gwyneth" => "Gwyneth",
			"ar-SA-HamedNeural" => "Hamed",
			"de-DE-Hans" => "Hans",
			"fi-FI-HarriNeural" => "Harri",
			"ne-NP-HemkalaNeural" => "Hemkala",
			"fr-FR-HenriNeural" => "Henri",
			"he-IL-HilaNeural" => "Hila",
			"sv-SE-HilleviNeural" => "Hillevi",
			"zh-HK-HiuGaaiNeural" => "HiuGaai",
			"zh-HK-HiuMaanNeural" => "HiuMaan",
			"vi-VN-HoaiMyNeural" => "HoaiMy",
			"en-GB-HollieNeural" => "Hollie",
			"zh-TW-HsiaoChenNeural" => "HsiaoChen",
			"zh-TW-HsiaoYuNeural" => "HsiaoYu",
			"pt-BR-HumbertoNeural" => "Humberto",
			"sq-AL-IlirNeural" => "Ilir",
			"en-TZ-ImaniNeural" => "Imani",
			"it-IT-ImeldaNeural" => "Imelda",
			"ko-KR-InJoonNeural" => "InJoon",
			"pt-PT-Ines" => "Ines",
			"de-AT-IngridNeural" => "Ingrid",
			"es-ES-IreneNeural" => "Irene",
			"it-IT-IrmaNeural" => "Irma",
			"it-IT-IsabellaNeural" => "Isabella",
			"nb-NO-IselinNeural" => "Iselin",
			"en-US-Ivy" => "Ivy",
			"pl-PL-Jacek" => "Jacek",
			"en-US-JacobNeural" => "Jacob",
			"fr-FR-JacquelineNeural" => "Jacqueline",
			"su-ID-JajangNeural" => "Jajang",
			"en-PH-JamesNeural" => "James",
			"de-CH-JanNeural" => "Jan",
			"pl-PL-Jan" => "Jan",
			"en-US-JaneNeural" => "Jane",
			"en-US-JasonNeural" => "Jason",
			"es-GQ-JavierNeural" => "Javier",
			"fr-CA-JeanNeural" => "Jean",
			"en-US-JennyNeural" => "Jenny",
			"en-US-JennyMultilingualNeural" => "Jenny Multilingual",
			"en-US-JennyMultilingualV2Neural" => "Jenny Multilingual V2",
			"fr-FR-JennyMultilingualV2Neural" => "Jenny(Multilingue)",
			"vi-VN-JennyMultilingualV2Neural" => "Jenny(Đa ngôn ngữ)",
			"da-DK-JeppeNeural" => "Jeppe",
			"fr-FR-JeromeNeural" => "Jerome",
			"ca-ES-JoanaNeural" => "Joana",
			"en-US-Joanna" => "Joanna",
			"en-AU-JoanneNeural" => "Joanne",
			"en-US-Joey" => "Joey",
			"de-AT-JonasNeural" => "Jonas",
			"es-MX-JorgeNeural" => "Jorge",
			"mt-MT-JosephNeural" => "Joseph",
			"fr-FR-JosephineNeural" => "Josephine",
			"es-CR-JuanNeural" => "Juan",
			"pt-BR-JulioNeural" => "Julio",
			"en-US-Justin" => "Justin",
			"bg-BG-KalinaNeural" => "Kalina",
			"es-PR-KarinaNeural" => "Karina",
			"is-IS-Karl" => "Karl",
			"es-HN-KarlaNeural" => "Karla",
			"de-DE-KasperNeural" => "Kasper",
			"de-DE-KatjaNeural" => "Katja",
			"en-AU-KenNeural" => "Ken",
			"en-US-Kendra" => "Kendra",
			"et-EE-KertNeural" => "Kert",
			"en-US-Kevin" => "Kevin",
			"de-DE-KillianNeural" => "Killian",
			"en-AU-KimNeural" => "Kim",
			"en-US-Kimberly" => "Kimberly",
			"de-DE-KlarissaNeural" => "Klarissa",
			"de-DE-KlausNeural" => "Klaus",
			"es-ES-LaiaNeural" => "Laia",
			"es-MX-LarissaNeural" => "Larissa",
			"en-ZA-LeahNeural" => "Leah",
			"pt-BR-LeilaNeural" => "Leila",
			"de-CH-LeniNeural" => "Leni",
			"lt-LT-LeonasNeural" => "Leonas",
			"pt-BR-LeticiaNeural" => "Leticia",
			"es-ES-LiaNeural" => "Lia",
			"en-CA-LiamNeural" => "Liam",
			"en-GB-LibbyNeural" => "Libby",
			"es-MX-LibertoNeural" => "Liberto",
			"it-IT-LisandroNeural" => "Lisandro",
			"nb-NO-Liv" => "Liv",
			"es-SV-LorenaNeural" => "Lorena",
			"es-CL-LorenzoNeural" => "Lorenzo",
			"nl-NL-Lotte" => "Lotte",
			"de-DE-LouisaNeural" => "Louisa",
			"es-ES-Lucia" => "Lucia",
			"es-MX-LucianoNeural" => "Luciano",
			"es-EC-LuisNeural" => "Luis",
			"sk-SK-LukasNeural" => "Lukas",
			"en-ZA-LukeNeural" => "Luke",
			"en-SG-LunaNeural" => "Luna",
			"es-US-Lupe" => "Lupe",
			"fr-FR-Lea" => "Léa",
			"nl-NL-MaartenNeural" => "Maarten",
			"hi-IN-MadhurNeural" => "Madhur",
			"uz-UZ-MadinaNeural" => "Madina",
			"da-DK-Mads" => "Mads",
			"en-GB-MaisieNeural" => "Maisie",
			"de-DE-MajaNeural" => "Maja",
			"pl-PL-Maja" => "Maja",
			"es-CU-ManuelNeural" => "Manuel",
			"pt-BR-ManuelaNeural" => "Manuela",
			"es-BO-MarceloNeural" => "Marcelo",
			"pl-PL-MarekNeural" => "Marek",
			"es-PA-MargaritaNeural" => "Margarita",
			"es-MX-MarinaNeural" => "Marina",
			"es-PY-MarioNeural" => "Mario",
			"de-DE-Marlene" => "Marlene",
			"es-GT-MartaNeural" => "Marta",
			"es-CR-MariaNeural" => "María",
			"es-UY-MateoNeural" => "Mateo",
			"fr-FR-Mathieu" => "Mathieu",
			"en-US-Matthew" => "Matthew",
			"sv-SE-MattiasNeural" => "Mattias",
			"fr-FR-MauriceNeural" => "Maurice",
			"ru-RU-Maxim" => "Maxim",
			"en-GB-MiaNeural" => "Mia",
			"es-MX-Mia" => "Mia",
			"en-US-MichelleNeural" => "Michelle",
			"es-US-Miguel" => "Miguel",
			"en-NZ-MitchellNeural" => "Mitchell",
			"te-IN-MohanNeural" => "Mohan",
			"en-NZ-MollyNeural" => "Molly",
			"en-US-MonicaNeural" => "Monica",
			"so-SO-MuuseNeural" => "Muuse",
			"da-DK-Naja" => "Naja",
			"vi-VN-NamMinhNeural" => "NamMinh",
			"en-US-NancyNeural" => "Nancy",
			"en-AU-NatashaNeural" => "Natasha",
			"en-IN-NeerjaNeural" => "Neerja",
			"en-AU-NeilNeural" => "Neil",
			"el-GR-NestorasNeural" => "Nestoras",
			"cy-GB-NiaNeural" => "Nia",
			"sr-Latn-RS-NicholasNeural" => "Nicholas",
			"pt-BR-NicolauNeural" => "Nicolau",
			"en-AU-Nicole" => "Nicole",
			"es-ES-NilNeural" => "Nil",
			"my-MM-NilarNeural" => "Nilar",
			"lv-LV-NilsNeural" => "Nils",
			"th-TH-NiwatNeural" => "Niwat",
			"en-GB-NoahNeural" => "Noah",
			"hu-HU-NoemiNeural" => "Noemi",
			"fi-FI-NooraNeural" => "Noora",
			"es-MX-NuriaNeural" => "Nuria",
			"en-GB-OliverNeural" => "Oliver",
			"en-AU-Olivia" => "Olivia",
			"en-GB-OliviaNeural" => "Olivia",
			"lt-LT-OnaNeural" => "Ona",
			"ga-IE-OrlaNeural" => "Orla",
			"ms-MY-OsmanNeural" => "Osman",
			"ta-IN-PallaviNeural" => "Pallavi",
			"it-IT-PalmiraNeural" => "Palmira",
			"es-US-PalomaNeural" => "Paloma",
			"es-VE-PaolaNeural" => "Paola",
			"es-MX-PelayoNeural" => "Pelayo",
			"es-US-Penelope" => "Penelope",
			"nb-NO-PernilleNeural" => "Pernille",
			"sl-SI-PetraNeural" => "Petra",
			"it-IT-PierinaNeural" => "Pierina",
			"en-IN-PrabhatNeural" => "Prabhat",
			"th-TH-PremwadeeNeural" => "Premwadee",
			"sw-KE-RafikiNeural" => "Rafiki",
			"de-DE-RalfNeural" => "Ralf",
			"es-DO-RamonaNeural" => "Ramona",
			"pt-PT-RaquelNeural" => "Raquel",
			"en-IN-Raveena" => "Raveena",
			"sw-TZ-RehemaNeural" => "Rehema",
			"es-MX-RenataNeural" => "Renata",
			"pt-BR-Ricardo" => "Ricardo",
			"it-IT-RinaldoNeural" => "Rinaldo",
			"es-PA-RobertoNeural" => "Roberto",
			"es-SV-RodrigoNeural" => "Rodrigo",
			"en-US-RogerNeural" => "Roger",
			"gl-ES-RoiNeural" => "Roi",
			"sl-SI-RokNeural" => "Rok",
			"en-PH-RosaNeural" => "Rosa",
			"nl-NL-Ruben" => "Ruben",
			"en-AU-Russell" => "Russell",
			"en-GB-RyanNeural" => "Ryan",
			"en-US-RyanMultilingualNeural" => "Ryan Multilingual",
			"gl-ES-SabelaNeural" => "Sabela",
			"ne-NP-SagarNeural" => "Sagar",
			"en-US-Salli" => "Salli",
			"ar-EG-SalmaNeural" => "Salma",
			"es-CO-SalomeNeural" => "Salome",
			"en-HK-SamNeural" => "Sam",
			"en-US-SaraNeural" => "Sara",
			"uz-UZ-SardorNeural" => "Sardor",
			"es-ES-SaulNeural" => "Saul",
			"es-VE-SebastianNeural" => "Sebastián",
			"fi-FI-SelmaNeural" => "Selma",
			"ko-KR-Seoyeon" => "Seoyeon",
			"ar-EG-ShakirNeural" => "Shakira",
			"te-IN-ShrutiNeural" => "Shruti",
			"jv-ID-SitiNeural" => "Siti",
			"es-BO-SofiaNeural" => "Sofia",
			"sv-SE-SofieNeural" => "Sofie",
			"en-GB-SoniaNeural" => "Sonia",
			"sr-Latn-RS-SophieNeural" => "Sophie",
			"hr-HR-SreckoNeural" => "Srecko",
			"en-US-SteffanNeural" => "Steffan",
			"ko-KR-SunHiNeural" => "SunHi",
			"ru-RU-SvetlanaNeural" => "Svetlana",
			"hi-IN-SwaraNeural" => "Swara",
			"fr-CA-SylvieNeural" => "Sylvie",
			"hu-HU-TamasNeural" => "Tamas",
			"es-PY-TaniaNeural" => "Tania",
			"de-DE-TanjaNeural" => "Tanja",
			"ru-RU-Tatyana" => "Tatyana",
			"es-ES-TeoNeural" => "Teo",
			"es-GQ-TeresaNeural" => "Teresa",
			"zu-ZA-ThandoNeural" => "Thando",
			"zu-ZA-ThembaNeural" => "Themba",
			"my-MM-ThihaNeural" => "Thiha",
			"en-GB-ThomasNeural" => "Thomas",
			"en-AU-TimNeural" => "Tim",
			"en-AU-TinaNeural" => "Tina",
			"es-AR-TomasNeural" => "Tomas",
			"en-US-TonyNeural" => "Tony",
			"es-ES-TrianaNeural" => "Triana",
			"su-ID-TutiNeural" => "Tuti",
			"so-SO-UbaxNeural" => "Ubax",
			"es-UY-ValentinaNeural" => "Valentina",
			"pt-BR-ValerioNeural" => "Valerio",
			"ta-IN-ValluvarNeural" => "Valluvar",
			"es-ES-VeraNeural" => "Vera",
			"bs-BA-VesnaNeural" => "Vesna",
			"de-DE-Vicki" => "Vicki",
			"sk-SK-ViktoriaNeural" => "Viktoria",
			"pt-BR-Vitoria" => "Vitoria",
			"cs-CZ-VlastaNeural" => "Vlasta",
			"es-PR-VictorNeural" => "Víctor",
			"zh-HK-WanLungNeural" => "WanLung",
			"en-SG-WayneNeural" => "Wayne",
			"af-ZA-WillemNeural" => "Willem",
			"en-AU-WilliamNeural" => "William",
			"zh-CN-XiaoxiaoNeural" => "Xiaoxiao",
			"zh-CN-XiaoyouNeural" => "Xiaoyou",
			"es-MX-YagoNeural" => "Yago",
			"en-HK-YanNeural" => "Yan",
			"pt-BR-YaraNeural" => "Yara",
			"ms-MY-YasminNeural" => "Yasmin",
			"es-NI-YolandaNeural" => "Yolanda",
			"zh-TW-YunJheNeural" => "YunJhe",
			"zh-CN-YunyangNeural" => "Yunyang",
			"zh-CN-YunyeNeural" => "Yunye",
			"fr-FR-YvesNeural" => "Yves",
			"fr-FR-YvetteNeural" => "Yvette",
			"ar-SA-ZariyahNeural" => "Zariyah",
			"ar-XA-Zeina" => "Zeina",
			"cmn-CN-Zhiyu" => "Zhiyu",
			"pl-PL-ZofiaNeural" => "Zofia",
			"sw-KE-ZuriNeural" => "Zuri",
			"ar-XA-Wavenet-A" => "ar-XA-A",
			"ar-XA-Wavenet-B" => "ar-XA-B",
			"ar-XA-Wavenet-C" => "ar-XA-C",
			"bn-IN-Wavenet-A" => "bn-IN-A",
			"bn-IN-Wavenet-B" => "bn-IN-B",
			"cmn-CN-Wavenet-A" => "cmn-CN-A",
			"cmn-CN-Wavenet-B" => "cmn-CN-B",
			"cmn-CN-Wavenet-C" => "cmn-CN-C",
			"cmn-CN-Wavenet-D" => "cmn-CN-D",
			"cmn-TW-Wavenet-A" => "cmn-TW-A",
			"cmn-TW-Wavenet-B" => "cmn-TW-B",
			"cmn-TW-Wavenet-C" => "cmn-TW-C",
			"cs-CZ-Wavenet-A" => "cs-CZ-A",
			"da-DK-Wavenet-A" => "da-DK-A",
			"da-DK-Wavenet-C" => "da-DK-C",
			"da-DK-Wavenet-D" => "da-DK-D",
			"da-DK-Wavenet-E" => "da-DK-E",
			"de-DE-Wavenet-A" => "de-DE-A",
			"de-DE-Wavenet-B" => "de-DE-B",
			"de-DE-Wavenet-C" => "de-DE-C",
			"de-DE-Wavenet-D" => "de-DE-D",
			"de-DE-Wavenet-E" => "de-DE-E",
			"de-DE-Wavenet-F" => "de-DE-F",
			"el-GR-Wavenet-A" => "el-GR-A",
			"en-AU-Wavenet-A" => "en-AU-A",
			"en-AU-Neural2-A" => "en-AU-A2",
			"en-AU-Wavenet-B" => "en-AU-B",
			"en-AU-Wavenet-C" => "en-AU-C",
			"en-AU-Neural2-C" => "en-AU-C2",
			"en-GB-Wavenet-A" => "en-GB-A",
			"en-GB-Neural2-A" => "en-GB-A2",
			"en-GB-Wavenet-B" => "en-GB-B",
			"en-GB-Wavenet-C" => "en-GB-C",
			"en-GB-Neural2-C" => "en-GB-C2",
			"en-GB-Wavenet-D" => "en-GB-D",
			"en-GB-Wavenet-F" => "en-GB-F",
			"en-IN-Wavenet-A" => "en-IN-A",
			"en-IN-Wavenet-B" => "en-IN-B",
			"en-IN-Wavenet-C" => "en-IN-C",
			"en-IN-Wavenet-D" => "en-IN-D",
			"en-US-Wavenet-A" => "en-US-A",
			"en-US-Neural2-A" => "en-US-A2",
			"en-US-Wavenet-B" => "en-US-B",
			"en-US-Wavenet-C" => "en-US-C",
			"en-US-Wavenet-D" => "en-US-D",
			"en-US-Wavenet-E" => "en-US-E",
			"en-US-Wavenet-F" => "en-US-F",
			"es-ES-Standard-A" => "es-ES-A",
			"es-US-Wavenet-A" => "es-US-A",
			"es-US-Neural2-A" => "es-US-A2",
			"es-US-Wavenet-B" => "es-US-B",
			"es-US-Wavenet-C" => "es-US-C",
			"es-US-Neural2-C" => "es-US-C2",
			"fi-FI-Wavenet-A" => "fi-FI-A",
			"fil-PH-Wavenet-A" => "fil-PH-A",
			"fil-PH-Wavenet-B" => "fil-PH-B",
			"fil-PH-Wavenet-C" => "fil-PH-C",
			"fil-PH-Wavenet-D" => "fil-PH-D",
			"fr-CA-Wavenet-A" => "fr-CA-A",
			"fr-CA-Wavenet-B" => "fr-CA-B",
			"fr-CA-Wavenet-C" => "fr-CA-C",
			"fr-CA-Wavenet-D" => "fr-CA-D",
			"fr-FR-Wavenet-A" => "fr-FR-A",
			"fr-FR-Wavenet-B" => "fr-FR-B",
			"fr-FR-Wavenet-C" => "fr-FR-C",
			"fr-FR-Wavenet-D" => "fr-FR-D",
			"fr-FR-Wavenet-E" => "fr-FR-E",
			"hi-IN-Wavenet-A" => "hi-IN-A",
			"hi-IN-Wavenet-B" => "hi-IN-B",
			"hi-IN-Wavenet-C" => "hi-IN-C",
			"hi-IN-Wavenet-D" => "hi-IN-D",
			"hu-HU-Wavenet-A" => "hu-HU-A",
			"id-ID-Wavenet-A" => "id-ID-A",
			"id-ID-Wavenet-B" => "id-ID-B",
			"id-ID-Wavenet-C" => "id-ID-C",
			"id-ID-Wavenet-D" => "id-ID-D",
			"it-IT-Wavenet-A" => "it-IT-A",
			"it-IT-Wavenet-B" => "it-IT-B",
			"it-IT-Wavenet-C" => "it-IT-C",
			"it-IT-Wavenet-D" => "it-IT-D",
			"ko-KR-Wavenet-A" => "ko-KR-A",
			"ko-KR-Wavenet-B" => "ko-KR-B",
			"ko-KR-Wavenet-C" => "ko-KR-C",
			"ko-KR-Wavenet-D" => "ko-KR-D",
			"nb-NO-Wavenet-A" => "nb-NO-A",
			"nb-NO-Wavenet-B" => "nb-NO-B",
			"nb-NO-Wavenet-C" => "nb-NO-C",
			"nb-NO-Wavenet-D" => "nb-NO-D",
			"nb-NO-Wavenet-E" => "nb-NO-E",
			"nl-NL-Wavenet-A" => "nl-NL-A",
			"nl-NL-Wavenet-B" => "nl-NL-B",
			"nl-NL-Wavenet-C" => "nl-NL-C",
			"nl-NL-Wavenet-D" => "nl-NL-D",
			"nl-NL-Wavenet-E" => "nl-NL-E",
			"pl-PL-Wavenet-A" => "pl-PL-A",
			"pl-PL-Wavenet-B" => "pl-PL-B",
			"pl-PL-Wavenet-C" => "pl-PL-C",
			"pl-PL-Wavenet-D" => "pl-PL-D",
			"pl-PL-Wavenet-E" => "pl-PL-E",
			"pt-BR-Wavenet-A" => "pt-BR-A",
			"pt-PT-Wavenet-A" => "pt-PT-A",
			"pt-PT-Wavenet-B" => "pt-PT-B",
			"pt-PT-Wavenet-C" => "pt-PT-C",
			"pt-PT-Wavenet-D" => "pt-PT-D",
			"ru-RU-Wavenet-A" => "ru-RU-A",
			"ru-RU-Wavenet-B" => "ru-RU-B",
			"ru-RU-Wavenet-C" => "ru-RU-C",
			"ru-RU-Wavenet-D" => "ru-RU-D",
			"ru-RU-Wavenet-E" => "ru-RU-E",
			"sk-SK-Wavenet-A" => "sk-SK-A",
			"sv-SE-Wavenet-A" => "sv-SE-A",
			"th-TH-Standard-A" => "th-TH-A",
			"tr-TR-Wavenet-A" => "tr-TR-A",
			"tr-TR-Wavenet-B" => "tr-TR-B",
			"tr-TR-Wavenet-C" => "tr-TR-C",
			"tr-TR-Wavenet-D" => "tr-TR-D",
			"tr-TR-Wavenet-E" => "tr-TR-E",
			"uk-UA-Wavenet-A" => "uk-UA-A",
			"vi-VN-Wavenet-A" => "vi-VN-A",
			"vi-VN-Wavenet-B" => "vi-VN-B",
			"vi-VN-Wavenet-C" => "vi-VN-C",
			"vi-VN-Wavenet-D" => "vi-VN-D",
			"kk-KZ-AigulNeural" => "Айгүл",
			"mk-MK-AleksandarNeural" => "Александар",
			"mn-MN-BataaNeural" => "Батаа",
			"kk-KZ-DauletNeural" => "Дәулет",
			"mn-MN-YesuiNeural" => "Есүй",
			"mk-MK-MarijaNeural" => "Марија",
			"sr-RS-NicholasNeural" => "Никола",
			"uk-UA-OstapNeural" => "Остап",
			"uk-UA-PolinaNeural" => "Поліна",
			"sr-RS-SophieNeural" => "Софија",
			"hy-AM-AnahitNeural" => "Անահիտ",
			"hy-AM-HaykNeural" => "Հայկ",
			"ar-LY-OmarNeural" => "أحمد",
			"ar-SY-AmanyNeural" => "أماني",
			"ar-QA-AmalNeural" => "أمل",
			"ar-DZ-AminaNeural" => "أمينة",
			"ar-DZ-IsmaelNeural" => "إسماعيل",
			"ar-LY-ImanNeural" => "إيمان",
			"ur-PK-AsadNeural" => "اسد",
			"ar-IQ-BasselNeural" => "باسل",
			"ar-JO-TaimNeural" => "تيم",
			"ar-MA-JamalNeural" => "جمال",
			"ar-AE-HamdanNeural" => "حمدان",
			"fa-IR-DilaraNeural" => "دلارا",
			"ar-LB-RamiNeural" => "رامي",
			"ar-IQ-RanaNeural" => "رنا",
			"ar-TN-ReemNeural" => "ريم",
			"ur-IN-SalmanNeural" => "سلمان",
			"ar-JO-SanaNeural" => "سناء",
			"ar-YE-SalehNeural" => "صالح",
			"ar-OM-AyshaNeural" => "عائشة",
			"ar-OM-AbdullahNeural" => "عبدالله",
			"ur-PK-UzmaNeural" => "عظمیٰ",
			"ar-BH-AliNeural" => "علي",
			"ar-AE-FatimaNeural" => "فاطمة",
			"fa-IR-FaridNeural" => "فرید",
			"ar-KW-FahedNeural" => "فهد",
			"ps-AF-LatifaNeural" => "لطيفه",
			"ar-SY-LaithNeural" => "ليث",
			"ar-BH-LailaNeural" => "ليلى",
			"ar-LB-LaylaNeural" => "ليلى",
			"ar-YE-MaryamNeural" => "مريم",
			"ar-QA-MoazNeural" => "معاذ",
			"ar-MA-MounaNeural" => "منى",
			"ar-KW-NouraNeural" => "نورا",
			"ar-TN-HediNeural" => "هادي",
			"ur-IN-GulNeural" => "گل",
			"mr-IN-AarohiNeural" => "आरोही",
			"mr-IN-ManoharNeural" => "मनोहर",
			"bn-IN-TanishaaNeural" => "তানিশা",
			"bn-BD-NabanitaNeural" => "নবনীতা",
			"bn-BD-PradeepNeural" => "প্রদ্বীপ",
			"bn-IN-BashkarNeural" => "ভাস্কর",
			"gu-IN-DhwaniNeural" => "ધ્વની",
			"gu-IN-NiranjanNeural" => "નિરંજન",
			"ta-SG-AnbuNeural" => "அன்பு",
			"ta-MY-KaniNeural" => "கனி",
			"ta-LK-KumarNeural" => "குமார்",
			"ta-LK-SaranyaNeural" => "சரண்யா",
			"ta-MY-SuryaNeural" => "சூர்யா",
			"ta-SG-VenbaNeural" => "வெண்பா",
			"kn-IN-GaganNeural" => "ಗಗನ್",
			"kn-IN-SapnaNeural" => "ಸಪ್ನಾ",
			"ml-IN-MidhunNeural" => "മിഥുൻ",
			"ml-IN-SobhanaNeural" => "ശോഭന",
			"si-LK-ThiliniNeural" => "තිළිණි",
			"si-LK-SameeraNeural" => "සමීර",
			"lo-LA-ChanthavongNeural" => "ຈັນທະວົງ",
			"lo-LA-KeomanyNeural" => "ແກ້ວມະນີ",
			"ka-GE-GiorgiNeural" => "გიორგი",
			"ka-GE-EkaNeural" => "ეკა",
			"am-ET-MekdesNeural" => "መቅደስ",
			"am-ET-AmehaNeural" => "አምሀ",
			"km-KH-PisethNeural" => "ពិសិដ្ឋ",
			"km-KH-SreymomNeural" => "ស្រីមុំ",
			"ja-JP-AoiNeural" => "あおい",
			"ja-JP-KeitaNeural" => "けいた",
			"ja-JP-ShioriNeural" => "しおり",
			"ja-JP-Takumi" => "たくみ",
			"ja-JP-Takumi-NTTS" => "たくみ(高低なし)",
			"ja-JP-DaichiNeural" => "だいち",
			"ja-JP-NaokiNeural" => "なおき",
			"ja-JP-NanamiNeural" => "ななみ",
			"ja-JP-NanamiNeural_cs" => "ななみ(案内)",
			"ja-JP-MayuNeural" => "まゆ",
			"ja-JP-Mizuki" => "みずき",
			"ja-JP-Wavenet-C" => "アナウンサー（A）",
			"ja-JP-Wavenet-D" => "アナウンサー（B）",
			"ja-JP-JennyMultilingualV2Neural" => "ジェニー（多言語）",
			"ja-JP-Wavenet-A" => "ロボット",
			"zh-CN-YunjianNeural" => "云健",
			"wuu-CN-YunzheNeural" => "云哲 (吴语)",
			"zh-CN-YunxiaNeural" => "云夏",
			"zh-CN-YunxiNeural" => "云希",
			"zh-CN-sichuan-YunxiNeural" => "云希 (四川方言)",
			"yue-CN-YunSongNeural" => "云松 (粤语)",
			"zh-CN-YunfengNeural" => "云枫",
			"zh-CN-YunzeNeural" => "云泽",
			"zh-CN-henan-YundengNeural" => "云登 (河南方言)",
			"zh-CN-YunhaoNeural" => "云皓",
			"zh-CN-shandong-YunxiangNeural" => "云翔 (山东方言)",
			"zh-CN-XiaoyiNeural" => "晓伊",
			"zh-CN-liaoning-XiaobeiNeural" => "晓北 (辽宁方言)",
			"zh-CN-XiaoshuangNeural" => "晓双",
			"zh-CN-XiaomoNeural" => "晓墨",
			"zh-CN-shaanxi-XiaoniNeural" => "晓妮 (陕西方言)",
			"wuu-CN-XiaotongNeural" => "晓彤 (吴语)",
			"yue-CN-XiaoMinNeural" => "晓敏 (粤语)",
			"zh-CN-XiaomengNeural" => "晓梦",
			"zh-CN-XiaohanNeural" => "晓涵",
			"zh-CN-XiaozhenNeural" => "晓甄",
			"zh-CN-XiaoruiNeural" => "晓睿",
			"zh-CN-XiaoqiuNeural" => "晓秋",
			"zh-CN-XiaoxuanNeural" => "晓萱",
			"zh-CN-XiaochenNeural" => "晓辰",
			"zh-CN-XiaoyanNeural" => "晓颜",
			"zh-CN-JennyMultilingualV2Neural" => "珍妮多(语种)",
			"ja-JP-Wavenet-B" => "音声アシスタント",
			"ko-KR-GookMinNeural" => "국민",
			"ko-KR-BongJinNeural" => "봉진",
			"ko-KR-SeoHyeonNeural" => "서현",
			"ko-KR-SoonBokNeural" => "순복",
			"ko-KR-YuJinNeural" => "유진",
			"ko-KR-JennyMultilingualV2Neural" => "제니 (다국어)",
			"ko-KR-JiMinNeural" => "지민",
		);
	}//
}
