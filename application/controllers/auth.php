<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth extends CI_Controller {

	function __construct () {
		parent::__construct();
		$this->load->library('form_validation');
	}
	
	public function index () {
		redirect('auth/login');
	}
	
	public function logout () {
		$this->auth_model->logout();
		redirect('auth/login');
	}

	private function login_redirect () {
		$this->_mobile_login();
		if ( ($referer = $this->session->userdata('referer') ) ) {
			$this->session->unset_userdata('referer');
			redirect($referer);
		} else {
			redirect('main');
		}
	}
	
	public function logined () {
		echo $this->auth_model->logined() ? "1" : "0";
	}
	
	public function login ($mobileDeviceId = false) {
		$this->setMobileSession($mobileDeviceId);
		if ( !$this->_is_mobile_login() && $this->auth_model->logined() ) {
			redirect('main');
		}
		if ( count($_POST) === 0 ) {
			$this->sandvich
				 ->partial('content', 'partial/auth/login', array(
					'form' => array(),
					'default' => 'login'
				 ))
				 ->render('layout/login', array(
					'js' => 'login'
				 ));
		} else {
			$id = $this->input->post('id');
			$pw = $this->input->post('pw');

			$errorMsg = $this->auth_model->login($id, $pw);
			if ( True !== $errorMsg ) {
				$this->session->set_flashdata('error', $errorMsg);
				redirect('auth/login');
			} else {
				$this->login_redirect();
			}
		}
	}
	
	public function register () {
		$this->form_validation->set_rules('id', '帳號', 'trim|required|alpha_numeric|min_length[3]|max_length[20]|callback__username_check');
		$this->form_validation->set_rules('pw', '密碼', 'required|min_length[8]|max_length[20]');
		$this->form_validation->set_rules('pw2', '密碼', 'matches[pw]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[254]|callback__email_check');
		$this->form_validation->set_rules('nickname', '暱稱', 'trim|required|min_length[2]|max_length[254]');
		$this->form_validation->set_rules('name', '姓名', 'trim|required|min_length[2]|max_length[20]');
		$this->form_validation->set_rules('relation', '從哪裡知道這個網站', 'trim|required|min_length[2]|max_length[50]');
		$this->form_validation->set_rules('captcha', '認證碼', 'trim|required|callback__captcha_check');
		
		if ( $this->form_validation->run() === True && $this->auth_model->register()) {
			$this->session->set_flashdata('success', '恭喜您完成註冊!請開啟您的email收信驗證之後才可以使用喔，請特別注意若沒收到信請到垃圾信夾中搜尋!');
			redirect('auth/login');
		} else {
			$this->sandvich
				 ->partial('content', 'partial/auth/login', array(
					'form' => $_POST,
					'default' => 'register'
				 ))
				 ->render('layout/login', array(
					'js' => 'register'
				 ));
		}
	}

	public function forgotten_password () {
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[254]|callback__email_check_false');
		if ( $this->form_validation->run() === True && $this->auth_model->forgotten_password( $this->input->post('email') ) ) {
			$this->session->set_flashdata('success', '已經發送密碼重設信件給你囉!快去收信吧!');
			redirect('auth/login');
		} else {
			$this->sandvich
				 ->partial('content', 'partial/auth/forgotten_password')
				 ->render('layout/login');
		}
	}
	
	public function forgotten_password_complete ($id, $auth) {
		if ( $this->auth_model->auth_check($id, $auth) ) {
			$this->form_validation->set_rules('pw', '密碼', 'required|min_length[8]|max_length[20]');
			$this->form_validation->set_rules('pw2', '密碼', 'matches[pw]');
			if ( $this->form_validation->run() === True
			&&   $this->auth_model->forgotten_password_complete( $id, $this->input->post('pw'), $auth ) ) {
				$this->session->set_flashdata('success', '成功重新設定密碼囉!');
				redirect('auth/login');
			} else {
				$this->sandvich
					 ->partial('content', 'partial/auth/forgotten_password_reset', array(
						'id'=>$id,
						'auth'=>$auth
					 ))
					 ->render('layout/login');
			}
		} else {
			$this->session->set_flashdata('error', '忘記密碼: 認證碼錯誤');
			redirect('auth/login');
		}
	}
	
	public function activate ($id, $auth) {
		if ( $this->auth_model->activate($id, $auth) ) {
			$this->session->set_flashdata('success', '恭喜您完成認證!可以開始享受SkyComic囉!');
			redirect('auth/login');
		} else {
			$this->session->set_flashdata('error', 'Oops!認證碼錯誤!');
			redirect('auth/login');
		}
	}
	
	public function captcha ($rand=False) {
		$this->load->library('simplecaptcha');
		$this->simplecaptcha->CreateImage();
	}

	/*
	 * form_validation related functions
	 */

	public function _username_check ($id) {
		if ( ( $msg = $this->auth_model->username_check($id) ) !== True ) {
			$this->form_validation->set_message('_username_check', $msg);
			return False;
		} else {
			return True;
		}
	}
	
	public function _email_check ($email) {
		if ( ( $msg = $this->auth_model->email_check($email) ) !== True ) {
			$this->form_validation->set_message('_email_check', $msg);
			return False;
		} else {
			return True;
		}
	}

	public function _email_check_false ($email) {
		if ( ( $msg = $this->auth_model->email_check($email) ) === True ) {
			$this->form_validation->set_message('_email_check_false', "找不到這個Email!");
			return False;
		} else {
			return True;
		}
	}

	public function _captcha_check ($captcha) {
		if ( ( $msg = $this->auth_model->captcha_check($captcha) ) !== True ) {
			$this->form_validation->set_message('_captcha_check', $msg);
			return False;
		} else {
			return True;
		}
	}
	
	
	/*
	 * Openid related functions
	 */
	
	public function oauth ($site) {
		if ( False === $this->auth_model->oauth($site) ) {
			redirect('auth/oauth_register');
		} else {
			$this->login_redirect();
		}
	}
	
	public function oauth_register () {
		if ( ALLOW_REGISTER ) {
			$this->form_validation->set_rules('nickname', '暱稱', 'trim|required|min_length[2]|max_length[254]');
			$this->form_validation->set_rules('relation', '從哪裡知道這個網站', 'trim|required|min_length[2]|max_length[50]');
			
			if ( $this->form_validation->run() === True && $this->auth_model->oauth_register()) {
				$this->session->unset_userdata('oauth_data');
				$this->session->set_flashdata('success', '恭喜您完成註冊!');
				$this->_mobile_login();
				redirect('main');
			} else {
				$this->sandvich
					 ->partial('content', 'partial/auth/oauth_register', $this->session->userdata('oauth_data'))
					 ->render('layout/login');
			}
		} else {
			$this->sandvich
				 ->partial('content', 'partial/auth/not_allow_register')
				 ->render('layout/login');
		}
	}

	public function mobile_success () {
		$this->session->sess_destroy();
		echo 'login successfully.';
	}

	private function setMobileSession ($mobileDeviceId) {
		if (False !== $mobileDeviceId)
			$this->session->set_userdata('mobileDeviceId', $mobileDeviceId);
	}
	
	private function _mobile_login () {
		if ($mobileDeviceId = $this->session->userdata('mobileDeviceId')) {
			$this->session->unset_userdata('mobileDeviceId');
			$user_id = $this->session->userdata('user_id');
			$access_token = $this->auth_model->mobile_login($user_id, $mobileDeviceId);
			redirect('auth/mobile_success#access_token=' . $access_token);
		}
	}

	private function _is_mobile_login () {
		return $this->session->userdata('mobileDeviceId');
	}
}

/* End of file auth.php */