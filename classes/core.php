<?php
class ONDOKUSAN {

	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );

		add_action( 'admin_notices', [ $this, 'notice' ] );

		/*プラグイン有効化時 オプション初期化*/
		/*プラグイン有効化時 設定ページへリダイレクト*/
		register_activation_hook(ONDOKUSAN, array( $this, 'activation' ) );
		add_action('admin_init', array( $this, 'activation_redirect') );
	}

	/*
	 * 初期定義
	 */
	public function init() {

		$hook = new ONDOKUSAN_Hook();

		if ( is_admin() ) {
			if ( wp_doing_ajax() ) {
			} else {
				$admin = new ONDOKUSAN_Setting();
			}
		}
	}

	/*翻訳ファイル*/
	public function plugins_loaded() {
		load_plugin_textdomain( 'ondoku3', false, dirname( plugin_basename( ONDOKUSAN ) ) .'/languages/' );
	}

	/*プラグイン有効化*/
	public function activation(){
		update_option('ondokusan_do_activation_redirect', true);
	}
	/*プラグイン有効化時にリダイレクト*/
	public function activation_redirect() {
		if (get_option('ondokusan_do_activation_redirect', false)) {
			delete_option('ondokusan_do_activation_redirect');
			wp_redirect( admin_url( 'admin.php?page=ondokusan_setting_page') );
			exit;
		}
	}

	/*管理画面警告*/
	public function notice(){


		global $pagenow;

		/*投稿編集もしくは新規投稿ページのときは専用のメッセージを表示させる*/
		if($pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
			return;
		}

		$class = '';
		$message = '';
		$option = get_option( 'ondokusan_settings' , false );

		if( isset($option['token']) && empty($option['token']) || isset($option['enable']) && !$option['enable'] ){
			$class = 'warning';
			$message = esc_html__("Please set Ondoku's token.",'ondoku3');
		}

		if(!isset($option['enable'])){
			$option['enable'] = $this->token_check();
			update_option( 'ondokusan_settings' , $option);
		}

		if(!$option['enable']){
			$class = 'error';
			$message = esc_html__("The entered Ondoku token is invalid.",'ondoku3');
		}

		if($message !== ''){
			?>
			<div class="notice notice-<?php echo $class; ?>">
				<p><?php echo $message; ?><a href="<?php echo esc_url( admin_url( 'options-general.php?page=ondokusan_setting_page' )); ?>" target="_blank"><?php esc_html_e('Click here for settings.','ondoku3') ?></a></p>
			</div>
			<?php
		}
	}

	/*トークンチェック*/
	public function token_check(){

		$option = get_option( 'ondokusan_settings' , false );


		$params['url'] = ONDOKUSAN_API;
		$params['body'] = array(
			'pitch' => $option['pitch'],
			'speed' => $option['speed'],
			'text' => 'Hi',
			'voice' => $option['voice']
		);
		$params['headers'] = array(
			'token' => $option['token'],
			'content-Type' => 'application/json;'
		);
		$data = wp_remote_post($params['url'] , array(
			'method' => 'POST',
			'headers' => $params['headers'],
			'httpversion' => '1.0',
			'sslverify' => false,
			'body' => json_encode($params['body'])
		));

		if( is_wp_error( $data ) ) {
			return false;
		}



		/*['code']部分を抜き取り*/
		$code = wp_remote_retrieve_response_code( $data );
		if( $code === 400 ){
			return false;
		}

		/*['body']部分を抜き取り*/
		$response = wp_remote_retrieve_body( $data );

		$result = json_decode( $response, true );

		/*レスポンスがメッセージの場合*/
		if( !is_array($result) ){
			return false;
		}


		return true;
	}
}