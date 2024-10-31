<?php
class ONDOKUSAN_Hook {

	public function __construct() {
		add_action( 'save_post', [ $this, 'read_ondoku' ] );
		add_action( 'admin_notices', array( $this, 'show_admin_message' ) );
		add_filter( 'the_content', [ $this, 'show_ondoku' ] );
	}

	public $admin_message;
	public $admin_status;
	/*
	 * 音読データ読み込み
	 */

	public function read_ondoku( $post_id ) {



		$post = get_post( $post_id );

		//自動保存と非公開では音読データを作らない
		if ( wp_is_post_revision( $post_id ) || $post->post_status !== 'publish' )
			return;

		//トークンが無い場合は音読データを作らない
		$option = get_option( 'ondokusan_settings' , false );

		if( is_array($option) ){

			if( empty($option['token']) ){
				delete_post_meta( $post_id, 'ondoku_mp3_url' );
				$this->admin_message = 0;
				$this->admin_status = 0;
				add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				return;
			}
			if( !$option['enable'] ){
				delete_post_meta( $post_id, 'ondoku_mp3_url' );
				$this->admin_message = 1;
				$this->admin_status = 1;
				add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				return;
			}

		}

		// APIリクエスト
		$params['text'] = preg_replace('/\n|\r|\r\n/', '', strip_tags( $post->post_content ) );

		$params['url'] = ONDOKUSAN_API;

		$params['body'] = array(
			'pitch' => $option['pitch'],
			'speed' => $option['speed'],
			'text' => $params['text'],
			'voice' => $option['voice']
		);

		$params['headers'] = array(
			'token' => $option['token'],
			'content-Type' => 'application/json;'//application/json; charset=UTF-8
		);

		try {


			$data = wp_remote_post($params['url'] , array(
				'method' => 'POST',
				'headers' => $params['headers'],
				//'timeout'     => 60,
				//'redirection' => 5,
				//'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify' => false,
				'body' => json_encode($params['body'])
			));

			if( is_wp_error( $data ) ) {
				return false;
			}


			/*['body']部分を抜き取り*/
			$response = wp_remote_retrieve_body( $data );


			$result = json_decode( $response, true );

			/*レスポンスがメッセージの場合*/
			if( !is_array($result) ){
				/*['code']部分を抜き取り*/
				$code = wp_remote_retrieve_response_code( $data );
				if( $code === 400 ){
					delete_post_meta( $post_id, 'ondoku_mp3_url' );
					$this->admin_message = 1;
					$this->admin_status = 1;
					$option['enable'] = false;
					update_option( 'ondokusan_settings' , $option);
					add_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
				}
			}

			if ( isset( $result['url'] ) ) {
				update_post_meta( $post_id, 'ondoku_mp3_url', esc_url_raw( wp_unslash( $result['url'] ) ) );
			}else{
				delete_post_meta( $post_id, 'ondoku_mp3_url' );
			}
		} catch (\Exception $e) {
			error_log( date_i18n( 'Y-m-d H:i' ) . " ".__('API error','ondoku3')."\n", 3, ONDOKUSAN_DIR . '/error.log' );
		}


	}

	/*
	 * 音読データ表示
	 */
	public function show_ondoku( $content ) {
		$ondoku_mp3_url = get_post_meta( get_the_ID(), 'ondoku_mp3_url', true );
		if ( ! empty( $ondoku_mp3_url ) ) {
			/*autioタグにheight:auto指定のテーマで非表示になるためmin-heightをあてる(Chrome以外のブラウザは問題無し)*/
			//$content = sprintf( '<audio controls style="min-height:54px;width:100%"><source src="%s" type="audio/mpeg"></audio>%s', esc_url_raw( $ondoku_mp3_url ), $content );PHP8で動作せず
			$content = '<audio controls style="min-height:54px;width:100%"><source src="'.esc_url_raw( $ondoku_mp3_url ).'" type="audio/mpeg"></audio>'.$content;
		}
		return $content;
	}

	/*
	 * 投稿画面に警告表示リダイレクト設定
	 */
	public function add_notice_query_var( $location ) {
		if($this->admin_message === '') return;
		remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );
		return add_query_arg( array( 'ONDOKUSAN_MESSAGE' => $this->admin_message,'ONDOKUSAN_STATUS' => $this->admin_status ), $location );
	}
	/*
	 * 投稿画面に警告表示
	 */
	public function show_admin_message(){
		if ( ! isset( $_GET['ONDOKUSAN_MESSAGE'] ) ) {
			return;
		}
		$message = array(
			esc_html__("Please set Ondoku's token.",'ondoku3'),
			esc_html__('The entered Ondoku token is invalid.','ondoku3'),
		);
		$status = array(
			'warning',
			'error',
		);

		?>
		<div class="notice notice-<?php echo $status[$_GET['ONDOKUSAN_STATUS']]; ?>">
			<p><?php echo $message[$_GET['ONDOKUSAN_MESSAGE']]; ?><a href="<?php echo esc_url( admin_url( 'options-general.php?page=ondokusan_setting_page' )); ?>" target="_blank"><?php esc_html_e('Click here for settings.','ondoku3') ?></a></p>
		</div>
		<?php
	}

}
