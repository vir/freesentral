<?
require_once("framework.php");

class Key extends Model
{
	public static function variables()
	{
		return array(
					"key_id" => new Variable("serial", "!null"),
					"key" => new Variable("text", "!null"),
					"prompt_id" => new Variable("serial", "!null","prompts"),
					"destination" => new Variable("text", "!null"),
					"description" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	// use this function so that name of the table is not automatically "keies"
	public function getTableName()
	{
		return "keys";
	}

	public function setObj($params)
	{
		$this->key = field_value("key", $params);
		if(($msg = $this->objectExists()))
			return array(false, (is_numeric($msg)) ? "This key ".$this->key." is already defined." : $msg);
		if(!is_numeric($this->key)) 
			return array(false, "Field 'Key' must be numeric.");
		if($this->key_id)
			$this->select();
		$this->setParams($params);
		return parent::setObj($params);
	}
}
?>