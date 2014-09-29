<?php

require_once('user.php');

abstract class API
{
	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	protected $method = '';
	/**
	 * Property: endpoint
	 * The Model requested in the URI. eg: /files
	 */
	protected $endpoint = '';
	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods. eg: /files/process
	 */
	protected $verb = '';
	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args = Array();
	/**
	 * Property: file
	 * Stores the input of the PUT request
	 */
	 protected $file = Null;

	/**
	 * Constructor: __construct
	 * Allow for CORS, assemble and pre-process the data
	 */
	public function __construct($request) {
		header("Access-Control-Allow-Orgin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");

		$this->args = explode('/', rtrim($request, '/'));
		$this->endpoint = array_shift($this->args);
		if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
			$this->verb = array_shift($this->args);
		}

		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				throw new Exception("Unexpected Header");
			}
		}

		switch($this->method) {
		case 'DELETE':
		case 'POST':
			$this->request = $this->_cleanInputs($_POST);
			break;
		case 'GET':
			$this->request = $this->_cleanInputs($_GET);
			break;
		case 'PUT':
			$this->request = $this->_cleanInputs($_GET);
			$this->file = file_get_contents("php://input");
			break;
		default:
			$this->_response('Invalid Method', 405);
			break;
		}
	}

	public function processAPI() {
		if ((int)method_exists($this, $this->endpoint) > 0) {
			return $this->_response($this->{$this->endpoint}($this->args));
		}
		return $this->_response("No Endpoint: $this->endpoint", 404);
	}

	private function _response($data, $status = 200) {
		header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
		return json_encode($data);
	}

	private function _cleanInputs($data) {
		$clean_input = Array();
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$clean_input[$k] = $this->_cleanInputs($v);
			}
		} else {
			$clean_input = trim(strip_tags($data));
		}
		return $clean_input;
	}

	private function _requestStatus($code) {
		$status = array(  
			200 => 'OK',
			404 => 'Not Found',   
			405 => 'Method Not Allowed',
			500 => 'Internal Server Error',
		); 
		return ($status[$code])?$status[$code]:$status[500]; 
	}
}

class MyAPI extends API
{
	private $user;
	public function __construct($request, $origin, $user) {
		parent::__construct($request);
		$this->user=$user;
	}

	 protected function example() {
		if ($this->method == 'GET') {
			return "Your name is ...";
		} else {
			return "Only accepts GET requests";
		}
	 }

	 protected function tracks() {
	 	if ($this->method == 'GET') {
			if(sizeof($this->args)==3){
				$id=$this->args[0];
				$start=$this->args[1];
				$end=$this->args[2];
			}
			else if(sizeof($this->args)==1){
				$id=$this->args[0];
				$start=0;
				$end=10;
			}
			else{
				$id=1;
				$start=0;
				$end=10;
			}

			if($this->user->authenticated){
				$db=new PDO('sqlite:' . 'users/' . $this->user->username . '/gpx.sqlite');
				$sql='SELECT * FROM tracks WHERE type=:id ORDER BY id DESC LIMIT :start, :end';
				$q=$db->prepare($sql);
				$q->execute(array(':id'=>$id, 'start'=>$start, 'end'=>$end));
				$i=0;
				while($r=$q->fetch(PDO::FETCH_ASSOC)){
					$trips[$i++]=$r;
				}
				$db=NULL;
				return $trips;
			}
		}
		else {
			return "Only accepts GET requests";
		}
	}
}

$user = new User('registration_callback');
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
	$_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
	 $API = new MyAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN'], $user);
	echo $API->processAPI();
} catch (Exception $e) {
	echo json_encode(Array('error' => $e->getMessage()));
}

?>