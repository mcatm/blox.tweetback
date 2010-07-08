<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* ------------------------------------

Tweetback Lib
made by HAMADA, Satoshi

blox setting
- tweetback_api : http://backtweets.com/search.json?q=%s&key=%s
- tweetback_devid
- tweetback_query

------------------------------------- */

class Tweetback extends Blox {
	
	var $apiurl	= "";
	var $devid	= "";
	var $query	= "artfolio.co.jp";
	
	function crawl($param = array()) {
		$CI =& get_instance();
		$CI->load->library('ext/twitter');
		
		$url = (!empty($this->query)) ? $this->query : base_url();
		
		if (empty($this->devid)) return false;
		$api_url = sprintf($this->apiurl, urlencode($url), $this->devid);
		
		$json = $CI->output->get_cache($api_url);
		if (!$json) {
			$json = @file_get_contents($api_url);
			$CI->output->set_cache($api_url, $json, 1);
		}
		$tweet = json_decode($json);
		
		if (isset($tweet->tweets)) {
			$CI->load->library(array('post', 'user'));
			$CI->load->helper('date');
			$now = now();
			print_r($tweet);exit;
			foreach ($tweet->tweets as $t) {
				$CI->db->where('post_app_id', $t->tweet_id);
				if ($CI->db->count_all_results(DB_TBL_POST) == 0) {//過去にポストされたかの確認
					$arr = array(
						'post_app'			=> 'twitter',
						'post_app_id'		=> $t->tweet_id,
						'post_text'			=> $t->tweet_text,
						'post_type'			=> 1,
						'post_createdate'	=> $t->tweet_created_at,
						'post_modifydate'	=> $t->tweet_created_at
					);
					print_r($arr);
					$post_id = $CI->post->_set_post($arr);
					
					$user_id = $this->set_user($t->tweet_from_user_id);//ユーザーの確認
					if (!empty($user_id)) {
						//著者登録
						if (isset($user_id) && $user_id > 0) {
							$CI->linx->set('post2user', array(
								'a'		=> $post_id,
								'b'		=> $user_id,
								'status'	=> 'main'
							));
						}
					}
				}
			}
		}
	}
	
	function set_user($user_id) {
		$CI =& get_instance();
		$CI->load->library('ext/twitter');
		
		#if (!$CI->setting->get('twitter_access_token') || !$CI->setting->get('twitter_access_token_secret')) return false;
		#if (!$CI->auth->oauth($CI->setting->get('twitter_access_token'), $CI->setting->get('twitter_access_token_secret'), base_url())) return false;
		
		$u = $CI->linx->get('user2twitter', array(
			'b'		=> $user_id
		));
		
		if (empty($u)) {//ユーザー未登録
			$result = $CI->twitter->call('statuses/show', array(
				'id'		=> $user_id
			));
			#$result = $CI->twitter->call('statuses/friends_timeline', array('count' => 2));
			#exit;
			
			if (!isset($result->result)) return false;//ユーザーデータ取得出来なかった場合、falseを返す
			print_r($result);
			//-------------リファクタリング！！！！twitterよりユーザデータを取得したい
			/*$anonymous = $CI->user->get_anonymous();
			$arr = array(
				'user_name'		=> $t->tweet_from_user,
				'user_account'	=> $t->tweet_from_user,
				'user_type'		=> $anonymous[0]['id'],
				'user_createdate'	=> $now,
				'user_modifydate'	=> $now,
				'user_actiondate'	=> $now
			);
			//-------------リファクタリング！！！！
			
			$CI->db->insert(DB_TBL_USER, $arr);
			$user_id = $CI->db->insert_id();
			
			$CI->linx->set('user2twitter', array(
				'a'			=> $user_id,
				'b'			=> $t->tweet_from_user_id,
				'status'	=> $t->tweet_profile_image_url
			));*/
		} else {//ユーザー登録済
			$user = $CI->user->get(array('id' => $u[0]['a'], 'stack' => false));
			$user_id = $user[0]['id'];
		}
		return $user_id;
	}
	
	function Tweetback() {
		$this->init();
	}
	
	function init() {
		$CI =& get_instance();
		if (empty($this->apiurl))	$this->apiurl	= $CI->setting->get('tweetback_api');
		if (empty($this->devid))	$this->devid	= $CI->setting->get('tweetback_devid');
		if (empty($this->query))	$this->query	= $CI->setting->get('tweetback_query');
	}
}

?>