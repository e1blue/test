<?php

Class BlipfotoAPI {
	
	public $key;
	protected $secret;
	public $userToken;
	public $userSecret;

	protected static $version 	= 3;
	protected static $timeDelta = null;

	protected $requiresSSL 		= Array("token");

	const AUTH_NONE = 0;
	const AUTH_APP  = 1;
	const AUTH_USER = 2;

	/**
	* コンストラクタ
	* @param String $key … APIキー
	* @param String $secret … シークレットアクセスコード
	* @param String $userToken (オプション) … ユーザトークン
	* @param String $userSecret (オプション) … ユーザシークレット
	**/
	public function BlipfotoAPI($key, $secret, $userToken = '', $userSecret = '') {
		$this->key = $key;
		$this->secret = $secret;
		$this->userToken = $userToken;
		$this->userSecret = $userSecret;
	}

	/**
	* APIバージョン
	* @return Int
	**/
	public function version() {
		return self::$version;
	}

	/**
	* API-URL作成
	* @param String $resource … リソース名
	* @return String …  URL
	**/
	protected function buildURL($resource) {
		$url = 'http' . (in_array($resource, $this->requiresSSL) ? 's' : '') . '://api.blipfoto.com';
		return $url . '/v' . $this->version() . '/' . $resource . '.json';
	}

	/**
	* API-URLパラメータ作成
	* @param Array $params … パラメータの連想配列
	* @return Array … リクエストパラメータ
	**/
	protected function buildParams($params, $auth) {
		$params['api_key'] = $this->key;

		if ($auth) {
			$nonce = $this->getNonce();
			$timestamp = $this->getTimestamp();

			if ($auth == self::AUTH_APP) {
				$token = '';
				$secret = $this->secret;
			} else {
				$token = $this->userToken;
				$secret = $this->userSecret;
			}

			$params['timestamp'] = $timestamp;
			$params['nonce'] = $nonce;
			$params['token'] = $token;
			$params['signature'] = md5($timestamp . $nonce . $token . $secret);
		}

		return $params;
	}

	/**
	* ランダム文字列作成
	* @return String
	**/
	protected function getNonce() {
		return str_shuffle(md5(uniqid(mt_rand(), true)));
	}

	/**
	* APIに同期したタイムスタンプ
	* @return String
	**/
	protected function getTimestamp(){

		$now = time();

		if (self::$timeDelta === null) {
			if ($data = $this->request('get','time')) {
				self::$timeDelta = $data['timestamp'] - $now;
			}
		}

		return (self::$timeDelta || 0) + $now;
	}

	/**
	* curl_multiでHTTP並列リクエスト
	* @param String $method … リクエストメソッド種類
	* @param Array … URL作成するためのリソース・パラメータ配列
	* @param Int $auth (optional) … 認証用定数
	* @return 取得結果(連想配列)
	**/
	public function request($method, $resource, $auth = BlipfotoAPI::AUTH_NONE) {
		
		$url = array();
		$params = array();

		$TIMEOUT = 10; //タイムアウト時間。10秒

		//URL・パラメータ作成
		foreach ($resource as $key => $val) {
			foreach ($val as $key2 => $val2) {
				if($key2=='resource'){
					$url[$key] = $this->buildURL($val2);
				}elseif($key2=='param'){
					$params[$key] = $this->buildParams($val2, $auth);
				}
			}
		}
		for ($i = 0; $i < count($resource); $i++) {
			if ($method == 'get') {
				$url[$i] .= '?' . http_build_query($params[$i]);
			} 
		}	

		// 準備
		$mh = curl_multi_init(); //curl_multiハンドラ用意

		foreach ($url as $u) {
			$ch = curl_init(); //各リクエストに対応するcurlハンドラを、リクエスト分だけ用意
			curl_setopt_array($ch, array(
				CURLOPT_URL            => $u,
				CURLOPT_RETURNTRANSFER => true, //レスポンスが必要な場合はtrue
				CURLOPT_TIMEOUT        => $TIMEOUT,
				CURLOPT_CONNECTTIMEOUT => $TIMEOUT,
			));
			curl_multi_add_handle($mh, $ch); //全てcurl_multiハンドラへ追加
		}

		//リクエスト開始
		do {
			$stat = curl_multi_exec($mh, $running); //multiリクエストスタート
		} while ($stat === CURLM_CALL_MULTI_PERFORM);
		if ( ! $running || $stat !== CURLM_OK) {
			throw new RuntimeException('リクエストが開始出来なかった');
		}

		//レスポンス対応
		$i=0;
		$response = array();
		$result = array();
		$data = array();
		do switch (curl_multi_select($mh, $TIMEOUT)) { //レスポンス処理
			case -1: //select失敗
			case 0:  //タイムアウト
			continue 2; //continueでリトライ
			default: //どれかが成功 or 失敗
				do {
						$stat = curl_multi_exec($mh, $running); //ステータス更新
				} while ($stat === CURLM_CALL_MULTI_PERFORM);

				do if ($raised = curl_multi_info_read($mh, $remains)) {
						$info = curl_getinfo($raised['handle']); //変化があったcurlハンドラ取得
						$response = curl_multi_getcontent($raised['handle']);
						if ($response === false) { //エラー。404など
							echo 'ERROR!!!', PHP_EOL;
						} else { //正常にレスポンス取得
							$result[$i] = json_decode($response, true); //JSON文字列を配列へ変換
							$data[$i] = $result[$i]['data']; //data階層のみ取得
						}
						curl_multi_remove_handle($mh, $raised['handle']);
						curl_close($raised['handle']);
						$i++;
				} while ($remains);
				//select前に全ての処理が終わっていたりすると複数結果が入っていることがあるのでループが必要
		} while ($running);
		curl_multi_close($mh);

		return $data;

	}

	public function get($resource, $auth = BlipfotoAPI::AUTH_NONE) {
		return $this->request('get', $resource, $auth);
	}
}
?>
