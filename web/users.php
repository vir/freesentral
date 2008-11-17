<?

require_once("framework.php");

class User extends Model
{
	public static function variables()
	{
		return array(
					"user_id" => new Variable("serial"),
					"username" => new Variable("text"),
					"password" => new Variable("text"),
					"firstname" => new Variable("text"),
					"lastname" => new Variable("text"),
					"email" => new Variable("text"),
					"description" => new Variable("text")
				);
	}

	function __construct()
	{
		parent::__construct();
	}

	public function login()
	{
		if (!$this->username || !$this->password)
			return NULL;
		$users = Model::selection("user", array("username"=>$this->username, "password"=>$this->password));
		if(count($users) == 1) 
		{
			foreach($users[0] as $key=>$value)
			{
				$this->{$key} = $users[0]->{$key};
			}
			self::writeLog("admin ".$this->username. " logged in");
			return true;
		}else{
			self::writeLog("failled attempt to log in as admin: ".$this->username);
			return false;
		}
	}

	public static function defaultObject()
	{
		$username = 'admin';
		$password = 'admin';
		$user = new User;
		$nr_users = $user->fieldSelect("count(*)");
		if ($nr_users)
			return true;
		$user->username = $username;
		$user->password = $password;
		$res = $user->insert();
		return $res[0];
	}

	// show that this is the object that will be the peformer for logs
	public function isPerformer()
	{
		return array("performer_id"=>"user_id", "performer"=>"username");
	}
}

?>