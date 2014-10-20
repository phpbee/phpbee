<?php
class oauth2_twitter implements oauth2_profile {
	/*
	http://habrahabr.ru/post/114955/
	*/
	function __construct($config) {
		$this->config=$config;
		load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'lib'.DIRECTORY_SEPARATOR.'twitter'.DIRECTORY_SEPARATOR.'twitteroauth'.DIRECTORY_SEPARATOR.'twitteroauth.php');
		load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'lib'.DIRECTORY_SEPARATOR.'twitter'.DIRECTORY_SEPARATOR.'config.php');

	}
	function authorize($callback) {

		$connection = new TwitterOAuth($this->config->CONSUMER_KEY, $this->config->APP_SECRET);
		$request_token = $connection->getRequestToken($callback);
		gs_session::save($request_token,'oauth2_twitter_token');
		$url=$connection->getAuthorizeURL($request_token);
		return $url;
	}
	function token($data) {
		$request_token=gs_session::load('oauth2_twitter_token');
		$connection = new TwitterOAuth($this->config->CONSUMER_KEY, $this->config->APP_SECRET,$request_token['oauth_token'],$request_token['oauth_token_secret']);
		$access_token = $connection->getAccessToken($data['oauth_verifier']);
		return $connection;
	}
	function profile($connection) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'twitter','email'=>null);
		$d=$connection->get('account/verify_credentials');
		if (!$d || !$d->id) return $ret;
		$ret['uid']='tw-'.$d->id;
		list($ret['first_name'],$ret['last_name'])=array_map(trim,explode(' ',$d->name,2));
		return $ret;
	}
	function friends($token) {
		return array();
	}
}
