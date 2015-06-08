<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth_model extends CI_Model {
	private $user_id;
	private $table = 'user';
	private $CI;
	private $cookie_name = 'SkyComic_auth';
	const MOBILE_EXPIRE_DAY = 60;

	function __construct () {
		parent::__construct();
		$this->user_id = $this->getUserId();
		$this->CI = & get_instance();
		$this->CI->load->model('user_model', 'user');
	}

	public function invalidIP () {
		return $this->db->select('count(*) AS count')->from('abuse')->where('ip', $_SERVER['REMOTE_ADDR'])->get()->row()->count != 0;
	}
	
	public function logined () {
		if ( $this->getUserId() !== False ) {
			return True;
		} else if ( $this->input->cookie($this->cookie_name) ) {
			$data = json_decode($this->input->cookie($this->cookie_name));
			if ( $this->auth_check($data->id, $data->auth) ) {
				$userdata = $this->CI->user->read_by_id($data->id);
				$this->real_login($userdata);
				return True;
			} else {
				return False;
			}
		} else {
			return False;
		}
	}
	
	public function logout () {
		$this->session->sess_destroy();
		$this->load->helper('cookie');
		delete_cookie($this->cookie_name);
	}

	public function getUserId () {
		$userId = $this->session->userdata('user_id');
		if ($access_token = $this->input->get('access_token')) {
			$device_id = $this->input->get('device_id');
			$result = $this->db->select('user_id,expire_time')
				->from('user_android')
				->where('access_token', $access_token)
				->where('device_id', $device_id)
				->get();
			if ($result->num_rows() > 0) {
				$row = $result->row();
				if ($row->expire_time >= time())
					$userId = $result->row()->user_id;
			}
		}
		return $userId;
	}

	public function mobile_login ($user_id, $mobileDeviceId) {
		if (!empty($user_id) && !empty($mobileDeviceId)) {
			$access_token = md5( uniqid() );
			$this->db->replace("user_android", array(
					"user_id" => $user_id,
					"device_id" => $mobileDeviceId,
					"access_token" => $access_token,
					"expire_time" => time() + self::MOBILE_EXPIRE_DAY * 24 * 60 * 60
				));
			return $access_token;
		}
		return False;
	}
	
	// check if id and pass okay
	public function login ($id, $pw) {
		$userdata = $this->CI->user->read_by_id($id);
		if ( count($userdata) == 1 ) {
			if( $userdata->pw != md5($pw) ) {
				return '密碼錯囉!';
			} elseif ( $userdata->is_mail_ok == '0') {
				return 'Email 還沒有驗證(請檢查是否在垃圾信件中)';
			} elseif ( $userdata->enable == '0' ) {
				return '您的帳號被鎖定了!請聯絡站長!';
			} else {
				$this->real_login($userdata);
				return True;
			}
		} else {
			return '沒有這個帳號';
		}
	}
	
	// in data: sn, id, name, setting
	public function real_login ($data) {
		$data = (object) $data;
		// updating database
		$update = array(
			'lastip' => $_SERVER['REMOTE_ADDR'],
			'lastlogin' => date('Y-m-d H:i:s')
		);
		if ($data->sn != '2') {
			$this->db->where('sn', $data->sn)
				->update('user', $update);
		}
	
		// setting session
		$session = array(
			'user_id' => $data->sn,
			'id' => $data->id,
			'name' => $data->name,
			'nickname' => isset($data->nickname) && $data->nickname ? $data->nickname : $data->name,
			'vip' => $data->vip
			// 'setting' => (isset($data->setting) && $data->setting ? json_decode($data->setting) : new stdClass())
		);
		$this->session->set_userdata($session);
		
		// isNCTU Check
		if ( !$data->isNCTU && isset($_SERVER['REMOTE_ADDR']) && preg_match('/^140\.113/', $_SERVER['REMOTE_ADDR']) ) {
			$this->db->where('sn', $data->sn)
					->update('user', array(
					'isNCTU' => '1'
				));
		}

		if ($data->sn == 2) {
			return ;
		}
		// regenerate auth
		$data->auth = md5( uniqid() );
		$this->db->where('sn', $data->sn)
				 ->update('user', array(
						'auth' => $data->auth
					));
		// setting long expiration cookie
		$cookie = json_encode((object) array(
			'id' => $data->id,
			'auth' => $data->auth
		));
		$this->input->set_cookie(array(
			'name' => $this->cookie_name,
			'value' => $cookie,
			'expire' => 60*60*24*365, // a year
			'domain' => $this->config->item('cookie_domain'),
			'path' => '/',
			'prefix' => '',
			'secure' => False
		));
	}
	
	public function register ($data = False) {
		if ($data === False) { // if openid
			// data = post to use.
			$data = $_POST;
		}
		$data['auth'] = md5( uniqid() );
		$data['setting'] = isset($data['setting']) ? $data['setting'] : array();
		$data['setting']['wheel'] = False;
		$insert = array(
			'id' 		=> $data['id'],
			'pw' 		=> md5($data['pw']),
			'name' 		=> $data['name'],
			'nickname' 	=> $data['nickname'],
			'email' 	=> $data['email'],
			'auth' 		=> $data['auth'],
			'relation'	=> $data['relation'],
			'isNCTU'	=> ( ( substr($_SERVER['REMOTE_ADDR'], 0, 7) == '140.113' ) ? True : False ),
			'lastip' 	=> $_SERVER['REMOTE_ADDR'],
			'lastlogin' => date('Y-m-d H:i:s'),
			'regdate' 	=> date('Y-m-d'),
			'setting' 	=> json_encode($data['setting'])
		);
		if ( isset($data['setting']['oauth']) ) {
			$insert['enable'] = 1;
			$insert['is_mail_ok'] = 1;
		}
		if ( $this->db->insert('user', $insert) ) {
			if ( isset($data['setting']['oauth']) ) {
				return $this->sendmail('email/register_oauth', 'SkyComic 註冊確認信', $data);
			} else {
				return $this->sendmail('email/register', 'SkyComic 註冊確認信', $data);
			}
		} else {
			return False;
		}
	}
	
	// data need name, id, auth, email
	private function sendmail ($view, $subject, $data) {
		$data = (object) $data;
		$this->load->library('phpmailer');
		$ReplyTo	= "support@skycomic.org";
        $ReplyName	= "Support";
        $SetFrom	= "support@skycomic.org";
        $SetName	= "Support";
        $adress		= $data->email;
        $SendName	= $data->name;
		$body 		= $this->CI->load->view($view, $data, True);
		
        $this->phpmailer->AddReplyTo($ReplyTo, $ReplyName);
        $this->phpmailer->SetFrom($SetFrom, $SetName);
        $this->phpmailer->AddAddress($adress, $SendName);
        $this->phpmailer->Subject    = $subject;
		$this->phpmailer->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
        $this->phpmailer->MsgHTML($body);
		
        return $this->phpmailer->Send();
	}
	
	public function change_password ($oldpw, $newpw, $newpw2) {
		if ( $newpw != $newpw2 ) {
			$this->session->set_flashdata('error', '新密碼兩次輸入不同');
			return False;
		}
		$newpw_md5 = md5($newpw);
		$oldpw = $this->CI->user->read_by_user_id($this->user_id)->pw;
		if ( $oldpw != $newpw_md5 ){
			$this->session->set_flashdata('error', '舊密碼輸入錯誤');
			return False;
		} else {
			$update = array(
				'pw' => $newpw_md5
			);
			return $this->db->where('sn', $this->user_id)
							->update('user', $update);
		}
	}

	public function forgotten_password ($email) {
		if ($this->email_check($email) !== True) {
			$data = array();
			$data['auth'] = md5( uniqid() );
			$update = array(
				'auth' => $data['auth']
			);
			$this->db->where('email', $email)
					 ->update('user', $update);
			$userdata = $this->CI->user->read_by_email($email);
			return $this->sendmail('email/forgotten', 'SkyComic 忘記密碼確認信', $userdata);
		} else {
			return False;
		}
	}
	
	public function forgotten_password_complete ($id, $newpw, $auth) {
		if ( $this->auth_check($id, $auth) ) {
			$update = array(
				'pw' => md5($newpw),
				'auth' => md5( uniqid() )
			);
			$this->db->where('id', $id)
					 ->update('user', $update);
			return True;
		} else {
			return False;
		}
	}
	
	public function activate ($id, $auth) {
		$userdata = $this->CI->user->read_by_id($id);
		if ( count($userdata) == 1 && $userdata->auth == $auth ) {
			$update = array(
				'enable' => '1',
				'is_mail_ok' => '1',
				'auth' => md5( uniqid() )
			);
			return $this->db->where('id', $id)
							->update('user', $update);
		} else {
			$this->session->set_flashdata('error', '驗證碼錯誤');
			return False;
		}
	}
	
	public function captcha_check ($captcha) {
		if ($this->session->flashdata('captcha') == $captcha) {
			return True;
		} else {
			return '認證碼輸入錯誤!';
		}
	}
	
	public function username_check ($id) {
		if (count($this->CI->user->read_by_id($id)) == 0) {
			return True;
		} else {
			return '帳號「'.$id.'」已經有人使用囉!';
		}
	}
	
	public function email_check ($email) {
		if (count($this->CI->user->read_by_email($email)) == 0) {
			return True;
		} else {
			return '你已經使用這個Email註冊過了!';
		}
	}
	
	public function auth_check ($id, $auth) {
		$userdata = $this->CI->user->read_by_id($id);
		return count($userdata) != 0 && $userdata->auth == $auth;
	}
	
	/*
	 * Oauth related functions
	 */
		
	private function oauth_redirect_uri ($second = False) {
		$https = $_SERVER["SERVER_PORT"] == 443 ? 's' : '';
		if (!$second) {
			$uri = 'http'. $https .'://'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"];
			$this->session->set_userdata('oauth_redirect_uri', $uri);
			return $uri;
		} else {
			$uri = $this->session->userdata('oauth_redirect_uri');
			$this->session->unset_userdata('oauth_redirect_uri');
			return $uri;
		}
	}
	
	public function oauth_register () {
		global $db, $session;
		$insert = $this->session->userdata('oauth_data');
		$insert['nickname'] = $this->input->post('nickname');
		$insert['relation'] = $this->input->post('relation');
		$insert['id'] = md5( $insert['id'].$insert['site'] );
		$insert['pw'] = md5( $insert['id'].$insert['site'] );
		$insert['setting'] = array('oauth' => True);
		
		if ( !$this->register( $insert ) ) {
			return False;
		} else {
			$user_id = $this->db->insert_id();
		}
		$this->oauth_old_user($insert, $user_id);
		$this->real_login( $this->user->read_by_user_id($user_id) );
		return True;
	}
	
	public function oauth_old_user (&$data, $u_sn) {
		$data['meta'] = isset($data['meta']) ? $data['meta'] : array();
		$data['meta']['rawid'] = $data['id'];
		return $this->db->replace('user_openid', array(
			'u_sn' => $u_sn,
			'open_id' => $data['open_id'],
			'site' => $data['site'],
			'meta' => json_encode($data['meta'])
		));
	}
	
	public function oauth_login ($data, $site) {
		$data['open_id'] = base64_encode($data['id']);
		$data['site'] = $site;
		$sql='SELECT t2.*, t1.* FROM user_openid AS t1, user AS t2'
			.' WHERE open_id = "'. $data['open_id'] .'" AND site = "'. $site .'" AND t1.u_sn = t2.sn LIMIT 1';
		$q = $this->db->query($sql);
		if ( $q->num_rows() != 0 ) {
			$this->real_login($q->row());
			return True;
		} elseif ( count( $old_data = $this->user->read_by_email($data['email']) ) != 0 ) {
			// Already have account, combine userdata.
			$this->oauth_old_user($data, $old_data->sn);
			$this->real_login($q->row());
			$this->session->set_flashdata('success', '帳號聯繫成功!');
			return True;
		} else {
			// No comic account, register
			$this->session->set_userdata('oauth_data', $data);
			return False;
		}
	}
	
	public function oauth ($site) {
		switch ($site) {
			case 'facebook':
			case 'google':
			case 'yahoo':
				return $this->{'oauth_'.$site}();
				break;
			default:
				show_404();
		}
	}
	
	public function oauth_google () {
		if ( count($_GET) == 0 ) {
			$this->load->library('curl');
			// sending oauth request.
			$uri = 'https://accounts.google.com/o/openid2/auth'
				. $this->curl->key_value('openid.ax.mode','fetch_request')
						->key_value('openid.ax.required','username,email,fullname,dateofbirth,gender,postalcode,country,language,timezone,firstname,lastname')
						->key_value('openid.ax.type.country','http://axschema.org/contact/country/home')
						->key_value('openid.ax.type.dateofbirth','http://axschema.org/birthDate')
						->key_value('openid.ax.type.email','http://axschema.org/contact/email')
						->key_value('openid.ax.type.firstname','http://axschema.org/namePerson/first')
						->key_value('openid.ax.type.fullname','http://axschema.org/namePerson')
						->key_value('openid.ax.type.gender','http://axschema.org/person/gender')
						->key_value('openid.ax.type.language','http://axschema.org/pref/language')
						->key_value('openid.ax.type.lastname','http://axschema.org/namePerson/last')
						->key_value('openid.ax.type.postalcode','http://axschema.org/contact/postalCode/home')
						->key_value('openid.ax.type.timezone','http://axschema.org/pref/timezone')
						->key_value('openid.ax.type.username','http://axschema.org/namePerson/friendly')
						->key_value('openid.claimed_id','http://specs.openid.net/auth/2.0/identifier_select')
						->key_value('openid.identity','http://specs.openid.net/auth/2.0/identifier_select')
						->key_value('openid.mode','checkid_setup')
						->key_value('openid.ns','http://specs.openid.net/auth/2.0')
						->key_value('openid.ns.ax','http://openid.net/srv/ax/1.0')
						->key_value('openid.realm','http://'.$_SERVER['HTTP_HOST'].'/')
						->key_value('openid.return_to', $this->oauth_redirect_uri())
						->query_string();
			redirect($uri, 'refresh');		
		} else {
			$this->oauth_redirect_uri(True);
			// receiveing oauth data.
			extract($_GET);
			if ( !isset($openid_identity, $openid_ext1_value_email, $openid_ext1_value_language) ) {
				show_404();
			} else {
				$data = array();
				$data['id'] = $openid_identity;
				$data['email'] = $openid_ext1_value_email;
				
				if($openid_ext1_value_language == 'zh-TW'){
					$data['name'] = $openid_ext1_value_lastname.$openid_ext1_value_firstname;
				} else {
					$data['name'] = $openid_ext1_value_firstname.' '.$openid_ext1_value_lastname;
				}
				return $this->oauth_login($data, 'google');
			}
		}
	}
	
	public function oauth_facebook () {
		$this->load->library('curl');
		$app_id = '153395381407572';
		$app_secrect = '7b0114796615995efaa0d038aa9cd5c7';
		if ( $this->input->get('code') === False ) {
			//Step 1:Get a request token.
			$url='https://www.facebook.com/dialog/oauth'
				. $this->curl->key_value('client_id', $app_id)
							 ->key_value('scope', 'email')
							 ->key_value('redirect_uri', $this->oauth_redirect_uri())
							 ->query_string();
			redirect($url);
		} else {
			//Step 2:Get access token.
			$url = 'https://graph.facebook.com/oauth/access_token';
			$this->curl->key_value('client_id', $app_id)
						->key_value('redirect_uri', $this->oauth_redirect_uri(True))
						->key_value('client_secret', $app_secrect)
						->key_value('code', $this->input->get('code') );
			parse_str($this->curl->url($url)->add()->get());
			if ( !isset($access_token) ) {
				show_404();
				exit;
			}
			$userid = strstr($access_token, '|', True);
			
			//Step 3:Get User data.
			$url = 'https://graph.facebook.com/me';
			$this->curl->key_value('access_token', $access_token);
			$d = json_decode ( $this->curl->url($url)->add()->get() );
			if( !isset($d->id, $d->name, $d->gender, $d->email) ){
				show_404();
				exit;
			} else {
				$data = array(
					'id' => $d->id,
					'email' => $d->email,
					'name' => $d->name,
					'sex' => ($d->gender == 'male' ? 'Male' : 'Female')
				);
				return $this->oauth_login($data, 'facebook');
			}
		}
	}
	
	public function oauth_yahoo () {
		$consumer_key = 'dj0yJmk9c2xsSUhuTVdsSlJBJmQ9WVdrOVVsTnpVMkV6TlRRbWNHbzlNVFl4TWpNMU16ZzJNZy0tJnM9Y29uc3VtZXJzZWNyZXQmeD1lZQ--';
		if ( count($_GET) == 0 ) {
			$this->load->library('curl');
			//Step 1:Get a request token.
			$url='https://open.login.yahooapis.com/openid/op/auth'
				. $this->curl->key_value('openid.ax.mode','fetch_request')
						->key_value('openid.ax.required','username,email,fullname,dateofbirth,gender,postalcode,country,language,timezone,firstname,lastname')
						->key_value('openid.ax.type.country','http://axschema.org/contact/country/home')
						->key_value('openid.ax.type.dateofbirth','http://axschema.org/birthDate')
						->key_value('openid.ax.type.email','http://axschema.org/contact/email')
						->key_value('openid.ax.type.firstname','http://axschema.org/namePerson/first')
						->key_value('openid.ax.type.fullname','http://axschema.org/namePerson')
						->key_value('openid.ax.type.gender','http://axschema.org/person/gender')
						->key_value('openid.ax.type.language','http://axschema.org/pref/language')
						->key_value('openid.ax.type.lastname','http://axschema.org/namePerson/last')
						->key_value('openid.ax.type.postalcode','http://axschema.org/contact/postalCode/home')
						->key_value('openid.ax.type.timezone','http://axschema.org/pref/timezone')
						->key_value('openid.ax.type.username','http://axschema.org/namePerson/friendly')
						->key_value('openid.claimed_id','http://specs.openid.net/auth/2.0/identifier_select')
						->key_value('openid.identity','http://specs.openid.net/auth/2.0/identifier_select')
						->key_value('openid.mode','checkid_setup')
						->key_value('openid.ns','http://specs.openid.net/auth/2.0')
						->key_value('openid.ns.ax','http://openid.net/srv/ax/1.0')
						->key_value('openid.realm','http://'.$_SERVER['HTTP_HOST'].'/')
						->key_value('openid.return_to', $this->oauth_redirect_uri())
						->key_value('openid.oauth.consumer',$consumer_key)
						->query_string();
			redirect($url);
		} else {
			$this->oauth_redirect_uri(True);
			extract($_GET);
			if ( !isset($openid_identity, $openid_ax_value_email, $openid_ax_value_fullname, $openid_ax_value_language, $openid_ax_value_gender) ) {
				show_404();
			} else {
				$data = array(
					'id' => $openid_identity,
					'email' => $openid_ax_value_email,
					'name' => $openid_ax_value_fullname,
					'sex' => ($openid_ax_value_gender == 'M' ? 'Male' : 'Female')
				);
				return $this->oauth_login($data, 'yahoo');
			}
		}
	}
}
