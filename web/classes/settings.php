<?

require_once("framework.php");

class Setting extends Model
{
	public static function variables()
	{
		return array(
					"setting_id" => new Variable("serial"),
					"param" => new Variable("text"),
					"value" => new Variable("text"),
					"description" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public static function defaultObject()
	{
		$params = array('vm'=>array('external/nodata/leavemaildb.php','script used for leaving a voicemail message'), "version"=>'1');
		$setting = new Setting;
		$nr_settings = $setting->fieldSelect("count(*)");
		if ($nr_settings>=count($params))
			return true;

		foreach($params as $key=>$value) {
			$description = NULL;
			if(is_array($value))
			{
				$description = $value[1];
				$value = $value[0];
			}
			$setting = new Setting;
			$setting->param = $key;
			$setting->select(array("param"=>$key));
			if($setting->setting_id) {
				if($setting->value != $value) {
					$setting->value = $value;
					$setting->description = $description;
					$setting->update();
				}
			}else{
				$setting->description = $description;
				$setting->value = $value;
				$setting->insert();
			}
		}
		return true;
	}
}

?>