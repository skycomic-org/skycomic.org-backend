<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sandvich
{
    protected $layout;
    protected $partials = array();
    protected $json = array();
    protected $instance;

    public function __construct () {
        $this->instance =& get_instance();
    }

    public function partial ($partial = '', $path = '', $locals = array()) {
        $this->partials[$partial] = $this->instance->load->view($path, $locals, TRUE);
        return $this;
    }

    public function json ($json = array()) {
        $this->json = $json;
        return $this;
    }

    public function render ($layout = '', $locals = array(), $return = FALSE) {
        if ($this->instance->input->is_ajax_request()) {
            $json = array();
            foreach ($this->json as $key) {
                $json[$key] = $this->partials[$key];
			}
            echo json_encode($json);
        } else {
            return $this->instance->load->view($layout, array_merge($this->partials, $locals), $return);
        }

    }
}